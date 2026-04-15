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
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class CacheHitRatesWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
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
        $rates = $this->metricsProvider->getCacheHitRates();

        $view = $this->createDashboardView($this->viewFactory, $this->request, $this->buttonProvider, $this->configuration);
        $view->assign('rates', $rates);

        return $view->render('Widget/CacheHitRates');
    }

    public function getOptions(): array
    {
        return [];
    }

    public function getCssFiles(): array
    {
        return ['EXT:typo3_solr_dashboard_widgets/Resources/Public/Css/typo3_solr_dashboard_widgets.css'];
    }
}
