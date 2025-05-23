<?php

namespace App\Providers;

use App\Http\Controllers\PostController;
use App\Repositories\AwsDynamoDbPostRepository;
use App\Repositories\AwsDynamoDbSiteRepository;
use App\Repositories\EloquentPostRepository;
use App\Repositories\EloquentSiteRepository;
use App\Repositories\PostRepositoryInterface;
use App\Repositories\SiteRepositoryInterface;
use App\Services\AwsS3SiteFilesystemFactory;
use App\Services\FilesystemPostPublisher;
use App\Services\LocalSiteFilesystemFactory;
use App\Services\PostPublisherInterface;
use App\Services\PostSitemapGenerator;
use App\Services\SimpleXmlPostSitemapGenerator;
use App\Services\SiteFilesystemFactoryInterface;
use App\Support\DateTimeFactory;
use App\Support\UniqueIdFactory;
use Aws\DynamoDb\DynamoDbClient;
use Aws\S3\S3Client;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\MarkdownConverterInterface;
use League\Flysystem\Adapter\AbstractAdapter;
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
        if ($this->app['config']['database.default'] == 'dynamodb') {
            $this->app->bind(PostRepositoryInterface::class, function ($app) {
                $client = new DynamoDbClient([
                    'credentials' => [
                        'key' => $app['config']['database.connections.dynamodb.key'],
                        'secret' => $app['config']['database.connections.dynamodb.secret'],
                        'token' => $app['config']['database.connections.dynamodb.token']
                    ],
                    'region' => $app['config']['database.connections.dynamodb.region'],
                    'version' => 'latest'
                ]);

                return new AwsDynamoDbPostRepository($client, $app['config']['database.connections.dynamodb.table'], new DateTimeFactory(), new UniqueIdFactory());
            });
            $this->app->bind(SiteRepositoryInterface::class, function ($app) {
                $client = new DynamoDbClient([
                    'credentials' => [
                        'key' => $app['config']['database.connections.dynamodb.key'],
                        'secret' => $app['config']['database.connections.dynamodb.secret'],
                        'token' => $app['config']['database.connections.dynamodb.token']
                    ],
                    'region' => $app['config']['database.connections.dynamodb.region'],
                    'version' => 'latest'
                ]);

                return new AwsDynamoDbSiteRepository($client, $app['config']['database.connections.dynamodb.table'], new DateTimeFactory());
            });
        } else {
            $this->app->bind(PostRepositoryInterface::class, EloquentPostRepository::class);
            $this->app->bind(SiteRepositoryInterface::class, EloquentSiteRepository::class);
        }

        // Filesystem
        $this->app->bind(FilesystemInterface::class, function ($app) {
            return new Filesystem($this->getApplicationFilesystemAdapter($app));
        });

        // Publishers
        $this->app->bind(PostSitemapGenerator::class, function ($app) {
            return new SimpleXmlPostSitemapGenerator('https', new DateTimeFactory());
        });
        $this->app->bind(PostPublisherInterface::class, function ($app) {
            $sourceFilesystem = new Filesystem($this->getPublisherResourceFilesystem($app));
            $destinationFilesystemFactory = $this->getPublisherFilesystemFactory($app);
            return new FilesystemPostPublisher(
                $this->app->make(ViewFactoryContract::class),
                $sourceFilesystem,
                $destinationFilesystemFactory,
                $this->app->make(PostSitemapGenerator::class),
                realpath(resource_path('sites')),
                $app['config']['app.publisher_page_size']
            );
        });

        // Markdown
        $this->app->bind(MarkdownConverterInterface::class, CommonMarkConverter::class);

        // Contextual bindings
        $this->app->when(PostController::class)
            ->needs(SiteFilesystemFactoryInterface::class)
            ->give(function ($app) {
                return $this->getPublisherFilesystemFactory($app);
            });
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

    /**
     * Returns the filesystem adapter for this application
     *
     * @param Application $app
     * @return AbstractAdapter
     */
    private function getApplicationFilesystemAdapter(Application $app)
    {
        if ($app['config']['filesystems.default'] == 's3') {
            // S3
            $client = new S3Client([
                'credentials' => [
                    'key' => $app['config']['filesystems.disks.s3.key'],
                    'secret' => $app['config']['filesystems.disks.s3.secret'],
                    'token' => $app['config']['filesystems.disks.s3.token']
                ],
                'region' => $app['config']['filesystems.disks.s3.region'],
                'version' => 'latest'
            ]);
            return new AwsS3Adapter($client, $app['config']['filesystems.disks.s3.bucket']);
        } else {
            // Local
            return new Local($app['config']['filesystems.disks.local.root']);
        }
    }

    /**
     * Returns the filesystem adapter for the Publisher's resources
     *
     * @param Application $app
     * @return AbstractAdapter
     */
    private function getPublisherResourceFilesystem(Application $app)
    {
        return new Local($app['config']['filesystems.publisher_disks.local.resources_root']);
    }

    /**
     * Returns the configured Publisher's filesystem factory
     *
     * @param Application $app
     * @return AwsS3SiteFilesystemFactory|LocalSiteFilesystemFactory
     */
    private function getPublisherFilesystemFactory(Application $app)
    {
        if ($app['config']['filesystems.publisher_default'] == 's3') {
            // S3
            return new AwsS3SiteFilesystemFactory(
                $this->app->make(SiteRepositoryInterface::class),
                new S3Client([
                    'credentials' => [
                        'key' => $app['config']['filesystems.publisher_disks.s3.key'],
                        'secret' => $app['config']['filesystems.publisher_disks.s3.secret'],
                        'token' => $app['config']['filesystems.publisher_disks.s3.token']
                    ],
                    'region' => $app['config']['filesystems.publisher_disks.s3.region'],
                    'version' => 'latest'
                ])
            );
        } else {
            // Local
            return new LocalSiteFilesystemFactory(
                $app['config']['filesystems.publisher_disks.local.published_files_root'],
                $this->app->make(SiteRepositoryInterface::class)
            );
        }
    }
}
