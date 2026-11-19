<?php 

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Libraries\SecurityAuditLogger;
use App\Libraries\SecurityExceptions;
use App\Models\AkunModel;

class Admin extends BaseController
{
    protected $akunModel;

    public function __construct()
    {
        $this->akunModel = new AkunModel();
    }
    
    // Helper Cek Admin
    private function isAdmin()
    {
        $session = session()->get('user_session');
        return ($session && isset($session['role']) && $session['role'] === 'Admin');
    }

    // 1. DASHBOARD
    public function dashboard()
    {
        $session = session()->get('user_session');
        $roles = $session['roles'] ?? []; // Ambil daftar role user

        // Allow Admin and Tendik to view Admin dashboard. Other roles are redirected to home.
        if (!in_array('tendik', $roles) && !in_array('admin', $roles)) {
            return redirect()->to(base_url('home')); 
        }

        $data = [
            'title'      => 'Dashboard',
            'total_user' => $this->akunModel->countAllResults(),
            'username'   => $session['username'] ?? 'User'
        ];
        
        return view('admin/dashboard', $data);
    }

    // 2. HALAMAN KELOLA PENGGUNA
    public function manageUsers()
    {
        if (!$this->isAdmin()) return redirect()->to(base_url('auth/login'));

        $users = $this->akunModel->findAll();

        $data = [
            'title' => 'Kelola Pengguna',
            'users' => $users
        ];

        return view('admin/manage_users', $data);
    }

    // 3. PROSES GANTI ROLE (INI YANG SAYA PERBAIKI)
    public function changeRole()
    {
        $session = session()->get('user_session');
        if (! $session) {
            return redirect()->to(base_url('auth/login'));
        }

        $roles = PortalRouter::normalizeRoles($session);
        if (! $this->isAdmin() && ! AccessControl::hasRole($roles, 'admin')) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak: hanya Admin yang dapat mengubah peran.', $roles);
        }

        $id        = (int) $this->request->getPost('id');
        $roleInput = trim((string) $this->request->getPost('role'));

        $roleMap = [
            'Admin'     => 8,
            'Dosen'     => 2,
            'Mahasiswa' => 1,
            'Tendik'    => 4,
            'Kaprodi'   => 3,
            'Klien'     => 7,
            'Fakultas'  => 5,
            'Tamu'      => 99,
        ];

        if ($id <= 0 || ! isset($roleMap[$roleInput])) {
            return redirect()->back()->with('message', [
                'icon'  => 'error',
                'title' => 'Input Tidak Valid',
                'text'  => 'ID akun atau peran tujuan tidak valid.',
            ]);
        }

        $id_role_baru = $roleMap[$roleInput];
        $actor        = (string) ($session['username'] ?? $session['email'] ?? 'admin');

        try {
            $targetAkun = $this->akunModel->find($id);
            if (! $targetAkun) {
                return redirect()->back()->with('message', [
                    'icon'  => 'error',
                    'title' => 'Gagal',
                    'text'  => 'Akun pengguna tidak ditemukan.',
                ]);
            }

            $oldRoleName = (string) ($targetAkun['role'] ?? ('role_id:' . ($targetAkun['id_role'] ?? '-')));

            $this->akunModel->update($id, [
                'id_role' => $id_role_baru,
            ]);

            SecurityAuditLogger::logRoleChange(
                (string) ($targetAkun['username'] ?? ('akun#' . $id)),
                $oldRoleName,
                $roleInput,
                $actor,
                'success',
                $this->request,
                ['akun_id' => $id]
            );
        } catch (\Throwable $e) {
            $eventId = SecurityExceptions::report($e, 'admin.changeRole', $this->request, ['target_akun_id' => $id]);

            return redirect()->back()->with('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => SecurityExceptions::publicErrorMessage($eventId, 'Gagal memperbarui peran pengguna.'),
            ]);
        }

        return redirect()->back()->with('message', [
            "icon" => "success",
            "title" => "Berhasil",
            "text" => "Role pengguna berhasil diubah menjadi " . $roleInput
        ]);
    }

    // 4. PROSES FORCE RESET PASSWORD
    public function forceResetPassword()
    {
        if (!$this->isAdmin()) {
            return redirect()->to(base_url('auth/login'))->with('message', [
                "icon" => "error",
                "title" => "Akses Ditolak",
                "text" => "Hanya Admin yang boleh melakukan ini."
            ]);
        }

        $id          = $this->request->getPost('id');
        $newPass     = $this->request->getPost('password');
        $confirmPass = $this->request->getPost('confirm_password');

        if ($newPass !== $confirmPass) {
            return redirect()->back()->with('message', [
                "icon" => "error",
                "title" => "Gagal",
                "text" => "Konfirmasi password tidak sama."
            ]);
        }

        $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);
        
        $this->akunModel->update($id, ['password' => $hashedPassword]);

        return redirect()->back()->with('message', [
            "icon" => "success",
            "title" => "Berhasil",
            "text" => "Password pengguna ID " . $id . " berhasil direset paksa."
        ]);
    }
 
}