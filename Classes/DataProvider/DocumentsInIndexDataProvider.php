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

use function count;
use function is_array;

/**
 * DocumentsInIndexDataProvider.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final readonly class DocumentsInIndexDataProvider
{
    public function __construct(
        private SiteRepository $siteRepository,
        private ConnectionManager $connectionManager,
        private RequestFactory $requestFactory,
    ) {}

    /**
     * Aggregate document counts per `type` facet across all reachable cores.
     *
     * @return array{reachable: bool, total: int, byType: list<array{type: string, count: int}>}
     */
    public function getDocumentCountsByType(): array
    {
        $perType = [];
        $total = 0;
        $anyReachable = false;

        foreach ($this->collectCoreEndpoints() as $coreUri) {
            $data = $this->fetchFacetCounts($coreUri);
            if (null === $data) {
                continue;
            }
            $anyReachable = true;
            $total += (int) ($data['response']['numFound'] ?? 0);
            $this->mergeTypeFacet($data['facet_counts']['facet_fields']['type'] ?? [], $perType);
        }

        arsort($perType);
        $byType = [];
        foreach ($perType as $type => $count) {
            $byType[] = ['type' => $type, 'count' => $count];
        }

        return [
            'reachable' => $anyReachable,
            'total' => $total,
            'byType' => $byType,
        ];
    }

    /**
     * @return list<string> `/select` base URIs for every configured Solr core
     */
    private function collectCoreEndpoints(): array
    {
        $uris = [];
        foreach ($this->siteRepository->getAvailableSites() as $site) {
            try {
                $connections = $this->connectionManager->getConnectionsBySite($site);
            } catch (Throwable) {
                continue;
            }
            foreach ($connections as $connection) {
                $uris[] = $connection->getEndpoint('read')->getCoreBaseUri()
                    .'select?q=*:*&rows=0&facet=true&facet.field=type&facet.mincount=1&wt=json';
            }
        }

        return $uris;
    }

    /**
     * @return array<string, mixed>|null parsed JSON response, or null if unreachable
     */
    private function fetchFacetCounts(string $url): ?array
    {
        try {
            $response = $this->requestFactory->request($url, 'GET', [
                'timeout' => 3,
                'connect_timeout' => 2,
            ]);
            if (200 !== $response->getStatusCode()) {
                return null;
            }

            $data = json_decode((string) $response->getBody(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * Solr returns type facets as a flat [value, count, value, count, ...] array.
     *
     * @param array<mixed>       $facet
     * @param array<string, int> &$perType accumulator keyed by type
     */
    private function mergeTypeFacet(array $facet, array &$perType): void
    {
        for ($i = 0, $n = count($facet); $i + 1 < $n; $i += 2) {
            $type = (string) $facet[$i];
            $count = (int) $facet[$i + 1];
            if ('' === $type || 0 === $count) {
                continue;
            }
            $perType[$type] = ($perType[$type] ?? 0) + $count;
        }
    }
}
