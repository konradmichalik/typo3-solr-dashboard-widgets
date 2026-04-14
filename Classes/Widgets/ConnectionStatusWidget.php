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

use KonradMichalik\SolrDashboardWidgets\DataProvider\ConnectionStatusDataProvider;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

final class ConnectionStatusWidget implements WidgetInterface
{
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly ConnectionStatusDataProvider $dataProvider,
        private readonly StandaloneView $view,
    ) {}

    public function renderWidgetContent(): string
    {
        $this->view->setTemplatePathAndFilename(
            'EXT:solr_dashboard_widgets/Resources/Private/Templates/Widget/ConnectionStatus.html'
        );
        $this->view->assign('connections', $this->dataProvider->getConnections());
        $this->view->assign('configuration', $this->configuration);

        return $this->view->render();
    }

    public function getOptions(): array
    {
        return [];
    }
}
