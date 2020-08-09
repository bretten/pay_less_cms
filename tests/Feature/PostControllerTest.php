<?php

namespace Tests\Feature;

use App\Repositories\PostRepositoryInterface;
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
     * Test show method is not implemented
     *
     * @return void
     */
    public function testShowIsNotImplemented()
    {
        $response = $this->get('/posts/show');

        $response->assertStatus(501);
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
