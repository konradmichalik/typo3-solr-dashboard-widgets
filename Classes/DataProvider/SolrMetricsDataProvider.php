<?php

declare(strict_types=1);

/*
 * This file is part of the "typo3_solr_dashboard_widgets" TYPO3 CMS extension.
 *
 * (c) 2026 Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KonradMichalik\SolrDashboardWidgets\DataProvider;

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use Throwable;
use TYPO3\CMS\Core\Http\RequestFactory;

use function array_key_exists;
use function is_array;
use function rtrim;
use function strlen;

/**
 * SolrMetricsDataProvider.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final class SolrMetricsDataProvider
{
    /** @var array<string, array<string, mixed>|null> keyed by Solr base URI */
    private array $metricsCache = [];

    /** @var array<string, ?string> keyed by Solr base URI */
    private array $versionCache = [];

    /** @var list<array{solrBaseUri: string, siteLabel: string, core: string, auth: array{username: ?string, password: ?string}}>|null */
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
        foreach ($this->getUniqueHosts() as $solrBaseUri => $auth) {
            $metrics = $this->fetchMetrics($solrBaseUri, $auth);
            if (null === $metrics) {
                continue;
            }
            $jvm = $metrics['metrics']['solr.jvm'] ?? null;
            if (!is_array($jvm) || !isset($jvm['memory.heap.used'], $jvm['memory.heap.max'])) {
                continue;
            }
            $used = (int) $jvm['memory.heap.used'];
            $max = (int) $jvm['memory.heap.max'];

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
            $requestTimes = $this->fetchCoreMetric($core, 'QUERY./select.requestTimes');
            if (null === $requestTimes) {
                continue;
            }
            $anyReachable = true;

            $count = (int) ($requestTimes['count'] ?? 0);
            $meanMs = (float) ($requestTimes['mean_ms'] ?? 0.0);
            $p95Ms = (float) ($requestTimes['p95_ms'] ?? 0.0);

            $totalCount += $count;
            $perMinute += (float) ($requestTimes['1minRate'] ?? 0.0) * 60.0;
            $weightedMeanNumerator += $meanMs * $count;
            $weightedMeanDenominator += $count;
            $maxP95 = max($maxP95, $p95Ms);

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
     * @return array{reachable: bool, caches: list<array{label: string, metric: string, tooltip: string, hitRatio: float, lookups: int, hits: int}>}
     */
    public function getCacheHitRates(): array
    {
        $caches = [
            [
                'label' => 'Filter Cache',
                'metric' => 'CACHE.searcher.filterCache',
                'tooltip' => 'Caches results of filter queries (fq=...). A high hit ratio means repeated filters (e.g. facets, access restrictions) are served from memory instead of re-running the query against the index.',
            ],
            [
                'label' => 'Query Result Cache',
                'metric' => 'CACHE.searcher.queryResultCache',
                'tooltip' => 'Caches the ordered list of document IDs returned by a search query. A high hit ratio means identical queries (same q, sort and filters) are answered directly from cache.',
            ],
            [
                'label' => 'Document Cache',
                'metric' => 'CACHE.searcher.documentCache',
                'tooltip' => 'Caches the stored fields of individual documents. A high hit ratio helps when the same documents are accessed repeatedly — e.g. pagination or recurring result sets.',
            ],
        ];

        $anyReachable = false;
        $result = [];

        foreach ($caches as $cache) {
            [$totalLookups, $totalHits, $reachable] = $this->aggregateCacheMetric($cache['metric']);
            $anyReachable = $anyReachable || $reachable;
            $result[] = [
                'label' => $cache['label'],
                'metric' => $cache['metric'],
                'tooltip' => $cache['tooltip'],
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
        foreach ($this->getUniqueHosts() as $solrBaseUri => $auth) {
            if (!array_key_exists($solrBaseUri, $this->versionCache)) {
                $this->versionCache[$solrBaseUri] = $this->fetchSolrVersion($solrBaseUri, $auth);
            }
            if (null !== $this->versionCache[$solrBaseUri]) {
                return $this->versionCache[$solrBaseUri];
            }
        }

        return null;
    }

    /**
     * @param array{username: ?string, password: ?string} $auth
     */
    private function fetchSolrVersion(string $solrBaseUri, array $auth): ?string
    {
        try {
            $response = $this->requestFactory->request($solrBaseUri.'admin/info/system?wt=json', 'GET', $this->buildRequestOptions($auth));
            if (200 !== $response->getStatusCode()) {
                return null;
            }
            $data = json_decode((string) $response->getBody(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        $version = (string) ($data['lucene']['solr-spec-version'] ?? $data['lucene']['solr-impl-version'] ?? '');

        return '' !== $version ? $version : null;
    }

    /**
     * Sum cumulative `lookups` and `hits` for a given cache metric across all reachable cores.
     *
     * @return array{0: int, 1: int, 2: bool} tuple of [totalLookups, totalHits, anyReachable]
     */
    private function aggregateCacheMetric(string $metricKey): array
    {
        $totalLookups = 0;
        $totalHits = 0;
        $anyReachable = false;

        foreach ($this->getCores() as $core) {
            $cacheMetrics = $this->fetchCoreMetric($core, $metricKey);
            if (null === $cacheMetrics) {
                continue;
            }
            $anyReachable = true;
            $totalLookups += (int) ($cacheMetrics['cumulative_lookups'] ?? $cacheMetrics['lookups'] ?? 0);
            $totalHits += (int) ($cacheMetrics['cumulative_hits'] ?? $cacheMetrics['hits'] ?? 0);
        }

        return [$totalLookups, $totalHits, $anyReachable];
    }

    /**
     * @return list<array{solrBaseUri: string, siteLabel: string, core: string, auth: array{username: ?string, password: ?string}}>
     */
    private function getCores(): array
    {
        if (null !== $this->coresCache) {
            return $this->coresCache;
        }

        $cores = [];
        foreach ($this->siteRepository->getAvailableSites() as $site) {
            try {
                $connections = $this->connectionManager->getConnectionsBySite($site);
            } catch (Throwable) {
                continue;
            }
            foreach ($connections as $connection) {
                $endpoint = $connection->getEndpoint('read');
                $core = $endpoint->getCore() ?? '';
                $cores[] = [
                    'solrBaseUri' => $this->deriveSolrBase($endpoint->getCoreBaseUri(), $core),
                    'siteLabel' => $site->getLabel(),
                    'core' => $core,
                    'auth' => ['username' => $endpoint->getAuthentication()['username'] ?? null, 'password' => $endpoint->getAuthentication()['password'] ?? null],
                ];
            }
        }

        return $this->coresCache = $cores;
    }

    /**
     * Fetch a specific metric from a core's metrics group.
     *
     * @param array{solrBaseUri: string, siteLabel: string, core: string, auth: array{username: ?string, password: ?string}} $core
     *
     * @return array<string, mixed>|null
     */
    private function fetchCoreMetric(array $core, string $metricKey): ?array
    {
        $metrics = $this->fetchMetrics($core['solrBaseUri'], $core['auth']);
        if (null === $metrics) {
            return null;
        }

        $coreGroup = $this->findCoreMetrics($metrics['metrics'] ?? [], $core['core']);
        $metric = $coreGroup[$metricKey] ?? null;

        return is_array($metric) ? $metric : null;
    }

    /**
     * Find the metrics group for a given core name.
     *
     * Standalone Solr uses `solr.core.{core}`, SolrCloud appends
     * `.shard{N}.replica_n{N}`. We match by prefix and return the
     * first matching group.
     *
     * @param array<string, mixed> $allMetrics
     *
     * @return array<string, mixed>|null
     */
    private function findCoreMetrics(array $allMetrics, string $core): ?array
    {
        $prefix = 'solr.core.'.$core;

        foreach ($allMetrics as $key => $value) {
            if (($key === $prefix || str_starts_with($key, $prefix.'.')) && is_array($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array{username: ?string, password: ?string} $auth
     *
     * @return array<string, mixed>
     */
    private function buildRequestOptions(array $auth): array
    {
        $options = [
            'timeout' => 3,
            'connect_timeout' => 2,
        ];

        if ('' !== ($auth['username'] ?? '')) {
            $options['auth'] = [$auth['username'], $auth['password'] ?? ''];
        }

        return $options;
    }

    /**
     * Derive the Solr base URI (e.g. `http://host:8983/solr/`) from the
     * core base URI returned by Solarium. This is more reliable than
     * `getServerUri()` because Solarium may internally prepend the
     * context path (`/solr/`) only when building the core URI.
     */
    private function deriveSolrBase(string $coreBaseUri, string $core): string
    {
        if ('' !== $core) {
            return substr($coreBaseUri, 0, -strlen($core) - 1);
        }

        return rtrim($coreBaseUri, '/').'/';
    }

    /**
     * @return array<string, array{username: ?string, password: ?string}>
     */
    private function getUniqueHosts(): array
    {
        $hosts = [];
        foreach ($this->getCores() as $core) {
            $hosts[$core['solrBaseUri']] = $core['auth'];
        }

        return $hosts;
    }

    /**
     * @param array{username: ?string, password: ?string} $auth
     *
     * @return array<mixed>|null
     */
    private function fetchMetrics(string $solrBaseUri, array $auth): ?array
    {
        if (array_key_exists($solrBaseUri, $this->metricsCache)) {
            return $this->metricsCache[$solrBaseUri];
        }

        $url = $solrBaseUri.'admin/metrics?wt=json&group=jvm,core&prefix=memory.heap,QUERY./select.requestTimes,CACHE.searcher';

        try {
            $response = $this->requestFactory->request($url, 'GET', $this->buildRequestOptions($auth));
            if (200 !== $response->getStatusCode()) {
                return $this->metricsCache[$solrBaseUri] = null;
            }
            $data = json_decode((string) $response->getBody(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return $this->metricsCache[$solrBaseUri] = null;
        }

        return $this->metricsCache[$solrBaseUri] = is_array($data) ? $data : null;
    }
}
