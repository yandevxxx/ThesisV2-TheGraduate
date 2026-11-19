<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\GraduateInputValidator;
use App\Libraries\PortalRouter;
use App\Models\MahasiswaTerplotModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Controller Modul SK Yudisium Sisi The Graduate
 *
 * Menangani plotting mahasiswa terplot SK Yudisium, impor berkas Excel,
 * penentuan status, dan pengaturan tanggal yudisium.
 */
class SkYudisiumController extends BaseController
{
    /**
     * Memastikan hanya staf/tendik/admin yang dapat mengelola SK Yudisium.
     */
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

    /**
     * Halaman utama daftar mahasiswa terplot SK Yudisium.
     */
    public function index()
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

        $semesterGradModel = new \App\Models\SemesterGraduateModel();
        $daftarSemester    = $semesterGradModel->orderBy('kode', 'DESC')->findAll();
        $semesterAktif     = $semesterGradModel->getActiveSemester();

        $semesterMap = [];
        foreach ($daftarSemester as $sem) {
            $semesterMap[(string) $sem['kode']] = \App\Models\SemesterGraduateModel::formatNama($sem['nama'] ?? null, $sem['kode'] ?? null);
        }

        $rawSemesterParam = $this->request->getGet('semester');
        if ($rawSemesterParam === null) {
            // Default ke semester yang sedang aktif; jika tidak ada semester aktif baru 'SEMUA'
            $selectedSemester = ! empty($semesterAktif['kode']) ? (string) $semesterAktif['kode'] : 'SEMUA';
        } else {
            $selectedSemester = trim((string) $rawSemesterParam);
            if ($selectedSemester === '') {
                $selectedSemester = ! empty($semesterAktif['kode']) ? (string) $semesterAktif['kode'] : 'SEMUA';
            }
        }

        $filters = [
            'q'        => trim((string) $this->request->getGet('q')),
            'status'   => strtolower(trim((string) $this->request->getGet('status'))),
            'semester' => $selectedSemester,
        ];

        if (! in_array($filters['status'], ['baru', 'transfer'], true)) {
            $filters['status'] = '';
        }

        $terplotModel = new MahasiswaTerplotModel();

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

        $db = \Config\Database::connect();
        $hasKodeSemester = false;
        try {
            if ($db->tableExists('mahasiswa_terplot_sk_yudisium')) {
                $hasKodeSemester = in_array('kode_semester', $db->getFieldNames('mahasiswa_terplot_sk_yudisium'), true);
            }
        } catch (\Throwable $e) {
            $hasKodeSemester = false;
        }

        if ($hasKodeSemester && $filters['semester'] !== '' && $filters['semester'] !== 'SEMUA') {
            $terplotModel->where('kode_semester', $filters['semester']);
        }

        $perPage       = 20;
        $daftarTerplot = $terplotModel->paginate($perPage, 'terplot');
        $pager         = $terplotModel->pager;
        $totalRows     = $pager->getTotal('terplot');
        $currentPage   = $pager->getCurrentPage('terplot');

        session()->set('active_side', 'graduate');

