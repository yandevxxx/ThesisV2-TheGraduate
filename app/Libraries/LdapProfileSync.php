<?php

namespace App\Libraries;

use App\Models\AkunModel;
use App\Models\AksesModel;
use App\Models\AuthLogModel;
use App\Models\DosenModel;
use App\Models\MahasiswaModel;
use App\Models\RoleModel;
use App\Models\TenagaKependidikanModel;

/**
 * B-03: menyiapkan akun/profil lokal dari data LDAP dan menyinkronkan
 * perubahan profil di background.
 *
 * Kebijakan:
 * - Akun lokal dibuat otomatis hanya bila profil lokal sudah ada
 *   (mahasiswa/dosen hasil data akademik) atau profil tendik dapat dibuat
 *   dari nama + email LDAP.
 * - Bila data tidak cukup, login tetap berhasil diverifikasi LDAP tetapi
 *   pengguna diarahkan ke formulir pendaftaran (alur B-02).
 * - Perubahan nama/email lokal dijalankan lewat antrean `ldap_profile_sync`
 *   dan diproses oleh command `auth:sync-ldap-profiles`.
 */
class LdapProfileSync
{
    public const MAX_ATTEMPTS = 5;

    /** @var \CodeIgniter\Database\BaseConnection */
    private $db;

    /** @var AkunModel */
    private $akunModel;

    /** @var AksesModel */
    private $aksesModel;

    /** @var RoleModel */
    private $roleModel;

    /** @var MahasiswaModel */
    private $mahasiswaModel;

    /** @var DosenModel */
    private $dosenModel;

    /** @var TenagaKependidikanModel */
    private $tendikModel;

    /** @var int|null Profil tendik yang baru dibuat dan perlu dibersihkan bila provisioning gagal. */
    private $lastCreatedTendikId;

    public function __construct()
    {
        $this->db             = \Config\Database::connect();
        $this->akunModel      = new AkunModel();
        $this->aksesModel     = new AksesModel();
        $this->roleModel      = new RoleModel();
        $this->mahasiswaModel = new MahasiswaModel();
        $this->dosenModel     = new DosenModel();
        $this->tendikModel    = new TenagaKependidikanModel();
    }

    /**
     * Map the LDAP display role to the local role name.
     */
    public static function roleName(string $role): ?string
    {
        $map = [
            'Mahasiswa' => 'mahasiswa',
            'Dosen'     => 'dosen',
            'Staff'     => 'tendik',
        ];

        return isset($map[$role]) ? $map[$role] : null;
    }

    /**
     * Keep only the LDAP fields that are safe to persist in the queue.
     */
    public static function payloadFromLdap(array $ldapProfile): array
    {
        $keys = [
            'username',
            'displayname',
            'mail',
            'employee_number',
            'id_nik',
            'street',
            'phone',
            'homephone',
            'homepostaladdress',
            'role',
        ];

        $payload = [];

        foreach ($keys as $key) {
            if (isset($ldapProfile[$key]) && is_scalar($ldapProfile[$key])) {
                $payload[$key] = trim((string) $ldapProfile[$key]);
            }
        }

        return $payload;
    }

