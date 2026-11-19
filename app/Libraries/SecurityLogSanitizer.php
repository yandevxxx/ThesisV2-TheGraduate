<?php

namespace App\Libraries;

/**
 * Helper sanitasi log dan pembentuk ID kejadian (Event ID).
 *
 * Memastikan karakter kontrol (anti log injection), kata sandi, cookie sesi,
 * token, dan isi lengkap respons LDAP tidak pernah tersimpan di log server.
 */
final class SecurityLogSanitizer
{
    /** @var string[] */
    private const SENSITIVE_KEYS = [
        'password',
        'passwd',
        'kata_sandi',
        'pwd',
        'pass',
        'cookie',
        'cookies',
        'set_cookie',
        'ci_session',
        'session_id',
        'token',
        'csrf',
        'csrf_test_name',
        'secret',
        'authorization',
        'bearer',
        'smtppass',
        'bindpass',
        'ldap_raw',
        'ldap_response',
        'ldap_entry',
        'entries',
        'entry',
        'dn',
        'userpassword',
    ];

    public static function newEventId(): string
    {
        try {
            $suffix = strtoupper(bin2hex(random_bytes(3)));
        } catch (\Throwable $e) {
            $suffix = strtoupper(substr(md5((string) microtime(true)), 0, 6));
        }

        return 'EVT-' . date('Ymd') . '-' . $suffix;
    }

    public static function sanitizeText(?string $text): string
    {
        $clean = (string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string) $text);
        $clean = trim($clean);

        if ($clean === '') {
            return '';
        }

        // Redact key=value / key: value secrets
        $clean = (string) preg_replace(
            '/\b(password|passwd|kata_sandi|pwd|secret|smtppass|bindpass|userpassword|token|csrf_test_name|ci_session|authorization)\b(\s*[:=]\s*)("[^"]*"|\'[^\']*\'|[^\s,;&|]+)/i',
            '$1$2[REDACTED]',
            $clean
        );

        // Redact Cookie / Set-Cookie headers
        $clean = (string) preg_replace(
            '/\b(Cookie|Set-Cookie)\b\s*:\s*[^|\r\n]+/i',
            '$1: [REDACTED]',
            $clean
        );

        // Redact Bearer tokens
        $clean = (string) preg_replace(
            '/\bBearer\s+[A-Za-z0-9\-._~+\/=]+/i',
            'Bearer [REDACTED]',
            $clean
        );

        // Redact LDAP bind password arguments or raw LDAP dump blocks
        $clean = (string) preg_replace(
            '/ldap_bind\([^,]+,\s*[^,]+,\s*[^)]+\)/i',
            'ldap_bind([REDACTED])',
            $clean
        );
        $clean = (string) preg_replace(
            '/\b(dn|entries|userpassword)\s*=>\s*.*/i',
            '$1 => [LDAP_REDACTED]',
            $clean
        );

        return trim($clean);
    }

    public static function sanitizeContext(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if (self::isSensitiveKey($lowerKey)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeContext($value);
                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = self::sanitizeText($value);
                continue;
            }

            if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $sanitized[$key] = $value;
                continue;
            }

            $sanitized[$key] = self::sanitizeText(is_object($value) ? get_class($value) : (string) $value);
        }

        return $sanitized;
    }

    public static function maskIdentifier(?string $identifier): string
    {
        $clean = self::sanitizeText($identifier);
        $len   = strlen($clean);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return substr($clean, 0, min(6, $len - 2)) . str_repeat('*', max(2, $len - 6));
    }

    private static function isSensitiveKey(string $lowerKey): bool
    {
        foreach (self::SENSITIVE_KEYS as $needle) {
            if ($lowerKey === $needle || strpos($lowerKey, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