        return view("The-Graduate-Web/{$folder}/sk_yudisium", [
            'title'          => 'SK Yudisium',
            'user'           => $userData,
            'role'           => $roles,
            'daftarTerplot'  => $daftarTerplot,
            'daftarSemester' => $daftarSemester,
            'semesterAktif'  => $semesterAktif,
            'semesterMap'    => $semesterMap,
            'filters'        => $filters,
            'pager'          => $pager,
            'perPage'        => $perPage,
            'totalRows'      => $totalRows,
            'currentPage'    => $currentPage,
        ]);
    }

    public function sk_yudisium()
    {
        return $this->index();
    }

    /**
     * Download template Excel untuk import data terplot SK Yudisium.
     */
    public function downloadTemplate()
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
        $sheet->setCellValue('D1', 'tanggal_yudisium');

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        $sheet->setCellValue('A2', 'Contoh Mahasiswa');
        $sheet->setCellValueExplicit('B2', '2021010001', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('C2', 'baru');
        $sheet->setCellValue('D2', date('Y-m-d'));

        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);

        $writer   = new Xlsx($spreadsheet);
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

    public function skYudisiumDownloadTemplate()
    {
        return $this->downloadTemplate();
    }

    /**
     * Upload berkas Excel dan parse isi baris ke session preview.
     */
    public function upload()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->back();
        }

        $semesterGradModel = new \App\Models\SemesterGraduateModel();
        $semesterAktif     = $semesterGradModel->getActiveSemester();

        if ($semesterAktif === null) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Unggah Gagal',
                'text'  => 'Tidak dapat mengunggah berkas karena belum ada semester kelulusan yang aktif. Silakan aktifkan semester terlebih dahulu melalui menu Semester.',
            ]);
            return redirect()->to(base_url('graduate/sk_yudisium'));
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

        $ext    = strtolower($file->getExtension());
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
            $spreadsheet   = $reader->load($file->getTempName());
            $worksheet     = $spreadsheet->getActiveSheet();
            $highestRow    = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();

            if ($highestRow <= 1) {
                session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Berkas Kosong', 'text' => 'Berkas Excel tidak memiliki baris data untuk diproses']);
                return redirect()->to(base_url('graduate/sk_yudisium'));
            }
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
            $cleanKey = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $val), '_'));
            if (in_array($cleanKey, ['nama', 'nama_mahasiswa', 'nama_lengkap', 'nama_mhs'], true)) {
                $headerMap['nama'] = $col;
            } elseif (in_array($cleanKey, ['npm', 'nim', 'no_bp', 'nomor_pokok_mahasiswa', 'nomor_mahasiswa'], true)) {
                $headerMap['npm'] = $col;
            } elseif (in_array($cleanKey, ['status', 'status_mahasiswa', 'status_mhs'], true)) {
                $headerMap['status'] = $col;
            } elseif (in_array($cleanKey, ['tanggal_yudisium', 'tanggal_sk_yudisium', 'tgl_yudisium', 'tanggal', 'tgl_sk_yudisium', 'tanggal_sk', 'tgl_sk'], true)) {
                $headerMap['tanggal_yudisium'] = $col;
            }
        }

        if (! isset($headerMap['nama']) || ! isset($headerMap['npm']) || ! isset($headerMap['status'])) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Format Salah', 'text' => 'Kolom wajib tidak ditemukan. Pastikan terdapat kolom: nama, npm, status']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $valid    = [];
        $errors   = [];
        $seenFile = [];

        $terplotModel = new MahasiswaTerplotModel();
        $existing     = $terplotModel->select('npm')->findAll();
        $existingMap  = [];
        foreach ($existing as $e) {
            $existingMap[trim((string) $e['npm'])] = true;
        }

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $namaCell = $worksheet->getCell($headerMap['nama'] . $row);
            $nama     = trim(preg_replace('/[\s\x{00a0}\x{200b}]+/u', ' ', (string) $namaCell->getValue()));

            $npmCell = $worksheet->getCell($headerMap['npm'] . $row);
            $npmRaw  = $npmCell->getValue();
            if (is_numeric($npmRaw) && ! is_string($npmRaw)) {
                $npm = sprintf('%.0f', $npmRaw);
            } else {
                $npm = trim((string) $npmRaw);
            }
            $npm = preg_replace('/[\s\x{00a0}\x{200b}]+/u', '', $npm);

            $statusCell = $worksheet->getCell($headerMap['status'] . $row);
            $status     = trim(preg_replace('/[\s\x{00a0}\x{200b}]+/u', ' ', (string) $statusCell->getValue()));

            // Parse dan validasi format tanggal_yudisium jika kolom tersedia di Excel
            $tanggalSk = null;
            if (isset($headerMap['tanggal_yudisium'])) {
                $tglCell = $worksheet->getCell($headerMap['tanggal_yudisium'] . $row);
                $tglVal  = $tglCell->getValue();

                if ($tglVal !== null && trim((string) $tglVal) !== '') {
                    $rawDateStr = trim((string) $tglVal);
                    $parsedDate = null;

                    // 1. Cek jika cell diformat sebagai tipe Date bawaan Excel
                    if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($tglCell)) {
                        try {
                            $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tglVal);
                            $y  = (int) $dt->format('Y');
                            $m  = (int) $dt->format('m');
                            $d  = (int) $dt->format('d');
                            if (checkdate($m, $d, $y) && $y >= 2000 && $y <= 2100) {
                                $parsedDate = $dt->format('Y-m-d');
                            }
                        } catch (\Throwable $e) {
                            $parsedDate = null;
                        }
                    }

                    // 2. Cek format teks YYYY-MM-DD (format standar template)
                    if ($parsedDate === null) {
                        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rawDateStr, $matches)) {
                            $year  = (int) $matches[1];
                            $month = (int) $matches[2];
                            $day   = (int) $matches[3];
                            if (checkdate($month, $day, $year) && $year >= 2000 && $year <= 2100) {
                                $parsedDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            }
                        }
                    }

                    // 3. Dukung juga format alternatif DD/MM/YYYY atau DD-MM-YYYY
                    if ($parsedDate === null) {
                        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $rawDateStr, $matches)) {
                            $day   = (int) $matches[1];
                            $month = (int) $matches[2];
                            $year  = (int) $matches[3];
                            if (checkdate($month, $day, $year) && $year >= 2000 && $year <= 2100) {
                                $parsedDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            }
                        }
                    }

                    if ($parsedDate === null) {
                        $errors[] = [
                            'baris'  => $row,
                            'npm'    => $npm,
                            'nama'   => $nama,
                            'status' => $status,
                            'pesan'  => "Format tanggal yudisium '{$rawDateStr}' tidak valid (gunakan format YYYY-MM-DD, contoh: " . date('Y-m-d') . ")",
                        ];
                        continue;
                    }

                    $tanggalSk = $parsedDate;
                }
            }

            if ($nama === '' && $npm === '' && $status === '' && $tanggalSk === null) {
                continue;
            }

            if ($nama === '') {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "Kolom 'nama' wajib diisi"];
                continue;
            }
            $namaRowCheck = GraduateInputValidator::validateNama($nama, 150);
            if (! $namaRowCheck['valid']) {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => (string) $namaRowCheck['error']];
                continue;
            }
            $nama = $namaRowCheck['value'];
            if ($npm === '') {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "Kolom 'npm' wajib diisi"];
                continue;
            }
            if (! ctype_digit($npm)) {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "NPM harus berupa angka"];
                continue;
            }
            $npmLen = strlen($npm);
            if ($npmLen < 10) {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "NPM tidak valid: kurang dari 10 digit (panjang {$npmLen} digit, harus 10 digit)"];
                continue;
            }
            if ($npmLen > 10) {
                $errors[] = ['baris' => $row, 'npm' => $npm, 'nama' => $nama, 'status' => $status, 'pesan' => "NPM tidak valid: lebih dari 10 digit (panjang {$npmLen} digit, harus 10 digit)"];
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
                'npm'                 => $npm,
                'nama'                => $nama,
                'status'              => $statusNorm,
                'tanggal_sk_yudisium' => $tanggalSk,
            ];
        }

        if (empty($valid) && empty($errors)) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Data Kosong', 'text' => 'Tidak ditemukan baris data mahasiswa yang dapat diproses']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        session()->set('sk_yudisium_plot_preview', [
            'valid'          => $valid,
            'errors'         => $errors,
            'countValid'     => count($valid),
            'countError'     => count($errors),
            'kode_semester'  => (string) $semesterAktif['kode'],
            'semester_nama'  => \App\Models\SemesterGraduateModel::formatNama($semesterAktif['nama'] ?? null, $semesterAktif['kode'] ?? null),
            'uploaded_at'    => date('Y-m-d H:i:s'),
            'uploaded_by'    => $auth['akunId'],
        ]);

        return redirect()->to(base_url('graduate/skYudisiumPreview'));
    }

    public function skYudisiumUpload()
    {
        return $this->upload();
    }

    /**
     * Halaman pratinjau hasil unggah berkas Excel.
     */
    public function preview()
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

        $semesterGradModel = new \App\Models\SemesterGraduateModel();
        $semesterAktif     = $semesterGradModel->getActiveSemester();

        return view('The-Graduate-Web/admin_tendik/sk_yudisium_preview', [
            'title'         => 'Pratinjau Daftar Terplot SK Yudisium',
            'user'          => session()->get('user_session'),
            'role'          => $roles,
            'preview'       => $preview,
            'semesterAktif' => $semesterAktif,
        ]);
    }

    public function skYudisiumPreview()
    {
        return $this->preview();
    }

    /**
     * Simpan data hasil pratinjau ke database.
     */
    public function save()
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

        $valid        = $preview['valid'];
        $terplotModel = new MahasiswaTerplotModel();

        $npms        = array_column($valid, 'npm');
        $existing    = $terplotModel->select('npm')->whereIn('npm', $npms)->findAll();
        $existingMap = [];
        foreach ($existing as $e) {
            $existingMap[trim((string) $e['npm'])] = true;
        }

        $prodiMap = [];
        try {
            $mahasiswaModel = new \App\Models\MahasiswaModel();
            $mhsList = $mahasiswaModel->select('npm, id_prodi')->whereIn('npm', $npms)->findAll();
            foreach ($mhsList as $m) {
                if (! empty($m['id_prodi'])) {
                    $prodiMap[trim((string) $m['npm'])] = (int) $m['id_prodi'];
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika pencarian prodi gagal
        }

        $semesterGradModel = new \App\Models\SemesterGraduateModel();
        $semesterAktif     = $semesterGradModel->getActiveSemester();

        if ($semesterAktif === null) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Menyimpan',
                'text'  => 'Tidak dapat menyimpan data karena belum ada semester kelulusan yang aktif. Silakan aktifkan semester terlebih dahulu melalui menu Semester.',
            ]);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $kodeSemesterAktif = (string) $semesterAktif['kode'];

        $db = \Config\Database::connect();
        $hasKodeSemester = false;
        try {
            if ($db->tableExists('mahasiswa_terplot_sk_yudisium')) {
                $hasKodeSemester = in_array('kode_semester', $db->getFieldNames('mahasiswa_terplot_sk_yudisium'), true);
            }
        } catch (\Throwable $e) {
            $hasKodeSemester = false;
        }

        $toInsert = [];
        $now      = date('Y-m-d H:i:s');
        foreach ($valid as $row) {
            $npm = trim((string) $row['npm']);
            if (isset($existingMap[$npm])) {
                continue;
            }
            $item = [
                'npm'                 => $npm,
                'nama'                => $row['nama'],
                'status'              => $row['status'],
                'tanggal_sk_yudisium' => $row['tanggal_sk_yudisium'] ?? null,
                'uploaded_by'         => $auth['akunId'],
                'uploaded_at'         => $now,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
            if (! empty($prodiMap[$npm])) {
                $item['id_prodi'] = $prodiMap[$npm];
            }
            if ($hasKodeSemester) {
                $item['kode_semester'] = $kodeSemesterAktif;
            }
            $toInsert[] = $item;
        }

        if (empty($toInsert)) {
            session()->remove('sk_yudisium_plot_preview');
            session()->setFlashdata('message', ['icon' => 'info', 'title' => 'Tidak Ada yang Disimpan', 'text' => 'Semua data valid sudah terdaftar di daftar terplot']);
            return redirect()->to(base_url('graduate/sk_yudisium?semester=' . urlencode($kodeSemesterAktif)));
        }

        try {
            $terplotModel->insertBatch($toInsert);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menyimpan batch mahasiswa terplot SK Yudisium: ' . $e->getMessage());
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Menyimpan', 'text' => 'Terjadi kesalahan saat menyimpan data ke database. Silakan coba beberapa saat lagi']);
            return redirect()->to(base_url('graduate/skYudisiumPreview'));
        }

        session()->remove('sk_yudisium_plot_preview');
        session()->setFlashdata('message', ['icon' => 'success', 'title' => 'Berhasil Menyimpan', 'text' => count($toInsert) . ' data mahasiswa berhasil ditambahkan ke daftar terplot']);
        return redirect()->to(base_url('graduate/sk_yudisium?semester=' . urlencode($kodeSemesterAktif)));
    }

    public function skYudisiumSave()
    {
        return $this->save();
    }

    /**
     * Batalkan dan bersihkan pratinjau.
     */
    public function clearPreview()
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

    public function skYudisiumClearPreview()
    {
        return $this->clearPreview();
    }

    private function validationErrorResponse(string $title, string $message, string $redirectTarget = 'graduate/sk_yudisium', bool $withInput = false)
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
            'icon'  => 'error',
            'title' => $title,
            'text'  => $message,
        ]);

        $redirect = redirect()->to(base_url($redirectTarget));

        return $withInput ? $redirect->withInput() : $redirect;
    }

    /**
     * Tetapkan tanggal SK Yudisium untuk satu mahasiswa.
     */
    public function setTanggal()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->back();
        }

        $npmCheck = GraduateInputValidator::validateNpm((string) $this->request->getPost('npm'));
        if (! $npmCheck['valid']) {
            return $this->validationErrorResponse('NPM Tidak Valid', (string) $npmCheck['error']);
        }
        $npm = $npmCheck['value'];

        $dateCheck = GraduateInputValidator::validateDate(
            (string) $this->request->getPost('tanggal_sk_yudisium'),
            true,
            'Tanggal SK Yudisium'
        );
        if (! $dateCheck['valid']) {
            return $this->validationErrorResponse('Tanggal Tidak Valid', (string) $dateCheck['error']);
        }
        $tanggal = (string) $dateCheck['value'];

        $terplotModel = new MahasiswaTerplotModel();
        $mahasiswa    = $terplotModel->where('npm', $npm)->first();

        if (! $mahasiswa) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Mahasiswa tidak terdaftar dalam daftar terplot']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        try {
            $terplotModel->update($mahasiswa['id'], [
                'tanggal_sk_yudisium' => $tanggal,
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal set tanggal SK Yudisium id=' . $mahasiswa['id'] . ': ' . $e->getMessage());
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Terjadi kesalahan saat menyimpan tanggal SK Yudisium']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        session()->setFlashdata('message', ['icon' => 'success', 'title' => 'Berhasil', 'text' => 'Tanggal SK Yudisium berhasil disimpan']);
        return redirect()->to(base_url('graduate/sk_yudisium'));
    }

    /**
     * Helper redirect dengan mempertahankan query string filter aktif.
     */
    private function redirectSkYudisium(?string $redirectQuery = null, ?string $fallbackSemester = null)
    {
        $redirectQuery = trim((string) $redirectQuery);
        if ($redirectQuery !== '') {
            $redirectQuery = ltrim($redirectQuery, '?');
            return redirect()->to(base_url('graduate/sk_yudisium?' . $redirectQuery));
        }

        if ($fallbackSemester !== null && $fallbackSemester !== '') {
            return redirect()->to(base_url('graduate/sk_yudisium?semester=' . urlencode($fallbackSemester)));
        }

        return redirect()->to(base_url('graduate/sk_yudisium'));
    }

    public function skYudisiumSetTanggal()
    {
        return $this->setTanggal();
    }

    /**
     * Edit massal: ubah status dan/atau tanggal SK Yudisium untuk beberapa mahasiswa sekaligus.
     */
    public function bulkEdit()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $redirectQuery = trim((string) $this->request->getPost('redirect_query'));

        if ($this->request->getMethod() !== 'post') {
            return $this->redirectSkYudisium($redirectQuery);
        }

        $status  = strtolower(trim((string) $this->request->getPost('status')));
        $tanggal = trim((string) $this->request->getPost('tanggal_sk_yudisium'));
        $npms    = $this->request->getPost('npms');

        if (! is_array($npms) || empty($npms)) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ada Data', 'text' => 'Pilih minimal satu mahasiswa terlebih dahulu']);
            return $this->redirectSkYudisium($redirectQuery);
        }

        $cleanNpms = [];
        foreach ($npms as $rawNpm) {
            $npmCheck = GraduateInputValidator::validateNpm((string) $rawNpm);
            if (! $npmCheck['valid']) {
                session()->setFlashdata('message', ['icon' => 'error', 'title' => 'NPM Tidak Valid', 'text' => (string) $npmCheck['error']]);
                return $this->redirectSkYudisium($redirectQuery);
            }
            $cleanNpms[$npmCheck['value']] = $npmCheck['value'];
        }
        $cleanNpms = array_values($cleanNpms);

        if (empty($cleanNpms)) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ada Data', 'text' => 'Daftar NPM yang dipilih tidak valid']);
            return $this->redirectSkYudisium($redirectQuery);
        }

        $terplotModel      = new MahasiswaTerplotModel();
        $semesterGradModel = new \App\Models\SemesterGraduateModel();
        $semesterAktif     = $semesterGradModel->getActiveSemester();
        $activeKode        = $semesterAktif ? (string) $semesterAktif['kode'] : '';

        if ($activeKode !== '') {
            $targets = $terplotModel->whereIn('npm', $cleanNpms)->findAll();
            foreach ($targets as $targetRow) {
                $targetSem = (string) ($targetRow['kode_semester'] ?? '');
                if ($targetSem !== '' && $targetSem !== $activeKode) {
                    $namaSem = \App\Models\SemesterGraduateModel::formatNama(null, $targetSem);
                    session()->setFlashdata('message', [
                        'icon'  => 'error',
                        'title' => 'Perubahan Massal Ditolak',
                        'text'  => "Mahasiswa {$targetRow['nama']} ({$targetRow['npm']}) berada di periode lampau ({$namaSem}) dan tidak dapat diubah.",
                    ]);
                    return $this->redirectSkYudisium($redirectQuery);
                }
            }
        }

        $updateData = [];

        if ($status !== '') {
            if (! in_array($status, ['baru', 'transfer'], true)) {
                session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => "Status harus 'baru' atau 'transfer'"]);
                return $this->redirectSkYudisium($redirectQuery);
            }
            $updateData['status'] = $status;
        }

        if ($tanggal !== '') {
            $dateCheck = GraduateInputValidator::validateDate($tanggal, true, 'Tanggal SK Yudisium');
            if (! $dateCheck['valid']) {
                session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Tanggal Tidak Valid', 'text' => (string) $dateCheck['error']]);
                return $this->redirectSkYudisium($redirectQuery);
            }
            $updateData['tanggal_sk_yudisium'] = (string) $dateCheck['value'];
        }

        $semester = trim((string) $this->request->getPost('kode_semester'));
        if ($semester !== '') {
            if ($semesterAktif !== null && $semester !== $activeKode) {
                session()->setFlashdata('message', [
                    'icon'  => 'error',
                    'title' => 'Gagal',
                    'text'  => 'Mahasiswa tidak dapat dipindahkan secara massal ke periode lampau yang sudah tutup.',
                ]);
                return $this->redirectSkYudisium($redirectQuery);
            }
            $updateData['kode_semester'] = $semester;
        }

        if (empty($updateData)) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ada Perubahan', 'text' => 'Pilih semester, status, atau masukkan tanggal SK Yudisium yang ingin diperbarui secara massal']);
            return $this->redirectSkYudisium($redirectQuery);
        }

        $now = date('Y-m-d H:i:s');
        $updateData['updated_at'] = $now;

        try {
            $terplotModel->whereIn('npm', $cleanNpms)
                ->set($updateData)
                ->update();
        } catch (\Throwable $e) {
            log_message('error', 'Gagal bulk edit mahasiswa terplot: ' . $e->getMessage());
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Terjadi kesalahan saat memperbarui data secara massal']);
            return $this->redirectSkYudisium($redirectQuery);
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => count($cleanNpms) . ' mahasiswa berhasil diperbarui secara massal',
        ]);

        return $this->redirectSkYudisium($redirectQuery, $updateData['kode_semester'] ?? null);
    }

    public function bulkSetTanggal()
    {
        return $this->bulkEdit();
    }

    public function skYudisiumBulkSetTanggal()
    {
        return $this->bulkEdit();
    }

    public function skYudisiumBulkEdit()
    {
        return $this->bulkEdit();
    }

    /**
     * Hapus massal data mahasiswa terplot SK Yudisium yang dipilih.
     */
    public function bulkDelete()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $redirectQuery = trim((string) $this->request->getPost('redirect_query'));

        if ($this->request->getMethod() !== 'post') {
            return $this->redirectSkYudisium($redirectQuery);
        }

        $npms = $this->request->getPost('npms');
        if (! is_array($npms) || empty($npms)) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Tidak Ada Data',
                'text'  => 'Pilih minimal satu mahasiswa terlebih dahulu untuk dihapus',
            ]);
            return $this->redirectSkYudisium($redirectQuery);
        }

        $cleanNpms = array_values(array_unique(array_filter(array_map('trim', $npms))));
        if (empty($cleanNpms)) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Tidak Ada Data',
                'text'  => 'Daftar NPM yang dipilih tidak valid',
            ]);
            return $this->redirectSkYudisium($redirectQuery);
        }

        $terplotModel   = new MahasiswaTerplotModel();
        $targetStudents = $terplotModel->whereIn('npm', $cleanNpms)->findAll();

        if (empty($targetStudents)) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Tidak Ditemukan',
                'text'  => 'Data mahasiswa terplot yang dipilih tidak ditemukan atau sudah dihapus',
            ]);
            return $this->redirectSkYudisium($redirectQuery);
        }

        $semesterGradModel = new \App\Models\SemesterGraduateModel();
        $semesterAktif     = $semesterGradModel->getActiveSemester();
        $activeKode        = $semesterAktif ? (string) $semesterAktif['kode'] : '';

        $db            = \Config\Database::connect();
        $deletableIds  = [];
        $skippedPast   = 0;
        $skippedWisuda = 0;
        $skippedSkl    = 0;

        foreach ($targetStudents as $m) {
            $studentSemester = (string) ($m['kode_semester'] ?? '');

            // Proteksi 1: Mahasiswa di periode lampau yang sudah tutup
            if ($activeKode !== '' && $studentSemester !== '' && $studentSemester !== $activeKode) {
                $skippedPast++;
                continue;
            }

            // Proteksi 2: Sudah memiliki pendaftaran wisuda
            try {
                if ($db->tableExists('pendaftaran_wisuda')) {
                    $hasWisuda = $db->table('pendaftaran_wisuda')->where('npm', $m['npm'])->countAllResults() > 0;
                    if ($hasWisuda) {
                        $skippedWisuda++;
                        continue;
                    }
                }
            } catch (\Throwable $e) {}

            // Proteksi 3: Sudah memiliki pengajuan SKL
            try {
                if ($db->tableExists('pengajuan_skl')) {
                    $hasSkl = $db->table('pengajuan_skl')->where('npm', $m['npm'])->countAllResults() > 0;
                    if ($hasSkl) {
                        $skippedSkl++;
                        continue;
                    }
                }
            } catch (\Throwable $e) {}

            $deletableIds[] = $m['id'];
        }

        if (empty($deletableIds)) {
            $reasons = [];
            if ($skippedPast > 0) {
                $reasons[] = "{$skippedPast} mahasiswa berada di periode lampau yang terkunci";
            }
            if ($skippedWisuda > 0) {
                $reasons[] = "{$skippedWisuda} mahasiswa sudah memiliki berkas pendaftaran wisuda";
            }
            if ($skippedSkl > 0) {
                $reasons[] = "{$skippedSkl} mahasiswa sudah memiliki pengajuan SKL";
            }
            $reasonText = implode(', ', $reasons);

            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Penghapusan Massal Ditolak',
                'text'  => 'Tidak ada mahasiswa yang dapat dihapus karena: ' . $reasonText . '.',
            ]);
            return $this->redirectSkYudisium($redirectQuery);
        }

        try {
            $terplotModel->whereIn('id', $deletableIds)->delete();
        } catch (\Throwable $e) {
            log_message('error', 'Gagal bulk delete mahasiswa terplot: ' . $e->getMessage());
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal',
                'text'  => 'Terjadi kesalahan saat menghapus data mahasiswa secara massal',
            ]);
            return $this->redirectSkYudisium($redirectQuery);
        }

        $deletedCount = count($deletableIds);
        $skippedCount = count($targetStudents) - $deletedCount;
        $note = '';
        if ($skippedCount > 0) {
            $note = " ({$skippedCount} mahasiswa dilewati karena terkunci di periode lampau atau memiliki berkas wisuda/SKL)";
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => "{$deletedCount} mahasiswa berhasil dihapus secara massal{$note}.",
        ]);

        return $this->redirectSkYudisium($redirectQuery);
    }

    public function skYudisiumBulkDelete()
    {
        return $this->bulkDelete();
    }

    /**
     * Ubah status (baru / transfer), nama, dan tanggal SK yudisium mahasiswa.
     */
    public function updateStatus()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $redirectQuery = trim((string) $this->request->getPost('redirect_query'));

        if ($this->request->getMethod() !== 'post') {
            return $this->redirectSkYudisium($redirectQuery);
        }

        $id        = (int) $this->request->getPost('id');
        $namaCheck = GraduateInputValidator::validateNama((string) $this->request->getPost('nama'), 150);
        $status    = strtolower(trim((string) $this->request->getPost('status')));
        $tanggal   = trim((string) $this->request->getPost('tanggal_sk_yudisium'));

        if ($id <= 0) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Data mahasiswa tidak valid']);
            return $this->redirectSkYudisium($redirectQuery);
        }

        if (! $namaCheck['valid']) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => (string) $namaCheck['error']]);
            return $this->redirectSkYudisium($redirectQuery);
        }
        $nama = $namaCheck['value'];

        if (! in_array($status, ['baru', 'transfer'], true)) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => "Status harus 'baru' atau 'transfer'"]);
            return $this->redirectSkYudisium($redirectQuery);
        }

        $tanggalVal = null;
        if ($tanggal !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $tanggal);
            if (! $d || $d->format('Y-m-d') !== $tanggal) {
                session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Tanggal Tidak Valid', 'text' => 'Format tanggal SK Yudisium tidak valid. Gunakan format YYYY-MM-DD']);
                return $this->redirectSkYudisium($redirectQuery);
            }
            $tanggalVal = $tanggal;
        }

        $terplotModel = new MahasiswaTerplotModel();
        $mahasiswa    = $terplotModel->find($id);

        if ($mahasiswa === null) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data mahasiswa terplot tidak ditemukan']);
            return $this->redirectSkYudisium($redirectQuery);
        }

        $currSemester = (string) ($mahasiswa['kode_semester'] ?? '');
        $semesterGradModel = new \App\Models\SemesterGraduateModel();
        $semesterAktif     = $semesterGradModel->getActiveSemester();
        $activeKode        = $semesterAktif ? (string) $semesterAktif['kode'] : '';

        $inputSemester = trim((string) $this->request->getPost('kode_semester'));
        // Tidak mengizinkan mengosongkan semester menjadi NULL
        $finalSemester = ($inputSemester !== '') ? $inputSemester : $currSemester;

        // Validasi perlindungan semester:
        if ($activeKode !== '') {
            $isOrigActive = ($currSemester === $activeKode);

            // Kasus 1: Mahasiswa berasal dari periode lampau yang sudah tutup
            if (! $isOrigActive && $currSemester !== '' && $finalSemester !== $currSemester) {
                $namaSem = \App\Models\SemesterGraduateModel::formatNama(null, $currSemester);
                session()->setFlashdata('message', [
                    'icon'  => 'error',
                    'title' => 'Perubahan Semester Ditolak',
                    'text'  => "Mahasiswa pada periode lampau ({$namaSem}) tidak dapat diubah semesternya demi menjaga integritas arsip kelulusan.",
                ]);
                return $this->redirectSkYudisium($redirectQuery, $currSemester);
            }

            // Kasus 2: Mahasiswa aktif dipindahkan ke periode lampau yang sudah tutup
            if ($isOrigActive && $finalSemester !== $activeKode) {
                session()->setFlashdata('message', [
                    'icon'  => 'error',
                    'title' => 'Perubahan Semester Ditolak',
                    'text'  => 'Mahasiswa pada semester aktif tidak dapat dipindahkan ke periode lampau yang sudah tutup.',
                ]);
                return $this->redirectSkYudisium($redirectQuery, $currSemester);
            }
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'nama'                => $nama,
            'status'              => $status,
            'tanggal_sk_yudisium' => $tanggalVal,
            'updated_at'          => $now,
        ];

        $db = \Config\Database::connect();
        $hasKodeSemester = false;
        try {
            if ($db->tableExists('mahasiswa_terplot_sk_yudisium')) {
                $hasKodeSemester = in_array('kode_semester', $db->getFieldNames('mahasiswa_terplot_sk_yudisium'), true);
            }
        } catch (\Throwable $e) {
            $hasKodeSemester = false;
        }

        if ($hasKodeSemester && $finalSemester !== '') {
            $updateData['kode_semester'] = $finalSemester;
        }

        try {
            $terplotModel->update($id, $updateData);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal update status mahasiswa terplot id=' . $id . ': ' . $e->getMessage());
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Terjadi kesalahan saat memperbarui data mahasiswa']);
            return $this->redirectSkYudisium($redirectQuery, $finalSemester);
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => 'Data mahasiswa ' . $nama . ' (' . $mahasiswa['npm'] . ') berhasil diperbarui',
        ]);

        return $this->redirectSkYudisium($redirectQuery, $finalSemester);
    }

    public function skYudisiumUpdateStatus()
    {
        return $this->updateStatus();
    }

    /**
     * Hapus data mahasiswa terplot SK Yudisium.
     */
    public function delete()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $redirectQuery = trim((string) $this->request->getPost('redirect_query'));

        if ($this->request->getMethod() !== 'post') {
            return $this->redirectSkYudisium($redirectQuery);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Data yang dipilih tidak valid']);
            return $this->redirectSkYudisium($redirectQuery);
        }

        $terplotModel = new MahasiswaTerplotModel();
        $mahasiswa    = $terplotModel->find($id);

        if ($mahasiswa === null) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data mahasiswa terplot tidak ditemukan atau sudah dihapus']);
            return $this->redirectSkYudisium($redirectQuery);
        }

        $studentSemester   = (string) ($mahasiswa['kode_semester'] ?? '');
        $semesterGradModel = new \App\Models\SemesterGraduateModel();
        $semesterAktif     = $semesterGradModel->getActiveSemester();
        $activeKode        = $semesterAktif ? (string) $semesterAktif['kode'] : '';

        // Proteksi 1: Mahasiswa pada periode lampau (yang sudah tutup) tidak dapat dihapus
        if ($activeKode !== '' && $studentSemester !== '' && $studentSemester !== $activeKode) {
            $namaSemester = \App\Models\SemesterGraduateModel::formatNama(null, $studentSemester);
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Penghapusan Ditolak',
                'text'  => "Mahasiswa pada periode lampau ({$namaSemester}) tidak dapat dihapus demi menjaga integritas arsip kelulusan.",
            ]);
            return $this->redirectSkYudisium($redirectQuery, $studentSemester);
        }

        // Proteksi 2: Pengecekan relasi pendaftaran wisuda
        $db = \Config\Database::connect();
        try {
            if ($db->tableExists('pendaftaran_wisuda')) {
                $countWisuda = $db->table('pendaftaran_wisuda')->where('npm', $mahasiswa['npm'])->countAllResults();
                if ($countWisuda > 0) {
                    session()->setFlashdata('message', [
                        'icon'  => 'error',
                        'title' => 'Tidak Dapat Dihapus',
                        'text'  => "Mahasiswa {$mahasiswa['nama']} ({$mahasiswa['npm']}) tidak dapat dihapus karena sudah memiliki berkas pendaftaran wisuda.",
                    ]);
                    return $this->redirectSkYudisium($redirectQuery, $studentSemester);
                }
            }
        } catch (\Throwable $e) {}

        // Proteksi 3: Pengecekan relasi pengajuan SKL
        try {
            if ($db->tableExists('pengajuan_skl')) {
                $countSkl = $db->table('pengajuan_skl')->where('npm', $mahasiswa['npm'])->countAllResults();
                if ($countSkl > 0) {
                    session()->setFlashdata('message', [
                        'icon'  => 'error',
                        'title' => 'Tidak Dapat Dihapus',
                        'text'  => "Mahasiswa {$mahasiswa['nama']} ({$mahasiswa['npm']}) tidak dapat dihapus karena sudah memiliki berkas pengajuan SKL.",
                    ]);
                    return $this->redirectSkYudisium($redirectQuery, $studentSemester);
                }
            }
        } catch (\Throwable $e) {}

        try {
            if (! $terplotModel->delete($id)) {
                throw new \RuntimeException('Model delete returned false.');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menghapus mahasiswa terplot SK Yudisium id=' . $id . ': ' . $e->getMessage());

            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Data mahasiswa terplot gagal dihapus']);
            return $this->redirectSkYudisium($redirectQuery, $studentSemester);
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => 'Data mahasiswa terplot ' . $mahasiswa['nama'] . ' (' . $mahasiswa['npm'] . ') berhasil dihapus',
        ]);

        return $this->redirectSkYudisium($redirectQuery, $studentSemester);
    }

    public function skYudisiumDelete()
    {
        return $this->delete();
    }

    /**
     * Tambah data mahasiswa terplot secara manual.
     */
    public function store()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $nama    = trim(preg_replace('/[\s\x{00a0}\x{200b}]+/u', ' ', (string) $this->request->getPost('nama')));
        $npm     = preg_replace('/[\s\x{00a0}\x{200b}]+/u', '', (string) $this->request->getPost('npm'));
        $status  = strtolower(trim((string) $this->request->getPost('status')));
        $tanggal = trim((string) $this->request->getPost('tanggal_sk_yudisium'));

        $errors = [];

        // Validasi ketersediaan Semester Aktif (mahasiswa baru tidak boleh didaftarkan ke periode lampau yang sudah tutup)
        $semesterGradModel = new \App\Models\SemesterGraduateModel();
        $semesterAktif     = $semesterGradModel->getActiveSemester();

        if ($semesterAktif === null) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Menambah',
                'text'  => 'Tidak dapat menambahkan mahasiswa karena belum ada semester kelulusan yang aktif. Silakan aktifkan semester terlebih dahulu melalui menu Semester.',
            ]);
            return redirect()->to(base_url('graduate/sk_yudisium'))->withInput();
        }

        $inputSemester = trim((string) $this->request->getPost('kode_semester'));
        if ($inputSemester !== '' && $inputSemester !== (string) $semesterAktif['kode']) {
            $namaSemAktif = \App\Models\SemesterGraduateModel::formatNama($semesterAktif['nama'] ?? null, $semesterAktif['kode'] ?? null);
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Menambah',
                'text'  => "Mahasiswa baru hanya dapat ditambahkan pada semester yang sedang aktif ({$namaSemAktif}). Periode lampau tidak dapat menerima data baru.",
            ]);
            return redirect()->to(base_url('graduate/sk_yudisium'))->withInput();
        }
        $finalSemester = (string) $semesterAktif['kode'];

        $tanggalVal = null;
        if ($tanggal !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $tanggal);
            if (! $d || $d->format('Y-m-d') !== $tanggal) {
                $errors[] = 'Format tanggal SK Yudisium tidak valid. Gunakan format YYYY-MM-DD';
            } else {
                $tanggalVal = $tanggal;
            }
        }

        $namaCheck = GraduateInputValidator::validateNama((string) $this->request->getPost('nama'), 150);
        if (! $namaCheck['valid']) {
            $errors[] = (string) $namaCheck['error'];
        }
        $nama = $namaCheck['value'];

        $npmCheck = GraduateInputValidator::validateNpm((string) $this->request->getPost('npm'));
        if (! $npmCheck['valid']) {
            $errors[] = (string) $npmCheck['error'];
        }
        $npm = $npmCheck['value'];

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
            return $this->validationErrorResponse('Gagal Menambah', implode(' ', $errors), 'graduate/sk_yudisium', true);
        }

        $now = date('Y-m-d H:i:s');

        $db = \Config\Database::connect();
        $hasKodeSemester = false;
        try {
            if ($db->tableExists('mahasiswa_terplot_sk_yudisium')) {
                $hasKodeSemester = in_array('kode_semester', $db->getFieldNames('mahasiswa_terplot_sk_yudisium'), true);
            }
        } catch (\Throwable $e) {
            $hasKodeSemester = false;
        }

        $insertData = [
            'npm'                 => $npm,
            'nama'                => $nama,
            'status'              => $status,
            'tanggal_sk_yudisium' => $tanggalVal,
            'uploaded_by'         => $auth['akunId'],
            'uploaded_at'         => $now,
        ];
        if ($hasKodeSemester) {
            $insertData['kode_semester'] = $finalSemester;
        }

        try {
            $mhs = (new \App\Models\MahasiswaModel())->select('id_prodi')->where('npm', $npm)->first();
            if ($mhs && ! empty($mhs['id_prodi'])) {
                $insertData['id_prodi'] = (int) $mhs['id_prodi'];
            }
        } catch (\Throwable $e) {
            // Abaikan pencarian id_prodi jika gagal
        }

        try {
            $insertId = $terplotModel->insert($insertData);

            if ($insertId === false) {
                $modelErrors = $terplotModel->errors();
                $errText = ! empty($modelErrors) ? implode(', ', $modelErrors) : 'Data mahasiswa terplot gagal disimpan';
                throw new \RuntimeException($errText);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menambah mahasiswa terplot SK Yudisium npm=' . $npm . ': ' . $e->getMessage());

            $errMsg = (stripos($e->getMessage(), 'Duplicate') !== false)
                ? 'NPM ' . $npm . ' sudah terdaftar di daftar terplot.'
                : $e->getMessage();

            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => $errMsg]);
            return redirect()->to(base_url('graduate/sk_yudisium'))->withInput();
        }

        $namaSemAktif = \App\Models\SemesterGraduateModel::formatNama($semesterAktif['nama'] ?? null, $semesterAktif['kode'] ?? null);
        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => 'Data mahasiswa terplot ' . $nama . ' (' . $npm . ') berhasil ditambahkan pada ' . $namaSemAktif,
        ]);

        return redirect()->to(base_url('graduate/sk_yudisium?semester=' . urlencode($finalSemester)));
    }

    public function skYudisiumStore()
    {
        return $this->store();
    }
}

