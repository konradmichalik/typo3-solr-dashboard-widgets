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

namespace KonradMichalik\SolrDashboardWidgets\Controller;

use KonradMichalik\SolrDashboardWidgets\DataProvider\IndexQueueDataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

final class IndexQueueErrorsAjaxController
{
    public function __construct(
        private readonly IndexQueueDataProvider $dataProvider,
    ) {}

    public function resetAction(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return new JsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $affectedRows = $this->dataProvider->resetErrors();

        return new JsonResponse([
            'success' => true,
            'affectedRows' => $affectedRows,
        ]);
    }
}
