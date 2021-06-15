<?php


namespace Tests\Unit\Services;


use App\Contracts\Models\Post;
use App\Services\FilesystemPostPublisher;
use App\Services\PostSitemapGenerator;
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
        $post5 = new Post(5, $site, 'title5', 'content5', 'url5', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null); // Post has already been updated on destination filesystem
        $posts = [
            $post1, $post2, $post3, $post4, $post5
        ];
        $resourcePath = '/home/resources/sites';

        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($site, $resourcePath, $post1, $post2, $post3, $post4, $post5) {
            // Mocks the content that will be rendered for each post
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
                        'posts' => [$post1, $post2, $post4, $post5],
                        'pagination' => [
                            'total_pages' => 1,
                            'page_size' => 0,
                            'current_page' => 1
                        ]
                    ])
                ->times(1)
                ->andReturn('custom site: posts list rendered in view');
        });
        $sourceFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($site) {
            // Lists the assets that will be published
            $mock->shouldReceive('listContents')
                ->with("$site/assets", true)
                ->times(1)
                ->andReturn([
                    [
                        'type' => 'file',
                        'path' => "$site/assets/asset1.js",
                        'dirname' => "$site/assets",
                        'basename' => 'asset1.js',
                        'timestamp' => 1597453260 // Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')
                    ],
                    [
                        'type' => 'file',
                        'path' => "$site/assets/asset2.css",
                        'dirname' => "$site/assets",
                        'basename' => 'asset2.css',
                        'timestamp' => 1597453260
                    ],
                    [
                        'type' => 'file',
                        'path' => "$site/assets/asset3.jpg",
                        'dirname' => "$site/assets",
                        'basename' => 'asset3.jpg',
                        'timestamp' => 1597453260
                    ]
                ]);
            // Reads the content so that it can be pushed to the destination file
            $mock->shouldReceive('read')
                ->with("$site/assets/asset1.js")
                ->times(1)
                ->andReturn('asset1 content');
            $mock->shouldReceive('read')
                ->with("$site/assets/asset2.css")
                ->times(1)
                ->andReturn('asset2 content');
            $mock->shouldReceive('read')
                ->with("$site/assets/asset3.jpg")
                ->times(1)
                ->andReturn('asset3 content');
        });
        $destinationFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) {
            // The publisher is checking to see if the files exist (so it can check their last update timestamp)
            $mock->shouldReceive('has')
                ->with('url1')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url2')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url3')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url4')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url5')
                ->times(1)
                ->andReturn(true);

            // The publisher is checking the timestamps of the posts (so it can check their last update timestamp)
            $mock->shouldReceive('getTimestamp')
                ->with('url1')
                ->times(1)
                ->andReturn(1597453260); // Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')
            $mock->shouldReceive('getTimestamp')
                ->with('url2')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('url3')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('url4')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('url5')
                ->times(1)
                ->andReturn(1597453262); // Greater than Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')

            // The publisher is publishing the posts
            $mock->shouldReceive('put')
                ->with('url1', 'custom site: content1 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url2', 'custom site: content2 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url3', 'custom site: content3 rendered in view', ['mimetype' => 'text/html'])
                ->times(0);
            $mock->shouldReceive('delete')
                ->with('url3')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url4', 'custom site: content4 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put') // Post has already been published
            ->with('url5', 'custom site: content5 rendered in view', ['mimetype' => 'text/html'])
                ->times(0);

            // The publisher is publishing the index file
            $mock->shouldReceive('put')
                ->with('index.html', 'custom site: posts list rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);

            // The publisher is checking if the assets already exist (so it can check their timestamps)
            $mock->shouldReceive('has')
                ->with('assets/asset1.js')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('assets/asset2.css')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('assets/asset3.jpg')
                ->times(1)
                ->andReturn(true);

            // The publisher is checking the last updated timestamps of the assets (so it can compare it with the corresponding source file)
            $mock->shouldReceive('getTimestamp')
                ->with('assets/asset1.js')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('assets/asset2.css')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('assets/asset3.jpg')
                ->times(1)
                ->andReturn(1597453262); // Greater than Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')

            // The publisher is copying assets
            $mock->shouldReceive('put')
                ->with('assets/asset1.js', 'asset1 content')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('assets/asset2.css', 'asset2 content')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put') // The asset was already published
            ->with('assets/asset3.jpg', 'asset3 content')
                ->times(0);

            // Sitemap
            $mock->shouldReceive('put')
                ->with('sitemap.xml', '<xml></xml>', ['mimetype' => 'application/xml'])
                ->times(1)
                ->andReturn(true);

            // The publisher is listing all files on the destination filesystem, so it can remove ones that weren't just published
            $mock->shouldReceive('listContents')
                ->with('', true)
                ->times(1)
                ->andReturn([
                    [
                        'type' => 'file',
                        'path' => 'assets/asset1.js',
                        'dirname' => 'assets',
                        'basename' => 'asset1.js',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'assets/asset2.css',
                        'dirname' => 'assets',
                        'basename' => 'asset2.css',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url1',
                        'dirname' => '',
                        'basename' => 'url1',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url2',
                        'dirname' => '',
                        'basename' => 'url2',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url4',
                        'dirname' => '',
                        'basename' => 'url4',
                    ],
                    [ // Example of a post that had its human_readable_url renamed. This file will be deleted
                        'type' => 'file',
                        'path' => 'url4_old_name',
                        'dirname' => '',
                        'basename' => 'url4_old_name',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url5',
                        'dirname' => '',
                        'basename' => 'url5',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'index.html',
                        'dirname' => '',
                        'basename' => 'index.html',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'sitemap.xml',
                        'dirname' => '',
                        'basename' => 'sitemap.xml',
                    ]
                ]);

            // Post4 now has a new name, so the old published file will be deleted
            $mock->shouldReceive('delete')
                ->with('url4_old_name')
                ->times(1)
                ->andReturn(true);
        });
        $destinationFilesystemFactory = Mockery::mock(SiteFilesystemFactoryInterface::class, function ($mock) use ($site, $destinationFilesystem) {
            $mock->shouldReceive('getSiteFilesystem')
                ->with($site)
                ->times(1)
                ->andReturn($destinationFilesystem);
        });
        $sitemapGenerator = Mockery::mock(PostSitemapGenerator::class, function ($mock) use ($posts, $site) {
            $mock->shouldReceive('generateSitemap')
                ->with($posts, $site)
                ->times(1)
                ->andReturn('<xml></xml>');
        });
        $publisher = new FilesystemPostPublisher($viewFactory, $sourceFilesystem, $destinationFilesystemFactory, $sitemapGenerator, $resourcePath, 0);

        // Execute
        $result = $publisher->publish($posts, $site);

        // Assert
        $this->assertEqualsCanonicalizing([
            "assets/asset1.js",
            "assets/asset2.css",
            "assets/asset3.jpg",
            'url1',
            'url2',
            'url4',
            'url5',
            'index.html',
            'sitemap.xml'
        ], $result);
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
        $post5 = new Post(5, $site, 'title5', 'content5', 'url5', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null); // Post has already been updated on destination filesystem
        $posts = [
            $post1, $post2, $post3, $post4, $post5
        ];
        $resourcePath = '/home/resources/sites';
        $pageSize = 1;

        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($site, $resourcePath, $post1, $post2, $post3, $post4, $post5) {
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
                            'total_pages' => 4,
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
                            'total_pages' => 4,
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
                            'total_pages' => 4,
                            'page_size' => 1,
                            'current_page' => 3
                        ]
                    ])
                ->times(1)
                ->andReturn('custom site: posts list page3 rendered in view');
            $mock->shouldReceive('make')
                ->with("$site::list",
                    [
                        'posts' => [$post5],
                        'pagination' => [
                            'total_pages' => 4,
                            'page_size' => 1,
                            'current_page' => 4
                        ]
                    ])
                ->times(1)
                ->andReturn('custom site: posts list page4 rendered in view');
        });
        $sourceFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($site) {
            // Lists the assets that will be published
            $mock->shouldReceive('listContents')
                ->with("$site/assets", true)
                ->times(1)
                ->andReturn([
                    [
                        'type' => 'file',
                        'path' => "$site/assets/asset1.js",
                        'dirname' => "$site/assets",
                        'basename' => 'asset1.js',
                        'timestamp' => 1597453260 // Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')
                    ],
                    [
                        'type' => 'file',
                        'path' => "$site/assets/asset2.css",
                        'dirname' => "$site/assets",
                        'basename' => 'asset2.css',
                        'timestamp' => 1597453260
                    ],
                    [
                        'type' => 'file',
                        'path' => "$site/assets/asset3.jpg",
                        'dirname' => "$site/assets",
                        'basename' => 'asset3.jpg',
                        'timestamp' => 1597453260
                    ]
                ]);
            // Reads the content so that it can be pushed to the destination file
            $mock->shouldReceive('read')
                ->with("$site/assets/asset1.js")
                ->times(1)
                ->andReturn('asset1 content');
            $mock->shouldReceive('read')
                ->with("$site/assets/asset2.css")
                ->times(1)
                ->andReturn('asset2 content');
            $mock->shouldReceive('read')
                ->with("$site/assets/asset3.jpg")
                ->times(1)
                ->andReturn('asset3 content');
        });
        $destinationFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) {
            // The publisher is checking to see if the files exist (so it can check their last update timestamp)
            $mock->shouldReceive('has')
                ->with('url1')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url2')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url3')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url4')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url5')
                ->times(1)
                ->andReturn(true);

            // The publisher is checking the timestamps of the posts (so it can check their last update timestamp)
            $mock->shouldReceive('getTimestamp')
                ->with('url1')
                ->times(1)
                ->andReturn(1597453260); // Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')
            $mock->shouldReceive('getTimestamp')
                ->with('url2')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('url3')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('url4')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('url5')
                ->times(1)
                ->andReturn(1597453262); // Greater than Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')

            // The publisher is publishing the posts
            $mock->shouldReceive('put')
                ->with('url1', 'custom site: content1 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url2', 'custom site: content2 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url3', 'custom site: content3 rendered in view', ['mimetype' => 'text/html'])
                ->times(0);
            $mock->shouldReceive('delete')
                ->with('url3')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url4', 'custom site: content4 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put') // Post has already been published
            ->with('url5', 'custom site: content5 rendered in view', ['mimetype' => 'text/html'])
                ->times(0);

            // The publisher is publishing the index file
            $mock->shouldReceive('put')
                ->with('index.html', 'custom site: posts list page1 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('page_2.html', 'custom site: posts list page2 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('page_3.html', 'custom site: posts list page3 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('page_4.html', 'custom site: posts list page4 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);

            // The publisher is checking if the assets already exist (so it can check their timestamps)
            $mock->shouldReceive('has')
                ->with('assets/asset1.js')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('assets/asset2.css')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('assets/asset3.jpg')
                ->times(1)
                ->andReturn(true);

            // The publisher is checking the last updated timestamps of the assets (so it can compare it with the corresponding source file)
            $mock->shouldReceive('getTimestamp')
                ->with('assets/asset1.js')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('assets/asset2.css')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('assets/asset3.jpg')
                ->times(1)
                ->andReturn(1597453262); // Greater than Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')

            // The publisher is copying assets
            $mock->shouldReceive('put')
                ->with('assets/asset1.js', 'asset1 content')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('assets/asset2.css', 'asset2 content')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put') // The asset was already published
            ->with('assets/asset3.jpg', 'asset3 content')
                ->times(0);

            // Sitemap
            $mock->shouldReceive('put')
                ->with('sitemap.xml', '<xml></xml>', ['mimetype' => 'application/xml'])
                ->times(1)
                ->andReturn(true);

            // The publisher is listing all files on the destination filesystem, so it can remove ones that weren't just published
            $mock->shouldReceive('listContents')
                ->with('', true)
                ->times(1)
                ->andReturn([
                    [
                        'type' => 'file',
                        'path' => 'assets/asset1.js',
                        'dirname' => 'assets',
                        'basename' => 'asset1.js',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'assets/asset2.css',
                        'dirname' => 'assets',
                        'basename' => 'asset2.css',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url1',
                        'dirname' => '',
                        'basename' => 'url1',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url2',
                        'dirname' => '',
                        'basename' => 'url2',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url4',
                        'dirname' => '',
                        'basename' => 'url4',
                    ],
                    [ // Example of a post that had its human_readable_url renamed. This file will be deleted
                        'type' => 'file',
                        'path' => 'url4_old_name',
                        'dirname' => '',
                        'basename' => 'url4_old_name',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url5',
                        'dirname' => '',
                        'basename' => 'url5',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'index.html',
                        'dirname' => '',
                        'basename' => 'index.html',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'page_2.html',
                        'dirname' => '',
                        'basename' => 'page_2.html',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'page_3.html',
                        'dirname' => '',
                        'basename' => 'page_3.html',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'page_4.html',
                        'dirname' => '',
                        'basename' => 'page_4.html',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'sitemap.xml',
                        'dirname' => '',
                        'basename' => 'sitemap.xml',
                    ]
                ]);

            // Post4 now has a new name, so the old published file will be deleted
            $mock->shouldReceive('delete')
                ->with('url4_old_name')
                ->times(1)
                ->andReturn(true);
        });
        $destinationFilesystemFactory = Mockery::mock(SiteFilesystemFactoryInterface::class, function ($mock) use ($site, $destinationFilesystem) {
            $mock->shouldReceive('getSiteFilesystem')
                ->with($site)
                ->times(1)
                ->andReturn($destinationFilesystem);
        });
        $sitemapGenerator = Mockery::mock(PostSitemapGenerator::class, function ($mock) use ($posts, $site) {
            $mock->shouldReceive('generateSitemap')
                ->with($posts, $site)
                ->times(1)
                ->andReturn('<xml></xml>');
        });
        $publisher = new FilesystemPostPublisher($viewFactory, $sourceFilesystem, $destinationFilesystemFactory, $sitemapGenerator, $resourcePath, $pageSize);

        // Execute
        $result = $publisher->publish($posts, $site);

        // Assert
        $this->assertEqualsCanonicalizing([
            "assets/asset1.js",
            "assets/asset2.css",
            "assets/asset3.jpg",
            'url1',
            'url2',
            'url4',
            'url5',
            'index.html',
            'page_2.html',
            'page_3.html',
            'page_4.html',
            'sitemap.xml'
        ], $result);
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
        $post4 = new Post(4, $site, 'title4', 'content4', 'url4', new DateTime('2020-08-15 04:04:04'), new DateTime('2020-08-15 04:04:04'), null);
        $post5 = new Post(5, $site, 'title5', 'content5', 'url5', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null); // Post has already been updated on destination filesystem
        $posts = [
            $post1, $post2, $post3, $post4, $post5
        ];
        $resourcePath = '/home/resources/sites';

        $viewFactory = Mockery::mock(ViewFactoryContract::class, function ($mock) use ($site, $resourcePath, $post1, $post2, $post3, $post4, $post5) {
            // Mocks the content that will be rendered for each post
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
                        'posts' => [$post1, $post2, $post4, $post5],
                        'pagination' => [
                            'total_pages' => 1,
                            'page_size' => 0,
                            'current_page' => 1
                        ]
                    ])
                ->times(1)
                ->andReturn('custom site: posts list rendered in view');
        });
        $sourceFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($site) {
            // Lists the assets that will be published
            $mock->shouldReceive('listContents')
                ->with("$site/assets", true)
                ->times(1)
                ->andReturn([
                    [
                        'type' => 'file',
                        'path' => "$site/assets/asset1.js",
                        'dirname' => "$site/assets",
                        'basename' => 'asset1.js',
                        'timestamp' => 1597453260 // Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')
                    ],
                    [
                        'type' => 'file',
                        'path' => "$site/assets/asset2.css",
                        'dirname' => "$site/assets",
                        'basename' => 'asset2.css',
                        'timestamp' => 1597453260
                    ],
                    [
                        'type' => 'file',
                        'path' => "$site/assets/asset3.jpg",
                        'dirname' => "$site/assets",
                        'basename' => 'asset3.jpg',
                        'timestamp' => 1597453260
                    ]
                ]);
            // Reads the content so that it can be pushed to the destination file
            $mock->shouldReceive('read')
                ->with("$site/assets/asset1.js")
                ->times(1)
                ->andReturn('asset1 content');
            $mock->shouldReceive('read')
                ->with("$site/assets/asset2.css")
                ->times(1)
                ->andReturn('asset2 content');
            $mock->shouldReceive('read')
                ->with("$site/assets/asset3.jpg")
                ->times(1)
                ->andReturn('asset3 content');
        });
        $destinationFilesystem = Mockery::mock(FilesystemInterface::class, function ($mock) {
            // The publisher is checking to see if the files exist (so it can check their last update timestamp)
            $mock->shouldReceive('has')
                ->with('url1')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url2')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url3')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url4')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('url5')
                ->times(1)
                ->andReturn(true);

            // The publisher is checking the timestamps of the posts (so it can check their last update timestamp)
            $mock->shouldReceive('getTimestamp')
                ->with('url1')
                ->times(1)
                ->andReturn(1597453260); // Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')
            $mock->shouldReceive('getTimestamp')
                ->with('url2')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('url3')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('url4')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('url5')
                ->times(1)
                ->andReturn(1597453262); // Greater than Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')

            // The publisher is publishing the posts
            $mock->shouldReceive('put')
                ->with('url1', 'custom site: content1 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url2', 'custom site: content2 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(false); // Failed
            $mock->shouldReceive('put')
                ->with('url3', 'custom site: content3 rendered in view', ['mimetype' => 'text/html'])
                ->times(0);
            $mock->shouldReceive('delete')
                ->with('url3')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('url4', 'custom site: content4 rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put') // Post has already been published
            ->with('url5', 'custom site: content5 rendered in view', ['mimetype' => 'text/html'])
                ->times(0);

            // The publisher is publishing the index file
            $mock->shouldReceive('put')
                ->with('index.html', 'custom site: posts list rendered in view', ['mimetype' => 'text/html'])
                ->times(1)
                ->andReturn(true);

            // The publisher is checking if the assets already exist (so it can check their timestamps)
            $mock->shouldReceive('has')
                ->with('assets/asset1.js')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('assets/asset2.css')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('has')
                ->with('assets/asset3.jpg')
                ->times(1)
                ->andReturn(true);

            // The publisher is checking the last updated timestamps of the assets (so it can compare it with the corresponding source file)
            $mock->shouldReceive('getTimestamp')
                ->with('assets/asset1.js')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('assets/asset2.css')
                ->times(1)
                ->andReturn(1597453260);
            $mock->shouldReceive('getTimestamp')
                ->with('assets/asset3.jpg')
                ->times(1)
                ->andReturn(1597453262); // Greater than Unix timestamp: 1597453261 => DateTime('2020-08-15 01:01:01')

            // The publisher is copying assets
            $mock->shouldReceive('put')
                ->with('assets/asset1.js', 'asset1 content')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put')
                ->with('assets/asset2.css', 'asset2 content')
                ->times(1)
                ->andReturn(true);
            $mock->shouldReceive('put') // The asset was already published
            ->with('assets/asset3.jpg', 'asset3 content')
                ->times(0);

            // Sitemap
            $mock->shouldReceive('put')
                ->with('sitemap.xml', '<xml></xml>', ['mimetype' => 'application/xml'])
                ->times(1)
                ->andReturn(true);

            // The publisher is listing all files on the destination filesystem, so it can remove ones that weren't just published
            $mock->shouldReceive('listContents')
                ->with('', true)
                ->times(1)
                ->andReturn([
                    [
                        'type' => 'file',
                        'path' => 'assets/asset1.js',
                        'dirname' => 'assets',
                        'basename' => 'asset1.js',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'assets/asset2.css',
                        'dirname' => 'assets',
                        'basename' => 'asset2.css',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url1',
                        'dirname' => '',
                        'basename' => 'url1',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url2',
                        'dirname' => '',
                        'basename' => 'url2',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url4',
                        'dirname' => '',
                        'basename' => 'url4',
                    ],
                    [ // Example of a post that had its human_readable_url renamed. This file will be deleted
                        'type' => 'file',
                        'path' => 'url4_old_name',
                        'dirname' => '',
                        'basename' => 'url4_old_name',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'url5',
                        'dirname' => '',
                        'basename' => 'url5',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'index.html',
                        'dirname' => '',
                        'basename' => 'index.html',
                    ],
                    [
                        'type' => 'file',
                        'path' => 'sitemap.xml',
                        'dirname' => '',
                        'basename' => 'sitemap.xml',
                    ]
                ]);

            // Post4 now has a new name, so the old published file will be deleted
            $mock->shouldReceive('delete')
                ->with('url4_old_name')
                ->times(1)
                ->andReturn(true);
        });
        $destinationFilesystemFactory = Mockery::mock(SiteFilesystemFactoryInterface::class, function ($mock) use ($site, $destinationFilesystem) {
            $mock->shouldReceive('getSiteFilesystem')
                ->with($site)
                ->times(1)
                ->andReturn($destinationFilesystem);
        });
        $sitemapGenerator = Mockery::mock(PostSitemapGenerator::class, function ($mock) use ($posts, $site) {
            $mock->shouldReceive('generateSitemap')
                ->with($posts, $site)
                ->times(1)
                ->andReturn('<xml></xml>');
        });
        $publisher = new FilesystemPostPublisher($viewFactory, $sourceFilesystem, $destinationFilesystemFactory, $sitemapGenerator, $resourcePath, 0);

        // Execute and Assert
        $this->expectExceptionMessage('Unable to publish post: 2, url2');
        $publisher->publish($posts, $site);
    }
}
