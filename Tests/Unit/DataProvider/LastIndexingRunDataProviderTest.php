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

namespace KonradMichalik\SolrDashboardWidgets\Tests\Unit\DataProvider;

use Doctrine\DBAL\Result;
use KonradMichalik\SolrDashboardWidgets\DataProvider\LastIndexingRunDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

final class LastIndexingRunDataProviderTest extends TestCase
{
    private ConnectionPool&MockObject $connectionPool;
    private LastIndexingRunDataProvider $subject;

    protected function setUp(): void
    {
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->subject = new LastIndexingRunDataProvider($this->connectionPool);
    }

    public function testGetLastRunReturnsNullWhenNoTaskFound(): void
    {
        $queryBuilder = $this->createConfiguredQueryBuilder(false);
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $result = $this->subject->getLastRun();

        self::assertNull($result);
    }

    public function testGetLastRunReturnsTimestampArrayWhenTaskExists(): void
    {
        $queryBuilder = $this->createConfiguredQueryBuilder(['lastexecution_time' => 1700000000]);
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $result = $this->subject->getLastRun();

        self::assertNotNull($result);
        self::assertSame(1700000000, $result['timestamp']);
    }

    public function testGetStatusReturnsOkForRecentTimestamp(): void
    {
        $timestamp = time() - 1800; // 30 minutes ago

        $result = $this->subject->getStatus($timestamp);

        self::assertSame('ok', $result);
    }

    public function testGetStatusReturnsWarningForTimestampOverOneHourAgo(): void
    {
        $timestamp = time() - 7200; // 2 hours ago

        $result = $this->subject->getStatus($timestamp);

        self::assertSame('warning', $result);
    }

    public function testGetStatusReturnsErrorForTimestampOverTwentyFourHoursAgo(): void
    {
        $timestamp = time() - 90000; // 25 hours ago

        $result = $this->subject->getStatus($timestamp);

        self::assertSame('error', $result);
    }

    /**
     * Creates a fully mocked QueryBuilder whose fluent chain returns itself
     * and whose executeQuery() returns a Result stub yielding $row.
     *
     * @param array<string, mixed>|false $row Pass false to simulate "no result found".
     */
    private function createConfiguredQueryBuilder(array|false $row): QueryBuilder&MockObject
    {
        $expr = $this->createMock(ExpressionBuilder::class);
        $expr->method('like')->willReturn('1=1');

        $resultStub = $this->createMock(Result::class);
        $resultStub->method('fetchAssociative')->willReturn($row);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expr);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('createNamedParameter')->willReturnArgument(0);
        $queryBuilder->method('executeQuery')->willReturn($resultStub);

        return $queryBuilder;
    }
}
