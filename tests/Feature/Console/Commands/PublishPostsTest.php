<?php


namespace Tests\Feature\Console\Commands;


use App\Contracts\Models\Post;
use App\Repositories\PostRepositoryInterface;
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
        $post1 = new Post(1, 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $expectedPosts = [
            $post1, $post2
        ];
        $site = null;

        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('getAll')
                ->andReturns($expectedPosts);
        });
        $publisher = Mockery::mock(PostPublisherInterface::class, function ($mock) use ($expectedPosts, $site) {
            $mock->shouldReceive('publish')
                ->with(array_reverse($expectedPosts), $site) // Post order will be sort by descending creation date
                ->andReturns(true);
        });

        $this->app->instance(PostRepositoryInterface::class, $repo);
        $this->app->instance(PostPublisherInterface::class, $publisher);

        // Execute and Assert
        $this->artisan('posts:publish')
            ->expectsOutput('Successfully published all posts');
    }

    /**
     * Tests that the command can publish all Posts with the site option
     *
     * @return void
     */
    public function testPublishPostsCommandWithSiteOption()
    {
        // Setup
        $post1 = new Post(1, 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $expectedPosts = [
            $post1, $post2
        ];
        $site = 'default';

        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('getAll')
                ->andReturns($expectedPosts);
        });
        $publisher = Mockery::mock(PostPublisherInterface::class, function ($mock) use ($expectedPosts, $site) {
            $mock->shouldReceive('publish')
                ->with(array_reverse($expectedPosts), $site) // Post order will be sort by descending creation date
                ->andReturns(true);
        });

        $this->app->instance(PostRepositoryInterface::class, $repo);
        $this->app->instance(PostPublisherInterface::class, $publisher);

        // Execute and Assert
        $this->artisan("posts:publish --site=$site")
            ->expectsOutput('Successfully published all posts');
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
        $publisher = Mockery::mock(PostPublisherInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('publish')
                ->with($expectedPosts)
                ->andReturns(true);
        });

        $this->app->instance(PostRepositoryInterface::class, $repo);
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
        $post1 = new Post(1, 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $expectedPosts = [
            $post1, $post2
        ];
        $site = null;

        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('getAll')
                ->andReturns($expectedPosts);
        });
        $publisher = Mockery::mock(PostPublisherInterface::class, function ($mock) use ($expectedPosts, $site) {
            $mock->shouldReceive('publish')
                ->with(array_reverse($expectedPosts), $site) // Post order will be sort by descending creation date
                ->andReturns(false);
        });

        $this->app->instance(PostRepositoryInterface::class, $repo);
        $this->app->instance(PostPublisherInterface::class, $publisher);

        // Execute and Assert
        $this->artisan('posts:publish')
            ->expectsOutput('Could not publish posts');
    }
}
