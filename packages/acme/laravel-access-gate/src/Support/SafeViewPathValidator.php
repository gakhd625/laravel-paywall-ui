<?php

namespace Acme\AccessGate\Support;

use Illuminate\Support\Facades\Log;

class SafeViewPathValidator
{
    public function __construct(
        protected array $allowedPrefixes = [],
        protected bool $strict = false
    ) {}

    /**
     * Validate custom view path to prevent path traversal and invalid characters.
     */
    public function validate(?string $viewPath): ?string
    {
        if ($viewPath === null || $viewPath === '') {
            return null;
        }

        if (str_contains($viewPath, '..') || str_contains($viewPath, '\\')) {
            $this->logWarning('Path traversal attempt detected', $viewPath);
            return null;
        }

        if (! preg_match('/^[a-zA-Z0-9._\/-]+$/', $viewPath)) {
            $this->logWarning('Invalid characters in view path', $viewPath);
            return null;
        }

        if ($this->allowedPrefixes !== [] && $this->strict) {
            $allowed = false;
            foreach ($this->allowedPrefixes as $prefix) {
                if (str_starts_with($viewPath, $prefix)) {
                    $allowed = true;
                    break;
                }
            }
            if (! $allowed) {
                $this->logWarning('View path not in allowed prefixes', $viewPath);
                return null;
            }
        }

        return $viewPath;
    }

    protected function logWarning(string $reason, string $path): void
    {
        if (app()->environment('local', 'development')) {
            Log::warning('Access Gate: Invalid custom view path', [
                'path' => $path,
                'reason' => $reason,
            ]);
        }
    }
}
