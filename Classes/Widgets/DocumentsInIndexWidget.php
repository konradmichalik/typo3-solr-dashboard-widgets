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

use KonradMichalik\SolrDashboardWidgets\DataProvider\DocumentsInIndexDataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\EventDataInterface;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class DocumentsInIndexWidget implements WidgetInterface, JavaScriptInterface, RequestAwareWidgetInterface, EventDataInterface
{
    use DashboardWidgetViewTrait;

    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly DocumentsInIndexDataProvider $dataProvider,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly ?ButtonProviderInterface $buttonProvider = null,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $result = $this->dataProvider->getDocumentCountsByType();

        $view = $this->createDashboardView($this->viewFactory, $this->request, $this->buttonProvider, $this->configuration);
        $view->assign('reachable', $result['reachable']);
        $view->assign('totalDocuments', $result['total']);
        $view->assign('byType', $result['byType']);

        return $view->render('Widget/DocumentsInIndex');
    }

    public function getEventData(): array
    {
        $result = $this->dataProvider->getDocumentCountsByType();

        return [
            'graphConfig' => [
                'type' => 'bar',
                'options' => [
                    'maintainAspectRatio' => false,
                    'plugins' => [
                        'legend' => ['display' => false],
                    ],
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'ticks' => ['precision' => 0],
                        ],
                    ],
                ],
                'data' => [
                    'labels' => array_map(static fn (array $row): string => $row['type'], $result['byType']),
                    'datasets' => [
                        [
                            'label' => 'Documents',
                            'data' => array_map(static fn (array $row): int => $row['count'], $result['byType']),
                            'backgroundColor' => '#f49700',
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

    public function getOptions(): array
    {
        return [];
    }
}
