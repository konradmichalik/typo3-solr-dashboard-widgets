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
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class IndexQueueStatusWidget implements WidgetInterface, JavaScriptInterface, RequestAwareWidgetInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly IndexQueueDataProvider $dataProvider,
        private readonly ViewFactoryInterface $viewFactory,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $status = $this->dataProvider->getQueueStatus();

        $view = $this->viewFactory->create(new ViewFactoryData(
            templateRootPaths: ['EXT:solr_dashboard_widgets/Resources/Private/Templates/'],
            request: $this->request,
        ));
        $view->assign('status', $status);
        $view->assign('chartData', json_encode($this->getChartData($status), JSON_THROW_ON_ERROR));
        $view->assign('configuration', $this->configuration);

        return $view->render('Widget/IndexQueueStatus');
    }

    public function getJavaScriptModuleInstructions(): array
    {
        return [
            JavaScriptModuleInstruction::create('@typo3/dashboard/chart-initializer.js'),
        ];
    }

    public function getOptions(): array
    {
        return [];
    }

    /**
     * @param array{indexed: int, pending: int, failed: int} $status
     * @return array<string, mixed>
     */
    private function getChartData(array $status): array
    {
        return [
            'labels' => ['Indexed', 'Pending', 'Failed'],
            'datasets' => [
                [
                    'data' => [$status['indexed'], $status['pending'], $status['failed']],
                    'backgroundColor' => ['#198754', '#ffc107', '#dc3545'],
                ],
            ],
        ];
    }
}
