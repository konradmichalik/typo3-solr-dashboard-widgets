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

namespace KonradMichalik\SolrDashboardWidgets\DataProvider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class IndexQueueDataProvider
{
    private const TABLE = 'tx_solr_indexqueue_item';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * @return array{indexed: int, pending: int, failed: int}
     */
    public function getQueueStatus(?int $rootPageId = null): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->select('indexed', 'errors')
            ->addSelectLiteral($queryBuilder->expr()->count('*', 'cnt'))
            ->from(self::TABLE)
            ->groupBy('indexed', 'errors');

        if ($rootPageId !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('root', $queryBuilder->createNamedParameter($rootPageId, Connection::PARAM_INT))
            );
        }

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        $indexed = 0;
        $pending = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $count = (int)$row['cnt'];
            if ($row['errors'] !== '') {
                $failed += $count;
            } elseif ((int)$row['indexed'] > 0) {
                $indexed += $count;
            } else {
                $pending += $count;
            }
        }

        return [
            'indexed' => $indexed,
            'pending' => $pending,
            'failed' => $failed,
        ];
    }

    /**
     * @return list<array{item_type: string, item_uid: int, errors: string, changed: int}>
     */
    public function getErrors(int $limit = 10): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        return $queryBuilder
            ->select('item_type', 'item_uid', 'errors', 'changed')
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->neq('errors', $queryBuilder->createNamedParameter('')))
            ->orderBy('changed', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }

}
