<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Models\AkunModel;
use App\Models\BerkasPengajuanSklModel;
use App\Models\MahasiswaModel;
use App\Models\MahasiswaTerplotModel;
use App\Models\PendaftaranWisudaModel;
use App\Models\PengajuanSklModel;
use App\Models\PersyaratanSklModel;
use App\Models\ProgramStudiModel;

/**
 * Controller Layanan & Pengambilan SKL (Surat Keterangan Lulus) Sisi The Graduate
 *
 * Mengelola:
 * - Persyaratan SKL dinamis (CRUD, aktivasi, reorder oleh tendik/admin)
 * - Pengajuan dan upload berkas persyaratan oleh mahasiswa
 * - Verifikasi kelayakan SKL oleh tendik/admin/fakultas
 */
class SklController extends BaseController
{
    protected PersyaratanSklModel $persyaratanModel;
    protected PengajuanSklModel $pengajuanModel;
    protected BerkasPengajuanSklModel $berkasModel;

    public function __construct()
    {
        $this->persyaratanModel = new PersyaratanSklModel();
        $this->pengajuanModel   = new PengajuanSklModel();
        $this->berkasModel      = new BerkasPengajuanSklModel();
    }

    /**
     * Memastikan hanya tendik/staf/admin/fakultas yang dapat mengelola SKL & persyaratannya.
     */
    private function ensureManager(): ?array
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return null;
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! PortalRouter::canUseGraduate($roles)) {
            return null;
        }

        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas'])) {
            return null;
        }

        return [
            'user'   => $userData,
            'roles'  => $roles,
            'akunId' => $userData['akun_id'] ?? $userData['id'] ?? null,
        ];
    }

    /**
     * Memeriksa apakah mahasiswa telah memiliki biodata wisuda yang berstatus DISETUJUI oleh Tendik.
     */
    public function isBiodataApproved(string $npm): bool
    {
        if ($npm === '') {
            return false;
        }

        try {
            $pendaftaranModel = new \App\Models\PendaftaranWisudaModel();
            $pendaftaran      = $pendaftaranModel->getByNpm($npm);
            if ($pendaftaran === null) {
                return false;
            }

            $statusBiodata = strtoupper((string) ($pendaftaran['status_biodata'] ?? ''));
            $statusVerif   = strtoupper((string) ($pendaftaran['status_verifikasi'] ?? ''));
            return ($statusBiodata === 'DISETUJUI' || $statusVerif === 'DISETUJUI');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Endpoint rute langsung: SKL Kelola (graduate/skl_kelola)
     */
    public function sklKelola()
    {
        return $this->indexWithTab('kelola');
    }

    /**
     * Endpoint rute langsung: SKL Mahasiswa (graduate/skl_mahasiswa)
     */
    public function sklMahasiswa()
    {
        return $this->indexWithTab('mahasiswa');
    }

    /**
     * Halaman utama SKL (mengarahkan ke view mahasiswa atau tendik sesuai role)
     */
    public function index()
    {
        $tab = trim((string) ($this->request->getGet('tab') ?? ''));
        return $this->indexWithTab($tab === 'kelola' ? 'kelola' : 'mahasiswa');
    }

    /**
     * Handler utama perutean tab SKL Kelola vs SKL Mahasiswa
     */
    protected function indexWithTab(string $activeTab = 'mahasiswa')
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! PortalRouter::canUseGraduate($roles)) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        session()->set('active_side', 'graduate');

        if (in_array('mahasiswa', $roles, true)) {
            return $this->indexMahasiswa($userData, $roles);
        }

        if (AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas'])) {
            return $this->indexAdminTendik($userData, $roles, $activeTab);
        }

        return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
    }

    /**
     * Tampilan SKL sisi Mahasiswa
     */
    protected function indexMahasiswa(array $userData, array $roles)
    {
        $npm = (string) ($userData['npm'] ?? $userData['id'] ?? '');

        // Gatekeeper SK Yudisium
        $terplotModel = new MahasiswaTerplotModel();
        $terplot      = $npm !== '' ? $terplotModel->where('npm', $npm)->first() : null;

        if ($terplot === null) {
            return view('The-Graduate-Web/mahasiswa/belum_terplot', [
                'title' => 'Akses Ditolak',
                'user'  => $userData,
                'role'  => $roles,
                'fitur' => 'layanan SKL',
            ]);
        }

        // Gatekeeper: Biodata Wisuda harus DISETUJUI oleh Tendik terlebih dahulu
        $pendaftaranModel = new \App\Models\PendaftaranWisudaModel();
        $pendaftaran      = $npm !== '' ? $pendaftaranModel->getByNpm($npm) : null;
        $statusBiodata    = strtoupper((string) ($pendaftaran['status_biodata'] ?? ''));

        if ($statusBiodata !== 'DISETUJUI') {
            return view('The-Graduate-Web/mahasiswa/biodata_belum_disetujui', [
                'title'         => 'Akses SKL Belum Dibuka',
                'user'          => $userData,
                'role'          => $roles,
                'pendaftaran'   => $pendaftaran,
                'statusBiodata' => $statusBiodata,
            ]);
        }

        // Ambil daftar persyaratan SKL aktif secara dinamis dari database
        $daftarPersyaratan = $this->persyaratanModel->getPersyaratanAktif();

        // Ambil data pengajuan SKL jika sudah pernah mengajukan
        $pengajuan = $this->pengajuanModel->getByNpm($npm);

        // Ambil peta berkas yang sudah diunggah oleh mahasiswa
        $berkasMap = $this->berkasModel->getBerkasMapByNpm($npm);

        return view('The-Graduate-Web/mahasiswa/skl', [
            'title'             => 'SKL',
            'user'              => $userData,
            'role'              => $roles,
            'daftarPersyaratan' => $daftarPersyaratan,
            'pengajuan'         => $pengajuan,
            'berkasMap'         => $berkasMap,
            'terplot'           => $terplot,
        ]);
    }

    /**
     * Tampilan SKL sisi Admin & Tendik (SKL Kelola + SKL Mahasiswa)
     */
    protected function indexAdminTendik(array $userData, array $roles, string $activeTab = 'mahasiswa')
    {
        // 1. Ambil seluruh persyaratan SKL (untuk tab SKL Kelola Dinamis)
        $semuaPersyaratan = $this->persyaratanModel->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')->findAll();

        // 2. Ambil persyaratan aktif (untuk header kolom dan badge dokumen di tabel pengajuan SKL Mahasiswa)
        $persyaratanAktif = $this->persyaratanModel->getPersyaratanAktif();

        // 3. Ambil daftar semester untuk filter periode
        $semModel          = new \App\Models\SemesterGraduateModel();
        $daftarSemester    = $semModel->orderBy('kode', 'DESC')->findAll();
        $semAktifObj       = $semModel->getActiveSemester();
        $kodeSemesterAktif = ! empty($semAktifObj['kode']) ? (string) $semAktifObj['kode'] : '';

        $rawSemester = $this->request->getGet('semester');
        if ($rawSemester === null) {
            // Default ke semester aktif di The Graduate jika belum ada input filter
            $semesterAktif = $kodeSemesterAktif !== '' ? $kodeSemesterAktif : 'semua';
        } else {
            $semesterAktif = trim((string) $rawSemester);
            if ($semesterAktif === '' || strtolower($semesterAktif) === 'semua') {
                $semesterAktif = 'semua';
            }
        }

        // 4. Filter pencarian, status, dan semester
        $filterStatus = trim((string) ($this->request->getGet('status') ?? ''));
        $kataCari     = trim((string) ($this->request->getGet('cari') ?? ''));

        $daftarPengajuan = $this->pengajuanModel->getFiltered(
            $filterStatus !== '' ? $filterStatus : null,
            $kataCari !== '' ? $kataCari : null,
            $semesterAktif !== 'semua' ? $semesterAktif : null
        );

        // Ambil berkas-berkas per NPM mahasiswa
        $npms = array_column($daftarPengajuan, 'npm');
        $berkasPerMahasiswa = $this->berkasModel->getBerkasMapGroupedByNpm($npms);

        return view('The-Graduate-Web/admin_tendik/skl', [
            'title'              => $activeTab === 'kelola' ? 'SKL Kelola' : 'SKL Mahasiswa',
            'user'               => $userData,
            'role'               => $roles,
            'daftarPengajuan'    => $daftarPengajuan,
            'persyaratanAktif'   => $persyaratanAktif,
            'semuaPersyaratan'   => $semuaPersyaratan,
            'berkasPerMahasiswa' => $berkasPerMahasiswa,
            'daftarSemester'     => $daftarSemester,
            'semesterAktif'      => $semesterAktif,
            'kodeSemesterAktif'  => $kodeSemesterAktif,
            'pilihanStatus'      => $filterStatus,
            'kataCari'           => $kataCari,
            'activeTab'          => $activeTab,
        ]);
    }

    /* ------------------------------------------------------------------
     |  Operasional Mahasiswa: Upload, Hapus Berkas, & Submit Pengajuan
     | ------------------------------------------------------------------ */

    /**
     * Upload dokumen persyaratan SKL oleh mahasiswa (AJAX)
     */
    public function uploadBerkas()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Silakan login terlebih dahulu.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! in_array('mahasiswa', $roles, true)) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Akses ditolak: Hanya mahasiswa yang dapat mengunggah berkas SKL.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $npm = (string) ($userData['npm'] ?? $userData['id'] ?? '');
        if ($npm === '') {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Identitas NPM tidak ditemukan pada sesi Anda.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // Gatekeeper: Biodata Wisuda harus DISETUJUI terlebih dahulu
        if (! $this->isBiodataApproved($npm)) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Layanan SKL belum dapat diakses: Biodata Wisuda Anda belum disetujui oleh Tendik.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // Cek pengajuan yang sudah disetujui (terkunci)
        $pengajuanExisting = $this->pengajuanModel->getByNpm($npm);
        if ($pengajuanExisting !== null && $pengajuanExisting['status'] === 'disetujui') {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Pengajuan SKL Anda telah disetujui dan tidak dapat diubah lagi.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $persyaratanId = (int) $this->request->getPost('persyaratan_id');
        $persyaratan   = $this->persyaratanModel->find($persyaratanId);
        if ($persyaratan === null || $persyaratan['status'] !== 'AKTIF') {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Persyaratan SKL tidak ditemukan atau sudah tidak aktif.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // Tentukan ekstensi & batas ukuran dari persyaratan
        $allowedExts = array_filter(array_map('trim', explode(',', strtolower($persyaratan['tipe_file'] ?? 'pdf'))));
        if (empty($allowedExts)) {
            $allowedExts = ['pdf'];
        }
        $maxAllowedKb = (int) ($persyaratan['max_size_kb'] ?? 5120);
        if ($maxAllowedKb <= 0) {
            $maxAllowedKb = 5120;
        }

        $file = $this->request->getFile('file_berkas') ?? $this->request->getFile('file');
        if (! $file || ! $file->isValid()) {
            $errString = 'Berkas tidak valid atau tidak dipilih.';
            if ($file && $file->getError() === UPLOAD_ERR_INI_SIZE) {
                $errString = 'Ukuran berkas melebihi batas upload maksimal server PHP. Harap kompres berkas Anda.';
            } elseif ($file && $file->getErrorString() !== '') {
                $errString = $file->getErrorString();
            }
            return $this->response->setJSON([
                'success'    => false,
                'message'    => $errString,
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $ext = strtolower($file->getExtension());
        if (! in_array($ext, $allowedExts, true)) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Format file tidak diizinkan. Format yang diperbolehkan: ' . strtoupper(implode(', ', $allowedExts)),
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $sizeKb = (int) round($file->getSizeByUnit('kb'));
        if ($sizeKb > $maxAllowedKb) {
            $maxMbFormatted = number_format($maxAllowedKb / 1024, 1);
            return $this->response->setJSON([
                'success'    => false,
                'message'    => "Ukuran berkas melebihi batas maksimal ({$maxMbFormatted} MB)",
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $targetDir = FCPATH . 'uploads/graduate/skl/';
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        $cleanPersyaratanSlug = url_title($persyaratan['nama'], '_', true);
        $newFileName          = 'skl_' . $npm . '_' . $cleanPersyaratanSlug . '_' . time() . '.' . $ext;

        try {
            $file->move($targetDir, $newFileName);

            // Cek apakah berkas dengan persyaratan_id ini sudah pernah ada
            $existingBerkas = $this->berkasModel
                ->where('npm', $npm)
                ->where('persyaratan_id', $persyaratanId)
                ->first();

            $dataSimpan = [
                'pengajuan_id'     => $pengajuanExisting['id'] ?? null,
                'npm'              => $npm,
                'persyaratan_id'   => $persyaratanId,
                'nama_persyaratan' => $persyaratan['nama'],
                'nama_file'        => $newFileName,
                'ukuran_kb'        => $sizeKb,
            ];

            if ($existingBerkas !== null) {
                // Hapus file lama jika ada
                $oldFile = $targetDir . $existingBerkas['nama_file'];
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
                $this->berkasModel->update($existingBerkas['id'], $dataSimpan);
                $berkasId = (int) $existingBerkas['id'];
            } else {
                $berkasId = (int) $this->berkasModel->insert($dataSimpan);
            }

            return $this->response->setJSON([
                'success'        => true,
                'message'        => 'Berkas ' . $persyaratan['nama'] . ' berhasil diunggah.',
                'berkas_id'      => $berkasId,
                'nama_file'      => $newFileName,
                'persyaratan_id' => $persyaratanId,
                'file_url'       => base_url('graduate/skl/preview/' . $newFileName),
                'csrf_token'     => csrf_token(),
                'csrf_hash'      => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal upload berkas SKL: ' . $e->getMessage());
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Terjadi kesalahan sistem saat menyimpan berkas: ' . $e->getMessage(),
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }
    }

    /**
     * Hapus berkas persyaratan SKL oleh mahasiswa (AJAX)
     */
    public function hapusBerkas($berkasId = null)
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Silakan login terlebih dahulu.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! in_array('mahasiswa', $roles, true)) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Akses ditolak.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $npm      = (string) ($userData['npm'] ?? $userData['id'] ?? '');

        // Gatekeeper: Biodata Wisuda harus DISETUJUI terlebih dahulu
        if (! $this->isBiodataApproved($npm)) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Layanan SKL belum dapat diakses: Biodata Wisuda Anda belum disetujui oleh Tendik.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $berkasId = (int) ($berkasId ?? $this->request->getPost('berkas_id'));
        $syaratId = (int) $this->request->getPost('persyaratan_id');

        $berkas = null;
        if ($berkasId > 0) {
            $berkas = $this->berkasModel->find($berkasId);
        } elseif ($syaratId > 0) {
            $berkas = $this->berkasModel->where('npm', $npm)->where('persyaratan_id', $syaratId)->first();
        }

        if ($berkas === null) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Berkas tidak ditemukan.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        if ($berkas['npm'] !== $npm) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Anda tidak memiliki hak untuk menghapus berkas ini.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // Cek status pengajuan
        $pengajuan = $this->pengajuanModel->getByNpm($npm);
        if ($pengajuan !== null && $pengajuan['status'] === 'disetujui') {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Pengajuan SKL telah disetujui dan berkas tidak dapat dihapus.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        try {
            $filePath = FCPATH . 'uploads/graduate/skl/' . $berkas['nama_file'];
            if (is_file($filePath)) {
                @unlink($filePath);
            }

            $this->berkasModel->delete($berkas['id']);

            return $this->response->setJSON([
                'success'        => true,
                'message'        => 'Berkas ' . $berkas['nama_persyaratan'] . ' berhasil dihapus.',
                'persyaratan_id' => (int) $berkas['persyaratan_id'],
                'csrf_token'     => csrf_token(),
                'csrf_hash'      => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Gagal menghapus berkas: ' . $e->getMessage(),
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }
    }

    /**
     * Kirim data pengajuan SKL oleh mahasiswa
     */
    public function submit()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! in_array('mahasiswa', $roles, true)) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $npm = (string) ($userData['npm'] ?? $userData['id'] ?? '');
        if ($npm === '') {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Identitas NPM tidak ditemukan.']);
            return redirect()->to(base_url('graduate/skl'));
        }

        // Gatekeeper: Biodata Wisuda harus DISETUJUI terlebih dahulu
        if (! $this->isBiodataApproved($npm)) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Akses Ditolak',
                'text'  => 'Layanan SKL belum dapat diakses karena Biodata Wisuda Anda belum disetujui oleh Tendik.',
            ]);
            return redirect()->to(base_url('graduate/skl'));
        }

        // Validasi kelengkapan semua persyaratan dinamis yang bersifat wajib (is_wajib = 1)
        $persyaratanAktif = $this->persyaratanModel->getPersyaratanAktif();
        $berkasMap        = $this->berkasModel->getBerkasMapByNpm($npm);

        $syaratBelumLengkap = [];
        foreach ($persyaratanAktif as $syarat) {
            if ((int) $syarat['is_wajib'] === 1 && ! isset($berkasMap[(int) $syarat['id']])) {
                $syaratBelumLengkap[] = $syarat['nama'];
            }
        }

        if (! empty($syaratBelumLengkap)) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Berkas Belum Lengkap',
                'text'  => 'Harap lengkapi seluruh berkas wajib: ' . implode(', ', $syaratBelumLengkap),
            ]);
            return redirect()->to(base_url('graduate/skl'));
        }

        // Ambil data mahasiswa & semester
        $terplotModel = new MahasiswaTerplotModel();
        $terplot      = $terplotModel->where('npm', $npm)->first();

        $namaMahasiswa = $userData['nama'] ?? ($terplot['nama'] ?? 'Mahasiswa');
        $idProdi       = $terplot['id_prodi'] ?? null;
        $kodeSemester  = $terplot['kode_semester'] ?? null;

        if (empty($kodeSemester)) {
            $pendaftaranModel = new \App\Models\PendaftaranWisudaModel();
            $pendaftaran      = $pendaftaranModel->getByNpm($npm);
            $kodeSemester     = $pendaftaran['kode_semester'] ?? null;
        }

        if (empty($kodeSemester)) {
            $semModel     = new \App\Models\SemesterGraduateModel();
            $semAktif     = $semModel->getActiveSemester();
            $kodeSemester = $semAktif['kode'] ?? null;
        }

        $existing = $this->pengajuanModel->getByNpm($npm);
        $now      = date('Y-m-d H:i:s');

        $dataPengajuan = [
            'npm'           => $npm,
            'nama'          => $namaMahasiswa,
            'id_prodi'      => $idProdi,
            'kode_semester' => $kodeSemester,
            'status'        => 'menunggu',
            'catatan'       => null,
            'diajukan_pada' => $now,
        ];

        try {
            if ($existing !== null) {
                $this->pengajuanModel->update($existing['id'], $dataPengajuan);
                $pengajuanId = (int) $existing['id'];
            } else {
                $pengajuanId = (int) $this->pengajuanModel->insert($dataPengajuan);
            }

            // Hubungkan semua berkas mahasiswa ke pengajuan_id ini
            $this->berkasModel->where('npm', $npm)->set(['pengajuan_id' => $pengajuanId])->update();

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Pengajuan SKL Berhasil',
                'text'  => 'Pengajuan SKL Anda telah berhasil dikirim dan sedang menunggu verifikasi tim akademik/tendik.',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal submit pengajuan SKL: ' . $e->getMessage());
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Mengirim',
                'text'  => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ]);
        }

        return redirect()->to(base_url('graduate/skl'));
    }

    /* ------------------------------------------------------------------
     |  Operasional Tendik/Admin: Verifikasi Pengajuan SKL
     | ------------------------------------------------------------------ */

    /**
     * Verifikasi (Setujui / Tolak) pengajuan SKL oleh tendik/admin
     */
    public function verifikasi()
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $id      = (int) $this->request->getPost('id');
        $status  = strtolower(trim((string) $this->request->getPost('status')));
        $catatan = trim((string) $this->request->getPost('catatan'));

        if (! in_array($status, ['disetujui', 'ditolak', 'menunggu'], true)) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Status Tidak Valid', 'text' => 'Status verifikasi tidak dikenali.']);
            return redirect()->to(base_url('graduate/skl_mahasiswa'));
        }

        $pengajuan = $this->pengajuanModel->find($id);
        if ($pengajuan === null) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data pengajuan SKL tidak ditemukan.']);
            return redirect()->to(base_url('graduate/skl_mahasiswa'));
        }

        $updateData = [
            'status'            => $status,
            'catatan'           => $catatan ?: null,
            'diverifikasi_oleh' => (int) ($auth['akunId'] ?? 1),
            'diverifikasi_pada' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->pengajuanModel->update($id, $updateData);

            $statusText = $status === 'disetujui' ? 'disetujui' : ($status === 'ditolak' ? 'ditolak' : 'diperbarui');
            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Verifikasi Berhasil',
                'text'  => 'Pengajuan SKL untuk mahasiswa ' . $pengajuan['nama'] . ' berhasil ' . $statusText . '.',
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Verifikasi', 'text' => $e->getMessage()]);
        }

        return redirect()->to(base_url('graduate/skl_mahasiswa'));
    }

    /**
     * Ubah status pengajuan SKL menjadi 'proses' oleh Tendik/Admin
     */
    public function ubahKeProses()
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $id = (int) $this->request->getPost('id');
        $pengajuan = $this->pengajuanModel->find($id);
        if ($pengajuan === null) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data pengajuan SKL tidak ditemukan.']);
            return redirect()->to(base_url('graduate/skl_mahasiswa'));
        }

        try {
            $this->pengajuanModel->update($id, [
                'status'            => 'proses',
                'diverifikasi_oleh' => (int) ($auth['akunId'] ?? 1),
                'diverifikasi_pada' => date('Y-m-d H:i:s'),
            ]);

            session()->setFlashdata('message', [
                'icon'  => 'info',
                'title' => 'Status Diproses',
                'text'  => 'Pengajuan SKL mahasiswa ' . $pengajuan['nama'] . ' kini berstatus DIPROSES. Silakan unduh template SKL dan siapkan dokumen resmi.',
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Memproses', 'text' => $e->getMessage()]);
        }

        return redirect()->to(base_url('graduate/skl_mahasiswa'));
    }

    /**
     * Download Template Surat Keterangan Lulus (SKL) Resmi (PDF) untuk Tendik
     */
    public function downloadTemplateSkl($id = null)
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $id = (int) ($id ?? $this->request->getGet('id'));
        $pengajuan = $this->pengajuanModel->find($id);
        if ($pengajuan === null) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data pengajuan SKL tidak ditemukan.']);
            return redirect()->to(base_url('graduate/skl_mahasiswa'));
        }

        $npm = (string) $pengajuan['npm'];

        // Ambil data biodata mahasiswa dari pendaftaran wisuda jika ada
        $pendaftaranModel = new \App\Models\PendaftaranWisudaModel();
        $biodata = $pendaftaranModel->getByNpm($npm);

        // Ambil data terplot SK Yudisium
        $terplotModel = new MahasiswaTerplotModel();
        $terplot = $terplotModel->where('npm', $npm)->first();

        // Siapkan format tanggal Indonesia
        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $formatTglIndo = static function (?string $date) use ($bulanIndo): string {
            if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
                return '-';
            }
            $time = strtotime($date);
            if ($time === false || $time <= 0) {
                return '-';
            }
            $d = date('j', $time);
            $m = (int) date('n', $time);
            $y = date('Y', $time);
            return $d . ' ' . ($bulanIndo[$m] ?? '') . ' ' . $y;
        };

        // Format TTL
        $tempatLahir = $biodata['tempat_lahir'] ?? null;
        $tglLahir    = $formatTglIndo($biodata['tanggal_lahir'] ?? null);
        $ttlText = ($tempatLahir && $tglLahir !== '-') ? ($tempatLahir . ', ' . $tglLahir) : ($tglLahir !== '-' ? $tglLahir : '-');

        // Tanggal Lulus / Yudisium
        $tglLulus = $formatTglIndo($biodata['tanggal_lulus'] ?? ($terplot['tanggal_sk_yudisium'] ?? null));

        // Program Studi Resmi, Gelar, dan Tembusan sesuai dokumen resmi FTI YARSI
        $prodi = $biodata['prodi'] ?? null;
        if (empty($prodi)) {
            $db = \Config\Database::connect();
            $prodiRow = $db->table('program_studi')->where('id', $pengajuan['id_prodi'] ?? 1)->get()->getRowArray();
            $prodi = $prodiRow['nama'] ?? 'Teknik Informatika';
        }

        $prodiLower = strtolower((string) $prodi);
        $isPdsi = str_contains($prodiLower, 'perpustakaan')
            || str_contains($prodiLower, 'pdsi')
            || str_contains($prodiLower, 'sains informasi')
            || str_starts_with($npm, '15');

        if ($isPdsi) {
            $namaProdiResmi = 'Program Studi Perpustakaan dan Sains Informasi';
            $gelarResmi     = 'Sarjana Sains Informasi (S.S.I.)';
            $tembusanProdi  = 'Kepala Prodi PdSI';
        } else {
            $namaProdiResmi = 'Program Studi Teknik Informatika Program Sarjana';
            $gelarResmi     = 'Sarjana Komputer (S.Kom.)';
            $tembusanProdi  = 'Kepala Prodi TI';
        }

        // Logo Base64 (gunakan kop resmi FTI YARSI jika ada)
        $pathLogo = FCPATH . 'assets/img/kop_fti_yarsi.png';
        if (! file_exists($pathLogo)) {
            $pathLogo = FCPATH . 'assets/img/fti.jpg';
        }
        $logoBase64 = '';
        if (file_exists($pathLogo)) {
            $type = pathinfo($pathLogo, PATHINFO_EXTENSION);
            $dataImg = file_get_contents($pathLogo);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($dataImg);
        }

        $romawiBulan = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $bulanRomawi = $romawiBulan[(int) date('n')];
        // Format nomor surat resmi FTI: 023/FTI/S.Ket PP.30/VII/2026
        $defaultNoSurat = !empty($pengajuan['no_surat']) ? $pengajuan['no_surat'] : ('...../FTI/S.Ket PP.30/' . $bulanRomawi . '/' . date('Y'));

        $dataPDF = [
            'logo_src'       => $logoBase64,
            'no_surat'       => $defaultNoSurat,
            'tanggal_surat'  => $formatTglIndo(!empty($pengajuan['tgl_terbit']) ? $pengajuan['tgl_terbit'] : date('Y-m-d')),
            'mhs'            => [
                'nama'              => $pengajuan['nama'],
                'npm'               => $npm,
                'ttl'               => $ttlText,
                'prodi'             => $prodi,
                'nama_prodi_resmi'  => $namaProdiResmi,
                'tanggal_lulus'     => $tglLulus !== '-' ? $tglLulus : $formatTglIndo(date('Y-m-d')),
                'gelar'             => $gelarResmi,
            ],
            'pejabat'        => [
                'nama'    => 'Dr. Ummi Azizah Rachmawati, S.Kom, M.Kom.',
                'jabatan' => 'DEKAN FAKULTAS TEKNOLOGI INFORMASI',
            ],
            'tembusan_prodi' => $tembusanProdi,
        ];

        helper('url');
        $cleanNama = function_exists('url_title') ? url_title($pengajuan['nama'] ?? 'Mahasiswa', '_', true) : preg_replace('/[^a-zA-Z0-9_-]/', '_', $pengajuan['nama'] ?? 'Mahasiswa');

        // 1. Ekspor dalam format Microsoft Word (.docx) agar Tendik dapat mengedit nomor surat & data secara manual
        $baseDocx = FCPATH . 'assets/dokumen/template_skl_base.docx';
        if (! file_exists($baseDocx)) {
            $baseDocx = ROOTPATH . 'public/assets/dokumen/template_skl_base.docx';
        }

        if (class_exists('\ZipArchive') && file_exists($baseDocx)) {
            $tempDir = (is_dir(WRITEPATH . 'cache') && is_writable(WRITEPATH . 'cache')) ? WRITEPATH . 'cache' : sys_get_temp_dir();
            $tempDocx = tempnam($tempDir, 'skl_docx_');
            if (copy($baseDocx, $tempDocx)) {
                $zip = new \ZipArchive();
                if ($zip->open($tempDocx) === true) {
                    $xml = $zip->getFromName('word/document.xml');

                    $replacements = [
                        '${NO_SURAT}'       => htmlspecialchars($defaultNoSurat, ENT_XML1, 'UTF-8'),
                        '${NAMA}'           => htmlspecialchars($pengajuan['nama'], ENT_XML1, 'UTF-8'),
                        '${NPM}'            => htmlspecialchars($npm, ENT_XML1, 'UTF-8'),
                        '${TTL}'            => htmlspecialchars($ttlText, ENT_XML1, 'UTF-8'),
                        '${PRODI_RESMI}'    => htmlspecialchars($namaProdiResmi, ENT_XML1, 'UTF-8'),
                        '${TGL_LULUS}'      => htmlspecialchars($tglLulus !== '-' ? $tglLulus : $formatTglIndo(date('Y-m-d')), ENT_XML1, 'UTF-8'),
                        '${GELAR}'          => htmlspecialchars($gelarResmi, ENT_XML1, 'UTF-8'),
                        '${TGL_SURAT}'      => htmlspecialchars($formatTglIndo(!empty($pengajuan['tgl_terbit']) ? $pengajuan['tgl_terbit'] : date('Y-m-d')), ENT_XML1, 'UTF-8'),
                        '${TEMBUSAN_PRODI}' => htmlspecialchars($tembusanProdi, ENT_XML1, 'UTF-8'),
                    ];

                    $newXml = str_replace(array_keys($replacements), array_values($replacements), $xml);
                    $zip->deleteName('word/document.xml');
                    $zip->addFromString('word/document.xml', $newXml);
                    $zip->close();

                    $docxContent = file_get_contents($tempDocx);
                    @unlink($tempDocx);

                    $fileName = 'Template_SKL_' . $npm . '_' . $cleanNama . '.docx';
                    return $this->response->download($fileName, $docxContent, true);
                }
                @unlink($tempDocx);
            }
        }

        // 2. Fallback ke PDF jika format docx tidak dapat diproses
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $html   = view('The-Graduate-Web/dokumen/template_skl_pdf', $dataPDF);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName  = 'Template_SKL_' . $npm . '_' . $cleanNama . '.pdf';

        return $dompdf->stream($fileName, ['Attachment' => 1]);
    }

    /**
     * Kirim & Unggah Berkas SKL Resmi ke Mahasiswa (Otomatis mengubah status jadi 'disetujui')
     */
    public function kirimSkl()
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $id        = (int) $this->request->getPost('id');
        $noSurat   = trim((string) $this->request->getPost('no_surat'));
        $tglTerbit = trim((string) $this->request->getPost('tgl_terbit'));
        $catatan   = trim((string) $this->request->getPost('catatan'));

        $pengajuan = $this->pengajuanModel->find($id);
        if ($pengajuan === null) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data pengajuan SKL tidak ditemukan.']);
            return redirect()->to(base_url('graduate/skl_mahasiswa'));
        }

        $file = $this->request->getFile('file_skl');
        if (! $file || ! $file->isValid()) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Berkas Tidak Valid',
                'text'  => 'Harap pilih berkas Surat Keterangan Lulus (SKL) resmi yang telah bertanda tangan & stempel.',
            ]);
            return redirect()->to(base_url('graduate/skl_mahasiswa'))->withInput();
        }

        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower($file->getExtension());
        if (! in_array($ext, $allowedExts, true)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Format Berkas Ditolak',
                'text'  => 'Berkas SKL wajib berformat PDF atau Gambar (JPG/PNG).',
            ]);
            return redirect()->to(base_url('graduate/skl_mahasiswa'))->withInput();
        }

        $targetDir = FCPATH . 'uploads/graduate/skl_resmi/';
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        $newFileName = 'SKL_Resmi_' . $pengajuan['npm'] . '_' . time() . '.' . $ext;

        try {
            // Hapus file lama jika ada
            if (! empty($pengajuan['file_skl'])) {
                $oldFile = $targetDir . $pengajuan['file_skl'];
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            $file->move($targetDir, $newFileName);

            $this->pengajuanModel->update($id, [
                'status'            => 'disetujui',
                'no_surat'          => $noSurat ?: null,
                'file_skl'          => $newFileName,
                'tgl_terbit'        => ! empty($tglTerbit) ? $tglTerbit : date('Y-m-d'),
                'dikirim_oleh'      => (int) ($auth['akunId'] ?? 1),
                'dikirim_pada'      => date('Y-m-d H:i:s'),
                'diverifikasi_oleh' => (int) ($auth['akunId'] ?? 1),
                'diverifikasi_pada' => date('Y-m-d H:i:s'),
                'catatan'           => $catatan ?: null,
            ]);

            // Kirim notifikasi email ke Gmail mahasiswa beserta dokumen SKL resmi terlampir
            $emailResult = $this->kirimNotifikasiEmailSkl(
                $pengajuan,
                $noSurat,
                $tglTerbit,
                $catatan,
                $targetDir . $newFileName
            );

            $pesanTeks = 'Surat Keterangan Lulus resmi untuk ' . $pengajuan['nama'] . ' berhasil dikirimkan. Status pengajuan otomatis DISETUJUI dan dokumen SKL dapat diunduh.';
            if (! empty($emailResult['status'])) {
                $pesanTeks .= ' Dokumen SKL resmi dan notifikasi berhasil dikirimkan ke email mahasiswa (' . $emailResult['email'] . ').';
            } elseif (! empty($emailResult['email'])) {
                $pesanTeks .= ' (Catatan: Notifikasi email ke ' . $emailResult['email'] . ' mengalami kendala, namun dokumen tetap tersimpan di sistem).';
            }

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'SKL Berhasil Dikirim',
                'text'  => $pesanTeks,
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Mengirim SKL',
                'text'  => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ]);
        }

        return redirect()->to(base_url('graduate/skl_mahasiswa'));
    }

    /**
     * Cari email tujuan mahasiswa dari Pendaftaran Wisuda, Mahasiswa, atau Akun.
     */
    private function cariEmailMahasiswa(string $npm): ?string
    {
        if ($npm === '') {
            return null;
        }

        // 1. Prioritas dari biodata pendaftaran wisuda
        try {
            $pendaftaranModel = new PendaftaranWisudaModel();
            $pendaftaran = $pendaftaranModel->getByNpm($npm);
            if (! empty($pendaftaran['email']) && filter_var($pendaftaran['email'], FILTER_VALIDATE_EMAIL)) {
                return trim($pendaftaran['email']);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal cek email pendaftaran wisuda: ' . $e->getMessage());
        }

        // 2. Dari tabel master mahasiswa
        try {
            $mahasiswaModel = new MahasiswaModel();
            $mhs = $mahasiswaModel->find($npm);
            if (! empty($mhs['email']) && filter_var($mhs['email'], FILTER_VALIDATE_EMAIL)) {
                return trim($mhs['email']);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal cek email master mahasiswa: ' . $e->getMessage());
        }

        // 3. Dari tabel akun
        try {
            $akunModel = new AkunModel();
            $akun = $akunModel->where('username', $npm)->first();
            if (! empty($akun['email']) && filter_var($akun['email'], FILTER_VALIDATE_EMAIL)) {
                return trim($akun['email']);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal cek email akun: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Kirim email pemberitahuan SKL resmi beserta lampiran berkas ke mahasiswa
     */
    private function kirimNotifikasiEmailSkl(array $pengajuan, string $noSurat, string $tglTerbit, string $catatan, string $filePath): array
    {
        $npm = (string) ($pengajuan['npm'] ?? '');
        $emailTujuan = $this->cariEmailMahasiswa($npm);

        if (empty($emailTujuan)) {
            return [
                'status'  => false,
                'message' => 'Email mahasiswa tidak ditemukan di database.',
                'email'   => null,
            ];
        }

        try {
            $namaProdi = '-';
            if (! empty($pengajuan['id_prodi'])) {
                $prodiModel = new ProgramStudiModel();
                $prodi = $prodiModel->find($pengajuan['id_prodi']);
                $namaProdi = $prodi['nama'] ?? '-';
            }

            $emailData = [
                'nama'       => $pengajuan['nama'] ?? 'Mahasiswa',
                'npm'        => $npm,
                'prodi'      => $namaProdi,
                'no_surat'   => ! empty($noSurat) ? $noSurat : ($pengajuan['no_surat'] ?? '-'),
                'tgl_terbit' => ! empty($tglTerbit) ? date('d F Y', strtotime($tglTerbit)) : date('d F Y'),
                'catatan'    => $catatan,
            ];

            $pesanHtml = view('emails/notifikasi_skl_resmi', $emailData);

            $email = \Config\Services::email();
            $email->clear(true);
            $emailConfig = config('Email');
            $pengirimEmail = ! empty($emailConfig->fromEmail) ? $emailConfig->fromEmail : 'zianbertanya2@gmail.com';
            $email->setFrom($pengirimEmail, 'Layanan SKL The Graduate');
            $email->setTo($emailTujuan);
            $email->setSubject('Surat Keterangan Lulus (SKL) Resmi - ' . ($pengajuan['nama'] ?? $npm));
            $email->setMessage($pesanHtml);

            // Lampirkan berkas SKL resmi jika file fisik tersedia
            if (! empty($filePath) && is_file($filePath)) {
                $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                $email->attach($filePath, 'attachment', 'SKL_Resmi_' . $npm . '.' . $ext);
            }

            if (! $email->send()) {
                $debugInfo = $email->printDebugger(['headers']);
                log_message('error', 'Gagal kirim email SKL ke ' . $emailTujuan . ': ' . print_r($debugInfo, true));
                return [
                    'status'  => false,
                    'message' => 'Gagal mengirim email melalui SMTP.',
                    'email'   => $emailTujuan,
                ];
            }

            return [
                'status'  => true,
                'message' => 'Email berhasil dikirim.',
                'email'   => $emailTujuan,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Exception saat kirim email SKL: ' . $e->getMessage());
            return [
                'status'  => false,
                'message' => $e->getMessage(),
                'email'   => $emailTujuan,
            ];
        }
    }

    /**
     * Download berkas SKL resmi yang telah dikirimkan oleh Tendik
     */
    public function downloadSklResmi($id = null)
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'));
        }

        $roles   = PortalRouter::normalizeRoles($userData);
        $isStaff = AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas']);
        $npmSesi = (string) ($userData['npm'] ?? $userData['id'] ?? '');

        $id = (int) ($id ?? $this->request->getGet('id'));
        $pengajuan = $id > 0 ? $this->pengajuanModel->find($id) : null;

        // Jika dipanggil oleh mahasiswa tanpa ID, cari by NPM sesi
        if ($pengajuan === null && ! $isStaff && $npmSesi !== '') {
            $pengajuan = $this->pengajuanModel->getByNpm($npmSesi);
        }

        if ($pengajuan === null || empty($pengajuan['file_skl'])) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Berkas Belum Tersedia',
                'text'  => 'Surat Keterangan Lulus (SKL) resmi belum diterbitkan atau belum dikirimkan oleh pihak akademik.',
            ]);
            return redirect()->to(base_url($isStaff ? 'graduate/skl_mahasiswa' : 'graduate/skl'));
        }

        // Keamanan: Mahasiswa hanya boleh download miliknya sendiri
        if (! $isStaff && (string) $pengajuan['npm'] !== $npmSesi) {
            return AccessControl::forbiddenResponse($this->request, 'Anda tidak memiliki hak untuk mengunduh dokumen ini.', $roles);
        }

        $filePath = FCPATH . 'uploads/graduate/skl_resmi/' . $pengajuan['file_skl'];
        if (! is_file($filePath)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Berkas Tidak Ditemukan',
                'text'  => 'File fisik SKL tidak ditemukan di server.',
            ]);
            return redirect()->to(base_url($isStaff ? 'graduate/skl_mahasiswa' : 'graduate/skl'));
        }

        $mime = mime_content_type($filePath) ?: 'application/pdf';
        $downloadName = 'SKL_Resmi_' . $pengajuan['npm'] . '_' . url_title($pengajuan['nama'], '_', true) . '.' . pathinfo($filePath, PATHINFO_EXTENSION);

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $downloadName . '"')
            ->setBody((string) file_get_contents($filePath));
    }

    /**
     * Pratinjau / unduh berkas SKL yang diunggah
     */
    public function previewBerkas($fileName = null)
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'));
        }

        $fileName = basename((string) $fileName);
        $filePath = FCPATH . 'uploads/graduate/skl/' . $fileName;

        if (! is_file($filePath)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan di server.');
        }

        // Keamanan: Tendik/Staf/Admin/Fakultas boleh melihat, Mahasiswa hanya berkas miliknya sendiri
        $roles   = PortalRouter::normalizeRoles($userData);
        $isStaff = AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas']);

        if (! $isStaff) {
            if (! in_array('mahasiswa', $roles, true)) {
                return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
            }

            $npm       = (string) ($userData['npm'] ?? $userData['id'] ?? '');
            $berkasRow = $this->berkasModel->where('nama_file', $fileName)->first();
            if ($berkasRow && (string) ($berkasRow['npm'] ?? '') !== $npm) {
                return AccessControl::forbiddenResponse($this->request, 'Anda tidak memiliki hak untuk melihat berkas ini.', $roles);
            }
        }

        $mime = mime_content_type($filePath) ?: 'application/pdf';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody((string) file_get_contents($filePath));
    }

    /* ------------------------------------------------------------------
     |  CRUD Persyaratan Pengambilan SKL Dinamis (Tendik/Admin/Fakultas)
     | ------------------------------------------------------------------ */

    /**
     * Tambah persyaratan SKL baru
     */
    public function storePersyaratan()
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $nama       = trim((string) $this->request->getPost('nama'));
        $keterangan = trim((string) $this->request->getPost('keterangan'));
        $tipeFile   = strtolower(trim((string) $this->request->getPost('tipe_file')));
        $maxSizeKb  = (int) $this->request->getPost('max_size_kb');
        $isWajib    = (int) $this->request->getPost('is_wajib');
        $urutan     = (int) $this->request->getPost('urutan');
        $status     = strtoupper(trim((string) $this->request->getPost('status')));

        if ($nama === '') {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Nama persyaratan SKL wajib diisi.']);
            return redirect()->to(base_url('graduate/skl_kelola'))->withInput();
        }

        $safeAllowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'zip'];
        $exts = array_filter(array_map('trim', explode(',', strtolower($tipeFile))));
        $exts = array_filter($exts, static fn ($ext) => in_array($ext, $safeAllowedExts, true));
        $tipeFile = ! empty($exts) ? implode(',', $exts) : 'pdf';

        if ($maxSizeKb <= 0) {
            $maxSizeKb = 5120;
        }

        if (! in_array($status, ['AKTIF', 'NON-AKTIF'], true)) {
            $status = 'AKTIF';
        }

        try {
            $this->persyaratanModel->insert([
                'nama'        => $nama,
                'keterangan'  => $keterangan ?: null,
                'tipe_file'   => $tipeFile,
                'max_size_kb' => $maxSizeKb,
                'is_wajib'    => $isWajib ? 1 : 0,
                'urutan'      => $urutan > 0 ? $urutan : 0,
                'status'      => $status,
            ]);

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Berhasil Menambah',
                'text'  => 'Persyaratan SKL baru "' . $nama . '" berhasil ditambahkan.',
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Menambah', 'text' => $e->getMessage()]);
        }

        return redirect()->to(base_url('graduate/skl_kelola'));
    }

    /**
     * Ubah persyaratan SKL yang sudah ada
     */
    public function updatePersyaratan()
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $id         = (int) $this->request->getPost('id');
        $nama       = trim((string) $this->request->getPost('nama'));
        $keterangan = trim((string) $this->request->getPost('keterangan'));
        $tipeFile   = strtolower(trim((string) $this->request->getPost('tipe_file')));
        $maxSizeKb  = (int) $this->request->getPost('max_size_kb');
        $isWajib    = (int) $this->request->getPost('is_wajib');
        $urutan     = (int) $this->request->getPost('urutan');
        $status     = strtoupper(trim((string) $this->request->getPost('status')));

        $persyaratan = $this->persyaratanModel->find($id);
        if ($persyaratan === null) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Tidak Ditemukan', 'text' => 'Persyaratan SKL tidak ditemukan.']);
            return redirect()->to(base_url('graduate/skl_kelola'));
        }

        if ($nama === '') {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Nama persyaratan SKL tidak boleh kosong.']);
            return redirect()->to(base_url('graduate/skl_kelola'))->withInput();
        }

        $safeAllowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'zip'];
        $exts = array_filter(array_map('trim', explode(',', strtolower($tipeFile))));
        $exts = array_filter($exts, static fn ($ext) => in_array($ext, $safeAllowedExts, true));
        $tipeFile = ! empty($exts) ? implode(',', $exts) : 'pdf';

        if ($maxSizeKb <= 0) {
            $maxSizeKb = 5120;
        }

        if (! in_array($status, ['AKTIF', 'NON-AKTIF'], true)) {
            $status = 'AKTIF';
        }

        try {
            $this->persyaratanModel->update($id, [
                'nama'        => $nama,
                'keterangan'  => $keterangan ?: null,
                'tipe_file'   => $tipeFile,
                'max_size_kb' => $maxSizeKb,
                'is_wajib'    => $isWajib ? 1 : 0,
                'urutan'      => $urutan,
                'status'      => $status,
            ]);

            // Sinkronkan nama_persyaratan pada berkas yang sudah pernah terunggah jika nama diubah
            $this->berkasModel->where('persyaratan_id', $id)->set(['nama_persyaratan' => $nama])->update();

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Pembaruan Berhasil',
                'text'  => 'Persyaratan SKL "' . $nama . '" berhasil diperbarui.',
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Memperbarui', 'text' => $e->getMessage()]);
        }

        return redirect()->to(base_url('graduate/skl_kelola'));
    }

    /**
     * Hapus persyaratan SKL
     */
    public function deletePersyaratan($id = null)
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $id = (int) ($id ?? $this->request->getPost('id'));
        $persyaratan = $this->persyaratanModel->find($id);

        if ($persyaratan === null) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Persyaratan SKL tidak ditemukan.']);
            return redirect()->to(base_url('graduate/skl_kelola'));
        }

        try {
            // Bersihkan berkas fisik dan data pada tabel berkas_pengajuan_skl
            $berkasTerkait = $this->berkasModel->where('persyaratan_id', $id)->findAll();
            foreach ($berkasTerkait as $b) {
                if (! empty($b['nama_file'])) {
                    $f = FCPATH . 'uploads/graduate/skl/' . $b['nama_file'];
                    if (is_file($f)) {
                        @unlink($f);
                    }
                }
            }
            $this->berkasModel->where('persyaratan_id', $id)->delete();

            $this->persyaratanModel->delete($id);
            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Berhasil Menghapus',
                'text'  => 'Persyaratan SKL "' . $persyaratan['nama'] . '" telah dihapus.',
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Menghapus', 'text' => $e->getMessage()]);
        }

        return redirect()->to(base_url('graduate/skl_kelola'));
    }

    /**
     * Toggle status aktif / non-aktif persyaratan SKL
     */
    public function toggleStatusPersyaratan($id = null)
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $id = (int) ($id ?? $this->request->getPost('id'));
        $persyaratan = $this->persyaratanModel->find($id);

        if ($persyaratan === null) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Persyaratan SKL tidak ditemukan.']);
            return redirect()->to(base_url('graduate/skl_kelola'));
        }

        $statusBaru = ($persyaratan['status'] === 'AKTIF') ? 'NON-AKTIF' : 'AKTIF';

        try {
            $this->persyaratanModel->update($id, ['status' => $statusBaru]);
            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Status Berubah',
                'text'  => 'Status persyaratan "' . $persyaratan['nama'] . '" diubah menjadi ' . $statusBaru . '.',
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Mengubah Status', 'text' => $e->getMessage()]);
        }

        return redirect()->to(base_url('graduate/skl_kelola'));
    }
}

