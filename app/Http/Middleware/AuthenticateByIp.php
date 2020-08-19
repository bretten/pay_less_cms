<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;

class AuthenticateByIp
{
    /**
     * @var array $trustedIps
     */
    private array $trustedIps;

    /**
     * Constructor
     */
    public function __construct(Application $app)
    {
        $this->trustedIps = $app['config']['auth.trusted_ips'];
    }

    /**
     * If the IP is not trusted, return a forbidden response. Otherwise,
     * let the request continue further into the application.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!in_array($request->ip(), $this->trustedIps)) {
            return response(null, 403);
        }
        return $next($request);
    }
}
