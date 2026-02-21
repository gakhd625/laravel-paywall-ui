<?php

namespace Acme\AccessGate\Contracts;

use Carbon\Carbon;

interface RestrictionResolverInterface
{
    /**
     * Whether access is restricted for the given context.
     *
     * @param  array{scope?: string, feature?: string, user?: \Illuminate\Contracts\Auth\Authenticatable}  $context
     */
    public function isRestricted(array $context = []): bool;

    /**
     * Expiration date for the context (e.g. subscription ends at). Null = no date-based restriction.
     *
     * @param  array{scope?: string, feature?: string, user?: \Illuminate\Contracts\Auth\Authenticatable, expires_at?: string|null}  $context
     */
    public function getExpirationDate(array $context = []): ?Carbon;

    /**
     * Human-readable reason for restriction (e.g. for API response).
     */
    public function getRestrictionReason(array $context = []): ?string;
}
