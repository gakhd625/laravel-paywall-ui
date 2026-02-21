<?php

namespace Acme\AccessGate;

use Acme\AccessGate\Contracts\MessageResolverInterface;
use Acme\AccessGate\Contracts\RestrictionResolverInterface;
use Acme\AccessGate\Middleware\RequireAccess;
use Acme\AccessGate\Middleware\RequireFeature;
use Acme\AccessGate\Middleware\RequireSubscription;
use Acme\AccessGate\Support\ApiMessageFetcher;
use Acme\AccessGate\Support\SafeViewPathValidator;
use Acme\AccessGate\View\Components\AccessGate;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AccessGateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->ensureLaravelVersion();

        $this->mergeConfigFrom(
            __DIR__ . '/../config/paywall-ui.php',
            'paywall-ui'
        );

        $this->app->singleton(SafeViewPathValidator::class, function () {
            return new SafeViewPathValidator(
                config('paywall-ui.security.allowed_view_prefixes', []),
                config('paywall-ui.security.strict_view_path', false)
            );
        });

        $this->app->singleton(ApiMessageFetcher::class, function () {
            return new ApiMessageFetcher(
                config('paywall-ui.api.url'),
                config('paywall-ui.api.enabled', false),
                config('paywall-ui.api.timeout', 5),
                config('paywall-ui.api.retries', 2),
                config('paywall-ui.api.cache_ttl', 600)
            );
        });

        $this->app->singleton(DefaultMessageResolver::class, function () {
            return new DefaultMessageResolver(app(ApiMessageFetcher::class));
        });

        $this->app->singleton(RestrictionResolver::class, function () {
            return new RestrictionResolver;
        });

        $this->app->singleton(AccessGateService::class, function () {
            return new AccessGateService(
                $this->resolveRestrictionResolver(),
                $this->resolveMessageResolver()
            );
        });

        $this->app->alias(AccessGateService::class, 'access-gate');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/paywall-ui.php' => config_path('paywall-ui.php'),
        ], 'paywall-ui-config');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'paywall-ui');

        $tag = config('paywall-ui.component.tag', 'access-gate');
        Blade::component(AccessGate::class, $tag);

        $this->registerMiddleware();
    }

    protected function ensureLaravelVersion(): void
    {
        if (! $this->app instanceof Application) {
            throw new Exceptions\AccessGateException('This package requires Laravel.');
        }

        $version = $this->app->version();
        if (version_compare($version, '10.0.0', '<')) {
            throw Exceptions\AccessGateException::unsupportedFramework('10.0', $version);
        }
    }

    protected function resolveRestrictionResolver(): RestrictionResolverInterface
    {
        $custom = config('paywall-ui.resolvers.restriction');
        if (is_string($custom) && class_exists($custom)) {
            $resolver = app($custom);
            if ($resolver instanceof RestrictionResolverInterface) {
                return $resolver;
            }
        }

        return app(RestrictionResolver::class);
    }

    protected function resolveMessageResolver(): MessageResolverInterface
    {
        $custom = config('paywall-ui.resolvers.message');
        if (is_string($custom) && class_exists($custom)) {
            $resolver = app($custom);
            if ($resolver instanceof MessageResolverInterface) {
                return $resolver;
            }
        }

        return app(DefaultMessageResolver::class);
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app->make('router');
        $router->aliasMiddleware('require.access', RequireAccess::class);
        $router->aliasMiddleware('require.subscription', RequireSubscription::class);
        $router->aliasMiddleware('require.feature', RequireFeature::class);
    }
}
