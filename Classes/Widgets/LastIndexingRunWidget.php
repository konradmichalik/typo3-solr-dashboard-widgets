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
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

final class LastIndexingRunWidget implements WidgetInterface
{
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly LastIndexingRunDataProvider $dataProvider,
        private readonly StandaloneView $view,
    ) {}

    public function renderWidgetContent(): string
    {
        $lastRun = $this->dataProvider->getLastRun();

        $this->view->setTemplatePathAndFilename(
            'EXT:solr_dashboard_widgets/Resources/Private/Templates/Widget/LastIndexingRun.html'
        );
        $this->view->assign('lastRun', $lastRun);
        $this->view->assign('configuration', $this->configuration);

        if ($lastRun !== null) {
            $this->view->assign('status', $this->dataProvider->getStatus($lastRun['timestamp']));
            $this->view->assign('humanReadableAge', $this->dataProvider->getHumanReadableAge($lastRun['timestamp']));
        }

        return $this->view->render();
    }

    public function getOptions(): array
    {
        return [];
    }
}
