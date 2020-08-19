<?php


namespace Tests\Feature\Http\Middleware;


use App\Http\Middleware\AuthenticateByIp;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class AuthenticateByIpTest extends TestCase
{
    /**
     * Tests that the middleware can authenticate a trusted IP
     */
    public function testAllowTrustedIp()
    {
        // Setup
        $trustedIps = ['192.168.7.1', '192.168.7.2'];
        $app = Mockery::mock(Application::class, function ($mock) use ($trustedIps) {
            $mock->shouldReceive('offsetGet')
                ->with('config')
                ->andReturn([
                    'auth.trusted_ips' => $trustedIps
                ]);
        });
        $request = Mockery::mock(Request::class, function ($mock) {
            $mock->shouldReceive('ip')
                ->times(1)
                ->andReturn('192.168.7.2');
        });
        $middleware = new AuthenticateByIp($app);

        // Execute
        $result = false;
        $middleware->handle($request, function ($r) use (&$result) {
            $result = true;
        });

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Tests that the middleware will forbid an unknown IP
     */
    public function testForbidUnknownIp()
    {
        // Setup
        $trustedIps = ['192.168.7.1', '192.168.7.2'];
        $app = Mockery::mock(Application::class, function ($mock) use ($trustedIps) {
            $mock->shouldReceive('offsetGet')
                ->with('config')
                ->andReturn([
                    'auth.trusted_ips' => $trustedIps
                ]);
        });
        $request = Mockery::mock(Request::class, function ($mock) {
            $mock->shouldReceive('ip')
                ->times(1)
                ->andReturn('192.168.7.10');
        });
        $middleware = new AuthenticateByIp($app);

        // Execute
        $result = false;
        $middleware->handle($request, function ($r) use (&$result) {
            $result = true;
        });

        // Assert
        $this->assertFalse($result);
    }
}
