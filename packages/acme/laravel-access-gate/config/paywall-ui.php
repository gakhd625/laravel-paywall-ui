<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Component
    |--------------------------------------------------------------------------
    */
    'component' => [
        'tag' => env('PAYWALL_UI_COMPONENT_TAG', 'access-gate'),
        'custom_view' => env('PAYWALL_UI_CUSTOM_VIEW'),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'mode' => env('PAYWALL_UI_MODE', 'blur'), // blur | solid
        'progressive_days' => (int) env('PAYWALL_UI_PROGRESSIVE_DAYS', 7),
        'gibberish' => env('PAYWALL_UI_GIBBERISH', false),
        'message_type' => env('PAYWALL_UI_MESSAGE_TYPE', 'info'), // info | warning | error
    ],

    /*
    |--------------------------------------------------------------------------
    | Expiration
    |--------------------------------------------------------------------------
    */
    'expiration' => [
        'type' => env('PAYWALL_UI_EXPIRATION_TYPE', 'normal'), // normal | progressive
        'default_expires_at' => env('PAYWALL_UI_EXPIRES_AT'), // YYYY-MM-DD or null
        'timezone' => env('PAYWALL_UI_TIMEZONE', config('app.timezone', 'UTC')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'title' => env('PAYWALL_UI_TITLE', 'Access Restricted'),
        'message' => env('PAYWALL_UI_MESSAGE', 'This content is temporarily unavailable.'),
        'fallback_message' => env('PAYWALL_UI_FALLBACK_MESSAGE', 'Access to this application is currently restricted.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API (dynamic message)
    |--------------------------------------------------------------------------
    */
    'api' => [
        'enabled' => env('PAYWALL_UI_API_ENABLED', false),
        'url' => env('PAYWALL_UI_API_URL'),
        'timeout' => (int) env('PAYWALL_UI_API_TIMEOUT', 5),
        'retries' => (int) env('PAYWALL_UI_API_RETRIES', 2),
        'cache_ttl' => (int) env('PAYWALL_UI_API_CACHE_TTL', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware (backend route blocking)
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'enabled' => env('PAYWALL_UI_MIDDLEWARE_ENABLED', true),
        'redirect_url' => env('PAYWALL_UI_REDIRECT_URL'), // null = abort
        'abort_code' => (int) env('PAYWALL_UI_ABORT_CODE', 403),
    ],

    /*
    |--------------------------------------------------------------------------
    | Features (feature-flag style keys)
    |--------------------------------------------------------------------------
    | Keys that can be passed to RequireFeature middleware or component.
    | Optional: 'expires_at', 'roles_allowed' to bypass.
    */
    'features' => [
        // 'premium_reports' => ['expires_at' => null, 'roles_allowed' => ['admin']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    | Roles that always bypass restriction (e.g. super_admin).
    */
    'roles' => [
        'bypass' => array_filter(explode(',', env('PAYWALL_UI_ROLES_BYPASS', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resolvers (optional custom logic)
    |--------------------------------------------------------------------------
    | Restriction: Acme\AccessGate\Contracts\RestrictionResolverInterface
    | Expiration: callable or class that returns ?Carbon for current user/tenant
    */
    'resolvers' => [
        'restriction' => null, // RestrictionResolverInterface
        'expiration' => null,  // callable(): ?Carbon or class
        'message' => null,     // MessageResolverInterface
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        'allowed_view_prefixes' => [], // e.g. ['lockout.', 'vendor.paywall-ui']
        'strict_view_path' => env('PAYWALL_UI_STRICT_VIEW_PATH', false),
    ],

];
