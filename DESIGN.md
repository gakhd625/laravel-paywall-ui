# Access Restriction & Subscription Enforcement — Package Design

A professional Laravel package for **UI lockout**, **backend route blocking**, **role-based restrictions**, and **feature-level locking**. Built for agencies and SaaS applications to enforce access by expiration, subscription status, or feature flags.

---

## 1. Package Architecture

### 1.1 Recommended Package Name

| Option | Name | Rationale |
|--------|------|-----------|
| **Recommended** | **Laravel Access Gate** / `yourvendor/laravel-access-gate` | Premium, clear, and describes “gating” access. |
| Alternative | **Laravel Restrict** / `laravel-restrict` | Short and action-oriented. |
| Agency-focused | **Laravel Client Gate** | Emphasizes “client” (agency use case). |
| SaaS-focused | **Laravel Subscription Gate** | Ties directly to subscriptions. |

**Suggested commercial name:** **AccessGate** (product) → package: `yourvendor/laravel-access-gate`.

---

### 1.2 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         Application / Routes                             │
└─────────────────────────────────────────────────────────────────────────┘
     │                              │
     ▼                              ▼
┌─────────────────┐        ┌─────────────────────────────────────────────┐
│  Blade Layout   │        │  Middleware (optional)                        │
│  <x-access-gate>│        │  - RequireSubscription / RequireFeature       │
│  - Wraps UI     │        │  - Redirect or Abort(403)                     │
│  - Overlay      │        └─────────────────────────────────────────────┘
└────────┬────────┘                              │
         │                                       │
         ▼                                       ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    AccessGateService (single source of truth)             │
│  - isRestricted(): bool (per scope/feature/expiration)                   │
│  - getRestrictionReason(): ?string                                        │
│  - getMessage(): string (config / API / callback)                        │
│  - resolveExpirationDate(): ?Carbon (user, tenant, config)                │
└─────────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────────────┐
│  Config         │    │  Drivers /       │    │  API / Callbacks        │
│  paywall-ui.php │    │  Resolvers       │    │  (dynamic messages)      │
└─────────────────┘    └─────────────────┘    └─────────────────────────┘
```

- **Service Provider:** Registers config, views, Blade component, middleware aliases, and binds `AccessGateService` (and optional custom drivers).
- **Middleware:** Uses `AccessGateService` to block routes (redirect or 403) when access is restricted.
- **Blade component:** Uses the same service for UI lockout (overlay, blur/solid, progressive, gibberish).
- **Config:** Single `paywall-ui.php` (or `access-gate.php`) for all options; environment-driven where needed.

---

### 1.3 Config Structure (Single File)

Single config file: `config/paywall-ui.php` (or `config/access-gate.php`).

**Sections:**

| Section | Purpose |
|--------|---------|
| `component` | Blade tag, default view namespace. |
| `ui` | Default mode (blur/solid), progressive_days, gibberish, message type (info/warning/error). |
| `expiration` | Default type (normal/progressive), default expires_at, timezone. |
| `messages` | title, message, fallback_message. |
| `api` | enable_api, api_url, timeout, retries, cache_duration. |
| `middleware` | enable_route_blocking, redirect_url, abort_code. |
| `features` | Feature-flag keys and optional expiration/roles. |
| `roles` | Role names that bypass or that are subject to restriction. |
| `resolvers` | Custom class for expiration date / restriction (e.g. per user/tenant). |
| `security` | Allowed view paths, strict mode (reject invalid view paths). |

---

## 2. Folder Structure

```
packages/yourvendor/laravel-access-gate/   (or repo root if repo = package)
├── composer.json
├── LICENSE
├── README.md
├── SECURITY.md
├── config/
│   └── paywall-ui.php
├── resources/
│   └── views/
│       ├── component.blade.php
│       └── modes/
│           ├── blur.blade.php
│           └── solid.blade.php
├── routes/
│   └── (optional) api.php for message endpoint
├── src/
│   ├── AccessGateServiceProvider.php
│   ├── AccessGateService.php           # Core: isRestricted(), getMessage(), etc.
│   ├── Contracts/
│   │   ├── RestrictionResolverInterface.php
│   │   └── MessageResolverInterface.php
│   ├── Middleware/
│   │   ├── RequireAccess.php           # Generic: uses RestrictionResolver
│   │   ├── RequireSubscription.php     # Alias / convenience
│   │   └── RequireFeature.php          # Feature-flag gate
│   ├── View/
│   │   └── Components/
│   │       └── AccessGate.php          # Blade component
│   ├── Support/
│   │   ├── GibberishObfuscator.php
│   │   ├── SafeViewPathValidator.php
│   │   └── ApiMessageFetcher.php
│   └── Exceptions/
│       ├── AccessGateException.php
│       ├── InvalidConfigurationException.php
│       └── FrameworkNotSupportedException.php
└── tests/
    ├── Unit/
    │   ├── AccessGateServiceTest.php
    │   ├── GibberishObfuscatorTest.php
    │   └── SafeViewPathValidatorTest.php
    └── Feature/
        ├── ComponentRenderingTest.php
        └── MiddlewareTest.php
