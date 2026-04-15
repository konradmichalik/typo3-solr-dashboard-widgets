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

use KonradMichalik\SolrDashboardWidgets\DataProvider\SearchStatisticsDataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\{ButtonProviderInterface, EventDataInterface, JavaScriptInterface, RequestAwareWidgetInterface, WidgetConfigurationInterface, WidgetInterface};

/**
 * SearchVolumeWidget.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final class SearchVolumeWidget implements WidgetInterface, JavaScriptInterface, RequestAwareWidgetInterface, EventDataInterface
{
    use DashboardWidgetViewTrait;

    private const DAYS = 14;

    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly SearchStatisticsDataProvider $dataProvider,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly ?ButtonProviderInterface $buttonProvider = null,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $view = $this->createDashboardView($this->viewFactory, $this->request, $this->buttonProvider, $this->configuration);

        if (!$this->dataProvider->isTableAvailable()) {
            $view->assign('noData', true);

            return $view->render('Widget/SearchVolume');
        }

        $volumePerDay = $this->dataProvider->getSearchVolumePerDay(self::DAYS);

        if ([] === $volumePerDay) {
            $view->assign('noData', true);

            return $view->render('Widget/SearchVolume');
        }

        $view->assign('noData', false);

        return $view->render('Widget/SearchVolume');
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        if (!$this->dataProvider->isTableAvailable()) {
            return ['graphConfig' => $this->emptyChart()];
        }

        $volumePerDay = $this->dataProvider->getSearchVolumePerDay(self::DAYS);

        if ([] === $volumePerDay) {
            return ['graphConfig' => $this->emptyChart()];
        }

        return [
            'graphConfig' => [
                'type' => 'line',
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
                    'labels' => array_map(
                        static fn (array $row): string => date('M d', strtotime($row['day']) ?: 0),
                        $volumePerDay,
                    ),
                    'datasets' => [
                        [
                            'label' => 'Searches',
                            'data' => array_map(static fn (array $row): int => $row['cnt'], $volumePerDay),
                            'borderColor' => '#f49700',
                            'backgroundColor' => 'rgba(244, 151, 0, 0.15)',
                            'fill' => true,
                            'tension' => 0.3,
                            'borderWidth' => 2,
                            'pointRadius' => 3,
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

    /**
     * @return array<string, mixed>
     */
    private function emptyChart(): array
    {
        return [
            'type' => 'line',
            'options' => ['maintainAspectRatio' => false],
            'data' => ['labels' => [], 'datasets' => []],
        ];
    }
}
