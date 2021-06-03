<?php

use Aws\DynamoDb\DynamoDbClient;

class DynamoDbMigration
{
    /**
     * @var DynamoDbClient $client
     */
    private DynamoDbClient $client;

    /**
     * Constructor
     *
     * @param DynamoDbClient $client
     */
    public function __construct(DynamoDbClient $client)
    {
        $this->client = $client;
    }

    /**
     * Migrates Posts from v0.1.0 to v0.2.0
     *
     * @param string $sourceTableName
     * @param string $destinationTableName
     * @param array $sitesToWrite
     */
    public function migrateFromV010ToV020(string $sourceTableName, string $destinationTableName, array $sitesToWrite)
    {
        // Scan all the items from the source
        $scanParams = [
            'TableName' => $sourceTableName
        ];
        $items = [];
        while (true) {
            $result = $this->client->scan($scanParams);
            $items = array_merge($items, $result['Items']);

            if (isset($result['LastEvaluatedKey'])) {
                $scanParams['ExclusiveStartKey'] = $result['LastEvaluatedKey'];
            } else {
                break;
            }
        }

        // Write the data to the destination
        $data = [
            'RequestItems' => [
                $destinationTableName => []
            ]
        ];
        foreach ($items as $item) {
            // Check if the item is a site and skip the site if it is not in the list to write
            if (!array_key_exists('site', $item) || !in_array($item['site']['S'], $sitesToWrite)) {
                continue;
            }

            $data['RequestItems'][$destinationTableName][] = [
                'PutRequest' => [
                    'Item' => [
                        // Standard attributes
                        'PK' => ['S' => 'SITE#' . $item['site']['S']],
                        'SK' => ['S' => 'POST#' . $item['id']['S']],
                        'id' => ['S' => $item['id']['S']],
                        'site' => ['S' => $item['site']['S']],
                        'title' => ['S' => $item['title']['S']],
                        'content' => ['S' => $item['content']['S']],
                        'human_readable_url' => ['S' => $item['human_readable_url']['S']],
                        'created_at' => ['N' => $item['created_at']['N']],
                        'updated_at' => ['N' => $item['updated_at']['N']],
                        'deleted_at' => array_key_exists('N', $item['deleted_at']) ? $item['deleted_at']['N'] : ['NULL' => true],
                        // GSI1 attributes
                        'GSI1PK' => ['S' => 'POST'],
                        'GSI1SK' => ['S' => $item['id']['S']]
                    ]
                ]
            ];
        }
        $result = $this->client->batchWriteItem($data);
    }

    /**
     * Copies all the items from the source table to the destination table
     *
     * @param string $sourceTableName
     * @param string $destinationTableName
     */
    public function copy(string $sourceTableName, string $destinationTableName)
    {
        // Scan all the items from the source
        $scanParams = [
            'TableName' => $sourceTableName
        ];
        $items = [];
        while (true) {
            $result = $this->client->scan($scanParams);
            $items = array_merge($items, $result['Items']);

            if (isset($result['LastEvaluatedKey'])) {
                $scanParams['ExclusiveStartKey'] = $result['LastEvaluatedKey'];
            } else {
                break;
            }
        }

        // Write the data to the destination
        $data = [
            'RequestItems' => [
                $destinationTableName => []
            ]
        ];
        foreach ($items as $item) {

            $data['RequestItems'][$destinationTableName][] = [
                'PutRequest' => [
                    'Item' => $item
                ]
            ];
        }
        $result = $this->client->batchWriteItem($data);
    }
}
