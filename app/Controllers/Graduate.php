<?php

namespace App\Controllers;

/**
 * Controller Sisi "The Graduate"
 *
 * Menangani:
 * - Halaman pilihan (gateway) Thesis / Graduate setelah login: graduate/choose
 * - Pindah sisi: graduate/enter/{thesis|graduate}
 * - Dashboard placeholder sisi Graduate: graduate/index
 */
class Graduate extends BaseController
{
    /**
     * Role yang diberi pilihan masuk sisi Thesis / Graduate.
     * Role lain (fakultas, kaprodi, klien, tamu, dospem eksternal) tetap langsung ke Thesis.
     */
    private $allowedRoles = ['admin', 'tendik', 'dosen', 'mahasiswa', 'fakultas'];

    /**
     * Dashboard sisi The Graduate (placeholder — modul menyusul).
     */
    public function index()
    {
        $userData = session()->get('user_session');

        if (!$userData) {
            return redirect()->to(base_url('auth'))->with('error', 'Silakan login terlebih dahulu.');
        }

        // Tandai sisi aktif
        session()->set('active_side', 'graduate');

        $roles = $this->getUserRoles($userData);

        // Folder view per role (admin & tendik berbagi folder admin_tendik)
        $folder = $this->roleFolder($roles);

        // Default/cadangan bila role di luar 4 role utama
        $view = $folder ? "graduate/{$folder}/dashboard" : 'graduate/dashboard';

        return view($view, [
            'title' => 'The Graduate',
            'user' => $userData,
            'role' => $roles,
        ]);
    }

    public function berkas()
    {
        return $this->halamanMenu('berkas', 'Berkas');
    }

    public function semester()
    {
        return $this->halamanMenu('semester', 'Semester');
    }

    public function pendaftaran_wisuda()
    {
        return $this->halamanMenu('pendaftaran_wisuda', 'Pendaftaran Wisuda');
    }

    public function skl()
    {
        return $this->halamanMenu('skl', 'SKL');
    }

    public function sk_yudisium()
    {
        return $this->halamanMenu('sk_yudisium', 'SK Yudisium');
    }

    public function pengambilan_ijazah()
    {
        return $this->halamanMenu('pengambilan_ijazah', 'Pengambilan Ijazah');
    }

    public function calon_wisudawan()
    {
        return $this->halamanMenu('calon_wisudawan', 'Calon Wisudawan');
    }

    public function mahasiswa_wisudawan()
    {
        return $this->halamanMenu('mahasiswa_wisudawan', 'Mahasiswa Wisudawan');
    }

    public function dosen_pembimbing()
    {
        return $this->halamanMenu('dosen_pembimbing', 'Dosen Pembimbing');
    }

    public function persyaratan()
    {
        return $this->halamanMenu('persyaratan_kelulusan', 'Persyaratan Kelulusan');
    }

    /**
     * Render halaman menu sisi The Graduate sesuai folder role.
     * Contoh: role admin/tendik + halaman "berkas" -> view graduate/admin_tendik/berkas
     */
    private function halamanMenu($halaman, $judul)
    {
        $userData = session()->get('user_session');

        if (!$userData) {
            return redirect()->to(base_url('auth'))->with('error', 'Silakan login terlebih dahulu.');
        }

        session()->set('active_side', 'graduate');

        $roles = $this->getUserRoles($userData);
        $folder = $this->roleFolder($roles);

        // Role di luar 4 role utama (folder tidak tersedia) -> kembali ke dashboard
        if ($folder === null) {
            return redirect()->to(base_url('graduate/index'));
        }

        // Halaman belum tersedia untuk role ini (mis. menu dosen/mahasiswa belum dibuat)
        // -> kembali ke dashboard, bukan halaman error
        if (! is_file(APPPATH . "Views/graduate/{$folder}/{$halaman}.php")) {
            return redirect()->to(base_url('graduate/index'));
        }

        return view("graduate/{$folder}/{$halaman}", [
            'title' => $judul,
            'user' => $userData,
            'role' => $roles,
        ]);
    }

    /**
     * Folder view per role.
     * admin & tendik memakai folder yang sama (admin_tendik) karena isinya identik.
     * Mengembalikan null bila role tidak termasuk 4 role utama tersebut.
     */
    private function roleFolder(array $roles)
    {
        if (in_array('admin', $roles) || in_array('tendik', $roles) || in_array('fakultas', $roles)) {
            return 'admin_tendik';
        }

        if (in_array('dosen', $roles) || in_array('dospem eksternal', $roles)) {
            return 'dosen';
        }

        if (in_array('mahasiswa', $roles)) {
            return 'mahasiswa';
        }

        return null;
    }

    /**
     * Halaman pilihan: Thesis atau The Graduate.
     */
    public function choose()
    {
        $userData = session()->get('user_session');

        // 1. Harus login
        if (!$userData) {
            return redirect()->to(base_url('auth'))->with('error', 'Silakan login terlebih dahulu.');
        }

        // 2. Hanya role tertentu yang diberi pilihan; selain itu langsung ke sisi Thesis
        $roles = $this->getUserRoles($userData);
        if (!array_intersect($this->allowedRoles, $roles)) {
            return redirect()->to(base_url('home'));
        }

        return view('graduate/choose', [
            'user' => $userData,
        ]);
    }

    /**
     * Menentukan sisi yang dipilih: /graduate/enter/thesis | /graduate/enter/graduate
     */
    public function enter($sisi = 'thesis')
    {
        $userData = session()->get('user_session');

        if (!$userData) {
            return redirect()->to(base_url('auth'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = $this->getUserRoles($userData);

        // ---- Sisi Graduate ----
        if ($sisi === 'graduate') {
            if (!array_intersect($this->allowedRoles, $roles)) {
                return redirect()->to(base_url('home'));
            }
            return redirect()->to(base_url('graduate/index'));
        }

        // ---- Sisi Thesis (pemetaan role sama seperti setelah login di Auth) ----
        session()->set('active_side', 'thesis');

        if (in_array('admin', $roles)) {
            return redirect()->to(base_url('admin/dashboard'));
        }

        if (in_array('tendik', $roles)) {
            return redirect()->to(base_url('fakultas/dashboard'));
        }

        if (in_array('kaprodi', $roles)) {
            return redirect()->to(base_url('kaprodi/dashboard'));
        }

        if (in_array('fakultas', $roles)) {
            return redirect()->to(base_url('fakultas/dashboard'));
        }

        if (in_array('dosen', $roles) || in_array('dospem eksternal', $roles)) {
            return redirect()->to(base_url('dosen/index'));
        }

        // Mahasiswa, tamu, dll -> Repositori (home)
        return redirect()->to(base_url('home'));
    }

    /**
     * Ambil daftar role user dalam huruf kecil dari session.
     */
    private function getUserRoles(array $userData): array
    {
        if (isset($userData['roles']) && is_array($userData['roles'])) {
            return array_map('strtolower', $userData['roles']);
        }

        if (isset($userData['role'])) {
            return [strtolower($userData['role'])];
        }

        return [];
    }
}
