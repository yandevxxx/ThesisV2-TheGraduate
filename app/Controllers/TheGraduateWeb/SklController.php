<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Models\BerkasPengajuanSklModel;
use App\Models\MahasiswaModel;
use App\Models\MahasiswaTerplotModel;
use App\Models\PengajuanSklModel;
use App\Models\PersyaratanSklModel;

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

        // 3. Filter pencarian & status
        $filterStatus = trim((string) ($this->request->getGet('status') ?? ''));
        $kataCari     = trim((string) ($this->request->getGet('cari') ?? ''));

        $daftarPengajuan = $this->pengajuanModel->getFiltered(
            $filterStatus !== '' ? $filterStatus : null,
            $kataCari !== '' ? $kataCari : null
        );

        // Jika tabel pengajuan_skl masih kosong, pre-populate dari mahasiswa_terplot_sk_yudisium untuk demo
        if (empty($daftarPengajuan) && $filterStatus === '' && $kataCari === '') {
            $this->seedInitialPengajuanDemo();
            $daftarPengajuan = $this->pengajuanModel->getFiltered();
        }

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
            'pilihanStatus'      => $filterStatus,
            'kataCari'           => $kataCari,
            'activeTab'          => $activeTab,
        ]);
    }

    /**
     * Helper membuat data demo pengajuan awal jika tabel pengajuan masih kosong
     */
    protected function seedInitialPengajuanDemo(): void
    {
        try {
            $terplotModel = new MahasiswaTerplotModel();
            $listTerplot  = $terplotModel->orderBy('id', 'ASC')->limit(6)->findAll();

            if (empty($listTerplot)) {
                return;
            }

            $syaratRows = $this->persyaratanModel->getPersyaratanAktif();
            $statuses   = ['disetujui', 'menunggu', 'disetujui', 'ditolak', 'menunggu', 'disetujui'];

            foreach ($listTerplot as $idx => $mhs) {
                $status = $statuses[$idx % count($statuses)];
                $now    = date('Y-m-d H:i:s');

                $pengajuanId = $this->pengajuanModel->insert([
                    'npm'           => $mhs['npm'],
                    'nama'          => $mhs['nama'],
                    'id_prodi'      => $mhs['id_prodi'] ?? 1,
                    'kode_semester' => $mhs['kode_semester'] ?? 'Ganjil 2025/2026',
                    'status'        => $status,
                    'catatan'       => $status === 'ditolak' ? 'Mohon unggah ulang lembar pengesahan yang sudah bertanda tangan lengkap' : null,
                    'diajukan_pada' => $now,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);

                // Buat berkas dummy terunggah untuk simulasi tampilan
                if ($pengajuanId && ! empty($syaratRows)) {
                    foreach ($syaratRows as $sIdx => $syarat) {
                        // Tentukan apakah berkas diunggah sesuai status
                        $isUploaded = true;
                        if ($status === 'menunggu' && $sIdx > 0 && ($idx % 2 !== 0)) {
                            $isUploaded = false;
                        }
                        if ($status === 'ditolak' && $sIdx > 0) {
                            $isUploaded = false;
                        }

                        if ($isUploaded) {
                            $slug = url_title($syarat['nama'], '_', true);
                            $this->berkasModel->insert([
                                'pengajuan_id'     => $pengajuanId,
                                'npm'              => $mhs['npm'],
                                'persyaratan_id'   => $syarat['id'],
                                'nama_persyaratan' => $syarat['nama'],
                                'nama_file'        => $slug . '-' . url_title($mhs['nama'], '-', true) . '.pdf',
                                'ukuran_kb'        => 1024,
                                'created_at'       => $now,
                                'updated_at'       => $now,
                            ]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal seed demo pengajuan SKL: ' . $e->getMessage());
        }
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

