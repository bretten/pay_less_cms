<?php


namespace Tests\Unit\Repositories;


use App\Contracts\Models\Site;
use App\Repositories\AwsDynamoDbSiteRepository;
use App\Support\DateTimeFactoryInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Result;
use DateTime;
use DateTimeZone;
use Mockery;
use PHPUnit\Framework\TestCase;

class AwsDynamoDbSiteRepositoryTest extends TestCase
{
    /**
     * Tests that the repository can get all Sites
     *
     * @return void
     * @throws \Exception
     */
    public function testGetAll()
    {
        // Setup
        $table = 'test-table';
        $client = Mockery::mock(DynamoDbClient::class, function ($mock) use ($table) {
            $mock->shouldReceive('query')
                ->with([
                    'TableName' => $table,
                    'IndexName' => 'GSI1',
                    'ExpressionAttributeValues' => [
                        ':pk' => [
                            'S' => 'SITE'
                        ]
                    ],
                    'KeyConditionExpression' => 'GSI1PK = :pk'
                ])
                ->andReturn(new Result([
                    'LastEvaluatedKey' => 'lastKey1',
                    'Items' => [
                        [
                            'domain_name' => ['S' => 'site1'],
                            'title' => ['S' => 'title1'],
                            'created_at' => ['N' => 1622536260],
                            'updated_at' => ['N' => 1622536260],
                            'deleted_at' => ['NULL' => true]
                        ],
                        [
                            'domain_name' => ['S' => 'site2'],
                            'title' => ['S' => 'title2'],
                            'created_at' => ['N' => 1622536260],
                            'updated_at' => ['N' => 1622536260],
                            'deleted_at' => ['N' => 1622536260]
                        ]
                    ]
                ]));
            $mock->shouldReceive('query')
                ->with([
                    'TableName' => $table,
                    'IndexName' => 'GSI1',
                    'ExpressionAttributeValues' => [
                        ':pk' => [
                            'S' => 'SITE'
                        ]
                    ],
                    'KeyConditionExpression' => 'GSI1PK = :pk',
                    'ExclusiveStartKey' => 'lastKey1'
                ])
                ->andReturn(new Result([
                    'Items' => [
                        [
                            'domain_name' => ['S' => 'site3'],
                            'title' => ['S' => 'title3'],
                            'created_at' => ['N' => 1622536260],
                            'updated_at' => ['N' => 1622536260],
                            'deleted_at' => ['NULL' => true]
                        ]
                    ]
                ]));
        });
        $repo = new AwsDynamoDbSiteRepository($client, $table, Mockery::mock(DateTimeFactoryInterface::class));

        $expectedSites = [
            new Site('site1', 'title1', new DateTime('@1622536260', new DateTimeZone('UTC')), new DateTime('@1622536260', new DateTimeZone('UTC')), null),
            new Site('site2', 'title2', new DateTime('@1622536260', new DateTimeZone('UTC')), new DateTime('@1622536260', new DateTimeZone('UTC')), new DateTime('@1622536260', new DateTimeZone('UTC'))),
            new Site('site3', 'title3', new DateTime('@1622536260', new DateTimeZone('UTC')), new DateTime('@1622536260', new DateTimeZone('UTC')), null),
        ];

        // Execute
        $result = $repo->getAll();

        // Assert
        $this->assertEquals($expectedSites, $result);
    }

    /**
     * Tests that the repository can get a Site by its domain name
     *
     * @return void
     * @throws \Exception
     */
    public function testGetByDomainName()
    {
        // Setup
        $table = 'test-table';
        $client = Mockery::mock(DynamoDbClient::class, function ($mock) use ($table) {
            $mock->shouldReceive('getItem')
                ->with([
                    'TableName' => $table,
                    'Key' => [
                        'PK' => ['S' => 'SITE#site1'],
                        'SK' => ['S' => 'SITE_DATA#site1']
                    ]
                ])
                ->andReturn(new Result([
                    'Item' => [
                        'domain_name' => ['S' => 'site1'],
                        'title' => ['S' => 'title1'],
                        'created_at' => ['N' => 1622536260],
                        'updated_at' => ['N' => 1622536260],
                        'deleted_at' => ['NULL' => true]
                    ]
                ]));
        });
        $repo = new AwsDynamoDbSiteRepository($client, $table, Mockery::mock(DateTimeFactoryInterface::class));

        $expectedSite = new Site('site1', 'title1', new DateTime('@1622536260', new DateTimeZone('UTC')), new DateTime('@1622536260', new DateTimeZone('UTC')), null);

        // Execute
        $result = $repo->getByDomainName('site1');

        // Assert
        $this->assertEquals($expectedSite, $result);
    }

