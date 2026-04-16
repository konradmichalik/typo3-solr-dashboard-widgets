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
use KonradMichalik\SolrDashboardWidgets\DataProvider\SolrMetricsDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, StreamInterface};
use RuntimeException;
use Solarium\Core\Client\Endpoint;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * SolrMetricsDataProviderTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 */
final class SolrMetricsDataProviderTest extends TestCase
{
    private SiteRepository&MockObject $siteRepository;
    private ConnectionManager&MockObject $connectionManager;
    private RequestFactory&MockObject $requestFactory;
    private SolrMetricsDataProvider $subject;

    protected function setUp(): void
    {
        $this->siteRepository = $this->createMock(SiteRepository::class);
        $this->connectionManager = $this->createMock(ConnectionManager::class);
        $this->requestFactory = $this->createMock(RequestFactory::class);

        $this->subject = new SolrMetricsDataProvider(
            $this->siteRepository,
            $this->connectionManager,
            $this->requestFactory,
        );
    }

    // --- getJvmMemory ---

    public function testGetJvmMemoryReturnsNullWhenNoSites(): void
    {
        $this->siteRepository->method('getAvailableSites')->willReturn([]);

        self::assertNull($this->subject->getJvmMemory());
    }

    public function testGetJvmMemoryReturnsNullWhenSolrUnreachable(): void
    {
        $this->configureOneCoreEndpoint();
        $this->requestFactory->method('request')->willThrowException(new RuntimeException('timeout'));

        self::assertNull($this->subject->getJvmMemory());
    }

    public function testGetJvmMemoryReturnsHeapData(): void
    {
        $this->configureOneCoreEndpoint();
        $this->mockMetricsResponse([
            'metrics' => [
                'solr.jvm' => [
                    'memory.heap.used' => 256000000,
                    'memory.heap.max' => 512000000,
                ],
            ],
        ]);

        $result = $this->subject->getJvmMemory();

        self::assertNotNull($result);
        self::assertTrue($result['reachable']);
        self::assertSame(256000000, $result['usedBytes']);
        self::assertSame(512000000, $result['maxBytes']);
        self::assertEqualsWithDelta(50.0, $result['usedPercent'], 0.01);
    }

    public function testGetJvmMemoryReturnsNullWhenHeapKeysAreMissing(): void
    {
        $this->configureOneCoreEndpoint();
        $this->mockMetricsResponse([
            'metrics' => [
                'solr.jvm' => ['some.other.metric' => 42],
            ],
        ]);

        self::assertNull($this->subject->getJvmMemory());
    }

    public function testGetJvmMemoryHandlesZeroMaxHeap(): void
    {
        $this->configureOneCoreEndpoint();
        $this->mockMetricsResponse([
            'metrics' => [
                'solr.jvm' => [
                    'memory.heap.used' => 0,
                    'memory.heap.max' => 0,
                ],
            ],
        ]);

        $result = $this->subject->getJvmMemory();

        self::assertNotNull($result);
        self::assertEqualsWithDelta(0.0, $result['usedPercent'], 0.01);
    }

    // --- getQueryPerformance ---

    public function testGetQueryPerformanceReturnsNotReachableWhenNoSites(): void
    {
        $this->siteRepository->method('getAvailableSites')->willReturn([]);

        $result = $this->subject->getQueryPerformance();

        self::assertFalse($result['reachable']);
        self::assertSame(0, $result['totalCount']);
    }

    public function testGetQueryPerformanceAggregatesAcrossCores(): void
    {
        $this->configureOneCoreEndpoint();
        $this->mockMetricsResponse([
            'metrics' => [
                'solr.core.core_en' => [
                    'QUERY./select.requestTimes' => [
                        'count' => 100,
                        '1minRate' => 1.0,
                        'mean_ms' => 10.0,
                        'p95_ms' => 25.0,
                    ],
                ],
            ],
        ]);

        $result = $this->subject->getQueryPerformance();

        self::assertTrue($result['reachable']);
        self::assertSame(100, $result['totalCount']);
        self::assertEqualsWithDelta(60.0, $result['perMinute'], 0.01);
        self::assertEqualsWithDelta(10.0, $result['meanMs'], 0.01);
        self::assertEqualsWithDelta(25.0, $result['p95Ms'], 0.01);
        self::assertCount(1, $result['perCore']);
    }

