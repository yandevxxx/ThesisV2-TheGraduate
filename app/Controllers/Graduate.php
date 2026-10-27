<?php

namespace App\Controllers;

use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;

/**
 * Controller Sisi "The Graduate"
 *
 * Menangani:
 * - Halaman pilihan (gateway) Thesis / Graduate setelah login: graduate/choose
 * - Pindah sisi: graduate/enter/{thesis|graduate}
 * - Dashboard per role: graduate/{admin_tendik|prodi|wisudawan|dosen|mahasiswa}
 */
class Graduate extends BaseController
{
    /**
     * Pintu masuk sisi Graduate. Semua role diarahkan ke folder dashboard-nya.
     */
    public function index()
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);

        if (! PortalRouter::canUseGraduate($roles)) {
            return redirect()->to(base_url(PortalRouter::thesisLanding($roles)))
                ->with('error', 'The Graduate belum tersedia untuk peran Anda.');
        }

        session()->set('active_side', 'graduate');

        return redirect()->to(base_url(PortalRouter::graduateLanding($roles)));
    }

    /* ------------------------------------------------------------------
     |  Dashboard per folder role
     | ------------------------------------------------------------------ */

    public function admin_tendik()
    {
        return $this->dashboard('admin_tendik', 'The Graduate');
    }

    public function prodi()
    {
        return $this->dashboard('prodi', 'Dasbor Program Studi');
    }

    public function wisudawan()
    {
        return $this->dashboard('wisudawan', 'Dasbor Wisudawan');
    }

    public function dosen()
    {
        return $this->dashboard('dosen', 'The Graduate');
    }

    public function mahasiswa()
    {
        return $this->dashboard('mahasiswa', 'The Graduate');
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
     * Halaman pilihan: Thesis atau The Graduate.
     * Semua user yang sudah login boleh melihat chooser; kartu Graduate
     * disembunyikan untuk role yang belum memiliki akses.
     */
    public function choose()
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);

        return view('graduate/choose', [
            'user'             => $userData,
            'roles'            => $roles,
            'roleLabel'        => PortalRouter::roleLabel($roles),
            'canUseGraduate'   => PortalRouter::canUseGraduate($roles),
            'thesisLanding'    => PortalRouter::thesisLanding($roles),
            'graduateLanding'  => PortalRouter::graduateLanding($roles),
        ]);
    }

    /**
     * Menentukan sisi yang dipilih: /graduate/enter/thesis | /graduate/enter/graduate
     */
    public function enter($sisi = 'thesis')
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);

        if ($sisi === 'graduate') {
            if (! PortalRouter::canUseGraduate($roles)) {
                return redirect()->to(base_url(PortalRouter::thesisLanding($roles)))
                    ->with('error', 'The Graduate belum tersedia untuk peran Anda.');
            }

            session()->set('active_side', 'graduate');

            return redirect()->to(base_url(PortalRouter::graduateLanding($roles)));
        }

        session()->set('active_side', 'thesis');

        return redirect()->to(base_url(PortalRouter::thesisLanding($roles)));
    }

    /**
     * Render dashboard sesuai folder milik role user.
     * Role yang mencoba membuka folder role lain dikembalikan ke dashboard-nya.
     */
    private function dashboard($folder, $title)
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);

        if (! PortalRouter::canUseGraduate($roles)) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if (PortalRouter::graduateFolder($roles) !== $folder) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        session()->set('active_side', 'graduate');

        $view = "graduate/{$folder}/dashboard";

        if (! is_file(APPPATH . "Views/{$view}.php")) {
            $view = 'graduate/dashboard';
        }

        return view($view, [
            'title' => $title,
            'user'  => $userData,
            'role'  => $roles,
        ]);
    }

    /**
     * Render halaman menu sisi The Graduate sesuai folder role.
     * Contoh: role admin/tendik + halaman "berkas" -> view graduate/admin_tendik/berkas
     */
    private function halamanMenu($halaman, $judul)
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);

        if (! PortalRouter::canUseGraduate($roles)) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $folder = PortalRouter::graduateFolder($roles);

        if ($folder === null) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        // Halaman belum tersedia untuk role ini -> kembali ke dashboard role.
        if (! is_file(APPPATH . "Views/graduate/{$folder}/{$halaman}.php")) {
            return redirect()->to(base_url(PortalRouter::graduateLanding($roles)));
        }

        session()->set('active_side', 'graduate');

        return view("graduate/{$folder}/{$halaman}", [
            'title' => $judul,
            'user'  => $userData,
            'role'  => $roles,
        ]);
    }
}
