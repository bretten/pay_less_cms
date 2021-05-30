<?php


namespace Tests\Feature\Console\Commands;


use App\Contracts\Models\Post;
use App\Contracts\Models\Site;
use App\Repositories\PostRepositoryInterface;
use App\Repositories\SiteRepositoryInterface;
use App\Services\PostPublisherInterface;
use DateTime;
use Mockery;
use Tests\TestCase;

class PublishPostsTest extends TestCase
{
    /**
     * Tests that the command can publish all Posts
     *
     * @return void
     */
    public function testPublishPostsCommand()
    {
        // Setup
        $post1 = new Post(1, "site1", 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, "site1", 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $post3 = new Post(3, "site2", 'title3', 'content3', 'url3', new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'), null);
        $post4 = new Post(4, "site3", 'title4', 'content4', 'url4', new DateTime('2020-08-15 04:04:04'), new DateTime('2020-08-15 04:04:04'), null);
        $post5 = new Post(5, "site3", 'title5', 'content5', 'url5', new DateTime('2020-08-15 05:05:05'), new DateTime('2020-08-15 05:05:05'), null);
        $expectedPosts = [
            $post1, $post2, $post3, $post4, $post5
        ];

        $site1 = new Site('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);
        $site2 = new Site('site2', 'title2', new DateTime('2021-05-29 02:02:02'), new DateTime('2021-05-29 02:02:02'), null);
        $site3 = new Site('site3', 'title3', new DateTime('2021-05-29 02:02:02'), new DateTime('2021-05-29 02:02:02'), null);
        $site4 = new Site('site4', 'title4', new DateTime('2021-05-29 02:02:02'), new DateTime('2021-05-29 02:02:02'), new DateTime('2021-05-29 02:02:02'));
        $sites = [
            $site1, $site2, $site3, $site4
        ];

        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('getAll')
                ->andReturns($expectedPosts);
        });
        $siteRepo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) use ($sites) {
            $mock->shouldReceive('getAll')
                ->andReturns($sites);
        });
        $publisher = Mockery::mock(PostPublisherInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('publish')
                ->with([$expectedPosts[1], $expectedPosts[0]], 'site1') // Post order will be sort by descending creation date
                ->andReturns(true);
            $mock->shouldReceive('publish')
                ->with([$expectedPosts[2]], 'site2') // Post order will be sort by descending creation date
                ->andReturns(true);
            $mock->shouldReceive('publish')
                ->with([$expectedPosts[4], $expectedPosts[3]], 'site3') // Post order will be sort by descending creation date
                ->andReturns(true);
        });

        $this->app->instance(PostRepositoryInterface::class, $repo);
        $this->app->instance(SiteRepositoryInterface::class, $siteRepo);
        $this->app->instance(PostPublisherInterface::class, $publisher);

        // Execute and Assert
        $this->artisan('posts:publish')
            ->expectsOutput('Successfully published 2 posts for site1')
            ->expectsOutput('Successfully published 1 posts for site2')
            ->expectsOutput('Successfully published 2 posts for site3')
            ->expectsOutput('Skipping site4 because it is deleted');
    }

    /**
     * Tests that the command can publish all Posts with the site option
     *
     * @return void
     */
    public function testPublishPostsCommandWithSiteOption()
    {
        // Setup
        $post1 = new Post(1, "site1", 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, "site1", 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $post3 = new Post(3, "site2", 'title3', 'content3', 'url3', new DateTime('2020-08-15 03:03:03'), new DateTime('2020-08-15 03:03:03'), null);
        $post4 = new Post(4, "site3", 'title4', 'content4', 'url4', new DateTime('2020-08-15 04:04:04'), new DateTime('2020-08-15 04:04:04'), null);
        $post5 = new Post(5, "site3", 'title5', 'content5', 'url5', new DateTime('2020-08-15 05:05:05'), new DateTime('2020-08-15 05:05:05'), null);
        $expectedPosts = [
            $post1, $post2, $post3, $post4, $post5
        ];

        $site3 = new Site('site3', 'title3', new DateTime('2021-05-29 02:02:02'), new DateTime('2021-05-29 02:02:02'), null);

        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('getAll')
                ->andReturns($expectedPosts);
        });
        $siteRepo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) use ($site3) {
            $mock->shouldReceive('getByDomainName')
                ->andReturns($site3);
        });
        $publisher = Mockery::mock(PostPublisherInterface::class, function ($mock) use ($expectedPosts, $site3) {
            $mock->shouldReceive('publish')
                ->with([$expectedPosts[4], $expectedPosts[3]], $site3->domainName) // Post order will be sort by descending creation date
                ->andReturns(true);
        });

        $this->app->instance(PostRepositoryInterface::class, $repo);
        $this->app->instance(SiteRepositoryInterface::class, $siteRepo);
        $this->app->instance(PostPublisherInterface::class, $publisher);

        // Execute and Assert
        $this->artisan("posts:publish --site=$site3->domainName")
            ->expectsOutput('Successfully published 2 posts for site3');
    }

    /**
     * Tests that the command stops when there are no Posts to publish
     *
     * @return void
     */
    public function testPublishPostsCommandStopsWhenThereIsNothingToPublish()
    {
        // Setup
        $expectedPosts = [];
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('getAll')
                ->andReturns($expectedPosts);
        });
        $site1 = new Site('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);
        $site2 = new Site('site2', 'title2', new DateTime('2021-05-29 02:02:02'), new DateTime('2021-05-29 02:02:02'), null);
        $site3 = new Site('site3', 'title3', new DateTime('2021-05-29 02:02:02'), new DateTime('2021-05-29 02:02:02'), null);
        $sites = [
            $site1, $site2, $site3
        ];
        $publisher = Mockery::mock(PostPublisherInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('publish')
                ->with($expectedPosts)
                ->andReturns(true);
        });
        $siteRepo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) use ($sites) {
            $mock->shouldReceive('getAll')
                ->andReturns($sites);
        });

        $this->app->instance(PostRepositoryInterface::class, $repo);
        $this->app->instance(SiteRepositoryInterface::class, $siteRepo);
        $this->app->instance(PostPublisherInterface::class, $publisher);

        // Execute and Assert
        $this->artisan('posts:publish')
            ->expectsOutput('No posts to publish');
    }

    /**
     * Tests that the command handles a publishing error
     *
     * @return void
     */
    public function testPublishPostsCommandHandlesErrorWhenPublishing()
    {
        // Setup
        $post1 = new Post(1, "site1", 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, "site1", 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $expectedPosts = [
            $post1, $post2
        ];

        $site1 = new Site('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);

        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('getAll')
                ->andReturns($expectedPosts);
        });
        $siteRepo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) use ($site1) {
            $mock->shouldReceive('getByDomainName')
                ->andReturns($site1);
        });
        $publisher = Mockery::mock(PostPublisherInterface::class, function ($mock) use ($expectedPosts, $site1) {
            $mock->shouldReceive('publish')
                ->with(array_reverse($expectedPosts), $site1->domainName) // Post order will be sort by descending creation date
                ->andReturns(false);
        });

        $this->app->instance(PostRepositoryInterface::class, $repo);
        $this->app->instance(SiteRepositoryInterface::class, $siteRepo);
        $this->app->instance(PostPublisherInterface::class, $publisher);

        // Execute and Assert
        $this->artisan('posts:publish --site=site1')
            ->expectsOutput('Could not publish posts for site1');
    }
}
