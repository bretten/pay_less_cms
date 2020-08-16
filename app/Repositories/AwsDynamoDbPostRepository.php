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
        $scanParams = [
            'TableName' => $this->tableName
        ];
        $posts = [];
        while (true) {
            $result = $this->client->scan($scanParams);
            $posts = array_merge($posts, $this->unmarshalPosts($result['Items']));

            if (isset($result['LastEvaluatedKey'])) {
                $scanParams['ExclusiveStartKey'] = $result['LastEvaluatedKey'];
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
        $result = $this->client->getItem([
            'TableName' => $this->tableName,
            'Key' => $this->marshaler->marshalItem([
                'id' => $id
            ])
        ]);
        return $this->unmarshalPost($result['Item']);
    }

    /**
     * Creates a new Post with the specified parameters
     *
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @return bool
     * @throws \Exception
     */
    public function create(string $title, string $content, string $humanReadableUrl)
    {
        $now = $this->dateTimeFactory->getUtcNow();
        $post = [
            'id' => $this->uniqueIdFactory->generateUniqueId(),
            'title' => $title,
            'content' => $content,
            'human_readable_url' => $humanReadableUrl,
            'created_at' => $now->getTimestamp(),
            'updated_at' => $now->getTimestamp(),
            'deleted_at' => null
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
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @return bool
     * @throws \Exception
     */
    public function update($id, string $title, string $content, string $humanReadableUrl)
    {
        $now = $this->dateTimeFactory->getUtcNow();
        $result = $this->client->updateItem([
            'TableName' => $this->tableName,
            'Key' => $this->marshaler->marshalItem([
                'id' => $id
            ]),
            'UpdateExpression' => 'set title = :t, content = :c, human_readable_url = :url, updated_at = :ua',
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([
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
        $now = $this->dateTimeFactory->getUtcNow();
        $result = $this->client->updateItem([
            'TableName' => $this->tableName,
            'Key' => $this->marshaler->marshalItem([
                'id' => $id
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
     * Converts a DynamoDb item to a Post
     *
     * @param $item
     * @return Post
     * @throws \Exception
     */
    private function unmarshalPost($item)
    {
        $item = $this->marshaler->unmarshalItem($item);
        return new Post($item['id'], $item['title'], $item['content'], $item['human_readable_url'],
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
