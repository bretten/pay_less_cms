<?php


namespace Repositories;


use App\Contracts\Models\Post;
use App\Repositories\AwsDynamoDbPostRepository;
use App\Support\DateTimeFactoryInterface;
use App\Support\UniqueIdFactoryInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Result;
use DateTime;
use DateTimeZone;
use Mockery;
use PHPUnit\Framework\TestCase;

class AwsDynamoDbPostRepositoryTest extends TestCase
{
    /**
     * Tests that the repository can get all Posts
     *
     * @return void
     * @throws \Exception
     */
    public function testGetAll()
    {
        // Setup
        $table = 'test-table';
        $client = Mockery::mock(DynamoDbClient::class, function ($mock) use ($table) {
            $mock->shouldReceive('scan')
                ->with([
                    'TableName' => $table
                ])
                ->andReturn(new Result([
                    'LastEvaluatedKey' => 'lastKey1',
                    'Items' => [
                        [
                            'id' => ['N' => 1],
                            'site' => ['S' => 'site1'],
                            'title' => ['S' => 'title1'],
                            'content' => ['S' => 'content1'],
                            'human_readable_url' => ['S' => 'url1'],
                            'created_at' => ['N' => 1597553412],
                            'updated_at' => ['N' => 1597560710],
                            'deleted_at' => ['NULL' => true],
                        ],
                        [
                            'id' => ['N' => 2],
                            'site' => ['S' => 'site1'],
                            'title' => ['S' => 'title2'],
                            'content' => ['S' => 'content2'],
                            'human_readable_url' => ['S' => 'url2'],
                            'created_at' => ['N' => 1597553422],
                            'updated_at' => ['N' => 1597560710],
                            'deleted_at' => ['N' => 1597560710],
                        ]
                    ]
                ]));
            $mock->shouldReceive('scan')
                ->with([
                    'TableName' => $table,
                    'ExclusiveStartKey' => 'lastKey1'
                ])
                ->andReturn(new Result([
                    'Items' => [
                        [
                            'id' => ['N' => 3],
                            'site' => ['S' => 'site2'],
                            'title' => ['S' => 'title3'],
                            'content' => ['S' => 'content3'],
                            'human_readable_url' => ['S' => 'url3'],
                            'created_at' => ['N' => 1597553432],
                            'updated_at' => ['N' => 1597560710],
                            'deleted_at' => ['NULL' => true],
                        ]
                    ]
                ]));
        });
        $repo = new AwsDynamoDbPostRepository($client, $table, Mockery::mock(DateTimeFactoryInterface::class), Mockery::mock(UniqueIdFactoryInterface::class));

        $expectedPosts = [
            new Post(1, 'site1', 'title1', 'content1', 'url1', new DateTime('@1597553412', new DateTimeZone('UTC')), new DateTime('@1597560710', new DateTimeZone('UTC')), null),
            new Post(2, 'site1', 'title2', 'content2', 'url2', new DateTime('@1597553422', new DateTimeZone('UTC')), new DateTime('@1597560710', new DateTimeZone('UTC')), new DateTime('@1597560710', new DateTimeZone('UTC'))),
            new Post(3, 'site2', 'title3', 'content3', 'url3', new DateTime('@1597553432', new DateTimeZone('UTC')), new DateTime('@1597560710', new DateTimeZone('UTC')), null),
        ];

        // Execute
        $result = $repo->getAll();

        // Assert
        $this->assertEquals($expectedPosts, $result);
    }

    /**
     * Tests that the repository can get a Post by ID
     *
     * @return void
     * @throws \Exception
     */
    public function testGetById()
    {
        // Setup
        $table = 'test-table';
        $client = Mockery::mock(DynamoDbClient::class, function ($mock) use ($table) {
            $mock->shouldReceive('getItem')
                ->with([
                    'TableName' => $table,
                    'Key' => [
                        'id' => ['N' => '1']
                    ]
                ])
                ->andReturn(new Result([
                    'Item' => [
                        'id' => ['N' => 1],
                        'site' => ['S' => 'site1'],
                        'title' => ['S' => 'title1'],
                        'content' => ['S' => 'content1'],
                        'human_readable_url' => ['S' => 'url1'],
                        'created_at' => ['N' => 1597553412],
                        'updated_at' => ['N' => 1597560710],
                        'deleted_at' => ['NULL' => true],
                    ]
                ]));
        });
        $repo = new AwsDynamoDbPostRepository($client, $table, Mockery::mock(DateTimeFactoryInterface::class), Mockery::mock(UniqueIdFactoryInterface::class));

        $expectedPost = new Post(1, 'site1', 'title1', 'content1', 'url1', new DateTime('@1597553412', new DateTimeZone('UTC')), new DateTime('@1597560710', new DateTimeZone('UTC')), null);

        // Execute
        $result = $repo->getById(1);

        // Assert
        $this->assertEquals($expectedPost, $result);
    }

