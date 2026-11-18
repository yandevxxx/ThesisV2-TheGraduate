<?php

namespace App\Libraries;

use Config\Services;

/**
 * Small cache-backed login throttle.
 *
 * There are two buckets: username + IP (strict) and IP alone (broad).
 * The login flow checks both before authenticating and records a hit only
 * after a failed attempt.
 */
class LoginThrottle
{
    private $cache;
    private $maxAttempts;
    private $decaySeconds;
    private $ipMaxAttempts;
    private $ipDecaySeconds;

    public function __construct()
    {
        $config = config('AuthSecurity');

        $this->cache          = Services::cache();
        $this->maxAttempts    = max(1, (int) ($config->loginMaxAttempts ?? 5));
        $this->decaySeconds   = max(1, (int) ($config->loginDecaySeconds ?? 60));
        $this->ipMaxAttempts  = max(1, (int) ($config->ipMaxAttempts ?? 20));
        $this->ipDecaySeconds = max(1, (int) ($config->ipDecaySeconds ?? 300));
    }

    public static function accountKey(string $username, string $ipAddress): string
    {
        return 'login_' . hash('sha256', strtolower(trim($username)) . '|' . $ipAddress);
    }

    public static function ipKey(string $ipAddress): string
    {
        return 'login_ip_' . hash('sha256', $ipAddress);
    }

    public function tooManyAttempts(string $key): bool
    {
        return $this->attempts($key) >= $this->limitFor($key);
    }

    public function attempts(string $key): int
{
    $data = $this->cache->get($key);

    if (is_array($data)) {
        return (int) ($data['attempts'] ?? 0);
    }

    // Kompatibilitas dengan data cache versi sebelumnya.
    return (int) ($data ?? 0);
}

    public function hit(string $key): int
{
    $attempts = $this->attempts($key) + 1;
    $decay    = $this->decayFor($key);

    $this->cache->save($key, [
        'attempts'   => $attempts,
        'expires_at' => time() + $decay,
    ], $decay);

    return $attempts;
}

    public function clear(string $key): void
    {
        $this->cache->delete($key);
    }

    public function availableIn(string $key): int
{
    if (! $this->tooManyAttempts($key)) {
        return 0;
    }

    $data = $this->cache->get($key);

    if (is_array($data) && isset($data['expires_at'])) {
        return max(0, (int) $data['expires_at'] - time());
    }

    // Fallback untuk data cache versi sebelumnya.
    return $this->decayFor($key);
}

    private function limitFor(string $key): int
    {
        return strpos($key, 'login_ip_') === 0 ? $this->ipMaxAttempts : $this->maxAttempts;
    }

    private function decayFor(string $key): int
    {
        return strpos($key, 'login_ip_') === 0 ? $this->ipDecaySeconds : $this->decaySeconds;
    }
}
