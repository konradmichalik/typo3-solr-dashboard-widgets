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

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use ApacheSolrForTypo3\Solr\System\Solr\SolrConnection;
use ApacheSolrForTypo3\Solr\System\Solr\Service\SolrReadService;
use KonradMichalik\SolrDashboardWidgets\DataProvider\DocumentsInIndexDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solarium\Core\Client\Endpoint;
use Solarium\QueryType\Select\Result\Result;

final class DocumentsInIndexDataProviderTest extends TestCase
{
    private SiteRepository&MockObject $siteRepository;
    private ConnectionManager&MockObject $connectionManager;
    private DocumentsInIndexDataProvider $subject;

    protected function setUp(): void
    {
        $this->siteRepository = $this->createMock(SiteRepository::class);
        $this->connectionManager = $this->createMock(ConnectionManager::class);

        $this->subject = new DocumentsInIndexDataProvider(
            $this->siteRepository,
            $this->connectionManager,
        );
    }

    public function testGetDocumentCountsReturnsEmptyArrayWhenNoSites(): void
    {
        $this->siteRepository
            ->method('getAvailableSites')
            ->willReturn([]);

        $result = $this->subject->getDocumentCounts();

        self::assertSame([], $result);
    }

    public function testGetDocumentCountsReturnsCorrectCountForReachableCore(): void
    {
        $site = $this->createMock(Site::class);
        $site->method('getLabel')->willReturn('My Site');

        $node = $this->createMock(Endpoint::class);
        $node->method('getCoreName')->willReturn('core_en');

        $parsedResponse = new \stdClass();
        $parsedResponse->response = new \stdClass();
        $parsedResponse->response->numFound = 42;

        $searchResponse = $this->createMock(Result::class);
        $searchResponse->method('getParsedData')->willReturn($parsedResponse);

        $readService = $this->createMock(SolrReadService::class);
        $readService->method('search')->with('*:*', 0, 0)->willReturn($searchResponse);

        $connection = $this->createMock(SolrConnection::class);
        $connection->method('getNode')->with('read')->willReturn($node);
        $connection->method('getReadService')->willReturn($readService);

        $this->siteRepository
            ->method('getAvailableSites')
            ->willReturn([$site]);

        $this->connectionManager
            ->method('getConnectionsBySite')
            ->with($site)
            ->willReturn([$connection]);

        $result = $this->subject->getDocumentCounts();

        self::assertCount(1, $result);
        self::assertSame('My Site', $result[0]['siteLabel']);
        self::assertSame('core_en', $result[0]['core']);
        self::assertSame(42, $result[0]['count']);
        self::assertTrue($result[0]['reachable']);
    }

    public function testGetDocumentCountsSetsCountZeroAndReachableFalseWhenSearchThrows(): void
    {
        $site = $this->createMock(Site::class);
        $site->method('getLabel')->willReturn('My Site');

        $node = $this->createMock(Endpoint::class);
        $node->method('getCoreName')->willReturn('core_en');

        $readService = $this->createMock(SolrReadService::class);
        $readService->method('search')->willThrowException(new \RuntimeException('Connection refused'));

        $connection = $this->createMock(SolrConnection::class);
        $connection->method('getNode')->with('read')->willReturn($node);
        $connection->method('getReadService')->willReturn($readService);

        $this->siteRepository
            ->method('getAvailableSites')
            ->willReturn([$site]);

        $this->connectionManager
            ->method('getConnectionsBySite')
            ->with($site)
            ->willReturn([$connection]);

        $result = $this->subject->getDocumentCounts();

        self::assertCount(1, $result);
        self::assertSame(0, $result[0]['count']);
        self::assertFalse($result[0]['reachable']);
    }
}
