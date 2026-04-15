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
use ApacheSolrForTypo3\Solr\System\Solr\SolrConnection;
use KonradMichalik\SolrDashboardWidgets\DataProvider\DocumentsInIndexDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, StreamInterface};
use RuntimeException;
use Solarium\Core\Client\Endpoint;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * DocumentsInIndexDataProviderTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final class DocumentsInIndexDataProviderTest extends TestCase
{
    private SiteRepository&MockObject $siteRepository;
    private ConnectionManager&MockObject $connectionManager;
    private RequestFactory&MockObject $requestFactory;
    private DocumentsInIndexDataProvider $subject;

    protected function setUp(): void
    {
        $this->siteRepository = $this->createMock(SiteRepository::class);
        $this->connectionManager = $this->createMock(ConnectionManager::class);
        $this->requestFactory = $this->createMock(RequestFactory::class);

        $this->subject = new DocumentsInIndexDataProvider(
            $this->siteRepository,
            $this->connectionManager,
            $this->requestFactory,
        );
    }

    public function testReturnsEmptyAggregateWhenNoSites(): void
    {
        $this->siteRepository->method('getAvailableSites')->willReturn([]);

        $result = $this->subject->getDocumentCountsByType();

        self::assertFalse($result['reachable']);
        self::assertSame(0, $result['total']);
        self::assertSame([], $result['byType']);
    }

    public function testAggregatesTypeFacetFromReachableCore(): void
    {
        $this->mockSingleCore();

        $response = $this->createJsonResponse([
            'response' => ['numFound' => 575],
            'facet_counts' => [
                'facet_fields' => [
                    'type' => ['pages', 120, 'tt_content', 310, 'news', 20],
                ],
            ],
        ]);
        $this->requestFactory->method('request')->willReturn($response);

        $result = $this->subject->getDocumentCountsByType();

        self::assertTrue($result['reachable']);
        self::assertSame(575, $result['total']);
        self::assertSame(
            [
                ['type' => 'tt_content', 'count' => 310],
                ['type' => 'pages', 'count' => 120],
                ['type' => 'news', 'count' => 20],
            ],
            $result['byType'],
        );
    }

    public function testMarksUnreachableWhenRequestThrows(): void
    {
        $this->mockSingleCore();
        $this->requestFactory->method('request')
            ->willThrowException(new RuntimeException('Connection refused'));

        $result = $this->subject->getDocumentCountsByType();

        self::assertFalse($result['reachable']);
        self::assertSame(0, $result['total']);
        self::assertSame([], $result['byType']);
    }

    private function mockSingleCore(): void
    {
        $site = $this->createMock(Site::class);
        $site->method('getLabel')->willReturn('My Site');

        $endpoint = $this->createMock(Endpoint::class);
        $endpoint->method('getCoreBaseUri')->willReturn('http://solr:8983/solr/core_en/');

        $connection = $this->createMock(SolrConnection::class);
        $connection->method('getEndpoint')->with('read')->willReturn($endpoint);

        $this->siteRepository->method('getAvailableSites')->willReturn([$site]);
        $this->connectionManager->method('getConnectionsBySite')->with($site)->willReturn([$connection]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createJsonResponse(array $payload): ResponseInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode($payload, \JSON_THROW_ON_ERROR));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);

        return $response;
    }
}
