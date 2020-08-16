<?php

namespace App\Providers;

use App\Repositories\AwsDynamoDbPostRepository;
use App\Repositories\PostRepositoryInterface;
use App\Services\FilesystemPostPublisher;
use App\Services\PostPublisherInterface;
use App\Support\DateTimeFactory;
use App\Support\UniqueIdFactory;
use Aws\DynamoDb\DynamoDbClient;
use Aws\S3\S3Client;
use Illuminate\Support\ServiceProvider;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\MarkdownConverterInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Repositories
        //$this->app->bind(PostRepositoryInterface::class, EloquentPostRepository::class);
        $this->app->bind(PostRepositoryInterface::class, function ($app) {
            $client = new DynamoDbClient([
                'credentials' => [
                    'key' => $app['config']['database.connections.dynamodb.key'],
                    'secret' => $app['config']['database.connections.dynamodb.secret']
                ],
                'region' => $app['config']['database.connections.dynamodb.region'],
                'version' => 'latest'
            ]);

            return new AwsDynamoDbPostRepository($client, $app['config']['database.connections.dynamodb.table'], new DateTimeFactory(), new UniqueIdFactory());
        });

        // Filesystem
        $this->app->bind(FilesystemInterface::class, function ($app) {
            if ($app['config']['filesystems.default'] == 's3') {
                // S3
                $client = new S3Client([
                    'credentials' => [
                        'key' => $app['config']['filesystems.disks.s3.key'],
                        'secret' => $app['config']['filesystems.disks.s3.secret']
                    ],
                    'region' => $app['config']['filesystems.disks.s3.region'],
                    'version' => 'latest'
                ]);
                $adapter = new AwsS3Adapter($client, $app['config']['filesystems.disks.s3.bucket']);
            } else {
                // Local
                $adapter = new Local($app['config']['filesystems.disks.local.root']);
            }

            return new Filesystem($adapter);
        });

        // Publishers
        $this->app->bind(PostPublisherInterface::class, FilesystemPostPublisher::class);

        // Markdown
        $this->app->bind(MarkdownConverterInterface::class, CommonMarkConverter::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
