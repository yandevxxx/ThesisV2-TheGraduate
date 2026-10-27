<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Authentication hardening settings.
 *
 * Values may be overridden from .env using the authsecurity. prefix,
 * for example: authsecurity.sessionIdleTimeout = 7200
 */
class AuthSecurity extends BaseConfig
{
    /** Maximum failed login attempts for one account + IP. */
    public $loginMaxAttempts = 5;

    /** Rolling window, in seconds, for $loginMaxAttempts. */
    public $loginDecaySeconds = 60;

    /** Broad per-IP failed-login limit. */
    public $ipMaxAttempts = 20;

    /** Rolling window, in seconds, for $ipMaxAttempts. */
    public $ipDecaySeconds = 300;

    /** Idle time before an authenticated session is ended. */
    public $sessionIdleTimeout = 7200;
}