    // --- SolrCloud prefix matching ---

    public function testGetQueryPerformanceMatchesSolrCloudCoreKeys(): void
    {
        $this->configureOneCoreEndpoint();
        $this->mockMetricsResponse([
            'metrics' => [
                'solr.core.core_en.shard1.replica_n1' => [
                    'QUERY./select.requestTimes' => [
                        'count' => 50,
                        '1minRate' => 0.5,
                        'mean_ms' => 5.0,
                        'p95_ms' => 12.0,
                    ],
                ],
            ],
        ]);

        $result = $this->subject->getQueryPerformance();

        self::assertTrue($result['reachable']);
        self::assertSame(50, $result['totalCount']);
    }

    // --- getCacheHitRates ---

    public function testGetCacheHitRatesReturnsNotReachableWhenNoSites(): void
    {
        $this->siteRepository->method('getAvailableSites')->willReturn([]);

        $result = $this->subject->getCacheHitRates();

        self::assertFalse($result['reachable']);
        self::assertCount(3, $result['caches']);
    }

    public function testGetCacheHitRatesCalculatesRatioFromCumulativeMetrics(): void
    {
        $this->configureOneCoreEndpoint();
        $this->mockMetricsResponse([
            'metrics' => [
                'solr.core.core_en' => [
                    'CACHE.searcher.filterCache' => [
                        'cumulative_lookups' => 200,
                        'cumulative_hits' => 180,
                        'lookups' => 50,
                        'hits' => 40,
                    ],
                    'CACHE.searcher.queryResultCache' => [
                        'lookups' => 100,
                        'hits' => 90,
                    ],
                    'CACHE.searcher.documentCache' => [
                        'lookups' => 0,
                        'hits' => 0,
                    ],
                ],
            ],
        ]);

        $result = $this->subject->getCacheHitRates();

        self::assertTrue($result['reachable']);
        // Filter cache: uses cumulative (200/180 = 90%)
        self::assertEqualsWithDelta(90.0, $result['caches'][0]['hitRatio'], 0.01);
        self::assertSame(200, $result['caches'][0]['lookups']);
        // Query result cache: uses lookups/hits (100/90 = 90%)
        self::assertEqualsWithDelta(90.0, $result['caches'][1]['hitRatio'], 0.01);
        // Document cache: 0 lookups = 0% ratio
        self::assertEqualsWithDelta(0.0, $result['caches'][2]['hitRatio'], 0.01);
    }

    public function testGetCacheHitRatesMatchesSolrCloudCoreKeys(): void
    {
        $this->configureOneCoreEndpoint();
        $this->mockMetricsResponse([
            'metrics' => [
                'solr.core.core_en.shard1.replica_n1' => [
                    'CACHE.searcher.filterCache' => ['lookups' => 10, 'hits' => 8],
                    'CACHE.searcher.queryResultCache' => ['lookups' => 10, 'hits' => 7],
                    'CACHE.searcher.documentCache' => ['lookups' => 10, 'hits' => 9],
                ],
            ],
        ]);

        $result = $this->subject->getCacheHitRates();

        self::assertTrue($result['reachable']);
        self::assertEqualsWithDelta(80.0, $result['caches'][0]['hitRatio'], 0.01);
    }

    // --- getSolrVersion ---

    public function testGetSolrVersionReturnsNullWhenNoSites(): void
    {
        $this->siteRepository->method('getAvailableSites')->willReturn([]);

        self::assertNull($this->subject->getSolrVersion());
    }

    public function testGetSolrVersionReturnsSpecVersion(): void
    {
        $this->configureOneCoreEndpoint();

        $this->requestFactory->method('request')->willReturn(
            $this->createJsonResponse(['lucene' => ['solr-spec-version' => '9.8.1']]),
        );

        self::assertSame('9.8.1', $this->subject->getSolrVersion());
    }

