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

use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;

/**
 * Button provider that links to a TYPO3 backend module via its route identifier.
 *
 * Accepts one or more route identifiers; the first resolvable one wins. This
 * lets us declare a single service for both current and legacy TYPO3 versions
 * (e.g. v14 `scheduler` vs. v13 `scheduler_manage`).
 */
final class ModuleButtonProvider implements ButtonProviderInterface
{
    /** @var list<string> */
    private readonly array $routeIdentifiers;

    /**
     * @param string|list<string> $routeIdentifiers
     */
    public function __construct(
        private readonly BackendUriBuilder $uriBuilder,
        string|array $routeIdentifiers,
        private readonly string $title,
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
                return (string)$this->uriBuilder->buildUriFromRoute($identifier);
            } catch (\Throwable) {
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
