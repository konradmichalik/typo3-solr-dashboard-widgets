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
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

final class SearchStatisticsWidget implements WidgetInterface, JavaScriptInterface
{
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly SearchStatisticsDataProvider $dataProvider,
        private readonly StandaloneView $view,
    ) {}

    public function renderWidgetContent(): string
    {
        $this->view->setTemplatePathAndFilename(
            'EXT:solr_dashboard_widgets/Resources/Private/Templates/Widget/SearchStatistics.html'
        );
        $this->view->assign('configuration', $this->configuration);

        if (!$this->dataProvider->isTableAvailable()) {
            $this->view->assign('noData', true);
            return $this->view->render();
        }

        $topTerms = $this->dataProvider->getTopSearchTerms(30, 10);
        $noHitQueries = $this->dataProvider->getNoHitQueries(30, 5);
        $volumePerDay = $this->dataProvider->getSearchVolumePerDay(14);

        $hasData = $topTerms !== [] || $noHitQueries !== [] || $volumePerDay !== [];

        if (!$hasData) {
            $this->view->assign('noData', true);
            return $this->view->render();
        }

        $this->view->assign('noData', false);
        $this->view->assign('topTerms', $topTerms);
        $this->view->assign('noHitQueries', $noHitQueries);
        $this->view->assign('topTermsChartData', json_encode($this->getTopTermsChartData($topTerms), JSON_THROW_ON_ERROR));
        $this->view->assign('volumeChartData', json_encode($this->getVolumeChartData($volumePerDay), JSON_THROW_ON_ERROR));

        return $this->view->render();
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
