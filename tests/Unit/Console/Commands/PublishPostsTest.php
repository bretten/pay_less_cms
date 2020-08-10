<?php


namespace Console\Commands;


use App\Repositories\PostRepositoryInterface;
use App\Services\PostPublisherInterface;
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
        $post1 = new \stdClass();
        $post1->content = 'content1';
        $post1->human_readable_url = 'url1';
        $post2 = new \stdClass();
        $post2->content = 'content2';
        $post2->human_readable_url = 'url2';
        $expectedPosts = [
            $post1, $post2
        ];
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
        $post1 = new \stdClass();
        $post1->content = 'content1';
        $post1->human_readable_url = 'url1';
        $post2 = new \stdClass();
        $post2->content = 'content2';
        $post2->human_readable_url = 'url2';
        $expectedPosts = [
            $post1, $post2
        ];
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('getAll')
                ->andReturns($expectedPosts);
        });
        $publisher = Mockery::mock(PostPublisherInterface::class, function ($mock) use ($expectedPosts) {
            $mock->shouldReceive('publish')
                ->with($expectedPosts)
                ->andReturns(false);
        });

        $this->app->instance(PostRepositoryInterface::class, $repo);
        $this->app->instance(PostPublisherInterface::class, $publisher);

        // Execute and Assert
        $this->artisan('posts:publish')
            ->expectsOutput('Could not publish posts');
    }
}
