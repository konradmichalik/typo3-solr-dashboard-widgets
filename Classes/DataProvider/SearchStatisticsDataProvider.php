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

namespace KonradMichalik\SolrDashboardWidgets\DataProvider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class SearchStatisticsDataProvider
{
    private const TABLE = 'tx_solr_statistics';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function isTableAvailable(): bool
    {
        try {
            $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
            $tableNames = $connection->createSchemaManager()->listTableNames();

            return in_array(self::TABLE, $tableNames, true);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<array{keywords: string, cnt: int}>
     */
    public function getTopSearchTerms(int $days = 30, int $limit = 10): array
    {
        $since = time() - ($days * 86400);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        return $queryBuilder
            ->select('keywords')
            ->addSelect($queryBuilder->expr()->count('*', 'cnt'))
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter($since, Connection::PARAM_INT)),
                $queryBuilder->expr()->gt('num_found', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->groupBy('keywords')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return list<array{keywords: string, cnt: int}>
     */
    public function getNoHitQueries(int $days = 30, int $limit = 5): array
    {
        $since = time() - ($days * 86400);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        return $queryBuilder
            ->select('keywords')
            ->addSelect($queryBuilder->expr()->count('*', 'cnt'))
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter($since, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('num_found', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->groupBy('keywords')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return list<array{day: string, cnt: int}>
     */
    public function getSearchVolumePerDay(int $days = 14): array
    {
        $since = time() - ($days * 86400);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        return $queryBuilder
            ->selectLiteral('DATE(FROM_UNIXTIME(tstamp)) AS day')
            ->addSelect($queryBuilder->expr()->count('*', 'cnt'))
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter($since, Connection::PARAM_INT))
            )
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
