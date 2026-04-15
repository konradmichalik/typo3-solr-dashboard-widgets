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

use KonradMichalik\SolrDashboardWidgets\DataProvider\SearchStatisticsDataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class SearchStatisticsWidget implements WidgetInterface, JavaScriptInterface, RequestAwareWidgetInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly SearchStatisticsDataProvider $dataProvider,
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
        $view->assign('configuration', $this->configuration);

        if (!$this->dataProvider->isTableAvailable()) {
            $view->assign('noData', true);
            return $view->render('Widget/SearchStatistics');
        }

        $topTerms = $this->dataProvider->getTopSearchTerms(30, 10);
        $noHitQueries = $this->dataProvider->getNoHitQueries(30, 5);
        $volumePerDay = $this->dataProvider->getSearchVolumePerDay(14);

        $hasData = $topTerms !== [] || $noHitQueries !== [] || $volumePerDay !== [];

        if (!$hasData) {
            $view->assign('noData', true);
            return $view->render('Widget/SearchStatistics');
        }

        $view->assign('noData', false);
        $view->assign('topTerms', $topTerms);
        $view->assign('noHitQueries', $noHitQueries);
        $view->assign('topTermsChartData', json_encode($this->getTopTermsChartData($topTerms), JSON_THROW_ON_ERROR));
        $view->assign('volumeChartData', json_encode($this->getVolumeChartData($volumePerDay), JSON_THROW_ON_ERROR));

        return $view->render('Widget/SearchStatistics');
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
     * @param list<array{keywords: string, cnt: int}> $topTerms
     * @return array<string, mixed>
     */
    private function getTopTermsChartData(array $topTerms): array
    {
        return [
            'labels' => array_column($topTerms, 'keywords'),
            'datasets' => [
                [
                    'data' => array_map(static fn (array $row): int => (int)$row['cnt'], $topTerms),
                    'backgroundColor' => '#0d6efd',
                ],
            ],
        ];
    }

    /**
     * @param list<array{day: string, cnt: int}> $volumePerDay
     * @return array<string, mixed>
     */
    private function getVolumeChartData(array $volumePerDay): array
    {
        return [
            'labels' => array_column($volumePerDay, 'day'),
            'datasets' => [
                [
                    'data' => array_map(static fn (array $row): int => (int)$row['cnt'], $volumePerDay),
                    'borderColor' => '#0d6efd',
                    'backgroundColor' => 'rgba(13, 110, 253, 0.1)',
                    'fill' => true,
                ],
            ],
        ];
    }
}
