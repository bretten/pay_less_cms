<?php


namespace Tests\Unit\Services;


use App\Contracts\Models\Post;
use App\Services\FilesystemPostPublisher;
use App\Services\SiteFilesystemFactoryInterface;
use DateTime;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use League\Flysystem\FilesystemInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class FilesystemPostPublisherTest extends TestCase
{
    /**
     * Tests that Posts can be published to the Filesystem
     *
     * @return void
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testPublishPostsToFilesystem()
    {
        // Setup
        $post1 = new Post(1, 'site1', 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, 'site1', 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $post3 = new Post(3, 'site1', 'title3', 'content3', 'url3', new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'));
        $posts = [
            $post1, $post2, $post3
        ];
        $assetFiles = [
            [
                'type' => 'file',
                'path' => 'assets_to_publish/asset1.js',
                'basename' => 'asset1.js',
            ],
            [
                'type' => 'file',
                'path' => 'assets_to_publish/asset2.js',
                'basename' => 'asset2.css',
            ]
        ];

        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($post1, $post2, $post3) {
            $mock->shouldReceive('make')
                ->with('posts.published.show', ['post' => $post1])
                ->times(1)
                ->andReturn('content1 rendered in view');
            $mock->shouldReceive('make')
                ->with('posts.published.show', ['post' => $post2])
                ->times(1)
                ->andReturn('content2 rendered in view');
            $mock->shouldReceive('make')
                ->with('posts.published.show', ['post' => $post3])
                ->times(0);
            $mock->shouldReceive('make')
                ->with('posts.published.list', ['posts' => [$post1, $post2]])
                ->times(1)
                ->andReturn('posts list rendered in view');
        });
        $sourceFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($assetFiles) {
            $mock->shouldReceive('listContents')
                ->with('assets_to_publish/')
                ->times(1)
                ->andReturn($assetFiles);
            $mock->shouldReceive('read')
                ->with($assetFiles[0]['path'])
                ->times(1)
                ->andReturn('asset1 content');
            $mock->shouldReceive('read')
                ->with($assetFiles[1]['path'])
                ->times(1)
                ->andReturn('asset2 content');
        });
        $destinationFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($assetFiles) {
            // Publish posts
            $mock->shouldReceive('put')
                ->with('url1', 'content1 rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url2', 'content2 rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url3', 'content3 rendered in view')
                ->times(0);
            $mock->shouldReceive('delete')
                ->with('url3')
                ->times(1)
                ->andReturn(true);

            // Publish index file
            $mock->shouldReceive('put')
                ->with('index.html', 'posts list rendered in view')
                ->times(1)
                ->andReturn(true);

            // Copy assets
            $mock->shouldReceive('deleteDir')
                ->with('assets')
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('assets/' . $assetFiles[0]['basename'], 'asset1 content')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('assets/' . $assetFiles[1]['basename'], 'asset2 content')
                ->times(1)
                ->andReturn(true);
        });
        $destinationFilesystemFactory = Mockery::mock(SiteFilesystemFactoryInterface::class, function ($mock) use ($destinationFilesystem) {
            $mock->shouldReceive('getSiteFilesystem')
                ->with(null)
                ->times(1)
                ->andReturn($destinationFilesystem);
        });
        $publisher = new FilesystemPostPublisher($viewFactory, $sourceFilesystem, $destinationFilesystemFactory);

        // Execute
        $result = $publisher->publish($posts);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Tests that Posts can be published to the Filesystem with a custom site view
     *
     * @return void
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testPublishPostsWithCustomSiteViewsToFilesystem()
    {
        // Setup
        $site = 'site1.exampletld';
        $siteReplaced = 'site1_exampletld'; // Publisher will replace dots with underscores to prevent issues with view dot notation

        $post1 = new Post(1, $site, 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, $site, 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $post3 = new Post(3, $site, 'title3', 'content3', 'url3', new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'));
        $posts = [
            $post1, $post2, $post3
        ];
        $assetFiles = [
            [
                'type' => 'file',
                'path' => "assets_to_publish/$site/asset1.js",
                'basename' => 'asset1.js',
            ],
            [
                'type' => 'file',
                'path' => "assets_to_publish/$site/asset2.js",
                'basename' => 'asset2.css',
            ]
        ];

        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($post1, $post2, $post3, $siteReplaced) {
            $mock->shouldReceive('make')
                ->with("posts.published.sites.$siteReplaced.show", ['post' => $post1])
                ->times(1)
                ->andReturn('custom site: content1 rendered in view');
            $mock->shouldReceive('make')
                ->with("posts.published.sites.$siteReplaced.show", ['post' => $post2])
                ->times(1)
                ->andReturn('custom site: content2 rendered in view');
            $mock->shouldReceive('make')
                ->with("posts.published.sites.$siteReplaced.show", ['post' => $post3])
                ->times(0);
            $mock->shouldReceive('make')
                ->with("posts.published.sites.$siteReplaced.list", ['posts' => [$post1, $post2]])
                ->times(1)
                ->andReturn('custom site: posts list rendered in view');
        });
        $sourceFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($site, $assetFiles) {
            $mock->shouldReceive('listContents')
                ->with("assets_to_publish/$site")
                ->times(1)
                ->andReturn($assetFiles);
            $mock->shouldReceive('read')
                ->with($assetFiles[0]['path'])
                ->times(1)
                ->andReturn('asset1 content');
            $mock->shouldReceive('read')
                ->with($assetFiles[1]['path'])
                ->times(1)
                ->andReturn('asset2 content');
        });
        $destinationFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($assetFiles) {
            // Publish posts
            $mock->shouldReceive('put')
                ->with('url1', 'custom site: content1 rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url2', 'custom site: content2 rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url3', 'custom site: content3 rendered in view')
                ->times(0);
            $mock->shouldReceive('delete')
                ->with('url3')
                ->times(1)
                ->andReturn(true);

            // Publish index file
            $mock->shouldReceive('put')
                ->with('index.html', 'custom site: posts list rendered in view')
                ->times(1)
                ->andReturn(true);

            // Copy assets
            $mock->shouldReceive('deleteDir')
                ->with('assets')
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('assets/' . $assetFiles[0]['basename'], 'asset1 content')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('assets/' . $assetFiles[1]['basename'], 'asset2 content')
                ->times(1)
                ->andReturn(true);
        });
        $destinationFilesystemFactory = Mockery::mock(SiteFilesystemFactoryInterface::class, function ($mock) use ($site, $destinationFilesystem) {
            $mock->shouldReceive('getSiteFilesystem')
                ->with($site)
                ->times(1)
                ->andReturn($destinationFilesystem);
        });
        $publisher = new FilesystemPostPublisher($viewFactory, $sourceFilesystem, $destinationFilesystemFactory);

        // Execute
        $result = $publisher->publish($posts, $site);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Tests that Posts can be published to the Filesystem with an error
     *
     * @return void
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testPublishPostsToFilesystemWithFailure()
    {
        // Setup
        $post1 = new Post(1, 'site1', 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, 'site1', 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $posts = [
            $post1, $post2
        ];
        $assetFiles = [
            [
                'type' => 'file',
                'path' => 'assets_to_publish/asset1.js',
                'basename' => 'asset1.js',
            ],
            [
                'type' => 'file',
                'path' => 'assets_to_publish/asset2.js',
                'basename' => 'asset2.css',
            ]
        ];

        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($post1, $post2) {
            $mock->shouldReceive('make')
                ->with('posts.published.show', ['post' => $post1])
                ->times(1)
                ->andReturn('content1 rendered in view');
            $mock->shouldReceive('make')
                ->with('posts.published.show', ['post' => $post2])
                ->times(1)
                ->andReturn('content2 rendered in view');
            $mock->shouldReceive('make')
                ->with('posts.published.list', ['posts' => [$post1, $post2]])
                ->times(1)
                ->andReturn('posts list rendered in view');
        });
        $sourceFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($assetFiles) {
            $mock->shouldReceive('listContents')
                ->with('assets_to_publish/')
                ->times(1)
                ->andReturn($assetFiles);
            $mock->shouldReceive('read')
                ->with($assetFiles[0]['path'])
                ->times(1)
                ->andReturn('asset1 content');
            $mock->shouldReceive('read')
                ->with($assetFiles[1]['path'])
                ->times(1)
                ->andReturn('asset2 content');
        });
        $destinationFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($assetFiles) {
            // Publish posts
            $mock->shouldReceive('put')
                ->with('url1', 'content1 rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url2', 'content2 rendered in view')
                ->times(1)
                ->andReturn(false); // Failed
            $mock->shouldReceive('put')
                ->with('index.html', 'posts list rendered in view')
                ->times(1)
                ->andReturn(true);

            // Publish index file
            $mock->shouldReceive('put')
                ->with('index.html', 'posts list rendered in view')
                ->times(1)
                ->andReturn(true);

            // Copy assets
            $mock->shouldReceive('deleteDir')
                ->with('assets')
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('assets/' . $assetFiles[0]['basename'], 'asset1 content')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('assets/' . $assetFiles[1]['basename'], 'asset2 content')
                ->times(1)
                ->andReturn(true);
        });
        $destinationFilesystemFactory = Mockery::mock(SiteFilesystemFactoryInterface::class, function ($mock) use ($destinationFilesystem) {
            $mock->shouldReceive('getSiteFilesystem')
                ->with(null)
                ->times(1)
                ->andReturn($destinationFilesystem);
        });
        $publisher = new FilesystemPostPublisher($viewFactory, $sourceFilesystem, $destinationFilesystemFactory);

        // Execute
        $result = $publisher->publish($posts);

        // Assert
        $this->assertFalse($result);
    }
}
