<?php

namespace Acme\AccessGate\Middleware;

use Acme\AccessGate\AccessGateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSubscription
{
    public function __construct(
        protected AccessGateService $accessGate
    ) {}

    /**
     * Restricts access when subscription (expiration) is past. Uses scope "subscription".
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return app(RequireAccess::class)->handle($request, $next, 'subscription', null);
    }
}
