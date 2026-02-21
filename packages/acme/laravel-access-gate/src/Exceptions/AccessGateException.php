<?php

namespace Acme\AccessGate\Exceptions;

use Exception;

class AccessGateException extends Exception
{
    public static function unsupportedFramework(string $minVersion, string $current): self
    {
        return new self(
            "Laravel Access Gate requires Laravel {$minVersion} or higher. Current version: {$current}"
        );
    }
}
