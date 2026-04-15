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

use KonradMichalik\SolrDashboardWidgets\DataProvider\LastIndexingRunDataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class LastIndexingRunWidget implements WidgetInterface, RequestAwareWidgetInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly LastIndexingRunDataProvider $dataProvider,
        private readonly ViewFactoryInterface $viewFactory,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $lastRun = $this->dataProvider->getLastRun();

        $view = $this->viewFactory->create(new ViewFactoryData(
            templateRootPaths: ['EXT:solr_dashboard_widgets/Resources/Private/Templates/'],
            request: $this->request,
        ));
        $view->assign('lastRun', $lastRun);
        $view->assign('configuration', $this->configuration);

        if ($lastRun !== null) {
            $view->assign('status', $this->dataProvider->getStatus($lastRun['timestamp']));
            $view->assign('humanReadableAge', $this->dataProvider->getHumanReadableAge($lastRun['timestamp']));
        }

        return $view->render('Widget/LastIndexingRun');
    }

    public function getOptions(): array
    {
        return [];
    }
}
