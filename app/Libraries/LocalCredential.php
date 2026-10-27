<?php

namespace App\Libraries;

/**
 * Helper for the local password fallback (B-04).
 *
 * A local credential is only usable when the stored value is a real password
 * hash. Plain text and empty values are rejected so a corrupted row can never
 * turn into a password bypass.
 */
final class LocalCredential
{
    /**
     * Check whether the stored value is a recognised password hash.
     *
     * @param mixed $hash
     */
    public static function isHash($hash): bool
    {
        if (! is_string($hash) || $hash === '') {
            return false;
        }

        $info = password_get_info($hash);

        return isset($info['algoName']) && $info['algoName'] !== 'unknown';
    }

    /**
     * Verify a password against a local hash. Plain text never matches.
     *
     * @param mixed $hash
     */
    public static function verify(string $password, $hash): bool
    {
        if ($password === '' || ! self::isHash($hash)) {
            return false;
        }

        return password_verify($password, $hash);
    }
}
