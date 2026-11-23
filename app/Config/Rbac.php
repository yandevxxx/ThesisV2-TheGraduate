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
        'prodi'            => ['kaprodi'],
        'kaprodi'          => ['prodi'],
        'staf'             => ['tendik'],
        'tendik'           => ['staf'],
        'dosen'            => ['dospem eksternal'],
        'dospem eksternal' => ['dosen'],
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
    'home' => [
        '*' => [
            'admin',
            'tendik',
            'staf',
            'dosen',
            'dospem eksternal',
            'mahasiswa',
            'alumni',
            'kaprodi',
            'prodi',
            'fakultas',
            'klien',
            'tamu'
        ],
    ],

    'graduate' => [
            // The chooser must stay available to every authenticated role,
            // including roles that cannot open The Graduate.
            'choose' => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'alumni', 'kaprodi', 'prodi', 'fakultas', 'klien', 'tamu'],
            'enter'  => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'alumni', 'kaprodi', 'prodi', 'fakultas', 'klien', 'tamu'],
            'index'  => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'alumni', 'kaprodi', 'prodi', 'fakultas', 'klien', 'tamu'],

            'admin_tendik' => ['admin', 'tendik', 'staf', 'fakultas'],
            'fakultas'     => ['fakultas', 'admin'],
            'prodi'        => ['kaprodi', 'prodi'],
            'dosen'        => ['dosen', 'dospem eksternal'],
            'mahasiswa'    => ['mahasiswa'],
            'alumni'       => ['alumni'],

            // SK Yudisium plotting is a staff-only feature.
            'sk_yudisium'                => ['admin', 'tendik', 'staf'],
            'skyudisiumupload'           => ['admin', 'tendik', 'staf'],
            'skyudisiumpreview'          => ['admin', 'tendik', 'staf'],
            'skyudisiumsave'             => ['admin', 'tendik', 'staf'],
            'skyudisiumsettanggal'       => ['admin', 'tendik', 'staf'],
            'skyudisiumbulksettanggal'   => ['admin', 'tendik', 'staf'],
            'bulkedit'                   => ['admin', 'tendik', 'staf'],
            'skyudisiumbulkedit'         => ['admin', 'tendik', 'staf'],
            'bulkdelete'                 => ['admin', 'tendik', 'staf'],
            'skyudisiumbulkdelete'       => ['admin', 'tendik', 'staf'],
            'skyudisiumupdatestatus'     => ['admin', 'tendik', 'staf'],
            'skyudisiumdownloadtemplate' => ['admin', 'tendik', 'staf'],
            'skyudisiumclearpreview'     => ['admin', 'tendik', 'staf'],
            'skyudisiumdelete'           => ['admin', 'tendik', 'staf'],
            'skyudisiumstore'            => ['admin', 'tendik', 'staf'],

            // Shared menu pages.
            'berkas'              => ['admin', 'tendik', 'staf', 'fakultas'],
            'semester'            => ['admin', 'fakultas', 'tendik', 'staf'],
            'semesterstore'       => ['admin', 'fakultas'],
            'semesterupdate'      => ['admin', 'fakultas'],
            'semesteractivate'    => ['admin', 'fakultas'],
            'semesternonactivate' => ['admin', 'fakultas'],
            'semesterdelete'      => ['admin', 'fakultas'],
            'calon_wisudawan'     => ['admin', 'tendik', 'staf', 'fakultas'],
            'mahasiswa_wisudawan' => ['admin', 'tendik', 'staf', 'fakultas'],
            'dosen_pembimbing'    => ['admin', 'tendik', 'staf', 'fakultas'],
            'persyaratan'              => ['admin', 'tendik', 'staf'],
            'persyaratanstore'         => ['admin', 'tendik', 'staf'],
            'persyaratanupdate'        => ['admin', 'tendik', 'staf'],
            'persyaratandelete'        => ['admin', 'tendik', 'staf'],
            'persyaratantogglestatus'  => ['admin', 'tendik', 'staf'],
            'persyaratanreorder'       => ['admin', 'tendik', 'staf'],
            'skl'                 => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'skl_kelola'                => ['admin', 'tendik', 'staf', 'fakultas'],
            'skl_mahasiswa'             => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'sklkelola'                 => ['admin', 'tendik', 'staf', 'fakultas'],
            'sklmahasiswa'              => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'skluploadberkas'           => ['mahasiswa'],
            'sklhapusberkas'            => ['mahasiswa'],
            'sklsubmit'                 => ['mahasiswa'],
            'sklverifikasi'             => ['admin', 'tendik', 'staf', 'fakultas'],
            'sklproses'                 => ['admin', 'tendik', 'staf', 'fakultas'],
            'skldownloadtemplate'       => ['admin', 'tendik', 'staf', 'fakultas'],
            'skl_download_template'     => ['admin', 'tendik', 'staf', 'fakultas'],
            'sklkirim'                  => ['admin', 'tendik', 'staf', 'fakultas'],
            'skldownloadresmi'          => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'skl_download_resmi'        => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'persyaratansklstore'       => ['admin', 'tendik', 'staf', 'fakultas'],
            'persyaratansklupdate'      => ['admin', 'tendik', 'staf', 'fakultas'],
            'persyaratanskldelete'      => ['admin', 'tendik', 'staf', 'fakultas'],
            'persyaratanskltogglestatus' => ['admin', 'tendik', 'staf', 'fakultas'],
            'pendaftaran_wisuda'            => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'pendaftaranwisudasubmit'       => ['mahasiswa'],
            'pendaftaranwisudaverifikasi'    => ['admin', 'tendik', 'staf'],
            'pendaftaranwisudauploadberkas' => ['mahasiswa'],
            'pendaftaranwisudahapusberkas'  => ['mahasiswa'],
            'biodata_wisuda'                => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'biodatawisudasubmit'           => ['mahasiswa'],
            'biodata_wisuda_verifikasi'     => ['admin', 'tendik', 'staf', 'fakultas'],
            'biodata_wisuda_export'         => ['admin', 'tendik', 'staf', 'fakultas'],
            'biodatawisudaexport'           => ['admin', 'tendik', 'staf', 'fakultas'],
            'biodatawisudaverifikasi'       => ['admin', 'tendik', 'staf'],
            'berkas_wisuda'                 => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'berkaswisudasubmit'            => ['mahasiswa'],
            'berkas_wisuda_verifikasi'      => ['admin', 'tendik', 'staf', 'fakultas'],
            'berkaswisudaverifikasi'        => ['admin', 'tendik', 'staf'],
            'previewberkas'                 => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'previewfoto'                   => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'belum_mendaftar'          => ['admin', 'tendik', 'staf', 'fakultas'],
            'pengambilan_ijazah'       => ['mahasiswa'],
            'tracer_studi'             => ['mahasiswa'],
            'tracerstudi'              => ['mahasiswa'],
            'tracerstudisubmit'        => ['mahasiswa'],
            'pertanyaan_tracer_studi'      => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudi'        => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudistore'   => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstuditampil'  => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudiurutan'  => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudiupdate'      => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudidelete'      => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstuditogglewajib' => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudipratinjau'      => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudiambilbank'     => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudisimpankebank'  => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudinonaktifkan'   => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'bank_pertanyaan_tracer_studi'       => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'bankpertanyaantracerstudi'          => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'bankpertanyaantracerstudistore'     => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'bankpertanyaantracerstudiupdate'    => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'bankpertanyaantracerstudidelete'    => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'tracer_studi_pantau'                => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'tracerstudipantau'            => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],

            // Notification endpoints for Graduate
            'fetchnotif'          => ['admin', 'tendik', 'staf'],
            'markasread'          => ['admin', 'tendik', 'staf'],
            'semua_notifikasi'    => ['admin', 'tendik', 'staf'],

            // Profile (ubah data diri) untuk semua role Graduate
            'profile'             => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'alumni', 'kaprodi', 'prodi', 'fakultas'],
            'saveprofile'         => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'alumni', 'kaprodi', 'prodi', 'fakultas'],
        ],

        'notificationcontroller' => [
            '*'                => ['admin', 'tendik', 'staf'],
            'fetchnotif'       => ['admin', 'tendik', 'staf'],
            'markasread'       => ['admin', 'tendik', 'staf'],
            'semua_notifikasi' => ['admin', 'tendik', 'staf'],
        ],

        'profilecontroller' => [
            '*'           => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'alumni', 'kaprodi', 'prodi', 'fakultas'],
            'index'       => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'alumni', 'kaprodi', 'prodi', 'fakultas'],
            'profile'     => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'alumni', 'kaprodi', 'prodi', 'fakultas'],
            'saveprofile' => ['admin', 'tendik', 'staf', 'dosen', 'dospem eksternal', 'mahasiswa', 'alumni', 'kaprodi', 'prodi', 'fakultas'],
        ],

        'skyudisiumcontroller' => [
            '*'                          => ['admin', 'tendik', 'staf'],
            'index'                      => ['admin', 'tendik', 'staf'],
            'sk_yudisium'                => ['admin', 'tendik', 'staf'],
            'downloadtemplate'           => ['admin', 'tendik', 'staf'],
            'skyudisiumdownloadtemplate' => ['admin', 'tendik', 'staf'],
            'upload'                     => ['admin', 'tendik', 'staf'],
            'skyudisiumupload'           => ['admin', 'tendik', 'staf'],
            'preview'                    => ['admin', 'tendik', 'staf'],
            'skyudisiumpreview'          => ['admin', 'tendik', 'staf'],
            'save'                       => ['admin', 'tendik', 'staf'],
            'skyudisiumsave'             => ['admin', 'tendik', 'staf'],
            'clearpreview'               => ['admin', 'tendik', 'staf'],
            'skyudisiumclearpreview'     => ['admin', 'tendik', 'staf'],
            'settanggal'                 => ['admin', 'tendik', 'staf'],
            'skyudisiumsettanggal'       => ['admin', 'tendik', 'staf'],
            'bulksettanggal'             => ['admin', 'tendik', 'staf'],
            'skyudisiumbulksettanggal'   => ['admin', 'tendik', 'staf'],
            'bulkedit'                   => ['admin', 'tendik', 'staf'],
            'skyudisiumbulkedit'         => ['admin', 'tendik', 'staf'],
            'bulkdelete'                 => ['admin', 'tendik', 'staf'],
            'skyudisiumbulkdelete'       => ['admin', 'tendik', 'staf'],
            'updatestatus'               => ['admin', 'tendik', 'staf'],
            'skyudisiumupdatestatus'     => ['admin', 'tendik', 'staf'],
            'delete'                     => ['admin', 'tendik', 'staf'],
            'skyudisiumdelete'           => ['admin', 'tendik', 'staf'],
            'store'                      => ['admin', 'tendik', 'staf'],
            'skyudisiumstore'            => ['admin', 'tendik', 'staf'],
        ],

        'semestercontroller' => [
            'index'               => ['admin', 'fakultas', 'tendik', 'staf'],
            'semester'            => ['admin', 'fakultas', 'tendik', 'staf'],
            'store'               => ['admin', 'fakultas'],
            'update'              => ['admin', 'fakultas'],
            'activate'            => ['admin', 'fakultas'],
            'nonactivate'         => ['admin', 'fakultas'],
            'delete'              => ['admin', 'fakultas'],
        ],
        'persyaratanwisudacontroller' => [
            '*'            => ['admin', 'tendik', 'staf'],
            'index'        => ['admin', 'tendik', 'staf'],
            'store'        => ['admin', 'tendik', 'staf'],
            'update'       => ['admin', 'tendik', 'staf'],
            'delete'       => ['admin', 'tendik', 'staf'],
            'togglestatus' => ['admin', 'tendik', 'staf'],
            'reorder'      => ['admin', 'tendik', 'staf'],
        ],
        'pendaftaranwisudacontroller' => [
            '*'                           => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'index'                       => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'biodata'                     => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'biodata_wisuda'              => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'submit'                      => ['mahasiswa'],
            'submitbiodata'               => ['mahasiswa'],
            'biodatawisudasubmit'         => ['mahasiswa'],
            'indexbiodatatendik'          => ['admin', 'tendik', 'staf', 'fakultas'],
            'biodata_wisuda_verifikasi'   => ['admin', 'tendik', 'staf', 'fakultas'],
            'exportbiodataexcel'          => ['admin', 'tendik', 'staf', 'fakultas'],
            'biodatawisudaexport'         => ['admin', 'tendik', 'staf', 'fakultas'],
            'verifikasibiodata'           => ['admin', 'tendik', 'staf'],
            'biodatawisudaverifikasi'     => ['admin', 'tendik', 'staf'],
            'berkaswisuda'                => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'berkas_wisuda'               => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'submitberkas'                => ['mahasiswa'],
            'berkaswisudasubmit'          => ['mahasiswa'],
            'indexberkastendik'           => ['admin', 'tendik', 'staf', 'fakultas'],
            'berkas_wisuda_verifikasi'    => ['admin', 'tendik', 'staf', 'fakultas'],
            'verifikasiberkas'            => ['admin', 'tendik', 'staf'],
            'berkaswisudaverifikasi'      => ['admin', 'tendik', 'staf'],
            'pendaftaranwisudasubmit'     => ['mahasiswa'],
            'uploadberkas'                => ['mahasiswa'],
            'pendaftaranwisudauploadberkas' => ['mahasiswa'],
            'hapusberkas'                 => ['mahasiswa'],
            'pendaftaranwisudahapusberkas' => ['mahasiswa'],
            'verifikasi'                  => ['admin', 'tendik', 'staf'],
            'pendaftaranwisudaverifikasi' => ['admin', 'tendik', 'staf'],
            'approvewisudawan'            => ['admin', 'tendik', 'staf'],
            'batalwisudawan'              => ['admin', 'tendik', 'staf'],
            'previewberkas'               => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
            'previewfoto'                 => ['admin', 'tendik', 'staf', 'fakultas', 'mahasiswa'],
        ],

        'tracerstudicontroller' => [
            '*'                       => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'index'                   => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'daftarpertanyaan'        => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaan_tracer_studi' => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'storepertanyaan'            => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudistore' => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'simpantampilpertanyaan'     => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstuditampil' => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'simpanurutanpertanyaan'     => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudiurutan' => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'updatepertanyaan'            => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudiupdate' => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'deletepertanyaan'                => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudidelete'     => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'togglewajibpertanyaan'           => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstuditogglewajib' => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pratinjau'                       => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'submitpratinjau'                 => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pratinjaulanjut'                 => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'resetpratinjau'                  => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pertanyaantracerstudipratinjau'   => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'pantaupengisian'                 => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'detailjawaban'                   => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'tracer_studi_pantau'             => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'bankpertanyaan'                  => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'storebankpertanyaan'             => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'updatebankpertanyaan'            => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'deletebankpertanyaan'            => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'ambildaribank'                   => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'simpankebank'                    => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'nonaktifkankebank'               => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'bank_pertanyaan_tracer_studi'    => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
            'formmahasiswa'           => ['mahasiswa'],
            'submitmahasiswa'         => ['mahasiswa'],
            'tracer_studi'            => ['mahasiswa'],
            'tracerstudi'             => ['mahasiswa'],
            'tracerstudisubmit'       => ['mahasiswa'],
        ],
    ];

    /**
     * Controllers that are public or handle their own role checks.
     *
     * @var string[]
     */
    public $publicControllers = ['auth'];

    /**
     * Menu key => roles allowed to see that section.
     *
     * A menu key that is not listed is treated as a shared menu.
     *
     * @var array<string, string[]>
     */
    public $menuRoles = [
        'admin'                   => ['admin'],
        'admin_users'             => ['admin'],
        'fakultas_dashboard'      => ['fakultas', 'tendik'],
        'fakultas'                => ['fakultas'],
        'kaprodi_dashboard'       => ['kaprodi', 'prodi'],
        'kaprodi'                 => ['kaprodi', 'prodi'],
        'dosen_dashboard'         => ['dosen', 'dospem eksternal'],
        'dosen'                   => ['dosen'],
        'dospem'                  => ['dospem eksternal'],
        'mahasiswa'               => ['mahasiswa'],
        'alumni'                  => ['alumni'],
        'tendik'                  => ['tendik', 'admin'],
        'klien'                   => ['klien'],
        'pertanyaan_tracer_studi'      => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
        'bank_pertanyaan_tracer_studi' => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
        'tracer_studi_pantau'          => ['admin', 'tendik', 'staf', 'fakultas', 'kaprodi', 'prodi'],
    ];
}
