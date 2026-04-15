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

use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * LastIndexingRunDataProvider.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final readonly class LastIndexingRunDataProvider
{
    private const TABLE = 'tx_scheduler_task';
    private const WARNING_THRESHOLD = 3600;
    private const ERROR_THRESHOLD = 86400;

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * @return array{timestamp: int}|null
     */
    public function getLastRun(): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        $row = $queryBuilder
            ->select('lastexecution_time')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->like(
                    'serialized_task_object',
                    $queryBuilder->createNamedParameter('%Solr%'),
                ),
            )
            ->orderBy('lastexecution_time', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return [
            'timestamp' => (int) $row['lastexecution_time'],
        ];
    }

    /**
     * Earliest upcoming execution of any non-disabled Solr-related scheduler task.
     *
     * @return array{timestamp: int}|null
     */
    public function getNextRun(): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        $row = $queryBuilder
            ->select('nextexecution')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->like(
                    'serialized_task_object',
                    $queryBuilder->createNamedParameter('%Solr%'),
                ),
                $queryBuilder->expr()->eq('disable', $queryBuilder->createNamedParameter(0, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                $queryBuilder->expr()->gt('nextexecution', $queryBuilder->createNamedParameter(0, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
            )
            ->orderBy('nextexecution', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return [
            'timestamp' => (int) $row['nextexecution'],
        ];
    }

    public function getHumanReadableEta(int $timestamp): string
    {
        $diff = $timestamp - time();

        if ($diff <= 0) {
            return 'due';
        }

        if ($diff < 60) {
            return 'in '.$diff.' seconds';
        }

        if ($diff < 3600) {
            return 'in '.(int) ($diff / 60).' minutes';
        }

        if ($diff < 86400) {
            return 'in '.(int) ($diff / 3600).' hours';
        }

        return 'in '.(int) ($diff / 86400).' days';
    }

    public function getStatus(int $timestamp): string
    {
        $age = time() - $timestamp;

        if ($age > self::ERROR_THRESHOLD) {
            return 'error';
        }

        if ($age > self::WARNING_THRESHOLD) {
            return 'warning';
        }

        return 'ok';
    }

    public function getHumanReadableAge(int $timestamp): string
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return $diff.' seconds';
        }

        if ($diff < 3600) {
            return (int) ($diff / 60).' minutes';
        }

        if ($diff < 86400) {
            return (int) ($diff / 3600).' hours';
        }

        return (int) ($diff / 86400).' days';
    }
}
