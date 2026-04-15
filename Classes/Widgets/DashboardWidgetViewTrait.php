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

namespace KonradMichalik\SolrDashboardWidgets\Widgets;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;

/**
 * Shared view creation for Solr Dashboard widgets.
 *
 * Every widget needs the same combination of its own template root, TYPO3
 * Dashboard's partials (for the footer Button partial) and layouts (for the
 * Widget/Widget layout). Consumers hand in their request, button provider and
 * configuration; the trait assumes a readonly `$viewFactory` property on the
 * using class.
 */
trait DashboardWidgetViewTrait
{
    protected function createDashboardView(
        ViewFactoryInterface $viewFactory,
        ServerRequestInterface $request,
        ?ButtonProviderInterface $buttonProvider,
        WidgetConfigurationInterface $configuration,
    ): ViewInterface {
        $view = $viewFactory->create(new ViewFactoryData(
            templateRootPaths: ['EXT:solr_dashboard_widgets/Resources/Private/Templates/'],
            partialRootPaths: ['EXT:dashboard/Resources/Private/Partials/'],
            layoutRootPaths: ['EXT:dashboard/Resources/Private/Layouts/'],
            request: $request,
        ));
        $view->assign('button', $buttonProvider);
        $view->assign('configuration', $configuration);
        return $view;
    }
}
