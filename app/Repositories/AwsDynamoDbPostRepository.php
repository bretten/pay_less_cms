<?php


namespace App\Repositories;


use App\Contracts\Models\Post;
use App\Support\DateTimeFactoryInterface;
use App\Support\UniqueIdFactoryInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use DateTime;
use DateTimeZone;

class AwsDynamoDbPostRepository implements PostRepositoryInterface
{
    /**
     * @var DynamoDbClient $client
     */
    private DynamoDbClient $client;

    /**
     * @var string $tableName
     */
    private string $tableName;

    /**
     * @var DateTimeFactoryInterface $dateTimeFactory
     */
    private DateTimeFactoryInterface $dateTimeFactory;

    /**
     * @var UniqueIdFactoryInterface $uniqueIdFactory
     */
    private UniqueIdFactoryInterface $uniqueIdFactory;

    /**
     * @var Marshaler $marshaler
     */
    private Marshaler $marshaler;

    /**
     * Constructor
     *
     * @param DynamoDbClient $client
     * @param string $tableName
     * @param DateTimeFactoryInterface $dateTimeFactory
     * @param UniqueIdFactoryInterface $uniqueIdFactory
     */
    public function __construct(DynamoDbClient $client, string $tableName, DateTimeFactoryInterface $dateTimeFactory, UniqueIdFactoryInterface $uniqueIdFactory)
    {
        $this->client = $client;
        $this->tableName = $tableName;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->uniqueIdFactory = $uniqueIdFactory;
        $this->marshaler = new Marshaler();
    }

    /**
     * Gets all Posts
     *
     * @return Post[]
     * @throws \Exception
     */
    public function getAll()
    {
        $queryParams = [
            'TableName' => $this->tableName,
            'IndexName' => 'GSI1',
            'ExpressionAttributeValues' => [
                ':pk' => [
                    'S' => 'POST'
                ]
            ],
            'KeyConditionExpression' => 'GSI1PK = :pk'
        ];
        $posts = [];
        while (true) {
            $result = $this->client->query($queryParams);
            $posts = array_merge($posts, $this->unmarshalPosts($result['Items']));

            if (isset($result['LastEvaluatedKey'])) {
                $queryParams['ExclusiveStartKey'] = $result['LastEvaluatedKey'];
            } else {
                break;
            }
        }

        return $posts;
    }

    /**
     * Returns the Post specified by the ID
     *
     * @param $id
     * @return Post
     * @throws \Exception
     */
    public function getById($id)
    {
        $queryParams = [
            'TableName' => $this->tableName,
            'IndexName' => 'GSI1',
            'ExpressionAttributeValues' => [
                ':pk' => [
                    'S' => 'POST'
                ],
                ':sk' => [
                    'S' => $id
                ]
            ],
            'KeyConditionExpression' => 'GSI1PK = :pk and GSI1SK = :sk'
        ];
        $result = $this->client->query($queryParams);
        return !empty($result['Items']) ? $this->unmarshalPost($result['Items'][0]) : null;
    }

    /**
     * Creates a new Post with the specified parameters
     *
     * @param string $site
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @return bool
     */
    public function create(string $site, string $title, string $content, string $humanReadableUrl)
    {
        $now = $this->dateTimeFactory->getUtcNow();
        $sortableByTimePostId = $this->uniqueIdFactory->generateSortableByTimeUniqueId();
        $post = [
            // Standard attributes
            'PK' => $this->getPartitionKey($site),
            'SK' => $this->getSortKey($sortableByTimePostId),
            'id' => $sortableByTimePostId,
            'site' => $site,
            'title' => $title,
            'content' => $content,
            'human_readable_url' => $humanReadableUrl,
            'created_at' => $now->getTimestamp(),
            'updated_at' => $now->getTimestamp(),
            'deleted_at' => null,
            // GSI1 attributes
            'GSI1PK' => $this->getGlobalSecondaryIndex1PartitionKey(),
            'GSI1SK' => $this->getGlobalSecondaryIndex1SortKey($sortableByTimePostId)
        ];
        $result = $this->client->putItem([
            'TableName' => $this->tableName,
            'Item' => $this->marshaler->marshalItem($post)
        ]);
        return 200 == $result['@metadata']['statusCode'];
    }

