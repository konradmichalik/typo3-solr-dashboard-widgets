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

use Throwable;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;

use function is_array;

/**
 * ModuleButtonProvider.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final readonly class ModuleButtonProvider implements ButtonProviderInterface
{
    /** @var list<string> */
    private array $routeIdentifiers;

    /**
     * @param string|list<string> $routeIdentifiers
     */
    public function __construct(
        private BackendUriBuilder $uriBuilder,
        string|array $routeIdentifiers,
        private string $title,
    ) {
        $this->routeIdentifiers = is_array($routeIdentifiers) ? $routeIdentifiers : [$routeIdentifiers];
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLink(): string
    {
        foreach ($this->routeIdentifiers as $identifier) {
            try {
                return (string) $this->uriBuilder->buildUriFromRoute($identifier);
            } catch (Throwable) {
                continue;
            }
        }

        return '';
    }

    public function getTarget(): string
    {
        return '';
    }
}
