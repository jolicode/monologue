<?php

namespace App\Util;

class UuidGenerator
{
    public const V4_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    public static function v4(): string
    {
        if (\extension_loaded('uuid')) {
            return strtolower(uuid_create(\UUID_TYPE_RANDOM));
        }

        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            random_int(0, 0xFFFF),
            random_int(0, 0xFFFF),
            // 16 bits for "time_mid"
            random_int(0, 0xFFFF),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            random_int(0, 0x0FFF) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            random_int(0, 0x3FFF) | 0x8000,
            // 48 bits for "node"
            random_int(0, 0xFFFF),
            random_int(0, 0xFFFF),
            random_int(0, 0xFFFF)
        );
    }

    public static function isValidV4(string $uuid): bool
    {
        return (bool) preg_match(self::V4_REGEX, $uuid);
    }
}
