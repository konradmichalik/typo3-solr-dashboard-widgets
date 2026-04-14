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

use KonradMichalik\SolrDashboardWidgets\Controller\IndexQueueErrorsAjaxController;

return [
    'solr_dashboard_widgets_reset_errors' => [
        'path' => '/solr-dashboard-widgets/reset-errors',
        'target' => IndexQueueErrorsAjaxController::class . '::resetAction',
    ],
];
