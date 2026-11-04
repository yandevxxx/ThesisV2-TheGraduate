<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Models\MahasiswaTerplotModel;
use App\Models\SemesterModel;

/**
 * Controller Sisi "The Graduate" (Gateway & Router Utama)
 *
 * Menangani:
 * - Halaman pilihan (gateway) Thesis / Graduate setelah login: graduate/choose
 * - Pindah sisi: graduate/enter/{thesis|graduate}
 * - Dashboard per role: graduate/{admin_tendik|fakultas|prodi|wisudawan|dosen|mahasiswa}
 * - Halaman menu operasional & delegasi ke controller modular (Semester, SK Yudisium, Notifikasi, Profile)
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
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        session()->set('active_side', 'graduate');

        return redirect()->to(base_url(PortalRouter::graduateLanding($roles)));
    }

    /* ------------------------------------------------------------------
     |  Dashboard per Role
     | ------------------------------------------------------------------ */

    public function admin_tendik()
    {
        $semesterGradModel = new \App\Models\SemesterGraduateModel();
        $daftarSemester    = $semesterGradModel->orderBy('kode', 'DESC')->findAll();
        $semesterAktif     = $semesterGradModel->getActiveSemester();

        $selectedSemester = trim((string) $this->request->getGet('semester'));
        if ($selectedSemester === '') {
            $selectedSemester = $semesterAktif['kode'] ?? ($daftarSemester[0]['kode'] ?? 'Ganjil 2025/2026');
        }

        $db = \Config\Database::connect();
        $hasKodeSemester = false;
        try {
            if ($db->tableExists('mahasiswa_terplot_sk_yudisium')) {
                $fields = $db->getFieldNames('mahasiswa_terplot_sk_yudisium');
                $hasKodeSemester = in_array('kode_semester', $fields, true);
            }
        } catch (\Throwable $e) {
            $hasKodeSemester = false;
        }

        $terplotModel = new MahasiswaTerplotModel();
        $selesaiQuery = (new MahasiswaTerplotModel())->where('tanggal_sk_yudisium IS NOT NULL');
        $pendingQuery = (new MahasiswaTerplotModel())->where('tanggal_sk_yudisium IS NULL');
        $listQuery    = (new MahasiswaTerplotModel())->orderBy('nama', 'ASC');

        if ($hasKodeSemester && $selectedSemester !== 'SEMUA') {
            $terplotModel->where('kode_semester', $selectedSemester);
            $selesaiQuery->where('kode_semester', $selectedSemester);
            $pendingQuery->where('kode_semester', $selectedSemester);
            $listQuery->where('kode_semester', $selectedSemester);
        }

        $jumlahYudisium        = $terplotModel->countAllResults();
        $jumlahYudisiumSelesai = $selesaiQuery->countAllResults();
        $jumlahYudisiumPending = $pendingQuery->countAllResults();
        $semuaTerplot          = $listQuery->findAll();

        // Ambil mahasiswa terplot yang belum mendaftar wisuda (pada semester terpilih)
        $registeredNpms       = $this->getRegisteredWisudaNpms();
        $mahasiswaBelumDaftar = [];
        foreach ($semuaTerplot as $mhs) {
            $npmStr = (string) ($mhs['npm'] ?? '');
            if (! in_array($npmStr, $registeredNpms, true)) {
                $mahasiswaBelumDaftar[] = $mhs;
            }
        }
        $jumlahBelumDaftar = count($mahasiswaBelumDaftar);

        return $this->dashboard('admin_tendik', 'The Graduate', [
            'jumlahYudisium'        => $jumlahYudisium,
            'jumlahYudisiumSelesai' => $jumlahYudisiumSelesai,
            'jumlahYudisiumPending' => $jumlahYudisiumPending,
            'daftarSemester'        => $daftarSemester,
            'semesterAktif'         => $semesterAktif,
            'selectedSemester'      => $selectedSemester,
            'jumlahBelumDaftar'     => $jumlahBelumDaftar,
            'mahasiswaBelumDaftar'  => $mahasiswaBelumDaftar,
        ]);
    }

    public function fakultas()
    {
        return $this->dashboard('fakultas', 'The Graduate');
    }

    public function prodi()
    {
        return $this->dashboard('prodi', 'The Graduate');
    }

    public function wisudawan()
    {
        return $this->dashboard('wisudawan', 'The Graduate');
    }

    public function dosen()
    {
        return $this->dashboard('dosen', 'The Graduate');
    }

    public function mahasiswa()
    {
        return $this->dashboard('mahasiswa', 'The Graduate');
    }

    /* ------------------------------------------------------------------
     |  Menu Halaman Operasional
     | ------------------------------------------------------------------ */

    public function berkas()
    {
        return $this->halamanMenu('berkas', 'Berkas');
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
                    return view('The-Graduate-Web/mahasiswa/belum_terplot', [
                        'title' => 'Akses Ditolak',
                        'user'  => $userData,
                        'role'  => $roles,
                    ]);
                }
            } else {
                session()->set('active_side', 'graduate');
                return view('The-Graduate-Web/mahasiswa/belum_terplot', [
                    'title' => 'Akses Ditolak',
                    'user'  => $userData,
                    'role'  => $roles,
                ]);
            }
        }

        return $this->halamanMenu('pendaftaran_wisuda', 'Pendaftaran Wisuda');
    }

    public function belum_mendaftar()
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
        if ($folder !== 'admin_tendik' && $folder !== 'fakultas') {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $semesterGradModel = new \App\Models\SemesterGraduateModel();
        $daftarSemester    = $semesterGradModel->orderBy('kode', 'DESC')->findAll();

        $registeredNpms       = $this->getRegisteredWisudaNpms();
        $semuaTerplot         = (new MahasiswaTerplotModel())->orderBy('nama', 'ASC')->findAll();
        $mahasiswaBelumDaftar = [];

        foreach ($semuaTerplot as $mhs) {
            $npmStr = (string) ($mhs['npm'] ?? '');
            if (! in_array($npmStr, $registeredNpms, true)) {
                $mahasiswaBelumDaftar[] = $mhs;
            }
        }

        $filters = [
            'q'        => trim((string) $this->request->getGet('q')),
            'status'   => strtolower(trim((string) $this->request->getGet('status'))),
            'sk'       => strtolower(trim((string) $this->request->getGet('sk'))),
            'semester' => trim((string) $this->request->getGet('semester')),
        ];

        if (! in_array($filters['status'], ['baru', 'transfer'], true)) {
            $filters['status'] = '';
        }
        if (! in_array($filters['sk'], ['ada', 'belum'], true)) {
            $filters['sk'] = '';
        }

        $daftarBelumDaftar = $mahasiswaBelumDaftar;
        if ($filters['q'] !== '' || $filters['status'] !== '' || $filters['sk'] !== '' || ($filters['semester'] !== '' && $filters['semester'] !== 'SEMUA')) {
            $daftarBelumDaftar = array_values(array_filter($daftarBelumDaftar, function ($m) use ($filters) {
                if ($filters['q'] !== '') {
                    $q = $filters['q'];
                    if (stripos($m['npm'], $q) === false && stripos($m['nama'], $q) === false) {
                        return false;
                    }
                }
                if ($filters['status'] !== '' && ($m['status'] ?? '') !== $filters['status']) {
                    return false;
                }
                if ($filters['sk'] === 'ada' && empty($m['tanggal_sk_yudisium'])) {
                    return false;
                }
                if ($filters['sk'] === 'belum' && ! empty($m['tanggal_sk_yudisium'])) {
                    return false;
                }
                if ($filters['semester'] !== '' && $filters['semester'] !== 'SEMUA') {
                    if (($m['kode_semester'] ?? '') !== $filters['semester']) {
                        return false;
                    }
                }
                return true;
            }));
        }

        session()->set('active_side', 'graduate');

        return view('The-Graduate-Web/admin_tendik/belum_mendaftar', [
            'title'             => 'Mahasiswa Belum Mendaftar Wisuda',
            'user'              => $userData,
            'role'              => $roles,
            'daftarBelumDaftar' => $daftarBelumDaftar,
            'daftarSemester'    => $daftarSemester,
            'totalBelumDaftar'  => count($mahasiswaBelumDaftar),
            'filters'           => $filters,
        ]);
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
                    return view('The-Graduate-Web/mahasiswa/belum_terplot', [
                        'title' => 'Akses Ditolak',
                        'user'  => $userData,
                        'role'  => $roles,
                    ]);
                }
            } else {
                session()->set('active_side', 'graduate');
                return view('The-Graduate-Web/mahasiswa/belum_terplot', [
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
        return (new PersyaratanWisudaController())->index();
    }

    public function persyaratanStore()
    {
        return (new PersyaratanWisudaController())->store();
    }

    public function persyaratanUpdate()
    {
        return (new PersyaratanWisudaController())->update();
    }

    public function persyaratanDelete($id = null)
    {
        return (new PersyaratanWisudaController())->delete($id);
    }

    public function persyaratanToggleStatus($id = null)
    {
        return (new PersyaratanWisudaController())->toggleStatus($id);
    }

    public function persyaratanReorder()
    {
        return (new PersyaratanWisudaController())->reorder();
    }

    /* ------------------------------------------------------------------
     |  Delegasi Modul Semester (SemesterController)
     | ------------------------------------------------------------------ */

    public function semester()
    {
        return (new SemesterController())->index();
    }

    public function semesterStore()
    {
        return (new SemesterController())->store();
    }

    public function semesterUpdate()
    {
        return (new SemesterController())->update();
    }

    public function semesterActivate($kode = null)
    {
        return (new SemesterController())->activate($kode);
    }

    public function semesterNonactivate($kode = null)
    {
        return (new SemesterController())->nonactivate($kode);
    }

    public function semesterDelete($kode = null)
    {
        return (new SemesterController())->delete($kode);
    }

    /* ------------------------------------------------------------------
     |  Delegasi Modul SK Yudisium (SkYudisiumController)
     | ------------------------------------------------------------------ */

    public function sk_yudisium()
    {
        return (new SkYudisiumController())->index();
    }

    public function skYudisiumDownloadTemplate()
    {
        return (new SkYudisiumController())->downloadTemplate();
    }

    public function skYudisiumUpload()
    {
        return (new SkYudisiumController())->upload();
    }

    public function skYudisiumPreview()
    {
        return (new SkYudisiumController())->preview();
    }

    public function skYudisiumSave()
    {
        return (new SkYudisiumController())->save();
    }

    public function skYudisiumClearPreview()
    {
        return (new SkYudisiumController())->clearPreview();
    }

    public function skYudisiumSetTanggal()
    {
        return (new SkYudisiumController())->setTanggal();
    }

    public function skYudisiumBulkSetTanggal()
    {
        return (new SkYudisiumController())->bulkSetTanggal();
    }

    public function skYudisiumUpdateStatus()
    {
        return (new SkYudisiumController())->updateStatus();
    }

    public function skYudisiumDelete()
    {
        return (new SkYudisiumController())->delete();
    }

    public function skYudisiumStore()
    {
        return (new SkYudisiumController())->store();
    }

    /* ------------------------------------------------------------------
     |  Delegasi Modul Notifikasi (NotificationController)
     | ------------------------------------------------------------------ */

    public function fetchNotif()
    {
        return (new NotificationController())->fetchNotif();
    }

    public function markAsRead()
    {
        return (new NotificationController())->markAsRead();
    }

    public function semua_notifikasi()
    {
        return (new NotificationController())->semua_notifikasi();
    }

    /* ------------------------------------------------------------------
     |  Delegasi Modul Profil (ProfileController)
     | ------------------------------------------------------------------ */

    public function profile()
    {
        return (new ProfileController())->profile();
    }

    public function saveProfile()
    {
        return (new ProfileController())->saveProfile();
    }

    /* ------------------------------------------------------------------
     |  Chooser & Portal Switcher
     | ------------------------------------------------------------------ */

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

        return view('The-Graduate-Web/choose', [
            'title'           => 'Pilih Sisi Portal',
            'user'            => $userData,
            'role'            => $roles,
            'canUseGraduate'  => PortalRouter::canUseGraduate($roles),
            'graduateLanding' => PortalRouter::graduateLanding($roles),
            'thesisLanding'   => PortalRouter::thesisLanding($roles),
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

    /* ------------------------------------------------------------------
     |  Helper Private
     | ------------------------------------------------------------------ */

    private function ensureMahasiswaPlotted(): ?array
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return null;
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! in_array('mahasiswa', $roles, true)) {
            return $userData;
        }

        $npm = $userData['npm'] ?? $userData['id'] ?? null;
        if (! $npm) {
            return null;
        }

        $terplotModel = new MahasiswaTerplotModel();
        $isPlotted    = $terplotModel->where('npm', $npm)->countAllResults() > 0;

        if (! $isPlotted) {
            return false;
        }

        return $userData;
    }

    private function dashboard($folder, $title, array $extraData = [])
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);

        if (PortalRouter::graduateFolder($roles) !== $folder) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        session()->set('active_side', 'graduate');

        $view = "The-Graduate-Web/{$folder}/dashboard";

        if (! is_file(APPPATH . "Views/{$view}.php")) {
            $view = 'The-Graduate-Web/dashboard';
        }

        $viewData = array_merge([
            'title' => $title,
            'user'  => $userData,
            'role'  => $roles,
        ], $extraData);

        return view($view, $viewData);
    }

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

            return view('The-Graduate-Web/mahasiswa/belum_terplot', [
                'title' => 'Akses Ditolak',
                'user'  => $userData,
                'role'  => $roles,
                'fitur' => $fiturTerplot,
            ]);
        }

        // Halaman belum tersedia untuk role ini -> fallback ke admin_tendik jika fakultas, atau kembali ke dashboard.
        if (! is_file(APPPATH . "Views/The-Graduate-Web/{$folder}/{$halaman}.php")) {
            if ($folder === 'fakultas' && is_file(APPPATH . "Views/The-Graduate-Web/admin_tendik/{$halaman}.php")) {
                $folder = 'admin_tendik';
            } else {
                return redirect()->to(base_url(PortalRouter::graduateLanding($roles)));
            }
        }

        session()->set('active_side', 'graduate');

        return view("The-Graduate-Web/{$folder}/{$halaman}", [
            'title' => $judul,
            'user'  => $userData,
            'role'  => $roles,
        ]);
    }

    private function mahasiswaBelumTerplot(array $userData): bool
    {
        $npm = $userData['npm'] ?? $userData['id'] ?? null;

        if (! $npm) {
            return true;
        }

        $terplotModel = new MahasiswaTerplotModel();

        return $terplotModel->where('npm', $npm)->countAllResults() === 0;
    }

    /**
     * Mendapatkan daftar NPM mahasiswa yang sudah mengajukan pendaftaran wisuda.
     *
     * @return string[]
     */
    public function getRegisteredWisudaNpms(): array
    {
        $registered = [];

        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('pendaftaran_wisuda')) {
                $rows = $db->table('pendaftaran_wisuda')->select('npm')->get()->getResultArray();
                $registered = array_map('strval', array_column($rows, 'npm'));
            }
        } catch (\Throwable $e) {
            $registered = [];
        }

        if (empty($registered)) {
            // Data pendaftaran wisuda tahap front-end (sinkron dengan admin_tendik/pendaftaran_wisuda.php)
            $registered = ['2021010001', '2021010002', '2021010003', '2021010004', '2021010005', '2021010006'];
        }

        return $registered;
    }
}
