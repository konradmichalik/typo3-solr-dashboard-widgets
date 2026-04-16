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
use Throwable;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;

use function strlen;

/**
 * SolrAdminUiButtonProvider.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final readonly class SolrAdminUiButtonProvider implements ButtonProviderInterface
{
    public function __construct(
        private SiteRepository $siteRepository,
        private ConnectionManager $connectionManager,
        private string $title,
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
            } catch (Throwable) {
                continue;
            }
            foreach ($connections as $connection) {
                $endpoint = $connection->getEndpoint('read');
                $core = $endpoint->getCore() ?? '';

                if ('' !== $core) {
                    return substr($endpoint->getCoreBaseUri(), 0, -strlen($core) - 1);
                }

                return $endpoint->getServerUri();
            }
        }

        return '';
    }

    public function getTarget(): string
    {
        return '_blank';
    }
}
