<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateByIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        $this->withoutMiddleware(AuthenticateByIp::class);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
