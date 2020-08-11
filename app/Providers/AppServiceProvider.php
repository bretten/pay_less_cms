<?php

namespace App\Providers;

use App\Repositories\EloquentPostRepository;
use App\Repositories\PostRepositoryInterface;
use App\Services\FilesystemPostPublisher;
use App\Services\PostPublisherInterface;
use Aws\S3\S3Client;
use Illuminate\Support\ServiceProvider;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\MarkdownConverterInterface;
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
        $this->app->bind(PostRepositoryInterface::class, EloquentPostRepository::class);

        // Filesystem
        $this->app->bind(FilesystemInterface::class, function ($app) {
            $client = new S3Client([
                'credentials' => [
                    'key' => $app['config']['filesystems.disks.s3.key'],
                    'secret' => $app['config']['filesystems.disks.s3.secret']
                ],
                'region' => $app['config']['filesystems.disks.s3.region'],
                'version' => 'latest'
            ]);
            $adapter = new AwsS3Adapter($client, $app['config']['filesystems.disks.s3.bucket']);
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