    /**
     * Create or locate the local account for a successful LDAP login.
     *
     * Returns null when local data is not sufficient; the caller then falls
     * back to the registration form.
     *
     * @return array|null ['akun' => array, 'profile' => array, 'roles' => array, 'created' => bool]
     */
    public function provision(array $ldapProfile, string $role): ?array
    {
        $username = isset($ldapProfile['username']) ? trim((string) $ldapProfile['username']) : '';
        $roleName = self::roleName($role);

        if ($username === '' || $roleName === null) {
            return null;
        }

        $this->lastCreatedTendikId = null;
        $createdAkunId             = null;

        try {
            $akun = $this->akunModel->where('username', $username)->first();

            /*
             * Tendik dapat dibuat otomatis, jadi pastikan email LDAP belum
             * dipakai akun lain sebelum baris profil tendik dibuat.
             */
            if ($akun === null && $roleName === 'tendik') {
                $candidateEmail = isset($ldapProfile['mail']) ? trim((string) $ldapProfile['mail']) : '';

                if ($candidateEmail !== '' && $this->findAccountByEmail($candidateEmail) !== null) {
                    $this->audit('failed', $username, null, 'email_conflict', 'Email sudah dipakai akun lain.');

                    return null;
                }
            }

            $profile = $this->resolveProfile($ldapProfile, $roleName);

            if ($profile === null) {
                $this->audit('failed', $username, null, 'profile_missing', 'Profil lokal belum tersedia.');
                $this->discardCreatedTendik();

                return null;
            }

            $email = $this->profileEmail($profile);

            if ($email === '' || strlen($email) > 150) {
                $this->audit('failed', $username, null, 'email_missing', 'Email LDAP/profil tidak valid.');
                $this->discardCreatedTendik();

                return null;
            }

            $profileOwner = $this->findProfileByEmail($roleName, $email);

            if ($profileOwner !== null && ! $this->sameProfile($roleName, $profileOwner, $profile)) {
                $this->audit('failed', $username, null, 'profile_email_conflict', 'Email dipakai lebih dari satu profil.');
                $this->discardCreatedTendik();

                return null;
            }

            if ($akun !== null && strcasecmp((string) $akun['email'], $email) !== 0) {
                $this->audit('failed', $username, $akun['id'], 'account_conflict', 'Akun dengan username ini memakai email berbeda.');
                $this->discardCreatedTendik();

                return null;
            }

            $created = false;

            if ($akun === null) {
                $emailOwner = $this->findAccountByEmail($email);

                if ($emailOwner !== null) {
                    $this->audit('failed', $username, $emailOwner['id'], 'email_conflict', 'Email sudah dipakai akun lain.');
                    $this->discardCreatedTendik();

                    return null;
                }

                $this->akunModel->insert([
                    'username' => $username,
                    'email'    => $email,
                    'status'   => 'ACTIVE',
                ]);

                $createdAkunId = (int) $this->db->insertID();

                if ($createdAkunId <= 0) {
                    $this->audit('failed', $username, null, 'account_create_failed', 'Gagal membuat akun lokal.');
                    $this->discardCreatedTendik();

                    return null;
                }

                $akun   = $this->akunModel->find($createdAkunId);
                $created = true;

                if ($akun === null) {
                    $this->discardCreatedTendik();

                    return null;
                }
            }

            $roles = $this->accountRoleNames($akun['id']);

            if ($roles === []) {
                if (! $this->assignRole($akun['id'], $roleName)) {
                    if ($created) {
                        $this->akunModel->delete($akun['id']);
                    }

                    $this->discardCreatedTendik();

                    return null;
                }

                $roles = [$roleName];
            }

            $this->audit('success', $username, $akun['id'], $created ? 'auto_provision' : 'account_linked');

            return [
                'akun'    => $akun,
                'profile' => $profile,
                'roles'   => $roles,
                'created' => $created,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'LDAP provisioning gagal: ' . $e->getMessage());

            if ($createdAkunId !== null && $this->accountRoleNames($createdAkunId) === []) {
                try {
                    $this->akunModel->delete($createdAkunId);
                } catch (\Throwable $cleanupError) {
                    log_message('error', 'Gagal membersihkan akun provisioning: ' . $cleanupError->getMessage());
                }
            }

            $this->discardCreatedTendik();
            $this->audit('failed', $username, null, 'provision_error', $e->getMessage());

            return null;
        }
    }

