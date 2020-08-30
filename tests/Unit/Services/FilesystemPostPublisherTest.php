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
        $site = 'site1.exampletld';

        $post1 = new Post(1, $site, 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, $site, 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $post3 = new Post(3, $site, 'title3', 'content3', 'url3', new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'));
        $post4 = new Post(4, $site, 'title4', 'content4', 'url4', new DateTime('2020-08-15 04:04:04'), new DateTime('2020-08-15 04:04:04'), null);
        $posts = [
            $post1, $post2, $post3, $post4
        ];
        $assetFiles = [
            [
                'type' => 'file',
                'path' => "$site/assets/asset1.js",
                'dirname' => "$site/assets",
                'basename' => 'asset1.js',
            ],
            [
                'type' => 'file',
                'path' => "$site/assets/asset2.js",
                'dirname' => "$site/assets",
                'basename' => 'asset2.css',
            ]
        ];
        $resourcePath = '/home/resources/sites';

        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($site, $resourcePath, $post1, $post2, $post3, $post4) {
            $mock->shouldReceive('addNamespace')
                ->with($site, $resourcePath . DIRECTORY_SEPARATOR . $site)
                ->times(1);
            $mock->shouldReceive('make')
                ->with("$site::show", ['post' => $post1])
                ->times(1)
                ->andReturn('custom site: content1 rendered in view');
            $mock->shouldReceive('make')
                ->with("$site::show", ['post' => $post2])
                ->times(1)
                ->andReturn('custom site: content2 rendered in view');
            $mock->shouldReceive('make')
                ->with("$site::show", ['post' => $post3])
                ->times(0);
            $mock->shouldReceive('make')
                ->with("$site::show", ['post' => $post4])
                ->times(1)
                ->andReturn('custom site: content4 rendered in view');
            $mock->shouldReceive('make')
                ->with("$site::list",
                    [
                        'posts' => [$post1, $post2, $post4],
                        'pagination' => [
                            'total_pages' => 1,
                            'page_size' => 0,
                            'current_page' => 1
                        ]
                    ])
                ->times(1)
                ->andReturn('custom site: posts list rendered in view');
        });
        $sourceFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($site, $assetFiles) {
            $mock->shouldReceive('listContents')
                ->with("$site/assets", true)
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
            $mock->shouldReceive('put')
                ->with('url4', 'custom site: content4 rendered in view')
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
        $publisher = new FilesystemPostPublisher($viewFactory, $sourceFilesystem, $destinationFilesystemFactory, $resourcePath, 0);

        // Execute
        $result = $publisher->publish($posts, $site);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Tests that Posts can be published to the Filesystem with paginated index files
     *
     * @return void
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function testPublishPostsToFilesystemWithPagination()
    {
        // Setup
        $site = 'site1.exampletld';

        $post1 = new Post(1, $site, 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, $site, 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $post3 = new Post(3, $site, 'title3', 'content3', 'url3', new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'));
        $post4 = new Post(4, $site, 'title4', 'content4', 'url4', new DateTime('2020-08-15 04:04:04'), new DateTime('2020-08-15 04:04:04'), null);
        $posts = [
            $post1, $post2, $post3, $post4
        ];
        $assetFiles = [
            [
                'type' => 'file',
                'path' => "$site/assets/asset1.js",
                'dirname' => "$site/assets",
                'basename' => 'asset1.js',
            ],
            [
                'type' => 'file',
                'path' => "$site/assets/asset2.js",
                'dirname' => "$site/assets",
                'basename' => 'asset2.css',
            ]
        ];
        $resourcePath = '/home/resources/sites';
        $pageSize = 1;

        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($site, $resourcePath, $post1, $post2, $post3, $post4) {
            $mock->shouldReceive('addNamespace')
                ->with($site, $resourcePath . DIRECTORY_SEPARATOR . $site)
                ->times(1);
            $mock->shouldReceive('make')
                ->with("$site::show", ['post' => $post1])
                ->times(1)
                ->andReturn('custom site: content1 rendered in view');
            $mock->shouldReceive('make')
                ->with("$site::show", ['post' => $post2])
                ->times(1)
                ->andReturn('custom site: content2 rendered in view');
            $mock->shouldReceive('make')
                ->with("$site::show", ['post' => $post3])
                ->times(0);
            $mock->shouldReceive('make')
                ->with("$site::show", ['post' => $post4])
                ->times(1)
                ->andReturn('custom site: content4 rendered in view');
            $mock->shouldReceive('make')
                ->with("$site::list",
                    [
                        'posts' => [$post1],
                        'pagination' => [
                            'total_pages' => 3,
                            'page_size' => 1,
                            'current_page' => 1
                        ]
                    ])
                ->times(1)
                ->andReturn('custom site: posts list page1 rendered in view');
            $mock->shouldReceive('make')
                ->with("$site::list",
                    [
                        'posts' => [$post2],
                        'pagination' => [
                            'total_pages' => 3,
                            'page_size' => 1,
                            'current_page' => 2
                        ]
                    ])
                ->times(1)
                ->andReturn('custom site: posts list page2 rendered in view');
            $mock->shouldReceive('make')
                ->with("$site::list",
                    [
                        'posts' => [$post4],
                        'pagination' => [
                            'total_pages' => 3,
                            'page_size' => 1,
                            'current_page' => 3
                        ]
                    ])
                ->times(1)
                ->andReturn('custom site: posts list page3 rendered in view');
        });
        $sourceFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($site, $assetFiles) {
            $mock->shouldReceive('listContents')
                ->with("$site/assets", true)
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
            $mock->shouldReceive('put')
                ->with('url4', 'custom site: content4 rendered in view')
                ->times(1)
                ->andReturn(true);

            // Publish index file
            $mock->shouldReceive('put')
                ->with('index.html', 'custom site: posts list page1 rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('page_2.html', 'custom site: posts list page2 rendered in view')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('page_3.html', 'custom site: posts list page3 rendered in view')
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
        $publisher = new FilesystemPostPublisher($viewFactory, $sourceFilesystem, $destinationFilesystemFactory, $resourcePath, $pageSize);

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
        $site = 'site1.exampletld';

        $post1 = new Post(1, $site, 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, $site, 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $post3 = new Post(3, $site, 'title3', 'content3', 'url3', new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'));
        $posts = [
            $post1, $post2, $post3
        ];
        $assetFiles = [
            [
                'type' => 'file',
                'path' => "$site/assets/asset1.js",
                'dirname' => "$site/assets",
                'basename' => 'asset1.js',
            ],
            [
                'type' => 'file',
                'path' => "$site/assets/asset2.js",
                'dirname' => "$site/assets",
                'basename' => 'asset2.css',
            ]
        ];
        $resourcePath = '/home/resources/sites';

        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($site, $resourcePath, $post1, $post2, $post3) {
            $mock->shouldReceive('addNamespace')
                ->with($site, $resourcePath . DIRECTORY_SEPARATOR . $site)
                ->times(1);
            $mock->shouldReceive('make')
                ->with("$site::show", ['post' => $post1])
                ->times(1)
                ->andReturn('custom site: content1 rendered in view');
            $mock->shouldReceive('make')
                ->with("$site::show", ['post' => $post2])
                ->times(1)
                ->andReturn('custom site: content2 rendered in view');
            $mock->shouldReceive('make')
                ->with("$site::show", ['post' => $post3])
                ->times(0);
            $mock->shouldReceive('make')
                ->with("$site::list",
                    [
                        'posts' => [$post1, $post2],
                        'pagination' => [
                            'total_pages' => 1,
                            'page_size' => 0,
                            'current_page' => 1
                        ]
                    ])
                ->times(1)
                ->andReturn('custom site: posts list rendered in view');
        });
        $sourceFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($site, $assetFiles) {
            $mock->shouldReceive('listContents')
                ->with("$site/assets", true)
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
                ->andReturn(false); // Failed
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
        $publisher = new FilesystemPostPublisher($viewFactory, $sourceFilesystem, $destinationFilesystemFactory, $resourcePath, 0);

        // Execute
        $result = $publisher->publish($posts, $site);

        // Assert
        $this->assertFalse($result);
    }
}
