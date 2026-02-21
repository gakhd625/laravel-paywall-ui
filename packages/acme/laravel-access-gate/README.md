# Laravel Access Gate

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)

## Project Description

A Laravel package for access restriction and subscription enforcement. It shows a lockout screen to block users from accessing your app and can optionally block routes in the backend. Built to help agencies and developers manage clients who haven't paid their invoices—preventing them from using the app until payment is complete—and to support SaaS apps with feature-level locking and role-based bypass.

**Works with Laravel 10.x, 11.x, and 12.x.**

---

## Project Use Cases

This package is perfect for:

**Main Purpose:** Stop clients who haven't paid from accessing or using your Laravel application—with both a UI lockout and optional backend route blocking.

- **Subscription Expired** — Block access when a client's subscription or license ends
- **Trial Period Ended** — Lock the app after the free trial finishes
- **Maintenance Mode** — Show a custom lockout page during updates or billing issues
- **Protect Sensitive Data** — Use gibberish mode to completely obscure confidential information
- **Feature Gating** — Restrict specific features or routes (e.g. premium reports) via middleware
- **Role-Based Bypass** — Let admins or support roles bypass the lockout via config

---

## How to Use

### Basic Usage

**1. Install the package**

```bash
composer require acme/laravel-access-gate
```

**2. Publish the config file** (optional)

```bash
php artisan vendor:publish --tag=paywall-ui-config
```

**3. Wrap your content with the component**

```blade
<x-access-gate expires-at="2026-12-31">
    {{-- Your main app content goes here --}}
    <div class="container">
        <h1>Welcome to Your App</h1>
        <p>This content shows normally until the expiration date.</p>
    </div>
</x-access-gate>
```

That's it! Your content shows normally until the expiration date, then the lockout screen takes over.

**4. (Optional) Block routes in the backend**

Apply middleware so restricted users cannot hit protected routes at all (JSON 403 or redirect):

```php
Route::middleware(['require.subscription'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

---

### Configuration Reference

All available configuration options in `config/paywall-ui.php`:

```php
<?php

return [
    // Component
    'component' => [
        'tag' => 'access-gate',           // Blade tag: <x-access-gate>
        'custom_view' => null,            // Custom lockout view path
    ],

    // UI
    'ui' => [
        'mode' => 'blur',                 // 'blur' or 'solid'
        'progressive_days' => 7,          // Days for progressive fade-in
        'gibberish' => false,             // Obfuscate background content
        'message_type' => 'info',         // 'info' | 'warning' | 'error'
    ],

    // Expiration
    'expiration' => [
        'type' => 'normal',               // 'normal' or 'progressive'
        'default_expires_at' => null,     // YYYY-MM-DD or null
        'timezone' => 'UTC',
    ],

    // Messages
    'messages' => [
        'title' => 'Access Restricted',
        'message' => 'This content is temporarily unavailable.',
        'fallback_message' => 'Access to this application is currently restricted.',
    ],

    // API (dynamic lockout message)
    'api' => [
        'enabled' => false,
        'url' => null,
        'timeout' => 5,
        'retries' => 2,
        'cache_ttl' => 600,
    ],

    // Middleware (backend route blocking)
    'middleware' => [
        'enabled' => true,
        'redirect_url' => null,           // null = abort 403
        'abort_code' => 403,
    ],

    // Roles that bypass restriction
    'roles' => [
        'bypass' => [],                   // e.g. ['super_admin']
    ],

    // Feature keys (for RequireFeature middleware)
    'features' => [],

    // Custom resolver classes (optional)
    'resolvers' => [
        'restriction' => null,
        'expiration' => null,
        'message' => null,
    ],

    // Security (custom view paths)
    'security' => [
        'allowed_view_prefixes' => [],
        'strict_view_path' => false,
    ],
];
```

You can also set many of these via environment variables (e.g. `PAYWALL_UI_EXPIRES_AT`, `PAYWALL_UI_MODE`). See the published config file for the full list.

---

### Advanced Usage

#### 1. Progressive Lockout (Gradual Fade-In)

Show the lockout message gradually as the deadline approaches.

**Config:** Set `expiration.type` to `'progressive'` and `ui.progressive_days` (e.g. `7`) in your config file (see [Configuration Reference](#configuration-reference)).

**Component usage:**

```blade
<x-access-gate expires-at="2026-03-01">
    {{-- Your main content --}}
    <div class="app-content">
        <h1>Your Application</h1>
        <p>Regular content here...</p>
    </div>
