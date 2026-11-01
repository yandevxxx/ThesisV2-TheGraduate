<?php

namespace App\Controllers;

use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Models\MahasiswaTerplotModel;
use App\Models\SemesterModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            return redirect()->to(base_url(PortalRouter::CHOOSER))
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

    public function fakultas()
    {
        return $this->dashboard('fakultas', 'The Graduate');
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

        return view('graduate/fakultas/semester', [
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

    public function semesterStore()
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
        $semCode = (strtolower($rawSem) === 'ganjil' || $rawSem === '1') ? 1 : 2;
        $semName = ($semCode === 1) ? 'Ganjil' : 'Genap';
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

    public function semesterUpdate()
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
        $semCode = (strtolower($rawSem) === 'ganjil' || $rawSem === '1') ? 1 : 2;
        $semName = ($semCode === 1) ? 'Ganjil' : 'Genap';
        $kodeBaru = "{$tahunMulai}-{$semCode}";
        $namaBaru = "Tahun Akademik {$tahunMulai}/{$tahunSelesai} Semester {$semName}";

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

    public function semesterActivate($kode = null)
    {
        $auth = $this->ensureSemesterManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/semester'));
        }

        $kode = trim((string) ($kode ?? $this->request->getPost('kode')));
        $semesterModel = new SemesterModel();
        $semester = $semesterModel->find($kode);

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

    public function semesterNonactivate($kode = null)
    {
        $auth = $this->ensureSemesterManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/semester'));
        }

        $kode = trim((string) ($kode ?? $this->request->getPost('kode')));
        $semesterModel = new SemesterModel();
        $semester = $semesterModel->find($kode);

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

    public function semesterDelete($kode = null)
    {
        $auth = $this->ensureSemesterManager();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/semester'));
        }

        $kode = trim((string) ($kode ?? $this->request->getPost('kode')));
        $semesterModel = new SemesterModel();
        $semester = $semesterModel->find($kode);

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

    private function ensureMahasiswaPlotted(): ?array
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return null;
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! in_array('mahasiswa', $roles, true)) {
            return $userData; // Not a student, let existing checks handle
        }

        $npm = $userData['npm'] ?? $userData['id'] ?? null;
        if (! $npm) {
            return null;
        }

        $terplotModel = new MahasiswaTerplotModel();
        $isPlotted = $terplotModel->where('npm', $npm)->countAllResults() > 0;

        if (! $isPlotted) {
            return false; // Indicates not plotted
        }

        return $userData;
    }

    public function pendaftaran_wisuda()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (in_array('mahasiswa', $roles, true)) {
            $npm = $userData['npm'] ?? $userData['id'] ?? null;
            if ($npm) {
                $terplotModel = new MahasiswaTerplotModel();
                if ($terplotModel->where('npm', $npm)->countAllResults() === 0) {
                    session()->set('active_side', 'graduate');
                    return view('graduate/mahasiswa/belum_terplot', [
                        'title' => 'Akses Ditolak',
                        'user'  => $userData,
                        'role'  => $roles,
                    ]);
                }
            } else {
                session()->set('active_side', 'graduate');
                return view('graduate/mahasiswa/belum_terplot', [
                    'title' => 'Akses Ditolak',
                    'user'  => $userData,
                    'role'  => $roles,
                ]);
            }
        }

        return $this->halamanMenu('pendaftaran_wisuda', 'Pendaftaran Wisuda');
    }

    public function tracer_studi()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (in_array('mahasiswa', $roles, true)) {
            $npm = $userData['npm'] ?? $userData['id'] ?? null;
            if ($npm) {
                $terplotModel = new MahasiswaTerplotModel();
                if ($terplotModel->where('npm', $npm)->countAllResults() === 0) {
                    session()->set('active_side', 'graduate');
                    return view('graduate/mahasiswa/belum_terplot', [
                        'title' => 'Akses Ditolak',
                        'user'  => $userData,
                        'role'  => $roles,
                    ]);
                }
            } else {
                session()->set('active_side', 'graduate');
                return view('graduate/mahasiswa/belum_terplot', [
                    'title' => 'Akses Ditolak',
                    'user'  => $userData,
                    'role'  => $roles,
                ]);
            }
        }

        return $this->halamanMenu('tracer_studi', 'Tracer Studi Awal');
    }

    public function skl()
    {
        return $this->halamanMenu('skl', 'SKL', 'layanan SKL');
    }

    public function sk_yudisium()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $userData = $auth['user'];
        $roles    = $auth['roles'];
        $folder   = PortalRouter::graduateFolder($roles);

        if ($folder === null) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $terplotModel = new MahasiswaTerplotModel();

        $filters = [
            'q'      => trim((string) $this->request->getGet('q')),
            'status' => strtolower(trim((string) $this->request->getGet('status'))),
        ];

        if (! in_array($filters['status'], ['baru', 'transfer'], true)) {
            $filters['status'] = '';
        }

        $terplotModel->orderBy('nama', 'ASC');

        if ($filters['q'] !== '') {
            $terplotModel->groupStart()
                ->like('npm', $filters['q'])
                ->orLike('nama', $filters['q'])
                ->groupEnd();
        }

        if ($filters['status'] !== '') {
            $terplotModel->where('status', $filters['status']);
        }

        $perPage       = 20;
        $daftarTerplot = $terplotModel->paginate($perPage, 'terplot');
        $pager         = $terplotModel->pager;
        $totalRows     = $pager->getTotal('terplot');
        $currentPage   = $pager->getCurrentPage('terplot');

        session()->set('active_side', 'graduate');

        return view("graduate/{$folder}/sk_yudisium", [
            'title'         => 'SK Yudisium',
            'user'          => $userData,
            'role'          => $roles,
            'daftarTerplot' => $daftarTerplot,
            'filters'       => $filters,
            'pager'         => $pager,
            'perPage'       => $perPage,
            'totalRows'     => $totalRows,
            'currentPage'   => $currentPage,
        ]);
    }

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

    private function ensureStafForPlot(): ?array
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return null;
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! PortalRouter::canUseGraduate($roles)) {
            return null;
        }

        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin'])) {
            return null;
        }

        return [
            'user'   => $userData,
            'roles'  => $roles,
            'akunId' => $userData['akun_id'] ?? $userData['id'] ?? null,
        ];
    }

    public function skYudisiumDownloadTemplate()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Terplot');

        $sheet->setCellValue('A1', 'nama');
        $sheet->setCellValue('B1', 'npm');
        $sheet->setCellValue('C1', 'status');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        $sheet->setCellValue('A2', 'Contoh Mahasiswa');
        $sheet->setCellValue('B2', '2021010001');
        $sheet->setCellValue('C2', 'baru');

        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(15);

        $writer = new Xlsx($spreadsheet);
        $filename = 'Template_Daftar_Mahasiswa_Terplot_SK_Yudisium.xlsx';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment;filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody((function () use ($writer) {
                ob_start();
                $writer->save('php://output');
                return ob_get_clean();
            })());
    }

    public function skYudisiumUpload()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->back();
        }

        $validationRules = [
            'filePlot' => [
                'rules'  => 'uploaded[filePlot]|mime_in[filePlot,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet]|ext_in[filePlot,xls,xlsx]|max_size[filePlot,5120]',
                'errors' => [
                    'uploaded' => 'Pilih berkas Excel terlebih dahulu',
                    'mime_in'  => 'Berkas harus berupa file Excel (.xls atau .xlsx)',
                    'ext_in'   => 'Berkas harus berekstensi .xls atau .xlsx',
                    'max_size' => 'Ukuran berkas tidak boleh lebih dari 5MB',
                ],
            ],
        ];

        if (! $this->validate($validationRules)) {
            $validation = \Config\Services::validation();
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Unggah Gagal', 'text' => $validation->getError('filePlot')]);
            return redirect()->to(base_url('graduate/sk_yudisium'))->withInput();
        }

        $file = $this->request->getFile('filePlot');
        if (! $file->isValid() || $file->hasMoved()) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Unggah Gagal', 'text' => 'Berkas tidak valid']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $ext = strtolower($file->getExtension());
        $reader = null;
        if ($ext === 'xls') {
            $reader = IOFactory::createReader('Xls');
        } elseif ($ext === 'xlsx') {
            $reader = IOFactory::createReader('Xlsx');
        } else {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Unggah Gagal', 'text' => 'Ekstensi berkas tidak didukung']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        try {
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getTempName());
            $worksheet   = $spreadsheet->getActiveSheet();
            $highestRow  = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
        } catch (\Throwable $e) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Unggah Gagal', 'text' => 'Tidak dapat membaca berkas Excel']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        // Detect header
        $headerMap = [];
        $headerRow = 1;
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $val = trim((string) $worksheet->getCell($col . $headerRow)->getValue());
            if ($val === '') {
                continue;
            }
            $key = strtolower($val);
            if (in_array($key, ['nama', 'npm', 'status'], true)) {
                $headerMap[$key] = $col;
            }
        }

        if (! isset($headerMap['nama']) || ! isset($headerMap['npm']) || ! isset($headerMap['status'])) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Format Salah', 'text' => 'Kolom wajib tidak ditemukan. Pastikan terdapat kolom: nama, npm, status']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $valid   = [];
        $errors  = [];
        $seenFile = [];

        $terplotModel = new MahasiswaTerplotModel();
        $existing = $terplotModel->select('npm')->findAll();
        $existingMap = [];
        foreach ($existing as $e) {
            $existingMap[trim((string) $e['npm'])] = true;
        }

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $nama   = trim((string) $worksheet->getCell($headerMap['nama'] . $row)->getValue());
            $npm    = trim((string) $worksheet->getCell($headerMap['npm'] . $row)->getValue());
            $status = trim((string) $worksheet->getCell($headerMap['status'] . $row)->getValue());

            if ($nama === '' && $npm === '' && $status === '') {
                continue;
            }

            if ($nama === '') {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "Kolom 'nama' wajib diisi"];
                continue;
            }
            if ($npm === '') {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "Kolom 'npm' wajib diisi"];
                continue;
            }
            if (! ctype_digit($npm)) {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "NPM harus berupa angka"];
                continue;
            }
            if (strlen($npm) < 8 || strlen($npm) > 12) {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "Format NPM tidak valid"];
                continue;
            }

            $statusNorm = strtolower($status);
            if (! in_array($statusNorm, ['baru', 'transfer'], true)) {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "Status harus 'baru' atau 'transfer'"];
                continue;
            }

            if (isset($seenFile[$npm])) {
                $firstRow = $seenFile[$npm];
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "NPM duplikat dengan baris {$firstRow}"];
                continue;
            }
            $seenFile[$npm] = $row;

            if (isset($existingMap[$npm])) {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "NPM sudah terdaftar di daftar terplot"];
                continue;
            }

            $valid[] = [
                'npm'    => $npm,
                'nama'   => $nama,
                'status' => $statusNorm,
            ];
        }

        session()->set('sk_yudisium_plot_preview', [
            'valid'  => $valid,
            'errors' => $errors,
            'countValid' => count($valid),
            'countError' => count($errors),
            'uploaded_at' => date('Y-m-d H:i:s'),
            'uploaded_by' => $auth['akunId'],
        ]);

        return redirect()->to(base_url('graduate/skYudisiumPreview'));
    }

    public function skYudisiumPreview()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $preview = session()->get('sk_yudisium_plot_preview');
        if (! is_array($preview)) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ada Pratinjau', 'text' => 'Silakan unggah berkas Excel terlebih dahulu']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $roles = PortalRouter::normalizeRoles(session()->get('user_session'));

        return view('graduate/admin_tendik/sk_yudisium_preview', [
            'title'   => 'Pratinjau Daftar Terplot SK Yudisium',
            'user'    => session()->get('user_session'),
            'role'    => $roles,
            'preview' => $preview,
        ]);
    }

    public function skYudisiumSave()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->back();
        }

        $preview = session()->get('sk_yudisium_plot_preview');
        if (! is_array($preview) || empty($preview['valid'])) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ada Data', 'text' => 'Tidak ada data valid untuk disimpan']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $valid = $preview['valid'];
        $terplotModel = new MahasiswaTerplotModel();

        $npms = array_column($valid, 'npm');
        $existing = $terplotModel->select('npm')->whereIn('npm', $npms)->findAll();
        $existingMap = [];
        foreach ($existing as $e) {
            $existingMap[trim((string) $e['npm'])] = true;
        }

        $toInsert = [];
        $now = date('Y-m-d H:i:s');
        foreach ($valid as $row) {
            $npm = trim((string) $row['npm']);
            if (isset($existingMap[$npm])) {
                continue;
            }
            $toInsert[] = [
                'npm'         => $npm,
                'nama'        => $row['nama'],
                'status'      => $row['status'],
                'uploaded_by' => $auth['akunId'],
                'uploaded_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        if (empty($toInsert)) {
            session()->remove('sk_yudisium_plot_preview');
            session()->setFlashdata('message', ['icon' => 'info', 'title' => 'Tidak Ada yang Disimpan', 'text' => 'Semua data valid sudah terdaftar di daftar terplot']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        try {
            $terplotModel->insertBatch($toInsert);
        } catch (\Throwable $e) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Menyimpan', 'text' => 'Terjadi kesalahan saat menyimpan data']);
            return redirect()->to(base_url('graduate/skYudisiumPreview'));
        }

        session()->remove('sk_yudisium_plot_preview');
        session()->setFlashdata('message', ['icon' => 'success', 'title' => 'Berhasil', 'text' => count($toInsert) . ' data mahasiswa terplot berhasil disimpan']);
        return redirect()->to(base_url('graduate/sk_yudisium'));
    }

    public function skYudisiumClearPreview()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        session()->remove('sk_yudisium_plot_preview');
        return redirect()->to(base_url('graduate/sk_yudisium'));
    }

    public function pengambilan_ijazah()
    {
        return $this->halamanMenu('pengambilan_ijazah', 'Pengambilan Ijazah', 'Formulir Pengambilan Ijazah');
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
                return redirect()->to(base_url(PortalRouter::CHOOSER))
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
    private function halamanMenu($halaman, $judul, $fiturTerplot = null)
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

        // Fitur khusus mahasiswa hanya boleh dibuka setelah terplot SK Yudisium.
        if ($folder === 'mahasiswa' && $fiturTerplot !== null && $this->mahasiswaBelumTerplot($userData)) {
            session()->set('active_side', 'graduate');

            return view('graduate/mahasiswa/belum_terplot', [
                'title' => 'Akses Ditolak',
                'user'  => $userData,
                'role'  => $roles,
                'fitur' => $fiturTerplot,
            ]);
        }

        // Halaman belum tersedia untuk role ini -> kembali ke dashboard role.
        if (! is_file(APPPATH . "Views/graduate/{$folder}/{$halaman}.php")) {
            if ($folder === 'fakultas' && is_file(APPPATH . "Views/graduate/admin_tendik/{$halaman}.php")) {
                $folder = 'admin_tendik';
            } else {
                return redirect()->to(base_url(PortalRouter::graduateLanding($roles)));
            }
        }

        session()->set('active_side', 'graduate');

        return view("graduate/{$folder}/{$halaman}", [
            'title' => $judul,
            'user'  => $userData,
            'role'  => $roles,
        ]);
    }

    /**
     * Apakah mahasiswa pada session ini belum terdaftar di daftar terplot SK Yudisium.
     */
    private function mahasiswaBelumTerplot(array $userData): bool
    {
        $npm = $userData['npm'] ?? $userData['id'] ?? null;

        if (! $npm) {
            return true;
        }

        $terplotModel = new MahasiswaTerplotModel();

        return $terplotModel->where('npm', $npm)->countAllResults() === 0;
    }

    public function skYudisiumSetTanggal()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->back();
        }

        $npm     = trim((string) $this->request->getPost('npm'));
        $tanggal = trim((string) $this->request->getPost('tanggal_sk_yudisium'));

        if ($npm === '') {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'NPM tidak valid']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        if ($tanggal === '') {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Tanggal SK Yudisium wajib diisi']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $d = \DateTime::createFromFormat('Y-m-d', $tanggal);
        if (! $d || $d->format('Y-m-d') !== $tanggal) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Tanggal Tidak Valid', 'text' => 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $terplotModel = new MahasiswaTerplotModel();
        $mahasiswa    = $terplotModel->where('npm', $npm)->first();

        if (! $mahasiswa) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Mahasiswa tidak terdaftar dalam daftar terplot']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $terplotModel->update($mahasiswa['id'], ['tanggal_sk_yudisium' => $tanggal]);

        session()->setFlashdata('message', ['icon' => 'success', 'title' => 'Berhasil', 'text' => 'Tanggal SK Yudisium berhasil disimpan']);
        return redirect()->to(base_url('graduate/sk_yudisium'));
    }

    public function skYudisiumBulkSetTanggal()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $tanggal = trim((string) $this->request->getPost('tanggal_sk_yudisium'));
        $npms    = $this->request->getPost('npms');

        if ($tanggal === '') {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Tanggal SK Yudisium wajib diisi']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $d = \DateTime::createFromFormat('Y-m-d', $tanggal);
        if (! $d || $d->format('Y-m-d') !== $tanggal) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Tanggal Tidak Valid', 'text' => 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        if (! is_array($npms) || empty($npms)) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ada Data', 'text' => 'Pilih minimal satu mahasiswa terlebih dahulu']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $cleanNpms = array_filter(array_map('trim', $npms));
        if (empty($cleanNpms)) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ada Data', 'text' => 'Daftar NPM yang dipilih tidak valid']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $terplotModel = new MahasiswaTerplotModel();
        $now = date('Y-m-d H:i:s');
        $terplotModel->whereIn('npm', $cleanNpms)
            ->set(['tanggal_sk_yudisium' => $tanggal, 'updated_at' => $now])
            ->update();

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => count($cleanNpms) . ' mahasiswa berhasil diperbarui tanggal SK Yudisiumnya secara massal',
        ]);

        return redirect()->to(base_url('graduate/sk_yudisium'));
    }

    public function skYudisiumUpdateStatus()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $id     = (int) $this->request->getPost('id');
        $status = strtolower(trim((string) $this->request->getPost('status')));

        if ($id <= 0) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Data mahasiswa tidak valid']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        if (! in_array($status, ['baru', 'transfer'], true)) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => "Status harus 'baru' atau 'transfer'"]);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $terplotModel = new MahasiswaTerplotModel();
        $mahasiswa    = $terplotModel->find($id);

        if ($mahasiswa === null) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data mahasiswa terplot tidak ditemukan']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $now = date('Y-m-d H:i:s');
        $terplotModel->update($id, [
            'status'     => $status,
            'updated_at' => $now,
        ]);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => 'Status mahasiswa ' . $mahasiswa['nama'] . ' (' . $mahasiswa['npm'] . ') berhasil diubah menjadi ' . ucfirst($status),
        ]);

        return redirect()->to(base_url('graduate/sk_yudisium'));
    }

    public function skYudisiumDelete()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Data yang dipilih tidak valid']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $terplotModel = new MahasiswaTerplotModel();
        $mahasiswa    = $terplotModel->find($id);

        if ($mahasiswa === null) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data mahasiswa terplot tidak ditemukan atau sudah dihapus']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        try {
            if (! $terplotModel->delete($id)) {
                throw new \RuntimeException('Model delete returned false.');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menghapus mahasiswa terplot SK Yudisium id=' . $id . ': ' . $e->getMessage());

            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Data mahasiswa terplot gagal dihapus']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => 'Data mahasiswa terplot ' . $mahasiswa['nama'] . ' (' . $mahasiswa['npm'] . ') berhasil dihapus',
        ]);

        return redirect()->to(base_url('graduate/sk_yudisium'));
    }

    public function skYudisiumStore()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $nama   = trim((string) $this->request->getPost('nama'));
        $npm    = trim((string) $this->request->getPost('npm'));
        $status = strtolower(trim((string) $this->request->getPost('status')));

        $errors = [];

        if ($nama === '') {
            $errors[] = 'Nama mahasiswa wajib diisi';
        } elseif (mb_strlen($nama) > 150) {
            $errors[] = 'Nama mahasiswa maksimal 150 karakter';
        }

        if ($npm === '') {
            $errors[] = 'NPM wajib diisi';
        } elseif (! ctype_digit($npm)) {
            $errors[] = 'NPM harus berupa angka';
        } elseif (strlen($npm) < 8 || strlen($npm) > 12) {
            $errors[] = 'Format NPM tidak valid (8-12 digit)';
        }

        if (! in_array($status, ['baru', 'transfer'], true)) {
            $errors[] = "Status harus 'baru' atau 'transfer'";
        }

        $terplotModel = new MahasiswaTerplotModel();

        if ($errors === []) {
            $existing = $terplotModel->where('npm', $npm)->countAllResults();

            if ($existing > 0) {
                $errors[] = 'NPM sudah terdaftar di daftar terplot';
            }
        }

        if ($errors !== []) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Menambah',
                'text'  => implode(' ', $errors),
            ]);

            return redirect()->to(base_url('graduate/sk_yudisium'))->withInput();
        }

        $now = date('Y-m-d H:i:s');

        try {
            $insertId = $terplotModel->insert([
                'npm'         => $npm,
                'nama'        => $nama,
                'status'      => $status,
                'uploaded_by' => $auth['akunId'],
                'uploaded_at' => $now,
            ]);

            if ($insertId === false) {
                throw new \RuntimeException('Model insert returned false.');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menambah mahasiswa terplot SK Yudisium npm=' . $npm . ': ' . $e->getMessage());

            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Data mahasiswa terplot gagal disimpan']);
            return redirect()->to(base_url('graduate/sk_yudisium'))->withInput();
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => 'Data mahasiswa terplot ' . $nama . ' (' . $npm . ') berhasil ditambahkan',
        ]);

        return redirect()->to(base_url('graduate/sk_yudisium'));
    }


    /* ------------------------------------------------------------------
     |  Sistem Notifikasi Khusus The Graduate
     | ------------------------------------------------------------------ */

    /**
     * Mengambil notifikasi kelulusan & yudisium untuk staf/tendik/admin.
     */
    public function fetchNotif()
    {
        if (ob_get_length()) {
            ob_clean();
        }

        $userData = session()->get('user_session');
        if (! $userData) {
            return $this->response->setStatusCode(401)->setJSON(['unread' => 0, 'list' => []]);
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin'])) {
            return $this->response->setStatusCode(403)->setJSON(['unread' => 0, 'list' => []]);
        }

        $db = \Config\Database::connect();
        $notifList = [];
        $unreadCount = 0;
        $lastRead = session()->get('graduate_notif_last_read') ?? '2000-01-01 00:00:00';
        $readAll  = (bool) session()->get('graduate_notif_read_all');

        // 1. Notifikasi Mahasiswa Terplot SK Yudisium (belum ada tanggal SK)
        if ($db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            $unsettled = $db->table('mahasiswa_terplot_sk_yudisium')
                ->select('npm, nama, uploaded_at, created_at')
                ->where('tanggal_sk_yudisium IS NULL')
                ->orderBy('id', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();

            foreach ($unsettled as $row) {
                $itemTime = $row['uploaded_at'] ?? $row['created_at'] ?? date('Y-m-d H:i:s');
                $isRead = ($readAll || $itemTime <= $lastRead) ? 1 : 0;
                if ($isRead === 0) {
                    $unreadCount++;
                }

                $notifList[] = [
                    'pesan'         => 'SK Yudisium: ' . $row['nama'] . ' (Perlu tanggal SK)',
                    'is_read_admin' => $isRead,
                    'link'          => base_url('graduate/sk_yudisium'),
                ];
            }
        }

        // 2. Notifikasi Calon Wisudawan (Lulus Sidang Skripsi di TheSIS)
        if ($db->tableExists('skripsi')) {
            $lulusan = $db->table('skripsi')
                ->select('mahasiswa.nama, skripsi.npm, skripsi.tanggal_selesai_skripsi')
                ->join('mahasiswa', 'mahasiswa.npm = skripsi.npm')
                ->where('skripsi.status', 'Lulus')
                ->orderBy('skripsi.id', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();

            foreach ($lulusan as $row) {
                $itemTime = $row['tanggal_selesai_skripsi'] ?? date('Y-m-d H:i:s');
                $isRead = ($readAll || $itemTime <= $lastRead) ? 1 : 0;
                if ($isRead === 0) {
                    $unreadCount++;
                }

                $notifList[] = [
                    'pesan'         => 'Calon Wisudawan: ' . $row['nama'] . ' (' . $row['npm'] . ')',
                    'is_read_admin' => $isRead,
                    'link'          => base_url('graduate/calon_wisudawan'),
                ];
            }
        }

        return $this->response->setJSON(['unread' => $unreadCount, 'list' => $notifList]);
    }

    /**
     * Menandai semua notifikasi kelulusan telah dibaca.
     */
    public function markAsRead()
    {
        session()->set('graduate_notif_last_read', date('Y-m-d H:i:s'));
        session()->set('graduate_notif_read_all', true);

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Halaman riwayat notifikasi khusus kelulusan dan wisuda.
     */
    public function semua_notifikasi()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin'])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $db = \Config\Database::connect();
        $lastRead = session()->get('graduate_notif_last_read') ?? '2000-01-01 00:00:00';
        $readAll  = (bool) session()->get('graduate_notif_read_all');

        $skYudisium = [];
        if ($db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            $rows = $db->table('mahasiswa_terplot_sk_yudisium')
                ->select('npm, nama, status, tanggal_sk_yudisium, uploaded_at')
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $itemTime = $r['uploaded_at'] ?? date('Y-m-d H:i:s');
                $r['is_read'] = ($readAll || $itemTime <= $lastRead) ? 1 : 0;
                $skYudisium[] = $r;
            }
        }

        $calonWisudawan = [];
        if ($db->tableExists('skripsi')) {
            $rows = $db->table('skripsi')
                ->select('mahasiswa.nama, skripsi.npm, skripsi.tanggal_selesai_skripsi')
                ->join('mahasiswa', 'mahasiswa.npm = skripsi.npm')
                ->where('skripsi.status', 'Lulus')
                ->orderBy('skripsi.id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $itemTime = $r['tanggal_selesai_skripsi'] ?? date('Y-m-d H:i:s');
                $r['is_read'] = ($readAll || $itemTime <= $lastRead) ? 1 : 0;
                $calonWisudawan[] = $r;
            }
        }

        session()->set('active_side', 'graduate');

        return view('graduate/admin_tendik/semua_notifikasi', [
            'title'          => 'Riwayat Notifikasi Kelulusan',
            'user'           => $userData,
            'role'           => $roles,
            'skYudisium'     => $skYudisium,
            'calonWisudawan' => $calonWisudawan,
        ]);
    }


    /* ------------------------------------------------------------------
     |  Ubah Data Diri versi The Graduate
     | ------------------------------------------------------------------ */

    public function profile()
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);

        if (! PortalRouter::canUseGraduate($roles)) {
            return redirect()->to(base_url(PortalRouter::CHOOSER))
                ->with('error', 'The Graduate belum tersedia untuk peran Anda.');
        }

        $primary   = PortalRouter::primaryRole($roles);
        $userEmail = $userData['email'] ?? '';
        $akunModel = new \App\Models\AkunModel();
        $akunData  = $akunModel->where('email', $userEmail)->first();

        $dataDiri = [
            'id'                  => $userData['id'] ?? '',
            'id_akun'             => $akunData['id'] ?? ($userData['akun_id'] ?? null),
            'nama'                => $userData['nama'] ?? '',
            'email'               => $userEmail,
            'roleLabel'           => PortalRouter::roleLabel($roles),
            'prodi'               => $userData['prodi'] ?? '-',
            'extra_label'         => '',
            'extra_value'         => '',
            'extra_label2'        => '',
            'extra_value2'        => '',
            'tanggal_sk_yudisium' => null,
        ];

        if (in_array($primary, ['mahasiswa', 'wisudawan'], true)) {
            $mhsModel = new \App\Models\MahasiswaModel();
            $npm = ! empty($userData['npm']) ? $userData['npm'] : ($userData['id'] ?? null);
            $mhs = $npm ? $mhsModel->getMahasiswaInfo($npm) : null;
            if ($mhs) {
                $dataDiri['nama']        = $mhs['nama'];
                $dataDiri['email']       = $mhs['email'];
                $dataDiri['prodi']       = $mhs['nama_prodi'] ?? ($userData['prodi'] ?? '-');
                $dataDiri['extra_label'] = 'NPM';
                $dataDiri['extra_value'] = $mhs['npm'];
            }

            if ($npm) {
                $terplotModel = new MahasiswaTerplotModel();
                $terplot = $terplotModel->where('npm', $npm)->first();
                if ($terplot && ! empty($terplot['tanggal_sk_yudisium'])) {
                    $dataDiri['tanggal_sk_yudisium'] = $terplot['tanggal_sk_yudisium'];
                }
            }
        } elseif (in_array($primary, ['dosen', 'dospem eksternal'], true)) {
            $dosenModel = new \App\Models\DosenModel();
            $dosen = $dosenModel->where('email', $userEmail)->first();
            if ($dosen) {
                $dataDiri['nama']        = $dosen['nama'];
                $dataDiri['email']       = $dosen['email'];
                $dataDiri['extra_label'] = 'NIDN';
                $dataDiri['extra_value'] = ! empty($dosen['nidn']) ? $dosen['nidn'] : '-';
                if (! empty($dosen['inisial'])) {
                    $dataDiri['extra_label2'] = 'Inisial';
                    $dataDiri['extra_value2'] = $dosen['inisial'];
                }
            }
        } elseif (in_array($primary, ['tendik', 'staf'], true)) {
            $tendikModel = new \App\Models\TenagaKependidikanModel();
            $tendik = $tendikModel->where('email', $userEmail)->first();
            if ($tendik) {
                $dataDiri['nama']  = $tendik['nama'];
                $dataDiri['email'] = $tendik['email'];
            }
        } elseif (in_array($primary, ['kaprodi', 'prodi'], true)) {
            $prodiModel = new \App\Models\ProgramStudiModel();
            $prodi = $prodiModel->where('email', $userEmail)->first();
            if ($prodi) {
                $dataDiri['nama']  = $prodi['nama'];
                $dataDiri['email'] = $prodi['email'];
                $dataDiri['prodi'] = $prodi['nama_prodi'] ?? ($prodi['nama'] ?? '-');
            }
        } elseif ($primary === 'fakultas') {
            $fakultasModel = new \App\Models\FakultasModel();
            $fak = $fakultasModel->where('email', $userEmail)->first();
            if ($fak) {
                $dataDiri['nama']  = $fak['nama'];
                $dataDiri['email'] = $fak['email'];
            }
        }

        session()->set('active_side', 'graduate');

        return view('graduate/profile', [
            'title'    => 'Ubah Data Diri',
            'dataDiri' => $dataDiri,
            'roles'    => $roles,
            'user'     => $userData,
        ]);
    }

    public function saveProfile()
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/profile'));
        }

        $roles   = PortalRouter::normalizeRoles($userData);
        $primary = PortalRouter::primaryRole($roles);
        $nama    = trim((string) $this->request->getPost('nama', FILTER_SANITIZE_SPECIAL_CHARS));
        $email   = trim((string) $this->request->getPost('email', FILTER_SANITIZE_EMAIL));

        if ($nama === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Ubah Data Diri Gagal',
                'text'  => 'Nama dan email yang valid wajib diisi.',
            ]);
            return redirect()->to(base_url('graduate/profile'));
        }

        $akunModel = new \App\Models\AkunModel();
        $userEmail = $userData['email'] ?? '';
        $akunData  = $akunModel->where('email', $userEmail)->first();
        $akunId    = $akunData ? (int) $akunData['id'] : (int) ($userData['akun_id'] ?? 0);

        if ($akunData && $akunModel->isEmailAlreadyUsed($akunId, $email)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Ubah Data Diri Gagal',
                'text'  => 'Email telah digunakan oleh akun lain!',
            ]);
            return redirect()->to(base_url('graduate/profile'));
        }

        // 1. Update tabel akun
        if ($akunData) {
            $akunModel->update($akunId, ['email' => $email]);
        }

        // 2. Update tabel profil spesifik role
        if (in_array($primary, ['mahasiswa', 'wisudawan'], true)) {
            $mhsModel = new \App\Models\MahasiswaModel();
            $npm = ! empty($userData['npm']) ? $userData['npm'] : ($userData['id'] ?? null);
            $mhs = $npm ? $mhsModel->find($npm) : $mhsModel->where('email', $userEmail)->first();
            if ($mhs) {
                $mhsModel->update($mhs['npm'], ['nama' => $nama, 'email' => $email]);
            }
        } elseif (in_array($primary, ['dosen', 'dospem eksternal'], true)) {
            $dosenModel = new \App\Models\DosenModel();
            $dosen = $dosenModel->where('email', $userEmail)->first();
            if ($dosen) {
                $updateData = ['nama' => $nama, 'email' => $email];
                $inisial = trim((string) $this->request->getPost('inisial', FILTER_SANITIZE_SPECIAL_CHARS));
                if ($inisial !== '') {
                    if (! $dosenModel->isInitialAlreadyUsed($dosen['id'], $inisial)) {
                        $updateData['inisial'] = $inisial;
                    }
                }
                $dosenModel->update($dosen['id'], $updateData);
            }
        } elseif (in_array($primary, ['tendik', 'staf'], true)) {
            $tendikModel = new \App\Models\TenagaKependidikanModel();
            $tendik = $tendikModel->where('email', $userEmail)->first();
            if ($tendik) {
                $tendikModel->update($tendik['id'], ['nama' => $nama, 'email' => $email]);
            }
        } elseif (in_array($primary, ['kaprodi', 'prodi'], true)) {
            $prodiModel = new \App\Models\ProgramStudiModel();
            $prodi = $prodiModel->where('email', $userEmail)->first();
            if ($prodi) {
                $prodiModel->update($prodi['id'], ['nama' => $nama, 'email' => $email]);
            }
        } elseif ($primary === 'fakultas') {
            $fakultasModel = new \App\Models\FakultasModel();
            $fak = $fakultasModel->where('email', $userEmail)->first();
            if ($fak) {
                $fakultasModel->update($fak['id'], ['nama' => $nama, 'email' => $email]);
            }
        }

        // 3. Update session yang aktif
        $userData['nama']  = $nama;
        $userData['email'] = $email;
        session()->set('user_session', $userData);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Ubah Data Diri Berhasil',
            'text'  => 'Data diri berhasil diperbarui!',
        ]);

        return redirect()->to(base_url('graduate/profile'));
    }
}
