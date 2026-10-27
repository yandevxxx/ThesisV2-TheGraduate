<?php

namespace App\Libraries;

/**
 * Single source of truth for role-based navigation between
 * Thesis and The Graduate.
 */
final class PortalRouter
{
    public const CHOOSER = 'graduate/choose';

    /** Roles allowed to open The Graduate. */
    private const GRADUATE_ROLES = [
        'admin',
        'tendik',
        'dosen',
        'dospem eksternal',
        'mahasiswa',
        'wisudawan',
        'kaprodi',
        'prodi',
        'fakultas',
    ];

    /** Display order used when a user owns more than one role. */
    private const ROLE_PRIORITY = [
        'admin',
        'tendik',
        'prodi',
        'kaprodi',
        'fakultas',
        'dosen',
        'dospem eksternal',
        'wisudawan',
        'mahasiswa',
        'klien',
        'tamu',
    ];

    private const ROLE_LABELS = [
        'admin'            => 'Admin',
        'tendik'           => 'Tendik',
        'kaprodi'          => 'Kaprodi',
        'prodi'            => 'Prodi',
        'fakultas'         => 'Fakultas',
        'dosen'            => 'Dosen',
        'dospem eksternal' => 'Dosen Pembimbing Eksternal',
        'wisudawan'        => 'Wisudawan',
        'mahasiswa'        => 'Mahasiswa',
        'klien'            => 'Klien',
        'tamu'             => 'Tamu',
    ];

    /**
     * Normalise the user_session payload into lowercase role names.
     *
     * @param array $userData
     *
     * @return string[]
     */
    public static function normalizeRoles(array $userData): array
    {
        $roles = isset($userData['roles']) ? $userData['roles'] : [];

        if (! is_array($roles)) {
            return [];
        }

        $normalised = [];

        foreach ($roles as $role) {
            $role = strtolower(trim((string) $role));

            if ($role !== '' && ! in_array($role, $normalised, true)) {
                $normalised[] = $role;
            }
        }

        usort($normalised, function ($a, $b) {
            $priorityA = array_search($a, self::ROLE_PRIORITY, true);
            $priorityB = array_search($b, self::ROLE_PRIORITY, true);

            if ($priorityA === false) {
                $priorityA = PHP_INT_MAX;
            }

            if ($priorityB === false) {
                $priorityB = PHP_INT_MAX;
            }

            return $priorityA - $priorityB;
        });

        return $normalised;
    }

    /**
     * @param string[] $roles
     */
    public static function primaryRole(array $roles): ?string
    {
        $roles = self::normalizeRoles(['roles' => $roles]);

        return isset($roles[0]) ? $roles[0] : null;
    }

    /**
     * @param string[] $roles
     */
    public static function roleLabel(array $roles): string
    {
        $primary = self::primaryRole($roles);

        return isset(self::ROLE_LABELS[$primary]) ? self::ROLE_LABELS[$primary] : 'Pengguna';
    }

    /**
     * @param string[] $roles
     */
    public static function canUseGraduate(array $roles): bool
    {
        return (bool) array_intersect(self::normalizeRoles(['roles' => $roles]), self::GRADUATE_ROLES);
    }

    /**
     * Folder under app/Views/graduate that owns the user's dashboard.
     *
     * @param string[] $roles
     */
    public static function graduateFolder(array $roles): ?string
    {
        $roles = self::normalizeRoles(['roles' => $roles]);

        if (array_intersect($roles, ['admin', 'tendik', 'fakultas'])) {
            return 'admin_tendik';
        }

        if (array_intersect($roles, ['kaprodi', 'prodi'])) {
            return 'prodi';
        }

        if (in_array('wisudawan', $roles, true)) {
            return 'wisudawan';
        }

        if (array_intersect($roles, ['dosen', 'dospem eksternal'])) {
            return 'dosen';
        }

        if (in_array('mahasiswa', $roles, true)) {
            return 'mahasiswa';
        }

        return null;
    }

    /**
     * Thesis landing page for the current role.
     *
     * @param string[] $roles
     */
    public static function thesisLanding(array $roles): string
    {
        $roles = self::normalizeRoles(['roles' => $roles]);

        if (in_array('admin', $roles, true)) {
            return 'admin/dashboard';
        }

        if (in_array('tendik', $roles, true)) {
            return 'fakultas/dashboard';
        }

        if (array_intersect($roles, ['kaprodi', 'prodi'])) {
            return 'kaprodi/dashboard';
        }

        if (in_array('fakultas', $roles, true)) {
            return 'fakultas/dashboard';
        }

        if (array_intersect($roles, ['dosen', 'dospem eksternal'])) {
            return 'dosen/index';
        }

        return 'home';
    }

    /**
     * Graduate landing page for the current role.
     *
     * @param string[] $roles
     */
    public static function graduateLanding(array $roles): ?string
    {
        if (! self::canUseGraduate($roles)) {
            return null;
        }

        $folder = self::graduateFolder($roles);

        return $folder === null ? 'graduate/index' : 'graduate/' . $folder;
    }
}
