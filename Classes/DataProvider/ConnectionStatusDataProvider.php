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
     * Returns one entry per site, each listing the site's configured Solr cores.
     *
     * @return list<array{
     *     siteLabel: string,
     *     reachable: bool,
     *     cores: list<array{host: string, port: int, core: string, reachable: bool}>
     * }>
     */
    public function getConnections(): array
    {
        $sites = [];

        foreach ($this->siteRepository->getAvailableSites() as $site) {
            try {
                $siteConnections = $this->connectionManager->getConnectionsBySite($site);
            } catch (\Throwable) {
                continue;
            }

            $cores = [];
            $siteReachable = false;

            foreach ($siteConnections as $connection) {
                $endpoint = $connection->getEndpoint('read');
                $reachable = false;

                try {
                    $reachable = $connection->getReadService()->ping();
                } catch (\Throwable) {
                }

                if ($reachable) {
                    $siteReachable = true;
                }

                $cores[] = [
                    'host' => $endpoint->getHost(),
                    'port' => $endpoint->getPort(),
                    'core' => $endpoint->getCore() ?? '',
                    'reachable' => $reachable,
                ];
            }

            $sites[] = [
                'siteLabel' => $site->getLabel(),
                'reachable' => $siteReachable,
                'cores' => $cores,
            ];
        }

        return $sites;
    }
}
