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

use KonradMichalik\SolrDashboardWidgets\DataProvider\DocumentsInIndexDataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class DocumentsInIndexWidget implements WidgetInterface, JavaScriptInterface, RequestAwareWidgetInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly DocumentsInIndexDataProvider $dataProvider,
        private readonly ViewFactoryInterface $viewFactory,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $documentCounts = $this->dataProvider->getDocumentCounts();

        $view = $this->viewFactory->create(new ViewFactoryData(
            templateRootPaths: ['EXT:solr_dashboard_widgets/Resources/Private/Templates/'],
            request: $this->request,
        ));
        $view->assign('chartData', json_encode($this->getChartData($documentCounts), JSON_THROW_ON_ERROR));
        $view->assign('configuration', $this->configuration);

        return $view->render('Widget/DocumentsInIndex');
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
     * @param list<array{siteLabel: string, core: string, count: int, reachable: bool}> $documentCounts
     * @return array<string, mixed>
     */
    private function getChartData(array $documentCounts): array
    {
        $labels = [];
        $data = [];
        $colors = [];

        foreach ($documentCounts as $entry) {
            $labels[] = $entry['siteLabel'] . ' (' . $entry['core'] . ')';
            $data[] = $entry['count'];
            $colors[] = $entry['reachable'] ? '#0d6efd' : '#6c757d';
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
        ];
    }
}
