<?php

namespace App\Controllers;

use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Models\MahasiswaTerplotModel;
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
            return redirect()->to(base_url(PortalRouter::thesisLanding($roles)))
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
        return $this->halamanMenu('semester', 'Semester');
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
        return $this->halamanMenu('skl', 'SKL');
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
        $daftarTerplot = $terplotModel->orderBy('nama', 'ASC')->findAll();

        session()->set('active_side', 'graduate');

        return view("graduate/{$folder}/sk_yudisium", [
            'title'         => 'SK Yudisium',
            'user'          => $userData,
            'role'          => $roles,
            'daftarTerplot' => $daftarTerplot,
        ]);
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
        return $this->halamanMenu('pengambilan_ijazah', 'Pengambilan Ijazah');
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
                return redirect()->to(base_url(PortalRouter::thesisLanding($roles)))
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
    private function halamanMenu($halaman, $judul)
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

        // Halaman belum tersedia untuk role ini -> kembali ke dashboard role.
        if (! is_file(APPPATH . "Views/graduate/{$folder}/{$halaman}.php")) {
            return redirect()->to(base_url(PortalRouter::graduateLanding($roles)));
        }

        session()->set('active_side', 'graduate');

        return view("graduate/{$folder}/{$halaman}", [
            'title' => $judul,
            'user'  => $userData,
            'role'  => $roles,
        ]);
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
}
