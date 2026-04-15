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

use KonradMichalik\SolrDashboardWidgets\DataProvider\LastIndexingRunDataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class LastIndexingRunWidget implements WidgetInterface, RequestAwareWidgetInterface
{
    use DashboardWidgetViewTrait;

    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly LastIndexingRunDataProvider $dataProvider,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly ?ButtonProviderInterface $buttonProvider = null,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $lastRun = $this->dataProvider->getLastRun();

        $view = $this->createDashboardView($this->viewFactory, $this->request, $this->buttonProvider, $this->configuration);
        $view->assign('lastRun', $lastRun);

        if ($lastRun !== null) {
            $status = $this->dataProvider->getStatus($lastRun['timestamp']);
            $view->assign('status', $status);
            $view->assign('boxState', match ($status) {
                'ok' => 0,
                'warning' => 1,
                'error' => 2,
                default => -1,
            });
            $view->assign('humanReadableAge', $this->dataProvider->getHumanReadableAge($lastRun['timestamp']));
        }

        $nextRun = $this->dataProvider->getNextRun();
        $view->assign('nextRun', $nextRun);
        if ($nextRun !== null) {
            $view->assign('nextRunEta', $this->dataProvider->getHumanReadableEta($nextRun['timestamp']));
        }

        return $view->render('Widget/LastIndexingRun');
    }

    public function getOptions(): array
    {
        return [];
    }
}
