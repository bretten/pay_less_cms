<?php

namespace Tests\Feature\Http\Controllers;

use App\Contracts\Models\Post;
use App\Contracts\Models\Site;
use App\Http\Controllers\PostController;
use App\Http\Middleware\AuthenticateByIp;
use App\Repositories\PostRepositoryInterface;
use App\Repositories\SiteRepositoryInterface;
use App\Services\SiteFilesystemFactoryInterface;
use DateTime;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\FilesystemInterface;
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
        $post1 = new Post(1, 'site1', 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);
        $post2 = new Post(2, 'site1', 'title2', 'content2', 'url2', new DateTime('2020-08-15 02:02:02'), new DateTime('2020-08-15 02:02:02'), null);
        $posts = [
            $post1, $post2
        ];

        $site1 = new Site('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);
        $sites = [
            $site1
        ];

        $repo = Mockery::mock(PostRepositoryInterface::class, function ($mock) use ($posts) {
            $mock->shouldReceive('getAll')
                ->times(1)
                ->andReturn($posts);
        });
        $siteRepo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) use ($sites) {
            $mock->shouldReceive('getAll')
                ->andReturns($sites);
        });

        $this->app->instance(PostRepositoryInterface::class, $repo);
        $this->app->instance(SiteRepositoryInterface::class, $siteRepo);

        // Execute
        $response = $this->get('/posts');

        // Assert
        $response->assertStatus(200);
        $actualContentWithCsrfRemoved = $this->removeCsrf($response->baseResponse->content());

        usort($posts, function (Post $a, Post $b) { // The controller sorts the post by descending timestamp
            return $b->createdAt->getTimestamp() - $a->createdAt->getTimestamp();
        });
        $expectedResponse = $this->app->make(ResponseFactory::class)->view('posts.index', ['posts' => $posts, 'sites' => $sites]); // This needs to be after calling $this->get

        $this->assertEquals($this->removeCsrf($expectedResponse->content()), $actualContentWithCsrfRemoved);
    }

    /**
     * Test that the create method displays a view
     *
     * @return void
     */
    public function testCreate()
    {
        // Setup
        $expectedResponse = $this->app->make(ResponseFactory::class)->view('posts.create');

        // Execute
        $response = $this->get('/posts/create');

        // Assert
        $response->assertStatus(200);
        $actualContentWithCsrfRemoved = $this->removeCsrf($response->baseResponse->content());
        $this->assertEquals($expectedResponse->content(), $actualContentWithCsrfRemoved);
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
                ->with('site1', 'title1', 'content1', 'human-readable-url1')
                ->andReturn(true);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->post('/posts', [
            'site' => 'site1',
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
                ->with('site1', 'title1', 'content1', 'human-readable-url1')
                ->andReturn(false);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->post('/posts', [
            'site' => 'site1',
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
        $post = new Post(1, 'site1', 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);

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
        $post = new Post(1, 'site1', 'title1', 'content1', 'url1', new DateTime('2020-08-15 01:01:01'), new DateTime('2020-08-15 01:01:01'), null);

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
        $actualContentWithCsrfRemoved = $this->removeCsrf($response->baseResponse->content());
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
                ->with(1, 'site1 v2', 'title1 v2', 'content1 v2', 'human-readable-url1-v2')
                ->andReturn(true);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->put('/posts/1', [
            'site' => 'site1 v2',
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
                ->with(1, 'site1 v2', 'title1 v2', 'content1 v2', 'human-readable-url1-v2')
                ->andReturn(false);
        });
        $this->app->instance(PostRepositoryInterface::class, $repo);

        // Execute
        $response = $this->put('/posts/1', [
            'site' => 'site1 v2',
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

    /**
     * Test that the store method can upload an image
     *
     * @return void
     */
    public function testStoreCanUploadImage()
    {
        // Setup
        $site = 'site1';
        $file = UploadedFile::fake()->create('file1.jpg', 5000, 'image/jpeg');
        $uploadDestinationPath = 'assets' . DIRECTORY_SEPARATOR . $file->getClientOriginalName();
        $expectedUploadFullPath = DIRECTORY_SEPARATOR . $uploadDestinationPath;

        $adapter = Mockery::mock(AbstractAdapter::class, function ($mock) use ($expectedUploadFullPath) {
            $mock->shouldReceive('getPathPrefix')
                ->with()
                ->andReturn($expectedUploadFullPath);
        });
        $filesystem = Mockery::mock(FilesystemInterface::class, function ($mock) use ($uploadDestinationPath, $file, $adapter) {
            $mock->shouldReceive('put')
                ->with($uploadDestinationPath, $file->get())
                ->andReturn(true);
            $mock->shouldReceive('getAdapter')
                ->with()
                ->andReturn($adapter);
        });
        $filesystemFactory = Mockery::mock(SiteFilesystemFactoryInterface::class, function ($mock) use ($site, $filesystem) {
            $mock->shouldReceive('getSiteFilesystem')
                ->with($site)
                ->andReturn($filesystem);
        });
        $this->app->when(PostController::class)
            ->needs(SiteFilesystemFactoryInterface::class)
            ->give(function ($app) use ($filesystemFactory) {
                return $filesystemFactory;
            });

        // Execute
        $response = $this->post('/posts', [
            'site' => $site,
            'file' => $file
        ]);

        // Assert
        $response->assertJson([
            'location' => $expectedUploadFullPath
        ]);
        $response->assertStatus(200);
    }

    /**
     * Removes the CSRF token value from a form's markup
     *
     * @param string $content
     * @return string
     */
    private function removeCsrf(string $content)
    {
        $actualContentWithCsrfRemoved = preg_replace('/<input type="hidden" name="_token" value="(.*?)">/', '<input type="hidden" name="_token" value="">', $content);
        $actualContentWithCsrfRemoved = preg_replace('/xhr.setRequestHeader\(\'X-CSRF-Token\', \'(.*?)\'\);/', 'xhr.setRequestHeader(\'X-CSRF-Token\', \'\');', $actualContentWithCsrfRemoved);
        return $actualContentWithCsrfRemoved;
    }
}
