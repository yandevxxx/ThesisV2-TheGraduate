<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Single source of truth for role based access control.
 *
 * Add a new role by:
 * 1. adding the role row in the RBAC migration,
 * 2. adding the controller/method entry below,
 * 3. adding the menu entry below (optional).
 *
 * No controller structure needs to change for new roles.
 */
class Rbac extends BaseConfig
{
    /**
     * Roles that may bypass controller level restrictions.
     *
     * @var string[]
     */
    public $superAdminRoles = ['admin'];

    /**
     * Equivalent role names. This keeps legacy data working while the
     * application moves to a canonical name.
     *
     * @var array<string, string[]>
     */
    public $roleAliases = [
        'prodi'  => ['kaprodi'],
        'kaprodi' => ['prodi'],
        'staf'   => ['tendik'],
        'tendik' => ['staf'],
    ];

    /**
     * Controller => method => allowed roles.
     *
     * A "*" entry is the fallback for every method in the controller.
     * Controllers not listed here keep their existing behaviour.
     *
     * @var array<string, array<string, string[]>>
     */
    public $controllerRoles = [
        'mahasiswa'          => ['*' => ['mahasiswa']],
        'tenagakependidikan' => ['*' => ['tendik']],
        'dosen'              => ['*' => ['dosen', 'dospem eksternal', 'klien']],
        'kaprodi'            => ['*' => ['kaprodi', 'prodi']],
        'fakultas'           => [
            '*'         => ['fakultas'],
            'dashboard' => ['fakultas', 'tendik', 'admin'],
            'index'     => ['fakultas'],
        ],
        'admin' => [
            '*' => ['admin'],
        ],
    ];

    /**
     * Controllers that are public or handle their own role checks.
     *
     * @var string[]
     */
    public $publicControllers = ['auth', 'home', 'graduate'];

    /**
     * Menu key => roles allowed to see that section.
     *
     * A menu key that is not listed is treated as a shared menu.
     *
     * @var array<string, string[]>
     */
    public $menuRoles = [
        'admin'              => ['admin'],
        'admin_users'        => ['admin'],
        'fakultas_dashboard' => ['fakultas', 'tendik'],
        'fakultas'           => ['fakultas'],
        'kaprodi_dashboard'  => ['kaprodi', 'prodi'],
        'kaprodi'            => ['kaprodi', 'prodi'],
        'dosen_dashboard'    => ['dosen', 'dospem eksternal'],
        'dosen'              => ['dosen'],
        'dospem'             => ['dospem eksternal'],
        'mahasiswa'          => ['mahasiswa'],
        'tendik'             => ['tendik', 'admin'],
        'klien'              => ['klien'],
    ];
}
