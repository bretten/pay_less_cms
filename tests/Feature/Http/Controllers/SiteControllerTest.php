<?php


namespace Tests\Feature\Http\Controllers;


use App\Contracts\Models\Site;
use App\Http\Middleware\AuthenticateByIp;
use App\Repositories\SiteRepositoryInterface;
use DateTime;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class SiteControllerTest extends TestCase
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
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testIndex()
    {
        // Setup
        $site1 = new Site('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);
        $site2 = new Site('site1', 'title2', new DateTime('2021-05-29 02:02:02'), new DateTime('2021-05-29 02:02:02'), null);
        $sites = [
            $site1, $site2
        ];
        $repo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) use ($sites) {
            $mock->shouldReceive('getAll')
                ->times(1)
                ->andReturn($sites);
        });
        $this->app->instance(SiteRepositoryInterface::class, $repo);

        $expectedResponse = $this->app->make(ResponseFactory::class)->view('sites.index', ['sites' => $sites]);

        // Execute
        $response = $this->get('/sites');

        // Assert
        $response->assertStatus(200);
        $actualContentWithCsrfRemoved = $this->removeCsrf($response->baseResponse->content());
        $this->assertEquals($expectedResponse->content(), $actualContentWithCsrfRemoved);
    }

    /**
     * Test that the create method displays a view
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testCreate()
    {
        // Setup
        $expectedResponse = $this->app->make(ResponseFactory::class)->view('sites.create');

        // Execute
        $response = $this->get('/sites/create');

        // Assert
        $response->assertStatus(200);
        $actualContentWithCsrfRemoved = $this->removeCsrf($response->baseResponse->content());
        $this->assertEquals($expectedResponse->content(), $actualContentWithCsrfRemoved);
    }

    /**
     * Test that the store method can create a Site and return success
     *
     * @return void
     */
    public function testStoreCanCreateNewSite()
    {
        // Setup
        $repo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('create')
                ->with('site1', 'title1')
                ->andReturn(true);
        });
        $this->app->instance(SiteRepositoryInterface::class, $repo);

        // Execute
        $response = $this->post('/sites', [
            'domain_name' => 'site1',
            'title' => 'title1'
        ]);

        // Assert
        $response->assertRedirect(Route::prefix(config('app.url_prefix'))->get('sites')->uri());
    }

    /**
     * Test that the store method can handle an error when creating a Site
     *
     * @return void
     */
    public function testStoreCanHandleSiteCreationError()
    {
        // Setup
        $repo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('create')
                ->with('site1', 'title1')
                ->andReturn(false);
        });
        $this->app->instance(SiteRepositoryInterface::class, $repo);

        // Execute
        $response = $this->post('/sites', [
            'domain_name' => 'site1',
            'title' => 'title1'
        ]);

        // Assert
        $response->assertStatus(500);
    }

    /**
     * Test show method can display a single Site
     *
     * @return void
     */
    public function testShow()
    {
        // Setup
        $site = new Site('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);

        $expectedResponse = json_encode($site);

        $repo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) use ($site) {
            $mock->shouldReceive('getByDomainName')
                ->with('site1')
                ->andReturn($site);
        });
        $this->app->instance(SiteRepositoryInterface::class, $repo);

        // Execute
        $response = $this->get('/sites/site1');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals($expectedResponse, $response->baseResponse->content());
    }

    /**
     * Test show method returns a 404 when trying to show a Site with an invalid ID
     *
     * @return void
     */
    public function testShowReturns404WhenTryingToShowASiteWithAnInvalidId()
    {
        // Setup
        $repo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getByDomainName')
                ->with('site1')
                ->andThrow(new ModelNotFoundException);
        });
        $this->app->instance(SiteRepositoryInterface::class, $repo);

        // Execute
        $response = $this->get('/sites/site1');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test that the edit method shows the edit form for an existing Site
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testEdit()
    {
        // Setup
        $site = new Site('site1', 'title1', new DateTime('2021-05-29 01:01:01'), new DateTime('2021-05-29 01:01:01'), null);

        $expectedResponse = $this->app->make(ResponseFactory::class)->view('sites.edit', ['site' => $site]);

        $repo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) use ($site) {
            $mock->shouldReceive('getByDomainName')
                ->with('site1')
                ->andReturn($site);
        });
        $this->app->instance(SiteRepositoryInterface::class, $repo);

        // Execute
        $response = $this->get('/sites/site1/edit');

        // Assert
        $response->assertStatus(200);
        $actualContentWithCsrfRemoved = $this->removeCsrf($response->baseResponse->content());
        $this->assertEquals($expectedResponse->content(), $actualContentWithCsrfRemoved);
    }

    /**
     * Test edit method returns a 404 when trying to edit a Site with an invalid ID
     *
     * @return void
     */
    public function testEditReturns404WhenTryingToEditASiteWithAnInvalidId()
    {
        // Setup
        $repo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('getByDomainName')
                ->with('site1')
                ->andThrow(new ModelNotFoundException);
        });
        $this->app->instance(SiteRepositoryInterface::class, $repo);

        // Execute
        $response = $this->get('/sites/site1/edit');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test that the update method can update a Site and return success
     *
     * @return void
     */
    public function testUpdateCanUpdateExistingSite()
    {
        // Setup
        $repo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('update')
                ->with('site1', 'title1 v2')
                ->andReturn(true);
        });
        $this->app->instance(SiteRepositoryInterface::class, $repo);

        // Execute
        $response = $this->put('/sites/site1', [
            'site' => 'site1',
            'title' => 'title1 v2'
        ]);

        // Assert
        $response->assertRedirect(Route::prefix(config('app.url_prefix'))->get('sites')->uri());
    }

    /**
     * Test that the update method can handle an error when trying to update a Site
     *
     * @return void
     */
    public function testUpdateCanHandleError()
    {
        // Setup
        $repo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('update')
                ->with('site1', 'title1 v2')
                ->andReturn(false);
        });
        $this->app->instance(SiteRepositoryInterface::class, $repo);

        // Execute
        $response = $this->put('/sites/site1', [
            'site' => 'site1',
            'title' => 'title1 v2'
        ]);

        // Assert
        $response->assertStatus(500);
    }

    /**
     * Test that the destroy method can delete a Site
     *
     * @return void
     */
    public function testDestroy()
    {
        // Setup
        $repo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('delete')
                ->with('site1')
                ->andReturn(true);
        });
        $this->app->instance(SiteRepositoryInterface::class, $repo);

        // Execute
        $response = $this->delete('/sites/site1');

        // Assert
        $response->assertRedirect(Route::prefix(config('app.url_prefix'))->get('sites')->uri());
    }

    /**
     * Test that the destroy method can handle an error while trying to delete a Site
     *
     * @return void
     */
    public function testDestroyCanHandleError()
    {
        // Setup
        $repo = Mockery::mock(SiteRepositoryInterface::class, function ($mock) {
            $mock->shouldReceive('delete')
                ->with('site1')
                ->andReturn(false);
        });
        $this->app->instance(SiteRepositoryInterface::class, $repo);

        // Execute
        $response = $this->delete('/sites/site1');

        // Assert
        $response->assertStatus(500);
    }

    /**
     * Removes the CSRF token value from a form's markup
     *
     * @param string $content
     * @return string
     */
    private function removeCsrf(string $content)
    {
        return preg_replace('/<input type="hidden" name="_token" value="(.*?)">/', '<input type="hidden" name="_token" value="">', $content);
    }
}
