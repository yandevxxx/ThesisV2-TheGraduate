<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Models\SemesterModel;

/**
 * Controller Pengelolaan Semester Sisi The Graduate
 *
 * Menangani seluruh operasional CRUD dan aktivasi periode akademik semester.
 * Hanya dapat diakses oleh role 'fakultas' dan 'admin'.
 */
class SemesterController extends BaseController
{
    /**
     * Memastikan hanya role fakultas dan admin yang dapat mengelola semester.
     */
    private function ensureSemesterManager(): ?array
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return null;
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! PortalRouter::canUseGraduate($roles)) {
            return null;
        }

        if (! AccessControl::hasAnyRole($roles, ['fakultas', 'admin'])) {
            return null;
        }

        return [
            'user'   => $userData,
            'roles'  => $roles,
            'akunId' => $userData['akun_id'] ?? $userData['id'] ?? null,
        ];
    }

    /**
     * Halaman daftar semester dengan pagination dan status aktif.
     */
    public function index()
    {
        $auth = $this->ensureSemesterManager();
        if ($auth === null) {
            $userData = session()->get('user_session');
            $roles    = $userData ? PortalRouter::normalizeRoles($userData) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $semesterModel = new SemesterModel();
        // Urutkan berdasarkan periode/kode semester (terbaru ke terlama) agar posisi baris stabil dan tidak melompat saat diaktifkan
        $perPage        = 10;
        $daftarSemester = $semesterModel->orderBy("kode", "DESC")->paginate($perPage, 'semester');
        $pager          = $semesterModel->pager;
        $totalRows      = $pager->getTotal('semester');
        $currentPage    = $pager->getCurrentPage('semester');

        session()->set('active_side', 'graduate');

        return view('The-Graduate-Web/fakultas/semester', [
            'title'          => 'Semester',
            'user'           => $auth['user'],
            'role'           => $auth['roles'],
            'daftarSemester' => $daftarSemester,
            'semester'       => $daftarSemester,
            'pager'          => $pager,
            'perPage'        => $perPage,
            'totalRows'      => $totalRows,
            'currentPage'    => $currentPage,
        ]);
    }

    /**
     * Tambah data semester baru.
     */
    public function store()
    {
        $auth = $this->ensureSemesterManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/semester'));
        }

        $rawTahun = trim((string) $this->request->getPost('tahunAkademik'));
        $rawSem   = trim((string) $this->request->getPost('semester'));

        if ($rawTahun === '' || $rawSem === '') {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Tahun akademik dan semester wajib diisi!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        if (preg_match('/(\d{4})/', $rawTahun, $matches)) {
            $tahunMulai = (int) $matches[1];
        } else {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Format tahun akademik tidak valid (contoh: 2025/2026)!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        $tahunSelesai = $tahunMulai + 1;
        $semCode      = (strtolower($rawSem) === 'ganjil' || $rawSem === '1') ? 1 : 2;
        $semName      = ($semCode === 1) ? 'Ganjil' : 'Genap';
        $kodeSemester = "{$tahunMulai}-{$semCode}";
        $namaSemester = "Tahun Akademik {$tahunMulai}/{$tahunSelesai} Semester {$semName}";

        $semesterModel = new SemesterModel();
        if ($semesterModel->find($kodeSemester)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => "Semester {$namaSemester} ({$kodeSemester}) sudah ada!",
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        $semesterModel->insert([
            'kode'   => $kodeSemester,
            'nama'   => $namaSemester,
            'status' => 'NON-AKTIF',
        ]);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => "Semester {$namaSemester} berhasil ditambahkan!",
        ]);

        return redirect()->to(base_url('graduate/semester'));
    }

    /**
     * Update data semester yang ada.
     */
    public function update()
    {
        $auth = $this->ensureSemesterManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/semester'));
        }

        $kodeLama = trim((string) $this->request->getPost('kode'));
        $rawTahun = trim((string) $this->request->getPost('tahunAkademik'));
        $rawSem   = trim((string) $this->request->getPost('semester'));

        $semesterModel = new SemesterModel();
        $existing = $semesterModel->find($kodeLama);
        if (! $existing) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Data semester tidak ditemukan!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        if ($rawTahun === '' || $rawSem === '') {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Tahun akademik dan semester wajib diisi!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        if (preg_match('/(\d{4})/', $rawTahun, $matches)) {
            $tahunMulai = (int) $matches[1];
        } else {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Format tahun akademik tidak valid (contoh: 2025/2026)!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        $tahunSelesai = $tahunMulai + 1;
        $semCode      = (strtolower($rawSem) === 'ganjil' || $rawSem === '1') ? 1 : 2;
        $semName      = ($semCode === 1) ? 'Ganjil' : 'Genap';
        $kodeBaru     = "{$tahunMulai}-{$semCode}";
        $namaBaru     = "Tahun Akademik {$tahunMulai}/{$tahunSelesai} Semester {$semName}";

        if ($kodeBaru !== $kodeLama && $semesterModel->find($kodeBaru)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => "Semester tujuan ({$kodeBaru}) sudah ada!",
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        if ($kodeBaru === $kodeLama) {
            $semesterModel->update($kodeLama, ['nama' => $namaBaru]);
        } else {
            if (! $semesterModel->isSemesterDeleteable($kodeLama)) {
                session()->setFlashdata('message', [
                    'icon'  => 'error',
                    'title' => 'Gagal',
                    'text'  => 'Kode semester tidak dapat diubah karena sudah terhubung ke data lain!',
                ]);
                return redirect()->to(base_url('graduate/semester'));
            }
            $status = $existing['status'];
            $semesterModel->insert([
                'kode'   => $kodeBaru,
                'nama'   => $namaBaru,
                'status' => $status,
            ]);
            $semesterModel->delete($kodeLama);
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => "Semester berhasil diperbarui menjadi {$namaBaru}!",
        ]);

        return redirect()->to(base_url('graduate/semester'));
    }

    /**
     * Aktivasi semester (menonaktifkan semester lain).
     */
    public function activate($kode = null)
    {
        $auth = $this->ensureSemesterManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/semester'));
        }

        $kode          = trim((string) ($kode ?? $this->request->getPost('kode')));
        $semesterModel = new SemesterModel();
        $semester      = $semesterModel->find($kode);

        if (! $semester) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Data semester tidak ditemukan!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        $semesterModel->where('status', 'AKTIF')->set(['status' => 'NON-AKTIF'])->update();
        $semesterModel->update($kode, ['status' => 'AKTIF']);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => "Semester {$semester['nama']} sekarang aktif!",
        ]);

        return redirect()->to(base_url('graduate/semester'));
    }

    /**
     * Nonaktifkan semester.
     */
    public function nonactivate($kode = null)
    {
        $auth = $this->ensureSemesterManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/semester'));
        }

        $kode          = trim((string) ($kode ?? $this->request->getPost('kode')));
        $semesterModel = new SemesterModel();
        $semester      = $semesterModel->find($kode);

        if (! $semester) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Data semester tidak ditemukan!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        $semesterModel->update($kode, ['status' => 'NON-AKTIF']);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => "Semester {$semester['nama']} berhasil dinonaktifkan!",
        ]);

        return redirect()->to(base_url('graduate/semester'));
    }

    /**
     * Hapus semester.
     */
    public function delete($kode = null)
    {
        $auth = $this->ensureSemesterManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/semester'));
        }

        $kode          = trim((string) ($kode ?? $this->request->getPost('kode')));
        $semesterModel = new SemesterModel();
        $semester      = $semesterModel->find($kode);

        if (! $semester) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Data semester tidak ditemukan!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        if ($semester['status'] === 'AKTIF') {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Tidak dapat menghapus semester yang sedang AKTIF!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        if (! $semesterModel->isSemesterDeleteable($kode)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Semester ini sudah digunakan pada data skripsi/proposal sehingga tidak dapat dihapus!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        $semesterModel->delete($kode);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => "Semester {$semester['nama']} berhasil dihapus!",
        ]);

        return redirect()->to(base_url('graduate/semester'));
    }
}

