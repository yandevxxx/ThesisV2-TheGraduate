<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\GraduateInputValidator;
use App\Libraries\PortalRouter;
use App\Libraries\SecurityAuditLogger;
use App\Libraries\SecurityExceptions;
use App\Libraries\TracerStudyGate;
use App\Models\BerkasPendaftaranWisudaModel;
use App\Models\DosenModel;
use App\Models\MahasiswaModel;
use App\Models\MahasiswaTerplotModel;
use App\Models\PendaftaranWisudaModel;
use App\Models\PengajuanSklModel;
use App\Models\PersyaratanWisudaModel;
use App\Models\SemesterGraduateModel;
use App\Models\SkripsiModel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
     * Mengarahkan secara otomatis ke modul terpisah sesuai role aktif:
     * - Mahasiswa -> Biodata Mahasiswa (graduate/biodata_wisuda)
     * - Tendik / Staf / Admin -> Verifikasi Biodata (graduate/biodata_wisuda_verifikasi)
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
            return redirect()->to(base_url('graduate/biodata_wisuda'));
        }

        if (AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas'])) {
            $sem = (string) ($this->request->getGet('semester') ?? '');
            $url = base_url('graduate/biodata_wisuda_verifikasi');
            if ($sem !== '') {
                $url .= '?semester=' . urlencode($sem);
            }
            return redirect()->to($url);
        }

        return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
    }

    private function rejectBiodataInput(string $title, string $message, string $redirectUrl)
    {
        $wantsJson = (method_exists($this->request, 'isAJAX') && $this->request->isAJAX())
            || strpos(strtolower($this->request->getHeaderLine('Accept')), 'application/json') !== false;

        if ($wantsJson) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'     => 422,
                'success'    => false,
                'title'      => $title,
                'message'    => $message,
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        session()->setFlashdata('message', [
            'icon'  => 'warning',
            'title' => $title,
            'text'  => $message,
        ]);

        return redirect()->to($redirectUrl)->withInput();
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
            return redirect()->to(base_url('graduate/biodata_wisuda'));
        }

        // Mahasiswa harus terplot di SK Yudisium untuk mendaftar wisuda
        $terplot = (new MahasiswaTerplotModel())->where('npm', $npm)->first();
        if ($terplot === null) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Akses Ditolak',
                'text'  => 'Anda belum terdaftar dalam SK Yudisium, sehingga belum dapat mengajukan pendaftaran wisuda.',
            ]);
            return redirect()->to(base_url('graduate/biodata_wisuda'));
        }

        // Mahasiswa wajib mengisi Tracer Study Awal terlebih dahulu sebelum mengisi biodata
        if (! $this->hasCompletedTracerAwal($npm)) {
            return TracerStudyGate::lockedRedirect('Biodata Mahasiswa');
        }

        // Cek pendaftaran existing: jika biodata sudah disetujui, tolak perubahan sepihak
        $existing = $this->pendaftaranModel->getByNpm($npm);
        if ($existing !== null && ($existing['status_biodata'] === 'DISETUJUI' || $existing['status_verifikasi'] === 'DISETUJUI')) {
            session()->setFlashdata('message', [
                'icon'  => 'info',
                'title' => 'Biodata Sudah Disetujui',
                'text'  => 'Biodata wisuda Anda telah disetujui oleh Tendik dan terkunci. Silakan hubungi bagian akademik/tendik jika memerlukan perubahan data.',
            ]);
            return redirect()->to(base_url('graduate/biodata_wisuda'));
        }

        $fromBiodata = $this->request->getPost('from_biodata') || (strpos((string) $this->request->getServer('HTTP_REFERER'), 'biodata_wisuda') !== false);
        $redirectUrl = $fromBiodata ? base_url('graduate/biodata_wisuda') : base_url('graduate/pendaftaran_wisuda');

        // Ambil & validasi data POST dengan allowlist server-side
        $namaCheck = GraduateInputValidator::validateNama((string) ($this->request->getPost('nama') ?? $userData['nama'] ?? ''), 150);
        if (! $namaCheck['valid']) {
            return $this->rejectBiodataInput('Nama Tidak Valid', (string) $namaCheck['error'], $redirectUrl);
        }
        $nama = $namaCheck['value'];

        $nikCheck = GraduateInputValidator::validateNik((string) $this->request->getPost('nik'));
        if (! $nikCheck['valid']) {
            return $this->rejectBiodataInput('NIK Tidak Valid', (string) $nikCheck['error'], $redirectUrl);
        }
        $nik = $nikCheck['value'];

        $tempatLahir = trim(preg_replace('/[\s\x{00a0}\x{200b}]+/u', ' ', (string) $this->request->getPost('tempatLahir')) ?? '');
        if ($tempatLahir === '') {
            return $this->rejectBiodataInput('Tempat Lahir Wajib Diisi', 'Tempat lahir wajib diisi.', $redirectUrl);
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $tempatLahir) || mb_strlen($tempatLahir, 'UTF-8') > 100) {
            return $this->rejectBiodataInput('Tempat Lahir Tidak Valid', 'Tempat lahir maksimal 100 karakter dan tidak boleh mengandung karakter kontrol.', $redirectUrl);
        }

        $tahunSemester = (new SemesterGraduateModel())->getActiveSemesterYear();
        $tglLahirCheck = GraduateInputValidator::validateTanggalLahir(
            (string) $this->request->getPost('tanggalLahir'),
            $tahunSemester,
            15
        );
        if (! $tglLahirCheck['valid']) {
            return $this->rejectBiodataInput('Tanggal Lahir Tidak Valid', (string) $tglLahirCheck['error'], $redirectUrl);
        }
        $tanggalLahir = (string) ($tglLahirCheck['value'] ?? '');

        $jenisKelamin = trim((string) $this->request->getPost('jenisKelamin'));
        if (! in_array($jenisKelamin, ['Laki-laki', 'Perempuan'], true)) {
            return $this->rejectBiodataInput('Jenis Kelamin Tidak Valid', 'Silakan pilih jenis kelamin Laki-laki atau Perempuan.', $redirectUrl);
        }

        $alamat = trim((string) $this->request->getPost('alamat'));
        if ($alamat === '') {
            return $this->rejectBiodataInput('Alamat Wajib Diisi', 'Alamat lengkap sesuai KTP wajib diisi.', $redirectUrl);
        }
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $alamat) || mb_strlen($alamat, 'UTF-8') > 500) {
            return $this->rejectBiodataInput('Alamat Tidak Valid', 'Alamat maksimal 500 karakter dan tidak boleh mengandung karakter kontrol.', $redirectUrl);
        }

        $noHpCheck = GraduateInputValidator::validatePhone((string) $this->request->getPost('noHp'), true, 'Nomor HP');
        if (! $noHpCheck['valid']) {
            return $this->rejectBiodataInput('Nomor HP Tidak Valid', (string) $noHpCheck['error'], $redirectUrl);
        }
        $noHp = $noHpCheck['value'];

        $noTelpCheck = GraduateInputValidator::validatePhone((string) $this->request->getPost('noTelepon'), false, 'Nomor Telepon');
        if (! $noTelpCheck['valid']) {
            return $this->rejectBiodataInput('Nomor Telepon Tidak Valid', (string) $noTelpCheck['error'], $redirectUrl);
        }
        $noTelepon = $noTelpCheck['value'];

        $emailCheck = GraduateInputValidator::validateEmail((string) ($this->request->getPost('email') ?? $userData['email'] ?? ''), true);
        if (! $emailCheck['valid']) {
            return $this->rejectBiodataInput('Email Tidak Valid', (string) $emailCheck['error'], $redirectUrl);
        }
        $email = $emailCheck['value'];

        // Program studi resmi mengikuti data master mahasiswa / plottingan SK Yudisium
        $prodi = trim((string) $this->request->getPost('prodi'));
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
        } catch (\Throwable $e) {
        }

        $sksCheck = GraduateInputValidator::validateSks($this->request->getPost('totalSks'), true);
        if (! $sksCheck['valid']) {
            return $this->rejectBiodataInput('Total SKS Tidak Valid', (string) $sksCheck['error'], $redirectUrl);
        }
        $totalSks = $sksCheck['value'];

        $ipkCheck = GraduateInputValidator::validateIpk($this->request->getPost('ipk'), true);
        if (! $ipkCheck['valid']) {
            return $this->rejectBiodataInput('IPK Tidak Valid', (string) $ipkCheck['error'], $redirectUrl);
        }
        $ipk = $ipkCheck['value'];

        // Semester resmi mengikuti penetapan plotting SK Yudisium oleh Fakultas / Tendik
        $kodeSemester = ! empty($terplot['kode_semester'])
            ? $terplot['kode_semester']
            : trim((string) ($this->request->getPost('kode_semester') ?? ''));
        if ($kodeSemester === '') {
            $semAktif     = (new SemesterGraduateModel())->getActiveSemester();
            $kodeSemester = $semAktif['kode'] ?? '2026-2';
        }
        // Tanggal yudisium resmi mengikuti plot SK Yudisium oleh Fakultas / Tendik (tidak dibuat-buat bila belum ditetapkan)
        $tanggalLulus = ! empty($terplot['tanggal_sk_yudisium'])
            ? (string) $terplot['tanggal_sk_yudisium']
            : (! empty($existing['tanggal_lulus']) ? (string) $existing['tanggal_lulus'] : '');
        if ($tanggalLulus !== '') {
            $tglLulusCheck = GraduateInputValidator::validateDate($tanggalLulus, false, 'Tanggal Yudisium');
            $tanggalLulus  = $tglLulusCheck['valid'] ? (string) ($tglLulusCheck['value'] ?? '') : '';
        }
        $gelombangWisuda  = trim((string) ($this->request->getPost('gelombangWisuda') ?? ($existing['gelombang_wisuda'] ?? '')));
        $pembimbing1      = trim((string) (preg_replace('/\s+/', ' ', (string) $this->request->getPost('pembimbing1')) ?? ''));
        $pembimbing2      = trim((string) (preg_replace('/\s+/', ' ', (string) $this->request->getPost('pembimbing2')) ?? ''));
        $pembimbingAgama  = trim((string) (preg_replace('/\s+/', ' ', (string) $this->request->getPost('pembimbingAgama')) ?? ''));
        $dosenPenguji     = trim((string) (preg_replace('/\s+/', ' ', (string) $this->request->getPost('dosenPenguji')) ?? ''));

        if ($pembimbing1 === '' || $pembimbingAgama === '' || $dosenPenguji === '') {
            return $this->rejectBiodataInput(
                'Data Belum Lengkap',
                'Pembimbing Ilmu 1, Pembimbing Agama, dan Dosen Penguji Sidang wajib dipilih.',
                $redirectUrl
            );
        }

        // Pembimbing Ilmu 1, Pembimbing Ilmu 2, Pembimbing Agama, dan Dosen Penguji Sidang tidak boleh ada yang sama
        $dosenPilihan = array_filter([
            'Pembimbing Ilmu 1'    => $pembimbing1,
            'Pembimbing Ilmu 2'    => $pembimbing2,
            'Pembimbing Agama'     => $pembimbingAgama,
            'Dosen Penguji Sidang' => $dosenPenguji,
        ], static fn ($d) => $d !== '');
        $dosenNormalized = array_map(
            static fn (string $d): string => mb_strtolower($d, 'UTF-8'),
            $dosenPilihan
        );

        if (count($dosenNormalized) !== count(array_unique($dosenNormalized))) {
            return $this->rejectBiodataInput(
                'Dosen Tidak Boleh Sama',
                'Pembimbing Ilmu 1, Pembimbing Ilmu 2, Pembimbing Agama, dan Dosen Penguji Sidang tidak boleh ada yang sama. Silakan pilih dosen yang berbeda untuk setiap peran.',
                $redirectUrl
            );
        }
        $rawJudul   = (string) $this->request->getPost('judulSkripsi');
        $judulClean = trim((string) (preg_replace('/\s+/', ' ', $rawJudul) ?? ''));
        if ($judulClean === '') {
            return $this->rejectBiodataInput('Judul Tugas Akhir Wajib Diisi', 'Judul tugas akhir/skripsi wajib diisi.', $redirectUrl);
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $judulClean) || mb_strlen($judulClean, 'UTF-8') > 500) {
            return $this->rejectBiodataInput('Judul Tugas Akhir Tidak Valid', 'Judul tugas akhir maksimal 500 karakter dan tidak boleh mengandung karakter kontrol.', $redirectUrl);
        }
        $judulSkripsi     = mb_strtoupper($judulClean, 'UTF-8');
        $jenisTugasAkhir  = trim((string) $this->request->getPost('jenis_tugas_akhir'));
        $validJenisTa     = ['Skripsi', 'Publikasi Artikel Ilmiah', 'Proyek Akhir'];
        if (! in_array($jenisTugasAkhir, $validJenisTa, true)) {
            $jenisTugasAkhir = 'Skripsi';
        }

        // Handle upload pas foto jika ada (validasi ekstensi, MIME, dan ukuran)
        $pasFotoNama = $existing['pas_foto'] ?? null;
        $fotoFile    = $this->request->getFile('pas_foto');
        if ($fotoFile && ($fotoFile->isValid() || $fotoFile->getError() !== UPLOAD_ERR_NO_FILE) && ! $fotoFile->hasMoved()) {
            $fotoCheck = GraduateInputValidator::validateUploadedFile($fotoFile, ['jpg', 'jpeg', 'png'], 2048, 'Pas foto');
            if (! $fotoCheck['valid']) {
                $errTitle = strpos((string) $fotoCheck['error'], 'melebihi batas') !== false
                    ? 'Ukuran Pas Foto Terlalu Besar'
                    : 'Format Pas Foto Tidak Valid';
                return $this->rejectBiodataInput($errTitle, (string) $fotoCheck['error'], $redirectUrl);
            }

            $targetDir = GraduateInputValidator::graduateUploadDir('foto');
            if (! is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            if (! empty($existing['pas_foto'])) {
                GraduateInputValidator::deleteGraduateUploadFiles('foto', $existing['pas_foto']);
            }
            $newFotoName = 'foto_' . $npm . '_' . time() . '.' . $fotoCheck['ext'];
            $fotoFile->move($targetDir, $newFotoName);
            $pasFotoNama = 'uploads/graduate/foto/' . $newFotoName;
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
            'total_sks'          => ($totalSks !== null && $totalSks > 0) ? $totalSks : null,
            'ipk'                => ($ipk !== null && $ipk > 0) ? $ipk : null,
            'kode_semester'      => $kodeSemester,
            'tanggal_lulus'      => $tanggalLulus ?: null,
            'gelombang_wisuda'   => $gelombangWisuda ?: null,
            'pembimbing1'        => $pembimbing1 ?: null,
            'pembimbing2'        => $pembimbing2 ?: null,
            'pembimbing_agama'   => $pembimbingAgama ?: null,
            'dosen_penguji'      => $dosenPenguji ?: null,
            'judul_skripsi'      => $judulSkripsi ?: null,
            'pas_foto'           => $pasFotoNama,
            'status_biodata'     => 'MENUNGGU',
            'catatan_biodata'    => null,
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
                    return redirect()->to($redirectUrl)->withInput();
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
                    return redirect()->to($redirectUrl)->withInput();
                }
                $pendaftaranId = (int) $insertedId;
            }

            $this->syncOverallStatus($pendaftaranId);

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Biodata Berhasil Dikirim',
                'text'  => 'Data formulir biodata wisuda berhasil disimpan dan sedang menunggu verifikasi Tendik.',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error submit pendaftaran: ' . $e->getMessage());
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Mengirim',
                'text'  => 'Terjadi kesalahan sistem saat menyimpan formulir pendaftaran. Silakan coba lagi.',
            ]);
        }

        return redirect()->to($redirectUrl);
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

        if (! $this->hasCompletedTracerAwal($npm)) {
            return $this->response->setJSON(TracerStudyGate::lockedJsonPayload('Berkas Wisuda & Biodata'));
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
                'status_biodata'    => 'DRAFT',
                'status_berkas'     => 'DRAFT',
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

        $statusBerkas = strtoupper((string) ($pendaftaran['status_berkas'] ?? 'DRAFT'));
        $statusVerif  = strtoupper((string) ($pendaftaran['status_verifikasi'] ?? 'DRAFT'));
        if ($statusBerkas === 'DISETUJUI' || in_array($statusVerif, ['DISETUJUI', 'VALID', 'DITERIMA', 'SELESAI'], true)) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Berkas persyaratan wisuda Anda telah disetujui dan tidak dapat diubah lagi.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        if ($statusBerkas === 'MENUNGGU') {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Berkas persyaratan sedang menunggu verifikasi Tendik dan tidak dapat diubah.',
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

        $file      = $this->request->getFile('file_berkas') ?? $this->request->getFile('file');
        $fileCheck = GraduateInputValidator::validateUploadedFile($file, $allowedExts, $maxAllowedKb, 'Berkas');
        if (! $fileCheck['valid']) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => (string) $fileCheck['error'],
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $ext    = $fileCheck['ext'];
        $sizeKb = $fileCheck['sizeKb'];

        $targetDir = GraduateInputValidator::graduateUploadDir('berkas');
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $cleanPersyaratanSlug = url_title($namaPersyaratan, '_', true);
        $newFileName = 'berkas_' . $npm . '_' . $cleanPersyaratanSlug . '_' . time() . '.' . $ext;

        try {
            $file->move($targetDir, $newFileName);

            $pendaftaranId = (int) $pendaftaran['id'];

            // Cek apakah berkas dengan persyaratan ini sudah ada (utamakan persyaratan_id, fallback ke nama_persyaratan)
            $existingBerkas = null;
            if ($persyaratanId > 0) {
                $existingBerkas = $this->berkasModel->getByPendaftaranAndPersyaratan($pendaftaranId, $persyaratanId);
            }
            if ($existingBerkas === null) {
                $existingBerkas = $this->berkasModel->getByPendaftaranAndNama($pendaftaranId, $namaPersyaratan);
            }
            if ($existingBerkas !== null) {
                // Hapus file lama jika ada di disk
                GraduateInputValidator::deleteGraduateUploadFiles('berkas', (string) ($existingBerkas['nama_file'] ?? ''));

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

            // Jika dokumen yang diunggah adalah Pas Foto berformat gambar valid, sinkronkan ke pas_foto pendaftaran_wisuda
            if (stripos($namaPersyaratan, 'foto') !== false && in_array($fileCheck['mime'], ['image/jpeg', 'image/pjpeg', 'image/png'], true)) {
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
                'message'    => 'Terjadi kesalahan sistem saat menyimpan berkas ke server.',
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

        $npm = TracerStudyGate::resolveNpm($userData);
        if ($npm === '' || (new MahasiswaTerplotModel())->where('npm', $npm)->first() === null) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Anda belum terdaftar dalam SK Yudisium, sehingga belum dapat mengelola berkas wisuda.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        if (! $this->hasCompletedTracerAwal($npm)) {
            return $this->response->setJSON(TracerStudyGate::lockedJsonPayload('Berkas Wisuda'));
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
        if ($pendaftaran === null || $pendaftaran['npm'] !== $npm) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Anda tidak memiliki hak untuk menghapus berkas ini',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $statusBerkas = strtoupper((string) ($pendaftaran['status_berkas'] ?? 'DRAFT'));
        $statusVerif  = strtoupper((string) ($pendaftaran['status_verifikasi'] ?? 'DRAFT'));
        if ($statusBerkas === 'DISETUJUI' || in_array($statusVerif, ['DISETUJUI', 'VALID', 'DITERIMA', 'SELESAI'], true)) {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Berkas pendaftaran telah disetujui dan tidak dapat dihapus.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        if ($statusBerkas === 'MENUNGGU') {
            return $this->response->setJSON([
                'success'    => false,
                'message'    => 'Berkas persyaratan sedang dalam antrean verifikasi Tendik dan tidak dapat dihapus.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        try {
            // Hapus file fisik (WRITEPATH maupun legacy FCPATH)
            GraduateInputValidator::deleteGraduateUploadFiles('berkas', (string) ($berkas['nama_file'] ?? ''));

            $this->berkasModel->delete($berkasId);

            // Jika berkas yang dihapus adalah Pas Foto, reset field pas_foto di pendaftaran_wisuda
            if (! empty($pendaftaran['pas_foto']) && (basename($pendaftaran['pas_foto']) === $berkas['nama_file'] || stripos($berkas['nama_persyaratan'], 'foto') !== false)) {
                $this->pendaftaranModel->update((int) $pendaftaran['id'], [
                    'pas_foto' => null,
                ]);
            }

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
                'message'    => 'Terjadi kesalahan sistem saat menghapus berkas dari server.',
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
            return redirect()->to(base_url('graduate/biodata_wisuda'));
        }

        if ($status === 'DITOLAK' && $catatan === '') {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Catatan Wajib Diisi',
                'text'  => 'Harap berikan catatan verifikasi atau alasan perbaikan jika menolak pendaftaran.',
            ]);
            return redirect()->to(base_url('graduate/biodata_wisuda'));
        }

        $pendaftaran = $this->pendaftaranModel->find($id);
        if ($pendaftaran === null) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Tidak Ditemukan',
                'text'  => 'Data pendaftaran wisuda tidak ditemukan.',
            ]);
            return redirect()->to(base_url('graduate/biodata_wisuda'));
        }

        if (strtoupper((string) ($pendaftaran['status_verifikasi'] ?? '')) === 'DRAFT') {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Pendaftaran Masih Draft',
                'text'  => 'Pendaftaran wisuda mahasiswa masih berstatus DRAFT dan belum dikirimkan oleh mahasiswa.',
            ]);
            return redirect()->to(base_url('graduate/biodata_wisuda'));
        }

        $updateData = [
            'status_biodata'     => $status,
            'status_berkas'      => $status,
            'status_verifikasi'  => $status,
            'catatan_verifikasi' => $catatan ?: null,
            'diverifikasi_oleh'  => (int) ($userData['id'] ?? $userData['akun_id'] ?? 1),
            'diverifikasi_pada'  => date('Y-m-d H:i:s'),
        ];

        try {
            $this->pendaftaranModel->update($id, $updateData);
            $this->syncOverallStatus($id);

            $statusText = $status === 'DISETUJUI' ? 'disetujui' : ($status === 'DITOLAK' ? 'ditolak' : 'diperbarui');
            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Verifikasi Berhasil',
                'text'  => 'Pendaftaran wisuda mahasiswa ' . $pendaftaran['nama'] . ' berhasil ' . $statusText . '.',
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[PendaftaranWisudaController] ' . $e->getMessage());
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Memproses',
                'text'  => 'Terjadi kesalahan sistem saat memproses data. Silakan coba lagi.',
            ]);
        }

        return redirect()->to(base_url('graduate/biodata_wisuda'));
    }

    /**
     * Helper menyusun data prefill form pendaftaran mahasiswa
     */
    protected function getMahasiswaPrefillData(string $npm, array $userData, ?array $terplot, ?array $pendaftaran): array
    {
        $db = \Config\Database::connect();

        // 1. Data dasar: prioritaskan data yang sudah pernah diisi/disimpan mahasiswa pada pendaftaran
        $nama  = ! empty($pendaftaran['nama']) ? (string) $pendaftaran['nama'] : '';
        $email = ! empty($pendaftaran['email']) ? (string) $pendaftaran['email'] : '';
        $prodi = ! empty($pendaftaran['prodi']) ? (string) $pendaftaran['prodi'] : '';
        $sks   = (isset($pendaftaran['total_sks']) && $pendaftaran['total_sks'] !== null && (int) $pendaftaran['total_sks'] > 0)
            ? (int) $pendaftaran['total_sks']
            : null;
        $ipk   = (isset($pendaftaran['ipk']) && $pendaftaran['ipk'] !== null && (float) $pendaftaran['ipk'] > 0)
            ? (float) $pendaftaran['ipk']
            : null;

        try {
            $mhsRow = (new MahasiswaModel())->where('npm', $npm)->first();
            if ($mhsRow) {
                if ($nama === '' && ! empty($mhsRow['nama'])) $nama = (string) $mhsRow['nama'];
                if ($email === '' && ! empty($mhsRow['email'])) $email = (string) $mhsRow['email'];
                if ($sks === null && ! empty($mhsRow['sks_lulus'])) $sks = (int) $mhsRow['sks_lulus'];
                if ($prodi === '' && ! empty($mhsRow['id_prodi'])) {
                    $prodiRow = $db->table('program_studi')->where('id', $mhsRow['id_prodi'])->get()->getRowArray();
                    if ($prodiRow && ! empty($prodiRow['nama'])) $prodi = (string) $prodiRow['nama'];
                }
            }
        } catch (\Throwable $e) {}

        if ($nama === '') {
            $nama = (string) ($userData['nama'] ?? ($terplot['nama'] ?? ''));
        }
        if ($email === '') {
            $email = (string) ($userData['email'] ?? '');
        }
        if ($sks === null) {
            $sks = 144;
        }
        if ($ipk === null) {
            $ipk = 3.50;
        }

        if ($prodi === '' && ! empty($terplot['id_prodi'])) {
            try {
                $prodiRow = $db->table('program_studi')->where('id', $terplot['id_prodi'])->get()->getRowArray();
                if ($prodiRow && ! empty($prodiRow['nama'])) $prodi = (string) $prodiRow['nama'];
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
                    if (empty($pembimbing1)) $pembimbing1 = $skripsi['nama_p1'] ?? '';
                    if (empty($pembimbing2)) $pembimbing2 = $skripsi['nama_p2'] ?? '';
                    if (empty($pembimbingAgama)) $pembimbingAgama = $skripsi['nama_pagama'] ?? '';
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
            'gelombangWisuda'  => $pendaftaran['gelombang_wisuda'] ?? '',
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

    /**
     * Sinkronisasi status_verifikasi keseluruhan berdasarkan status_biodata dan status_berkas.
     */
    public function syncOverallStatus(int $id): void
    {
        $row = $this->pendaftaranModel->find($id);
        if (! $row) {
            return;
        }

        $sBio = strtoupper((string) ($row['status_biodata'] ?? 'DRAFT'));
        $sBer = strtoupper((string) ($row['status_berkas'] ?? 'DRAFT'));

        if ($sBio === 'DISETUJUI' && $sBer === 'DISETUJUI') {
            $overall = 'DISETUJUI';
        } elseif ($sBio === 'DITOLAK' || $sBer === 'DITOLAK') {
            $overall = 'DITOLAK';
        } elseif ($sBio === 'MENUNGGU' || $sBer === 'MENUNGGU') {
            $overall = 'MENUNGGU';
        } else {
            $overall = 'DRAFT';
        }

        $this->pendaftaranModel->update($id, ['status_verifikasi' => $overall]);
    }

    /**
     * Tampilan pengisian biodata wisuda sisi Mahasiswa
     */
    public function biodata()
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
            $terplot = null;
            $blocked = TracerStudyGate::guardMahasiswaView(
                $userData,
                $roles,
                'Form Tracer Study Awal dan Form Pendaftaran Wisuda',
                'Biodata Mahasiswa',
                $terplot
            );
            if ($blocked !== null) {
                return $blocked;
            }

            $npm = TracerStudyGate::resolveNpm($userData);

            $pendaftaran = $this->pendaftaranModel->getByNpm($npm);
            $prefill = $this->getMahasiswaPrefillData($npm, $userData, $terplot, $pendaftaran);

            $dosenModel  = new DosenModel();
            $daftarDosen = array_column($dosenModel->findAll(), 'nama');

            $daftarSemester = (new SemesterGraduateModel())->orderBy('kode', 'DESC')->findAll();

            return view('The-Graduate-Web/mahasiswa/biodata_wisuda', [
                'title'                 => 'Biodata Mahasiswa',
                'user'                  => $userData,
                'role'                  => $roles,
                'pendaftaran'           => $pendaftaran,
                'prefill'               => $prefill,
                'daftarDosen'           => $daftarDosen,
                'daftarSemester'        => $daftarSemester,
                'daftarJenisTugasAkhir' => ['Skripsi', 'Publikasi Artikel Ilmiah', 'Proyek Akhir'],
            ]);
        }

        if (AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas'])) {
            return redirect()->to(base_url('graduate/biodata_wisuda_verifikasi'));
        }

        return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
    }

    /**
     * Memeriksa apakah mahasiswa sudah mengirim jawaban Tracer Study Awal.
     */
    protected function hasCompletedTracerAwal(string $npm): bool
    {
        return TracerStudyGate::hasCompletedTracerAwal($npm);
    }

    /**
     * Mengambil data pengajuan SKL terbaru berdasarkan NPM mahasiswa.
     */
    public function getPengajuanSklByNpm(string $npm): ?array
    {
        if ($npm === '') {
            return null;
        }

        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('pengajuan_skl')) {
                return null;
            }

            return (new PengajuanSklModel())->getByNpm($npm);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Memeriksa apakah mahasiswa telah membuat pengajuan SKL dan pengajuan tersebut sudah disetujui.
     */
    public function isSklApproved(string $npm): bool
    {
        $pengajuan = $this->getPengajuanSklByNpm($npm);
        if ($pengajuan === null) {
            return false;
        }

        return strtolower(trim((string) ($pengajuan['status'] ?? ''))) === 'disetujui';
    }

    /**
     * Endpoint submit biodata wisuda
     */
    public function submitBiodata()
    {
        return $this->submit();
    }

    /**
     * Tampilan pengunggahan berkas persyaratan wisuda sisi Mahasiswa
     */
    public function berkasWisuda()
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
            $blocked = TracerStudyGate::guardMahasiswaView(
                $userData,
                $roles,
                'Form Tracer Study Awal dan Form Pendaftaran Wisuda',
                'Berkas Wisuda'
            );
            if ($blocked !== null) {
                return $blocked;
            }

            $npm           = TracerStudyGate::resolveNpm($userData);
            $pendaftaran   = $this->pendaftaranModel->getByNpm($npm);
            $pengajuanSkl  = $this->getPengajuanSklByNpm($npm);
            $statusSkl     = strtolower(trim((string) ($pengajuanSkl['status'] ?? '')));
            $isSklApproved = ($statusSkl === 'disetujui');

            $persyaratanModel = new PersyaratanWisudaModel();
            $persyaratanAktif = $persyaratanModel->getPersyaratanAktif();

            $berkasMap            = [];
            $jumlahBerkasDiunggah = 0;
            if ($pendaftaran !== null) {
                $berkasRows           = $this->berkasModel->getByPendaftaranId((int) $pendaftaran['id']);
                $jumlahBerkasDiunggah = count($berkasRows);
                $berkasMap            = $this->berkasModel->getBerkasMapByPendaftaranId((int) $pendaftaran['id']);
            }

            return view('The-Graduate-Web/mahasiswa/berkas_wisuda', [
                'title'                => 'Berkas Persyaratan Wisuda',
                'user'                 => $userData,
                'role'                 => $roles,
                'pendaftaran'          => $pendaftaran,
                'persyaratanAktif'     => $persyaratanAktif,
                'berkasMap'            => $berkasMap,
                'jumlahBerkasDiunggah' => $jumlahBerkasDiunggah,
                'pengajuanSkl'         => $pengajuanSkl,
                'statusSkl'            => $statusSkl,
                'isSklApproved'        => $isSklApproved,
            ]);
        }

        if (AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas'])) {
            return redirect()->to(base_url('graduate/berkas_wisuda_verifikasi'));
        }

        return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
    }

    /**
     * Submit berkas persyaratan wisuda oleh mahasiswa
     */
    public function submitBerkas()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! in_array('mahasiswa', $roles, true)) {
            return AccessControl::forbiddenResponse($this->request, 'Hanya mahasiswa yang dapat mengirim berkas wisuda', $roles);
        }

        $npm = TracerStudyGate::resolveNpm($userData);
        if ($npm === '' || (new MahasiswaTerplotModel())->where('npm', $npm)->first() === null) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Akses Ditolak',
                'text'  => 'Anda belum terdaftar dalam SK Yudisium, sehingga belum dapat mengirim berkas wisuda.',
            ]);
            return redirect()->to(base_url('graduate/berkas_wisuda'));
        }

        if (! $this->hasCompletedTracerAwal($npm)) {
            return TracerStudyGate::lockedRedirect('Berkas Wisuda');
        }

        if (! $this->isSklApproved($npm)) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Pengajuan SKL Belum Selesai',
                'text'  => 'Anda belum dapat mengirim berkas wisuda. Harap selesaikan pengajuan SKL terlebih dahulu hingga disetujui.',
            ]);
            return redirect()->to(base_url('graduate/berkas_wisuda'));
        }

        $pendaftaran = $this->pendaftaranModel->getByNpm($npm);
        if ($pendaftaran === null) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Belum Ada Berkas',
                'text'  => 'Anda belum mengunggah dokumen persyaratan wisuda apapun.',
            ]);
            return redirect()->to(base_url('graduate/berkas_wisuda'));
        }

        $berkasRows = $this->berkasModel->getByPendaftaranId((int) $pendaftaran['id']);
        if (empty($berkasRows)) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Berkas Belum Diunggah',
                'text'  => 'Harap unggah minimal dokumen persyaratan yang wajib sebelum mengirim berkas.',
            ]);
            return redirect()->to(base_url('graduate/berkas_wisuda'));
        }

        // Cek persyaratan wajib
        $persyaratanModel = new PersyaratanWisudaModel();
        $persyaratanAktif = $persyaratanModel->getPersyaratanAktif();
        $berkasMap        = $this->berkasModel->getBerkasMapByPendaftaranId((int) $pendaftaran['id']);

        $missingWajib = [];
        foreach ($persyaratanAktif as $syarat) {
            if (! empty($syarat['is_wajib'])) {
                $nama = $syarat['nama'];
                $idS  = $syarat['id'];
                if (! isset($berkasMap[$nama]) && ! isset($berkasMap['id_' . $idS])) {
                    $missingWajib[] = $nama;
                }
            }
        }

        if (! empty($missingWajib)) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Berkas Wajib Belum Lengkap',
                'text'  => 'Harap lengkapi dokumen wajib berikut: ' . implode(', ', $missingWajib),
            ]);
            return redirect()->to(base_url('graduate/berkas_wisuda'));
        }

        // Update status_berkas menjadi MENUNGGU
        $this->pendaftaranModel->update($pendaftaran['id'], [
            'status_berkas'  => 'MENUNGGU',
            'catatan_berkas' => null,
        ]);

        // Update status berkas di berkas_pendaftaran_wisuda
        $this->berkasModel->where('pendaftaran_id', (int) $pendaftaran['id'])
            ->set(['status_berkas' => 'MENUNGGU'])
            ->update();

        $this->syncOverallStatus((int) $pendaftaran['id']);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berkas Berhasil Dikirim',
            'text'  => 'Seluruh berkas persyaratan wisuda berhasil dikirimkan dan sedang menunggu verifikasi Tendik.',
        ]);

        return redirect()->to(base_url('graduate/berkas_wisuda'));
    }

    /**
     * Tampilan antrean verifikasi Biodata Wisuda oleh Tendik / Staf / Admin
     */
    public function indexBiodataTendik()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas'])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        session()->set('active_side', 'graduate');

        $semModel       = new SemesterGraduateModel();
        $daftarSemester = $semModel->orderBy('kode', 'DESC')->findAll();
        $semAktif       = $semModel->getActiveSemester();

        $filterSem    = $this->request->getGet('semester') ?? ($semAktif['kode'] ?? 'semua');
        $filterStatus = $this->request->getGet('status') ?? '';
        $filterCari   = trim((string) ($this->request->getGet('cari') ?? ''));

        $daftarBiodata = $this->pendaftaranModel->getFilteredBiodata(
            $filterSem === 'semua' ? null : $filterSem,
            $filterStatus === '' || $filterStatus === 'semua' ? null : $filterStatus,
            $filterCari !== '' ? $filterCari : null
        );

        $metrics = [
            'total'     => count($daftarBiodata),
            'menunggu'  => count(array_filter($daftarBiodata, static fn($b) => ($b['status_biodata'] ?? '') === 'MENUNGGU')),
            'disetujui' => count(array_filter($daftarBiodata, static fn($b) => ($b['status_biodata'] ?? '') === 'DISETUJUI')),
            'ditolak'   => count(array_filter($daftarBiodata, static fn($b) => ($b['status_biodata'] ?? '') === 'DITOLAK')),
        ];

        $currentUrlWithQuery = current_url() . (! empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');

        return view('The-Graduate-Web/admin_tendik/biodata_wisuda', [
            'title'               => 'Verifikasi Biodata Wisuda',
            'user'                => $userData,
            'role'                => $roles,
            'daftarBiodata'       => $daftarBiodata,
            'daftarSemester'      => $daftarSemester,
            'semesterAktif'       => $filterSem,
            'statusFilter'        => $filterStatus,
            'kataCari'            => $filterCari,
            'metrics'             => $metrics,
            'currentUrlWithQuery' => $currentUrlWithQuery,
            'exportColumns'       => self::biodataExportColumns(),
        ]);
    }

    /**
     * Verifikasi Biodata Wisuda oleh Tendik / Staf / Admin
     */
    public function verifikasiBiodata()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin'])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $id      = (int) $this->request->getPost('id');
        $status  = strtoupper(trim((string) $this->request->getPost('status')));
        $catatan = trim((string) $this->request->getPost('catatan_biodata'));

        $redirectUrl = $this->resolveBiodataVerifikasiRedirect($this->request->getPost('redirect_to'));

        if (! in_array($status, ['DISETUJUI', 'DITOLAK', 'MENUNGGU'], true)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Status Tidak Valid',
                'text'  => 'Status verifikasi biodata harus DISETUJUI, DITOLAK, atau MENUNGGU.',
            ]);
            return redirect()->to($redirectUrl);
        }

        if ($status === 'DITOLAK' && $catatan === '') {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Catatan Wajib Diisi',
                'text'  => 'Harap berikan catatan verifikasi atau alasan perbaikan jika menolak biodata mahasiswa.',
            ]);
            return redirect()->to($redirectUrl);
        }

        $pendaftaran = $this->pendaftaranModel->find($id);
        if ($pendaftaran === null) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Tidak Ditemukan',
                'text'  => 'Data pendaftaran wisuda tidak ditemukan.',
            ]);
            return redirect()->to($redirectUrl);
        }

        $updateData = [
            'status_biodata'    => $status,
            'catatan_biodata'   => $catatan ?: null,
            'diverifikasi_oleh' => (int) ($userData['id'] ?? $userData['akun_id'] ?? 1),
            'diverifikasi_pada' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->pendaftaranModel->update($id, $updateData);
            $this->syncOverallStatus($id);

            $statusText = $status === 'DISETUJUI' ? 'disetujui' : ($status === 'DITOLAK' ? 'ditolak' : 'dikembalikan ke status Menunggu');
            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Verifikasi Biodata Berhasil',
                'text'  => 'Biodata wisuda mahasiswa ' . $pendaftaran['nama'] . ' berhasil ' . $statusText . '.',
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[PendaftaranWisudaController] ' . $e->getMessage());
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Memproses',
                'text'  => 'Terjadi kesalahan sistem saat memproses data. Silakan coba lagi.',
            ]);
        }

        return redirect()->to($redirectUrl);
    }

    /**
     * Definisi kolom biodata yang dapat dipilih untuk ekspor Excel oleh Tendik/Admin.
     *
     * @return array<string, array{label: string, group: string, type: string, default: bool, wrap?: bool}>
     */
    public static function biodataExportColumns(): array
    {
        return [
            'npm' => [
                'label'   => 'NPM',
                'group'   => 'Data Diri',
                'type'    => 'string',
                'default' => true,
            ],
            'nama' => [
                'label'   => 'Nama Lengkap',
                'group'   => 'Data Diri',
                'type'    => 'string',
                'default' => true,
            ],
            'nik' => [
                'label'   => 'NIK (KTP)',
                'group'   => 'Data Diri',
                'type'    => 'string',
                'default' => true,
            ],
            'tempat_lahir' => [
                'label'   => 'Tempat Lahir',
                'group'   => 'Data Diri',
                'type'    => 'string',
                'default' => true,
            ],
            'tanggal_lahir' => [
                'label'   => 'Tanggal Lahir',
                'group'   => 'Data Diri',
                'type'    => 'date',
                'default' => true,
            ],
            'jenis_kelamin' => [
                'label'   => 'Jenis Kelamin',
                'group'   => 'Data Diri',
                'type'    => 'string',
                'default' => true,
            ],
            'no_hp' => [
                'label'   => 'No. HP / WA',
                'group'   => 'Data Diri',
                'type'    => 'string',
                'default' => true,
            ],
            'no_telepon' => [
                'label'   => 'No. Telepon Alternatif',
                'group'   => 'Data Diri',
                'type'    => 'string',
                'default' => false,
            ],
            'email' => [
                'label'   => 'E-mail',
                'group'   => 'Data Diri',
                'type'    => 'string',
                'default' => true,
            ],
            'alamat' => [
                'label'   => 'Alamat Lengkap',
                'group'   => 'Data Diri',
                'type'    => 'string',
                'default' => true,
                'wrap'    => true,
            ],
            'prodi' => [
                'label'   => 'Program Studi',
                'group'   => 'Data Akademik',
                'type'    => 'string',
                'default' => true,
            ],
            'total_sks' => [
                'label'   => 'Total SKS',
                'group'   => 'Data Akademik',
                'type'    => 'int',
                'default' => true,
            ],
            'ipk' => [
                'label'   => 'IPK',
                'group'   => 'Data Akademik',
                'type'    => 'decimal',
                'default' => true,
            ],
            'kode_semester' => [
                'label'   => 'Semester Kelulusan',
                'group'   => 'Data Akademik',
                'type'    => 'semester',
                'default' => true,
            ],
            'tanggal_lulus' => [
                'label'   => 'Tanggal Yudisium',
                'group'   => 'Data Akademik',
                'type'    => 'date',
                'default' => true,
            ],
            'jenis_tugas_akhir' => [
                'label'   => 'Jenis Tugas Akhir',
                'group'   => 'Data Akademik',
                'type'    => 'string',
                'default' => true,
            ],
            'judul_skripsi' => [
                'label'   => 'Judul Tugas Akhir',
                'group'   => 'Data Akademik',
                'type'    => 'string',
                'default' => true,
                'wrap'    => true,
            ],
            'pembimbing1' => [
                'label'   => 'Pembimbing Ilmu 1',
                'group'   => 'Data Akademik',
                'type'    => 'string',
                'default' => false,
            ],
            'pembimbing2' => [
                'label'   => 'Pembimbing Ilmu 2',
                'group'   => 'Data Akademik',
                'type'    => 'string',
                'default' => false,
            ],
            'pembimbing_agama' => [
                'label'   => 'Pembimbing Agama',
                'group'   => 'Data Akademik',
                'type'    => 'string',
                'default' => false,
            ],
            'dosen_penguji' => [
                'label'   => 'Dosen Penguji Sidang',
                'group'   => 'Data Akademik',
                'type'    => 'string',
                'default' => false,
                'wrap'    => true,
            ],
            'status_biodata' => [
                'label'   => 'Status Biodata',
                'group'   => 'Verifikasi',
                'type'    => 'string',
                'default' => true,
            ],
            'catatan_biodata' => [
                'label'   => 'Catatan Verifikasi Biodata',
                'group'   => 'Verifikasi',
                'type'    => 'string',
                'default' => false,
                'wrap'    => true,
            ],
            'status_berkas' => [
                'label'   => 'Status Berkas',
                'group'   => 'Verifikasi',
                'type'    => 'string',
                'default' => false,
            ],
            'status_verifikasi' => [
                'label'   => 'Status Verifikasi Keseluruhan',
                'group'   => 'Verifikasi',
                'type'    => 'string',
                'default' => false,
            ],
            'diverifikasi_pada' => [
                'label'   => 'Waktu Verifikasi',
                'group'   => 'Verifikasi',
                'type'    => 'datetime',
                'default' => false,
            ],
        ];
    }

    /**
     * Ekspor data Biodata Mahasiswa terfilter ke berkas Excel (.xlsx)
     * sesuai daftar kolom yang dipilih oleh Tendik/Staf/Admin/Fakultas.
     */
    public function exportBiodataExcel()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas'])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $redirectUrl = $this->resolveBiodataVerifikasiRedirect((string) $this->request->getGet('redirect_to'));

        $catalog       = self::biodataExportColumns();
        $requestedCols = $this->request->getGet('fields');
        $requestedCols = is_array($requestedCols) ? $requestedCols : [];
        $selectedKeys  = array_values(array_intersect(array_keys($catalog), array_map('strval', $requestedCols)));
        $includeNo     = (string) ($this->request->getGet('include_no') ?? '1') === '1';

        if ($selectedKeys === []) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Pilih Minimal 1 Data',
                'text'  => 'Harap pilih minimal satu kolom data biodata mahasiswa untuk diunduh.',
            ]);
            return redirect()->to($redirectUrl);
        }

        $semModel  = new SemesterGraduateModel();
        $semAktif  = $semModel->getActiveSemester();
        $filterSem = (string) ($this->request->getGet('semester') ?? ($semAktif['kode'] ?? 'semua'));
        $filterSt  = (string) ($this->request->getGet('status') ?? '');
        $filterQ   = trim((string) ($this->request->getGet('cari') ?? ''));

        $rows = $this->pendaftaranModel->getFilteredBiodata(
            $filterSem === 'semua' ? null : $filterSem,
            $filterSt === '' || strtolower($filterSt) === 'semua' ? null : $filterSt,
            $filterQ !== '' ? $filterQ : null
        );

        if ($rows === []) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Tidak Ada Data',
                'text'  => 'Tidak ada data biodata mahasiswa yang sesuai dengan filter yang dipilih.',
            ]);
            return redirect()->to($redirectUrl);
        }

        $spreadsheet = $this->buildBiodataSpreadsheet($rows, $selectedKeys, $includeNo);
        $writer      = new Xlsx($spreadsheet);

        $semSlug  = ($filterSem !== '' && strtolower($filterSem) !== 'semua')
            ? preg_replace('/[^A-Za-z0-9_-]/', '', $filterSem)
            : 'SemuaSemester';
        $filename = 'Biodata_Wisuda_' . ($semSlug !== '' ? $semSlug : 'Export') . '_' . date('Ymd_His') . '.xlsx';

        ob_start();
        try {
            $writer->save('php://output');
            $binary = (string) ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment;filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($binary);
    }

    /**
     * Menyusun objek Spreadsheet berisi kolom biodata wisuda terpilih.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param string[]                         $selectedKeys
     */
    public function buildBiodataSpreadsheet(array $rows, array $selectedKeys, bool $includeNo = true): Spreadsheet
    {
        $catalog     = self::biodataExportColumns();
        $validKeys   = array_values(array_intersect(array_keys($catalog), $selectedKeys));
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Biodata Wisuda');

        $headers = [];
        if ($includeNo) {
            $headers[] = ['key' => '_no', 'label' => 'No', 'type' => 'no', 'wrap' => false];
        }
        foreach ($validKeys as $key) {
            $headers[] = array_merge(['key' => $key, 'wrap' => false], $catalog[$key]);
        }

        $totalCols  = count($headers);
        $lastColStr = Coordinate::stringFromColumnIndex($totalCols);
        $maxLengths = array_fill(1, $totalCols, 10);

        foreach ($headers as $idx => $colMeta) {
            $colNum = $idx + 1;
            $coord  = Coordinate::stringFromColumnIndex($colNum) . '1';
            $sheet->setCellValueExplicit($coord, $colMeta['label'], DataType::TYPE_STRING);
            $len                  = function_exists('mb_strlen') ? mb_strlen($colMeta['label']) : strlen($colMeta['label']);
            $maxLengths[$colNum]  = max($maxLengths[$colNum], $len);
        }

        $sheet->getStyle("A1:{$lastColStr}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1C476C'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $rowNum = 2;
        $seq    = 1;
        foreach ($rows as $row) {
            foreach ($headers as $idx => $colMeta) {
                $colNum = $idx + 1;
                $coord  = Coordinate::stringFromColumnIndex($colNum) . $rowNum;

                if ($colMeta['key'] === '_no') {
                    $sheet->setCellValueExplicit($coord, $seq, DataType::TYPE_NUMERIC);
                    $sheet->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                } else {
                    $val = $this->formatBiodataExportValue($row, $colMeta['key'], $colMeta['type']);

                    if ($colMeta['type'] === 'int' && is_int($val)) {
                        $sheet->setCellValueExplicit($coord, $val, DataType::TYPE_NUMERIC);
                        $sheet->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    } elseif ($colMeta['type'] === 'decimal' && is_float($val)) {
                        $sheet->setCellValueExplicit($coord, $val, DataType::TYPE_NUMERIC);
                        $sheet->getStyle($coord)->getNumberFormat()->setFormatCode('0.00');
                        $sheet->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    } else {
                        $sheet->setCellValueExplicit($coord, (string) $val, DataType::TYPE_STRING);
                    }

                    if (! empty($colMeta['wrap'])) {
                        $sheet->getStyle($coord)->getAlignment()->setWrapText(true);
                    }

                    $strVal              = (string) $val;
                    $len                 = function_exists('mb_strlen') ? mb_strlen($strVal) : strlen($strVal);
                    $maxLengths[$colNum] = max($maxLengths[$colNum], $len);
                }
            }
            $sheet->getStyle("A{$rowNum}:{$lastColStr}{$rowNum}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $rowNum++;
            $seq++;
        }

        $lastRow = max(1, $rowNum - 1);
        $sheet->getStyle("A1:{$lastColStr}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D3E2');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColStr}1");

        foreach ($headers as $idx => $colMeta) {
            $colNum    = $idx + 1;
            $colLetter = Coordinate::stringFromColumnIndex($colNum);
            if ($colMeta['key'] === '_no') {
                $sheet->getColumnDimension($colLetter)->setWidth(7);
            } else {
                $width = min(max($maxLengths[$colNum] + 4, 14), 45);
                $sheet->getColumnDimension($colLetter)->setWidth($width);
            }
        }

        return $spreadsheet;
    }

    /**
     * Memformat satu nilai kolom biodata untuk penulisan sel Excel.
     *
     * @param array<string, mixed> $row
     * @return string|int|float
     */
    private function formatBiodataExportValue(array $row, string $field, string $type)
    {
        $raw = $row[$field] ?? null;

        if ($type === 'date') {
            if (empty($raw) || $raw === '0000-00-00') {
                return '-';
            }
            $ts = strtotime((string) $raw);
            return ($ts !== false && $ts > 0) ? date('d/m/Y', $ts) : '-';
        }

        if ($type === 'datetime') {
            if (empty($raw) || $raw === '0000-00-00 00:00:00') {
                return '-';
            }
            $ts = strtotime((string) $raw);
            return ($ts !== false && $ts > 0) ? date('d/m/Y H:i', $ts) : '-';
        }

        if ($type === 'semester') {
            $kode = trim((string) ($raw ?? ''));
            return $kode !== '' ? SemesterGraduateModel::formatNama('', $kode) : '-';
        }

        if ($type === 'int') {
            return ($raw !== null && $raw !== '' && is_numeric($raw)) ? (int) $raw : '-';
        }

        if ($type === 'decimal') {
            return ($raw !== null && $raw !== '' && is_numeric($raw)) ? (float) $raw : '-';
        }

        $text = trim((string) ($raw ?? ''));
        if ($field === 'jenis_tugas_akhir' && $text === '') {
            return 'Skripsi';
        }

        return $text !== '' ? $text : '-';
    }

    private function resolveBiodataVerifikasiRedirect(?string $candidate): string
    {
        $fallback  = base_url('graduate/biodata_wisuda_verifikasi');
        $candidate = trim((string) $candidate);
        if ($candidate !== '' && str_starts_with($candidate, $fallback)) {
            return $candidate;
        }

        $referer = (string) $this->request->getServer('HTTP_REFERER');
        if ($referer !== '' && str_starts_with($referer, $fallback)) {
            return $referer;
        }

        return $fallback;
    }

    /**
     * Tampilan antrean verifikasi Berkas Persyaratan Wisuda oleh Tendik / Staf / Admin
     */
    public function indexBerkasTendik()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas'])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        session()->set('active_side', 'graduate');

        $semModel       = new SemesterGraduateModel();
        $daftarSemester = $semModel->orderBy('kode', 'DESC')->findAll();
        $semAktif       = $semModel->getActiveSemester();

        $filterSem    = $this->request->getGet('semester') ?? ($semAktif['kode'] ?? 'semua');
        $filterStatus = $this->request->getGet('status') ?? '';
        $filterCari   = trim((string) ($this->request->getGet('cari') ?? ''));

        $daftarBerkasPendaftaran = $this->pendaftaranModel->getFilteredBerkas(
            $filterSem === 'semua' ? null : $filterSem,
            $filterStatus === '' || $filterStatus === 'semua' ? null : $filterStatus,
            $filterCari !== '' ? $filterCari : null
        );

        $berkasPerPendaftaran = [];
        foreach ($daftarBerkasPendaftaran as $p) {
            $berkasPerPendaftaran[$p['id']] = $this->berkasModel->getByPendaftaranId((int) $p['id']);
        }

        return view('The-Graduate-Web/admin_tendik/berkas_wisuda', [
            'title'                   => 'Verifikasi Berkas Persyaratan Wisuda',
            'user'                    => $userData,
            'role'                    => $roles,
            'daftarBerkasPendaftaran' => $daftarBerkasPendaftaran,
            'berkasPerPendaftaran'    => $berkasPerPendaftaran,
            'daftarSemester'          => $daftarSemester,
            'semesterAktif'           => $filterSem,
            'statusFilter'            => $filterStatus,
            'kataCari'                => $filterCari,
        ]);
    }

    /**
     * Verifikasi Berkas Persyaratan Wisuda oleh Tendik / Staf / Admin
     */
    public function verifikasiBerkas()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin'])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $id      = (int) $this->request->getPost('id');
        $status  = strtoupper(trim((string) $this->request->getPost('status')));
        $catatan = trim((string) $this->request->getPost('catatan_berkas'));

        if (! in_array($status, ['DISETUJUI', 'DITOLAK', 'MENUNGGU'], true)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Status Tidak Valid',
                'text'  => 'Status verifikasi berkas harus DISETUJUI atau DITOLAK.',
            ]);
            return redirect()->to(base_url('graduate/berkas_wisuda_verifikasi'));
        }

        if ($status === 'DITOLAK' && $catatan === '') {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Catatan Wajib Diisi',
                'text'  => 'Harap berikan catatan verifikasi atau alasan perbaikan jika menolak berkas persyaratan.',
            ]);
            return redirect()->to(base_url('graduate/berkas_wisuda_verifikasi'));
        }

        $pendaftaran = $this->pendaftaranModel->find($id);
        if ($pendaftaran === null) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Tidak Ditemukan',
                'text'  => 'Data pendaftaran wisuda tidak ditemukan.',
            ]);
            return redirect()->to(base_url('graduate/berkas_wisuda_verifikasi'));
        }

        $updateData = [
            'status_berkas'     => $status,
            'catatan_berkas'    => $catatan ?: null,
            'diverifikasi_oleh' => (int) ($userData['id'] ?? $userData['akun_id'] ?? 1),
            'diverifikasi_pada' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->pendaftaranModel->update($id, $updateData);

            // Perbarui juga status setiap baris berkas di berkas_pendaftaran_wisuda
            $this->berkasModel->where('pendaftaran_id', $id)
                ->set(['status_berkas' => $status])
                ->update();

            $this->syncOverallStatus($id);

            $statusText = $status === 'DISETUJUI' ? 'disetujui' : ($status === 'DITOLAK' ? 'ditolak' : 'diperbarui');
            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Verifikasi Berkas Berhasil',
                'text'  => 'Berkas persyaratan wisuda mahasiswa ' . $pendaftaran['nama'] . ' berhasil ' . $statusText . '.',
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[PendaftaranWisudaController] ' . $e->getMessage());
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Memproses',
                'text'  => 'Terjadi kesalahan sistem saat memproses data. Silakan coba lagi.',
            ]);
        }

        return redirect()->to(base_url('graduate/berkas_wisuda_verifikasi'));
    }

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

        // Cari berkas di folder berkas atau folder foto (WRITEPATH lebih dulu, lalu fallback FCPATH legacy)
        $filePath = GraduateInputValidator::resolveGraduateUploadPath('berkas', $fileName)
            ?? GraduateInputValidator::resolveGraduateUploadPath('foto', $fileName);

        if ($filePath === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Berkas tidak ditemukan.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        $isStaff = AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas']);

        if (! $isStaff) {
            if (! in_array('mahasiswa', $roles, true)) {
                return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
            }

            $npm = TracerStudyGate::resolveNpm($userData);
            if (! $this->hasCompletedTracerAwal($npm)) {
                return AccessControl::forbiddenResponse($this->request, 'Silakan isi Tracer Study Awal terlebih dahulu sebelum mengakses berkas.', $roles);
            }

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

    /**
     * Menyetujui Calon Wisudawan menjadi Mahasiswa Wisudawan
     */
    public function approveWisudawan(int $id)
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin'])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $pendaftaran = $this->pendaftaranModel->find($id);
        if (! $pendaftaran) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Data Tidak Ditemukan',
                'text'  => 'Data pendaftaran calon wisudawan tidak ditemukan.',
            ]);
            return redirect()->back();
        }

        $sBio = strtoupper((string) ($pendaftaran['status_biodata'] ?? ''));
        $sBer = strtoupper((string) ($pendaftaran['status_berkas'] ?? ''));
        if ($sBio !== 'DISETUJUI' || $sBer !== 'DISETUJUI') {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Syarat Belum Terpenuhi',
                'text'  => 'Mahasiswa harus memiliki biodata dan berkas yang telah disetujui terlebih dahulu.',
            ]);
            return redirect()->back();
        }

        try {
            $this->pendaftaranModel->update($id, [
                'status_wisudawan'       => 'DISETUJUI',
                'tgl_approval_wisudawan' => date('Y-m-d H:i:s'),
                'catatan_wisudawan'      => $this->request->getPost('catatan') ?: null,
            ]);

            // Sinkronisasi hak akses role alumni jika akun mahasiswa ada
            $db = \Config\Database::connect();
            $npm = (string) ($pendaftaran['npm'] ?? '');
            if ($npm !== '' && $db->tableExists('role') && $db->tableExists('akses') && $db->tableExists('akun')) {
                $roleWis = $db->table('role')->where('nama', 'alumni')->get()->getRowArray();
                if ($roleWis) {
                    $mhs = $db->tableExists('mahasiswa') ? $db->table('mahasiswa')->where('npm', $npm)->get()->getRowArray() : null;
                    $akun = null;
                    if ($mhs && ! empty($mhs['email'])) {
                        $akun = $db->table('akun')->where('email', $mhs['email'])->get()->getRowArray();
                    }
                    if (! $akun) {
                        $akun = $db->table('akun')->where('username', $npm)->get()->getRowArray();
                    }
                    if ($akun) {
                        $hasAkses = $db->table('akses')
                            ->where('id_akun', (int) $akun['id'])
                            ->where('id_role', (int) $roleWis['id'])
                            ->countAllResults();
                        if ($hasAkses === 0) {
                            $db->table('akses')->insert([
                                'id_akun' => (int) $akun['id'],
                                'id_role' => (int) $roleWis['id'],
                            ]);
                        }
                    }
                }
            }

            $actor = (string) ($userData['username'] ?? $userData['email'] ?? 'tendik');
            SecurityAuditLogger::logRoleChange(
                $npm !== '' ? $npm : ('pendaftaran#' . $id),
                'calon_wisudawan',
                'alumni',
                $actor,
                'success',
                $this->request,
                ['akun_id' => $userData['akun_id'] ?? null]
            );

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Approval Wisudawan Berhasil',
                'text'  => 'Mahasiswa ' . ($pendaftaran['nama'] ?? '') . ' (' . $npm . ') resmi disetujui menjadi Mahasiswa Wisudawan.',
            ]);
        } catch (\Throwable $e) {
            $eventId = SecurityExceptions::report($e, 'graduate.approveWisudawan', $this->request);
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Approval',
                'text'  => SecurityExceptions::publicErrorMessage($eventId, 'Terjadi kesalahan sistem saat menyetujui wisudawan.'),
            ]);
        }

        return redirect()->back();
    }

    /**
     * Membatalkan penetapan Mahasiswa Wisudawan kembali menjadi Calon Wisudawan
     */
    public function batalWisudawan(int $id)
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin'])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $pendaftaran = $this->pendaftaranModel->find($id);
        if (! $pendaftaran) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Data Tidak Ditemukan',
                'text'  => 'Data pendaftaran calon wisudawan tidak ditemukan.',
            ]);
            return redirect()->back();
        }

        try {
            $this->pendaftaranModel->update($id, [
                'status_wisudawan'       => 'MENUNGGU',
                'tgl_approval_wisudawan' => null,
            ]);

            $actor = (string) ($userData['username'] ?? $userData['email'] ?? 'tendik');
            $npm   = (string) ($pendaftaran['npm'] ?? ('pendaftaran#' . $id));
            SecurityAuditLogger::logRoleChange(
                $npm,
                'alumni',
                'calon_wisudawan',
                $actor,
                'success',
                $this->request,
                ['akun_id' => $userData['akun_id'] ?? null]
            );

            session()->setFlashdata('message', [
                'icon'  => 'info',
                'title' => 'Status Dibatalkan',
                'text'  => 'Status wisudawan mahasiswa ' . ($pendaftaran['nama'] ?? '') . ' berhasil dikembalikan menjadi Calon Wisudawan.',
            ]);
        } catch (\Throwable $e) {
            $eventId = SecurityExceptions::report($e, 'graduate.batalWisudawan', $this->request);
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Membatalkan',
                'text'  => SecurityExceptions::publicErrorMessage($eventId, 'Terjadi kesalahan sistem saat membatalkan status wisudawan.'),
            ]);
        }

        return redirect()->back();
    }
}
