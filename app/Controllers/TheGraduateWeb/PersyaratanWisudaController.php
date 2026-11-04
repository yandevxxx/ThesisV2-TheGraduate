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

        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin', 'fakultas'])) {
            return null;
        }

        return [
            'user'   => $userData,
            'roles'  => $roles,
            'akunId' => $userData['akun_id'] ?? $userData['id'] ?? null,
        ];
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

        $perPage           = 10;
        $daftarPersyaratan = $query->paginate($perPage, 'persyaratan');
        $pager             = $query->pager;
        $totalRows         = $pager->getTotal('persyaratan');
        $currentPage       = $pager->getCurrentPage('persyaratan');

        session()->set('active_side', 'graduate');

        return view("The-Graduate-Web/{$folder}/persyaratan_kelulusan", [
            'title'             => 'Persyaratan Wisuda',
            'user'              => $auth['user'],
            'role'              => $roles,
            'daftarPersyaratan' => $daftarPersyaratan,
            'filters'           => $filters,
            'pager'             => $pager,
            'perPage'           => $perPage,
            'totalRows'         => $totalRows,
            'currentPage'       => $currentPage,
            'metrics'           => [
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

        if ($tipeFile === '') {
            $tipeFile = 'pdf,jpg,jpeg,png';
        }

        if ($maxSizeKb <= 0) {
            $maxSizeKb = 2048;
        }

        if (! in_array($status, ['AKTIF', 'NON-AKTIF'], true)) {
            $status = 'AKTIF';
        }

        $model = new PersyaratanWisudaModel();
        $model->insert([
            'nama'        => $nama,
            'keterangan'  => $keterangan ?: null,
            'tipe_file'   => $tipeFile,
            'max_size_kb' => $maxSizeKb,
            'is_wajib'    => $isWajib ? 1 : 0,
            'urutan'      => $urutan,
            'status'      => $status,
        ]);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => 'Persyaratan wisuda "' . $nama . '" berhasil ditambahkan',
        ]);

        return redirect()->to(base_url('graduate/persyaratan'));
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
            return redirect()->to(base_url('graduate/persyaratan'));
        }

        if ($nama === '') {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Memperbarui', 'text' => 'Nama persyaratan wajib diisi']);
            return redirect()->to(base_url('graduate/persyaratan'))->withInput();
        }

        if (mb_strlen($nama) > 255) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal Memperbarui', 'text' => 'Nama persyaratan maksimal 255 karakter']);
            return redirect()->to(base_url('graduate/persyaratan'))->withInput();
        }

        if ($tipeFile === '') {
            $tipeFile = 'pdf,jpg,jpeg,png';
        }

        if ($maxSizeKb <= 0) {
            $maxSizeKb = 2048;
        }

        if (! in_array($status, ['AKTIF', 'NON-AKTIF'], true)) {
            $status = 'AKTIF';
        }

        $model = new PersyaratanWisudaModel();
        $item  = $model->find($id);
        if (! $item) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data persyaratan tidak ditemukan']);
            return redirect()->to(base_url('graduate/persyaratan'));
        }

        $model->update($id, [
            'nama'        => $nama,
            'keterangan'  => $keterangan ?: null,
            'tipe_file'   => $tipeFile,
            'max_size_kb' => $maxSizeKb,
            'is_wajib'    => $isWajib ? 1 : 0,
            'urutan'      => $urutan,
            'status'      => $status,
        ]);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil',
            'text'  => 'Persyaratan wisuda "' . $nama . '" berhasil diperbarui',
        ]);

        return redirect()->to(base_url('graduate/persyaratan'));
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
            return redirect()->to(base_url('graduate/persyaratan'));
        }

        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        if ($id <= 0) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'ID persyaratan tidak valid']);
            return redirect()->to(base_url('graduate/persyaratan'));
        }

        $model = new PersyaratanWisudaModel();
        $item  = $model->find($id);
        if (! $item) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data persyaratan tidak ditemukan atau sudah dihapus']);
            return redirect()->to(base_url('graduate/persyaratan'));
        }

        $model->delete($id);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Berhasil Dihapus',
            'text'  => 'Persyaratan wisuda "' . $item['nama'] . '" berhasil dihapus',
        ]);

        return redirect()->to(base_url('graduate/persyaratan'));
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
            return redirect()->to(base_url('graduate/persyaratan'));
        }

        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        if ($id <= 0) {
            session()->setFlashdata('message', ['icon' => 'error', 'title' => 'Gagal', 'text' => 'ID persyaratan tidak valid']);
            return redirect()->to(base_url('graduate/persyaratan'));
        }

        $model = new PersyaratanWisudaModel();
        $item  = $model->find($id);
        if (! $item) {
            session()->setFlashdata('message', ['icon' => 'warning', 'title' => 'Tidak Ditemukan', 'text' => 'Data persyaratan tidak ditemukan']);
            return redirect()->to(base_url('graduate/persyaratan'));
        }

        $newStatus = ($item['status'] === 'AKTIF') ? 'NON-AKTIF' : 'AKTIF';
        $model->update($id, ['status' => $newStatus]);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Status Diperbarui',
            'text'  => 'Status persyaratan "' . $item['nama'] . '" berhasil diubah menjadi ' . $newStatus,
        ]);

        return redirect()->to(base_url('graduate/persyaratan'));
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

        $model = new PersyaratanWisudaModel();
        $db    = \Config\Database::connect();
        $db->transStart();

        foreach ($orders as $index => $id) {
            $model->update((int) $id, [
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

