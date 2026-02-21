<?php

namespace Acme\AccessGate;

use Acme\AccessGate\Contracts\MessageResolverInterface;
use Acme\AccessGate\Contracts\RestrictionResolverInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AccessGateService
{
    protected ?bool $restrictionResult = null;

    protected ?float $opacityResult = null;

    protected ?string $scope = null;

    protected ?string $feature = null;

    protected ?string $expiresAtOverride = null;

    public function __construct(
        protected RestrictionResolverInterface $restrictionResolver,
        protected MessageResolverInterface $messageResolver
    ) {}

    /**
     * Whether access is restricted for the given scope/feature (and current user).
     */
    public function isRestricted(?string $scope = null, ?string $feature = null, ?string $expiresAt = null): bool
    {
        $this->scope = $scope;
        $this->feature = $feature;
        $this->expiresAtOverride = $expiresAt;
        $context = $this->context();

        $custom = config('paywall-ui.resolvers.restriction');
        if ($custom && is_string($custom) && class_exists($custom)) {
            $resolver = app($custom);
            if ($resolver instanceof RestrictionResolverInterface) {
                return $resolver->isRestricted($context);
            }
        }

        return $this->restrictionResolver->isRestricted($context);
    }

    /**
     * Overlay opacity 0.0–1.0 (for progressive lockout).
     */
    public function getOverlayOpacity(?string $scope = null, ?string $feature = null, ?string $expiresAt = null): float
    {
        $context = array_merge($this->context(), [
            'scope' => $scope ?? $this->scope,
            'feature' => $feature ?? $this->feature,
            'expires_at' => $expiresAt ?? $this->expiresAtOverride,
        ]);

        $expirationDate = $this->restrictionResolver->getExpirationDate($context);
        $type = config('paywall-ui.expiration.type', 'normal');
        $progressiveDays = (int) config('paywall-ui.ui.progressive_days', 7);

        if ($expirationDate === null) {
            $default = config('paywall-ui.expiration.default_expires_at');
            if ($default === null) {
                return 0.0;
            }
            try {
                $expirationDate = Carbon::parse($default, config('paywall-ui.expiration.timezone'));
            } catch (\Throwable) {
                return 1.0;
            }
        }

        $now = Carbon::now(config('paywall-ui.expiration.timezone'));

        if ($now->greaterThan($expirationDate->endOfDay())) {
            return 1.0;
        }

        if ($type !== 'progressive') {
            return 0.0;
        }

        $daysUntil = $now->diffInDays($expirationDate->endOfDay(), false);
        if ($daysUntil >= $progressiveDays) {
            return 0.0;
        }

        $daysPassed = $progressiveDays - $daysUntil;

        return min(1.0, $daysPassed / $progressiveDays);
    }

    /**
     * Lockout message (config, API, or custom resolver).
     */
    public function getMessage(?string $scope = null, ?string $feature = null): string
    {
        $context = [
            'scope' => $scope ?? $this->scope,
            'feature' => $feature ?? $this->feature,
        ];

        return $this->messageResolver->getMessage($context);
    }

    /**
     * Reason for restriction (e.g. for 403 JSON response).
     */
    public function getRestrictionReason(?string $scope = null, ?string $feature = null): ?string
    {
        $context = [
            'scope' => $scope ?? $this->scope,
            'feature' => $feature ?? $this->feature,
            'user' => Auth::user(),
        ];

        return $this->restrictionResolver->getRestrictionReason($context);
    }

    /**
     * Resolve expiration date for current context (for custom views or APIs).
     */
    public function resolveExpirationDate(?string $scope = null, ?string $feature = null): ?Carbon
    {
        $context = $this->context();
        $context['scope'] = $scope ?? $this->scope;
        $context['feature'] = $feature ?? $this->feature;

        return $this->restrictionResolver->getExpirationDate($context);
    }

    protected function context(): array
    {
        return [
            'scope' => $this->scope,
            'feature' => $this->feature,
            'expires_at' => $this->expiresAtOverride,
            'user' => Auth::user(),
        ];
    }
}
