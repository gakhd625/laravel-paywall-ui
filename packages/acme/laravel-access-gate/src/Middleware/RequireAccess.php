<?php

namespace Acme\AccessGate\Middleware;

use Acme\AccessGate\AccessGateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAccess
{
    public function __construct(
        protected AccessGateService $accessGate
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $scope
     * @param  string|null  $feature
     */
    public function handle(Request $request, Closure $next, ?string $scope = null, ?string $feature = null): Response
    {
        if (! $this->accessGate->isRestricted($scope, $feature)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->accessGate->getRestrictionReason($scope, $feature) ?? 'Access restricted.',
            ], config('paywall-ui.middleware.abort_code', 403));
        }

        $redirectUrl = config('paywall-ui.middleware.redirect_url');
        if ($redirectUrl) {
            return redirect($redirectUrl);
        }

        abort(config('paywall-ui.middleware.abort_code', 403));
    }
}
