<?php

declare(strict_types=1);

/**
 * Lightweight polyfills for mbstring functions when the extension
 * is not available in the PHP runtime. These fallbacks are not fully
 * multibyte-aware but allow the APIs to operate in environments
 * without mbstring by degrading gracefully to single-byte behaviour.
 */

if (!function_exists('mb_internal_encoding')) {
    function mb_internal_encoding(string $encoding): bool
    {
        // No-op fallback: nothing to configure without mbstring.
        return true;
    }
}

if (!function_exists('mb_check_encoding')) {
    function mb_check_encoding($value, ?string $encoding = null): bool
    {
        // Assume strings are valid when mbstring is unavailable.
        return true;
    }
}

if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding(string $value, string $to_encoding, $from_encoding = null): string
    {
        // No conversion possible without mbstring; return original string.
        return $value;
    }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, string $encoding = 'UTF-8'): string
    {
        return strtolower($string);
    }
}

if (!function_exists('mb_strpos')) {
    function mb_strpos(string $haystack, string $needle, int $offset = 0, string $encoding = 'UTF-8'): int|false
    {
        return strpos($haystack, $needle, $offset);
    }
}

