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

use Throwable;
use TYPO3\CMS\Core\Database\{Connection, ConnectionPool};

/**
 * SearchStatisticsDataProvider.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final readonly class SearchStatisticsDataProvider
{
    private const TABLE = 'tx_solr_statistics';

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function isTableAvailable(): bool
    {
        try {
            return $this->connectionPool
                ->getConnectionForTable(self::TABLE)
                ->createSchemaManager()
                ->tablesExist([self::TABLE]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<array{keywords: string, cnt: int, percent: float}>
     */
    public function getTopSearchTerms(int $days = 30, int $limit = 10): array
    {
        $since = time() - ($days * 86400);
        $rows = $this->fetchTerms($since, $limit, withHits: true);

        return $this->attachPercentages($rows, $this->countSearches($since, withHits: true));
    }

    /**
     * @return list<array{keywords: string, cnt: int, percent: float}>
     */
    public function getNoHitQueries(int $days = 30, int $limit = 5): array
    {
        $since = time() - ($days * 86400);
        $rows = $this->fetchTerms($since, $limit, withHits: false);

        return $this->attachPercentages($rows, $this->countSearches($since, withHits: false));
    }

    /**
     * @return list<array{day: string, cnt: int}>
     */
    public function getSearchVolumePerDay(int $days = 14): array
    {
        $since = time() - ($days * 86400);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        $rows = $queryBuilder
            ->selectLiteral('DATE(FROM_UNIXTIME(tstamp)) AS day')
            ->addSelectLiteral($queryBuilder->expr()->count('*', 'cnt'))
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter($since, Connection::PARAM_INT)),
            )
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(
            static fn (array $row): array => [
                'day' => (string) $row['day'],
                'cnt' => (int) $row['cnt'],
            ],
            $rows,
        );
    }

    /**
     * @return list<array{keywords: string, cnt: int}>
     */
    private function fetchTerms(int $since, int $limit, bool $withHits): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        $rows = $queryBuilder
            ->select('keywords')
            ->addSelectLiteral($queryBuilder->expr()->count('*', 'cnt'))
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter($since, Connection::PARAM_INT)),
                $withHits
                    ? $queryBuilder->expr()->gt('num_found', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                    : $queryBuilder->expr()->eq('num_found', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->groupBy('keywords')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(
            static fn (array $row): array => [
                'keywords' => (string) $row['keywords'],
                'cnt' => (int) $row['cnt'],
            ],
            $rows,
        );
    }

    private function countSearches(int $since, bool $withHits): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $countExpression = $queryBuilder->expr()->count('*', 'cnt');

        $row = $queryBuilder
            ->selectLiteral($countExpression)
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter($since, Connection::PARAM_INT)),
                $withHits
                    ? $queryBuilder->expr()->gt('num_found', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                    : $queryBuilder->expr()->eq('num_found', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAssociative();

        return false === $row ? 0 : (int) $row['cnt'];
    }

    /**
     * @param list<array{keywords: string, cnt: int}> $rows
     *
     * @return list<array{keywords: string, cnt: int, percent: float}>
     */
    private function attachPercentages(array $rows, int $total): array
    {
        return array_map(
            static fn (array $row): array => [
                'keywords' => $row['keywords'],
                'cnt' => $row['cnt'],
                'percent' => $total > 0 ? ($row['cnt'] / $total) * 100.0 : 0.0,
            ],
            $rows,
        );
    }
}
