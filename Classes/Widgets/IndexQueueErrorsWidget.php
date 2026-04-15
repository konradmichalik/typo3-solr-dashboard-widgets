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
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class IndexQueueErrorsWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
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
        $view = $this->createDashboardView($this->viewFactory, $this->request, $this->buttonProvider, $this->configuration);
        $view->assign('errors', $this->dataProvider->getErrors(10));

        return $view->render('Widget/IndexQueueErrors');
    }

    public function getOptions(): array
    {
        return [];
    }

    public function getCssFiles(): array
    {
        return ['EXT:solr_dashboard_widgets/Resources/Public/Css/solr_dashboard_widgets.css'];
    }
}
