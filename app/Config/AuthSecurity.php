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

    /** Rolling window, in seconds, for $loginMaxAttempts (2 menit). */
    public $loginDecaySeconds = 120;

    /** Broad per-IP failed-login limit. */
    public $ipMaxAttempts = 20;

    /** Rolling window, in seconds, for $ipMaxAttempts (2 menit). */
    public $ipDecaySeconds = 120;

    /** Idle time, in seconds, before an authenticated session is ended (30 menit). */
    public $sessionIdleTimeout = 1800;

    /** Maximum lifetime, in seconds, since login_at before re-authentication is required (8 jam). */
    public $sessionAbsoluteTimeout = 28800;
}
