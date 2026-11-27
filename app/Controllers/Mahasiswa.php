<?php



namespace App\Controllers;


use App\Libraries\AccessControl;
use App\Models\SkripsiModel;
use CodeIgniter\I18n\Time;
use Exception;



class Mahasiswa extends BaseController
{

    protected $mahasiswaModel;

    protected $prodiModel;

    protected $proposalModel;

    protected $dosenModel;

    protected $bidangModel;

    protected $semproModel;

    protected $pembimbingModel;

    protected $catatanBimbinganModel;

    protected $skripsiModel;

    protected $pengajuanPrasidangModel;

    protected $pengajuanSidangModel;

    protected $seminarPrasidangModel;

    protected $sidangSkripsiModel;

    protected $penilaianSidangModel;

    protected $makalahModel;

    protected $semesterModel;

    protected $akunModel;

    protected $modeSemproModel;

    protected $penilaianSidangNewModel;

    protected $penilaianSidangParamModel;

    protected $pengajuanHardcoverModel;

    protected $riwayatKomentarModel;

    public function __construct()
    {

        $this->mahasiswaModel = new \App\Models\MahasiswaModel();

        $this->prodiModel = new \App\Models\ProgramStudiModel();

        $this->proposalModel = new \App\Models\ProposalModel();

        $this->dosenModel = new \App\Models\DosenModel();

        $this->bidangModel = new \App\Models\BidangModel();

        $this->semproModel = new \App\Models\SeminarProposalModel();

        $this->pembimbingModel = new \App\Models\PembimbingModel();

        $this->catatanBimbinganModel = new \App\Models\CatatanBimbinganModel();

        $this->skripsiModel = new \App\Models\SkripsiModel();

        $this->pengajuanPrasidangModel = new \App\Models\PengajuanPrasidangModel();

        $this->pengajuanSidangModel = new \App\Models\PengajuanSidangModel();

        $this->seminarPrasidangModel = new \App\Models\SeminarPrasidangModel();

        $this->sidangSkripsiModel = new \App\Models\SidangSkripsiModel();

        $this->penilaianSidangModel = new \App\Models\PenilaianSidangModel();

        $this->makalahModel = new \App\Models\MakalahModel();

        $this->semesterModel = new \App\Models\SemesterModel();

        $this->akunModel = new \App\Models\AkunModel();

        $this->modeSemproModel = new \App\Models\ModeSemproModel();

        $this->penilaianSidangNewModel = new \App\Models\PenilaianSidangNewModel();

        $this->penilaianSidangParamModel = new \App\Models\PenilaianSidangParamModel();

        $this->pengajuanHardcoverModel = new \App\Models\PengajuanHardcoverModel();

        $this->riwayatKomentarModel = new \App\Models\RiwayatKomentarPengajuanSkripsiModel();
    }



    public function index()
    {
        

        // 2. Ambil NPM dari session (fallback ke profil untuk session lama)
        $npm = $this->currentNpm();

        // 3. Panggil fungsi progres dari SkripsiModel yang sudah kita buat
        $progresData = $this->skripsiModel->getThesisProgress($npm);

        // 4. Siapkan data untuk dikirim ke view
        $data = [
            'title'        => 'Dashboard Mahasiswa',
            'skripsi'      => $progresData ? $progresData['detail'] : null,
            'current_step' => $progresData ? $progresData['current_step'] : 0,
            'tahapan'      => [
                "Penyusunan", "Draft Proposal", "Jadwal Sempro", 
                "Judul Final", "Draft Prasidang", "Pra Sidang", 
                "Draft Sidang", "Jadwal Sidang", "Sidang Akhir"
            ]
        ];

        // 5. Tampilkan view dashboard mahasiswa (Pastikan file ini ada di app/Views/mahasiswa/dashboard.php)
        return view('mahasiswa/dashboard', $data);
    }







