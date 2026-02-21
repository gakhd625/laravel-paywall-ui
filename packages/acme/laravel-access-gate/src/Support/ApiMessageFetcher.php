<?php

namespace Acme\AccessGate\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ApiMessageFetcher
{
    public function __construct(
        protected ?string $url,
        protected bool $enabled,
        protected int $timeout,
        protected int $retries,
        protected int $cacheTtl
    ) {}

    public function getMessage(): ?string
    {
        if (! $this->enabled || $this->url === null || $this->url === '') {
            return null;
        }

        $cacheKey = 'access_gate_message_' . md5($this->url);

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            return $this->fetch();
        });
    }

    protected function fetch(): ?string
    {
        $rateLimitKey = 'access-gate-api:' . md5($this->url);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            Log::warning('Access Gate: API rate limit exceeded', ['url' => $this->url]);
            return null;
        }

        try {
            RateLimiter::hit($rateLimitKey, 60);

            $response = Http::timeout($this->timeout)
                ->retry($this->retries, 100)
                ->get($this->url);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && isset($data['message']) && is_string($data['message'])) {
                    return $data['message'];
                }
            }
        } catch (\Throwable $e) {
            Log::error('Access Gate: API request failed', [
                'url' => $this->url,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
