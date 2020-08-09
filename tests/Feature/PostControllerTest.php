<?php

namespace Tests\Feature;

use App\Post;
use App\Repositories\PostRepositoryInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/posts');

        $response->assertStatus(200);
    }

    /**
     * Test create method is not implemented
     *
     * @return void
     */
    public function testCreate()
    {
        $response = $this->get('/posts/create');

        $response->assertStatus(200);
    }

    /**
     * Test that the store method can create a Post and return success
     *
     * @return void
     */
    public function testStoreCanCreateNewPost()
    {
        // Setup
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('create')
                ->with('title1', 'content1', 'human-readable-url1')
                ->andReturn(true);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->post('/posts', [
            'title' => 'title1',
            'content' => 'content1',
            'human_readable_url' => 'human-readable-url1'
        ]);

        // Assert
        $response->assertStatus(204);
    }

    /**
     * Test that the store method can handle an error when creating a Post
     *
     * @return void
     */
    public function testStoreCanHandlePostCreationError()
    {
        // Setup
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('create')
                ->with('title1', 'content1', 'human-readable-url1')
                ->andReturn(false);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->post('/posts', [
            'title' => 'title1',
            'content' => 'content1',
            'human_readable_url' => 'human-readable-url1'
        ]);

        // Assert
        $response->assertStatus(500);
    }

    /**
     * Test show method can display a single Post
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testShow()
    {
        // Setup
        $expectedPost = new Post;
        $expectedPost->id = 7;
        $expectedPost->title = 'Post title';
        $expectedPost->content = 'Post content';

        $expectedResponse = $this->app->make(ResponseFactory::class)->view('posts.show', ['post' => $expectedPost]);

        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($expectedPost) {
            $mock->shouldReceive('getById')
                ->with(7)
                ->andReturn($expectedPost);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->get('/posts/7');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals($expectedResponse->content(), $response->baseResponse->content());
    }

    /**
     * Test show method returns a 404 when trying to show a Post with an invalid ID
     *
     * @return void
     */
    public function testShowReturns404WhenTryingToShowAPostWithAnInvalidId()
    {
        // Setup
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getById')
                ->with(7)
                ->andThrow(new ModelNotFoundException);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->get('/posts/7');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test edit method is not implemented
     *
     * @return void
     */
    public function testEditIsNotImplemented()
    {
        $response = $this->get('/posts/edit');

        $response->assertStatus(500);
    }

    /**
     * Test update method is not implemented
     *
     * @return void
     */
    public function testUpdateIsNotImplemented()
    {
        $response = $this->get('/posts/update');

        $response->assertStatus(500);
    }

    /**
     * Test destroy method is not implemented
     *
     * @return void
     */
    public function testDestroyIsNotImplemented()
    {
        $response = $this->get('/posts/destroy');

        $response->assertStatus(500);
    }
}
