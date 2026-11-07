<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Models\PersyaratanWisudaModel;

/**
 * Controller Pengelolaan Persyaratan Wisuda Sisi The Graduate
 *
 * Menangani CRUD persyaratan dokumen kelulusan/wisuda untuk tendik, staf, admin, dan fakultas.
 */
class PersyaratanWisudaController extends BaseController
{
    /**
     * Memastikan hanya staf/tendik/admin/fakultas yang dapat mengelola persyaratan wisuda.
     */
    private function ensureManager(): ?array
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
     * Dapatkan URL redirect yang aman dengan mempertahankan parameter filter.
     */
    private function getRedirectUrl(): string
    {
        $redirect = $this->request->getPost('redirect_to');
        if (! empty($redirect) && str_starts_with($redirect, base_url('graduate/persyaratan'))) {
            return $redirect;
        }

        $referer = $this->request->getServer('HTTP_REFERER');
        if ($referer && str_starts_with($referer, base_url('graduate/persyaratan'))) {
            return $referer;
        }

        return base_url('graduate/persyaratan');
    }

    /**
     * Halaman daftar persyaratan wisuda dengan pencarian, filter, dan metrik.
     */
    public function index()
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $userData = session()->get('user_session');
            $roles    = $userData ? PortalRouter::normalizeRoles($userData) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $roles  = $auth['roles'];
        $folder = PortalRouter::graduateFolder($roles);
        if ($folder === 'fakultas' && ! is_file(APPPATH . 'Views/The-Graduate-Web/fakultas/persyaratan_kelulusan.php')) {
            $folder = 'admin_tendik';
        }

        $filters = [
            'q'      => trim((string) $this->request->getGet('q')),
            'status' => strtoupper(trim((string) $this->request->getGet('status'))),
            'sifat'  => strtolower(trim((string) $this->request->getGet('sifat'))),
        ];

        if (! in_array($filters['status'], ['AKTIF', 'NON-AKTIF'], true)) {
            $filters['status'] = '';
        }
        if (! in_array($filters['sifat'], ['wajib', 'opsional'], true)) {
            $filters['sifat'] = '';
        }

        $model = new PersyaratanWisudaModel();

        // Metrik Ringkasan (sebelum filter pencarian)
        $totalSemua    = $model->countAllResults(false);
        $totalAktif    = (new PersyaratanWisudaModel())->where('status', 'AKTIF')->countAllResults();
        $totalNonaktif = (new PersyaratanWisudaModel())->where('status', 'NON-AKTIF')->countAllResults();
        $totalWajib    = (new PersyaratanWisudaModel())->where('is_wajib', 1)->countAllResults();
        $totalOpsional = (new PersyaratanWisudaModel())->where('is_wajib', 0)->countAllResults();

        // Query daftar terfilter
        $query = $model->orderBy('urutan', 'ASC')->orderBy('id', 'ASC');

        if ($filters['q'] !== '') {
            $query->groupStart()
                ->like('nama', $filters['q'])
                ->orLike('keterangan', $filters['q'])
                ->groupEnd();
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['sifat'] === 'wajib') {
            $query->where('is_wajib', 1);
        } elseif ($filters['sifat'] === 'opsional') {
            $query->where('is_wajib', 0);
        }

        $perPage           = 50;
        $daftarPersyaratan = $query->paginate($perPage, 'persyaratan');
        $pager             = $query->pager;
        $totalRows         = $pager->getTotal('persyaratan');
        $currentPage       = $pager->getCurrentPage('persyaratan');