    public function pengajuanPenulisanSkripsi()
{
    

    // Ambil data mahasiswa yang sedang login
    $mahasiswa = $this->mahasiswaModel->find(
        session()->get("user_session")['id']
    );

    // Ambil data dosen berdasarkan program studi mahasiswa
    $dosen = $this->dosenModel->getDosenByProdi(
        $mahasiswa['id_prodi']
    );

    // Ambil mata kuliah berdasarkan program studi mahasiswa
    $mataKuliahModel = new \App\Models\MataKuliahModel();

    $mataKuliah = $mataKuliahModel->getMataKuliahByProdi(
        (int) $mahasiswa['id_prodi']
    );

    // Ambil jenis tugas akhir berdasarkan program studi mahasiswa
    $jenisTugasAkhir = \Config\Database::connect()
        ->table('jenis_tugas_akhir')
        ->where('id_prodi', (int) $mahasiswa['id_prodi'])
        ->get()
        ->getResultArray();

    // Ambil semester yang sedang aktif dari pengaturan Fakultas
    $currentSemester = $this->semesterModel->getActiveSemester();

    // Ambil riwayat percakapan Tendik dan mahasiswa
    $riwayatKomentar = $this->riwayatKomentarModel
        ->where('npm', $mahasiswa['npm'])
        ->orderBy('tanggal_pesan', 'ASC')
        ->findAll();

    // Kirim data ke halaman View
    $data = [
        'title' => 'Pengajuan Penulisan Skripsi',
        'mahasiswa' => $mahasiswa,
        'dosen' => $dosen,
        'mataKuliah' => $mataKuliah,
        'jenisTugasAkhir' => $jenisTugasAkhir,
         'currentSemester' => $currentSemester,
         'riwayatKomentar' => $riwayatKomentar,
    ];

    return view("mahasiswa/pengajuan_penulisan_skripsi", $data);
}

public function mataKuliahWajib()
{
    $data['title'] = 'Status Mata Kuliah Wajib';

    return view('mahasiswa/mata_kuliah_wajib', $data);
}


public function balasKomentarPengajuanSkripsi()
{
    // Pastikan hanya request POST yang dapat diproses.
    if ($this->request->getMethod() !== 'post') {
        return redirect()->back();
    }

    // Pastikan hanya mahasiswa yang sudah login dapat membalas.
    if (!$this->authenticate(["mahasiswa"])) {
        return redirect()->to(base_url("auth/login"));
    }

    // Ambil data mahasiswa berdasarkan session login.
    $userSession = session()->get("user_session");
    $mahasiswa = $this->mahasiswaModel->find($userSession['id'] ?? null);

    if (!$mahasiswa) {
        return redirect()->back()->with('message', [
            "icon" => "error",
            "title" => "Gagal",
            "text" => "Data mahasiswa tidak ditemukan."
        ]);
    }

    // Ambil dan validasi pesan balasan.
    $pesan = $this->request->getPost('pesan');

    if (!is_string($pesan)) {
        return redirect()->back()->withInput()->with('message', [
            "icon" => "error",
            "title" => "Pesan Tidak Valid",
            "text" => "Format pesan tidak valid."
        ]);
    }

    $pesan = trim($pesan);

    if ($pesan === '' || mb_strlen($pesan, 'UTF-8') > 5000) {
        return redirect()->back()->withInput()->with('message', [
            "icon" => "error",
            "title" => "Pesan Tidak Valid",
            "text" => "Pesan wajib diisi dan maksimal 5000 karakter."
        ]);
    }

            $berhasil = $this->riwayatKomentarModel->insert([
            'npm' => $mahasiswa['npm'],
            'id_tendik' => null,
            'pesan' => $pesan,
            'tanggal_pesan' => date('Y-m-d H:i:s')
        ]);

    if (!$berhasil) {
        return redirect()->back()->withInput()->with('message', [
            "icon" => "error",
            "title" => "Balasan Gagal",
            "text" => "Pesan gagal dikirim. Silakan coba kembali."
        ]);
    }

    return redirect()->to(base_url("mahasiswa/pengajuanPenulisanSkripsi"))
        ->with('message', [
            "icon" => "success",
            "title" => "Balasan Terkirim",
            "text" => "Balasan Anda berhasil dikirim."
        ]);
}


   
public function insertPengajuanPenulisanSkripsi($npm)
{
    if ($this->request->getMethod() != 'post') {
        return redirect()->back();
    }

    // Memastikan hanya mahasiswa yang sudah login dapat mengakses endpoint.
    if (!$this->authenticate(["mahasiswa"])) {
        return redirect()->back()->with('message', [
            "icon" => "error",
            "title" => "Akses Ditolak",
            "text" => "Silakan login sebagai mahasiswa."
        ]);
    }

    // Mengambil data mahasiswa berdasarkan sesi login.
    $userSession = session()->get("user_session");
    $mahasiswa = $this->mahasiswaModel->find($userSession['id'] ?? null);

    // Memastikan mahasiswa hanya dapat mengubah pengajuannya sendiri.
    if (!$mahasiswa || (string) $mahasiswa['npm'] !== (string) $npm) {
        return redirect()->back()->with('message', [
            "icon" => "error",
            "title" => "Akses Ditolak",
            "text" => "Anda tidak dapat mengubah pengajuan mahasiswa lain."
        ]);
    }

    $db = \Config\Database::connect();
    $idJenisTugasAkhir = (int) $this->request->getPost("jenis_ta");
    $jenisTugasAkhir = $db->table('jenis_tugas_akhir')
        ->where('id_jenis_tugas_akhir', $idJenisTugasAkhir)
        ->where('id_prodi', (int) $mahasiswa['id_prodi'])
        ->get()->getRowArray();

    if (!$jenisTugasAkhir) {
        return redirect()->back()->withInput()->with('message', [
            "icon" => "error", "title" => "Jenis Tugas Akhir Tidak Valid",
            "text" => "Silakan pilih jenis tugas akhir yang tersedia untuk program studi Anda."
        ]);
    }

    // Validasi file dokumen sebelum proses upload.
    $daftarFile = [
        'inputKhs' => 'KHS',
        'inputKrs' => 'KRS',
        'inputPersetujuanSkripsi' => 'Persetujuan Skripsi'
    ];

    foreach ($daftarFile as $namaInput => $namaDokumen) {
        $file = $this->request->getFile($namaInput);

        // Lewati jika pengguna tidak mengunggah file baru.
        if (!$file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (!$file->isValid()) {
            return redirect()->back()->withInput()->with('message', [
                'icon' => 'error',
                'title' => 'Upload Gagal',
                'text' => "File {$namaDokumen} gagal diunggah."
            ]);
        }

        if (
            strtolower($file->getClientExtension()) !== 'pdf' ||
            $file->getMimeType() !== 'application/pdf'
        ) {
            return redirect()->back()->withInput()->with('message', [
                'icon' => 'error',
                'title' => 'Format File Tidak Valid',
                'text' => "File {$namaDokumen} harus menggunakan format PDF."
            ]);
        }

        if ($file->getSize() > 10 * 1024 * 1024) {
            return redirect()->back()->withInput()->with('message', [
                'icon' => 'error',
                'title' => 'Ukuran File Terlalu Besar',
                'text' => "Ukuran file {$namaDokumen} maksimal 10 MB."
            ]);
        }
    }

    // Proses upload tetap mengikuti alur yang sudah ada.
    $fileKhsBaru = $mahasiswa['file_khs'];
    $fileKhs = $this->request->getFile("inputKhs");
    if ($fileKhs && $fileKhs->isValid() && !$fileKhs->hasMoved()) {
        $fileKhsBaru = "KHS_" . $npm . "_" . $this->generateTimeStamp() . "." . $fileKhs->getClientExtension();
        $fileKhs->move("folderKHS", $fileKhsBaru);
    }

    $fileKrsBaru = $mahasiswa['file_krs'];
    $fileKrs = $this->request->getFile("inputKrs");
    if ($fileKrs && $fileKrs->isValid() && !$fileKrs->hasMoved()) {
        $fileKrsBaru = "KRS_" . $npm . "_" . $this->generateTimeStamp() . "." . $fileKrs->getClientExtension();
        $fileKrs->move("folderKRS", $fileKrsBaru);
    }

    $filePengajuanSkripsiBaru = $mahasiswa['file_persetujuan_skripsi'];
    $filePersetujuan = $this->request->getFile("inputPersetujuanSkripsi");
    if ($filePersetujuan && $filePersetujuan->isValid() && !$filePersetujuan->hasMoved()) {
        $filePengajuanSkripsiBaru = "PersetujuanSkripsi_" . $npm . "_" . $this->generateTimeStamp() . "." . $filePersetujuan->getClientExtension();
        $filePersetujuan->move("folderPersetujuanSkripsi", $filePengajuanSkripsiBaru);
    }

    // Memastikan seluruh dokumen wajib tersedia.
    if (
        empty($fileKhsBaru) ||
        empty($fileKrsBaru) ||
        empty($filePengajuanSkripsiBaru)
    ) {
        return redirect()->back()->withInput()->with('message', [
            "icon" => "error",
            "title" => "Dokumen Belum Lengkap",
            "text" => "KHS, KRS, dan Persetujuan Skripsi wajib tersedia sebelum pengajuan dikirim."
        ]);
    }

    $pembimbingAkademik = $this->request->getPost("pembimbing_akademik");

    // Memastikan dosen pembimbing valid dan berasal dari prodi mahasiswa.
    $idPembimbing = filter_var($pembimbingAkademik, FILTER_VALIDATE_INT);

    $pembimbingValid = false;

    if ($idPembimbing !== false && $idPembimbing > 0) {
        $pembimbingValid = $db->table('dosen')
            ->where('id', $idPembimbing)
            ->where('id_prodi', (int) $mahasiswa['id_prodi'])
            ->countAllResults() > 0;
    }

    if (!$pembimbingValid) {
        return redirect()->back()->withInput()->with('message', [
            "icon" => "error",
            "title" => "Dosen Pembimbing Tidak Valid",
            "text" => "Silakan pilih dosen pembimbing yang tersedia pada program studi Anda."
        ]);
    }

    // Mahasiswa hanya mengirim dua pilihan dari View.
    $mkSudahLulus = $this->request->getPost("mk_wajib_sudah_lulus") ?? [];
    $mkSedangDiambil = $this->request->getPost("mk_sedang_diambil") ?? [];

    if (!is_array($mkSudahLulus) || !is_array($mkSedangDiambil)) {
        return redirect()->back()->withInput()->with('message', [
            "icon" => "error", "title" => "Pilihan Tidak Valid",
            "text" => "Format pilihan mata kuliah tidak valid. Silakan pilih ulang."
        ]);
    }

    $mkSudahLulus = array_values(array_unique(array_filter(array_map('trim', $mkSudahLulus))));
    $mkSedangDiambil = array_values(array_unique(array_filter(array_map('trim', $mkSedangDiambil))));

    if (empty($mkSudahLulus)) {
        return redirect()->back()->withInput()->with('message', [
            "icon" => "error", "title" => "Data Belum Lengkap",
            "text" => "Pilih mata kuliah yang sudah lulus."
        ]);
    }

    // Ambil mata kuliah resmi dari program studi mahasiswa.
    $daftarMK = $db->table('detail_kurikulum dk')
        ->select('mk.kode_matkul, mk.nama_matkul, mk.sks, dk.kategori')
        ->join('mata_kuliah mk', 'mk.kode_matkul = dk.kode_matkul')
        ->join('kurikulum k', 'k.id_kurikulum = dk.id_kurikulum')
        ->where('mk.id_prodi', (int) $mahasiswa['id_prodi'])
        ->where('k.id_prodi', (int) $mahasiswa['id_prodi'])
        ->groupBy(['mk.kode_matkul', 'mk.nama_matkul', 'mk.sks', 'dk.kategori'])
        ->get()->getResultArray();

    // Konversi pilihan lama berbentuk nama mata kuliah ke kode agar tetap terbaca.
    $kodeByNama = [];
    $sksByKode = [];
    $kategoriByKode = [];
    foreach ($daftarMK as $mk) {
        $kodeByNama[$mk['nama_matkul']] = $mk['kode_matkul'];
        $sksByKode[$mk['kode_matkul']] = (int) $mk['sks'];
        $kategoriByKode[$mk['kode_matkul']] = $mk['kategori'];
    }

    $normalisasiKeKode = function (array $pilihan) use ($kodeByNama, $sksByKode) {
        $hasil = [];
        foreach ($pilihan as $item) {
            if (isset($sksByKode[$item])) {
                $hasil[] = $item;
            } elseif (isset($kodeByNama[$item])) {
                $hasil[] = $kodeByNama[$item];
            } else {
                return null;
            }
        }
        return array_values(array_unique($hasil));
    };

    $mkSudahLulus = $normalisasiKeKode($mkSudahLulus);
    $mkSedangDiambil = $normalisasiKeKode($mkSedangDiambil);

    if ($mkSudahLulus === null || $mkSedangDiambil === null) {
        return redirect()->back()->withInput()->with('message', [
            "icon" => "error", "title" => "Mata Kuliah Tidak Valid",
            "text" => "Terdapat mata kuliah yang tidak terdaftar pada program studi Anda."
        ]);
    }

    // Satu mata kuliah tidak boleh sekaligus berstatus lulus dan sedang berjalan.
    if (count(array_intersect($mkSudahLulus, $mkSedangDiambil)) > 0) {
        return redirect()->back()->withInput()->with('message', [
            "icon" => "error", "title" => "Pilihan Mata Kuliah Tidak Sesuai",
            "text" => "Mata kuliah yang sudah lulus tidak boleh dipilih kembali sebagai mata kuliah yang sedang berjalan."
        ]);
    }

    // Hitung otomatis: seluruh mata kuliah Wajib yang tidak lulus dan tidak sedang diambil.
    // Hasilnya disimpan ke mk_akan_diambil tanpa dropdown ketiga di View.
    $mkBelumLulus = [];
    foreach ($daftarMK as $mk) {
        $kode = (string) $mk['kode_matkul'];
        if (
            ($mk['kategori'] ?? '') === 'Wajib'
            && !in_array($kode, $mkSudahLulus, true)
            && !in_array($kode, $mkSedangDiambil, true)
        ) {
            $mkBelumLulus[] = $kode;
        }
    }
    $mkBelumLulus = array_values(array_unique($mkBelumLulus));

    $sksLulus = 0;
    foreach ($mkSudahLulus as $kode) {
        $sksLulus += $sksByKode[$kode] ?? 0;
    }

    $nilaiMKLulus = implode(',', $mkSudahLulus);
    $nilaiMKSedang = implode(',', $mkSedangDiambil);
    $nilaiMKBelumLulus = implode(',', $mkBelumLulus);

    if (
    strlen($nilaiMKLulus) > 65535 ||
    strlen($nilaiMKSedang) > 65535 ||
    strlen($nilaiMKBelumLulus) > 65535
) {
    return redirect()->back()->withInput()->with('message', [
        "icon" => "error",
        "title" => "Pilihan Terlalu Banyak",
        "text" => "Jumlah data mata kuliah melebihi kapasitas penyimpanan."
    ]);
}

    $berhasil = $this->mahasiswaModel->update($npm, [
        "status_persetujuan_skripsi" => null,
        "alasan_penolakan" => null,
        "file_khs" => $fileKhsBaru,
        "file_krs" => $fileKrsBaru,
        "file_persetujuan_skripsi" => $filePengajuanSkripsiBaru,
        "sks_lulus" => $sksLulus,
        "pembimbing_akademik" => $pembimbingAkademik,
        "id_jenis_tugas_akhir" => $idJenisTugasAkhir,
        "mk_wajib_sudah_lulus" => $nilaiMKLulus,
        "mk_sedang_diambil" => $nilaiMKSedang,
        "mk_akan_diambil" => $nilaiMKBelumLulus,
        "tanggal_pengajuan_skripsi" => date_format(Time::now('Asia/Jakarta', 'en_us'), 'Y-m-d H:i:s'),
    ]);

    if (!$berhasil) {
    return redirect()->back()->withInput()->with('message', [
        "icon" => "error",
        "title" => "Pengajuan Gagal",
        "text" => "Data pengajuan gagal disimpan. Silakan coba kembali."
    ]);
}

    session()->setFlashdata("message", [
        "icon" => "success", "title" => "Upload Data Berhasil",
        "text" => "Data Pengajuan Penulisan Skripsi Berhasil diunggah"
    ]);
    return redirect()->to(base_url("mahasiswa/pengajuanPenulisanSkripsi"));
}


    public function apiMataKuliahMahasiswa()
{
    if (!$this->authenticate(["mahasiswa"])) {
        return $this->response->setStatusCode(401)->setJSON([
            "status" => "error",
            "message" => "Silakan login sebagai mahasiswa."
        ]);
    }

    $userSession = session()->get("user_session");
    $mahasiswa = $this->mahasiswaModel->find($userSession['id'] ?? null);

    if (!$mahasiswa) {
        return $this->response->setStatusCode(404)->setJSON([
            "status" => "error",
            "message" => "Data mahasiswa tidak ditemukan."
        ]);
    }

    $db = \Config\Database::connect();
    $mataKuliah = $db->table('detail_kurikulum dk')
        ->select("
            mk.kode_matkul,
            mk.nama_matkul,
            mk.sks,
            dk.kategori,
            MIN(dk.semester_kurikulum) AS semester_kurikulum
        ")
        ->join('kurikulum k', 'k.id_kurikulum = dk.id_kurikulum')
        ->join('mata_kuliah mk', 'mk.kode_matkul = dk.kode_matkul')
        ->where('k.id_prodi', $mahasiswa['id_prodi'])
        ->where('mk.id_prodi', $mahasiswa['id_prodi'])
        ->groupBy(['mk.kode_matkul', 'mk.nama_matkul', 'mk.sks', 'dk.kategori'])
        ->orderBy('semester_kurikulum', 'ASC')
        ->get()->getResultArray();

    $mkSudahLulus = array_values(array_filter(array_map('trim', explode(',', $mahasiswa['mk_wajib_sudah_lulus'] ?? ''))));
    $mkSedangDiambil = array_values(array_filter(array_map('trim', explode(',', $mahasiswa['mk_sedang_diambil'] ?? ''))));

    // Hitung daftar Wajib belum lulus dari dua pilihan yang tersimpan.
    $mkBelumLulus = [];
    foreach ($mataKuliah as $mk) {
        $kode = (string) $mk['kode_matkul'];
        if (
            ($mk['kategori'] ?? '') === 'Wajib'
            && !in_array($kode, $mkSudahLulus, true)
            && !in_array($kode, $mkSedangDiambil, true)
        ) {
            $mkBelumLulus[] = $kode;
        }
    }
    $mkBelumLulus = array_values(array_unique($mkBelumLulus));

    return $this->response->setJSON([
        "status" => "success",
        "message" => "Data mata kuliah berhasil diambil.",
        "data" => [
            "npm" => $mahasiswa['npm'],
            "id_prodi" => $mahasiswa['id_prodi'],
            "mata_kuliah" => $mataKuliah,
            "pilihan_mahasiswa" => [
                "mk_wajib_sudah_lulus" => implode(',', $mkSudahLulus),
                "mk_sedang_diambil" => implode(',', $mkSedangDiambil),
                "mk_akan_diambil" => implode(',', $mkBelumLulus)
            ]
        ]
    ]);
}


public function apiAlasanPenolakan()
{
    // Pastikan hanya mahasiswa yang sudah login dapat mengakses API
    if (!$this->authenticate(["mahasiswa"])) {
        return $this->response->setStatusCode(401)->setJSON([
            "status" => "error",
            "message" => "Silakan login sebagai mahasiswa."
        ]);
    }

    // Ambil data mahasiswa berdasarkan session login
    $userSession = session()->get("user_session");
    $mahasiswa = $this->mahasiswaModel->find($userSession['id'] ?? null);

    if (!$mahasiswa) {
        return $this->response->setStatusCode(404)->setJSON([
            "status" => "error",
            "message" => "Data mahasiswa tidak ditemukan."
        ]);
    }

    // Pastikan pengajuan memang ditolak
    if ($mahasiswa['status_persetujuan_skripsi'] !== 'Ditolak') {
        return $this->response->setStatusCode(400)->setJSON([
            "status" => "error",
            "message" => "Pengajuan skripsi tidak dalam status ditolak."
        ]);
    }

    // Ambil alasan penolakan milik mahasiswa yang sedang login
    return $this->response->setJSON([
        "status" => "success",
        "message" => "Alasan penolakan berhasil diambil.",
        "data" => [
            "status_pengajuan" => $mahasiswa['status_persetujuan_skripsi'],
            "alasan_penolakan" => $mahasiswa['alasan_penolakan']
        ]
    ]);
}


    public function apiSimpanMataKuliahMahasiswa()
{
    if (!$this->authenticate(["mahasiswa"])) {
        return $this->response->setStatusCode(401)->setJSON([
            "status" => "error", "message" => "Silakan login sebagai mahasiswa."
        ]);
    }

    $userSession = session()->get("user_session");
    $mahasiswa = $this->mahasiswaModel->find($userSession['id'] ?? null);
    if (!$mahasiswa) {
        return $this->response->setStatusCode(404)->setJSON([
            "status" => "error", "message" => "Data mahasiswa tidak ditemukan."
        ]);
    }

    $input = $this->request->getJSON(true);
    if (!is_array($input)) {
        $input = $this->request->getPost();
    }

    $normalisasi = function ($nilai) {
        if (is_string($nilai)) {
            $nilai = trim($nilai) === '' ? [] : explode(',', $nilai);
        }
        if (!is_array($nilai)) {
            return null;
        }

        $hasil = [];
        foreach ($nilai as $item) {
            if (!is_string($item) && !is_numeric($item)) {
                return null;
            }
            $item = trim((string) $item);
            if ($item !== '') {
                $hasil[] = $item;
            }
        }
        return array_values(array_unique($hasil));
    };

    // API juga hanya menerima dua pilihan dari mahasiswa.
    $mkSudahLulus = $normalisasi($input['mk_wajib_sudah_lulus'] ?? []);
    $mkSedang = $normalisasi($input['mk_sedang_diambil'] ?? []);

    if ($mkSudahLulus === null || $mkSedang === null) {
        return $this->response->setStatusCode(400)->setJSON([
            "status" => "error", "message" => "Format pilihan mata kuliah tidak valid."
        ]);
    }

    $db = \Config\Database::connect();
    $mataKuliah = $db->table('detail_kurikulum dk')
        ->select('mk.kode_matkul, mk.nama_matkul, mk.sks, dk.kategori')
        ->join('kurikulum k', 'k.id_kurikulum = dk.id_kurikulum')
        ->join('mata_kuliah mk', 'mk.kode_matkul = dk.kode_matkul')
        ->where('k.id_prodi', $mahasiswa['id_prodi'])
        ->where('mk.id_prodi', $mahasiswa['id_prodi'])
        ->groupBy(['mk.kode_matkul', 'mk.nama_matkul', 'mk.sks', 'dk.kategori'])
        ->get()->getResultArray();

    $kodeByNama = [];
    $sksByKode = [];
    $kategoriByKode = [];
    foreach ($mataKuliah as $mk) {
        $kodeByNama[$mk['nama_matkul']] = $mk['kode_matkul'];
        $sksByKode[$mk['kode_matkul']] = (int) $mk['sks'];
        $kategoriByKode[$mk['kode_matkul']] = $mk['kategori'];
    }

    $konversi = function (array $pilihan) use ($kodeByNama, $sksByKode) {
        $hasil = [];
        foreach ($pilihan as $item) {
            if (isset($sksByKode[$item])) {
                $hasil[] = $item;
            } elseif (isset($kodeByNama[$item])) {
                $hasil[] = $kodeByNama[$item];
            } else {
                return null;
            }
        }
        return array_values(array_unique($hasil));
    };

    $mkSudahLulus = $konversi($mkSudahLulus);
    $mkSedang = $konversi($mkSedang);

    if ($mkSudahLulus === null || $mkSedang === null) {
        return $this->response->setStatusCode(400)->setJSON([
            "status" => "error", "message" => "Terdapat mata kuliah yang tidak valid untuk program studi Anda."
        ]);
    }

    if (count(array_intersect($mkSudahLulus, $mkSedang)) > 0) {
        return $this->response->setStatusCode(400)->setJSON([
            "status" => "error",
            "message" => "Mata kuliah yang sudah lulus tidak boleh dipilih kembali sebagai mata kuliah yang sedang berjalan."
        ]);
    }

    // Hitung otomatis mata kuliah Wajib yang tidak lulus dan tidak sedang diambil.
    $mkBelumLulus = [];
    foreach ($mataKuliah as $mk) {
        $kode = (string) $mk['kode_matkul'];
        if (
            ($mk['kategori'] ?? '') === 'Wajib'
            && !in_array($kode, $mkSudahLulus, true)
            && !in_array($kode, $mkSedang, true)
        ) {
            $mkBelumLulus[] = $kode;
        }
    }
    $mkBelumLulus = array_values(array_unique($mkBelumLulus));

    $nilaiLulus = implode(',', $mkSudahLulus);
    $nilaiSedang = implode(',', $mkSedang);
    $nilaiBelumLulus = implode(',', $mkBelumLulus);

    if (
    strlen($nilaiLulus) > 65535 ||
    strlen($nilaiSedang) > 65535 ||
    strlen($nilaiBelumLulus) > 65535
) {
    return $this->response->setStatusCode(400)->setJSON([
        "status" => "error",
        "message" => "Jumlah data mata kuliah melebihi kapasitas penyimpanan."
    ]);
}

    $totalSKS = 0;
    foreach ($mkSudahLulus as $kode) {
        $totalSKS += $sksByKode[$kode] ?? 0;
    }

    $berhasil = $this->mahasiswaModel->update($mahasiswa['npm'], [
        'mk_wajib_sudah_lulus' => $nilaiLulus,
        'mk_sedang_diambil' => $nilaiSedang,
        'mk_akan_diambil' => $nilaiBelumLulus,
        'sks_lulus' => $totalSKS,
    ]);

    if (!$berhasil) {
        return $this->response->setStatusCode(500)->setJSON([
            "status" => "error", "message" => "Pilihan mata kuliah gagal disimpan."
        ]);
    }

    return $this->response->setJSON([
        "status" => "success",
        "message" => "Pilihan mata kuliah berhasil disimpan.",
        "data" => [
            "npm" => $mahasiswa['npm'],
            "mk_wajib_sudah_lulus" => $nilaiLulus,
            "mk_sedang_diambil" => $nilaiSedang,
            "mk_akan_diambil" => $nilaiBelumLulus,
            "sks_lulus" => $totalSKS,
        ]
    ]);
}


    public function proposal()
    {

        //autentikasi

        

        // [BARU] Cek Jadwal Sempro DISINI
        $cek = $this->checkJadwal('sempro');

        $mahasiswa = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $proposal = $this->proposalModel->getProposalMahasiswa($mahasiswa['npm']);

        $lastProposal = $this->proposalModel->getMahasiswaLastProposal($mahasiswa['npm']);

        $dosen = $this->dosenModel->getDosenByProdi($mahasiswa['id_prodi']);

        $bidang = $this->bidangModel->getBidangByProdi($mahasiswa['id_prodi']);

        $prodi = $this->prodiModel->find($mahasiswa['id_prodi']);

        $lastSkripsi = $this->skripsiModel->getMahasiswaLastSkripsi($mahasiswa['npm']);

        for ($i = 0; $i < count($proposal); $i++) {

            $proposal[$i]['editable'] = $this->proposalModel->isEditable($proposal[$i]['id']);

        }

        $currentSemester = $this->semesterModel->getActiveSemester();

        $modeSempro = $this->modeSemproModel->getModeSempro($currentSemester['kode'], $prodi['id']);



        $data = [

            'title' => "Kelola Proposal Skripsi",

            "mahasiswa" => $mahasiswa,

            "proposal" => $proposal,

            "dosen" => $dosen,

            "prodi" => $prodi,

            "bidang" => $bidang,

            "lastProposal" => $lastProposal,

            "lastSkripsi" => $lastSkripsi,

            "currentSemester" => $currentSemester,

            "modeSempro" => $modeSempro,

            // [BARU] Kirim status jadwal ke view
            'is_open' => $cek['status'],
            'deadline' => ($cek['data']) ? $cek['data']['tanggal_selesai'] : null,
            'pesan_tutup' => $cek['msg'] // Pesan jika tutup
        ];

        return view('mahasiswa/proposal', $data);

    }



    public function detailProposal($idProposal)
    {

        //autentikasi

        



        $dataAkun = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $detailProposal = $this->proposalModel->getDetailProposalById($idProposal);



        if ($detailProposal == null || $dataAkun == null) {

            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        }



        if ($detailProposal['npm'] != $dataAkun['npm']) {

            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        }

        $bidang = $this->bidangModel->getBidangByProdi($dataAkun['id_prodi']);

        $dosen = $this->dosenModel->getDosenByProdi($dataAkun['id_prodi']);



        $data = [

            'title' => 'Detail Proposal Skripsi',

            'detailProposal' => $detailProposal,

            'bidang' => $bidang,

            'dosen' => $dosen,

        ];



        return view("mahasiswa/detail_proposal", $data);

    }



    public function insertProposal()
    {

        //autentikasi

        



        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }

        // --- CEK JADWAL DULU ---
        $cek = $this->checkJadwal('sempro'); // Parameter: 'sempro', 'prasidang', atau 'sidang'
        
        if ($cek['status'] === false) {
            // Blokir Aksi
            session()->setFlashdata("message", ["icon" => "error", "title" => "Akses Ditolak", "text" => $cek['msg']]);
            return redirect()->back();
        }
        
        // Jika lolos, kirim warning (jika ada) ke view atau flashdata info
        if (!empty($cek['msg'])) {
            session()->setFlashdata("warning_deadline", $cek['msg']);
        }

        // [BARU] Cegat proses insert jika jadwal tutup
        $cek = $this->checkJadwal('sempro');
        if ($cek['status'] === false) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Gagal", "text" => "Jadwal Pengajuan Proposal ditutup."]);
            return redirect()->back();
        }

        $validationRules = [

            'file_proposal' => [

                'rules' => 'uploaded[file_proposal]|mime_in[file_proposal,application/pdf]|ext_in[file_proposal,pdf]|max_size[file_proposal,10000]',

                'errors' => [

                    'uploaded' => 'Pilih File Proposal terlebih dahulu',

                    'mime_in' => 'File Proposal harus berupa PDF',

                    'ext_in' => 'File Proposal harus berekstensi .pdf',

                    'max_size' => 'Ukuran File Proposal tidak boleh lebih dari 10MB'

                ]

            ]

        ];



        if (!$this->validate($validationRules)) {

            $validation = \Config\Services::validation();

            session()->setFlashdata("message", ["icon" => "error", "title" => "Upload File Proposal Gagal", "text" => $validation->getError("file_proposal")]);

            return redirect()->to(base_url("mahasiswa/proposal"))->withInput();

        }

        // Validasi dan proses judul proposal
        $judul = $this->prosesJudulSkripsi(
            $this->request->getPost("judul")
        );

        if ($judul === false) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Judul Tidak Valid",
                "text" => "Judul wajib diisi, minimal 5 dan maksimal 255 karakter, serta tidak boleh mengandung tag HTML."
            ]);

            return redirect()->back()->withInput();
        }



        $npm = $this->request->getPost("npm", FILTER_SANITIZE_SPECIAL_CHARS);

        $fileProposal = $this->request->getFile("file_proposal");

        $newName = "Proposal_" . $npm . "_" . $this->generateTimeStamp() . "." . $fileProposal->getClientExtension();

        $fileProposal->move("folderProposal", $newName);



        $this->proposalModel->save([

            'judul' => $judul,

            'id_bidang' => $this->request->getPost("bidang", FILTER_SANITIZE_SPECIAL_CHARS),

            'sifat' => $this->request->getPost("sifat", FILTER_SANITIZE_SPECIAL_CHARS),

            'sumber' => $this->request->getPost("sumber", FILTER_SANITIZE_SPECIAL_CHARS),

            'file_proposal' => $newName,

            'npm' => $npm,

            'tanggal_upload' => date_format(Time::now('Asia/Jakarta', 'en_us'), 'Y-m-d H:i:s'),

            'dosen_usulan1' => $this->request->getPost("dosen_usulan1", FILTER_SANITIZE_SPECIAL_CHARS),

            'dosen_usulan2' => $this->request->getPost("dosen_usulan2", FILTER_SANITIZE_SPECIAL_CHARS),

            'kode_semester' => $this->semesterModel->getActiveSemester()['kode'],

            'status' => 'TERTUNDA'

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Tambah Proposal Berhasil", "text" => "Proposal berhasil ditambahkan"]);

        return redirect()->to(base_url("mahasiswa/proposal"));

    }



    public function updateProposal($idProposal)
    {

        //autentikasi

        



        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $proposal = $this->proposalModel->find($idProposal);

        $npm = $this->request->getPost("npm");



        $fileProposal = $this->request->getFile("file_proposal");

        $fileProposalBaru = $proposal['file_proposal'];

        if ($fileProposal != null) {

            $fileProposalBaru = "Proposal_" . $npm . "_" . $this->generateTimeStamp() . "." . $fileProposal->getClientExtension();

            $fileProposal->move("folderProposal", $fileProposalBaru);

        }

        // Validasi dan proses judul proposal
        $judul = $this->prosesJudulSkripsi(
            $this->request->getPost("judul")
        );

        if ($judul === false) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Judul Tidak Valid",
                "text" => "Judul wajib diisi, minimal 5 dan maksimal 255 karakter, serta tidak boleh mengandung tag HTML."
            ]);

            return redirect()->back()->withInput();
        }



        $this->proposalModel->update($idProposal, [

            'judul' => $judul,

            'id_bidang' => $this->request->getPost("bidang", FILTER_SANITIZE_SPECIAL_CHARS),

            'sifat' => $this->request->getPost("sifat", FILTER_SANITIZE_SPECIAL_CHARS),

            'sumber' => $this->request->getPost("sumber", FILTER_SANITIZE_SPECIAL_CHARS),

            'tanggal_upload' => date_format(Time::now('Asia/Jakarta', 'en_us'), 'Y-m-d H:i:s'),

            'file_proposal' => $fileProposalBaru,

            'dosen_usulan1' => $this->request->getPost("dosen_usulan1", FILTER_SANITIZE_SPECIAL_CHARS),

            'dosen_usulan2' => $this->request->getPost("dosen_usulan2", FILTER_SANITIZE_SPECIAL_CHARS),

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Ubah Proposal Berhasil", "text" => "Proposal berhasil diubah"]);

        return redirect()->back();

    }









    public function seminarProposal()
    {

        //autentikasi

        



        $mahasiswa = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $proposal = $this->proposalModel->getMahasiswaLastProposal($mahasiswa['npm']);

        $prodi = $this->prodiModel->find($mahasiswa['id_prodi']);

        $jadwalSempro = $this->semproModel->getJadwalByNPM($mahasiswa['npm']);

        $currentSemester = $this->semesterModel->getActiveSemester();

        $modeSempro = $this->modeSemproModel->getModeSempro($currentSemester['kode'], $prodi['id']);



        if ($modeSempro != null && $modeSempro['mode'] == 'Asinkronus') {

            for ($i = 0; $i < count($jadwalSempro); $i++) {

                $jadwalSempro[$i]['editable'] = $this->semproModel->isEditable($jadwalSempro[$i]['id']);

            }

            $data = [

                'title' => "Seminar Proposal",

                'mahasiswa' => $mahasiswa,

                'proposal' => $proposal,

                'modeSempro' => $modeSempro,

                'prodi' => $prodi,

                'jadwalSempro' => $jadwalSempro,

            ];



            return view("mahasiswa/seminar_proposal_asinkronus", $data);

        } elseif ($modeSempro != null && ($modeSempro['mode'] == 'Sinkronus Daring' || $modeSempro['mode'] == 'Sinkronus Luring')) {

            $dosen = $this->dosenModel->getDosenByProdi($prodi['id']);

            $data = [

                'title' => "Seminar Proposal",

                'mahasiswa' => $mahasiswa,

                'proposal' => $proposal,

                'prodi' => $prodi,

                'modeSempro' => $modeSempro,

                'jadwalSempro' => $jadwalSempro,

                'dosen' => $dosen,

            ];



            return view("mahasiswa/seminar_proposal_sinkronus", $data);

        } elseif ($modeSempro == null) {

            $data = [

                'title' => 'Seminar Proposal',

                'prodi' => $prodi,

            ];

            return view("mahasiswa/seminar_proposal_default", $data);

        }



    }



    public function insertVideoSempro()
    {

        //autentikasi

        



        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $id_proposal = $this->request->getPost("id_proposal", FILTER_SANITIZE_SPECIAL_CHARS);

        $link_video = $this->request->getPost("link_video", FILTER_SANITIZE_URL);



        $this->semproModel->insert([

            'id_proposal' => $id_proposal,

            'link_video' => $link_video,

            'tanggal' => date_format(Time::now('Asia/Jakarta', 'en_us'), 'Y-m-d H:i:s'),

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Pengumpulan Video Seminar Proposal Berhasil", "text" => "Link Video Seminar Proposal berhasil dikumpulkan"]);

        return redirect()->to(base_url("mahasiswa/seminarProposal"));

    }



    public function updateVideoSempro($id)
    {

        //autentikasi

       



        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $id_proposal = $this->request->getPost("id_proposal", FILTER_SANITIZE_SPECIAL_CHARS);

        $link_video = $this->request->getPost("link_video", FILTER_SANITIZE_URL);



        $this->semproModel->update($id, [

            'id_proposal' => $id_proposal,

            'link_video' => $link_video,

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Pengubahan Video Seminar Proposal Berhasil", "text" => "Link Video Seminar Proposal berhasil diubah"]);

        return redirect()->to(base_url("mahasiswa/seminarProposal"));

    }















    public function pembimbing()
    {

        //autentikasi

        

        $mahasiswa = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $lastSkripsi = $this->skripsiModel->getMahasiswaLastSkripsi($mahasiswa['npm']);

        $dosenPembimbing = array();

        $kodeSemester = $this->request->getGet("semester");

        $currentSemester = $this->semesterModel->getActiveSemester();
        if ($kodeSemester != null)
            $currentSemester = $this->semesterModel->find($kodeSemester);

        $skripsi = [];
        if ($currentSemester != null) {
            if ($lastSkripsi != null) {
                $dosenPembimbing = $this->pembimbingModel->getAllPembimbingByIdSkripsiAndKodeSemester($lastSkripsi['id'], $currentSemester['kode']);
            }

            $hasilBimbingan = $this->catatanBimbinganModel->getAllCatatanByNpmAndKodeSemester($mahasiswa['npm'], $currentSemester['kode']);
        }
        $allSemester = $this->semesterModel->orderBy("status", "ASC")->orderBy("kode", "DESC")->get()->getResultArray();

        $data = [

            'title' => "Catatan Bimbingan Skripsi",

            'mahasiswa' => $mahasiswa,

            'dosenPembimbing' => $dosenPembimbing,

            'hasilBimbingan' => $hasilBimbingan,

            'semesterList' => $allSemester,

            'currentSemester' => $currentSemester,

        ];



        return view("mahasiswa/pembimbing", $data);

    }



    public function insertHasilBimbingan()
    {

        //autentikasi

        



        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $idPembimbing = $this->request->getPost("pembimbing");

        $materi = $this->request->getPost("materi", FILTER_SANITIZE_SPECIAL_CHARS);

        $tanggal = date_create($this->request->getPost("tanggalBimbingan"));

        $status = 'TERTUNDA';



        $this->catatanBimbinganModel->insert([

            'id_pembimbing' => $idPembimbing,

            'hasil_bimbingan' => $materi,

            'tanggal_bimbingan' => date_format($tanggal, 'Y-m-d'),

            'status' => $status,

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Pembuatan Catatan Hasil Bimbingan Berhasil", "text" => "Catatan Hasil Bimbingan telah berhasil disimpan"]);

        return redirect()->to(base_url("mahasiswa/pembimbing"));

    }



    public function updateHasilBimbingan($idCatatan)
    {

        //autentikasi

       



        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $idPembimbing = $this->request->getPost("pembimbing");

        $materi = $this->request->getPost("materi", FILTER_SANITIZE_SPECIAL_CHARS);

        $tanggal = date_create($this->request->getPost("tanggalBimbingan"));

        $status = 'TERTUNDA';



        $this->catatanBimbinganModel->update($idCatatan, [

            'id_pembimbing' => $idPembimbing,

            'hasil_bimbingan' => $materi,

            'tanggal_bimbingan' => date_format($tanggal, 'Y-m-d'),

            'status' => $status,

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Pengubahan Catatan Hasil Bimbingan Berhasil", "text" => "Catatan Hasil Bimbingan telah berhasil diubah"]);

        return redirect()->to(base_url("mahasiswa/pembimbing"));

    }



    public function deleteHasilBimbingan($idCatatan)
    {

        //autentikasi

        



        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $this->catatanBimbinganModel->delete($idCatatan);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Penghapusan Catatan Hasil Bimbingan Berhasil", "text" => "Catatan Hasil Bimbingan telah berhasil dihapus"]);

        return redirect()->to(base_url("mahasiswa/pembimbing"));

    }



    public function cetakFormBimbingan($npm)
    {

        //autentikasi

        



        $mahasiswa = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $lastSkripsi = $this->skripsiModel->getMahasiswaLastSkripsi($mahasiswa['npm']);

        $pembimbingIlmu1 = $this->pembimbingModel->getPembimbingIlmu1ByIdSkripsi($lastSkripsi['id']);

        $pembimbingIlmu2 = $this->pembimbingModel->getPembimbingIlmu2ByIdSkripsi($lastSkripsi['id']);

        $pembimbingAgama = $this->pembimbingModel->getPembimbingAgamaByIdSkripsi($lastSkripsi['id']);

        $hasilBimbinganIlmu1 = $this->catatanBimbinganModel->getWhere(['id_pembimbing' => $pembimbingIlmu1[0]['id']])->getResultArray();

        $hasilBimbinganIlmu2 = $this->catatanBimbinganModel->getWhere(['id_pembimbing' => $pembimbingIlmu2[0]['id']])->getResultArray();

        $hasilBimbinganAgama = $this->catatanBimbinganModel->getWhere(['id_pembimbing' => $pembimbingAgama[0]['id']])->getResultArray();

        $prodi = $this->prodiModel->find($mahasiswa['id_prodi']);

        $data = [

            'mahasiswa' => $mahasiswa,

            'lastSkripsi' => $lastSkripsi,

            'pembimbingIlmu1' => $pembimbingIlmu1,

            'pembimbingIlmu2' => $pembimbingIlmu2,

            'pembimbingAgama' => $pembimbingAgama,

            'hasilBimbinganIlmu1' => $hasilBimbinganIlmu1,

            'hasilBimbinganIlmu2' => $hasilBimbinganIlmu2,

            'hasilBimbinganAgama' => $hasilBimbinganAgama,

            'prodi' => $prodi,

        ];



        return view("cetakBimbingan", $data);

    }















    public function skripsi()
    {

        //autentikasi

        



        $mahasiswa = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $lastProposal = $this->proposalModel->getMahasiswaLastProposal($mahasiswa['npm']);

        $lastSkripsi = $this->skripsiModel->getMahasiswaLastSkripsi($mahasiswa['npm']);

        $bidang = $this->bidangModel->getBidangByProdi($mahasiswa['id_prodi']);

        $skripsi = $this->skripsiModel->getSkripsiByNpm($mahasiswa['npm']);

        $currentSemester = $this->semesterModel->getActiveSemester();

        $jenisTugasAkhir = \Config\Database::connect()
            ->table('jenis_tugas_akhir')
            ->where('id_prodi', (int) $mahasiswa['id_prodi'])
            ->orderBy('nama_jenis_tugas_akhir', 'ASC')
            ->get()
            ->getResultArray();

        $data = [

            'title' => 'Kelola Skripsi',

            'lastProposal' => $lastProposal,

            'lastSkripsi' => $lastSkripsi,

            'mahasiswa' => $mahasiswa,

            'bidang' => $bidang,

            'skripsi' => $skripsi,

            'currentSemester' => $currentSemester,

            'jenisTugasAkhir' => $jenisTugasAkhir,

        ];



        return view("mahasiswa/skripsi", $data);

    }



    public function detailSkripsi($idSkripsi)
{
    // Pastikan hanya mahasiswa yang login dapat mengakses
    if (!$this->authenticate(["mahasiswa"])) {
        return redirect()->to(base_url("unauthorized.php"));
    }

    // Ambil data mahasiswa yang sedang login
    $userSession = session()->get("user_session");
    $dataAkun = $this->mahasiswaModel->find($userSession['id'] ?? null);

    // Ambil detail skripsi berdasarkan ID
    $detailSkripsi = $this->skripsiModel->getDetailSkripsiById($idSkripsi);

    // Pastikan data tersedia
    if ($detailSkripsi == null || $dataAkun == null) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    // Pastikan skripsi tersebut milik mahasiswa yang login
    if ($detailSkripsi['npm'] != $dataAkun['npm']) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    // Ambil daftar bidang sesuai program studi
    $bidang = $this->bidangModel->getBidangByProdi($dataAkun['id_prodi']);

    // Kirim data ke halaman detail
    $data = [
        'title' => 'Detail Skripsi',
        'detailSkripsi' => $detailSkripsi,
        'bidang' => $bidang,
    ];

    return view("mahasiswa/detail_skripsi", $data);
}






// ======================================================
// VALIDASI, SANITIZATION, DAN UPPERCASE JUDUL SKRIPSI
// ======================================================

private function prosesJudulSkripsi($judul)
{
    // Memastikan input berupa teks
    if (!is_string($judul)) {
        return false;
    }

    // Menghapus spasi di awal dan akhir
    $judul = trim($judul);

    // Memastikan judul tidak kosong
    if ($judul === '') {
        return false;
    }

    // Menolak tag HTML
    if ($judul !== strip_tags($judul)) {
        return false;
    }

    // Menolak karakter kontrol
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $judul)) {
        return false;
    }

    // Merapikan spasi berlebihan
    $judul = preg_replace('/\s+/u', ' ', $judul);

    // Memeriksa panjang judul
    $panjang = mb_strlen($judul, 'UTF-8');

    if ($panjang < 5 || $panjang > 255) {
        return false;
    }

    // Mengubah judul menjadi huruf kapital
    return mb_strtoupper($judul, 'UTF-8');
}


    // ======================================================
    // MEMPERBARUI DATA SKRIPSI
    // ======================================================
    // Validasi backend untuk judul yang diperbarui:
    // 1. Memastikan judul tidak kosong.
    // 2. Memvalidasi panjang judul.
    // 3. Menolak tag HTML.
    // 4. Membersihkan spasi berlebihan.
    // 5. Menyimpan judul dalam huruf kapital.
    // ======================================================


    public function insertSkripsi()
{
    // Pastikan request menggunakan POST
    if ($this->request->getMethod() != 'post') {
        return redirect()->back();
    }

    // Ambil data mahasiswa yang sedang login
    $userSession = session()->get("user_session");

    $mahasiswa = $this->mahasiswaModel->find($userSession['id'] ?? null);

    if (!$mahasiswa) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Gagal",
            "text" => "Data mahasiswa tidak ditemukan."
        ]);

        return redirect()->to(base_url("auth/login"));
    }

    // Pastikan semester aktif tersedia
    $currentSemester = $this->semesterModel->getActiveSemester();

    if (!$currentSemester) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Gagal",
            "text" => "Tidak ada semester yang sedang aktif."
        ]);

        return redirect()->back();
    }

    // Pastikan proposal sudah diterima atau direvisi
    $lastProposal = $this->proposalModel->getMahasiswaLastProposal($mahasiswa['npm']);

    if (
        !$lastProposal ||
        !in_array($lastProposal['status'], ['DITERIMA', 'REVISI'], true) ||
        $lastProposal['kode_semester'] != $currentSemester['kode']
    ) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Gagal",
            "text" => "Anda harus memiliki proposal yang diterima atau direvisi pada semester aktif."
        ]);

        return redirect()->back();
    }

    // Pastikan mahasiswa belum memiliki data skripsi pada semester aktif
    $lastSkripsi = $this->skripsiModel->getMahasiswaLastSkripsi($mahasiswa['npm']);

    if ($lastSkripsi != null) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Gagal",
            "text" => "Data skripsi sudah tersedia."
        ]);

        return redirect()->back();
    }

    // Validasi judul
    $judul = $this->prosesJudulSkripsi(
        $this->request->getPost("judul")
    );

    if ($judul === false) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Judul Tidak Valid",
            "text" => "Judul wajib diisi, minimal 5 dan maksimal 255 karakter, serta tidak boleh mengandung tag HTML."
        ]);

        return redirect()->back()->withInput();
    }

    // Ambil data dari form
    $idBidang = (int) $this->request->getPost("bidang");
    $jenis = trim((string) $this->request->getPost("jenis_skripsi"));
    $sifat = $this->request->getPost("sifat");
    $sumber = $this->request->getPost("sumber");
    $klien = $this->request->getPost("klien", FILTER_SANITIZE_SPECIAL_CHARS);

    // Pastikan bidang sesuai dengan program studi mahasiswa
    $daftarBidang = $this->bidangModel->getBidangByProdi($mahasiswa['id_prodi']);

    $bidangValid = false;

    foreach ($daftarBidang as $bidang) {
        if ((int) $bidang['id'] === $idBidang) {
            $bidangValid = true;
            break;
        }
    }

    if (!$bidangValid) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Bidang Tidak Valid",
            "text" => "Silakan pilih bidang penelitian yang tersedia."
        ]);

        return redirect()->back()->withInput();
    }

    // Pastikan jenis tugas akhir tersedia untuk program studi mahasiswa
    $db = \Config\Database::connect();

    $jenisValid = $db->table('jenis_tugas_akhir')
        ->where('id_prodi', (int) $mahasiswa['id_prodi'])
        ->where('nama_jenis_tugas_akhir', $jenis)
        ->countAllResults();

    if (!$jenisValid) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Jenis Tugas Akhir Tidak Valid",
            "text" => "Silakan pilih jenis tugas akhir yang tersedia."
        ]);

        return redirect()->back()->withInput();
    }

    // Validasi sifat penelitian
    if (!in_array($sifat, ['Baru', 'Lanjutan'], true)) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Gagal",
            "text" => "Sifat penelitian tidak valid."
        ]);

        return redirect()->back()->withInput();
    }

    // Validasi sumber penelitian
    if (!in_array($sumber, ['Sendiri', 'Dosen', 'Teman', 'Keluarga'], true)) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Gagal",
            "text" => "Sumber penelitian tidak valid."
        ]);

        return redirect()->back()->withInput();
    }

    // Simpan data skripsi
    $berhasil = $this->skripsiModel->save([
        'judul' => $judul,
        'sumber' => $sumber,
        'sifat' => $sifat,
        'npm' => $mahasiswa['npm'],
        'id_bidang' => $idBidang,
        'tanggal_skripsi' => date_format(
            Time::now('Asia/Jakarta', 'en_us'),
            'Y-m-d H:i:s'
        ),
        'status' => 'Dalam Pengerjaan',
        'kode_semester' => $currentSemester['kode'],
        'jenis' => $jenis,
        'klien' => $jenis == 'Proyek Akhir' ? $klien : null,
        'jumlah_penolakan_sidang' => 0
    ]);

    if (!$berhasil) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Gagal",
            "text" => "Data skripsi gagal disimpan."
        ]);

        return redirect()->back()->withInput();
    }

    session()->setFlashdata("message", [
        "icon" => "success",
        "title" => "Tambah Skripsi Berhasil",
        "text" => "Data skripsi berhasil ditambahkan."
    ]);

    return redirect()->to(base_url("mahasiswa/skripsi"));
}




    public function updateSkripsi($idSkripsi)
    {
        // Autentikasi mahasiswa
        
        // Memastikan request menggunakan POST
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        // Mengambil data skripsi
        $skripsi = $this->skripsiModel->find($idSkripsi);

        if (!$skripsi) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Gagal",
                "text" => "Data skripsi tidak ditemukan."
            ]);

            return redirect()->back();
        }

        // Validasi dan proses judul
        $judul = $this->prosesJudulSkripsi(
            $this->request->getPost("judul")
        );

        if ($judul === false) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Judul Tidak Valid",
                "text" => "Judul wajib diisi, minimal 5 dan maksimal 255 karakter, serta tidak boleh mengandung tag HTML."
            ]);

            return redirect()->back()->withInput();
        }

        $npm = $this->request->getPost("npm");

        // Mengelola file skripsi
        $fileSkripsi = $this->request->getFile("file_skripsi");
        $fileSkripsiBaru = $skripsi['file_skripsi'];

        if ($fileSkripsi != null && $fileSkripsi->isValid()) {
            $fileSkripsiBaru = "Skripsi_" . $npm . "_" . $idSkripsi . "_" .
                $this->generateTimeStamp() . "." .
                $fileSkripsi->getClientExtension();

            $fileSkripsi->move("folderSkripsi", $fileSkripsiBaru);
        }

        $jenis = $this->request->getPost("jenis");
        $klien = $this->request->getPost(
            "klien",
            FILTER_SANITIZE_SPECIAL_CHARS
        );

        // Memperbarui data skripsi
        $this->skripsiModel->update($idSkripsi, [
            'file_skripsi' => $fileSkripsiBaru,
            'tanggal_selesai_skripsi' => date_format(
                Time::now('Asia/Jakarta', 'en_us'),
                'Y-m-d H:i:s'
            ),
            'judul' => $judul,
            'sifat' => $this->request->getPost(
                "sifat",
                FILTER_SANITIZE_SPECIAL_CHARS
            ),
            'sumber' => $this->request->getPost(
                "sumber",
                FILTER_SANITIZE_SPECIAL_CHARS
            ),
            'jenis' => $jenis,
            'klien' => $jenis == "Proyek Akhir" ? $klien : NULL,
            'id_bidang' => $this->request->getPost("bidang"),
        ]);

        session()->setFlashdata("message", [
            "icon" => "success",
            "title" => "Ubah Skripsi Berhasil",
            "text" => "Skripsi berhasil diubah!"
        ]);

        return redirect()->back();
    }









    public function pengajuanPraSidang()
    {

        

        // [BARU] Cek Jadwal Prasidang
        $cek = $this->checkJadwal('prasidang');

        $mahasiswa = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $lastSkripsi = $this->skripsiModel->getMahasiswaLastSkripsi($mahasiswa['npm']);

        $pembimbing = [];
        $pengajuanPrasidang = [];
        $seminarPrasidang = [];

        if ($lastSkripsi != null) {

            $lastSkripsi['nama_bidang'] = $this->bidangModel->find($lastSkripsi['id_bidang'])['nama'];

            $pembimbing = $this->pembimbingModel->getAllPembimbingByIdSkripsi($lastSkripsi['id']);

            $pengajuanPrasidang = $this->pengajuanPrasidangModel->getPengajuanPrasidangByIdSkripsi($lastSkripsi['id']);

            $seminarPrasidang = $this->seminarPrasidangModel->getJadwalByIdSkripsi($lastSkripsi['id']);
        }



        $data = [

            'title' => 'Pengajuan Seminar Prasidang',

            'mahasiswa' => $mahasiswa,

            'lastSkripsi' => $lastSkripsi,

            'pengajuanPrasidang' => $pengajuanPrasidang,

            'seminarPrasidang' => $seminarPrasidang,

            'pembimbing' => $pembimbing,

            // [BARU] Kirim status ke View
            'is_open' => $cek['status'],
            'deadline' => ($cek['data']) ? $cek['data']['tanggal_selesai'] : null,
            'pesan_tutup' => $cek['msg']
        ];

        return view("mahasiswa/pengajuan_pra_sidang", $data);

    }



    public function detailPengajuanPraSidang($idPengajuanPrasidang)
    {

        //autentikasi

        



        $dataAkun = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $detailPengajuan = $this->pengajuanPrasidangModel->getDetailPengajuanById($idPengajuanPrasidang);



        if ($detailPengajuan == null || $dataAkun == null) {

            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        }



        $skripsi = $this->skripsiModel->find($detailPengajuan['id_skripsi']);

        if ($skripsi == null || $skripsi['npm'] != $dataAkun['npm']) {

            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        }



        $data = [

            'title' => 'Detail Pengajuan Seminar Prasidang',

            'detailPengajuan' => $detailPengajuan,

        ];



        return view("mahasiswa/detail_pengajuan_prasidang", $data);

    }


    private function validasiFilePdfPrasidang($file, $namaDokumen, $wajib = true)
{
    // Memastikan file wajib diunggah
    if ($file === null) {
        return $wajib ? "$namaDokumen wajib diunggah." : null;
    }

    if ($file->getError() === UPLOAD_ERR_NO_FILE) {
        return $wajib ? "$namaDokumen wajib diunggah." : null;
    }

    // Memastikan proses upload berhasil
    if (!$file->isValid() || $file->hasMoved()) {
        return "$namaDokumen gagal diunggah. Silakan pilih ulang.";
    }

    // Memastikan ekstensi dan MIME sesuai PDF
    if (
        strtolower($file->getClientExtension()) !== 'pdf' ||
        strtolower($file->getMimeType()) !== 'application/pdf'
    ) {
        return "$namaDokumen harus menggunakan format PDF.";
    }

    // Maksimal 10 MB
    if ($file->getSize() > 10 * 1024 * 1024) {
        return "$namaDokumen tidak boleh lebih dari 10 MB.";
    }

    return null;
}





    public function insertPengajuanPraSidang()
    {

        // Memastikan hanya mahasiswa yang sudah login dapat mengakses.
        if (!$this->authenticate(["mahasiswa"])) {
            return redirect()->back()->with('message', [
                "icon" => "error",
                "title" => "Akses Ditolak",
                "text" => "Silakan login sebagai mahasiswa."
            ]);
        }

        $userSession = session()->get("user_session");
        $mahasiswa = $this->mahasiswaModel->find($userSession['id'] ?? null);

        if (!$mahasiswa) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Data Tidak Ditemukan",
                "text" => "Data mahasiswa tidak ditemukan."
            ]);

            return redirect()->to(base_url("auth/login"));
        }

    

        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }

        // [BARU] Cegat jika jadwal tutup
        $cek = $this->checkJadwal('prasidang');
        if ($cek['status'] === false) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Gagal", "text" => "Jadwal Seminar Pra-Sidang ditutup."]);
            return redirect()->back();
        }

        $id_skripsi = (int) $this->request->getPost("id_skripsi");

        $skripsi = $this->skripsiModel->find($id_skripsi);

        if (!$skripsi || (string) $skripsi['npm'] !== (string) $mahasiswa['npm']) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Akses Ditolak",
                "text" => "Anda tidak dapat mengajukan seminar untuk skripsi mahasiswa lain."
            ]);

            return redirect()->back();
        }

        $npm = $mahasiswa['npm'];



        $fileDraft = $this->request->getFile("file_draft");
        $lembarPersetujuan = $this->request->getFile("lembar_persetujuan");

        // Validasi file draft
        $pesanError = $this->validasiFilePdfPrasidang(
            $fileDraft,
            "Draft Skripsi"
        );

        // Validasi lembar persetujuan
        if ($pesanError === null) {
            $pesanError = $this->validasiFilePdfPrasidang(
                $lembarPersetujuan,
                "Lembar Persetujuan Seminar Prasidang"
            );
        }

        // Hentikan proses jika file tidak sesuai
        if ($pesanError !== null) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Upload Gagal",
                "text" => $pesanError
            ]);

            return redirect()->back()->withInput();
        }

        // Simpan file draft
        $fileDraftBaru = "Draft_" . $npm . "_" . $id_skripsi . "_" .
            $this->generateTimeStamp() . "." . $fileDraft->getClientExtension();

        $fileDraft->move("folderDraft", $fileDraftBaru);

        // Simpan lembar persetujuan
        $lembarPersetujuanBaru = "LembarPersetujuanPrasidang_" . $npm . "_" .
            $id_skripsi . "_" . $this->generateTimeStamp() . "." .
            $lembarPersetujuan->getClientExtension();

        $lembarPersetujuan->move(
            "folderLembarPersetujuanPrasidang",
            $lembarPersetujuanBaru
        );


        $this->pengajuanPrasidangModel->insert([

            "id_skripsi" => $id_skripsi,

            "file_draft" => $fileDraftBaru,

            "lembar_persetujuan" => $lembarPersetujuanBaru,

            "tanggal_pengajuan" => date_format(Time::now('Asia/Jakarta', 'en_us'), 'Y-m-d H:i:s'),

            "status" => "TERTUNDA",

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Upload Pengajuan Prasidang Berhasil", "text" => "Pengajuan Seminar Prasidang Berhasil diunggah"]);

        return redirect()->to(base_url("mahasiswa/pengajuanPraSidang"));

    }



    public function updatePengajuanPrasidang($idPengajuanPrasidang)
    {

        // Memastikan hanya mahasiswa yang sudah login dapat mengakses.
        if (!$this->authenticate(["mahasiswa"])) {
            return redirect()->back()->with('message', [
                "icon" => "error",
                "title" => "Akses Ditolak",
                "text" => "Silakan login sebagai mahasiswa."
            ]);
        }

        $userSession = session()->get("user_session");
        $mahasiswa = $this->mahasiswaModel->find($userSession['id'] ?? null);

        if (!$mahasiswa) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Data Tidak Ditemukan",
                "text" => "Data mahasiswa tidak ditemukan."
            ]);

            return redirect()->to(base_url("auth/login"));
        }

       


        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }


        $pengajuanPrasidang = $this->pengajuanPrasidangModel->find($idPengajuanPrasidang);

        if (!$pengajuanPrasidang) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $skripsi = $this->skripsiModel->find($pengajuanPrasidang['id_skripsi']);

        if (!$skripsi || (string) $skripsi['npm'] !== (string) $mahasiswa['npm']) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Akses Ditolak",
                "text" => "Anda tidak dapat mengubah pengajuan mahasiswa lain."
            ]);

            return redirect()->back();
        }

        $npm = $mahasiswa['npm'];
        $id_skripsi = $skripsi['id'];

       
        $fileDraft = $this->request->getFile("file_draft");
        $lembarPersetujuan = $this->request->getFile("lembar_persetujuan");

        // Validasi draft jika ada file baru
        if (
            $fileDraft !== null &&
            $fileDraft->getError() !== UPLOAD_ERR_NO_FILE
        ) {
            $pesanError = $this->validasiFilePdfPrasidang(
                $fileDraft,
                "Draft Skripsi",
                false
            );

            if ($pesanError !== null) {
                session()->setFlashdata("message", [
                    "icon" => "error",
                    "title" => "Perbaruan Upload Gagal",
                    "text" => $pesanError
                ]);

                return redirect()->back();
            }
        }

        // Validasi lembar persetujuan jika ada file baru
        if (
            $lembarPersetujuan !== null &&
            $lembarPersetujuan->getError() !== UPLOAD_ERR_NO_FILE
        ) {
            $pesanError = $this->validasiFilePdfPrasidang(
                $lembarPersetujuan,
                "Lembar Persetujuan Seminar Prasidang",
                false
            );

            if ($pesanError !== null) {
                session()->setFlashdata("message", [
                    "icon" => "error",
                    "title" => "Perbaruan Upload Gagal",
                    "text" => $pesanError
                ]);

                return redirect()->back();
            }
        }

        // Pertahankan nama file lama jika tidak ada penggantian
        $fileDraftBaru = $pengajuanPrasidang['file_draft'];
        $lembarPersetujuanBaru = $pengajuanPrasidang['lembar_persetujuan'];

        // Pindahkan draft setelah kedua file selesai divalidasi
        if (
            $fileDraft !== null &&
            $fileDraft->getError() !== UPLOAD_ERR_NO_FILE
        ) {
            $fileDraftBaru = "Draft_" . $npm . "_" . $id_skripsi . "_" .
                $this->generateTimeStamp() . "." . $fileDraft->getClientExtension();

            $fileDraft->move("folderDraft", $fileDraftBaru);
        }

        // Pindahkan lembar persetujuan jika ada file baru
        if (
            $lembarPersetujuan !== null &&
            $lembarPersetujuan->getError() !== UPLOAD_ERR_NO_FILE
        ) {
            $lembarPersetujuanBaru = "LembarPersetujuanPrasidang_" .
                $npm . "_" . $id_skripsi . "_" .
                $this->generateTimeStamp() . "." .
                $lembarPersetujuan->getClientExtension();

            $lembarPersetujuan->move(
                "folderLembarPersetujuanPrasidang",
                $lembarPersetujuanBaru
            );
        }


        $this->pengajuanPrasidangModel->update($idPengajuanPrasidang, [

            "file_draft" => $fileDraftBaru,

            "lembar_persetujuan" => $lembarPersetujuanBaru,

            "status" => 'TERTUNDA'

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Perbaruan Pengajuan Seminar Prasidang Berhasil", "text" => "Data Pengajuan Seminar Prasidang Berhasil diperbarui"]);

        return redirect()->back();

    }

















    public function seminarPrasidang()
    {

        //autentikasi

      



        $dataAkun = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $lastSkripsi = $this->skripsiModel->getMahasiswaLastSkripsi($dataAkun['npm']);

        $pengajuan = null;

        if ($lastSkripsi != null) {

            $pengajuan = $this->pengajuanPrasidangModel->where('id_skripsi', $lastSkripsi['id'])->orderBy('tanggal_pengajuan', 'DESC')->limit(1)->findAll();

        }



        if ($pengajuan != null) {

            $pengajuan = $pengajuan[0];

        }



        $jadwalSeminarPrasidang = $this->seminarPrasidangModel->getJadwalByNPM($dataAkun['npm']);



        $data = [

            'title' => "Jadwal Seminar Prasidang",

            'jadwalSeminarPrasidang' => $jadwalSeminarPrasidang,

            'lastSkripsi' => $lastSkripsi,

            'pengajuan' => $pengajuan,

        ];



        return view("mahasiswa/seminar_prasidang", $data);

    }



    public function pengajuanSidangSkripsi()
    {

        //autentikasi

    

        // [BARU] Cek Jadwal Sidang
        $cek = $this->checkJadwal('sidang');

        $dataAkun = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $lastSkripsi = $this->skripsiModel->getMahasiswaLastSkripsi($dataAkun['npm']);

        if ($lastSkripsi != null) {

            $lastSkripsi['nama_bidang'] = $this->bidangModel->find($lastSkripsi['id_bidang'])['nama'];

            $lastSkripsi['jumlah_bimbingan_ilmu'] = 0;

            $lastSkripsi['jumlah_bimbingan_agama'] = 0;

            foreach ($this->catatanBimbinganModel->getAllCatatanByLastSkripsi($lastSkripsi['id']) as $catatan) {

                if (($catatan['role'] == 'Pembimbing Ilmu 1' || $catatan['role'] == 'Pembimbing Ilmu 2') && $catatan['status'] == 'DISETUJUI') {

                    $lastSkripsi['jumlah_bimbingan_ilmu']++;

                }



                if ($catatan['role'] == 'Pembimbing Agama' && $catatan['status'] == 'DISETUJUI') {

                    $lastSkripsi['jumlah_bimbingan_agama']++;

                }

            }

        }



        $seminarPrasidang = null;

        if ($lastSkripsi != null) {

            $seminarPrasidang = $this->seminarPrasidangModel->where('id_skripsi', $lastSkripsi['id'])->orderBy('tanggal', 'DESC')->limit(1)->find();



            if (count($seminarPrasidang) == 0) {

                $seminarPrasidang = null;

            } else {

                $seminarPrasidang = $seminarPrasidang[0];

            }

        }



        $pengajuanSidangSkripsi = $this->pengajuanSidangModel->getPengajuanSidangByNpm($dataAkun['npm']);



        $data = [

            'title' => 'Pengajuan Sidang Skripsi',

            'dataAkun' => $dataAkun,

            'lastSkripsi' => $lastSkripsi,

            'seminarPrasidang' => $seminarPrasidang,

            'pengajuanSidangSkripsi' => $pengajuanSidangSkripsi,

            // [BARU] Kirim status ke View
            'is_open' => $cek['status'],
            'deadline' => ($cek['data']) ? $cek['data']['tanggal_selesai'] : null,
            'pesan_tutup' => $cek['msg']
        ];



        return view("mahasiswa/pengajuan_sidang_skripsi", $data);

    }



    public function detailPengajuanSidangSkripsi($idpengajuanSidangSkripsi)
    {

        //autentikasi

     



        $dataAkun = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $detailPengajuan = $this->pengajuanSidangModel->getDetailPengajuanById($idpengajuanSidangSkripsi);

        if ($detailPengajuan == null || $dataAkun == null) {

            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        }



        $skripsi = $this->skripsiModel->find($detailPengajuan['id_skripsi']);

        if ($skripsi == null || $skripsi['npm'] != $dataAkun['npm']) {

            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        }





        $data = [

            'title' => 'Detail Pengajuan Sidang Skripsi',

            'detailPengajuan' => $detailPengajuan,

        ];



        return view("mahasiswa/detail_pengajuan_sidang_skripsi", $data);

    }



    public function insertPengajuanSidangSkripsi()
    {

    // Memastikan hanya mahasiswa yang sudah login dapat mengakses.
    if (!$this->authenticate(["mahasiswa"])) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Akses Ditolak",
            "text" => "Silakan login sebagai mahasiswa."
        ]);

        return redirect()->back();
    }

    // Mengambil data mahasiswa berdasarkan sesi login.
    $userSession = session()->get("user_session");
    $mahasiswa = $this->mahasiswaModel->find($userSession['id'] ?? null);

    if (!$mahasiswa) {
        session()->setFlashdata("message", [
            "icon" => "error",
            "title" => "Data Tidak Ditemukan",
            "text" => "Data mahasiswa tidak ditemukan."
        ]);

        return redirect()->to(base_url("auth/login"));
    }


        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }

        // [BARU] Cegat jika jadwal tutup
        $cek = $this->checkJadwal('sidang');
        if ($cek['status'] === false) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Gagal", "text" => "Jadwal Sidang Skripsi ditutup."]);
            return redirect()->back();
        }

        // Mengambil ID skripsi dan memastikan kepemilikannya.
        $id_skripsi = (int) $this->request->getPost("id_skripsi");

        $skripsi = $this->skripsiModel->find($id_skripsi);

        if (!$skripsi || (string) $skripsi['npm'] !== (string) $mahasiswa['npm']) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Akses Ditolak",
                "text" => "Anda tidak dapat mengajukan sidang untuk skripsi mahasiswa lain."
            ]);

            return redirect()->back();
        }

        // Menggunakan NPM dari mahasiswa yang sedang login.
        $npm = $mahasiswa['npm'];



            // Mengambil ketiga file dari form.
        $fileDraftFinal = $this->request->getFile("file_draft_final");
        $formBimbingan = $this->request->getFile("file_form_bimbingan");
        $persyaratanSidang = $this->request->getFile("file_persyaratan_sidang");

        // Memeriksa semua file sebelum proses penyimpanan.
        $daftarFileSidang = [
            [
                'file' => $fileDraftFinal,
                'nama' => 'Draft Final Skripsi'
            ],
            [
                'file' => $formBimbingan,
                'nama' => 'Formulir Bimbingan Skripsi'
            ],
            [
                'file' => $persyaratanSidang,
                'nama' => 'Form Persyaratan Sidang Skripsi'
            ]
        ];

        foreach ($daftarFileSidang as $item) {
            $pesanError = $this->validasiFilePdfPrasidang(
                $item['file'],
                $item['nama']
            );

            if ($pesanError !== null) {
                session()->setFlashdata("message", [
                    "icon" => "error",
                    "title" => "Upload Gagal",
                    "text" => $pesanError
                ]);

                return redirect()->back();
            }
        }

        // Membuat nama file setelah semua validasi berhasil.
        $fileDraftFinalBaru = "Draft_Final_" . $npm . "_" . $id_skripsi . "_" . $this->generateTimeStamp() . ".pdf";

        $formBimbinganBaru = "Form_Bimbingan_" . $npm . "_" . $id_skripsi . "_" . $this->generateTimeStamp() . ".pdf";

        $persyaratanSidangBaru = "Persyaratan_Sidang_" . $npm . "_" . $id_skripsi . "_" . $this->generateTimeStamp() . ".pdf";

        // Memindahkan file ke folder masing-masing.
        $fileDraftFinal->move("folderDraftFinal", $fileDraftFinalBaru);
        $formBimbingan->move("folderFormBimbingan", $formBimbinganBaru);
        $persyaratanSidang->move("folderPersyaratanSidang", $persyaratanSidangBaru);



        $this->pengajuanSidangModel->insert([

            "id_skripsi" => $id_skripsi,

            "file_draft_final" => $fileDraftFinalBaru,

            "file_form_bimbingan" => $formBimbinganBaru,

            "file_persyaratan_sidang" => $persyaratanSidangBaru,

            "tanggal_pengajuan" => date_format(Time::now('Asia/Jakarta', 'en_us'), 'Y-m-d H:i:s'),

            "status" => "TERTUNDA",

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Upload Pengajuan Sidang Berhasil", "text" => "Pengajuan Sidang Skripsi Berhasil diunggah"]);

        return redirect()->to(base_url("mahasiswa/pengajuanSidangSkripsi"));

    }



    public function updatePengajuanSidangSkripsi($idpengajuanSidangSkripsi)
    {

        // Memastikan hanya mahasiswa yang sudah login dapat mengakses.
        if (!$this->authenticate(["mahasiswa"])) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Akses Ditolak",
                "text" => "Silakan login sebagai mahasiswa."
            ]);

            return redirect()->back();
        }

        // Mengambil data mahasiswa berdasarkan sesi login.
        $userSession = session()->get("user_session");
        $mahasiswa = $this->mahasiswaModel->find($userSession['id'] ?? null);

        if (!$mahasiswa) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Data Tidak Ditemukan",
                "text" => "Data mahasiswa tidak ditemukan."
            ]);

            return redirect()->to(base_url("auth/login"));
        }




        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        // Mengambil data pengajuan berdasarkan ID.
