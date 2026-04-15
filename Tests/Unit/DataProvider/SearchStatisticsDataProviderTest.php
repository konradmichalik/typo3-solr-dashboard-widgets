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

namespace KonradMichalik\SolrDashboardWidgets\Tests\Unit\DataProvider;

use Doctrine\DBAL\Result;
use KonradMichalik\SolrDashboardWidgets\DataProvider\SearchStatisticsDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

final class SearchStatisticsDataProviderTest extends TestCase
{
    private ConnectionPool&MockObject $connectionPool;
    private SearchStatisticsDataProvider $subject;

    protected function setUp(): void
    {
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->subject = new SearchStatisticsDataProvider($this->connectionPool);
    }

    public function testIsTableAvailableReturnsFalseOnException(): void
    {
        $this->connectionPool
            ->method('getConnectionForTable')
            ->willThrowException(new \RuntimeException('DB not available'));

        self::assertFalse($this->subject->isTableAvailable());
    }

    public function testIsTableAvailableReturnsTrueWhenTableExists(): void
    {
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $schemaManager->method('listTableNames')->willReturn(['tx_solr_statistics', 'other_table']);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $this->connectionPool
            ->method('getConnectionForTable')
            ->willReturn($connection);

        self::assertTrue($this->subject->isTableAvailable());
    }

    public function testIsTableAvailableReturnsFalseWhenTableMissing(): void
    {
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $schemaManager->method('listTableNames')->willReturn(['other_table']);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $this->connectionPool
            ->method('getConnectionForTable')
            ->willReturn($connection);

        self::assertFalse($this->subject->isTableAvailable());
    }

    public function testGetTopSearchTermsReturnsResults(): void
    {
        $rows = [
            ['keywords' => 'typo3', 'cnt' => 42],
            ['keywords' => 'solr', 'cnt' => 17],
        ];

        $queryBuilder = $this->createConfiguredQueryBuilder($rows);
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $result = $this->subject->getTopSearchTerms(30, 10);

        self::assertCount(2, $result);
        self::assertSame('typo3', $result[0]['keywords']);
        self::assertSame(42, $result[0]['cnt']);
    }

    public function testGetTopSearchTermsReturnsEmptyArrayWhenNoData(): void
    {
        $queryBuilder = $this->createConfiguredQueryBuilder([]);
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $result = $this->subject->getTopSearchTerms();

        self::assertSame([], $result);
    }

    public function testGetNoHitQueriesReturnsResults(): void
    {
        $rows = [
            ['keywords' => 'xyznotfound', 'cnt' => 8],
            ['keywords' => 'missingpage', 'cnt' => 3],
        ];

        $queryBuilder = $this->createConfiguredQueryBuilder($rows);
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $result = $this->subject->getNoHitQueries(30, 5);

        self::assertCount(2, $result);
        self::assertSame('xyznotfound', $result[0]['keywords']);
        self::assertSame(8, $result[0]['cnt']);
    }

    public function testGetNoHitQueriesReturnsEmptyArrayWhenNoData(): void
    {
        $queryBuilder = $this->createConfiguredQueryBuilder([]);
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $result = $this->subject->getNoHitQueries();

        self::assertSame([], $result);
    }

    public function testGetSearchVolumePerDayReturnsResults(): void
    {
        $rows = [
            ['day' => '2026-04-01', 'cnt' => 120],
            ['day' => '2026-04-02', 'cnt' => 95],
        ];

        $queryBuilder = $this->createConfiguredQueryBuilder($rows);
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $result = $this->subject->getSearchVolumePerDay(14);

        self::assertCount(2, $result);
        self::assertSame('2026-04-01', $result[0]['day']);
        self::assertSame(120, $result[0]['cnt']);
    }

    public function testGetSearchVolumePerDayReturnsEmptyArrayWhenNoData(): void
    {
        $queryBuilder = $this->createConfiguredQueryBuilder([]);
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $result = $this->subject->getSearchVolumePerDay();

        self::assertSame([], $result);
    }

    /**
     * Creates a fully mocked QueryBuilder whose fluent chain returns itself
     * and whose executeQuery() returns a Result stub yielding $rows.
     */
    private function createConfiguredQueryBuilder(array $rows): QueryBuilder&MockObject
    {
        $expr = $this->createMock(ExpressionBuilder::class);
        $expr->method('count')->willReturn('COUNT(*)');
        $expr->method('eq')->willReturn('1=1');
        $expr->method('gt')->willReturn('1=1');

        $resultStub = $this->createMock(Result::class);
        $resultStub->method('fetchAllAssociative')->willReturn($rows);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expr);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('addSelect')->willReturnSelf();
        $queryBuilder->method('selectLiteral')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('groupBy')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('createNamedParameter')->willReturnArgument(0);
        $queryBuilder->method('executeQuery')->willReturn($resultStub);

        return $queryBuilder;
    }
}
