<?php

namespace Acme\AccessGate\Middleware;

use Acme\AccessGate\AccessGateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireFeature
{
    public function __construct(
        protected AccessGateService $accessGate
    ) {}

    /**
     * Restricts access when the given feature is not allowed (e.g. not in config or expired).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        return app(RequireAccess::class)->handle($request, $next, null, $feature);
    }
}
