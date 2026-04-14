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

final class DocumentsInIndexDataProvider
{
    public function __construct(
        private readonly SiteRepository $siteRepository,
        private readonly ConnectionManager $connectionManager,
    ) {}

    /**
     * @return list<array{siteLabel: string, core: string, count: int, reachable: bool}>
     */
    public function getDocumentCounts(): array
    {
        $counts = [];

        foreach ($this->siteRepository->getAvailableSites() as $site) {
            try {
                $connections = $this->connectionManager->getConnectionsBySite($site);
            } catch (\Throwable) {
                continue;
            }

            foreach ($connections as $connection) {
                $count = 0;
                $reachable = false;

                try {
                    $response = $connection->getReadService()->search('*:*', 0, 0);
                    $count = (int)$response->getParsedData()->response->numFound;
                    $reachable = true;
                } catch (\Throwable) {
                }

                $counts[] = [
                    'siteLabel' => $site->getLabel(),
                    'core' => $connection->getNode('read')->getCoreName(),
                    'count' => $count,
                    'reachable' => $reachable,
                ];
            }
        }

        return $counts;
    }
}
