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

use TYPO3\CMS\Core\Imaging\IconProvider\{SvgIconProvider, SvgSpriteIconProvider};

return [
    'typo3-solr-dashboard-widgets' => [
        'provider' => SvgSpriteIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/Solr.svg',
        'sprite' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/sprites.svg#solr',
    ],
    'typo3-solr-dashboard-widgets-connection-status' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/solr-dashboard-widget-status.svg',
    ],
    'typo3-solr-dashboard-widgets-index-queue-status' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/solr-dashboard-widget-queue.svg',
    ],
    'typo3-solr-dashboard-widgets-index-queue-errors' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/solr-dashboard-widget-errors.svg',
    ],
    'typo3-solr-dashboard-widgets-documents-in-index' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/solr-dashboard-widget-index-by-type.svg',
    ],
    'typo3-solr-dashboard-widgets-last-indexing-run' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/solr-dashboard-widget-last-run.svg',
    ],
    'typo3-solr-dashboard-widgets-search-volume' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/solr-dashboard-widget-search-volume.svg',
    ],
    'typo3-solr-dashboard-widgets-search-terms' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/solr-dashboard-widget-search-words.svg',
    ],
    'typo3-solr-dashboard-widgets-solr-health' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/solr-dashboard-widget-health.svg',
    ],
    'typo3-solr-dashboard-widgets-cache-hit-rates' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/solr-dashboard-widget-cache-hits.svg',
    ],
    'typo3-solr-dashboard-widgets-preset-search-insights' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/solr-dashboard-widget-preset-editor.svg',
    ],
    'typo3-solr-dashboard-widgets-preset-overview' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:typo3_solr_dashboard_widgets/Resources/Public/Icons/solr-dashboard-widget-preset.svg',
    ],
];