</x-access-gate>
```

**How it works:**

- **7+ days before:** Content displays normally (0% lockout overlay)
- **Within 7 days:** Lockout overlay fades in day by day
- **Expiration date:** Lockout fully blocks access (100% opacity)

#### 2. Display Modes

Choose between two visual styles.

**Blur mode (default)**

```blade
<x-access-gate expires-at="2026-12-31" mode="blur">
    <div>Your content</div>
</x-access-gate>
```

Shows a blurred backdrop with a centered card containing the lockout message.

**Solid mode**

```blade
<x-access-gate expires-at="2026-12-31" mode="solid">
    <div>Your content</div>
</x-access-gate>
```

Shows a solid color background covering the entire screen.

**Config:** Set `ui.mode` to `'blur'` or `'solid'` (see [Configuration Reference](#configuration-reference)).

#### 3. Gibberish Mode (Content Obfuscation)

Convert background content into gibberish to completely obscure sensitive information.

**Component attribute:**

```blade
<x-access-gate expires-at="2026-12-31" :gibberish="true">
    <div>Your sensitive content</div>
</x-access-gate>
```

**How it works:**

- Replaces all text content with random characters
- Maintains exact length and spacing for visual consistency
- Preserves HTML structure and tags
- Useful for protecting sensitive data even in blur mode

**Config:** Set `ui.gibberish` to `true` to enable globally (see [Configuration Reference](#configuration-reference)).

#### 4. Custom Title and Message

Set `messages.title`, `messages.message`, and `messages.fallback_message` in your config file (see [Configuration Reference](#configuration-reference)). The lockout component uses these; the API message (if enabled) overrides the message when available.

#### 5. Using API for Dynamic Messages

Fetch lockout messages from your API for real-time updates.

**Step 1: Set up your API endpoint**

Your API should return JSON with a `message` field:

```json
{
    "message": "Payment overdue. Please update your billing information."
}
```

**Step 2: Configure the API**

Set in config: `api.enabled`, `api.url`, `api.timeout`, `api.retries`, and `api.cache_ttl` (see [Configuration Reference](#configuration-reference)). The message is cached to limit API calls. If the API fails, the fallback message is shown.

#### 6. Customize the Component Tag Name

Change `<x-access-gate>` to something shorter by setting `component.tag` in your config file (see [Configuration Reference](#configuration-reference)).

**Example:** If you set `'tag' => 'lockout'`:

```blade
<x-lockout expires-at="2026-12-31">
    <div>Your content</div>
</x-lockout>
```

#### 7. Custom Lockout Screen Design

Create a custom Blade view:

```blade
{{-- resources/views/lockout/custom.blade.php --}}
<div style="text-align:center; padding:3rem;">
    <h1 style="color:#dc2626; font-size:3rem;">🔒 Access Denied</h1>
    <p style="font-size:1.2rem; margin-top:1rem;">{{ $message }}</p>
    @if($title)
        <p>{{ $title }}</p>
    @endif
</div>
```

**Component attribute:**

```blade
<x-access-gate expires-at="2026-12-31" custom-view="lockout.custom">
    <div>Your content</div>
</x-access-gate>
```

**Note:** Use `custom-view` (with hyphen) in the component tag.

**Config:** Set `component.custom_view` to your view path to use it globally (see [Configuration Reference](#configuration-reference)).

**Example with gibberish content:**

```blade
{{-- resources/views/lockout/with-gibberish.blade.php --}}
<div class="lockout-overlay">
    <div class="message-card">
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
    </div>
    @if($gibberish)
        <div class="gibberish-background">
            {!! $htmlToGibberish((string) $slot) !!}
        </div>
    @else
        <div class="blurred-background">
            {{ $slot }}
        </div>
    @endif
