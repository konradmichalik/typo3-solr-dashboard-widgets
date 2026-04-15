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

namespace KonradMichalik\SolrDashboardWidgets\Widgets\Provider;

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;

/**
 * Button provider linking to the Solr admin UI (`{scheme}://{host}:{port}/solr/`),
 * derived from the first configured Solr connection.
 *
 * Note: the host/port come from EXT:solr's site configuration (typically the
 * TYPO3 server's view of Solr). If Solr sits behind an internal-only hostname
 * the link will not be reachable from the user's browser — that's an
 * infrastructure concern, not something this provider can resolve.
 */
final class SolrAdminUiButtonProvider implements ButtonProviderInterface
{
    public function __construct(
        private readonly SiteRepository $siteRepository,
        private readonly ConnectionManager $connectionManager,
        private readonly string $title,
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLink(): string
    {
        foreach ($this->siteRepository->getAvailableSites() as $site) {
            try {
                $connections = $this->connectionManager->getConnectionsBySite($site);
            } catch (\Throwable) {
                continue;
            }
            foreach ($connections as $connection) {
                $endpoint = $connection->getEndpoint('read');
                return sprintf(
                    '%s://%s:%d/solr/',
                    $endpoint->getScheme(),
                    $endpoint->getHost(),
                    $endpoint->getPort()
                );
            }
        }
        return '';
    }

    public function getTarget(): string
    {
        return '_blank';
    }
}
