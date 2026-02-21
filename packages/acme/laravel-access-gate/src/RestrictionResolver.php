<?php

namespace Acme\AccessGate;

use Acme\AccessGate\Contracts\RestrictionResolverInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class RestrictionResolver implements RestrictionResolverInterface
{
    public function __construct() {}

    /**
     * @param  array{scope?: string, feature?: string, user?: \Illuminate\Contracts\Auth\Authenticatable}  $context
     */
    public function isRestricted(array $context = []): bool
    {
        $user = $context['user'] ?? Auth::user();

        if ($user && $this->userHasBypassRole($user)) {
            return false;
        }

        $expirationDate = $this->getExpirationDate($context);

        if ($expirationDate === null) {
            return config('paywall-ui.expiration.default_expires_at') === null
                ? false
                : Carbon::parse(config('paywall-ui.expiration.default_expires_at'), config('paywall-ui.expiration.timezone'))
                    ->endOfDay()
                    ->isPast();
        }

        $now = Carbon::now(config('paywall-ui.expiration.timezone'));

        return $now->greaterThan($expirationDate->endOfDay());
    }

    /**
     * @param  array{scope?: string, feature?: string, user?: \Illuminate\Contracts\Auth\Authenticatable, expires_at?: string|null}  $context
     */
    public function getExpirationDate(array $context = []): ?Carbon
    {
        $custom = config('paywall-ui.resolvers.expiration');
        if (is_callable($custom)) {
            $date = $custom($context);
            return $date instanceof Carbon ? $date : null;
        }
        if (is_string($custom) && class_exists($custom)) {
            $resolver = app($custom);
            if (method_exists($resolver, 'getExpirationDate')) {
                return $resolver->getExpirationDate($context);
            }
        }

        $explicit = $context['expires_at'] ?? null;
        $default = $explicit ?? config('paywall-ui.expiration.default_expires_at');
        if ($default === null || $default === '') {
            return null;
        }

        try {
            return Carbon::parse($default, config('paywall-ui.expiration.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array{scope?: string, feature?: string}  $context
     */
    public function getRestrictionReason(array $context = []): ?string
    {
        if (! $this->isRestricted($context)) {
            return null;
        }

        return config('paywall-ui.messages.fallback_message', 'Access restricted.');
    }

    protected function userHasBypassRole(Authenticatable $user): bool
    {
        $bypass = config('paywall-ui.roles.bypass', []);
        if ($bypass === []) {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            foreach ($bypass as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        if (method_exists($user, 'getRoleNames')) {
            $userRoles = $user->getRoleNames();
            foreach ($bypass as $role) {
                if ($userRoles->contains($role)) {
                    return true;
                }
            }
        }

        return false;
    }
}
