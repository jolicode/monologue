<?php

namespace App\Util;

class Uuid
{
    final public const V4_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    public static function isValidV4(string $uuid): bool
    {
        return (bool) preg_match(self::V4_REGEX, $uuid);
    }
}
