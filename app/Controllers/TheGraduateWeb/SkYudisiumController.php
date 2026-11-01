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

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        $sheet->setCellValue('A2', 'Contoh Mahasiswa');
        $sheet->setCellValue('B2', '2021010001');
        $sheet->setCellValue('C2', 'baru');

        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(15);

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
            $key = strtolower($val);
            if (in_array($key, ['nama', 'npm', 'status'], true)) {
                $headerMap[$key] = $col;
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
     * Tetapkan tanggal SK Yudisium secara massal untuk beberapa mahasiswa.
     */
    public function bulkSetTanggal()
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
        $now          = date('Y-m-d H:i:s');
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

    public function skYudisiumBulkSetTanggal()
    {
        return $this->bulkSetTanggal();
    }

    /**
     * Ubah status mahasiswa (baru / transfer).
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

    public function skYudisiumStore()
    {
        return $this->store();
    }
}