$pengajuanSidang = $this->pengajuanSidangModel->find($idpengajuanSidangSkripsi);

        // Memastikan pengajuan tersedia.
        if (!$pengajuanSidang) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Data Tidak Ditemukan",
                "text" => "Data pengajuan sidang tidak ditemukan."
            ]);

            return redirect()->back();
        }

        // Memeriksa kepemilikan skripsi.
        $id_skripsi = (int) $pengajuanSidang['id_skripsi'];
        $skripsi = $this->skripsiModel->find($id_skripsi);

        if (!$skripsi || (string) $skripsi['npm'] !== (string) $mahasiswa['npm']) {
            session()->setFlashdata("message", [
                "icon" => "error",
                "title" => "Akses Ditolak",
                "text" => "Anda tidak dapat mengubah pengajuan sidang mahasiswa lain."
            ]);

            return redirect()->back();
        }

        // Menggunakan NPM dari sesi mahasiswa.
        $npm = $mahasiswa['npm'];


        // Mengambil ketiga file dari form.
        $fileDraftFinal = $this->request->getFile("file_draft_final");
        $formBimbingan = $this->request->getFile("file_form_bimbingan");
        $persyaratanSidang = $this->request->getFile("file_persyaratan_sidang");

        // Memeriksa file yang diunggah.
        // Ketiga file bersifat opsional saat proses update.
        $daftarFileSidang = [
            [
                'file' => $fileDraftFinal,
                'nama' => 'Draft Final Skripsi'
            ],
            [
                'file' => $formBimbingan,
                'nama' => 'Formulir Bimbingan Skripsi'
            ],
            [
                'file' => $persyaratanSidang,
                'nama' => 'Form Persyaratan Sidang Skripsi'
            ]
        ];

        // Validasi semua file sebelum ada file yang dipindahkan.
        foreach ($daftarFileSidang as $item) {
            $pesanError = $this->validasiFilePdfPrasidang(
                $item['file'],
                $item['nama'],
                false
            );

            if ($pesanError !== null) {
                session()->setFlashdata("message", [
                    "icon" => "error",
                    "title" => "Upload Gagal",
                    "text" => $pesanError
                ]);

                return redirect()->back();
            }
        }

        // Mempertahankan nama file lama jika tidak ada penggantian.
        $fileDraftFinalBaru = $pengajuanSidang['file_draft_final'];
        $formBimbinganBaru = $pengajuanSidang['file_form_bimbingan'];
        $persyaratanSidangBaru = $pengajuanSidang['file_persyaratan_sidang'];

        // Memproses Draft Final Skripsi jika ada file pengganti.
        if ($fileDraftFinal && $fileDraftFinal->getError() !== UPLOAD_ERR_NO_FILE) {
            $fileDraftFinalBaru = "Draft_Final_" . $npm . "_" . $id_skripsi . "_" . $this->generateTimeStamp() . ".pdf";

            $fileDraftFinal->move("folderDraftFinal", $fileDraftFinalBaru);
        }

        // Memproses Formulir Bimbingan jika ada file pengganti.
        if ($formBimbingan && $formBimbingan->getError() !== UPLOAD_ERR_NO_FILE) {
            $formBimbinganBaru = "Form_Bimbingan_" . $npm . "_" . $id_skripsi . "_" . $this->generateTimeStamp() . ".pdf";

            $formBimbingan->move("folderFormBimbingan", $formBimbinganBaru);
        }

        // Memproses Persyaratan Sidang jika ada file pengganti.
        if ($persyaratanSidang && $persyaratanSidang->getError() !== UPLOAD_ERR_NO_FILE) {
            $persyaratanSidangBaru = "Persyaratan_Sidang_" . $npm . "_" . $id_skripsi . "_" . $this->generateTimeStamp() . ".pdf";

            $persyaratanSidang->move("folderPersyaratanSidang", $persyaratanSidangBaru);
        }



        $this->pengajuanSidangModel->update($idpengajuanSidangSkripsi, [

            "file_draft_final" => $fileDraftFinalBaru,

            "file_form_bimbingan" => $formBimbinganBaru,

            "file_persyaratan_sidang" => $persyaratanSidangBaru,

            "status" => 'TERTUNDA'

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Update Pengajuan Sidang Skripsi Berhasil", "text" => "Pengajuan Sidang Skripsi Berhasil diubah"]);

        return redirect()->back();

    }













    public function sidangSkripsi()
    {

        //autentikasi





        $dataAkun = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $lastSkripsi = $this->skripsiModel->getMahasiswaLastSkripsi($dataAkun['npm']);

        $pengajuan = null;

        if ($lastSkripsi != null) {

            $pengajuan = $this->pengajuanSidangModel->getWhere(['id_skripsi' => $lastSkripsi['id']])->getResultArray();

        }



        if ($pengajuan != null && count($pengajuan) == 1) {

            $pengajuan = $pengajuan[0];

        }



        $jadwalSidangSkripsi = $this->sidangSkripsiModel->getJadwalByNPM($dataAkun['npm']);



        $data = [

            'title' => "Jadwal Sidang Skripsi",

            'jadwalSidangSkripsi' => $jadwalSidangSkripsi,

            'lastSkripsi' => $lastSkripsi,

            'pengajuan' => $pengajuan,

        ];



        return view("mahasiswa/sidang_skripsi", $data);

    }


    public function hasilSidangSkripsi($idSidangSkripsi)
    {
        //autentikasi


        $dataAkun = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $sidangSkripsi = $this->sidangSkripsiModel->find($idSidangSkripsi);
        if ($sidangSkripsi == null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $lastSkripsi = $this->skripsiModel->find($sidangSkripsi['id_skripsi']);

        if ($lastSkripsi == null || $lastSkripsi['npm'] != $dataAkun['npm']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tahunAkademik = explode("-", $lastSkripsi['kode_semester'])[0];
        $semester = explode("-", $lastSkripsi['kode_semester'])[1];

        if ($tahunAkademik <= BATAS_TAHUN_AKADEMIK_LAMA && $semester <= BATAS_SEMESTER_LAMA) {
            return $this->hasilSidangSkripsiOld($lastSkripsi, $sidangSkripsi);
        } else {
            return $this->hasilSidangSkripsiNew($lastSkripsi, $sidangSkripsi);
        }
    }
    private function hasilSidangSkripsiOld($lastSkripsi, $sidangSkripsi)
    {
        $pembimbingIlmu1 = $this->pembimbingModel->getPembimbingIlmu1ByIdSkripsi($lastSkripsi['id']);
        $pembimbingIlmu2 = $this->pembimbingModel->getPembimbingIlmu2ByIdSkripsi($lastSkripsi['id']);
        $pembimbingAgama = $this->pembimbingModel->getPembimbingAgamaByIdSkripsi($lastSkripsi['id']);
        $penguji = $this->dosenModel->find($sidangSkripsi['dosen_penguji']);

        $idSidangSkripsi = $sidangSkripsi['id'];
        $nilaiPembimbing1 = $this->penilaianSidangModel->getWhere(['id_sidang_skripsi' => $idSidangSkripsi, 'id_dosen' => $pembimbingIlmu1[0]['id_dosen']])->getResultArray();
        $nilaiPembimbing2 = $this->penilaianSidangModel->getWhere(['id_sidang_skripsi' => $idSidangSkripsi, 'id_dosen' => $pembimbingIlmu2[0]['id_dosen']])->getResultArray();
        $nilaiPembimbingAgama = $this->penilaianSidangModel->getWhere(['id_sidang_skripsi' => $idSidangSkripsi, 'id_dosen' => $pembimbingAgama[0]['id_dosen']])->getResultArray();
        $nilaiPenguji = $this->penilaianSidangModel->getWhere(['id_sidang_skripsi' => $idSidangSkripsi, 'id_dosen' => $sidangSkripsi['dosen_penguji']])->getResultArray();

        $data = [
            'title' => 'Hasil Sidang Skripsi',
            'lastSkripsi' => $lastSkripsi,
            'sidangSkripsi' => $sidangSkripsi,
            'pembimbingIlmu1' => $pembimbingIlmu1,
            'pembimbingIlmu2' => $pembimbingIlmu2,
            'pembimbingAgama' => $pembimbingAgama,
            'penguji' => $penguji,
            'nilaiPembimbing1' => $nilaiPembimbing1,
            'nilaiPembimbing2' => $nilaiPembimbing2,
            'nilaiPembimbingAgama' => $nilaiPembimbingAgama,
            'nilaiPenguji' => $nilaiPenguji,
        ];

        return view("mahasiswa/hasil_sidang_skripsi", $data);
    }

    private function hasilSidangSkripsiNew($lastSkripsi, $sidangSkripsi)
    {
        $idSidangSkripsi = $sidangSkripsi['id'];
        $detailSidangSkripsi = $this->sidangSkripsiModel->getDetailSidangSkripsiById($idSidangSkripsi);

        if (count($detailSidangSkripsi) == 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }


        if ($lastSkripsi['jenis'] == JENIS_SKRIPSI_REGULER) {
            return $this->hasilSidangSkripsiNonProyek($idSidangSkripsi, $sidangSkripsi, $lastSkripsi, $detailSidangSkripsi);
        } else {
            return $this->hasilSidangSkripsiProyek($idSidangSkripsi, $sidangSkripsi, $lastSkripsi, $detailSidangSkripsi);
        }

    }


    private function hasilSidangSkripsiNonProyek($idSidangSkripsi, $sidangSkripsi, $lastSkripsi, $detailSidangSkripsi)
    {
        $nilaiPenguji = $this->penilaianSidangNewModel->getPenilaianSidang($detailSidangSkripsi[0]['dosen_penguji'], 'Dosen Penguji', $sidangSkripsi["id"]);
        $nilaiPembimbing1 = $this->penilaianSidangNewModel->getPenilaianSidang($detailSidangSkripsi[0]['id_pembimbing1'], 'Pembimbing Ilmu 1', $sidangSkripsi["id"]);
        $nilaiPembimbing2 = $this->penilaianSidangNewModel->getPenilaianSidang($detailSidangSkripsi[0]['id_pembimbing2'], 'Pembimbing Ilmu 2', $sidangSkripsi["id"]);
        $nilaiPembimbingAgama = $this->penilaianSidangNewModel->getPenilaianSidang($detailSidangSkripsi[0]['id_pembimbing_agama'], 'Pembimbing Agama', $sidangSkripsi["id"]);

        $rubrikNilai = $this->penilaianSidangParamModel->getRubrikPdsi2023();

        $rataRataNilai = $this->penilaianSidangNewModel->getAverageByIdSidang($lastSkripsi['id'], $idSidangSkripsi,
            $detailSidangSkripsi[0]['dosen_penguji'], $detailSidangSkripsi[0]['id_pembimbing1'],
            $detailSidangSkripsi[0]['id_pembimbing2'], $detailSidangSkripsi[0]['id_pembimbing_agama']);

        $data = [

            'title' => 'Hasil Sidang Skripsi',

            'lastSkripsi' => $lastSkripsi,

            'sidangSkripsi' => $sidangSkripsi,

            'detailSidangSkripsi' => $detailSidangSkripsi[0],

            'nilaiPembimbing1' => $nilaiPembimbing1,

            'nilaiPembimbing2' => $nilaiPembimbing2,

            'nilaiPembimbingAgama' => $nilaiPembimbingAgama,

            'nilaiPenguji' => $nilaiPenguji,

            'rubrikNilai' => $rubrikNilai,

            'avgNilai' => $rataRataNilai,

        ];



        return view("mahasiswa/hasil_sidang_skripsi_non_proyek", $data);
    }

    private function hasilSidangSkripsiProyek($idSidangSkripsi, $sidangSkripsi, $lastSkripsi, $detailSidangSkripsi)
    {
        $nilaiPenguji = $this->penilaianSidangNewModel->getPenilaianSidang($detailSidangSkripsi[0]['dosen_penguji'], 'Penguji', $sidangSkripsi["id"]);
        $nilaiPembimbing = $this->penilaianSidangNewModel->getPenilaianSidang($detailSidangSkripsi[0]['id_pembimbing1'], 'Pembimbing Ilmu', $sidangSkripsi["id"]);
        $nilaiPembimbingAgama = $this->penilaianSidangNewModel->getPenilaianSidang($detailSidangSkripsi[0]['id_pembimbing_agama'], 'Pembimbing Agama', $sidangSkripsi["id"]);
        $nilaiKlien = $this->penilaianSidangNewModel->getPenilaianSidang($detailSidangSkripsi[0]['id_klien'], 'Klien', $sidangSkripsi["id"]);

        $rubrikPenguji = $this->penilaianSidangParamModel->getRubrikByPemangkuKepentingan('Penguji');
        $rubrikPembimbing = $this->penilaianSidangParamModel->getRubrikByPemangkuKepentingan('Pembimbing Ilmu');
        $rubrikPembimbingAgama = $this->penilaianSidangParamModel->getRubrikByPemangkuKepentingan('Pembimbing Agama');
        $rubrikKlien = $this->penilaianSidangParamModel->getRubrikByPemangkuKepentingan('Klien');

        $totalBobot = $this->penilaianSidangParamModel->getAllSumBobot();
        $totalNilai = $this->penilaianSidangNewModel->getTotalProyekByIdSidang($lastSkripsi['id'], $idSidangSkripsi, $lastSkripsi['jenis']);

        $data = [

            'title' => 'Hasil Sidang Skripsi',

            'lastSkripsi' => $lastSkripsi,

            'sidangSkripsi' => $sidangSkripsi,

            'detailSidangSkripsi' => $detailSidangSkripsi[0],

            'nilaiPembimbing' => $nilaiPembimbing,

            'nilaiPembimbingAgama' => $nilaiPembimbingAgama,

            'nilaiPenguji' => $nilaiPenguji,

            'nilaiKlien' => $nilaiKlien,

            'rubrikPenguji' => $rubrikPenguji,

            'rubrikPembimbing' => $rubrikPembimbing,

            'rubrikPembimbingAgama' => $rubrikPembimbingAgama,

            'rubrikKlien' => $rubrikKlien,

            'totalBobot' => $totalBobot,

            'totalNilai' => $totalNilai,
        ];



        return view("mahasiswa/hasil_sidang_skripsi_proyek", $data);
    }

















    public function makalah()
    {

        //autentikasi





        $dataAkun = $this->mahasiswaModel->find(session()->get("user_session")['id']);

        $lastSkripsi = $this->skripsiModel->getMahasiswaLastSkripsi($dataAkun['npm']);

        $bidang = $this->bidangModel->getBidangByProdi($dataAkun['id_prodi']);

        $makalah = $this->makalahModel->getWhere(['npm' => $dataAkun['npm']])->getResultArray();





        $data = [

            'title' => 'Kelola Makalah',

            'dataAkun' => $dataAkun,

            'lastSkripsi' => $lastSkripsi,

            'bidang' => $bidang,

            'makalah' => $makalah,

            'activeSemester' => $this->semesterModel->getActiveSemester(),

        ];



        return view("mahasiswa/makalah", $data);

    }



    public function insertMakalah()
    {

        //autentikasi





        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $validationRules = [

            'file_makalah' => [

                'rules' => 'uploaded[file_makalah]|mime_in[file_makalah,application/pdf]|ext_in[file_makalah,pdf]|max_size[file_makalah,10000]',

                'errors' => [

                    'uploaded' => 'Pilih File Makalah terlebih dahulu',

                    'mime_in' => 'File Makalah harus berupa PDF',

                    'ext_in' => 'File Makalah harus berekstensi .pdf',

                    'max_size' => 'Ukuran File Makalah tidak boleh lebih dari 10MB'

                ]

            ]

        ];



        if (!$this->validate($validationRules)) {

            $validation = \Config\Services::validation();

            session()->setFlashdata("message", ["icon" => "error", "title" => "Unggah File Makalah Gagal", "text" => $validation->getError("file_makalah")]);

            return redirect()->to(base_url("mahasiswa/makalah"))->withInput();

        }



        $npm = $this->request->getPost("npm");

        $lastSkripsi = $this->skripsiModel->getMahasiswaLastSkripsi($npm);
        if ($lastSkripsi['status'] != STATUS_SKRIPSI_LULUS) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Unggah Makalah Gagal", "text" => "Anda belum bisa mengumpulkan makalah karena belum dinyatakan lulus"]);
            return redirect()->to(base_url("mahasiswa/makalah"));
        }

        $judul = $this->request->getPost("judul", FILTER_SANITIZE_SPECIAL_CHARS);

        $deskripsi = $this->request->getPost("deskripsi", FILTER_SANITIZE_SPECIAL_CHARS);

        $kata_kunci = $this->request->getPost("kata_kunci", FILTER_SANITIZE_SPECIAL_CHARS);

        $id_bidang = $this->request->getPost("bidang");



        $fileMakalah = $this->request->getFile("file_makalah");

        $fileMakalahBaru = "Makalah_" . $npm . "_" . $this->generateTimeStamp() . "." . $fileMakalah->getClientExtension();

        $fileMakalah->move("folderMakalah", $fileMakalahBaru);



        $this->makalahModel->insert([

            "judul" => $judul,

            "deskripsi" => $deskripsi,

            "kata_kunci" => $kata_kunci,

            "file_makalah" => $fileMakalahBaru,

            "npm" => $npm,

            "tanggal_upload" => date_format(Time::now('Asia/Jakarta', 'en_us'), 'Y-m-d H:i:s'),

            "id_bidang" => $id_bidang,

            "kode_semester" => $lastSkripsi['kode_semester'],

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Unggah Makalah Berhasil", "text" => "Makalah Anda telah berhasil diunggah"]);

        return redirect()->to(base_url("mahasiswa/makalah"));

    }



    public function updateMakalah($idMakalah)
    {

        //autentikasi





        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $makalah = $this->makalahModel->find($idMakalah);

        $npm = $this->request->getPost("npm");



        $fileMakalah = $this->request->getFile("file_makalah");

        $fileMakalahBaru = $makalah['file_makalah'];

        if ($fileMakalah != null) {

            $fileMakalahBaru = "Makalah_" . $npm . "_" . $this->generateTimeStamp() . "." . $fileMakalah->getClientExtension();

            $fileMakalah->move("folderMakalah", $fileMakalahBaru);

        }



        $judul = $this->request->getPost("judul", FILTER_SANITIZE_SPECIAL_CHARS);

        $deskripsi = $this->request->getPost("deskripsi", FILTER_SANITIZE_SPECIAL_CHARS);

        $kata_kunci = $this->request->getPost("kata_kunci", FILTER_SANITIZE_SPECIAL_CHARS);

        $id_bidang = $this->request->getPost("bidang");



        $this->makalahModel->update($idMakalah, [

            "file_makalah" => $fileMakalahBaru,

            "judul" => $judul,

            "deskripsi" => $deskripsi,

            "kata_kunci" => $kata_kunci,

            "id_bidang" => $id_bidang,

        ]);



        session()->setFlashdata("message", ["icon" => "success", "title" => "Perbarui Makalah Berhasil", "text" => "Makalah Anda telah berhasil diperbarui"]);

        return redirect()->to(base_url("mahasiswa/makalah"));

    }













    public function downloadKhs($npm)
    {
        //autentikasi
        if (!$this->authenticate(["mahasiswa", "tendik", "kaprodi", "fakultas", "dosen"])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak');
        }

        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $namaFileKhs = $this->mahasiswaModel->find($npm);

        if ($namaFileKhs == null || empty($namaFileKhs['file_khs'])) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File KHS Gagal", "text" => "File KHS tidak ditemukan"]);
            return redirect()->back();
        }

        $filePath = $this->resolveFilePath("folderKHS/" . $namaFileKhs['file_khs']);
        if (!$filePath) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File KHS Gagal", "text" => "File fisik KHS tidak ditemukan di server (" . $namaFileKhs['file_khs'] . ")"]);
            return redirect()->back();
        }

        try {
            return $this->response->download($filePath, null);
        } catch (Exception $e) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File KHS Gagal", "text" => "Terjadi kesalahan pada saat mengunduh"]);
            return redirect()->back();
        }
    }

    public function downloadKrs($npm)
    {
        //autentikasi
        if (!$this->authenticate(["mahasiswa", "tendik", "kaprodi", "fakultas", "dosen"])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak');
        }

        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $namaFileKrs = $this->mahasiswaModel->find($npm);

        if ($namaFileKrs == null || empty($namaFileKrs['file_krs'])) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File KRS Gagal", "text" => "File KRS tidak ditemukan"]);
            return redirect()->back();
        }

        $filePath = $this->resolveFilePath("folderKRS/" . $namaFileKrs['file_krs']);
        if (!$filePath) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File KRS Gagal", "text" => "File fisik KRS tidak ditemukan di server (" . $namaFileKrs['file_krs'] . ")"]);
            return redirect()->back();
        }

        try {
            return $this->response->download($filePath, null);
        } catch (Exception $e) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File KRS Gagal", "text" => "Terjadi kesalahan pada saat mengunduh"]);
            return redirect()->back();
        }
    }

    public function downloadPersetujuanSkripsi($npm)
    {
        //autentikasi
        if (!$this->authenticate(["mahasiswa", "tendik", "kaprodi", "fakultas", "dosen"])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak');
        }

        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $namaFilePengajuan = $this->mahasiswaModel->find($npm);

        if ($namaFilePengajuan == null || empty($namaFilePengajuan['file_persetujuan_skripsi'])) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Persetujuan Skripsi Gagal", "text" => "File Persetujuan Skripsi tidak ditemukan"]);
            return redirect()->back();
        }

        $filePath = $this->resolveFilePath("folderPersetujuanSkripsi/" . $namaFilePengajuan['file_persetujuan_skripsi']);
        if (!$filePath) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Persetujuan Skripsi Gagal", "text" => "File fisik Persetujuan Skripsi tidak ditemukan di server (" . $namaFilePengajuan['file_persetujuan_skripsi'] . ")"]);
            return redirect()->back();
        }

        try {
            return $this->response->download($filePath, null);
        } catch (Exception $e) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Persetujuan Skripsi Gagal", "text" => "Terjadi kesalahan pada saat mengunduh"]);
            return redirect()->back();
        }
    }

    public function downloadProposal($idProposal)
    {
        //autentikasi
        if (!$this->authenticate(["mahasiswa", "tendik", "kaprodi", "fakultas", "dosen"])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak');
        }

        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $proposal = $this->proposalModel->find($idProposal);
        $namaFileProposal = $proposal['file_proposal'] ?? null;

        if ($namaFileProposal == null) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Proposal Gagal", "text" => "File Proposal tidak ditemukan"]);
            return redirect()->back();
        }

        $filePath = $this->resolveFilePath("folderProposal/" . $namaFileProposal);
        if (!$filePath) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Proposal Gagal", "text" => "File fisik Proposal tidak ditemukan di server (" . $namaFileProposal . ")"]);
            return redirect()->back();
        }

        try {
            return $this->response->download($filePath, null);
        } catch (Exception $e) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Proposal Gagal", "text" => "Terjadi kesalahan pada saat mengunduh"]);
            return redirect()->back();
        }
    }

    public function downloadDraft($id)
    {
        //autentikasi
        if (!$this->authenticate(["mahasiswa", "tendik", "kaprodi", "fakultas", "dosen"])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak');
        }

        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $namaFilePengajuan = $this->pengajuanPrasidangModel->find($id);

        if ($namaFilePengajuan == null || empty($namaFilePengajuan['file_draft'])) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Unduh File Draft Skripsi Gagal", "text" => "File Draft Skripsi tidak ditemukan"]);
            return redirect()->back();
        }

        $filePath = $this->resolveFilePath("folderDraft/" . $namaFilePengajuan['file_draft']);
        if (!$filePath) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Unduh File Draft Skripsi Gagal", "text" => "File fisik Draft Skripsi tidak ditemukan di server (" . $namaFilePengajuan['file_draft'] . ")"]);
            return redirect()->back();
        }

        try {
            return $this->response->download($filePath, null);
        } catch (Exception $e) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Unduh File Draft Skripsi Gagal", "text" => "Terjadi kesalahan pada saat mengunduh"]);
            return redirect()->back();
        }
    }

    public function downloadLembarPersetujuan($id)
    {
        //autentikasi
        if (!$this->authenticate(["mahasiswa", "tendik", "kaprodi", "fakultas", "dosen"])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak');
        }

        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $namaFilePengajuan = $this->pengajuanPrasidangModel->find($id);

        if ($namaFilePengajuan == null || empty($namaFilePengajuan['lembar_persetujuan'])) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Unduh Lembar Persetujuan Seminar Prasidang Gagal", "text" => "Lembar Persetujuan Seminar Prasidang tidak ditemukan"]);
            return redirect()->back();
        }

        $filePath = $this->resolveFilePath("folderLembarPersetujuanPrasidang/" . $namaFilePengajuan['lembar_persetujuan']);
        if (!$filePath) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Unduh Lembar Persetujuan Seminar Prasidang Gagal", "text" => "File fisik Lembar Persetujuan Seminar Prasidang tidak ditemukan di server (" . $namaFilePengajuan['lembar_persetujuan'] . ")"]);
            return redirect()->back();
        }

        try {
            return $this->response->download($filePath, null);
        } catch (Exception $e) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Unduh Lembar Persetujuan Seminar Prasidang Gagal", "text" => "Terjadi kesalahan pada saat mengunduh"]);
            return redirect()->back();
        }
    }

    public function downloadDraftFinal($id)
    {
        //autentikasi
        if (!$this->authenticate(["mahasiswa", "tendik", "kaprodi", "fakultas", "dosen"])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak');
        }

        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $namaFileDraftFinal = $this->pengajuanSidangModel->find($id);

        if ($namaFileDraftFinal == null || empty($namaFileDraftFinal['file_draft_final'])) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Draft Final Skripsi Gagal", "text" => "File Draft Final Skripsi tidak ditemukan"]);
            return redirect()->back();
        }

        $filePath = $this->resolveFilePath("folderDraftFinal/" . $namaFileDraftFinal['file_draft_final']);
        if (!$filePath) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Draft Final Skripsi Gagal", "text" => "File fisik Draft Final Skripsi tidak ditemukan di server (" . $namaFileDraftFinal['file_draft_final'] . ")"]);
            return redirect()->back();
        }

        try {
            return $this->response->download($filePath, null);
        } catch (Exception $e) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Draft Final Skripsi Gagal", "text" => "Terjadi kesalahan pada saat mengunduh"]);
            return redirect()->back();
        }
    }

    public function downloadFormBimbingan($id)
    {
        //autentikasi
        if (!$this->authenticate(["mahasiswa", "tendik", "kaprodi", "fakultas", "dosen"])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak');
        }

        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $namaFormBimbingan = $this->pengajuanSidangModel->find($id);

        if ($namaFormBimbingan == null || empty($namaFormBimbingan['file_form_bimbingan'])) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Form Bimbingan Skripsi Gagal", "text" => "File Form Bimbingan Skripsi tidak ditemukan"]);
            return redirect()->back();
        }

        $filePath = $this->resolveFilePath("folderFormBimbingan/" . $namaFormBimbingan['file_form_bimbingan']);
        if (!$filePath) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Form Bimbingan Skripsi Gagal", "text" => "File fisik Form Bimbingan Skripsi tidak ditemukan di server (" . $namaFormBimbingan['file_form_bimbingan'] . ")"]);
            return redirect()->back();
        }

        try {
            return $this->response->download($filePath, null);
        } catch (Exception $e) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Form Bimbingan Skripsi Gagal", "text" => "Terjadi kesalahan pada saat mengunduh"]);
            return redirect()->back();
        }
    }

    public function downloadPersyaratanSidang($id)
    {
        //autentikasi
        if (!$this->authenticate(["mahasiswa", "tendik", "kaprodi", "fakultas", "dosen"])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak');
        }

        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $namaFilePersyaratanSidang = $this->pengajuanSidangModel->find($id);

        if ($namaFilePersyaratanSidang == null || empty($namaFilePersyaratanSidang['file_persyaratan_sidang'])) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Persyaratan Sidang Skripsi Gagal", "text" => "File Persyaratan Sidang Skripsi tidak ditemukan"]);
            return redirect()->back();
        }

        $filePath = $this->resolveFilePath("folderPersyaratanSidang/" . $namaFilePersyaratanSidang['file_persyaratan_sidang']);
        if (!$filePath) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Persyaratan Sidang Skripsi Gagal", "text" => "File fisik Persyaratan Sidang Skripsi tidak ditemukan di server (" . $namaFilePersyaratanSidang['file_persyaratan_sidang'] . ")"]);
            return redirect()->back();
        }

        try {
            return $this->response->download($filePath, null);
        } catch (Exception $e) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Download File Persyaratan Sidang Skripsi Gagal", "text" => "Terjadi kesalahan pada saat mengunduh"]);
            return redirect()->back();
        }
    }

    public function downloadSkripsi($idSkripsi)
    {
        //autentikasi
        if (!$this->authenticate(["mahasiswa", "tendik", "kaprodi", "fakultas", "dosen"])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak');
        }

        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $skripsi = $this->skripsiModel->find($idSkripsi);

        if ($skripsi == null || empty($skripsi['file_skripsi'])) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Unduh File Skripsi Gagal", "text" => "File Skripsi tidak ditemukan"]);
            return redirect()->back();
        }

        $filePath = $this->resolveFilePath("folderSkripsi/" . $skripsi['file_skripsi']);
        if (!$filePath) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Unduh File Skripsi Gagal", "text" => "File fisik Skripsi tidak ditemukan di server (" . $skripsi['file_skripsi'] . ")"]);
            return redirect()->back();
        }

        try {
            return $this->response->download($filePath, null);
        } catch (Exception $e) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Unduh File Skripsi Gagal", "text" => "Terjadi kesalahan pada saat mengunduh"]);
            return redirect()->back();
        }
    }

















    public function deleteKhs($npm)
    {

        //autentikasi




        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $namaFileKhs = $this->mahasiswaModel->find($npm);

        if ($namaFileKhs == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Hapus File KHS Gagal", "text" => "File KHS tidak ditemukan"]);

            return redirect()->to(base_url("mahasiswa/pengajuanPenulisanSkripsi"));

        }

        unlink("folderKHS/" . $namaFileKhs['file_khs']);

        $this->mahasiswaModel->update($npm, [

            "file_khs" => null,

            "status_persetujuan_skripsi" => null,

        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Hapus File KHS Berhasil", "text" => "File KHS berhasil dihapus"]);

        return redirect()->to(base_url("mahasiswa/pengajuanPenulisanSkripsi"));

    }



    public function deleteKrs($npm)
    {

        //autentikasi





        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $namaFileKrs = $this->mahasiswaModel->find($npm);

        if ($namaFileKrs == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Hapus File KRS Gagal", "text" => "File KRS tidak ditemukan"]);

            return redirect()->to(base_url("mahasiswa/pengajuanPenulisanSkripsi"));

        }

        unlink("folderKRS/" . $namaFileKrs['file_krs']);

        $this->mahasiswaModel->update($npm, [

            "file_krs" => null,

            "status_persetujuan_skripsi" => null,

        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Hapus File KRS Berhasil", "text" => "File KRS berhasil dihapus"]);

        return redirect()->to(base_url("mahasiswa/pengajuanPenulisanSkripsi"));

    }



    public function deletePersetujuanSkripsi($npm)
    {

        //autentikasi





        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $namaFilePengajuan = $this->mahasiswaModel->find($npm);

        if ($namaFilePengajuan == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Hapus File Persetujuan Skripsi Gagal", "text" => "File Persetujuan Skripsi tidak ditemukan"]);

            return redirect()->to(base_url("mahasiswa/pengajuanPenulisanSkripsi"));

        }

        unlink("folderPersetujuanSkripsi/" . $namaFilePengajuan['file_persetujuan_skripsi']);

        $this->mahasiswaModel->update($npm, [

            "file_persetujuan_skripsi" => null,

            "status_persetujuan_skripsi" => null,

        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Hapus File Persetujuan Skripsi Berhasil", "text" => "File Persetujuan Skripsi berhasil dihapus"]);

        return redirect()->to(base_url("mahasiswa/pengajuanPenulisanSkripsi"));

    }



    public function deleteProposal($id)
    {

        //autentikasi





        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $namaFileProposal = $this->proposalModel->find($id);

        if ($namaFileProposal == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Hapus File Proposal Gagal", "text" => "File Proposal tidak ditemukan"]);

            return redirect()->back();

        }

        unlink("folderProposal/" . $namaFileProposal['file_proposal']);

        $this->proposalModel->update($id, [

            "file_proposal" => null,

            "status" => 'TERTUNDA',

        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Hapus File Proposal Berhasil", "text" => "File Proposal berhasil dihapus"]);

        return redirect()->back();

    }



    public function deleteDraft($id)
    {

        //autentikasi





        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $namaFileDraft = $this->pengajuanPrasidangModel->find($id);

        if ($namaFileDraft == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Hapus File Draft Skripsi Gagal", "text" => "File Draft Skripsi tidak ditemukan"]);

            return redirect()->back();

        }

        unlink("folderDraft/" . $namaFileDraft['file_draft']);

        $this->pengajuanPrasidangModel->update($id, [

            "file_draft" => null,

            "status" => 'TERTUNDA',

        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Hapus File Draft Skripsi Berhasil", "text" => "File Draft Skripsi berhasil dihapus"]);

        return redirect()->back();

    }



    public function deleteLembarPersetujuan($id)
    {

        //autentikasi





        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $namaFileDraft = $this->pengajuanPrasidangModel->find($id);

        if ($namaFileDraft == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Hapus File Lembar Persetujuan Seminar Prasidang Gagal", "text" => "File Lembar Persetujuan Seminar Prasidang tidak ditemukan"]);

            return redirect()->back();

        }

        unlink("folderLembarPersetujuanPrasidang/" . $namaFileDraft['lembar_persetujuan']);

        $this->pengajuanPrasidangModel->update($id, [

            "lembar_persetujuan" => null,

            "status" => 'TERTUNDA',

        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Hapus File Lembar Persetujuan Seminar Prasidang Berhasil", "text" => "File Lembar Persetujuan Seminar Prasidang berhasil dihapus"]);

        return redirect()->back();

    }



    public function deleteDraftFinal($id)
    {

        //autentikasi





        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $namaFileDraftFinal = $this->pengajuanSidangModel->find($id);

        if ($namaFileDraftFinal == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Hapus File Draft Final Skripsi Gagal", "text" => "File Draft Final Skripsi tidak ditemukan"]);

            return redirect()->back();

        }

        unlink("folderDraftFinal/" . $namaFileDraftFinal['file_draft_final']);

        $this->pengajuanSidangModel->update($id, [

            "file_draft_final" => null,

            "status" => 'TERTUNDA',

        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Hapus File Draft Final Skripsi Berhasil", "text" => "File Draft Final Skripsi berhasil dihapus"]);

        return redirect()->back();

    }



    public function deleteFormBimbingan($id)
    {

        //autentikasi




        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $namaFileFormBimbingan = $this->pengajuanSidangModel->find($id);

        if ($namaFileFormBimbingan == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Hapus File Form Bimbingan Skripsi Gagal", "text" => "File Form Bimbingan Skripsi tidak ditemukan"]);

            return redirect()->back();

        }

        unlink("folderFormBimbingan/" . $namaFileFormBimbingan['file_form_bimbingan']);

        $this->pengajuanSidangModel->update($id, [

            "file_form_bimbingan" => null,

            "status" => 'TERTUNDA',

        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Hapus File Form Bimbingan Skripsi Berhasil", "text" => "File Form Bimbingan Skripsi berhasil dihapus"]);

        return redirect()->back();

    }



    public function deletePersyaratanSidang($id)
    {

        //autentikasi





        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $namaFilePersyaratanSidang = $this->pengajuanSidangModel->find($id);

        if ($namaFilePersyaratanSidang == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Hapus File Persyaratan Sidang Skripsi Gagal", "text" => "File Persyaratan Sidang Skripsi tidak ditemukan"]);

            return redirect()->back();

        }

        unlink("folderPersyaratanSidang/" . $namaFilePersyaratanSidang['file_persyaratan_sidang']);

        $this->pengajuanSidangModel->update($id, [

            "file_persyaratan_sidang" => null,

            "status" => 'TERTUNDA',

        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Hapus File Persyaratan Sidang Skripsi Berhasil", "text" => "File Persyaratan Sidang Skripsi berhasil dihapus"]);

        return redirect()->back();

    }



    public function deleteSkripsi($id)
    {

        //autentikasi




        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $namaFileSkripsi = $this->skripsiModel->find($id);

        if ($namaFileSkripsi == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Hapus File Skripsi Gagal", "text" => "File Skripsi tidak ditemukan"]);

            return redirect()->back();

        }

        unlink("folderSkripsi/" . $namaFileSkripsi['file_skripsi']);

        $this->skripsiModel->update($id, [

            "file_skripsi" => null,

            "tanggal_selesai_skripsi" => null,

        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Hapus File Skripsi Berhasil", "text" => "File Skripsi berhasil dihapus"]);

        return redirect()->back();

    }



    public function deleteMakalah($id)
    {

        //autentikasi

        



        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();

        }



        $namaFileMakalah = $this->makalahModel->find($id);

        if ($namaFileMakalah == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Hapus File Makalah Gagal", "text" => "File Makalah tidak ditemukan"]);

            return redirect()->back();

        }

        unlink("folderMakalah/" . $namaFileMakalah['file_makalah']);

        $this->makalahModel->update($id, [

            "file_makalah" => null,

            "tanggal_upload" => null,

        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Hapus File Skripsi Berhasil", "text" => "File Skripsi berhasil dihapus"]);

        return redirect()->back();

    }



    public function profile()
    {
        //autentikasi
        

        $dataAkun = $this->mahasiswaModel->getMahasiswaInfo(session()->get("user_session")['id']);
        if ($dataAkun == null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! empty($dataAkun['npm'])) {
            $terplotModel = new \App\Models\MahasiswaTerplotModel();
            $terplot = $terplotModel->where('npm', $dataAkun['npm'])->first();
            if ($terplot && ! empty($terplot['tanggal_sk_yudisium'])) {
                $dataAkun['tanggal_sk_yudisium'] = $terplot['tanggal_sk_yudisium'];
            }
        }

        $data = [
            'title' => 'Ubah Data Diri',
            'dataDiri' => $dataAkun,
        ];

        return view("mahasiswa/profile", $data);
    }


    public function saveProfile()
    {
        //autentikasi
       

        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $npm = $this->request->getPost("npm");
        $nama = $this->request->getPost("nama", FILTER_SANITIZE_SPECIAL_CHARS);
        $email = $this->request->getPost("email", FILTER_SANITIZE_SPECIAL_CHARS);

        $dataMahasiswa = $this->mahasiswaModel->getMahasiswaInfo($npm);
        if ($dataMahasiswa == null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($this->akunModel->isEmailAlreadyUsed($dataMahasiswa['id_akun'], $email)) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Ubah Data Diri Gagal", "text" => "Email telah digunakan!"]);
            return redirect()->to(base_url("Mahasiswa/profile"));
        }

        $this->akunModel->update($dataMahasiswa['id_akun'], [
            "email" => $email,
        ]);

        $this->mahasiswaModel->update($npm, [
            "nama" => $nama,
            "email" => $email,
        ]);


        session()->set("user_session", [
            'id' => $dataMahasiswa['npm'],
            'nama' => $nama,
            'email' => $email,
            'prodi' => $dataMahasiswa['inisial_prodi'],
            'roles' => ['mahasiswa'],
        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Ubah Data Diri Berhasil", "text" => "Data diri berhasil diubah!"]);
        return redirect()->to(base_url("Mahasiswa/profile"));
    }


    private function generateTimeStamp()
    {
        return date_format(Time::now('Asia/Jakarta', 'en_us'), 'YmdHis');
    }


    private function resolveFilePath(string $relativePath): ?string
    {
        $relativePath = ltrim($relativePath, '/\\');

        $candidates = [
            FCPATH . $relativePath,
            ROOTPATH . 'public/' . $relativePath,
            ROOTPATH . $relativePath,
            $relativePath,
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function authenticate($roles)
    {
        $userSession = session("user_session");

        if ($userSession == null) {
            return false;
        }

        $userRoles = AccessControl::rolesFromSession($userSession);

        if (AccessControl::isSuperAdmin($userRoles)) {
            return true;
        }

        return AccessControl::hasAnyRole($userRoles, (array) $roles);
    }

    public function statusProgres()
    {
        $npm = $this->currentNpm();
        $progresData = $this->skripsiModel->getThesisProgress($npm);

        $data = [
            'title'        => 'Status Progres Skripsi',
            'skripsi'      => $progresData ? $progresData['detail'] : null,
            'current_step' => $progresData ? $progresData['current_step'] : 0,
            'tahapan'      => [
                "Penyusunan", "Draft Proposal", "Jadwal Sempro", 
                "Judul Final", "Draft Prasidang", "Pra Sidang", 
                "Draft Sidang", "Jadwal Sidang", "Sidang Akhir"
            ]
        ];

        return view('mahasiswa/status_progres', $data);
    }

    // --- [BARU] Helper Function untuk Cek Jadwal ---
    private function checkJadwal($jenisKegiatan)
    {
        $session = session('user_session');
        if (!isset($session['id'])) return ['status' => false, 'data' => null, 'msg' => 'Sesi login tidak valid.'];

        // Ambil Data Mahasiswa & Semester Aktif
        $mhs = $this->mahasiswaModel->find($session['id']);
        $semesterAktif = $this->semesterModel->where('status', 'AKTIF')->first();

        if (!$semesterAktif) {
            return ['status' => false, 'data' => null, 'msg' => 'Tidak ada Semester Aktif saat ini.'];
        }

        // Cek Kalender
        $kalenderModel = new \App\Models\KalenderSkripsiModel();
        $jadwal = $kalenderModel->getJadwalAktif($jenisKegiatan, $mhs['id_prodi'], $semesterAktif['kode']);

        if ($jadwal) {
            return ['status' => true, 'data' => $jadwal, 'msg' => null]; // BUKA (Pesan kosong)
        } else {
            return ['status' => false, 'data' => null, 'msg' => 'Periode pendaftaran belum dibuka atau sudah berakhir.']; // TUTUP (Ada Pesan)
        }
    }


    // --- FITUR KELOLA HARD COVER (MAHASISWA) ---

    public function pengajuanHardcover()
    {

        $mahasiswa = $this->mahasiswaModel->find(session()->get("user_session")['id']);
        
        // 1. Ambil Data Skripsi Terakhir (Harus LULUS)
        $skripsi = $this->skripsiModel->getMahasiswaLastSkripsi($mahasiswa['npm']);
        
        // 2. Ambil List Hard Cover yang sudah diupload (Jika ada)
        // Kita pakai findAll() supaya strukturnya sama kayak tabel proposal (bisa list history kalau perlu)
        // Tapi logic-nya biasanya cuma 1 file final.
        $hardcoverList = [];
        if ($skripsi) {
            $hardcoverList = $this->pengajuanHardcoverModel
                            ->where('id_skripsi', $skripsi['id'])
                            ->orderBy('tanggal_pengajuan', 'DESC')
                            ->findAll();
        }

        $data = [
            'title' => "Kelola Hard Cover",
            'mahasiswa' => $mahasiswa,
            'skripsi' => $skripsi,
            'hardcoverList' => $hardcoverList
        ];

        return view('mahasiswa/pengajuan_hardcover', $data);
    }

    public function insertHardcover()
    {
        if ($this->request->getMethod() != 'post') return redirect()->back();

        // 1. Validasi
        $validationRules = [
            'file_hardcover' => [
                'rules' => 'uploaded[file_hardcover]|mime_in[file_hardcover,application/pdf]|ext_in[file_hardcover,pdf]|max_size[file_hardcover,10000]',
                'errors' => [
                    'uploaded' => 'Pilih File Hard Cover terlebih dahulu',
                    'mime_in' => 'File harus berupa PDF',
                    'ext_in' => 'File harus berekstensi .pdf',
                    'max_size' => 'Ukuran File tidak boleh lebih dari 10MB'
                ]
            ]
        ];

        if (!$this->validate($validationRules)) {
            $validation = \Config\Services::validation();
            session()->setFlashdata("message", ["icon" => "error", "title" => "Gagal", "text" => $validation->getError("file_hardcover")]);
            return redirect()->back()->withInput();
        }

        // 2. Proses Upload
        $idSkripsi = $this->request->getPost('id_skripsi');
        $npm = $this->request->getPost('npm');
        
        $file = $this->request->getFile('file_hardcover');
        $namaFile = "Hardcover_" . $npm . "_" . $this->generateTimeStamp() . "." . $file->getClientExtension();
        $file->move("folderHardcover", $namaFile);

        // 3. Simpan Database (Logika: Jika ditolak/baru -> Insert baru sebagai history atau Update status? 
        // Agar sesuai style 'Kelola Proposal' yang bentuknya list, kita Insert data baru saja biar ada history revisi)
        
        // Cek apakah ada data sebelumnya untuk di-update (Opsional: overwrite atau insert baru)
        // Disini saya buat UPDATE jika ada, agar tidak menumpuk sampah file, tapi status direset jadi TERTUNDA.
        $cekAda = $this->pengajuanHardcoverModel->where('id_skripsi', $idSkripsi)->first();

        if($cekAda) {
            // Update data lama (Revisi)
            $this->pengajuanHardcoverModel->update($cekAda['id'], [
                'file_hardcover' => $namaFile,
                'tanggal_pengajuan' => date_format(Time::now('Asia/Jakarta', 'en_us'), 'Y-m-d H:i:s'),
                'status' => 'TERTUNDA',
                'komentar_penolakan' => null // Reset komentar
            ]);
        } else {
            // Insert baru
            $this->pengajuanHardcoverModel->insert([
                'id_skripsi' => $idSkripsi,
                'file_hardcover' => $namaFile,
                'tanggal_pengajuan' => date_format(Time::now('Asia/Jakarta', 'en_us'), 'Y-m-d H:i:s'),
                'status' => 'TERTUNDA'
            ]);
        }

        session()->setFlashdata("message", ["icon" => "success", "title" => "Berhasil", "text" => "Hard Cover berhasil diunggah!"]);
        return redirect()->to(base_url("Mahasiswa/pengajuanHardcover"));
    }
}
