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

namespace KonradMichalik\SolrDashboardWidgets\Widgets;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\View\{ViewFactoryData, ViewFactoryInterface, ViewInterface};
use TYPO3\CMS\Dashboard\Widgets\{ButtonProviderInterface, WidgetConfigurationInterface};

/**
 * DashboardWidgetViewTrait.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
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
            templateRootPaths: ['EXT:typo3_solr_dashboard_widgets/Resources/Private/Templates/'],
            partialRootPaths: ['EXT:dashboard/Resources/Private/Partials/'],
            layoutRootPaths: ['EXT:dashboard/Resources/Private/Layouts/'],
            request: $request,
        ));
        $view->assign('button', $buttonProvider);
        $view->assign('configuration', $configuration);

        return $view;
    }
}