    /**
     * Updates the Post indicated by the ID with the new parameters
     *
     * @param $id
     * @param string $site
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @return bool
     */
    public function update($id, string $site, string $title, string $content, string $humanReadableUrl)
    {
        $now = $this->dateTimeFactory->getUtcNow();
        $result = $this->client->updateItem([
            'TableName' => $this->tableName,
            'Key' => $this->marshaler->marshalItem([
                'PK' => $this->getPartitionKey($site),
                'SK' => $this->getSortKey($id)
            ]),
            'UpdateExpression' => 'set site = :s, title = :t, content = :c, human_readable_url = :url, updated_at = :ua',
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                ':s' => $site,
                ':t' => $title,
                ':c' => $content,
                ':url' => $humanReadableUrl,
                ':ua' => $now->getTimestamp()
            ])
        ]);
        return 200 == $result['@metadata']['statusCode'];
    }

    /**
     * Deletes the Post indicated by the ID
     *
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function delete($id)
    {
        // Can only update using the composite key (PK + SK). Cannot update on a GSI. Changes are propagated to the GSIs after the table is updated.
        // For now, retrieve row first by ID
        $post = $this->getById($id);

        $now = $this->dateTimeFactory->getUtcNow();
        $result = $this->client->updateItem([
            'TableName' => $this->tableName,
            'Key' => $this->marshaler->marshalItem([
                'PK' => $this->getPartitionKey($post->site),
                'SK' => $this->getSortKey($id)
            ]),
            'UpdateExpression' => 'set updated_at = :ua, deleted_at = :da',
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                ':ua' => $now->getTimestamp(),
                ':da' => $now->getTimestamp()
            ])
        ]);
        return 200 == $result['@metadata']['statusCode'];
    }

    /**
     * Gets the partition key for a Post
     *
     * @param string $site
     * @return string
     */
    private function getPartitionKey(string $site)
    {
        return 'SITE#' . $site;
    }

    /**
     * Gets the sort key for a Post
     *
     * @param string $sortableByTimeId An ID that can be sorted by time as a string
     * @return string
     */
    private function getSortKey(string $sortableByTimeId)
    {
        return 'POST#' . $sortableByTimeId;
    }

    /**
     * Gets the partition key of Global Secondary Index #1 for a Post
     *
     * @return string
     */
    private function getGlobalSecondaryIndex1PartitionKey()
    {
        return 'POST';
    }

    /**
     * Gets the sort key of Global Secondary Index #1 for a Post
     *
     * @param string $sortableByTimeId An ID that can be sorted by time as a string
     * @return string
     */
    private function getGlobalSecondaryIndex1SortKey(string $sortableByTimeId)
    {
        return $sortableByTimeId;
    }

    /**
     * Converts a DynamoDb item to a Post
     *
     * @param $item
     * @return Post
     * @throws \Exception
     */
    private function unmarshalPost($item)
    {
        $item = $this->marshaler->unmarshalItem($item);
        return new Post($item['id'], $item['site'], $item['title'], $item['content'], $item['human_readable_url'],
            new DateTime('@' . $item['created_at'], new DateTimeZone('UTC')),
            new DateTime('@' . $item['updated_at'], new DateTimeZone('UTC')),
            $item['deleted_at'] ? new DateTime('@' . $item['deleted_at'], new DateTimeZone('UTC')) : null);
    }

    /**
     * Converts a collection of DynamoDb items to Posts
     *
     * @param $items
     * @return Post[]
     * @throws \Exception
     */
    private function unmarshalPosts($items)
    {
        $posts = [];
        foreach ($items as $item) {
            $posts[] = $this->unmarshalPost($item);
        }
        return $posts;
    }
}
