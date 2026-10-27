<?php

namespace App\Libraries;

/**
 * Credential resolver for existing local accounts (B-04).
 *
 * Order of verification:
 *   1. LDAP is always tried first.
 *   2. The LDAP result is accepted only when LoginPolicy allows its role.
 *   3. Otherwise a local password hash is used as fallback, when available.
 *
 * The manual_local path is used when the user explicitly ticks "Gunakan kata
 * sandi lokal (cadangan)"; it skips LDAP entirely so an administrator can log
 * in while the campus server is down.
 */
final class LoginResolver
{
    /** @var AuthLdap|null */
    private $ldap;

    public function __construct(?AuthLdap $ldap = null)
    {
        $this->ldap = $ldap;
    }

    /**
     * Resolve one credential attempt.
     *
     * The returned "reason" is safe for auth_logs only; it must never be shown
     * to the user. "method" is one of ldap, local, or manual_local.
     *
     * @param mixed $storedHash
     * @param array $localRoles
     *
     * @return array{authenticated: bool, method: string, reason: string|null, ldap: array|null}
     */
    public function resolve(string $username, string $password, $storedHash, array $localRoles, bool $forceLocal = false): array
    {
        if ($forceLocal) {
            $authenticated = LocalCredential::verify($password, $storedHash);

            return [
                'authenticated' => $authenticated,
                'method'        => 'manual_local',
                'reason'        => $authenticated ? null : 'invalid_credentials',
                'ldap'          => null,
            ];
        }

        $ldap   = $this->ldap === null ? new AuthLdap() : $this->ldap;
        $result = $ldap->authenticate($username, $password);

        if (! empty($result['authenticated'])) {
            $ldapRole = isset($result['role']) ? $result['role'] : null;

            if (LoginPolicy::ldapRoleAllowed($ldapRole, $localRoles)) {
                return [
                    'authenticated' => true,
                    'method'        => 'ldap',
                    'reason'        => null,
                    'ldap'          => $result,
                ];
            }

            $reason = 'ldap_role_mismatch';
        } else {
            $error  = isset($result['error']) ? (string) $result['error'] : 'configuration';
            $reason = $error === 'invalid_credentials' ? 'invalid_credentials' : 'ldap_' . $error;
        }

        $hasLocalHash = LocalCredential::isHash($storedHash);

        if ($hasLocalHash && LocalCredential::verify($password, $storedHash)) {
            return [
                'authenticated' => true,
                'method'        => 'local',
                'reason'        => $reason,
                'ldap'          => null,
            ];
        }

        return [
            'authenticated' => false,
            'method'        => $hasLocalHash ? 'local' : 'ldap',
            'reason'        => $reason,
            'ldap'          => null,
        ];
    }
}
