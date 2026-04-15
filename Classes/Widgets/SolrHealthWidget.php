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

use KonradMichalik\SolrDashboardWidgets\DataProvider\SolrMetricsDataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\{AdditionalCssInterface, ButtonProviderInterface, EventDataInterface, JavaScriptInterface, RequestAwareWidgetInterface, WidgetConfigurationInterface, WidgetInterface};

/**
 * SolrHealthWidget.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final class SolrHealthWidget implements WidgetInterface, JavaScriptInterface, RequestAwareWidgetInterface, EventDataInterface, AdditionalCssInterface
{
    use DashboardWidgetViewTrait;

    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly SolrMetricsDataProvider $metricsProvider,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly ?ButtonProviderInterface $buttonProvider = null,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $memory = $this->metricsProvider->getJvmMemory();
        $performance = $this->metricsProvider->getQueryPerformance();

        $view = $this->createDashboardView($this->viewFactory, $this->request, $this->buttonProvider, $this->configuration);
        $view->assign('memory', $memory);
        $view->assign('usedMb', null !== $memory ? (int) round($memory['usedBytes'] / 1024 / 1024) : 0);
        $view->assign('maxMb', null !== $memory ? (int) round($memory['maxBytes'] / 1024 / 1024) : 0);
        $view->assign('usedPercent', null !== $memory ? (int) round($memory['usedPercent']) : 0);
        $view->assign('performance', $performance);
        $view->assign('solrVersion', $this->metricsProvider->getSolrVersion() ?? '');

        return $view->render('Widget/SolrHealth');
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        $memory = $this->metricsProvider->getJvmMemory();

        if (null === $memory) {
            return [
                'graphConfig' => [
                    'type' => 'doughnut',
                    'options' => ['maintainAspectRatio' => false],
                    'data' => ['labels' => [], 'datasets' => []],
                ],
            ];
        }

        $usedPercent = $memory['usedPercent'];
        $usedColor = $usedPercent >= 85 ? '#c83c3c' : ($usedPercent >= 70 ? '#e8a33d' : '#4ba2b3');

        return [
            'graphConfig' => [
                'type' => 'doughnut',
                'options' => [
                    'maintainAspectRatio' => false,
                    'plugins' => [
                        'legend' => ['display' => false],
                    ],
                    'cutout' => '70%',
                ],
                'data' => [
                    'labels' => ['Used', 'Free'],
                    'datasets' => [
                        [
                            'data' => [
                                $memory['usedBytes'],
                                max(0, $memory['maxBytes'] - $memory['usedBytes']),
                            ],
                            'backgroundColor' => [$usedColor, 'rgba(128,128,128,0.15)'],
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

    /**
     * @return list<string>
     */
    public function getCssFiles(): array
    {
        return ['EXT:typo3_solr_dashboard_widgets/Resources/Public/Css/typo3_solr_dashboard_widgets.css'];
    }
}
