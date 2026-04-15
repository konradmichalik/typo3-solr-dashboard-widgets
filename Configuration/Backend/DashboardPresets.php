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

return [
    'solrOverview' => [
        'title' => 'LLL:EXT:typo3_solr_dashboard_widgets/Resources/Private/Language/locallang.xlf:preset.solrOverview.title',
        'description' => 'LLL:EXT:typo3_solr_dashboard_widgets/Resources/Private/Language/locallang.xlf:preset.solrOverview.description',
        'iconIdentifier' => 'typo3-solr-dashboard-widgets',
        'defaultWidgets' => [
            'solrDashboardWidgets.connectionStatus',
            'solrDashboardWidgets.solrHealth',
            'solrDashboardWidgets.lastIndexingRun',
            'solrDashboardWidgets.searchTerms',
            'solrDashboardWidgets.indexQueueStatus',
            'solrDashboardWidgets.searchVolume',
            'solrDashboardWidgets.documentsInIndex',
            'solrDashboardWidgets.indexQueueErrors',
        ],
        'showInWizard' => true,
    ],
];
