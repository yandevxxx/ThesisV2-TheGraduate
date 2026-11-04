<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Models\BerkasPendaftaranWisudaModel;
use App\Models\DosenModel;
use App\Models\MahasiswaModel;
use App\Models\MahasiswaTerplotModel;
use App\Models\PendaftaranWisudaModel;
use App\Models\PersyaratanWisudaModel;
use App\Models\SemesterGraduateModel;
use App\Models\SkripsiModel;

/**
 * Controller Pengelolaan Pendaftaran Wisuda Sisi The Graduate
 *
 * Menangani pengajuan pendaftaran wisuda oleh mahasiswa serta
 * verifikasi dan monitoring oleh tendik/admin/fakultas.
 */
class PendaftaranWisudaController extends BaseController
{
    protected PendaftaranWisudaModel $pendaftaranModel;
    protected BerkasPendaftaranWisudaModel $berkasModel;

    public function __construct()
    {
        $this->pendaftaranModel = new PendaftaranWisudaModel();
        $this->berkasModel      = new BerkasPendaftaranWisudaModel();
    }

    /**
     * Halaman utama pendaftaran wisuda.
     * Mengarahkan ke tampilan mahasiswa atau admin/tendik sesuai role aktif.
     */
    public function index()
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
            return $this->indexAdminTendik($userData, $roles);
        }

        return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
    }

    /**
     * Tampilan pendaftaran wisuda sisi Mahasiswa
     */
    protected function indexMahasiswa(array $userData, array $roles)
    {
        $npm = (string) ($userData['npm'] ?? $userData['id'] ?? '');
        if ($npm === '') {
            return view('The-Graduate-Web/mahasiswa/belum_terplot', [
                'title' => 'Akses Ditolak',
                'user'  => $userData,
                'role'  => $roles,
            ]);
        }

        // Mahasiswa harus terplot di SK Yudisium untuk mendaftar wisuda
        $terplotModel = new MahasiswaTerplotModel();
        $terplot = $terplotModel->where('npm', $npm)->first();
        if ($terplot === null) {
            return view('The-Graduate-Web/mahasiswa/belum_terplot', [
                'title' => 'Akses Ditolak',
                'user'  => $userData,
                'role'  => $roles,
            ]);
        }

        // Ambil data pendaftaran jika sudah pernah mendaftar
        $pendaftaran = $this->pendaftaranModel->getByNpm($npm);

        // Ambil daftar persyaratan wisuda aktif
        $persyaratanModel = new PersyaratanWisudaModel();
        $persyaratanAktif = $persyaratanModel->getPersyaratanAktif();

        // Ambil berkas-berkas yang sudah diunggah jika sudah ada pendaftaran
        $berkasMap = [];
        if ($pendaftaran !== null) {
            $berkasMap = $this->berkasModel->getBerkasMapByPendaftaranId((int) $pendaftaran['id']);
        }

        // Persiapkan data prefill form jika belum mendaftar atau jika ditolak (revisi)
        $prefill = $this->getMahasiswaPrefillData($npm, $userData, $terplot, $pendaftaran);

        // Daftar Dosen untuk dropdown pembimbing / penguji
        $daftarDosen = [];
        try {
            $dosenModel  = new DosenModel();
            $dosenRows   = $dosenModel->select('nama')->orderBy('nama', 'ASC')->findAll();
            $daftarDosen = array_column($dosenRows, 'nama');
        } catch (\Throwable $e) {
            $daftarDosen = [];
        }

        // Gelombang wisuda default
        $gelombangWisuda = [
            'Gelombang 1 - 15 November 2025' => 'Gelombang 1 — 15 November 2025',
            'Gelombang 2 - 15 Desember 2025' => 'Gelombang 2 — 15 Desember 2025',
            'Gelombang 1 - 15 Mei 2026'      => 'Gelombang 1 — 15 Mei 2026',
            'Gelombang 2 - 15 Juni 2026'     => 'Gelombang 2 — 15 Juni 2026',
        ];

        // Daftar semester dari database
        $semesterGradModel = new SemesterGraduateModel();
        $daftarSemester    = $semesterGradModel->orderBy('kode', 'DESC')->findAll();

        return view('The-Graduate-Web/mahasiswa/pendaftaran_wisuda', [
            'title'            => 'Pendaftaran Wisuda',
            'user'             => $userData,
            'role'             => $roles,
            'pendaftaran'      => $pendaftaran,
            'prefill'          => $prefill,
            'persyaratanAktif' => $persyaratanAktif,
            'berkasMap'        => $berkasMap,
            'daftarDosen'      => $daftarDosen,
            'gelombangWisuda'  => $gelombangWisuda,
            'daftarSemester'   => $daftarSemester,
        ]);
    }

    /**
     * Tampilan pendaftaran wisuda sisi Admin / Tendik / Fakultas
     */
    protected function indexAdminTendik(array $userData, array $roles)
    {
        $semesterGradModel = new SemesterGraduateModel();
        $daftarSemester    = $semesterGradModel->orderBy('kode', 'DESC')->findAll();

        $semesterAktifObj  = $semesterGradModel->getActiveSemester();
        $defaultSemester   = $semesterAktifObj ? $semesterAktifObj['kode'] : '';

        // Filter dari query parameters
        $filterSemester = (string) ($this->request->getGet('semester') ?? $this->request->getGet('tahunAkademik') ?? '');
        if ($filterSemester === '') {
            // Default gunakan semester aktif jika tidak ada filter eksplisit
            $filterSemester = $defaultSemester;
        }

        $filterStatus = trim((string) ($this->request->getGet('status') ?? ''));
        $kataCari     = trim((string) ($this->request->getGet('cari') ?? ''));

        $daftarPendaftaran = $this->pendaftaranModel->getFiltered(
            $filterSemester !== 'semua' ? $filterSemester : null,
            $filterStatus !== 'semua' && $filterStatus !== '' ? $filterStatus : null,
            $kataCari !== '' ? $kataCari : null
        );

        // Ambil berkas untuk masing-masing pendaftaran agar bisa ditampilkan di modal preview
        $berkasPerPendaftaran = [];
        foreach ($daftarPendaftaran as $p) {
            $berkasPerPendaftaran[$p['id']] = $this->berkasModel->getByPendaftaranId((int) $p['id']);
        }

        return view('The-Graduate-Web/admin_tendik/pendaftaran_wisuda', [
            'title'                => 'Pendaftaran Wisuda',
            'user'                 => $userData,
            'role'                 => $roles,
            'daftarPendaftaran'    => $daftarPendaftaran,
            'daftarSemester'       => $daftarSemester,
            'semesterAktif'        => $filterSemester,
            'statusFilter'         => $filterStatus,
            'kataCari'             => $kataCari,
            'berkasPerPendaftaran' => $berkasPerPendaftaran,
        ]);
    }

    /**
     * Submit formulir pendaftaran wisuda oleh mahasiswa
     */
    public function submit()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! in_array('mahasiswa', $roles, true)) {
            return AccessControl::forbiddenResponse($this->request, 'Hanya mahasiswa yang dapat mendaftar wisuda', $roles);
        }

        $npm = (string) ($userData['npm'] ?? $userData['id'] ?? '');
        if ($npm === '') {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Identitas NPM tidak ditemukan pada sesi Anda.',
            ]);
            return redirect()->to(base_url('graduate/pendaftaran_wisuda'));
        }

        // Cek pendaftaran existing
        $existing = $this->pendaftaranModel->getByNpm($npm);
        if ($existing !== null && $existing['status_verifikasi'] === 'DISETUJUI') {
            session()->setFlashdata('message', [
                'icon'  => 'info',
                'title' => 'Sudah Disetujui',
                'text'  => 'Pendaftaran wisuda Anda telah disetujui dan tidak dapat diubah.',
            ]);
            return redirect()->to(base_url('graduate/pendaftaran_wisuda'));
        }

        // Ambil data POST
        $nama             = trim((string) ($this->request->getPost('nama') ?? $userData['nama'] ?? ''));
        $nik              = trim((string) $this->request->getPost('nik'));
        $tempatLahir      = trim((string) $this->request->getPost('tempatLahir'));
        $tanggalLahir     = trim((string) $this->request->getPost('tanggalLahir'));
        $jenisKelamin     = trim((string) $this->request->getPost('jenisKelamin'));
        $alamat           = trim((string) $this->request->getPost('alamat'));
        $noHp             = trim((string) $this->request->getPost('noHp'));
        $noTelepon        = trim((string) $this->request->getPost('noTelepon'));
        $email            = trim((string) ($this->request->getPost('email') ?? $userData['email'] ?? ''));
        $prodi            = trim((string) $this->request->getPost('prodi'));
        $totalSks         = (int) $this->request->getPost('totalSks');
        $ipk              = (float) $this->request->getPost('ipk');
        $kodeSemester     = trim((string) $this->request->getPost('kode_semester'));
        if ($kodeSemester === '') {
            $semAktif     = (new SemesterGraduateModel())->getActiveSemester();
            $kodeSemester = $semAktif['kode'] ?? '2026-2';
        }
        $tanggalLulus     = trim((string) $this->request->getPost('tanggalLulus'));
        $gelombangWisuda  = trim((string) $this->request->getPost('gelombangWisuda'));
        $pembimbing1      = trim((string) $this->request->getPost('pembimbing1'));
        $pembimbing2      = trim((string) $this->request->getPost('pembimbing2'));
        $pembimbingAgama  = trim((string) $this->request->getPost('pembimbingAgama'));
        $dosenPenguji     = trim((string) $this->request->getPost('dosenPenguji'));
        $judulSkripsi     = trim((string) $this->request->getPost('judulSkripsi'));

        // Handle upload pas foto jika ada
        $pasFotoNama = $existing['pas_foto'] ?? null;
        $fotoFile    = $this->request->getFile('pas_foto');
        if ($fotoFile && $fotoFile->isValid() && ! $fotoFile->hasMoved()) {
            $ext = strtolower($fotoFile->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $targetDir = FCPATH . 'uploads/graduate/foto/';
                if (! is_dir($targetDir)) {
                    @mkdir($targetDir, 0777, true);
                }
                $newFotoName = 'foto_' . $npm . '_' . time() . '.' . $ext;
                $fotoFile->move($targetDir, $newFotoName);
                $pasFotoNama = 'uploads/graduate/foto/' . $newFotoName;
            }
        }

        $dataSimpan = [
            'npm'                => $npm,
            'nama'               => $nama,
            'nik'                => $nik ?: null,
            'tempat_lahir'       => $tempatLahir ?: null,
            'tanggal_lahir'      => $tanggalLahir ?: null,
            'jenis_kelamin'      => in_array($jenisKelamin, ['Laki-laki', 'Perempuan'], true) ? $jenisKelamin : null,
            'alamat'             => $alamat ?: null,
            'no_hp'              => $noHp ?: null,
            'no_telepon'         => $noTelepon ?: null,
            'email'              => $email ?: null,
            'prodi'              => $prodi ?: null,
            'total_sks'          => $totalSks > 0 ? $totalSks : null,
            'ipk'                => $ipk > 0 ? $ipk : null,
            'kode_semester'      => $kodeSemester,
            'tanggal_lulus'      => $tanggalLulus ?: null,
            'gelombang_wisuda'   => $gelombangWisuda ?: null,
            'pembimbing1'        => $pembimbing1 ?: null,
            'pembimbing2'        => $pembimbing2 ?: null,
            'pembimbing_agama'   => $pembimbingAgama ?: null,
            'dosen_penguji'      => $dosenPenguji ?: null,
            'judul_skripsi'      => $judulSkripsi ?: null,
            'pas_foto'           => $pasFotoNama,
            'status_verifikasi'  => 'MENUNGGU',
            'catatan_verifikasi' => null,
        ];

        try {
            if ($existing !== null) {
                $this->pendaftaranModel->update($existing['id'], $dataSimpan);
                $pendaftaranId = (int) $existing['id'];
            } else {
                $pendaftaranId = (int) $this->pendaftaranModel->insert($dataSimpan);
            }

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Pendaftaran Berhasil Dikirim',
                'text'  => 'Data formulir pendaftaran wisuda berhasil disimpan dan sedang menunggu verifikasi.',
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Mengirim',
                'text'  => 'Terjadi kesalahan saat menyimpan formulir pendaftaran: ' . $e->getMessage(),
            ]);
        }

        return redirect()->to(base_url('graduate/pendaftaran_wisuda'));
    }

    /**
     * Upload dokumen persyaratan wisuda oleh mahasiswa
     */
    public function uploadBerkas()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return $this->response->setJSON(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! in_array('mahasiswa', $roles, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak']);
        }

        $npm = (string) ($userData['npm'] ?? $userData['id'] ?? '');
        if ($npm === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'NPM tidak ditemukan']);
        }

        // Cek pendaftaran existing, jika belum ada, buat draft pendaftaran otomatis
        $pendaftaran = $this->pendaftaranModel->getByNpm($npm);
        if ($pendaftaran === null) {
            $semAktif = (new SemesterGraduateModel())->getActiveSemester();
            $draftId  = $this->pendaftaranModel->insert([
                'npm'               => $npm,
                'nama'              => (string) ($userData['nama'] ?? 'Mahasiswa'),
                'email'             => (string) ($userData['email'] ?? null),
                'kode_semester'     => $semAktif['kode'] ?? '2026-2',
                'status_verifikasi' => 'MENUNGGU',
            ]);
            $pendaftaran = $this->pendaftaranModel->find($draftId);
        }

        $persyaratanId   = (int) $this->request->getPost('persyaratan_id');
        $namaPersyaratan = trim((string) $this->request->getPost('nama_persyaratan'));
        if ($namaPersyaratan === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Nama dokumen persyaratan tidak valid']);
        }

        $file = $this->request->getFile('file_berkas') ?? $this->request->getFile('file');
        if (! $file || ! $file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Berkas tidak valid atau tidak dipilih']);
        }

        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower($file->getExtension());
        if (! in_array($ext, $allowedExts, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Format file harus PDF, JPG, atau PNG']);
        }

        // Cek ukuran maks 5 MB (5120 KB)
        $sizeKb = (int) round($file->getSizeByUnit('kb'));
        if ($sizeKb > 5120) {
            return $this->response->setJSON(['success' => false, 'message' => 'Ukuran berkas melebihi batas maksimal (5 MB)']);
        }

        $targetDir = FCPATH . 'uploads/graduate/berkas/';
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        $cleanPersyaratanSlug = url_title($namaPersyaratan, '_', true);
        $newFileName = 'berkas_' . $npm . '_' . $cleanPersyaratanSlug . '_' . time() . '.' . $ext;
        $file->move($targetDir, $newFileName);

        $pendaftaranId = (int) $pendaftaran['id'];

        // Cek apakah berkas dengan nama persyaratan ini sudah ada
        $existingBerkas = $this->berkasModel->getByPendaftaranAndNama($pendaftaranId, $namaPersyaratan);
        if ($existingBerkas !== null) {
            // Hapus file lama jika ada di disk
            $oldFilePath = $targetDir . $existingBerkas['nama_file'];
            if (is_file($oldFilePath)) {
                @unlink($oldFilePath);
            }

            $this->berkasModel->update($existingBerkas['id'], [
                'persyaratan_id'   => $persyaratanId ?: null,
                'nama_persyaratan' => $namaPersyaratan,
                'nama_file'        => $newFileName,
                'ukuran_kb'        => $sizeKb,
                'status_berkas'    => 'MENUNGGU',
            ]);
            $berkasId = (int) $existingBerkas['id'];
        } else {
            $berkasId = (int) $this->berkasModel->insert([
                'pendaftaran_id'   => $pendaftaranId,
                'persyaratan_id'   => $persyaratanId ?: null,
                'nama_persyaratan' => $namaPersyaratan,
                'nama_file'        => $newFileName,
                'ukuran_kb'        => $sizeKb,
                'status_berkas'    => 'MENUNGGU',
            ]);
        }

        return $this->response->setJSON([
            'success'   => true,
            'message'   => 'Dokumen ' . $namaPersyaratan . ' berhasil diunggah',
            'berkas_id' => $berkasId,
            'nama_file' => $newFileName,
            'url'       => base_url('uploads/graduate/berkas/' . $newFileName),
        ]);
    }

    /**
     * Hapus berkas persyaratan yang sudah diunggah
     */
    public function hapusBerkas($berkasId = null)
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return $this->response->setJSON(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! in_array('mahasiswa', $roles, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak']);
        }

        $berkasId = (int) ($berkasId ?? $this->request->getPost('berkas_id'));
        $berkas   = $this->berkasModel->find($berkasId);
        if ($berkas === null) {
            return $this->response->setJSON(['success' => false, 'message' => 'Berkas tidak ditemukan']);
        }

        // Validasi kepemilikan berkas
        $pendaftaran = $this->pendaftaranModel->find($berkas['pendaftaran_id']);
        $npm = (string) ($userData['npm'] ?? $userData['id'] ?? '');
        if ($pendaftaran === null || $pendaftaran['npm'] !== $npm) {
            return $this->response->setJSON(['success' => false, 'message' => 'Anda tidak memiliki hak untuk menghapus berkas ini']);
        }

        // Hapus file fisik
        $filePath = FCPATH . 'uploads/graduate/berkas/' . $berkas['nama_file'];
        if (is_file($filePath)) {
            @unlink($filePath);
        }

        $this->berkasModel->delete($berkasId);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Berkas ' . $berkas['nama_persyaratan'] . ' berhasil dihapus',
        ]);
    }

    /**
     * Verifikasi pendaftaran wisuda oleh tendik/admin/fakultas
     */
    public function verifikasi()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas'])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $id     = (int) $this->request->getPost('id');
        $status = strtoupper(trim((string) $this->request->getPost('status')));
        $catatan = trim((string) $this->request->getPost('catatan_verifikasi'));

        if (! in_array($status, ['DISETUJUI', 'DITOLAK', 'MENUNGGU'], true)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Status Tidak Valid',
                'text'  => 'Status verifikasi harus DISETUJUI atau DITOLAK.',
            ]);
            return redirect()->to(base_url('graduate/pendaftaran_wisuda'));
        }

        $pendaftaran = $this->pendaftaranModel->find($id);
        if ($pendaftaran === null) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Tidak Ditemukan',
                'text'  => 'Data pendaftaran wisuda tidak ditemukan.',
            ]);
            return redirect()->to(base_url('graduate/pendaftaran_wisuda'));
        }

        $updateData = [
            'status_verifikasi'  => $status,
            'catatan_verifikasi' => $catatan ?: null,
            'diverifikasi_oleh'  => (int) ($userData['id'] ?? $userData['akun_id'] ?? 1),
            'diverifikasi_pada'  => date('Y-m-d H:i:s'),
        ];

        try {
            $this->pendaftaranModel->update($id, $updateData);

            $statusText = $status === 'DISETUJUI' ? 'disetujui' : ($status === 'DITOLAK' ? 'ditolak' : 'diperbarui');
            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Verifikasi Berhasil',
                'text'  => 'Pendaftaran wisuda mahasiswa ' . $pendaftaran['nama'] . ' berhasil ' . $statusText . '.',
            ]);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Verifikasi',
                'text'  => 'Terjadi kesalahan: ' . $e->getMessage(),
            ]);
        }

        return redirect()->to(base_url('graduate/pendaftaran_wisuda'));
    }

    /**
     * Helper menyusun data prefill form pendaftaran mahasiswa
     */
    protected function getMahasiswaPrefillData(string $npm, array $userData, ?array $terplot, ?array $pendaftaran): array
    {
        $db = \Config\Database::connect();

        // 1. Data dasar dari mahasiswa/session
        $nama  = $pendaftaran['nama'] ?? $userData['nama'] ?? $terplot['nama'] ?? '';
        $email = $pendaftaran['email'] ?? $userData['email'] ?? '';
        $prodi = $pendaftaran['prodi'] ?? '';
        $sks   = $pendaftaran['total_sks'] ?? 144;
        $ipk   = $pendaftaran['ipk'] ?? 3.50;

        try {
            $mhsRow = (new MahasiswaModel())->where('npm', $npm)->first();
            if ($mhsRow) {
                if (! empty($mhsRow['nama'])) $nama = $mhsRow['nama'];
                if (! empty($mhsRow['email'])) $email = $mhsRow['email'];
                if (! empty($mhsRow['sks_lulus'])) $sks = (int) $mhsRow['sks_lulus'];
                if (! empty($mhsRow['id_prodi']) && empty($prodi)) {
                    $prodiRow = $db->table('program_studi')->where('id', $mhsRow['id_prodi'])->get()->getRowArray();
                    if ($prodiRow) $prodi = $prodiRow['nama'];
                }
            }
        } catch (\Throwable $e) {}

        if (empty($prodi) && ! empty($terplot['id_prodi'])) {
            try {
                $prodiRow = $db->table('program_studi')->where('id', $terplot['id_prodi'])->get()->getRowArray();
                if ($prodiRow) $prodi = $prodiRow['nama'];
            } catch (\Throwable $e) {}
        }

        // 2. Data skripsi & pembimbing dari SkripsiModel
        $judulSkripsi    = $pendaftaran['judul_skripsi'] ?? '';
        $pembimbing1     = $pendaftaran['pembimbing1'] ?? '';
        $pembimbing2     = $pendaftaran['pembimbing2'] ?? '';
        $pembimbingAgama = $pendaftaran['pembimbing_agama'] ?? '';

        if (empty($judulSkripsi)) {
            try {
                $skripsiList = (new SkripsiModel())->getSkripsiByNpm($npm);
                if (! empty($skripsiList) && isset($skripsiList[0])) {
                    $skripsi = $skripsiList[0];
                    $judulSkripsi    = $skripsi['judul'] ?? '';
                    $pembimbing1     = $skripsi['nama_p1'] ?? '';
                    $pembimbing2     = $skripsi['nama_p2'] ?? '';
                    $pembimbingAgama = $skripsi['nama_pagama'] ?? '';
                }
            } catch (\Throwable $e) {}
        }

        // 3. Tanggal lulus dari SK Yudisium
        $tanggalLulus = $pendaftaran['tanggal_lulus'] ?? ($terplot['tanggal_sk_yudisium'] ?? date('Y-m-d'));

        // 4. Semester
        $kodeSemester = $pendaftaran['kode_semester'] ?? ($terplot['kode_semester'] ?? '');
        if (empty($kodeSemester)) {
            $semAktif     = (new SemesterGraduateModel())->getActiveSemester();
            $kodeSemester = $semAktif['kode'] ?? '2026-2';
        }

        return [
            'npm'              => $npm,
            'nama'             => $nama,
            'nik'              => $pendaftaran['nik'] ?? '',
            'tempatLahir'      => $pendaftaran['tempat_lahir'] ?? '',
            'tanggalLahir'     => $pendaftaran['tanggal_lahir'] ?? '',
            'jenisKelamin'     => $pendaftaran['jenis_kelamin'] ?? 'Laki-laki',
            'alamat'           => $pendaftaran['alamat'] ?? '',
            'noHp'             => $pendaftaran['no_hp'] ?? '',
            'noTelepon'        => $pendaftaran['no_telepon'] ?? '',
            'email'            => $email,
            'prodi'            => $prodi ?: 'Teknik Informatika',
            'totalSks'         => $sks,
            'ipk'              => $ipk,
            'kode_semester'    => $kodeSemester,
            'tanggalLulus'     => $tanggalLulus,
            'gelombangWisuda'  => $pendaftaran['gelombang_wisuda'] ?? 'Gelombang 1 - 15 November 2025',
            'pembimbing1'      => $pembimbing1,
            'pembimbing2'      => $pembimbing2,
            'pembimbingAgama'  => $pembimbingAgama,
            'dosenPenguji'     => $pendaftaran['dosen_penguji'] ?? '',
            'judulSkripsi'     => $judulSkripsi,
            'pas_foto'         => $pendaftaran['pas_foto'] ?? null,
        ];
    }
}
