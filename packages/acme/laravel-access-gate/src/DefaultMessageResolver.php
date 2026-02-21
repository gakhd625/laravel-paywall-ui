<?php

namespace Acme\AccessGate;

use Acme\AccessGate\Contracts\MessageResolverInterface;
use Acme\AccessGate\Support\ApiMessageFetcher;

class DefaultMessageResolver implements MessageResolverInterface
{
    public function __construct(
        protected ApiMessageFetcher $apiFetcher
    ) {}

    /**
     * @param  array{scope?: string, feature?: string}  $context
     */
    public function getMessage(array $context = []): string
    {
        $apiMessage = $this->apiFetcher->getMessage();

        if ($apiMessage !== null && $apiMessage !== '') {
            return $apiMessage;
        }

        return config('paywall-ui.messages.message', 'This content is temporarily unavailable.');
    }
}
