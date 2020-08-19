<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateByIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->withoutMiddleware(AuthenticateByIp::class);

        $response = $this->get('/');

        $response->assertRedirect(Route::prefix(config('app.url_prefix'))->get('posts')->uri());
    }
}
