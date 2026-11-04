<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
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

        return view("The-Graduate-Web/{$folder}/sk_yudisium", [
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
            if (in_array($cleanKey, ['nama', 'npm', 'status'], true)) {
                $headerMap[$cleanKey] = $col;
            } elseif (in_array($cleanKey, ['tanggal_yudisium', 'tanggal_sk_yudisium', 'tgl_yudisium', 'tanggal', 'tgl_sk_yudisium'], true)) {
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
            $nama     = trim((string) $namaCell->getValue());

            $npmCell = $worksheet->getCell($headerMap['npm'] . $row);
            $npmRaw  = $npmCell->getValue();
            if (is_numeric($npmRaw) && ! is_string($npmRaw)) {
                $npm = sprintf('%.0f', $npmRaw);
            } else {
                $npm = trim((string) $npmRaw);
            }

            $statusCell = $worksheet->getCell($headerMap['status'] . $row);
            $status     = trim((string) $statusCell->getValue());

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

        session()->set('sk_yudisium_plot_preview', [
            'valid'       => $valid,
            'errors'      => $errors,
            'countValid'  => count($valid),
            'countError'  => count($errors),
            'uploaded_at' => date('Y-m-d H:i:s'),
            'uploaded_by' => $auth['akunId'],
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

        return view('The-Graduate-Web/admin_tendik/sk_yudisium_preview', [
            'title'   => 'Pratinjau Daftar Terplot SK Yudisium',
            'user'    => session()->get('user_session'),
            'role'    => $roles,
            'preview' => $preview,
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

        $toInsert = [];
        $now      = date('Y-m-d H:i:s');
        foreach ($valid as $row) {
            $npm = trim((string) $row['npm']);
            if (isset($existingMap[$npm])) {
                continue;
            }
            $toInsert[] = [
                'npm'                 => $npm,
                'nama'                => $row['nama'],
                'status'              => $row['status'],
                'tanggal_sk_yudisium' => $row['tanggal_sk_yudisium'] ?? null,
                'uploaded_by'         => $auth['akunId'],
                'uploaded_at'         => $now,
                'created_at'          => $now,
                'updated_at'          => $now,
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
        session()->setFlashdata('message', ['icon' => 'success', 'title' => 'Berhasil Menyimpan', 'text' => count($toInsert) . ' data mahasiswa berhasil ditambahkan ke daftar terplot']);
        return redirect()->to(base_url('graduate/sk_yudisium'));
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

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $status  = strtolower(trim((string) $this->request->getPost('status')));
        $tanggal = trim((string) $this->request->getPost('tanggal_sk_yudisium'));
        $npms    = $this->request->getPost('npms');

        if (! is_array($npms) || empty($npms)) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ada Data', 'text' => 'Pilih minimal satu mahasiswa terlebih dahulu']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $cleanNpms = array_filter(array_map('trim', $npms));
        if (empty($cleanNpms)) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ada Data', 'text' => 'Daftar NPM yang dipilih tidak valid']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $updateData = [];

        if ($status !== '') {
            if (! in_array($status, ['baru', 'transfer'], true)) {
                session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => "Status harus 'baru' atau 'transfer'"]);
                return redirect()->to(base_url('graduate/sk_yudisium'));
            }
            $updateData['status'] = $status;
        }

        if ($tanggal !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $tanggal);
            if (! $d || $d->format('Y-m-d') !== $tanggal) {
                session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Tanggal Tidak Valid', 'text' => 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD']);
                return redirect()->to(base_url('graduate/sk_yudisium'));
            }
            $updateData['tanggal_sk_yudisium'] = $tanggal;
        }

        if (empty($updateData)) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ada Perubahan', 'text' => 'Pilih status atau masukkan tanggal SK Yudisium yang ingin diperbarui secara massal']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $now = date('Y-m-d H:i:s');
        $updateData['updated_at'] = $now;

        $terplotModel = new MahasiswaTerplotModel();
        $terplotModel->whereIn('npm', $cleanNpms)
            ->set($updateData)
            ->update();

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => count($cleanNpms) . ' mahasiswa berhasil diperbarui secara massal',
        ]);

        return redirect()->to(base_url('graduate/sk_yudisium'));
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
     * Ubah status (baru / transfer) dan tanggal SK yudisium mahasiswa.
     */
    public function updateStatus()
    {
        $auth = $this->ensureStafForPlot();
        if ($auth === null) {
            $roles = session()->get('user_session') ? PortalRouter::normalizeRoles(session()->get('user_session')) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $id      = (int) $this->request->getPost('id');
        $status  = strtolower(trim((string) $this->request->getPost('status')));
        $tanggal = trim((string) $this->request->getPost('tanggal_sk_yudisium'));

        if ($id <= 0) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Data mahasiswa tidak valid']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        if (! in_array($status, ['baru', 'transfer'], true)) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => "Status harus 'baru' atau 'transfer'"]);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $tanggalVal = null;
        if ($tanggal !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $tanggal);
            if (! $d || $d->format('Y-m-d') !== $tanggal) {
                session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Tanggal Tidak Valid', 'text' => 'Format tanggal SK Yudisium tidak valid. Gunakan format YYYY-MM-DD']);
                return redirect()->to(base_url('graduate/sk_yudisium'));
            }
            $tanggalVal = $tanggal;
        }

        $terplotModel = new MahasiswaTerplotModel();
        $mahasiswa    = $terplotModel->find($id);

        if ($mahasiswa === null) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data mahasiswa terplot tidak ditemukan']);
            return redirect()->to(base_url('graduate/sk_yudisium'));
        }

        $now = date('Y-m-d H:i:s');
        $terplotModel->update($id, [
            'status'              => $status,
            'tanggal_sk_yudisium' => $tanggalVal,
            'updated_at'          => $now,
        ]);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => 'Data mahasiswa ' . $mahasiswa['nama'] . ' (' . $mahasiswa['npm'] . ') berhasil diperbarui',
        ]);

        return redirect()->to(base_url('graduate/sk_yudisium'));
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
        } elseif (strlen($npm) < 10) {
            $errors[] = 'NPM tidak valid: kurang dari 10 digit (harus 10 digit)';
        } elseif (strlen($npm) > 10) {
            $errors[] = 'NPM tidak valid: lebih dari 10 digit (harus 10 digit)';
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

    public function skYudisiumStore()
    {
        return $this->store();
    }
}

