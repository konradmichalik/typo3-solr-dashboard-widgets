<?php

declare(strict_types=1);

/*
 * This file is part of the "solr_dashboard_widgets" TYPO3 CMS extension.
 *
 * (c) 2026 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KonradMichalik\SolrDashboardWidgets\DataProvider;

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Reads operational metrics from Solr's `/admin/metrics` endpoint (plus the
 * node-level system info for the Solr version).
 *
 * One HTTP call per reachable host covers JVM memory, per-core query
 * performance, and per-core cache statistics. Responses are cached for the
 * duration of the request so multiple widgets on the same dashboard share a
 * single fetch per host.
 */
final class SolrMetricsDataProvider
{
    /** @var array<string, array|null> keyed by "scheme://host:port" */
    private array $metricsCache = [];

    /** @var array<string, ?string> keyed by "scheme://host:port" */
    private array $versionCache = [];

    /** @var list<array{hostUri: string, siteLabel: string, core: string}>|null */
    private ?array $coresCache = null;

    public function __construct(
        private readonly SiteRepository $siteRepository,
        private readonly ConnectionManager $connectionManager,
        private readonly RequestFactory $requestFactory,
    ) {}

    /**
     * @return array{reachable: bool, usedBytes: int, maxBytes: int, usedPercent: float}|null
     */
    public function getJvmMemory(): ?array
    {
        foreach ($this->getUniqueHosts() as $hostUri) {
            $metrics = $this->fetchMetrics($hostUri);
            if ($metrics === null) {
                continue;
            }
            $jvm = $metrics['metrics']['solr.jvm'] ?? null;
            if (!is_array($jvm) || !isset($jvm['memory.heap.used'], $jvm['memory.heap.max'])) {
                continue;
            }
            $used = (int)$jvm['memory.heap.used'];
            $max = (int)$jvm['memory.heap.max'];
            return [
                'reachable' => true,
                'usedBytes' => $used,
                'maxBytes' => $max,
                'usedPercent' => $max > 0 ? ($used / $max) * 100.0 : 0.0,
            ];
        }
        return null;
    }

    /**
     * Aggregated query performance across all reachable cores.
     *
     * @return array{reachable: bool, totalCount: int, perMinute: float, meanMs: float, p95Ms: float, perCore: list<array{siteLabel: string, core: string, count: int, meanMs: float, p95Ms: float}>}
     */
    public function getQueryPerformance(): array
    {
        $perCore = [];
        $totalCount = 0;
        $perMinute = 0.0;
        $weightedMeanNumerator = 0.0;
        $weightedMeanDenominator = 0;
        $maxP95 = 0.0;
        $anyReachable = false;

        foreach ($this->getCores() as $core) {
            $metrics = $this->fetchMetrics($core['hostUri']);
            if ($metrics === null) {
                continue;
            }
            $coreMetrics = $metrics['metrics']['solr.core.' . $core['core']] ?? null;
            if (!is_array($coreMetrics)) {
                continue;
            }
            $requestTimes = $coreMetrics['QUERY./select.requestTimes'] ?? null;
            if (!is_array($requestTimes)) {
                continue;
            }
            $anyReachable = true;

            $count = (int)($requestTimes['count'] ?? 0);
            $rate1m = (float)($requestTimes['1minRate'] ?? 0.0);
            $meanMs = (float)($requestTimes['mean_ms'] ?? 0.0);
            $p95Ms = (float)($requestTimes['p95_ms'] ?? 0.0);

            $totalCount += $count;
            $perMinute += $rate1m * 60.0;
            if ($count > 0) {
                $weightedMeanNumerator += $meanMs * $count;
                $weightedMeanDenominator += $count;
            }
            if ($p95Ms > $maxP95) {
                $maxP95 = $p95Ms;
            }

            $perCore[] = [
                'siteLabel' => $core['siteLabel'],
                'core' => $core['core'],
                'count' => $count,
                'meanMs' => $meanMs,
                'p95Ms' => $p95Ms,
            ];
        }

        return [
            'reachable' => $anyReachable,
            'totalCount' => $totalCount,
            'perMinute' => $perMinute,
            'meanMs' => $weightedMeanDenominator > 0 ? $weightedMeanNumerator / $weightedMeanDenominator : 0.0,
            'p95Ms' => $maxP95,
            'perCore' => $perCore,
        ];
    }

