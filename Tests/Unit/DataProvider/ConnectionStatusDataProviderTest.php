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

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\Domain\Site\{Site, SiteRepository};
use ApacheSolrForTypo3\Solr\System\Solr\Service\SolrReadService;
use ApacheSolrForTypo3\Solr\System\Solr\SolrConnection;
use KonradMichalik\SolrDashboardWidgets\DataProvider\ConnectionStatusDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Solarium\Core\Client\Endpoint;

/**
 * ConnectionStatusDataProviderTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final class ConnectionStatusDataProviderTest extends TestCase
{
    private SiteRepository&MockObject $siteRepository;
    private ConnectionManager&MockObject $connectionManager;
    private ConnectionStatusDataProvider $subject;

    protected function setUp(): void
    {
        $this->siteRepository = $this->createMock(SiteRepository::class);
        $this->connectionManager = $this->createMock(ConnectionManager::class);

        $this->subject = new ConnectionStatusDataProvider(
            $this->siteRepository,
            $this->connectionManager,
        );
    }

    public function testGetConnectionsReturnsEmptyArrayWhenNoSites(): void
    {
        $this->siteRepository
            ->method('getAvailableSites')
            ->willReturn([]);

        $result = $this->subject->getConnections();

        self::assertSame([], $result);
    }

    public function testGetConnectionsReturnsCorrectDataForReachableCore(): void
    {
        $site = $this->createMock(Site::class);
        $site->method('getLabel')->willReturn('My Site');

        $node = $this->createMock(Endpoint::class);
        $node->method('getHost')->willReturn('localhost');
        $node->method('getPort')->willReturn(8983);
        $node->method('getCore')->willReturn('core_en');

        $readService = $this->createMock(SolrReadService::class);
        $readService->method('ping')->willReturn(true);

        $connection = $this->createMock(SolrConnection::class);
        $connection->method('getEndpoint')->with('read')->willReturn($node);
        $connection->method('getReadService')->willReturn($readService);

        $this->siteRepository
            ->method('getAvailableSites')
            ->willReturn([$site]);

        $this->connectionManager
            ->method('getConnectionsBySite')
            ->with($site)
            ->willReturn([$connection]);

        $result = $this->subject->getConnections();

        self::assertCount(1, $result);
        self::assertSame('My Site', $result[0]['siteLabel']);
        self::assertTrue($result[0]['reachable']);
        self::assertCount(1, $result[0]['cores']);
        self::assertSame('localhost', $result[0]['cores'][0]['host']);
        self::assertSame(8983, $result[0]['cores'][0]['port']);
        self::assertSame('core_en', $result[0]['cores'][0]['core']);
        self::assertTrue($result[0]['cores'][0]['reachable']);
    }

    public function testGetConnectionsSetsReachableFalseWhenPingThrows(): void
    {
        $site = $this->createMock(Site::class);
        $site->method('getLabel')->willReturn('My Site');

        $node = $this->createMock(Endpoint::class);
        $node->method('getHost')->willReturn('localhost');
        $node->method('getPort')->willReturn(8983);
        $node->method('getCore')->willReturn('core_en');

        $readService = $this->createMock(SolrReadService::class);
        $readService->method('ping')->willThrowException(new RuntimeException('Connection refused'));

        $connection = $this->createMock(SolrConnection::class);
        $connection->method('getEndpoint')->with('read')->willReturn($node);
        $connection->method('getReadService')->willReturn($readService);

        $this->siteRepository
            ->method('getAvailableSites')
            ->willReturn([$site]);

        $this->connectionManager
            ->method('getConnectionsBySite')
            ->with($site)
            ->willReturn([$connection]);

        $result = $this->subject->getConnections();

        self::assertCount(1, $result);
        self::assertFalse($result[0]['reachable']);
    }
}
