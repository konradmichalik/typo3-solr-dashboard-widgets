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
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\{AdditionalCssInterface, ButtonProviderInterface, RequestAwareWidgetInterface, WidgetConfigurationInterface, WidgetInterface};

/**
 * SearchTermsWidget.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final class SearchTermsWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
{
    use DashboardWidgetViewTrait;

    private const WINDOW_DAYS = 30;
    private const TOP_LIMIT = 10;
    private const NOHIT_LIMIT = 5;

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

            return $view->render('Widget/SearchTerms');
        }

        $topTerms = $this->dataProvider->getTopSearchTerms(self::WINDOW_DAYS, self::TOP_LIMIT);
        $noHitQueries = $this->dataProvider->getNoHitQueries(self::WINDOW_DAYS, self::NOHIT_LIMIT);

        if ([] === $topTerms && [] === $noHitQueries) {
            $view->assign('noData', true);

            return $view->render('Widget/SearchTerms');
        }

        $view->assign('noData', false);
        $view->assign('topTerms', $topTerms);
        $view->assign('noHitQueries', $noHitQueries);

        return $view->render('Widget/SearchTerms');
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
