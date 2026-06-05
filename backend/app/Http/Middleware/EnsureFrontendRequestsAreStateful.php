<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Configuration\Middleware as MiddlewareAlias;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful as SanctumStatefulMiddleware;

class EnsureFrontendRequestsAreStateful
{
    /**
     * List of routes that should be excluded from stateful/CSRF checks
     */
    protected array $except = [
        '/api/login',
        '/api/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Exclude login endpoint from stateful checks
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        // Use Sanctum's stateful middleware for other routes
        $sanctumMiddleware = new SanctumStatefulMiddleware();

        return $sanctumMiddleware->handle($request, $next);
    }

    /**
     * Determine if the request should pass through stateful checks.
     */
    protected function shouldPassThrough(Request $request): bool
    {
        // Check if the request path matches any of the exceptions
        foreach ($this->except as $except) {
            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
