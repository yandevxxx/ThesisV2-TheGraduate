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
    private $allowedRoles = ['admin', 'tendik', 'dosen', 'mahasiswa'];

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

        // Pemetaan role -> view dashboard (prioritas: admin > tendik > dosen > mahasiswa)
        $map = [
            'admin'     => 'graduate/admin/dashboard',
            'tendik'    => 'graduate/tendik/dashboard',
            'dosen'     => 'graduate/dosen/dashboard',
            'mahasiswa' => 'graduate/mahasiswa/dashboard',
        ];

        // Default/cadangan bila role tidak termasuk di atas
        $view = 'graduate/dashboard';

        foreach ($map as $role => $viewPath) {
            if (in_array($role, $roles)) {
                $view = $viewPath;
                break;
            }
        }

        return view($view, [
            'title' => 'The Graduate',
            'user' => $userData,
            'role' => $roles,
        ]);
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