    /**
     * Aggregated cache hit rates across all reachable cores.
     *
     * @return array{reachable: bool, caches: list<array{label: string, metric: string, hitRatio: float, lookups: int, hits: int}>}
     */
    public function getCacheHitRates(): array
    {
        $caches = [
            ['label' => 'Filter Cache', 'metric' => 'CACHE.searcher.filterCache'],
            ['label' => 'Query Result Cache', 'metric' => 'CACHE.searcher.queryResultCache'],
            ['label' => 'Document Cache', 'metric' => 'CACHE.searcher.documentCache'],
        ];

        $anyReachable = false;
        $result = [];

        foreach ($caches as $cache) {
            $totalLookups = 0;
            $totalHits = 0;

            foreach ($this->getCores() as $core) {
                $metrics = $this->fetchMetrics($core['hostUri']);
                if ($metrics === null) {
                    continue;
                }
                $coreMetrics = $metrics['metrics']['solr.core.' . $core['core']][$cache['metric']] ?? null;
                if (!is_array($coreMetrics)) {
                    continue;
                }
                $anyReachable = true;
                // Prefer cumulative metrics (more stable over time) when present.
                $totalLookups += (int)($coreMetrics['cumulative_lookups'] ?? $coreMetrics['lookups'] ?? 0);
                $totalHits += (int)($coreMetrics['cumulative_hits'] ?? $coreMetrics['hits'] ?? 0);
            }

            $result[] = [
                'label' => $cache['label'],
                'metric' => $cache['metric'],
                'hitRatio' => $totalLookups > 0 ? ($totalHits / $totalLookups) * 100.0 : 0.0,
                'lookups' => $totalLookups,
                'hits' => $totalHits,
            ];
        }

        return [
            'reachable' => $anyReachable,
            'caches' => $result,
        ];
    }

    /**
     * Returns the Solr server version reported by the first reachable host.
     *
     * Uses the node-level `/admin/info/system` endpoint (single HTTP call,
     * cached per-request). Returns null if no host responds.
     */
    public function getSolrVersion(): ?string
    {
        foreach ($this->getUniqueHosts() as $hostUri) {
            if (array_key_exists($hostUri, $this->versionCache)) {
                if ($this->versionCache[$hostUri] !== null) {
                    return $this->versionCache[$hostUri];
                }
                continue;
            }

            $url = $hostUri . '/solr/admin/info/system?wt=json';
            try {
                $response = $this->requestFactory->request($url, 'GET', [
                    'timeout' => 3,
                    'connect_timeout' => 2,
                ]);
                if ($response->getStatusCode() !== 200) {
                    $this->versionCache[$hostUri] = null;
                    continue;
                }
                $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                $this->versionCache[$hostUri] = null;
                continue;
            }

            $version = (string)($data['lucene']['solr-spec-version'] ?? $data['lucene']['solr-impl-version'] ?? '');
            $this->versionCache[$hostUri] = $version !== '' ? $version : null;
            if ($this->versionCache[$hostUri] !== null) {
                return $this->versionCache[$hostUri];
            }
        }
        return null;
    }

    /**
     * @return list<array{hostUri: string, siteLabel: string, core: string}>
     */
    private function getCores(): array
    {
        if ($this->coresCache !== null) {
            return $this->coresCache;
        }

        $cores = [];
        foreach ($this->siteRepository->getAvailableSites() as $site) {
            try {
                $connections = $this->connectionManager->getConnectionsBySite($site);
            } catch (\Throwable) {
                continue;
            }
            foreach ($connections as $connection) {
                $endpoint = $connection->getEndpoint('read');
                $cores[] = [
                    'hostUri' => sprintf(
                        '%s://%s:%d',
                        $endpoint->getScheme(),
                        $endpoint->getHost(),
                        $endpoint->getPort()
                    ),
                    'siteLabel' => $site->getLabel(),
                    'core' => $endpoint->getCore() ?? '',
                ];
            }
        }

        return $this->coresCache = $cores;
    }

    /**
     * @return list<string>
     */
    private function getUniqueHosts(): array
    {
        $hosts = [];
        foreach ($this->getCores() as $core) {
            $hosts[$core['hostUri']] = true;
        }
        return array_keys($hosts);
    }

    /**
     * @return array<mixed>|null
     */
    private function fetchMetrics(string $hostUri): ?array
    {
        if (array_key_exists($hostUri, $this->metricsCache)) {
            return $this->metricsCache[$hostUri];
        }

        $url = $hostUri . '/solr/admin/metrics?wt=json&group=jvm,core&prefix=memory.heap,QUERY./select.requestTimes,CACHE.searcher';

        try {
            $response = $this->requestFactory->request($url, 'GET', [
                'timeout' => 3,
                'connect_timeout' => 2,
            ]);
            if ($response->getStatusCode() !== 200) {
                return $this->metricsCache[$hostUri] = null;
            }
            $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $this->metricsCache[$hostUri] = null;
        }

        return $this->metricsCache[$hostUri] = is_array($data) ? $data : null;
    }
}
