# Laravel Access Gate

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-FF2D20?logo=laravel)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**Access restriction and subscription enforcement for Laravel** — UI lockout, backend route blocking, progressive overlay, role-based bypass, and feature-level locking. Built for agencies and SaaS.

---

## Features

- **UI lockout** — Full-screen overlay (blur or solid) when access is restricted
- **Backend route blocking** — Middleware to redirect or 403 before controllers run
- **Progressive lockout** — Gradual overlay opacity as expiration approaches
- **Blur / solid modes** — Configurable visual style
- **Content obfuscation** — Optional gibberish mode for underlying content
- **Role-based restrictions** — Bypass roles (e.g. `super_admin`) via config
- **Feature-level locking** — Gate by feature key (e.g. `RequireFeature('premium')`)
- **Dynamic messages** — Optional API or custom resolver for lockout message
- **Secure** — XSS-safe output, path traversal protection for custom views
- **PSR-4, testable** — Clean contracts and Laravel 10–12 support

---

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, or 12.x

---

## Installation

```bash
composer require acme/laravel-access-gate
```

Publish config:

```bash
php artisan vendor:publish --tag=paywall-ui-config
```

---

## Quick Start

**1. Set expiration (e.g. in `.env`):**

```env
PAYWALL_UI_EXPIRES_AT=2025-12-31
```

**2. Wrap your layout with the Blade component:**

```blade
<x-access-gate>
    @yield('content')
</x-access-gate>
```

When the expiration date has passed, users see the lockout overlay instead of the content.

**3. (Optional) Block routes with middleware:**

```php
Route::middleware(['require.subscription'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

---

## Configuration

| Section | Key | Description |
|--------|-----|-------------|
| **component** | `tag` | Blade component tag (default: `access-gate`). |
| | `custom_view` | Custom Blade view for lockout body. |
| **ui** | `mode` | `blur` or `solid`. |
| | `progressive_days` | Days for progressive overlay to reach full opacity. |
| | `gibberish` | Obfuscate background content when overlay is strong. |
| **expiration** | `type` | `normal` or `progressive`. |
| | `default_expires_at` | Default date (YYYY-MM-DD) or null. |
| **messages** | `title`, `message`, `fallback_message` | Lockout copy. |
| **api** | `enabled`, `url`, `timeout`, `retries`, `cache_ttl` | Dynamic message API. |
| **middleware** | `enabled`, `redirect_url`, `abort_code` | Route blocking behavior. |
| **roles** | `bypass` | Role names that bypass restriction. |
| **features** | Associative array | Feature keys and optional rules. |
| **resolvers** | `restriction`, `expiration`, `message` | Custom resolver classes. |
| **security** | `allowed_view_prefixes`, `strict_view_path` | Custom view allowlist. |

---

## Usage

### Blade component

```blade
<x-access-gate
    expires-at="2025-12-31"
    mode="blur"
    feature="premium_reports"
    :gibberish="true"
>
    {{ $slot or main content }}
</x-access-gate>
```

### Middleware

- **RequireSubscription** — Restricts when subscription (expiration) is past.
- **RequireFeature** — Restricts when feature is not allowed (e.g. `require.feature:premium`).
- **RequireAccess** — Generic: `require.access:scope,feature`.

### Programmatic

```php
use Acme\AccessGate\AccessGateService;

$service = app(AccessGateService::class);
if ($service->isRestricted(null, 'premium')) {
    return redirect()->route('billing');
}
$message = $service->getMessage();
```

---

## Customization

- **Custom view:** Set `component.custom_view` or pass `custom-view` to the component. View receives component props (e.g. `$message`, `$title`, `$opacity`, `$slot`).
- **Custom resolvers:** Implement `RestrictionResolverInterface` or `MessageResolverInterface` and register in `config/paywall-ui.php` under `resolvers.restriction` or `resolvers.message`.

---

## Security

- All dynamic output is escaped or sanitized (ids, classes, messages).
- Custom view paths are validated (no `..` or `\`); optional allowlist.
- **Always use middleware for real enforcement** — the UI overlay is for UX; backend middleware enforces access.

See [SECURITY.md](SECURITY.md) for reporting vulnerabilities.

---

## Testing

```bash
composer test
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

---

## License

MIT. See [LICENSE](LICENSE).

---

## Credits

Inspired by [laravel-ui-lockout](https://github.com/sticknologic/laravel-ui-lockout). Extended into a full access control engine for SaaS and agency use.