    /**
     * Tests that the repository can create a Site
     *
     * @return void
     * @throws \Exception
     */
    public function testCreate()
    {
        // Setup
        $table = 'test-table';
        $now = new DateTime('2021-06-01 01:59:01');
        $dateTimeFactory = Mockery::mock(DateTimeFactoryInterface::class, function ($mock) use ($now) {
            $mock->shouldReceive('getUtcNow')
                ->times(1)
                ->andReturn($now);
        });
        $client = Mockery::mock(DynamoDbClient::class, function ($mock) use ($table, $now) {
            $mock->shouldReceive('putItem')
                ->with([
                    'TableName' => $table,
                    'Item' => [
                        // Standard attributes
                        'PK' => ['S' => 'SITE#site1'],
                        'SK' => ['S' => 'SITE_DATA#site1'],
                        'domain_name' => ['S' => 'site1'],
                        'title' => ['S' => 'title1'],
                        'created_at' => ['N' => $now->getTimestamp()],
                        'updated_at' => ['N' => $now->getTimestamp()],
                        'deleted_at' => ['NULL' => true],
                        // GSI1 attributes
                        'GSI1PK' => ['S' => 'SITE'],
                        'GSI1SK' => ['S' => 'site1']
                    ]
                ])
                ->andReturn(new Result([
                    '@metadata' => [
                        'statusCode' => 200
                    ]
                ]));
        });
        $repo = new AwsDynamoDbSiteRepository($client, $table, $dateTimeFactory);

        // Execute
        $result = $repo->create('site1', 'title1');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Tests that the repository can update a Site
     *
     * @return void
     * @throws \Exception
     */
    public function testUpdate()
    {
        // Setup
        $table = 'test-table';
        $now = new DateTime('2021-06-01 01:59:01');
        $dateTimeFactory = Mockery::mock(DateTimeFactoryInterface::class, function ($mock) use ($now) {
            $mock->shouldReceive('getUtcNow')
                ->times(1)
                ->andReturn($now);
        });
        $client = Mockery::mock(DynamoDbClient::class, function ($mock) use ($table, $now) {
            $mock->shouldReceive('updateItem')
                ->with([
                    'TableName' => $table,
                    'Key' => [
                        'PK' => ['S' => 'SITE#site1'],
                        'SK' => ['S' => 'SITE_DATA#site1']
                    ],
                    'UpdateExpression' => 'set title = :t, updated_at = :ua',
                    'ExpressionAttributeValues' => [
                        ':t' => ['S' => 'title1 v2'],
                        ':ua' => ['N' => $now->getTimestamp()]
                    ]
                ])
                ->andReturn(new Result([
                    '@metadata' => [
                        'statusCode' => 200
                    ]
                ]));
        });
        $repo = new AwsDynamoDbSiteRepository($client, $table, $dateTimeFactory);

        // Execute
        $result = $repo->update('site1', 'title1 v2');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Tests that the repository can delete a Site
     *
     * @return void
     * @throws \Exception
     */
    public function testDelete()
    {
        // Setup
        $table = 'test-table';
        $now = new DateTime('2021-06-01 01:59:01');
        $dateTimeFactory = Mockery::mock(DateTimeFactoryInterface::class, function ($mock) use ($now) {
            $mock->shouldReceive('getUtcNow')
                ->times(1)
                ->andReturn($now);
        });
        $client = Mockery::mock(DynamoDbClient::class, function ($mock) use ($table, $now) {
            $mock->shouldReceive('updateItem')
                ->with([
                    'TableName' => $table,
                    'Key' => [
                        'PK' => ['S' => 'SITE#site1'],
                        'SK' => ['S' => 'SITE_DATA#site1']
                    ],
                    'UpdateExpression' => 'set updated_at = :ua, deleted_at = :da',
                    'ExpressionAttributeValues' => [
                        ':ua' => ['N' => $now->getTimestamp()],
                        ':da' => ['N' => $now->getTimestamp()]
                    ]
                ])
                ->andReturn(new Result([
                    '@metadata' => [
                        'statusCode' => 200
                    ]
                ]));
        });
        $repo = new AwsDynamoDbSiteRepository($client, $table, $dateTimeFactory);

        // Execute
        $result = $repo->delete('site1');

        // Assert
        $this->assertTrue($result);
    }
}