    public function testGetSolrVersionFallsBackToImplVersion(): void
    {
        $this->configureOneCoreEndpoint();

        $this->requestFactory->method('request')->willReturn(
            $this->createJsonResponse(['lucene' => ['solr-impl-version' => '9.10.0']]),
        );

        self::assertSame('9.10.0', $this->subject->getSolrVersion());
    }

    public function testGetSolrVersionReturnsNullOnError(): void
    {
        $this->configureOneCoreEndpoint();
        $this->requestFactory->method('request')->willThrowException(new RuntimeException('timeout'));

        self::assertNull($this->subject->getSolrVersion());
    }

    // --- Auth forwarding ---

    public function testRequestsIncludeAuthWhenConfigured(): void
    {
        $this->configureOneCoreEndpoint('solr', 'SolrRocks');

        $this->requestFactory
            ->expects(self::atLeastOnce())
            ->method('request')
            ->with(
                self::anything(),
                'GET',
                self::callback(static fn (array $options): bool => isset($options['auth'])
                    && 'solr' === $options['auth'][0]
                    && 'SolrRocks' === $options['auth'][1]),
            )
            ->willReturn($this->createJsonResponse([
                'metrics' => [
                    'solr.jvm' => [
                        'memory.heap.used' => 100,
                        'memory.heap.max' => 200,
                    ],
                ],
            ]));

        $this->subject->getJvmMemory();
    }

    public function testRequestsOmitAuthWhenNotConfigured(): void
    {
        $this->configureOneCoreEndpoint();

        $this->requestFactory
            ->expects(self::atLeastOnce())
            ->method('request')
            ->with(
                self::anything(),
                'GET',
                self::callback(static fn (array $options): bool => !isset($options['auth'])),
            )
            ->willReturn($this->createJsonResponse([
                'metrics' => [
                    'solr.jvm' => [
                        'memory.heap.used' => 100,
                        'memory.heap.max' => 200,
                    ],
                ],
            ]));

        $this->subject->getJvmMemory();
    }

    // --- Metrics caching ---

    public function testMetricsAreCachedPerHost(): void
    {
        $this->configureOneCoreEndpoint();

        $this->requestFactory
            ->expects(self::once())
            ->method('request')
            ->willReturn($this->createJsonResponse([
                'metrics' => [
                    'solr.jvm' => [
                        'memory.heap.used' => 100,
                        'memory.heap.max' => 200,
                    ],
                    'solr.core.core_en' => [
                        'QUERY./select.requestTimes' => [
                            'count' => 10,
                            '1minRate' => 0.1,
                            'mean_ms' => 1.0,
                            'p95_ms' => 2.0,
                        ],
                    ],
                ],
            ]));

        // Two calls should only trigger one HTTP request
        $this->subject->getJvmMemory();
        $this->subject->getQueryPerformance();
    }

    // --- Helpers ---

    private function configureOneCoreEndpoint(?string $username = null, ?string $password = null): void
    {
        $site = $this->createMock(Site::class);
        $site->method('getLabel')->willReturn('Test Site');

        $endpoint = $this->createMock(Endpoint::class);
        $endpoint->method('getCoreBaseUri')->willReturn('http://solr:8983/solr/core_en/');
        $endpoint->method('getCore')->willReturn('core_en');
        $endpoint->method('getAuthentication')->willReturn([
            'username' => $username,
            'password' => $password,
        ]);

        $connection = $this->createMock(SolrConnection::class);
        $connection->method('getEndpoint')->with('read')->willReturn($endpoint);

        $this->siteRepository->method('getAvailableSites')->willReturn([$site]);
        $this->connectionManager->method('getConnectionsBySite')->with($site)->willReturn([$connection]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function mockMetricsResponse(array $data): void
    {
        $this->requestFactory->method('request')->willReturn($this->createJsonResponse($data));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createJsonResponse(array $data): ResponseInterface&MockObject
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode($data, \JSON_THROW_ON_ERROR));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);

        return $response;
    }
}
