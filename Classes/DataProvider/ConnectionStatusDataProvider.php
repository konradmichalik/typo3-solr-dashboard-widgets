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

final class ConnectionStatusDataProvider
{
    public function __construct(
        private readonly SiteRepository $siteRepository,
        private readonly ConnectionManager $connectionManager,
    ) {}

    /**
     * @return list<array{siteLabel: string, host: string, port: int, core: string, reachable: bool}>
     */
    public function getConnections(): array
    {
        $connections = [];

        foreach ($this->siteRepository->getAvailableSites() as $site) {
            try {
                $siteConnections = $this->connectionManager->getConnectionsBySite($site);
            } catch (\Throwable) {
                continue;
            }

            foreach ($siteConnections as $connection) {
                $reachable = false;
                $node = $connection->getNode('read');

                try {
                    $reachable = $connection->getReadService()->ping();
                } catch (\Throwable) {
                }

                $connections[] = [
                    'siteLabel' => $site->getLabel(),
                    'host' => $node->getHost(),
                    'port' => $node->getPort(),
                    'core' => $node->getCoreName(),
                    'reachable' => $reachable,
                ];
            }
        }

        return $connections;
    }
}
