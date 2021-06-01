<?php


namespace App\Repositories;


use App\Contracts\Models\Site;
use App\Support\DateTimeFactoryInterface;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use DateTime;
use DateTimeZone;

class AwsDynamoDbSiteRepository implements SiteRepositoryInterface
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
     * @var Marshaler $marshaler
     */
    private Marshaler $marshaler;

    /**
     * Constructor
     *
     * @param DynamoDbClient $client
     * @param string $tableName
     * @param DateTimeFactoryInterface $dateTimeFactory
     */
    public function __construct(DynamoDbClient $client, string $tableName, DateTimeFactoryInterface $dateTimeFactory)
    {
        $this->client = $client;
        $this->tableName = $tableName;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->marshaler = new Marshaler();
    }

    /**
     * Returns all Sites
     */
    public function getAll()
    {
        $queryParams = [
            'TableName' => $this->tableName,
            'IndexName' => 'GSI1',
            'ExpressionAttributeValues' => [
                ':pk' => [
                    'S' => 'SITE',
                ],
            ],
            'KeyConditionExpression' => 'GSI1PK = :pk'
        ];
        $sites = [];
        while (true) {
            $result = $this->client->query($queryParams);
            $sites = array_merge($sites, $this->unmarshalSites($result['Items']));

            if (isset($result['LastEvaluatedKey'])) {
                $queryParams['ExclusiveStartKey'] = $result['LastEvaluatedKey'];
            } else {
                break;
            }
        }

        return $sites;
    }

    /**
     * Returns the Site by the domain name
     *
     * @param string $domainName
     * @return Site
     * @throws \Exception
     */
    public function getByDomainName(string $domainName)
    {
        $result = $this->client->getItem([
            'TableName' => $this->tableName,
            'Key' => $this->marshaler->marshalItem([
                'PK' => $this->getPartitionKey($domainName),
                'SK' => $this->getSortKey($domainName)
            ])
        ]);
        return $this->unmarshalSite($result['Item']);
    }

    /**
     * Creates a Site
     *
     * @param string $domainName
     * @param string $title
     * @return bool
     */
    public function create(string $domainName, string $title)
    {
        $now = $this->dateTimeFactory->getUtcNow();
        $site = [
            // Standard attributes
            'PK' => $this->getPartitionKey($domainName),
            'SK' => $this->getSortKey($domainName),
            'domain_name' => $domainName,
            'title' => $title,
            'created_at' => $now->getTimestamp(),
            'updated_at' => $now->getTimestamp(),
            'deleted_at' => null,
            // GSI1 attributes
            'GSI1PK' => $this->getGlobalSecondaryIndex1PartitionKey(),
            'GSI1SK' => $this->getGlobalSecondaryIndex1SortKey($domainName)
        ];
        $result = $this->client->putItem([
            'TableName' => $this->tableName,
            'Item' => $this->marshaler->marshalItem($site)
        ]);
        return 200 == $result['@metadata']['statusCode'];
    }

    /**
     * Updates a Site
     *
     * @param string $domainName
     * @param string $title
     * @return bool
     */
    public function update(string $domainName, string $title)
    {
        $now = $this->dateTimeFactory->getUtcNow();
        $result = $this->client->updateItem([
            'TableName' => $this->tableName,
            'Key' => $this->marshaler->marshalItem([
                'PK' => $this->getPartitionKey($domainName),
                'SK' => $this->getSortKey($domainName)
            ]),
            'UpdateExpression' => 'set title = :t, updated_at = :ua',
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                ':t' => $title,
                ':ua' => $now->getTimestamp()
            ])
        ]);
        return 200 == $result['@metadata']['statusCode'];
    }

    /**
     * Deletes a Site
     *
     * @param string $domainName
     * @return bool
     */
    public function delete(string $domainName)
    {
        $now = $this->dateTimeFactory->getUtcNow();
        $result = $this->client->updateItem([
            'TableName' => $this->tableName,
            'Key' => $this->marshaler->marshalItem([
                'PK' => $this->getPartitionKey($domainName),
                'SK' => $this->getSortKey($domainName)
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
     * Gets the partition key for a Site
     *
     * @param string $domainName
     * @return string
     */
    private function getPartitionKey(string $domainName)
    {
        return 'SITE#' . $domainName;
    }

    /**
     * Gets the sort key for a Site
     *
     * @param string $domainName
     * @return string
     */
    private function getSortKey(string $domainName)
    {
        return 'SITE_DATA#' . $domainName;
    }

    /**
     * Gets the partition key of Global Secondary Index #1 for a Site
     *
     * @return string
     */
    private function getGlobalSecondaryIndex1PartitionKey()
    {
        return 'SITE';
    }

    /**
     * Gets the sort key of Global Secondary Index #1 for a Site
     *
     * @param string $domainName
     * @return string
     */
    private function getGlobalSecondaryIndex1SortKey(string $domainName)
    {
        return $domainName;
    }

    /**
     * Converts a DynamoDb item to a Site
     *
     * @param $item
     * @return Site
     * @throws \Exception
     */
    private function unmarshalSite($item)
    {
        $item = $this->marshaler->unmarshalItem($item);
        return new Site($item['domain_name'], $item['title'],
            new DateTime('@' . $item['created_at'], new DateTimeZone('UTC')),
            new DateTime('@' . $item['updated_at'], new DateTimeZone('UTC')),
            $item['deleted_at'] ? new DateTime('@' . $item['deleted_at'], new DateTimeZone('UTC')) : null);
    }

    /**
     * Converts a collection of DynamoDb items to Sites
     *
     * @param $items
     * @return Site[]
     * @throws \Exception
     */
    private function unmarshalSites($items)
    {
        $sites = [];
        foreach ($items as $item) {
            $sites[] = $this->unmarshalSite($item);
        }
        return $sites;
    }
}
