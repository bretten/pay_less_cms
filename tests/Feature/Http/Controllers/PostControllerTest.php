<?php

namespace Tests\Feature\Http\Controllers;

use App\Contracts\Models\Post;
use App\Http\Middleware\AuthenticateByIp;
use App\Repositories\PostRepositoryInterface;
use DateTime;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Exclude middleware that checks for trusted IP
        $this->withoutMiddleware(AuthenticateByIp::class);
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex()
    {
        // Setup
        $post1 = new Post(1, 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $posts = [
            $post1, $post2
        ];
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($posts) {
            $mock->shouldReceive('getAll')
                ->times(1)
                ->andReturn($posts);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->get('/posts');

        // Assert
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
        $response->assertRedirect(Route::prefix(config('app.url_prefix'))->get('posts')->uri());
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
        $post = new Post(1, 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);

        $expectedResponse = $this->app->make(ResponseFactory::class)->view('posts.published.show', ['post' => $post]);

        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($post) {
            $mock->shouldReceive('getById')
                ->with(1)
                ->andReturn($post);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->get('/posts/1');

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
                ->with(1)
                ->andThrow(new ModelNotFoundException);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->get('/posts/1');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test that the edit method shows the edit form for an existing Post
     *
     * @return void
     */
    public function testEdit()
    {
        // Setup
        $post = new Post(1, 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);

        $expectedResponse = $this->app->make(ResponseFactory::class)->view('posts.edit', ['post' => $post]);

        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($post) {
            $mock->shouldReceive('getById')
                ->with(1)
                ->andReturn($post);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->get('/posts/1/edit');

        // Assert
        $response->assertStatus(200);
        $actualContentWithCsrfRemoved = preg_replace('/<input type="hidden" name="_token" value="(.*?)">/', '<input type="hidden" name="_token" value="">', $response->baseResponse->content());
        $this->assertEquals($expectedResponse->content(), $actualContentWithCsrfRemoved);
    }

    /**
     * Test edit method returns a 404 when trying to edit a Post with an invalid ID
     *
     * @return void
     */
    public function testEditReturns404WhenTryingToEditAPostWithAnInvalidId()
    {
        // Setup
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getById')
                ->with(1)
                ->andThrow(new ModelNotFoundException);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->get('/posts/1/edit');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test that the update method can update a Post and return success
     *
     * @return void
     */
    public function testUpdateCanUpdateExistingPost()
    {
        // Setup
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('update')
                ->with(1, 'title1 v2', 'content1 v2', 'human-readable-url1-v2')
                ->andReturn(true);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->put('/posts/1', [
            'title' => 'title1 v2',
            'content' => 'content1 v2',
            'human_readable_url' => 'human-readable-url1-v2'
        ]);

        // Assert
        $response->assertRedirect(Route::prefix(config('app.url_prefix'))->get('posts')->uri());
    }

    /**
     * Test that the update method can handle an error when trying to update a Post
     *
     * @return void
     */
    public function testUpdateCanHandleError()
    {
        // Setup
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('update')
                ->with('title1 v2', 'content1 v2', 'human-readable-url1-v2')
                ->andReturn(false);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->put('/posts/1', [
            'title' => 'title1 v2',
            'content' => 'content1 v2',
            'human_readable_url' => 'human-readable-url1-v2'
        ]);

        // Assert
        $response->assertStatus(500);
    }

    /**
     * Test that the destroy method can delete a Post
     *
     * @return void
     */
    public function testDestroy()
    {
        // Setup
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('delete')
                ->with(1)
                ->andReturn(true);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->delete('/posts/1');

        // Assert
        $response->assertRedirect(Route::prefix(config('app.url_prefix'))->get('posts')->uri());
    }

    /**
     * Test that the destroy method can handle an error while trying to delete a Post
     *
     * @return void
     */
    public function testDestroyCanHandleError()
    {
        // Setup
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('delete')
                ->with(1)
                ->andReturn(false);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->delete('/posts/1');

        // Assert
        $response->assertStatus(500);
    }
}
