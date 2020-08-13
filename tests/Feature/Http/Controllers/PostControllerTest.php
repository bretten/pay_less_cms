<?php

namespace Tests\Feature\Http\Controllers;

use App\Post;
use App\Repositories\PostRepositoryInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
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
        // Setup
        $post1 = new \stdClass();
        $post1->id = 1;
        $post1->title = 'title1';
        $post1->content = 'content1';
        $post1->human_readable_url = 'url1';
        $post1->created_at = date('Y-m-d H:i:s');
        $post1->updated_at = date('Y-m-d H:i:s');
        $post1->deleted_at = false;
        $post2 = new \stdClass();
        $post2->id = 2;
        $post2->title = 'title2';
        $post2->content = 'content2';
        $post2->human_readable_url = 'url2';
        $post2->created_at = date('Y-m-d H:i:s');
        $post2->updated_at = date('Y-m-d H:i:s');
        $post2->deleted_at = false;
        $posts = [
            $post1, $post2
        ];
        $collection = Mockery::mock(Collection::class, function ($mock) use ($posts) {
            $mock->shouldReceive('sortByDesc')
                ->with('created_at')
                ->times(1)
                ->andReturn($posts);
        });
        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($collection) {
            $mock->shouldReceive('getAll')
                ->times(1)
                ->andReturn($collection);
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
        $response->assertRedirect('/posts');
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
        $expectedPost = Mockery::mock(Post::class, function ($mock) {
            $mock->shouldReceive('getAttribute')
                ->with('id')
                ->andReturn(7);
            $mock->shouldReceive('getAttribute')
                ->with('title')
                ->andReturn('Post title');
            $mock->shouldReceive('getAttribute')
                ->with('content')
                ->andReturn('Post content');
            $mock->shouldReceive('getAttribute')
                ->with('human_readable_url')
                ->andReturn('url1');
        });

        $expectedResponse = $this->app->make(ResponseFactory::class)->view('posts.published.show', ['post' => $expectedPost]);

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
     * Test that the edit method shows the edit form for an existing Post
     *
     * @return void
     */
    public function testEdit()
    {
        // Setup
        $expectedPost = Mockery::mock(Post::class, function ($mock) {
            $mock->shouldReceive('getAttribute')
                ->with('id')
                ->andReturn(1);
            $mock->shouldReceive('getAttribute')
                ->with('title')
                ->andReturn('Post title1');
            $mock->shouldReceive('getAttribute')
                ->with('content')
                ->andReturn('Post content1');
            $mock->shouldReceive('getAttribute')
                ->with('human_readable_url')
                ->andReturn('url1');
        });

        $expectedResponse = $this->app->make(ResponseFactory::class)->view('posts.edit', ['post' => $expectedPost]);

        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($expectedPost) {
            $mock->shouldReceive('getById')
                ->with(1)
                ->andReturn($expectedPost);
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
        $response->assertRedirect('/posts');
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
        $response->assertRedirect('/posts');
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
