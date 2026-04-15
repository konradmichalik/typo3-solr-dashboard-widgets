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
use KonradMichalik\SolrDashboardWidgets\DataProvider\IndexQueueDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

final class IndexQueueDataProviderTest extends TestCase
{
    private ConnectionPool&MockObject $connectionPool;
    private IndexQueueDataProvider $subject;

    protected function setUp(): void
    {
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->subject = new IndexQueueDataProvider($this->connectionPool);
    }

    public function testGetQueueStatusReturnsZerosWhenTableIsEmpty(): void
    {
        $queryBuilder = $this->createConfiguredQueryBuilder([]);
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $result = $this->subject->getQueueStatus();

        self::assertSame(['indexed' => 0, 'pending' => 0, 'failed' => 0], $result);
    }

    public function testGetQueueStatusCategorisesRowsCorrectly(): void
    {
        $rows = [
            ['indexed' => 1, 'errors' => '', 'cnt' => 5],   // indexed
            ['indexed' => 0, 'errors' => '', 'cnt' => 3],   // pending
            ['indexed' => 0, 'errors' => 'some error', 'cnt' => 2], // failed
            ['indexed' => 1, 'errors' => 'also error', 'cnt' => 1], // failed (errors take precedence)
        ];

        $queryBuilder = $this->createConfiguredQueryBuilder($rows);
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $result = $this->subject->getQueueStatus();

        self::assertSame(5, $result['indexed']);
        self::assertSame(3, $result['pending']);
        self::assertSame(3, $result['failed']);
    }

    public function testGetErrorsReturnsErrorRows(): void
    {
        $rows = [
            ['item_type' => 'pages', 'item_uid' => 1, 'errors' => 'Error A', 'changed' => 1700000002],
            ['item_type' => 'pages', 'item_uid' => 2, 'errors' => 'Error B', 'changed' => 1700000001],
        ];

        $queryBuilder = $this->createConfiguredQueryBuilder($rows);
        $this->connectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $result = $this->subject->getErrors();

        self::assertCount(2, $result);
        self::assertSame('Error A', $result[0]['errors']);
        self::assertSame('Error B', $result[1]['errors']);
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
        $expr->method('neq')->willReturn('1=1');

        $resultStub = $this->createMock(Result::class);
        $resultStub->method('fetchAllAssociative')->willReturn($rows);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('expr')->willReturn($expr);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('addSelect')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('groupBy')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('update')->willReturnSelf();
        $queryBuilder->method('set')->willReturnSelf();
        $queryBuilder->method('createNamedParameter')->willReturnArgument(0);
        $queryBuilder->method('executeQuery')->willReturn($resultStub);

        return $queryBuilder;
    }
}