```

---

## 3. Core Classes and Responsibilities

| Class | Responsibility |
|-------|----------------|
| **AccessGateServiceProvider** | Merge config, publish config/views, register Blade component, register middleware aliases, bind AccessGateService and optional resolver contracts. |
| **AccessGateService** | `isRestricted(?string $scope = null, ?string $feature = null): bool`; `getRestrictionReason(): ?string`; `getMessage(): string`; `getOverlayOpacity(): float`; `resolveExpirationDate(): ?Carbon`. Delegates to resolvers for per-user/tenant/feature. |
| **RestrictionResolverInterface** | `isRestricted(array $context): bool`, `getExpirationDate(array $context): ?Carbon`. Default implementation uses config + optional auth user. |
| **MessageResolverInterface** | `getMessage(array $context): string`. Default: config + API fetcher + fallback. |
| **AccessGate (Blade component)** | Renders slot or lockout overlay. Gets restriction state and opacity from AccessGateService; supports mode (blur/solid), progressive, gibberish, custom view; escapes output (XSS-safe). |
| **RequireAccess** | Middleware: calls AccessGateService; if restricted, redirect or Abort(403). |
| **RequireSubscription** | Middleware: alias or thin wrapper for “subscription not expired” (uses expiration resolver). |
| **RequireFeature** | Middleware: gate by feature key (config or resolver). |
| **GibberishObfuscator** | Replace text nodes in HTML with length-preserving gibberish (same API as reference). |
| **SafeViewPathValidator** | Validate custom view paths (no `..`, `\`, invalid chars); used by component. |
| **ApiMessageFetcher** | HTTP fetch + cache + rate limit; returns message string or null (fallback to config). |

---

## 4. Example Config File

See `config/paywall-ui.php` in the scaffold (full example below in section 4.1).

**Highlights:**

- `component_tag` → Blade tag (e.g. `access-gate`).
- `ui.mode`, `ui.progressive_days`, `ui.gibberish`, `ui.message_type`.
- `expiration.type`, `expiration.default_expires_at`, `expiration.timezone`.
- `messages.title`, `messages.message`, `messages.fallback_message`.
- `api.*` for dynamic message URL.
- `middleware.enabled`, `redirect_url`, `abort_code`.
- `features` and `roles` for feature/role-based rules.
- `resolvers.restriction`, `resolvers.expiration` (optional custom classes).
- `security.allowed_view_prefixes`, `security.strict_view_path`.

---

## 5. Example Middleware Logic

```php
// src/Middleware/RequireAccess.php
public function handle(Request $request, Closure $next, ?string $scope = null, ?string $feature = null): Response
{
    $service = app(AccessGateService::class);

    if (!$service->isRestricted($scope, $feature)) {
        return $next($request);
    }

    if ($request->expectsJson()) {
        return response()->json(['message' => $service->getRestrictionReason()], 403);
    }

    $redirectUrl = config('paywall-ui.middleware.redirect_url');
    if ($redirectUrl) {
        return redirect($redirectUrl);
    }

    abort(config('paywall-ui.middleware.abort_code', 403));
}
```

- **RequireSubscription:** Same, but scope fixed to `subscription` (expiration-based).
- **RequireFeature:** Pass feature key as parameter; `AccessGateService::isRestricted(null, $feature)`.

---

## 6. Example Blade Component Class

```php
// src/View/Components/AccessGate.php
public function __construct(
    ?string $expiresAt = null,
    ?string $mode = null,
    ?string $feature = null,
    ?string $scope = null,
    bool $useApiMessage = true,
    ?string $customView = null,
    ?string $id = null,
    ?string $class = null,
) {
    $this->accessGate = app(AccessGateService::class);
    $this->expiresAt = $expiresAt ?? config('paywall-ui.expiration.default_expires_at');
    $this->feature = $feature;
    $this->scope = $scope;
    // Resolve restriction and opacity from AccessGateService
    $this->isRestricted = $this->accessGate->isRestricted($scope, $feature);
    $this->opacity = $this->accessGate->getOverlayOpacity();
    $this->mode = $this->validateMode($mode ?? config('paywall-ui.ui.mode'));
    $this->customView = $this->safeViewValidator->validate($customView ?? config('paywall-ui.component.custom_view'));
    $this->message = $useApiMessage ? $this->accessGate->getMessage() : config('paywall-ui.messages.message');
    $this->title = config('paywall-ui.messages.title');
    $this->id = $this->sanitizeId($id ?? 'ag-' . Str::random(4));
    $this->class = $this->sanitizeClass($class);
}

