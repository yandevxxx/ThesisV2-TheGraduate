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

        if (AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin'])) {
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
        $berkasMap            = [];
        $jumlahBerkasDiunggah = 0;
        if ($pendaftaran !== null) {
            $berkasRows           = $this->berkasModel->getByPendaftaranId((int) $pendaftaran['id']);
            $jumlahBerkasDiunggah = count($berkasRows);
            $berkasMap            = $this->berkasModel->getBerkasMapByPendaftaranId((int) $pendaftaran['id']);
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

        $daftarJenisTugasAkhir = [
            'Skripsi',
            'Publikasi Artikel Ilmiah',
            'Proyek Akhir',
        ];

        return view('The-Graduate-Web/mahasiswa/pendaftaran_wisuda', [
            'title'                 => 'Pendaftaran Wisuda',
            'user'                  => $userData,
            'role'                  => $roles,
            'pendaftaran'           => $pendaftaran,
            'prefill'               => $prefill,
            'persyaratanAktif'      => $persyaratanAktif,
            'berkasMap'             => $berkasMap,
            'jumlahBerkasDiunggah'  => $jumlahBerkasDiunggah,
            'daftarDosen'           => $daftarDosen,
            'gelombangWisuda'       => $gelombangWisuda,
            'daftarSemester'        => $daftarSemester,
            'daftarJenisTugasAkhir' => $daftarJenisTugasAkhir,
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

        // Mahasiswa harus terplot di SK Yudisium untuk mendaftar wisuda
        $terplot = (new MahasiswaTerplotModel())->where('npm', $npm)->first();
        if ($terplot === null) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Akses Ditolak',
                'text'  => 'Anda belum terdaftar dalam SK Yudisium, sehingga belum dapat mengajukan pendaftaran wisuda.',
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
        $nikClean         = preg_replace('/\D/', '', $nik);
        if (strlen($nikClean) !== 16) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'NIK Tidak Valid',
                'text'  => 'NIK harus terdiri dari tepat 16 digit angka (tidak boleh kurang atau lebih).',
            ]);
            return redirect()->to(base_url('graduate/pendaftaran_wisuda'))->withInput();
        }
        $nik              = $nikClean;
        $tempatLahir      = trim((string) $this->request->getPost('tempatLahir'));
        $tanggalLahir     = trim((string) $this->request->getPost('tanggalLahir'));
        $jenisKelamin     = trim((string) $this->request->getPost('jenisKelamin'));
        $alamat           = trim((string) $this->request->getPost('alamat'));
        $noHp             = trim((string) $this->request->getPost('noHp'));
        $noTelepon        = trim((string) $this->request->getPost('noTelepon'));
        $email            = trim((string) ($this->request->getPost('email') ?? $userData['email'] ?? ''));
        // Program studi resmi mengikuti data master mahasiswa / plottingan SK Yudisium
        $prodi            = trim((string) $this->request->getPost('prodi'));
        try {
            $prodiId = $terplot['id_prodi'] ?? null;
            if (empty($prodiId)) {
                $mhsRow = (new MahasiswaModel())->where('npm', $npm)->first();
                $prodiId = $mhsRow['id_prodi'] ?? null;
            }
            if (! empty($prodiId)) {
                $prodiRow = \Config\Database::connect()->table('program_studi')->where('id', $prodiId)->get()->getRowArray();
                if ($prodiRow && ! empty($prodiRow['nama'])) {
                    $prodi = $prodiRow['nama'];
                }
            }
        } catch (\Throwable $e) {}
        $totalSks         = (int) $this->request->getPost('totalSks');
        $ipk              = (float) $this->request->getPost('ipk');
        if ($ipk < 0.0 || $ipk > 4.0) {
            $ipk = 0.0;
        }
        if ($totalSks < 0 || $totalSks > 250) {
            $totalSks = null;
        }

        $kodeSemester     = trim((string) $this->request->getPost('kode_semester'));
        if ($kodeSemester === '') {
            $semAktif     = (new SemesterGraduateModel())->getActiveSemester();
            $kodeSemester = $semAktif['kode'] ?? '2026-2';
        }
        // Tanggal yudisium resmi mengikuti plot SK Yudisium oleh Fakultas / Tendik
        $tanggalLulus     = ! empty($terplot['tanggal_sk_yudisium'])
            ? $terplot['tanggal_sk_yudisium']
            : (! empty($existing['tanggal_lulus']) ? $existing['tanggal_lulus'] : trim((string) $this->request->getPost('tanggalLulus')));
        if ($tanggalLulus === '') {
            $tanggalLulus = date('Y-m-d');
        }
        $gelombangWisuda  = trim((string) $this->request->getPost('gelombangWisuda'));
        $pembimbing1      = trim((string) $this->request->getPost('pembimbing1'));
        $pembimbing2      = trim((string) $this->request->getPost('pembimbing2'));
        $pembimbingAgama  = trim((string) $this->request->getPost('pembimbingAgama'));
        $dosenPenguji     = trim((string) $this->request->getPost('dosenPenguji'));
        $rawJudul         = (string) $this->request->getPost('judulSkripsi');
        $judulClean       = preg_replace('/\s+/', ' ', trim($rawJudul));
        $judulSkripsi     = mb_strtoupper((string) $judulClean, 'UTF-8');
        $jenisTugasAkhir  = trim((string) $this->request->getPost('jenis_tugas_akhir'));
        $validJenisTa     = ['Skripsi', 'Publikasi Artikel Ilmiah', 'Proyek Akhir'];
        if (! in_array($jenisTugasAkhir, $validJenisTa, true)) {
            $jenisTugasAkhir = 'Skripsi';
        }

        // Handle upload pas foto jika ada
        $pasFotoNama = $existing['pas_foto'] ?? null;
        $fotoFile    = $this->request->getFile('pas_foto');
        if ($fotoFile && $fotoFile->isValid() && ! $fotoFile->hasMoved()) {
            $ext = strtolower($fotoFile->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $fotoSizeKb = (int) round($fotoFile->getSizeByUnit('kb'));
                if ($fotoSizeKb <= 2048) {
                    $targetDir = FCPATH . 'uploads/graduate/foto/';
                    if (! is_dir($targetDir)) {
                        @mkdir($targetDir, 0777, true);
                    }
                    if (! empty($existing['pas_foto']) && is_file(FCPATH . $existing['pas_foto'])) {
                        @unlink(FCPATH . $existing['pas_foto']);
                    }
                    $newFotoName = 'foto_' . $npm . '_' . time() . '.' . $ext;
                    $fotoFile->move($targetDir, $newFotoName);
                    $pasFotoNama = 'uploads/graduate/foto/' . $newFotoName;
                }
            }
        }

        // Jika belum ada foto di form biodata, coba ambil dari berkas persyaratan wisuda (Pas Foto Berwarna)
        if (empty($pasFotoNama) && $existing !== null) {
            $berkasFoto = $this->berkasModel
                ->where('pendaftaran_id', (int) $existing['id'])
                ->like('nama_persyaratan', 'foto')
                ->first();
            if ($berkasFoto && !empty($berkasFoto['nama_file'])) {
                $bExt = strtolower(pathinfo($berkasFoto['nama_file'], PATHINFO_EXTENSION));
                if (in_array($bExt, ['jpg', 'jpeg', 'png'], true)) {
                    $pasFotoNama = 'uploads/graduate/berkas/' . $berkasFoto['nama_file'];
                }
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

        // Pastikan kolom jenis_tugas_akhir tersimpan jika kolom ada di database
        try {
            $db = \Config\Database::connect();
            if ($db->fieldExists('jenis_tugas_akhir', 'pendaftaran_wisuda')) {
                $dataSimpan['jenis_tugas_akhir'] = $jenisTugasAkhir;
            }
        } catch (\Throwable $e) {
            $dataSimpan['jenis_tugas_akhir'] = $jenisTugasAkhir;
        }

        try {
            if ($existing !== null) {
                $updated = $this->pendaftaranModel->update($existing['id'], $dataSimpan);
                if ($updated === false) {
                    $errors    = $this->pendaftaranModel->errors();
                    $errorText = ! empty($errors) ? implode(', ', $errors) : 'Gagal memperbarui formulir pendaftaran.';
                    session()->setFlashdata('message', [
                        'icon'  => 'error',
                        'title' => 'Gagal Mengirim',
                        'text'  => $errorText,
                    ]);
                    return redirect()->to(base_url('graduate/pendaftaran_wisuda'))->withInput();
                }
                $pendaftaranId = (int) $existing['id'];
            } else {
                $insertedId = $this->pendaftaranModel->insert($dataSimpan);
                if ($insertedId === false) {
                    $errors    = $this->pendaftaranModel->errors();
                    $errorText = ! empty($errors) ? implode(', ', $errors) : 'Gagal menyimpan formulir pendaftaran.';
                    session()->setFlashdata('message', [
                        'icon'  => 'error',
                        'title' => 'Gagal Mengirim',
                        'text'  => $errorText,
                    ]);
                    return redirect()->to(base_url('graduate/pendaftaran_wisuda'))->withInput();
                }
                $pendaftaranId = (int) $insertedId;
            }

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Pendaftaran Berhasil Dikirim',
                'text'  => 'Data formulir pendaftaran wisuda berhasil disimpan dan sedang menunggu verifikasi.',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error submit pendaftaran: ' . $e->getMessage());
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
                'message'    => 'Akses ditolak',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $npm = (string) ($userData['npm'] ?? $userData['id'] ?? '');
        if ($npm === '') {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'NPM tidak ditemukan',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // Mahasiswa harus terplot di SK Yudisium untuk mengunggah berkas
        $terplot = (new MahasiswaTerplotModel())->where('npm', $npm)->first();
        if ($terplot === null) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Anda belum terdaftar dalam SK Yudisium, sehingga belum dapat mengunggah berkas wisuda.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // Cek pendaftaran existing, jika belum ada, buat draft pendaftaran otomatis
        $pendaftaran = $this->pendaftaranModel->getByNpm($npm);
        if ($pendaftaran === null) {
            $semAktif = (new SemesterGraduateModel())->getActiveSemester();
            $prodiDraft = null;
            try {
                $prodiId = $terplot['id_prodi'] ?? null;
                if (empty($prodiId)) {
                    $mhsRow = (new MahasiswaModel())->where('npm', $npm)->first();
                    $prodiId = $mhsRow['id_prodi'] ?? null;
                }
                if (! empty($prodiId)) {
                    $prodiRow = \Config\Database::connect()->table('program_studi')->where('id', $prodiId)->get()->getRowArray();
                    if ($prodiRow && ! empty($prodiRow['nama'])) {
                        $prodiDraft = $prodiRow['nama'];
                    }
                }
            } catch (\Throwable $e) {}

            $draftId  = $this->pendaftaranModel->insert([
                'npm'               => $npm,
                'nama'              => (string) ($userData['nama'] ?? 'Mahasiswa'),
                'email'             => (string) ($userData['email'] ?? null),
                'prodi'             => $prodiDraft,
                'kode_semester'     => $semAktif['kode'] ?? '2026-2',
                'tanggal_lulus'     => $terplot['tanggal_sk_yudisium'] ?? null,
                'status_verifikasi' => 'DRAFT',
            ]);
            $pendaftaran = $this->pendaftaranModel->find($draftId);
        }

        $persyaratanId   = (int) $this->request->getPost('persyaratan_id');
        $namaPersyaratan = trim((string) $this->request->getPost('nama_persyaratan'));
        if ($namaPersyaratan === '') {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Nama dokumen persyaratan tidak valid',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        if (in_array(strtoupper((string) ($pendaftaran['status_verifikasi'] ?? '')), ['DISETUJUI', 'VALID', 'DITERIMA', 'SELESAI'], true)) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Pendaftaran wisuda Anda telah disetujui dan tidak dapat diubah lagi.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $maxAllowedKb = 5120;
        $allowedExts  = ['pdf', 'jpg', 'jpeg', 'png'];

        if ($persyaratanId > 0) {
            $syarat = (new PersyaratanWisudaModel())->find($persyaratanId);
            if ($syarat) {
                if (! empty($syarat['tipe_file'])) {
                    $customExts = array_filter(array_map('trim', explode(',', strtolower($syarat['tipe_file']))));
                    if (! empty($customExts)) {
                        $allowedExts = $customExts;
                    }
                }
                if (! empty($syarat['max_size_kb']) && (int) $syarat['max_size_kb'] > 0) {
                    $maxAllowedKb = (int) $syarat['max_size_kb'];
                }
            }
        }

        $file = $this->request->getFile('file_berkas') ?? $this->request->getFile('file');
        if (! $file || ! $file->isValid()) {
            $errString = 'Berkas tidak valid atau tidak dipilih';
            if ($file && $file->getError() === UPLOAD_ERR_INI_SIZE) {
                $errString = 'Ukuran berkas melebihi batas upload maksimal server PHP (upload_max_filesize). Harap kompres berkas Anda.';
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

        // Cek ukuran berkas sesuai ketentuan persyaratan
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

        $targetDir = FCPATH . 'uploads/graduate/berkas/';
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        $cleanPersyaratanSlug = url_title($namaPersyaratan, '_', true);
        $newFileName = 'berkas_' . $npm . '_' . $cleanPersyaratanSlug . '_' . time() . '.' . $ext;

        try {
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

            // Jika dokumen yang diunggah adalah Pas Foto, sinkronkan ke pas_foto pendaftaran_wisuda
            if (stripos($namaPersyaratan, 'foto') !== false && in_array(strtolower($ext), ['jpg', 'jpeg', 'png'], true)) {
                $this->pendaftaranModel->update($pendaftaranId, [
                    'pas_foto' => 'uploads/graduate/berkas/' . $newFileName,
                ]);
            }

            $totalBerkas = $this->berkasModel->where('pendaftaran_id', $pendaftaranId)->countAllResults();

            return $this->response->setJSON([
                'success'      => true,
                'message'      => 'Dokumen ' . $namaPersyaratan . ' berhasil diunggah',
                'berkas_id'    => $berkasId,
                'nama_file'    => $newFileName,
                'url'          => base_url('graduate/berkas/view/' . $newFileName),
                'total_berkas' => $totalBerkas,
                'csrf_token'   => csrf_token(),
                'csrf_hash'    => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal upload berkas: ' . $e->getMessage());
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Gagal menyimpan berkas ke server: ' . $e->getMessage(),
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }
    }

    /**
     * Hapus berkas persyaratan yang sudah diunggah
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
                'message'    => 'Akses ditolak',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $berkasId = (int) ($berkasId ?? $this->request->getPost('berkas_id'));
        $berkas   = $this->berkasModel->find($berkasId);
        if ($berkas === null) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Berkas tidak ditemukan',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // Validasi kepemilikan berkas
        $pendaftaran = $this->pendaftaranModel->find($berkas['pendaftaran_id']);
        $npm = (string) ($userData['npm'] ?? $userData['id'] ?? '');
        if ($pendaftaran === null || $pendaftaran['npm'] !== $npm) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Anda tidak memiliki hak untuk menghapus berkas ini',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        if (in_array(strtoupper((string) ($pendaftaran['status_verifikasi'] ?? '')), ['DISETUJUI', 'VALID', 'DITERIMA', 'SELESAI'], true)) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Berkas pendaftaran telah disetujui dan tidak dapat dihapus.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        try {
            // Hapus file fisik
            $filePath = FCPATH . 'uploads/graduate/berkas/' . $berkas['nama_file'];
            if (is_file($filePath)) {
                @unlink($filePath);
            }

            $this->berkasModel->delete($berkasId);

            $totalBerkas = $this->berkasModel->where('pendaftaran_id', (int) $pendaftaran['id'])->countAllResults();

            return $this->response->setJSON([
                'success'      => true,
                'message'      => 'Berkas ' . $berkas['nama_persyaratan'] . ' berhasil dihapus',
                'total_berkas' => $totalBerkas,
                'csrf_token'   => csrf_token(),
                'csrf_hash'    => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal hapus berkas: ' . $e->getMessage());
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Gagal menghapus berkas dari server: ' . $e->getMessage(),
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }
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
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin'])) {
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

        if ($status === 'DITOLAK' && $catatan === '') {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Catatan Wajib Diisi',
                'text'  => 'Harap berikan catatan verifikasi atau alasan perbaikan jika menolak pendaftaran.',
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

        if (strtoupper((string) ($pendaftaran['status_verifikasi'] ?? '')) === 'DRAFT') {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Pendaftaran Masih Draft',
                'text'  => 'Pendaftaran wisuda mahasiswa masih berstatus DRAFT dan belum dikirimkan oleh mahasiswa.',
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
                if (! empty($mhsRow['id_prodi'])) {
                    $prodiRow = $db->table('program_studi')->where('id', $mhsRow['id_prodi'])->get()->getRowArray();
                    if ($prodiRow) $prodi = $prodiRow['nama'];
                }
            }
        } catch (\Throwable $e) {}

        if (! empty($terplot['id_prodi'])) {
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

        // 3. Tanggal lulus dari SK Yudisium (prioritaskan penetapan resmi SK Yudisium oleh Tendik)
        $tanggalLulus = ! empty($terplot['tanggal_sk_yudisium'])
            ? $terplot['tanggal_sk_yudisium']
            : ($pendaftaran['tanggal_lulus'] ?? date('Y-m-d'));

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
            'judulSkripsi'     => mb_strtoupper(preg_replace('/\s+/', ' ', trim((string) $judulSkripsi)), 'UTF-8'),
            'jenisTugasAkhir'  => $pendaftaran['jenis_tugas_akhir'] ?? 'Skripsi',
            'pas_foto'         => $pendaftaran['pas_foto'] ?? null,
        ];
    }

    /**
     * Preview / download dokumen berkas pendaftaran wisuda secara aman.
     * Hanya pemilik berkas (mahasiswa bersangkutan) atau staf/tendik/admin yang diizinkan.
     */
    public function previewBerkas($fileName = null)
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $fileName = basename((string) ($fileName ?? $this->request->getGet('file') ?? ''));
        if ($fileName === '' || preg_match('/[^a-zA-Z0-9_\.-]/', $fileName)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Nama berkas tidak valid.');
        }

        // Cari berkas di folder berkas atau folder foto
        $filePath = FCPATH . 'uploads/graduate/berkas/' . $fileName;
        if (! is_file($filePath)) {
            $filePath = FCPATH . 'uploads/graduate/foto/' . $fileName;
        }

        if (! is_file($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Berkas tidak ditemukan.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        $isStaff = AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin']);

        if (! $isStaff) {
            if (! in_array('mahasiswa', $roles, true)) {
                return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
            }

            $npm = (string) ($userData['npm'] ?? $userData['id'] ?? '');
            $isOwner = false;

            // 1. Cek dari prefix nama file jika mengandung NPM
            if ($npm !== '' && (strpos($fileName, 'berkas_' . $npm . '_') === 0 || strpos($fileName, 'foto_' . $npm . '_') === 0)) {
                $isOwner = true;
            }

            // 2. Cek dari database berkas_pendaftaran_wisuda
            if (! $isOwner) {
                $berkasRow = $this->berkasModel->where('nama_file', $fileName)->first();
                if ($berkasRow) {
                    $pendaftaran = $this->pendaftaranModel->find($berkasRow['pendaftaran_id']);
                    if ($pendaftaran && (string) ($pendaftaran['npm'] ?? '') === $npm) {
                        $isOwner = true;
                    }
                }
            }

            // 3. Cek dari field pas_foto di pendaftaran_wisuda
            if (! $isOwner) {
                $pendaftaranFoto = $this->pendaftaranModel->where('npm', $npm)->first();
                if ($pendaftaranFoto && ! empty($pendaftaranFoto['pas_foto']) && basename($pendaftaranFoto['pas_foto']) === $fileName) {
                    $isOwner = true;
                }
            }

            if (! $isOwner) {
                return AccessControl::forbiddenResponse($this->request, 'Anda tidak memiliki hak untuk melihat berkas ini.', $roles);
            }
        }

        $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody(file_get_contents($filePath));
    }
}