        $isFiltered          = ($filters['q'] !== '' || $filters['status'] !== '' || $filters['sifat'] !== '');
        $currentUrlWithQuery = current_url() . (! empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');

        session()->set('active_side', 'graduate');

        return view("The-Graduate-Web/{$folder}/persyaratan_kelulusan", [
            'title'               => 'Persyaratan Wisuda',
            'user'                => $auth['user'],
            'role'                => $roles,
            'daftarPersyaratan'   => $daftarPersyaratan,
            'filters'             => $filters,
            'isFiltered'          => $isFiltered,
            'currentUrlWithQuery' => $currentUrlWithQuery,
            'pager'               => $pager,
            'perPage'             => $perPage,
            'totalRows'           => $totalRows,
            'currentPage'         => $currentPage,
            'metrics'             => [
                'total'    => $totalSemua,
                'aktif'    => $totalAktif,
                'nonaktif' => $totalNonaktif,
                'wajib'    => $totalWajib,
                'opsional' => $totalOpsional,
            ],
        ]);
    }

    /**
     * Tambah persyaratan baru.
     */
    public function store()
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $userData = session()->get('user_session');
            $roles    = $userData ? PortalRouter::normalizeRoles($userData) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/persyaratan'));
        }

        $nama       = trim((string) $this->request->getPost('nama'));
        $keterangan = trim((string) $this->request->getPost('keterangan'));
        $tipeFile   = strtolower(trim((string) $this->request->getPost('tipe_file')));
        $maxSizeKb  = (int) $this->request->getPost('max_size_kb');
        $isWajib    = (int) $this->request->getPost('is_wajib');
        $urutan     = (int) $this->request->getPost('urutan');
        $status     = strtoupper(trim((string) $this->request->getPost('status')));

        if ($nama === '') {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Menambah', 'text' => 'Nama persyaratan wajib diisi']);
            return redirect()->to(base_url('graduate/persyaratan'))->withInput();
        }

        if (mb_strlen($nama) > 255) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Menambah', 'text' => 'Nama persyaratan maksimal 255 karakter']);
            return redirect()->to(base_url('graduate/persyaratan'))->withInput();
        }

        $safeAllowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'zip'];
        $cleanExts = [];
        foreach (explode(',', strtolower($tipeFile)) as $ext) {
            $ext = trim($ext, " .\t\n\r\0\x0B");
            if (in_array($ext, $safeAllowedExts, true)) {
                $cleanExts[] = $ext;
            }
        }
        $tipeFile = ! empty($cleanExts) ? implode(',', array_unique($cleanExts)) : 'pdf,jpg,jpeg,png';

        if ($maxSizeKb < 256) {
            $maxSizeKb = 2048;
        } elseif ($maxSizeKb > 51200) {
            $maxSizeKb = 51200;
        }

        if (! in_array($status, ['AKTIF', 'NON-AKTIF'], true)) {
            $status = 'AKTIF';
        }

        $model = new PersyaratanWisudaModel();

        // Validasi duplikasi nama persyaratan (case-insensitive & trim)
        $existing = $model->where('LOWER(nama)', strtolower($nama))->first();
        if ($existing) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Nama Sudah Digunakan',
                'text'  => 'Persyaratan dengan nama "' . $nama . '" sudah terdaftar! Harap gunakan nama dokumen yang berbeda.',
            ]);
            return redirect()->to($this->getRedirectUrl())->withInput();
        }

        if ($urutan <= 0) {
            $maxRow = $model->selectMax('urutan')->first();
            $urutan = (int) ($maxRow['urutan'] ?? 0) + 1;
        }

        try {
            $model->insert([
                'nama'        => $nama,
                'keterangan'  => $keterangan ?: null,
                'tipe_file'   => $tipeFile,
                'max_size_kb' => $maxSizeKb,
                'is_wajib'    => $isWajib ? 1 : 0,
                'urutan'      => $urutan,
                'status'      => $status,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menambah persyaratan wisuda: ' . $e->getMessage());
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Menambah', 'text' => 'Terjadi kesalahan sistem saat menyimpan data persyaratan']);
            return redirect()->to($this->getRedirectUrl())->withInput();
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => 'Persyaratan wisuda "' . $nama . '" berhasil ditambahkan',
        ]);

        return redirect()->to($this->getRedirectUrl());
    }

    /**
     * Perbarui data persyaratan.
     */
    public function update()
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $userData = session()->get('user_session');
            $roles    = $userData ? PortalRouter::normalizeRoles($userData) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/persyaratan'));
        }

        $id         = (int) $this->request->getPost('id');
        $nama       = trim((string) $this->request->getPost('nama'));
        $keterangan = trim((string) $this->request->getPost('keterangan'));
        $tipeFile   = strtolower(trim((string) $this->request->getPost('tipe_file')));
        $maxSizeKb  = (int) $this->request->getPost('max_size_kb');
        $isWajib    = (int) $this->request->getPost('is_wajib');
        $urutan     = (int) $this->request->getPost('urutan');
        $status     = strtoupper(trim((string) $this->request->getPost('status')));

        if ($id <= 0) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'ID persyaratan tidak valid']);
            return redirect()->to($this->getRedirectUrl());
        }

        if ($nama === '') {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Memperbarui', 'text' => 'Nama persyaratan wajib diisi']);
            return redirect()->to($this->getRedirectUrl())->withInput();
        }

        if (mb_strlen($nama) > 255) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Memperbarui', 'text' => 'Nama persyaratan maksimal 255 karakter']);
            return redirect()->to($this->getRedirectUrl())->withInput();
        }

        $safeAllowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'zip'];
        $cleanExts = [];
        foreach (explode(',', strtolower($tipeFile)) as $ext) {
            $ext = trim($ext, " .\t\n\r\0\x0B");
            if (in_array($ext, $safeAllowedExts, true)) {
                $cleanExts[] = $ext;
            }
        }
        $tipeFile = ! empty($cleanExts) ? implode(',', array_unique($cleanExts)) : 'pdf,jpg,jpeg,png';

        if ($maxSizeKb < 256) {
            $maxSizeKb = 2048;
        } elseif ($maxSizeKb > 51200) {
            $maxSizeKb = 51200;
        }

        if (! in_array($status, ['AKTIF', 'NON-AKTIF'], true)) {
            $status = 'AKTIF';
        }

        $model = new PersyaratanWisudaModel();
        $item  = $model->find($id);
        if (! $item) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data persyaratan tidak ditemukan']);
            return redirect()->to($this->getRedirectUrl());
        }

        // Validasi duplikasi nama persyaratan jika nama diubah (case-insensitive & trim)
        $existing = $model->where('LOWER(nama)', strtolower($nama))
            ->where('id !=', $id)
            ->first();
        if ($existing) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Nama Sudah Digunakan',
                'text'  => 'Persyaratan dengan nama "' . $nama . '" sudah digunakan oleh dokumen lain!',
            ]);
            return redirect()->to($this->getRedirectUrl())->withInput();
        }

        if ($urutan <= 0) {
            $urutan = (int) ($item['urutan'] ?? 1);
        }

        try {
            $model->update($id, [
                'nama'        => $nama,
                'keterangan'  => $keterangan ?: null,
                'tipe_file'   => $tipeFile,
                'max_size_kb' => $maxSizeKb,
                'is_wajib'    => $isWajib ? 1 : 0,
                'urutan'      => $urutan,
                'status'      => $status,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal memperbarui persyaratan wisuda id=' . $id . ': ' . $e->getMessage());
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Memperbarui', 'text' => 'Terjadi kesalahan sistem saat memperbarui data persyaratan']);
            return redirect()->to($this->getRedirectUrl())->withInput();
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => 'Persyaratan wisuda "' . $nama . '" berhasil diperbarui',
        ]);

        return redirect()->to($this->getRedirectUrl());
    }

    /**
     * Hapus persyaratan wisuda.
     */
    public function delete($id = null)
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $userData = session()->get('user_session');
            $roles    = $userData ? PortalRouter::normalizeRoles($userData) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to($this->getRedirectUrl());
        }

        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        if ($id <= 0) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'ID persyaratan tidak valid']);
            return redirect()->to($this->getRedirectUrl());
        }

        $model = new PersyaratanWisudaModel();
        $item  = $model->find($id);
        if (! $item) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data persyaratan tidak ditemukan atau sudah dihapus']);
            return redirect()->to($this->getRedirectUrl());
        }

        // Cek integritas data: cegah penghapusan jika persyaratan sudah memiliki berkas mahasiswa
        $berkasModel = new \App\Models\BerkasPendaftaranWisudaModel();
        $usedCount   = $berkasModel->groupStart()
            ->where('persyaratan_id', $id)
            ->orWhere('nama_persyaratan', $item['nama'])
            ->groupEnd()
            ->countAllResults();
        if ($usedCount > 0) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Tidak Dapat Dihapus',
                'text'  => 'Persyaratan "' . $item['nama'] . '" tidak dapat dihapus karena sudah memiliki ' . $usedCount . ' berkas yang diunggah oleh mahasiswa. Silakan ubah statusnya menjadi NON-AKTIF agar tidak tampil pada form baru.',
            ]);
            return redirect()->to($this->getRedirectUrl());
        }

        try {
            $model->delete($id);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menghapus persyaratan wisuda id=' . $id . ': ' . $e->getMessage());
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Terjadi kesalahan sistem saat menghapus data']);
            return redirect()->to($this->getRedirectUrl());
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil Dihapus',
            'text'  => 'Persyaratan wisuda "' . $item['nama'] . '" berhasil dihapus',
        ]);

        return redirect()->to($this->getRedirectUrl());
    }

    /**
     * Ubah status aktif/non-aktif secara cepat.
     */
    public function toggleStatus($id = null)
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            $userData = session()->get('user_session');
            $roles    = $userData ? PortalRouter::normalizeRoles($userData) : [];
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to($this->getRedirectUrl());
        }

        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        if ($id <= 0) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'ID persyaratan tidak valid']);
            return redirect()->to($this->getRedirectUrl());
        }

        $model = new PersyaratanWisudaModel();
        $item  = $model->find($id);
        if (! $item) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data persyaratan tidak ditemukan']);
            return redirect()->to($this->getRedirectUrl());
        }

        try {
            $newStatus = ($item['status'] === 'AKTIF') ? 'NON-AKTIF' : 'AKTIF';
            $model->update($id, ['status' => $newStatus]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal mengubah status persyaratan id=' . $id . ': ' . $e->getMessage());
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Terjadi kesalahan sistem saat mengubah status']);
            return redirect()->to($this->getRedirectUrl());
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Status Diperbarui',
            'text'  => 'Status persyaratan "' . $item['nama'] . '" berhasil diubah menjadi ' . $newStatus,
        ]);

        return redirect()->to($this->getRedirectUrl());
    }

    /**
     * Simpan urutan persyaratan dari drag-and-drop.
     */
    public function reorder()
    {
        $auth = $this->ensureManager();
        if ($auth === null) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Akses ditolak',
            ]);
        }

        $orders = $this->request->getJSON(true) ?: $this->request->getPost('orders');
        if (empty($orders) || ! is_array($orders)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Data urutan tidak valid',
            ]);
        }

        $validOrders = [];
        foreach ($orders as $o) {
            $intId = (int) $o;
            if ($intId > 0) {
                $validOrders[] = $intId;
            }
        }

        if (empty($validOrders)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Daftar ID urutan tidak valid',
            ]);
        }

        $model    = new PersyaratanWisudaModel();
        $totalAll = $model->countAllResults();

        // Cegah reorder parsial (misal saat terfilter atau terpecah) agar tidak mengacak urutan item lainnya
        if (count($validOrders) < $totalAll) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Pengaturan urutan hanya dapat dilakukan pada seluruh daftar persyaratan (tanpa filter/pencarian aktif).',
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($validOrders as $index => $id) {
            $model->update($id, [
                'urutan'     => (int) $index + 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Gagal menyimpan urutan ke database',
            ]);
        }

        return $this->response->setJSON([
            'status'     => 'success',
            'message'    => 'Urutan persyaratan berhasil diperbarui',
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }
}

