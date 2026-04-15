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

use KonradMichalik\SolrDashboardWidgets\DataProvider\IndexQueueDataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class IndexQueueErrorsWidget implements WidgetInterface, RequestAwareWidgetInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly IndexQueueDataProvider $dataProvider,
        private readonly BackendUriBuilder $backendUriBuilder,
        private readonly ViewFactoryInterface $viewFactory,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $view = $this->viewFactory->create(new ViewFactoryData(
            templateRootPaths: ['EXT:solr_dashboard_widgets/Resources/Private/Templates/'],
            request: $this->request,
        ));
        $view->assign('errors', $this->dataProvider->getErrors(10));
        $view->assign('resetUrl', (string)$this->backendUriBuilder->buildUriFromRoute('solr_dashboard_widgets_reset_errors'));
        $view->assign('configuration', $this->configuration);

        return $view->render('Widget/IndexQueueErrors');
    }

    public function getOptions(): array
    {
        return [];
    }
}