public function shouldDisplay(): bool
{
    return $this->isRestricted || $this->opacity > 0;
}

public function render()
{
    return view('paywall-ui::component');
}
```

- All user-controllable output (id, class, title, message, custom view path) is validated/escaped (see Security).

---

## 7. Security Considerations

| Risk | Mitigation |
|------|------------|
| **XSS** | Escape all dynamic output in Blade (`{{ }}`). Sanitize `id`/`class` (alphanumeric, hyphen, underscore only). |
| **Path traversal** | Validate custom view path: no `..`, `\`. Allow only view names (e.g. `a-zA-Z0-9._/-`). Use `SafeViewPathValidator` and optional allowlist by prefix. |
| **Open redirect** | If `redirect_url` is user-dependent, validate against allowlist or same-origin. Prefer config-only redirect. |
| **API abuse** | Rate limit API message fetcher per URL; timeout and retry limits in config. |
| **Bypass** | Backend enforcement via middleware; never rely on UI-only lockout for sensitive actions. Document that UI is UX; middleware is enforcement. |
| **Config injection** | Use config (and env) for URLs/keys; avoid passing raw user input into resolver bindings. |

---

## 8. Possible Extension Ideas

- **Multi-tenant:** Resolver that uses `tenant_id` or current tenant to resolve expiration/features.
- **Stripe/Laravel Cashier:** Resolver that checks subscription status and end date.
- **Feature flags:** Integrate with Laravel Pennant or custom store; `RequireFeature('premium-report')`.
- **Webhook:** Endpoint to receive “subscription updated” and clear cache/state.
- **Admin bypass:** Role (e.g. `super_admin`) that always passes `isRestricted()`.
- **Scheduled lockout:** Cron-driven “maintenance lockout” with start/end and optional message API.
- **A/B testing:** Different messages or lockout strictness by segment (resolver returns different state by segment).
- **Audit log:** Optional logging when middleware blocks a request (user, route, reason).

---

## 9. How to Make It Monetizable Later

- **Free tier:** Single expiration date (config), one overlay mode, no middleware, community support.
- **Pro / Agency:** Middleware, role-based rules, feature flags, API message, custom view, priority support.
- **Enterprise:** Custom resolvers (per-tenant, Cashier, SSO), SLA, audit log, white-label.
- **Monetization mechanics:** License key in config; optional “phone home” check; separate package `yourvendor/laravel-access-gate-pro` with same API but more features; or single package with feature toggles driven by license.

---

## 10. README Structure Outline (Professional-Grade)

1. **Badge row** — PHP, Laravel 10|11|12, License, Tests.
2. **Short tagline** — One sentence (access restriction & subscription enforcement for Laravel).
3. **Features** — Bullet list (UI lockout, backend middleware, progressive overlay, blur/solid, gibberish, roles, feature flags, dynamic messages, security).
4. **Requirements** — PHP 8.2+, Laravel 10|11|12.
5. **Installation** — Composer, publish config (and optional views), env vars if any.
6. **Quick start** — Wrap layout with `<x-access-gate>`, set `expires_at` (or use resolver).
7. **Configuration** — Link to config file; tables for main options (component, ui, expiration, messages, api, middleware, features, roles).
8. **Usage**  
   - Blade component (attributes, slots, custom view).  
   - Middleware (global vs route, RequireSubscription, RequireFeature).  
   - Programmatic (AccessGateService in controllers).
9. **Customization** — Custom view, custom resolver (contract + bind in provider).
10. **Security** — XSS, path traversal, backend enforcement; link to SECURITY.md.
11. **Testing** — How to run tests; basic assertions.
12. **Changelog** — Link to CHANGELOG.md.
13. **License** — MIT (or your license).
14. **Credits** — Inspired by laravel-ui-lockout; your vendor.

---

## Next Step

The scaffold in `packages/yourvendor/laravel-access-gate/` implements this design with config, Service Provider, AccessGateService, middleware, Blade component, and support classes. Rename vendor/package and adjust namespaces as needed.
