<?php


namespace App\Services;


use Aws\Ecs\EcsClient;

class AwsEcsHostCreator implements HostCreatorInterface
{
    /**
     * @var EcsClient
     */
    private EcsClient $ecsClient;

    /**
     * Constructor
     *
     * @param EcsClient $ecsClient
     */
    public function __construct(EcsClient $ecsClient)
    {
        $this->ecsClient = $ecsClient;
    }

    /**
     * Creates infrastructure by running a task in an ECS cluster. Handles creation of all infrastructure, so other
     * methods not needed
     *
     * @param string $site
     */
    public function createHost(string $site)
    {
        $this->ecsClient->runTask([
            'launchType' => 'FARGATE',
            'taskDefinition' => 'task-def',
            'cluster' => 'default',
            'networkConfiguration' => [
                'awsvpcConfiguration' => [
                    'assignPublicIp' => 'ENABLED',
                    'subnets' => ['subnet'],
                ],
            ],
            'overrides' => [
                'containerOverrides' => [
                    [
                        'name' => 'image-name',
                        'environment' => [
                            [
                                "name" => "SITE_NAME",
                                "value" => $site
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    /**
     * Handled by createHost
     *
     * @param string $site
     */
    public function createSiteCertificate(string $site)
    {
        // Do nothing
    }

    /**
     * Handled by createHost
     *
     * @param string $site
     * @param array $data
     */
    public function distributeSite(string $site, array $data)
    {
        // Do nothing
    }
}
