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

use KonradMichalik\SolrDashboardWidgets\DataProvider\IndexQueueDataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\{ButtonProviderInterface, EventDataInterface, JavaScriptInterface, RequestAwareWidgetInterface, WidgetConfigurationInterface, WidgetInterface};

/**
 * IndexQueueStatusWidget.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final class IndexQueueStatusWidget implements WidgetInterface, JavaScriptInterface, RequestAwareWidgetInterface, EventDataInterface
{
    use DashboardWidgetViewTrait;

    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly IndexQueueDataProvider $dataProvider,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly ?ButtonProviderInterface $buttonProvider = null,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $status = $this->dataProvider->getQueueStatus();

        $view = $this->createDashboardView($this->viewFactory, $this->request, $this->buttonProvider, $this->configuration);
        $view->assign('status', $status);
        $view->assign('total', $status['indexed'] + $status['pending'] + $status['failed']);

        return $view->render('Widget/IndexQueueStatus');
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        $status = $this->dataProvider->getQueueStatus();

        return [
            'graphConfig' => [
                'type' => 'doughnut',
                'options' => [
                    'maintainAspectRatio' => false,
                    'plugins' => [
                        'legend' => [
                            'display' => true,
                            'position' => 'bottom',
                        ],
                    ],
                    'cutout' => '65%',
                ],
                'data' => [
                    'labels' => ['Indexed', 'Pending', 'Failed'],
                    'datasets' => [
                        [
                            'data' => [$status['indexed'], $status['pending'], $status['failed']],
                            'backgroundColor' => ['#79a548', '#e8a33d', '#c83c3c'],
                            'borderWidth' => 0,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getJavaScriptModuleInstructions(): array
    {
        return [
            JavaScriptModuleInstruction::create('@typo3/dashboard/contrib/chartjs.js'),
            JavaScriptModuleInstruction::create('@typo3/dashboard/chart-initializer.js'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [];
    }
}
