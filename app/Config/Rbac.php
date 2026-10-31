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
        'graduate' => [
            '*' => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'wisudawan', 'kaprodi', 'prodi', 'fakultas'],

            // The chooser must stay available to every authenticated role,
            // including roles that cannot open The Graduate.
            'choose' => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'wisudawan', 'kaprodi', 'prodi', 'fakultas', 'klien', 'tamu'],
            'enter'  => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'wisudawan', 'kaprodi', 'prodi', 'fakultas', 'klien', 'tamu'],
            'index'  => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'wisudawan', 'kaprodi', 'prodi', 'fakultas', 'klien', 'tamu'],

            'admin_tendik' => ['admin', 'tendik', 'staf', 'fakultas'],
            'prodi'        => ['kaprodi', 'prodi'],
            'dosen'        => ['dosen', 'dospem eksternal'],
            'mahasiswa'    => ['mahasiswa'],
            'wisudawan'    => ['wisudawan'],

            // SK Yudisium plotting is a staff-only feature.
            'sk_yudisium'                => ['admin', 'tendik', 'staf'],
            'skyudisiumupload'           => ['admin', 'tendik', 'staf'],
            'skyudisiumpreview'          => ['admin', 'tendik', 'staf'],
            'skyudisiumsave'             => ['admin', 'tendik', 'staf'],
            'skyudisiumsettanggal'       => ['admin', 'tendik', 'staf'],
            'skyudisiumbulksettanggal'   => ['admin', 'tendik', 'staf'],
            'skyudisiumupdatestatus'     => ['admin', 'tendik', 'staf'],
            'skyudisiumdownloadtemplate' => ['admin', 'tendik', 'staf'],
            'skyudisiumclearpreview'     => ['admin', 'tendik', 'staf'],
            'skyudisiumdelete'           => ['admin', 'tendik', 'staf'],
            'skyudisiumstore'            => ['admin', 'tendik', 'staf'],

            // Shared menu pages.
            'berkas'              => ['admin', 'tendik', 'staf', 'fakultas'],
            'semester'            => ['admin', 'tendik', 'staf', 'fakultas'],
            'calon_wisudawan'     => ['admin', 'tendik', 'staf', 'fakultas'],
            'mahasiswa_wisudawan' => ['admin', 'tendik', 'staf', 'fakultas'],
            'dosen_pembimbing'    => ['admin', 'tendik', 'staf', 'fakultas'],
            'persyaratan'         => ['admin', 'tendik', 'staf', 'fakultas'],
            'skl'                 => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'pendaftaran_wisuda'  => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'pengambilan_ijazah'  => ['mahasiswa'],
            'tracer_studi'        => ['mahasiswa'],

            // Notification endpoints for Graduate
            'fetchnotif'          => ['admin', 'tendik', 'staf'],
            'markasread'          => ['admin', 'tendik', 'staf'],
            'semua_notifikasi'    => ['admin', 'tendik', 'staf'],

            // Profile (ubah data diri) untuk semua role Graduate
            'profile'             => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'wisudawan', 'kaprodi', 'prodi', 'fakultas'],
            'saveprofile'         => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'wisudawan', 'kaprodi', 'prodi', 'fakultas'],
        ],
    ];

    /**
     * Controllers that are public or handle their own role checks.
     *
     * @var string[]
     */
    public $publicControllers = ['auth', 'home'];

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
