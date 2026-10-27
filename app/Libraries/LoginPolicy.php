<?php

namespace App\Libraries;

/**
 * Role gate for LDAP-first login (B-04).
 *
 * LDAP may only authenticate an existing local account when the LDAP role is
 * compatible with every local role owned by that account. This prevents an
 * LDAP account from gaining a local-only role such as admin, kaprodi,
 * fakultas, klien, or tamu through a successful LDAP bind.
 */
final class LoginPolicy
{
    /**
     * Local roles covered by each LDAP role.
     */
    private const LDAP_ROLE_MAP = [
        'Mahasiswa' => ['mahasiswa', 'wisudawan'],
        'Dosen'     => ['dosen', 'dospem eksternal'],
        'Staff'     => ['tendik'],
    ];

    /**
     * Roles that are managed by LDAP. Used when the LDAP role attribute is
     * empty: the account may pass only if it owns no local-only role.
     */
    private const LDAP_MANAGED_ROLES = [
        'mahasiswa',
        'wisudawan',
        'dosen',
        'dospem eksternal',
        'tendik',
    ];

    /**
     * Decide whether a successful LDAP result may be used for this account.
     *
     * @param string|null $ldapRole   Role mapped from the LDAP attribute.
     * @param string[]    $localRoles Roles from the akses table.
     */
    public static function ldapRoleAllowed($ldapRole, array $localRoles): bool
    {
        $localRoles = self::normalise($localRoles);

        // Nothing to escalate; Auth will report no_role after verification.
        if ($localRoles === []) {
            return true;
        }

        $ldapRole = trim((string) $ldapRole);

        if ($ldapRole === '') {
            foreach ($localRoles as $role) {
                if (! in_array($role, self::LDAP_MANAGED_ROLES, true)) {
                    return false;
                }
            }

            return true;
        }

        if (! isset(self::LDAP_ROLE_MAP[$ldapRole])) {
            return false;
        }

        return array_diff($localRoles, self::LDAP_ROLE_MAP[$ldapRole]) === [];
    }

    /**
     * @param string[] $roles
     *
     * @return string[]
     */
    private static function normalise(array $roles): array
    {
        $normalised = [];

        foreach ($roles as $role) {
            $role = strtolower(trim((string) $role));

            if ($role !== '' && ! in_array($role, $normalised, true)) {
                $normalised[] = $role;
            }
        }

        return $normalised;
    }
}