    /**
     * Tests that the repository can create a Post
     *
     * @return void
     * @throws \Exception
     */
    public function testCreate()
    {
        // Setup
        $table = 'test-table';
        $now = new DateTime('2020-08-16 02:01:01');
        $id = 1;
        $dateTimeFactory = Mockery::mock(DateTimeFactoryInterface::class, function ($mock) use ($now) {
            $mock->shouldReceive('getUtcNow')
                ->times(1)
                ->andReturn($now);
        });
        $uniqueIdFactory = Mockery::mock(UniqueIdFactoryInterface::class, function ($mock) use ($id) {
            $mock->shouldReceive('generateUniqueId')
                ->times(1)
                ->andReturn($id);
        });
        $client = Mockery::mock(DynamoDbClient::class, function ($mock) use ($table, $id, $now) {
            $mock->shouldReceive('putItem')
                ->with([
                    'TableName' => $table,
                    'Item' => [
                        'id' => ['N' => 1],
                        'site' => ['S' => 'site1'],
                        'title' => ['S' => 'title1'],
                        'content' => ['S' => 'content1'],
                        'human_readable_url' => ['S' => 'url1'],
                        'created_at' => ['N' => $now->getTimestamp()],
                        'updated_at' => ['N' => $now->getTimestamp()],
                        'deleted_at' => ['NULL' => true],
                    ]
                ])
                ->andReturn(new Result([
                    '@metadata' => [
                        'statusCode' => 200
                    ]
                ]));
        });
        $repo = new AwsDynamoDbPostRepository($client, $table, $dateTimeFactory, $uniqueIdFactory);

        // Execute
        $result = $repo->create('site1', 'title1', 'content1', 'url1');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Tests that the repository can edit a Post
     *
     * @return void
     * @throws \Exception
     */
    public function testUpdate()
    {
        // Setup
        $table = 'test-table';
        $id = 1;
        $site = 'site1 v2';
        $title = 'title1 v2';
        $content = 'content1 v2';
        $url = 'url1 v2';
        $now = new DateTime('2020-08-16 02:01:01');
        $dateTimeFactory = Mockery::mock(DateTimeFactoryInterface::class, function ($mock) use ($now) {
            $mock->shouldReceive('getUtcNow')
                ->times(1)
                ->andReturn($now);
        });
        $client = Mockery::mock(DynamoDbClient::class, function ($mock) use ($table, $id, $site, $title, $content, $url, $now) {
            $mock->shouldReceive('updateItem')
                ->with([
                    'TableName' => $table,
                    'Key' => [
                        'id' => ['N' => $id]
                    ],
                    'UpdateExpression' => 'set site = :s, title = :t, content = :c, human_readable_url = :url, updated_at = :ua',
                    'ExpressionAttributeValues' => [
                        ':s' => ['S' => $site],
                        ':t' => ['S' => $title],
                        ':c' => ['S' => $content],
                        ':url' => ['S' => $url],
                        ':ua' => ['N' => $now->getTimestamp()]
                    ]
                ])
                ->andReturn(new Result([
                    '@metadata' => [
                        'statusCode' => 200
                    ]
                ]));
        });
        $repo = new AwsDynamoDbPostRepository($client, $table, $dateTimeFactory, Mockery::Mock(UniqueIdFactoryInterface::class));

        // Execute
        $result = $repo->update($id, $site, $title, $content, $url);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Tests that the repository can delete a Post by ID
     *
     * @return void
     * @throws \Exception
     */
    public function testDelete()
    {
        // Setup
        $table = 'test-table';
        $id = 1;
        $now = new DateTime('2020-08-16 02:01:01');
        $dateTimeFactory = Mockery::mock(DateTimeFactoryInterface::class, function ($mock) use ($now) {
            $mock->shouldReceive('getUtcNow')
                ->times(1)
                ->andReturn($now);
        });
        $client = Mockery::mock(DynamoDbClient::class, function ($mock) use ($table, $id, $now) {
            $mock->shouldReceive('updateItem')
                ->with([
                    'TableName' => $table,
                    'Key' => [
                        'id' => ['N' => $id]
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
        $repo = new AwsDynamoDbPostRepository($client, $table, $dateTimeFactory, Mockery::mock(UniqueIdFactoryInterface::class));

        // Execute
        $result = $repo->delete($id);

        // Assert
        $this->assertTrue($result);
    }
}