    /**
     * Queue a background profile refresh. Never throws: login must not fail
     * because the queue is unavailable.
     */
    public function enqueue(array $ldapProfile, $akunId, string $role): void
    {
        try {
            $akunId = (int) $akunId;

            if ($akunId <= 0) {
                return;
            }

            $payload = json_encode(self::payloadFromLdap($ldapProfile));

            if ($payload === false) {
                return;
            }

            $now      = date('Y-m-d H:i:s');
            $existing = $this->db->table('ldap_profile_sync')
                ->where('akun_id', $akunId)
                ->where('status', 'pending')
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            if ($existing !== null) {
                $this->db->table('ldap_profile_sync')->where('id', $existing['id'])->update([
                    'role'         => $role,
                    'payload'      => $payload,
                    'available_at' => $now,
                    'last_error'   => null,
                    'updated_at'   => $now,
                ]);

                return;
            }

            $this->db->table('ldap_profile_sync')->insert([
                'akun_id'      => $akunId,
                'username'     => substr((string) ($ldapProfile['username'] ?? ''), 0, 150),
                'role'         => $role,
                'payload'      => $payload,
                'status'       => 'pending',
                'attempts'     => 0,
                'last_error'   => null,
                'available_at' => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membuat antrean sinkronisasi LDAP: ' . $e->getMessage());
        }
    }

    /**
     * Process pending queue rows. Used by the cron command.
     *
     * @return array ['processed' => int, 'succeeded' => int, 'failed' => int, 'skipped' => int]
     */
    public function processPending(int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));
        $now   = date('Y-m-d H:i:s');

        $stats = [
            'processed' => 0,
            'succeeded' => 0,
            'failed'    => 0,
            'skipped'   => 0,
        ];

        /*
         * Kembalikan baris yang tertinggal di status processing (mis. proses
         * CLI terhenti) agar tidak menggantung selamanya.
         */
        $this->db->table('ldap_profile_sync')
            ->where('status', 'processing')
            ->where('updated_at <', date('Y-m-d H:i:s', time() - 900))
            ->update([
                'status'       => 'pending',
                'available_at' => $now,
                'updated_at'   => $now,
            ]);

        $rows = $this->db->table('ldap_profile_sync')
            ->where('status', 'pending')
            ->groupStart()
                ->where('available_at', null)
                ->orWhere('available_at <=', $now)
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $updated = $this->db->table('ldap_profile_sync')
                ->where('id', $row['id'])
                ->where('status', 'pending')
                ->update([
                    'status'     => 'processing',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            if (! $updated || $this->db->affectedRows() === 0) {
                $stats['skipped']++;

                continue;
            }

            $stats['processed']++;

            try {
                $this->processRow($row);

                $this->db->table('ldap_profile_sync')->where('id', $row['id'])->update([
                    'status'     => 'done',
                    'last_error' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $this->audit('success', $row['username'], $row['akun_id'], 'profile_synced');
                $stats['succeeded']++;
            } catch (\Throwable $e) {
                $attempts = (int) $row['attempts'] + 1;
                $failed   = $attempts >= self::MAX_ATTEMPTS;

                $this->db->table('ldap_profile_sync')->where('id', $row['id'])->update([
                    'status'       => $failed ? 'failed' : 'pending',
                    'attempts'     => $attempts,
                    'last_error'   => substr($e->getMessage(), 0, 255),
                    'available_at' => date('Y-m-d H:i:s', time() + (60 * $attempts)),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

                $this->audit('failed', $row['username'], $row['akun_id'], 'sync_error', $e->getMessage());
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * @return array|null
     */
    private function resolveProfile(array $ldapProfile, string $roleName)
    {
        if ($roleName === 'mahasiswa') {
            return $this->findMahasiswa($ldapProfile);
        }

        if ($roleName === 'dosen') {
            return $this->findDosen($ldapProfile);
        }

        if ($roleName === 'tendik') {
            return $this->findOrCreateTendik($ldapProfile);
        }

        return null;
    }

    /**
     * @return array|null
     */
    private function findMahasiswa(array $ldapProfile)
    {
        $nid   = isset($ldapProfile['id_nik']) ? trim((string) $ldapProfile['id_nik']) : '';
        $email = isset($ldapProfile['mail']) ? trim((string) $ldapProfile['mail']) : '';

        if (preg_match('/^[0-9]{1,10}$/', $nid) === 1) {
            $mahasiswa = $this->mahasiswaModel->find($nid);

            if ($mahasiswa !== null) {
                return $mahasiswa;
            }
        }

        if ($email === '' || strlen($email) > 70) {
            return null;
        }

        return $this->findByEmail('mahasiswa', $email);
    }

    /**
     * @return array|null
     */
    private function findDosen(array $ldapProfile)
    {
        $email = isset($ldapProfile['mail']) ? trim((string) $ldapProfile['mail']) : '';

        if ($email === '' || strlen($email) > 150) {
            return null;
        }

        return $this->findByEmail('dosen', $email);
    }

    /**
     * Tendik hanya membutuhkan nama + email, sehingga profil baru boleh
     * dibuat otomatis. Bila nama sudah dipakai lebih dari satu baris,
     * provisioning dibatalkan agar tidak salah taut.
     *
     * @return array|null
     */
    private function findOrCreateTendik(array $ldapProfile)
    {
        $email = isset($ldapProfile['mail']) ? trim((string) $ldapProfile['mail']) : '';
        $nama  = isset($ldapProfile['displayname']) ? trim((string) $ldapProfile['displayname']) : '';

        if ($nama === '' && isset($ldapProfile['cn'])) {
            $nama = trim((string) $ldapProfile['cn']);
        }

        if ($nama === '') {
            $nama = isset($ldapProfile['username']) ? trim((string) $ldapProfile['username']) : '';
        }

        if ($email === '' || $nama === '' || strlen($email) > 150) {
            return null;
        }

        $existing = $this->findByEmail('tenaga_kependidikan', $email);

        if ($existing !== null) {
            return $existing;
        }

        $sameName = $this->db->query(
            'SELECT * FROM ' . $this->db->prefixTable('tenaga_kependidikan') . ' WHERE LOWER(nama) = ? LIMIT 2',
            [strtolower($nama)]
        )->getResultArray();

        if (count($sameName) === 1) {
            return $sameName[0];
        }

        if (count($sameName) > 1) {
            return null;
        }

        $this->tendikModel->insert([
            'nama'  => $nama,
            'email' => $email,
        ]);

        if ($this->db->affectedRows() === 0) {
            return null;
        }

        $id = (int) $this->db->insertID();

        if ($id <= 0) {
            return null;
        }

        $this->lastCreatedTendikId = $id;

        return $this->tendikModel->find($id);
    }

    /**
     * Remove a tendik profile created during a provisioning attempt that
     * failed before an account was linked to it.
     */
    private function discardCreatedTendik(): void
    {
        if ($this->lastCreatedTendikId === null) {
            return;
        }

        try {
            $this->db->table('tenaga_kependidikan')->where('id', $this->lastCreatedTendikId)->delete();
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membersihkan profil tendik provisioning: ' . $e->getMessage());
        }

        $this->lastCreatedTendikId = null;
    }

    /**
     * @return array|null
     */
    private function findByEmail(string $table, string $email)
    {
        $sql = 'SELECT * FROM ' . $this->db->prefixTable($table) . ' WHERE LOWER(email) = ? LIMIT 1';

        return $this->db->query($sql, [strtolower($email)])->getRowArray();
    }

    /**
     * @return array|null
     */
    private function findAccountByEmail(string $email)
    {
        $sql = 'SELECT * FROM ' . $this->db->prefixTable('akun') . ' WHERE LOWER(email) = ? LIMIT 1';

        return $this->db->query($sql, [strtolower($email)])->getRowArray();
    }

    /**
     * @return string[]
     */
    private function accountRoleNames($akunId): array
    {
        $roles = [];

        foreach ($this->aksesModel->getRoles($akunId) as $row) {
            if (! empty($row['nama'])) {
                $roles[] = strtolower($row['nama']);
            }
        }

        return array_values(array_unique($roles));
    }

    private function assignRole($akunId, string $roleName): bool
    {
        $role = $this->roleModel->where('nama', $roleName)->first();

        if ($role === null) {
            $this->audit('failed', '', $akunId, 'role_missing', 'Role lokal tidak ditemukan.');

            return false;
        }

        $exists = $this->db->table('akses')
            ->where('id_akun', $akunId)
            ->where('id_role', $role['id'])
            ->countAllResults();

        if ($exists > 0) {
            return true;
        }

        $this->aksesModel->insert([
            'id_akun' => $akunId,
            'id_role' => $role['id'],
        ]);

        $inserted = $this->db->affectedRows() > 0;
        if ($inserted) {
            SecurityAuditLogger::logRoleChange(
                'akun#' . $akunId,
                'none',
                $roleName,
                'ldap_profile_sync',
                'success',
                null,
                ['akun_id' => $akunId, 'auth_method' => 'ldap']
            );
        }

        return $inserted;
    }

    /**
     * Apply one queued profile refresh atomically.
     */
    private function processRow(array $row): void
    {
        $this->db->transBegin();

        try {
            $this->applyRow($row);
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * Apply one queued profile refresh.
     */
    private function applyRow(array $row): void
    {
        $payload = json_decode((string) $row['payload'], true);

        if (! is_array($payload)) {
            throw new \RuntimeException('Payload antrean tidak valid.');
        }

        $akun = $this->akunModel->find($row['akun_id']);

        if ($akun === null) {
            throw new \RuntimeException('Akun lokal tidak ditemukan.');
        }

        $roleName = self::roleName((string) $row['role']);

        if ($roleName === null) {
            foreach (['mahasiswa', 'dosen', 'tendik'] as $candidate) {
                if (in_array($candidate, $this->accountRoleNames($akun['id']), true)) {
                    $roleName = $candidate;
                    break;
                }
            }
        }

        if ($roleName === null) {
            throw new \RuntimeException('Role akun tidak dikenali.');
        }

        $profile = $this->profileForAccount($akun, $roleName, $payload);

        if ($profile === null) {
            throw new \RuntimeException('Profil lokal tidak ditemukan.');
        }

        $email = isset($payload['mail']) ? trim((string) $payload['mail']) : '';
        $nama  = isset($payload['displayname']) ? trim((string) $payload['displayname']) : '';

        $maxEmailLength = $roleName === 'mahasiswa' ? 70 : 150;

        if ($email !== '' && strlen($email) <= $maxEmailLength) {
            $owner = $this->findAccountByEmail($email);

            if ($owner !== null && (int) $owner['id'] !== (int) $akun['id']) {
                throw new \RuntimeException('Email sudah dipakai akun lain.');
            }

            $profileOwner = $this->findProfileByEmail($roleName, $email);

            if ($profileOwner !== null && ! $this->sameProfile($roleName, $profileOwner, $profile)) {
                throw new \RuntimeException('Email sudah dipakai profil lain.');
            }

            if (strcasecmp((string) $akun['email'], $email) !== 0) {
                $this->akunModel->update($akun['id'], ['email' => $email]);
            }
        }

        $profileUpdate = [];

        if ($nama !== '' && $nama !== (string) $profile['nama']) {
            $profileUpdate['nama'] = $nama;
        }

        if (
            $email !== ''
            && strlen($email) <= $maxEmailLength
            && strcasecmp((string) $profile['email'], $email) !== 0
        ) {
            $profileUpdate['email'] = $email;
        }

        if ($profileUpdate !== []) {
            $this->updateProfile($roleName, $profile, $profileUpdate);
        }
    }

    /**
     * @return array|null
     */
    private function profileForAccount(array $akun, string $roleName, array $payload)
    {
        if ($roleName === 'mahasiswa') {
            $profile = $this->findByEmail('mahasiswa', (string) $akun['email']);

            if ($profile !== null) {
                return $profile;
            }

            $nid = isset($payload['id_nik']) ? trim((string) $payload['id_nik']) : '';

            if (preg_match('/^[0-9]{1,10}$/', $nid) === 1) {
                return $this->mahasiswaModel->find($nid);
            }

            return null;
        }

        if ($roleName === 'dosen') {
            return $this->findByEmail('dosen', (string) $akun['email']);
        }

        return $this->findByEmail('tenaga_kependidikan', (string) $akun['email']);
    }

    /**
     * @return array|null
     */
    private function findProfileByEmail(string $roleName, string $email)
    {
        $table = $this->profileTable($roleName);

        return $table === null ? null : $this->findByEmail($table, $email);
    }

    private function updateProfile(string $roleName, array $profile, array $data): void
    {
        if ($roleName === 'mahasiswa') {
            $this->db->table('mahasiswa')->where('npm', $profile['npm'])->update($data);

            return;
        }

        $table = $this->profileTable($roleName);

        if ($table !== null) {
            $this->db->table($table)->where('id', $profile['id'])->update($data);
        }
    }

    private function sameProfile(string $roleName, array $first, array $second): bool
    {
        if ($roleName === 'mahasiswa') {
            return (string) $first['npm'] === (string) $second['npm'];
        }

        return (int) $first['id'] === (int) $second['id'];
    }

    private function profileTable(string $roleName): ?string
    {
        $map = [
            'mahasiswa' => 'mahasiswa',
            'dosen'     => 'dosen',
            'tendik'    => 'tenaga_kependidikan',
        ];

        return isset($map[$roleName]) ? $map[$roleName] : null;
    }

    private function profileEmail(array $profile): string
    {
        return isset($profile['email']) ? trim((string) $profile['email']) : '';
    }

    private function audit($status, $username, $akunId, $reason, $message = ''): void
    {
        try {
            $cleanUser    = substr(SecurityLogSanitizer::sanitizeText((string) $username), 0, 100);
            $cleanMessage = SecurityLogSanitizer::sanitizeText((string) $message);
            $cleanReason  = SecurityLogSanitizer::sanitizeText((string) $reason);

            $model = new AuthLogModel();
            $model->insert([
                'username'    => $cleanUser,
                'akun_id'     => $akunId,
                'event'       => 'profile_sync',
                'status'      => substr(SecurityLogSanitizer::sanitizeText((string) $status), 0, 30),
                'auth_method' => 'ldap',
                'roles'       => null,
                'reason'      => substr($cleanReason . ($cleanMessage !== '' ? ': ' . $cleanMessage : ''), 0, 100),
                'ip_address'  => null,
                'user_agent'  => 'ldap-profile-sync',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menulis audit sinkronisasi LDAP: ' . SecurityLogSanitizer::sanitizeText($e->getMessage()));
        }
    }
}