</div>
```

**Available variables in custom views:**

- `$message` (string) — Lockout message
- `$title` (?string) — Lockout title, may be null
- `$opacity` (float) — Overlay opacity (0.0 to 1.0)
- `$expiresAt` (?string) — Expiration date string, may be null
- `$isRestricted` (bool) — Whether access is restricted
- `$mode` (string) — `'blur'` or `'solid'`
- `$gibberish` (bool) — Whether gibberish mode is enabled
- `$slot` — The wrapped content
- `$shouldDisplay()` (bool) — Whether the lockout overlay should be shown
- `$htmlToGibberish($html)` (HtmlString) — Converts HTML text to gibberish; use with `{!! !!}` where needed

#### 8. Backend Middleware (Route Blocking)

Use middleware to block restricted users from hitting routes at all (not just the UI).

**Require subscription (expiration-based):**

```php
Route::middleware(['require.subscription'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

**Require a feature (feature flag style):**

```php
Route::middleware(['require.feature:premium_reports'])->group(function () {
    Route::get('/reports/premium', [ReportsController::class, 'premium']);
});
```

**Generic require access (scope/feature):**

```php
Route::middleware(['require.access:subscription,premium'])->get('/premium', ...);
```

When restricted, the middleware returns a JSON 403 for AJAX/API requests, or redirects to `middleware.redirect_url` if set; otherwise it aborts with `middleware.abort_code` (default 403).

#### 9. Role-Based Bypass

Users with a bypass role never see the lockout and are not blocked by the middleware. Configure roles in config:

```php
'roles' => [
    'bypass' => ['super_admin', 'support'],
],
```

The package looks for `hasRole()` or `getRoleNames()` on the authenticated user (e.g. Laravel Spatie Permission). Implement one of these methods or use a custom `resolvers.restriction` class.

#### 10. Component Attributes Reference

All available component attributes:

```blade
<x-access-gate
    expires-at="2026-12-31"     {{-- Expiration date (YYYY-MM-DD) --}}
    mode="blur"                {{-- 'blur' or 'solid' --}}
    feature="premium_reports"  {{-- Feature key for gating --}}
    scope="subscription"      {{-- Optional scope --}}
    :use-api-message="true"    {{-- Use API/custom message --}}
    :gibberish="false"         {{-- Enable content obfuscation --}}
    id="main-gate"             {{-- Custom ID for wrapper div --}}
    class="custom-wrapper"     {{-- Custom CSS classes --}}
    custom-view="lockout.custom" {{-- Custom view path --}}
>
    <div>Your content</div>
</x-access-gate>
```

**Security notes:**

- `id` and `class` values are sanitized to prevent XSS attacks
- `custom-view` paths are validated to prevent path traversal attacks
- All attributes are optional and fall back to config values

**Attribute details:**

- `expires-at` — Override expiration date for this component
- `mode` — Visual style (blur or solid)
- `feature` — Feature key (used with custom resolvers / feature config)
- `scope` — Optional scope for resolver context
- `use-api-message` — Whether to use API or custom message resolver
- `gibberish` — Replace background text with gibberish when overlay is strong
- `id` — Alphanumeric and hyphens only
- `class` — Extra CSS classes for the wrapper
- `custom-view` — Blade view path (validated for security)

#### 11. Global Configuration

With defaults set in the configuration file (see [Configuration Reference](#configuration-reference)), you can use the component without attributes:

```blade
<x-access-gate>
    <div>Your content</div>
</x-access-gate>
```

Expiration and messaging then come from config (and optional resolvers).

---

## Contributing

Want to help improve this package?

1. Fork the repository
2. Create a new branch for your feature
3. Make your changes
4. Submit a pull request

We welcome contributions—bug fixes, new features, or documentation improvements.

---

## Security

Found a security issue? Please **do not** open a public issue.

Report security concerns privately (e.g. via the repository maintainer or security policy). For more details, see [SECURITY.md](SECURITY.md).

**Important:** The UI lockout is for user experience. Always use the provided middleware (or equivalent server-side checks) to enforce access to sensitive routes and APIs.

---

## License

This package is open-source software licensed under the [MIT License](LICENSE).

---

## Credits

Inspired by [Laravel UI Lockout](https://github.com/sticknologic/laravel-ui-lockout). Extended into a full access control engine for SaaS and agency use—with backend middleware, role-based bypass, feature gating, and custom resolvers.
