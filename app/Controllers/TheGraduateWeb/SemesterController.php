<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Models\SemesterGraduateModel;

/**
 * Controller Pengelolaan Semester Sisi The Graduate
 *
 * Menangani seluruh operasional CRUD dan aktivasi periode akademik semester.
 * Hanya dapat diakses oleh role 'fakultas' dan 'admin'.
 */
class SemesterController extends BaseController
{
    /**
     * Memastikan role fakultas, admin, tendik, dan staf dapat melihat daftar semester.
     */
    private function ensureSemesterViewer(): ?array
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return null;
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! PortalRouter::canUseGraduate($roles)) {
            return null;
        }

        if (! AccessControl::hasAnyRole($roles, ['fakultas', 'admin', 'tendik', 'staf'])) {
            return null;
        }

        return [
            'user'      => $userData,
            'roles'     => $roles,
            'akunId'    => $userData['akun_id'] ?? $userData['id'] ?? null,
            'canManage' => AccessControl::hasAnyRole($roles, ['fakultas', 'admin']),
        ];
    }

    /**
     * Memastikan hanya role fakultas dan admin yang dapat mengelola (tambah/edit/hapus/aktivasi) semester.
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
     * Dapat dilihat oleh admin, fakultas, tendik, dan staf.
     * Tendik & staf hanya memiliki akses baca (read-only), sedangkan fakultas & admin dapat mengelola.
     */
    public function index()
    {
        $auth = $this->ensureSemesterViewer();
        if ($auth === null) {
            $userData = session()->get('user_session');
            $roles    = $userData ? PortalRouter::normalizeRoles($userData) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $canManage = (bool) ($auth['canManage'] ?? false);

        $semesterModel = new SemesterGraduateModel();
        // Urutkan berdasarkan periode/kode semester (terbaru ke terlama) agar posisi baris stabil dan tidak melompat saat diaktifkan
        $perPage        = 10;
        $daftarSemester = $semesterModel->orderBy("kode", "DESC")->paginate($perPage, 'semester');
        $pager          = $semesterModel->pager;
        $totalRows      = $pager->getTotal('semester');
        $currentPage    = $pager->getCurrentPage('semester');

        session()->set('active_side', 'graduate');

        $folder   = PortalRouter::graduateFolder($auth['roles']);
        $viewName = ($folder === 'admin_tendik')
            ? 'The-Graduate-Web/admin_tendik/semester'
            : 'The-Graduate-Web/fakultas/semester';

        return view($viewName, [
            'title'          => 'Semester',
            'user'           => $auth['user'],
            'role'           => $auth['roles'],
            'canManage'      => $canManage,
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

        $rawSemLower = strtolower($rawSem);
        if (! in_array($rawSemLower, ['1', '2', 'ganjil', 'genap'], true)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Pilihan semester tidak valid! Pilih Ganjil (1) atau Genap (2).',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        if (preg_match('/^(\d{4})/', $rawTahun, $matches)) {
            $tahunMulai = (int) $matches[1];
            if ($tahunMulai < 2000 || $tahunMulai > 2100) {
                session()->setFlashdata('message', [
                    'icon'  => 'error',
                    'title' => 'Gagal',
                    'text'  => 'Tahun akademik harus berada di antara tahun 2000 hingga 2100!',
                ]);
                return redirect()->to(base_url('graduate/semester'));
            }
        } else {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Format tahun akademik tidak valid (contoh: 2025 atau 2025/2026)!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        $tahunSelesai = $tahunMulai + 1;
        $semCode      = ($rawSemLower === 'ganjil' || $rawSem === '1') ? 1 : 2;
        $semName      = ($semCode === 1) ? 'Ganjil' : 'Genap';
        $kodeSemester = "{$tahunMulai}-{$semCode}";
        $namaSemester = "Tahun Akademik {$tahunMulai}/{$tahunSelesai} Semester {$semName}";

        $semesterModel = new SemesterGraduateModel();
        if ($semesterModel->find($kodeSemester)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => "Semester {$namaSemester} ({$kodeSemester}) sudah ada!",
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        try {
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
        } catch (\Throwable $e) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Terjadi kesalahan saat menambahkan semester: ' . $e->getMessage(),
            ]);
        }

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

        $semesterModel = new SemesterGraduateModel();
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

        $rawSemLower = strtolower($rawSem);
        if (! in_array($rawSemLower, ['1', '2', 'ganjil', 'genap'], true)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Pilihan semester tidak valid! Pilih Ganjil (1) atau Genap (2).',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        if (preg_match('/^(\d{4})/', $rawTahun, $matches)) {
            $tahunMulai = (int) $matches[1];
            if ($tahunMulai < 2000 || $tahunMulai > 2100) {
                session()->setFlashdata('message', [
                    'icon'  => 'error',
                    'title' => 'Gagal',
                    'text'  => 'Tahun akademik harus berada di antara tahun 2000 hingga 2100!',
                ]);
                return redirect()->to(base_url('graduate/semester'));
            }
        } else {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Format tahun akademik tidak valid (contoh: 2025 atau 2025/2026)!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        $tahunSelesai = $tahunMulai + 1;
        $semCode      = ($rawSemLower === 'ganjil' || $rawSem === '1') ? 1 : 2;
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

        try {
            if ($kodeBaru === $kodeLama) {
                $semesterModel->update($kodeLama, ['nama' => $namaBaru]);
            } else {
                if (! $semesterModel->isSemesterDeleteable($kodeLama)) {
                    session()->setFlashdata('message', [
                        'icon'  => 'error',
                        'title' => 'Gagal',
                        'text'  => 'Kode semester tidak dapat diubah karena sudah terhubung ke data pendaftaran wisuda atau SK Yudisium!',
                    ]);
                    return redirect()->to(base_url('graduate/semester'));
                }

                $status = $existing['status'];
                $db = \Config\Database::connect();
                $db->transStart();
                $semesterModel->insert([
                    'kode'   => $kodeBaru,
                    'nama'   => $namaBaru,
                    'status' => $status,
                ]);
                $semesterModel->delete($kodeLama);
                $db->transComplete();

                if ($db->transStatus() === false) {
                    throw new \RuntimeException('Gagal memproses pembaruan kode semester.');
                }
            }

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Berhasil',
                'text'  => "Semester berhasil diperbarui menjadi {$namaBaru}!",
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Terjadi kesalahan saat memperbarui semester: ' . $e->getMessage(),
            ]);
        }

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
        $semesterModel = new SemesterGraduateModel();
        $semester      = $semesterModel->find($kode);

        if (! $semester) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Data semester tidak ditemukan!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        try {
            $db = \Config\Database::connect();
            $db->transStart();
            $semesterModel->where('status', 'AKTIF')->set(['status' => 'NON-AKTIF'])->update();
            $semesterModel->update($kode, ['status' => 'AKTIF']);
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Gagal mengubah status aktif semester.');
            }

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Berhasil',
                'text'  => "Semester {$semester['nama']} sekarang aktif!",
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Terjadi kesalahan saat mengaktifkan semester: ' . $e->getMessage(),
            ]);
        }

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
        $semesterModel = new SemesterGraduateModel();
        $semester      = $semesterModel->find($kode);

        if (! $semester) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Data semester tidak ditemukan!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        try {
            $semesterModel->update($kode, ['status' => 'NON-AKTIF']);

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Berhasil',
                'text'  => "Semester {$semester['nama']} berhasil dinonaktifkan!",
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Terjadi kesalahan saat menonaktifkan semester: ' . $e->getMessage(),
            ]);
        }

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
        $semesterModel = new SemesterGraduateModel();
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
                'text'  => 'Semester ini sudah digunakan pada data pendaftaran wisuda atau SK Yudisium sehingga tidak dapat dihapus!',
            ]);
            return redirect()->to(base_url('graduate/semester'));
        }

        try {
            $semesterModel->delete($kode);

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Berhasil',
                'text'  => "Semester {$semester['nama']} berhasil dihapus!",
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Terjadi kesalahan saat menghapus semester: ' . $e->getMessage(),
            ]);
        }

        return redirect()->to(base_url('graduate/semester'));
    }
}

