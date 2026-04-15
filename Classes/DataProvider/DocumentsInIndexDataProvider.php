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

final class DocumentsInIndexDataProvider
{
    public function __construct(
        private readonly SiteRepository $siteRepository,
        private readonly ConnectionManager $connectionManager,
        private readonly RequestFactory $requestFactory,
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

        foreach ($this->siteRepository->getAvailableSites() as $site) {
            try {
                $connections = $this->connectionManager->getConnectionsBySite($site);
            } catch (\Throwable) {
                continue;
            }

            foreach ($connections as $connection) {
                $endpoint = $connection->getEndpoint('read');
                $baseUri = $endpoint->getCoreBaseUri();
                $url = $baseUri . 'select?q=*:*&rows=0&facet=true&facet.field=type&facet.mincount=1&wt=json';

                try {
                    $response = $this->requestFactory->request($url, 'GET', [
                        'timeout' => 3,
                        'connect_timeout' => 2,
                    ]);
                    if ($response->getStatusCode() !== 200) {
                        continue;
                    }
                    $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                } catch (\Throwable) {
                    continue;
                }

                $anyReachable = true;
                $total += (int)($data['response']['numFound'] ?? 0);

                $facet = $data['facet_counts']['facet_fields']['type'] ?? [];
                for ($i = 0, $n = count($facet); $i + 1 < $n; $i += 2) {
                    $type = (string)$facet[$i];
                    $count = (int)$facet[$i + 1];
                    if ($type === '' || $count === 0) {
                        continue;
                    }
                    $perType[$type] = ($perType[$type] ?? 0) + $count;
                }
            }
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
}
