<?php

namespace Acme\AccessGate\Exceptions;

use Exception;

class InvalidConfigurationException extends Exception
{
    public static function invalidExpirationType(string $type): self
    {
        return new self("Invalid expiration type: {$type}. Allowed: normal, progressive.");
    }

    public static function invalidMode(string $mode): self
    {
        return new self("Invalid UI mode: {$mode}. Allowed: blur, solid.");
    }
}
