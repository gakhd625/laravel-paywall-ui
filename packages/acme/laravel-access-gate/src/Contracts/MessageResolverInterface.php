<?php

namespace Acme\AccessGate\Contracts;

interface MessageResolverInterface
{
    /**
     * Resolve the lockout message (config, API, or custom).
     *
     * @param  array{scope?: string, feature?: string}  $context
     */
    public function getMessage(array $context = []): string;
}
