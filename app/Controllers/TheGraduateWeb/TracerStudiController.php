<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Models\BankPertanyaanTracerStudiModel;
use App\Models\JawabanTracerStudiAwalModel;
use App\Models\MahasiswaTerplotModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use App\Models\SemesterGraduateModel;
use CodeIgniter\HTTP\RequestInterface;

/**
 * Controller Tracer Studi Awal Sisi The Graduate (B-10 & form pengisian Mahasiswa).
 *
 * - Staf (tendik/staf/admin) dan Prodi (prodi/kaprodi) melihat daftar pertanyaan (B-10).
 * - Mahasiswa terplot SK Yudisium wajib melihat dan mengisi daftar pertanyaan yang sama
 *   sebelum dapat mengisi Biodata Mahasiswa.
 */
class TracerStudiController extends BaseController
{
    protected PertanyaanTracerStudiAwalModel $pertanyaanModel;
    protected JawabanTracerStudiAwalModel $jawabanModel;
    protected MahasiswaTerplotModel $terplotModel;
    protected BankPertanyaanTracerStudiModel $bankModel;

    public function __construct(
        ?PertanyaanTracerStudiAwalModel $pertanyaanModel = null,
        ?JawabanTracerStudiAwalModel $jawabanModel = null,
        ?MahasiswaTerplotModel $terplotModel = null,
        ?BankPertanyaanTracerStudiModel $bankModel = null
    ) {
        $this->pertanyaanModel = $pertanyaanModel ?? new PertanyaanTracerStudiAwalModel();
        $this->jawabanModel    = $jawabanModel ?? new JawabanTracerStudiAwalModel();
        $this->terplotModel    = $terplotModel ?? new MahasiswaTerplotModel();
        $this->bankModel       = $bankModel ?? new BankPertanyaanTracerStudiModel();
    }

    /**
     * Halaman utama controller diarahkan ke daftar pertanyaan tracer studi awal.
     */
    public function index()
    {
        return $this->daftarPertanyaan();
    }

    /**
     * Menampilkan daftar pertanyaan form tracer studi awal (seragam untuk semua prodi).
     */
    public function daftarPertanyaan()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        session()->set('active_side', 'graduate');
        session()->remove(['tracer_preview_draft', '_ci_old_input']);

        $isTableReady         = $this->pertanyaanModel->isTableReady();
        $isBankTableReady     = $this->bankModel->isTableReady();
        if ($isTableReady && $isBankTableReady) {
            $this->pertanyaanModel->syncExclusiveWithBank(
                $this->bankModel,
                $this->resolveCreatorIdentifier($context['user'])
            );
        }

        $daftarPertanyaan     = $this->pertanyaanModel->getDaftarManajemenPertanyaan();
        $editPertanyaanErrors = session()->getFlashdata('edit_pertanyaan_errors');
        $editPertanyaanId     = (int) (session()->getFlashdata('edit_pertanyaan_id') ?? 0);

        return view('The-Graduate-Web/tracer_studi/pertanyaan', [
            'title'                => 'Daftar Pertanyaan Tracer Study Awal',
            'user'                 => $context['user'],
            'role'                 => $context['roles'],
            'daftarPertanyaan'     => $daftarPertanyaan,
            'isTableReady'         => $isTableReady,
            'isBankTableReady'     => $isBankTableReady,
            'editPertanyaanErrors' => is_array($editPertanyaanErrors) ? $editPertanyaanErrors : [],
            'editPertanyaanId'     => $editPertanyaanId,
        ]);
    }

    /**
     * Menyimpan pertanyaan tracer studi awal baru oleh Staf atau Prodi (B-11).
     */
    public function storePertanyaan()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request = $this->currentRequest();
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $payload      = $this->extractPertanyaanPayload($request);
        $kategoriBank = trim((string) ($request->getPost('kategori_bank') ?? ''));

        if ($this->bankModel->isTableReady() && strtoupper(trim((string) ($payload['status'] ?? 'AKTIF'))) === 'NON-AKTIF') {
            if ($this->pertanyaanModel->isTextActiveInForm((string) ($payload['teks_pertanyaan'] ?? ''))) {
                $dupMsg = 'Pertanyaan dengan teks tersebut saat ini sudah aktif di daftar Pertanyaan Tracer Study.';
                session()->setFlashdata('pertanyaan_errors', [$dupMsg]);
                session()->setFlashdata('message', [
                    'icon'  => 'warning',
                    'title' => 'Sudah Ada di Pertanyaan Aktif',
                    'text'  => $dupMsg,
                ]);

                return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'))->withInput();
            }

            $bankRes = $this->bankModel->createBankPertanyaan([
                'kategori'         => $kategoriBank !== '' ? $kategoriBank : BankPertanyaanTracerStudiModel::DEFAULT_KATEGORI,
                'teks_pertanyaan'  => $payload['teks_pertanyaan'],
                'tipe_jawaban'     => $payload['tipe_jawaban'],
                'opsi_jawaban'     => $payload['opsi_jawaban'],
                'is_wajib_default' => $payload['is_wajib'] ?? '1',
            ], $this->resolveCreatorIdentifier($context['user']));

            if (! $bankRes['success']) {
                session()->setFlashdata('pertanyaan_errors', $bankRes['errors']);
                session()->setFlashdata('message', [
                    'icon'  => 'error',
                    'title' => 'Gagal Menambah Pertanyaan',
                    'text'  => implode(' ', $bankRes['errors']),
                ]);

                return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'))->withInput();
            }

            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Disimpan ke Bank Pertanyaan',
                'text'  => 'Pertanyaan berstatus nonaktif hanya disimpan di Bank Pertanyaan Tracer Study.',
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $result  = $this->pertanyaanModel->createPertanyaan($payload);
        if (! $result['success']) {
            session()->setFlashdata('pertanyaan_errors', $result['errors']);
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Menambah Pertanyaan',
                'text'  => implode(' ', $result['errors']),
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'))->withInput();
        }

        if ($this->bankModel->isTableReady()) {
            $this->bankModel->deleteByNormalizedText((string) ($payload['teks_pertanyaan'] ?? ''));
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Pertanyaan Ditambahkan',
            'text'  => 'Pertanyaan aktif baru berhasil disimpan dan hanya tampil di daftar Pertanyaan Tracer Study.',
        ]);

        return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
    }

    /**
     * Memperbarui pertanyaan tracer studi awal oleh Staf atau Prodi (B-15).
     */
    public function updatePertanyaan(?int $id = null)
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request = $this->currentRequest();
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $targetId = $this->resolvePertanyaanId($id, $request);
        if ($targetId <= 0) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Mengubah Pertanyaan',
                'text'  => 'ID pertanyaan tracer study awal tidak valid.',
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $payload         = $this->extractPertanyaanPayload($request);
        $deactivateMove  = $this->bankModel->isTableReady()
            && strtoupper(trim((string) ($payload['status'] ?? 'AKTIF'))) === 'NON-AKTIF';

        if ($deactivateMove && $this->pertanyaanModel->isTableReady()) {
            $otherActive = (int) $this->pertanyaanModel
                ->where('status', 'AKTIF')
                ->where('id !=', $targetId)
                ->countAllResults();

            if ($otherActive <= 0) {
                $msg = 'Minimal satu pertanyaan aktif harus tetap berada pada form Tracer Study Awal.';
                session()->setFlashdata('edit_pertanyaan_errors', [$msg]);
                session()->setFlashdata('edit_pertanyaan_id', $targetId);
                session()->setFlashdata('message', [
                    'icon'  => 'warning',
                    'title' => 'Tidak Dapat Menonaktifkan Semua Pertanyaan',
                    'text'  => $msg,
                ]);

                return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'))->withInput();
            }
        }

        $result  = $this->pertanyaanModel->updatePertanyaan($targetId, $payload);
        if (! $result['success']) {
            if (empty($result['notFound'])) {
                session()->setFlashdata('edit_pertanyaan_errors', $result['errors']);
                session()->setFlashdata('edit_pertanyaan_id', $targetId);
            }
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Mengubah Pertanyaan',
                'text'  => implode(' ', $result['errors']),
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'))->withInput();
        }

        if ($deactivateMove) {
            $moveRes = $this->pertanyaanModel->moveQuestionToBank(
                $targetId,
                $this->bankModel,
                $this->resolveCreatorIdentifier($context['user'])
            );

            if ($moveRes['success']) {
                session()->setFlashdata('message', [
                    'icon'  => 'success',
                    'title' => 'Dipindahkan ke Bank Pertanyaan',
                    'text'  => 'Pertanyaan diperbarui, dinonaktifkan, dan langsung dipindahkan ke Bank Pertanyaan.',
                ]);

                return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
            }
        }

        if ($this->bankModel->isTableReady()) {
            $this->bankModel->deleteByNormalizedText((string) ($payload['teks_pertanyaan'] ?? ''));
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Pertanyaan Diperbarui',
            'text'  => 'Perubahan pertanyaan tracer study awal berhasil disimpan dan tampil pada formulir sesuai pengaturan tampil. Jawaban mahasiswa yang sudah tersimpan tidak berubah.',
        ]);

        return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
    }

    /**
     * Menghapus pertanyaan tracer studi awal setelah konfirmasi Staf atau Prodi (B-16).
     */
    public function deletePertanyaan(?int $id = null)
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request = $this->currentRequest();
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $targetId = $this->resolvePertanyaanId($id, $request);
        if ($targetId <= 0) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Menghapus Pertanyaan',
                'text'  => 'ID pertanyaan tracer study awal tidak valid.',
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $result = $this->pertanyaanModel->deletePertanyaan($targetId);
        if (! $result['success']) {
            session()->setFlashdata('message', [
                'icon'  => ! empty($result['notFound']) ? 'warning' : 'error',
                'title' => ! empty($result['notFound']) ? 'Data Tidak Ditemukan' : 'Gagal Menghapus Pertanyaan',
                'text'  => (string) ($result['error'] ?? 'Gagal menghapus pertanyaan tracer study awal.'),
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Pertanyaan Dihapus',
            'text'  => 'Pertanyaan tracer study awal berhasil dihapus dan tidak lagi tampil pada form. Jawaban mahasiswa yang sudah tersimpan tetap utuh di halaman pemantauan.',
        ]);

        return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
    }

    /**
     * Mengaktifkan atau mematikan pengaturan "Wajib Diisi" pada satu pertanyaan (B-17).
     */
    public function toggleWajibPertanyaan(?int $id = null)
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request = $this->currentRequest();
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $targetId = $this->resolvePertanyaanId($id, $request);
        if ($targetId <= 0) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Mengubah Pengaturan Wajib',
                'text'  => 'ID pertanyaan tracer study awal tidak valid.',
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $rawWajib      = $request->getPost('is_wajib');
        $explicitWajib = null;
        if ($rawWajib !== null && trim((string) $rawWajib) !== '') {
            $explicitWajib = PertanyaanTracerStudiAwalModel::parseIsWajibFlag($rawWajib) === 1;
        }

        $result = $this->pertanyaanModel->toggleWajibPertanyaan($targetId, $explicitWajib);
        if (! $result['success']) {
            session()->setFlashdata('message', [
                'icon'  => ! empty($result['notFound']) ? 'warning' : 'error',
                'title' => ! empty($result['notFound']) ? 'Data Tidak Ditemukan' : 'Gagal Mengubah Pengaturan Wajib',
                'text'  => (string) ($result['error'] ?? 'Gagal memperbarui pengaturan Wajib Diisi.'),
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $statusLabel = ((int) ($result['is_wajib'] ?? 1)) === 1 ? 'Wajib Diisi' : 'Opsional';
        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Pengaturan Sifat Diperbarui',
            'text'  => sprintf('Pertanyaan berhasil ditetapkan menjadi "%s" untuk form tracer study awal berikutnya.', $statusLabel),
        ]);

        return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractPertanyaanPayload(RequestInterface $request): array
    {
        return [
            'teks_pertanyaan' => $request->getPost('teks_pertanyaan'),
            'tipe_jawaban'    => $request->getPost('tipe_jawaban'),
            'opsi_jawaban'    => $request->getPost('opsi_jawaban'),
            'urutan'          => $request->getPost('urutan'),
            'is_wajib'        => $request->getPost('is_wajib') ?? '1',
            'status'          => $request->getPost('status') ?? 'AKTIF',
        ];
    }

    protected function resolvePertanyaanId(?int $routeId, RequestInterface $request): int
    {
        if ($routeId !== null && $routeId > 0) {
            return $routeId;
        }

        return (int) $request->getPost('id');
    }

    /**
     * Menyimpan pengaturan pertanyaan yang tampil pada form tracer studi awal (B-12).
     */
    public function simpanTampilPertanyaan()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request = $this->currentRequest();
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $selectedIds = $request->getPost('tampil_ids');
        if (! is_array($selectedIds)) {
            $selectedIds = [];
        }

        $bankReady = $this->bankModel->isTableReady();
        $result    = $bankReady
            ? $this->pertanyaanModel->deactivateMultipleToBank(
                $selectedIds,
                $this->bankModel,
                $this->resolveCreatorIdentifier($context['user'])
            )
            : $this->pertanyaanModel->updateTampilPertanyaan($selectedIds);

        if (! $result['success']) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Pengaturan Gagal Disimpan',
                'text'  => (string) ($result['error'] ?? 'Gagal memperbarui pengaturan tampil pertanyaan.'),
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        // B-18: Apabila input urutan juga dikirim bersama pengaturan tampil, simpan urutan baru sekaligus
        $orderSaved = false;
        $orderInput = $this->extractUrutanPostInput($request);
        if ($orderInput !== null) {
            $orderRes = $this->pertanyaanModel->updateUrutanPertanyaan($orderInput['data'], $orderInput['isOrderedList']);
            if (! $orderRes['success']) {
                session()->setFlashdata('message', [
                    'icon'  => 'warning',
                    'title' => 'Urutan Gagal Disimpan',
                    'text'  => (string) ($orderRes['error'] ?? 'Gagal memperbarui urutan pertanyaan.'),
                ]);

                return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
            }
            $orderSaved = true;
        }

        if ($bankReady) {
            $movedCount = (int) ($result['movedCount'] ?? 0);
            if ($movedCount > 0 && $orderSaved) {
                $text = sprintf(
                    '%d pertanyaan tetap aktif, %d pertanyaan dinonaktifkan dan dipindahkan ke Bank Pertanyaan, serta urutan form berhasil disimpan.',
                    $result['activeCount'],
                    $movedCount
                );
            } elseif ($movedCount > 0) {
                $text = sprintf(
                    '%d pertanyaan tetap aktif dan %d pertanyaan yang dinonaktifkan berhasil dipindahkan ke Bank Pertanyaan.',
                    $result['activeCount'],
                    $movedCount
                );
            } elseif ($orderSaved) {
                $text = sprintf(
                    'Pengaturan %d pertanyaan aktif dan urutan pertanyaan berhasil disimpan untuk form mahasiswa berikutnya.',
                    $result['activeCount']
                );
            } else {
                $text = sprintf('%d pertanyaan aktif berhasil disimpan untuk form mahasiswa berikutnya.', $result['activeCount']);
            }
        } else {
            $text = $orderSaved
                ? sprintf(
                    'Pengaturan tampil (%d tampil, %d disembunyikan) dan urutan pertanyaan berhasil disimpan untuk form mahasiswa berikutnya. Jawaban mahasiswa yang sudah tersimpan tidak berubah.',
                    $result['activeCount'],
                    $result['inactiveCount']
                )
                : sprintf(
                    'Pengaturan tampil berhasil disimpan (%d pertanyaan tampil, %d disembunyikan) dan berlaku untuk pengisian berikutnya. Jawaban mahasiswa yang sudah tersimpan tidak berubah.',
                    $result['activeCount'],
                    $result['inactiveCount']
                );
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Pengaturan Disimpan',
            'text'  => $text,
        ]);

        return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
    }

    /**
     * Menonaktifkan satu pertanyaan dari toggle daftar aktif dan langsung memindahkannya ke Bank Pertanyaan.
     */
    public function nonaktifkanKeBank(?int $id = null)
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request = $this->currentRequest();
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $targetId = $this->resolvePertanyaanId($id, $request);
        if ($targetId <= 0) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Menonaktifkan Pertanyaan',
                'text'  => 'ID pertanyaan tracer study awal tidak valid.',
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $result = $this->pertanyaanModel->moveQuestionToBank(
            $targetId,
            $this->bankModel,
            $this->resolveCreatorIdentifier($context['user'])
        );

        if (! $result['success']) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Tidak Dapat Memindahkan Pertanyaan',
                'text'  => (string) ($result['error'] ?? 'Gagal memindahkan pertanyaan ke Bank Pertanyaan.'),
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Dipindahkan ke Bank Pertanyaan',
            'text'  => 'Pertanyaan dinonaktifkan dari form aktif dan langsung dipindahkan ke Bank Pertanyaan. Anda dapat mengambilnya kembali kapan saja, sementara jawaban mahasiswa tetap tersimpan aman.',
        ]);

        return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
    }

    /**
     * Menyimpan urutan tampil pertanyaan form tracer studi awal secara massal (B-18).
     */
    public function simpanUrutanPertanyaan()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request = $this->currentRequest();
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $orderInput = $this->extractUrutanPostInput($request);
        if ($orderInput === null) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Urutan Tidak Valid',
                'text'  => 'Tidak ada data urutan pertanyaan yang dikirim.',
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        $result = $this->pertanyaanModel->updateUrutanPertanyaan($orderInput['data'], $orderInput['isOrderedList']);
        if (! $result['success']) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Urutan Gagal Disimpan',
                'text'  => (string) ($result['error'] ?? 'Gagal memperbarui urutan pertanyaan.'),
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Urutan Disimpan',
            'text'  => sprintf(
                'Urutan %d pertanyaan berhasil disimpan dan langsung berlaku pada formulir mahasiswa berikutnya. Jawaban yang sudah tersimpan tidak berubah.',
                $result['updatedCount']
            ),
        ]);

        return redirect()->to(base_url('graduate/pertanyaan_tracer_studi'));
    }

    /**
     * @return array{data: array<int|string, mixed>, isOrderedList: bool}|null
     */
    protected function extractUrutanPostInput(RequestInterface $request): ?array
    {
        $orders = $request->getPost('orders');
        if (is_array($orders) && $orders !== []) {
            return ['data' => $orders, 'isOrderedList' => true];
        }

        $urutanMap = $request->getPost('urutan_map');
        if (is_array($urutanMap) && $urutanMap !== []) {
            return ['data' => $urutanMap, 'isOrderedList' => false];
        }

        return null;
    }

    /**
     * Halaman pratinjau form Tracer Studi Awal untuk Staf dan Prodi (B-19a).
     * Menampilkan form aktif persis seperti tampilan mahasiswa tanpa menyimpan jawaban ke database.
     */
    public function pratinjau()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        session()->set('active_side', 'graduate');

        $previewDraft   = session()->get('tracer_preview_draft');
        $previewAnswers = is_array($previewDraft['answers'] ?? null) ? $previewDraft['answers'] : [];
        $jawabanMap     = [];
        foreach ($previewAnswers as $qid => $ansVal) {
            $jawabanMap[(int) $qid] = ['jawaban' => (string) $ansVal];
        }
        $tracerErrors   = session()->getFlashdata('tracer_errors');

        return view('The-Graduate-Web/mahasiswa/tracer_studi', [
            'title'            => 'Pratinjau Form Tracer Study Awal',
            'user'             => $context['user'],
            'role'             => $context['roles'],
            'terplot'          => [
                'npm'           => 'SIMULASI-MHS',
                'nama'          => 'Simulasi Tampilan Mahasiswa',
                'kode_semester' => 'PRATINJAU',
            ],
            'daftarPertanyaan' => $this->pertanyaanModel->getDaftarPertanyaan(),
            'jawabanMap'       => $jawabanMap,
            'sudahDiisi'       => false,
            'lastSubmittedAt'  => null,
            'isTableReady'     => $this->pertanyaanModel->isTableReady(),
            'tracerErrors'     => is_array($tracerErrors) ? $tracerErrors : [],
            'isPreview'        => true,
            'previewBackUrl'   => base_url('graduate/pertanyaan_tracer_studi'),
            'formActionUrl'    => base_url('graduate/pertanyaan_tracer_studi/pratinjau'),
            'hasPreviewDraft'  => $previewAnswers !== [],
        ]);
    }

    /**
     * Memvalidasi pengisian simulasi pada mode pratinjau dan meneruskan ke halaman lanjutan
     * tanpa menulis apa pun ke tabel jawaban_tracer_studi_awal (B-19a).
     */
    public function submitPratinjau()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request = $this->currentRequest();
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi/pratinjau'));
        }

        $daftarPertanyaan = $this->pertanyaanModel->getDaftarPertanyaan();
        if ($daftarPertanyaan === []) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Daftar Pertanyaan Kosong',
                'text'  => 'Belum ada pertanyaan Tracer Study Awal aktif untuk disimulasikan.',
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi/pratinjau'));
        }

        $rawJawaban = $request->getPost('jawaban');
        if (! is_array($rawJawaban)) {
            $rawJawaban = [];
        }

        $validation = JawabanTracerStudiAwalModel::validateJawabanSet($daftarPertanyaan, $rawJawaban);
        if ($validation['errors'] !== []) {
            session()->setFlashdata('tracer_errors', $validation['errors']);
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Isian Simulasi Belum Lengkap',
                'text'  => 'Validasi aktif: mohon lengkapi seluruh pertanyaan wajib Tracer Study Awal.',
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi/pratinjau'))->withInput();
        }

        session()->remove('_ci_old_input');
        session()->set('tracer_preview_draft', [
            'answers'        => $validation['answers'],
            'submitted_at'   => date('Y-m-d H:i:s'),
            'question_count' => count($daftarPertanyaan),
        ]);

        return redirect()->to(base_url('graduate/pertanyaan_tracer_studi/pratinjau/lanjut'));
    }

    /**
     * Halaman lanjutan simulasi setelah Staf/Prodi mengirim form pratinjau (B-19a).
     */
    public function pratinjauLanjut()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        session()->set('active_side', 'graduate');

        $previewDraft = session()->get('tracer_preview_draft');
        if (! is_array($previewDraft) || ! is_array($previewDraft['answers'] ?? null)) {
            session()->setFlashdata('message', [
                'icon'  => 'info',
                'title' => 'Simulasi Belum Dikirim',
                'text'  => 'Silakan isi dan kirim form pratinjau terlebih dahulu untuk melihat perpindahan ke halaman berikutnya.',
            ]);

            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi/pratinjau'));
        }

        $daftarPertanyaan = $this->pertanyaanModel->getDaftarPertanyaan();
        $previewAnswers   = $previewDraft['answers'];
        $summaryRows      = [];

        foreach ($daftarPertanyaan as $idx => $pertanyaan) {
            $qid     = (int) ($pertanyaan['id'] ?? $pertanyaan['urutan'] ?? ($idx + 1));
            $jawaban = array_key_exists($qid, $previewAnswers)
                ? trim((string) $previewAnswers[$qid])
                : '';

            $summaryRows[] = [
                'urutan'     => (int) ($pertanyaan['urutan'] ?? ($idx + 1)),
                'pertanyaan' => (string) ($pertanyaan['teks_pertanyaan'] ?? ''),
                'is_wajib'   => ! empty($pertanyaan['is_wajib']),
                'tipe_label' => PertanyaanTracerStudiAwalModel::formatTipeJawaban((string) ($pertanyaan['tipe_jawaban'] ?? 'TEKS')),
                'jawaban'    => $jawaban,
            ];
        }

        return view('The-Graduate-Web/tracer_studi/pratinjau_lanjut', [
            'title'          => 'Halaman Lanjutan Pratinjau Tracer Study Awal',
            'user'           => $context['user'],
            'role'           => $context['roles'],
            'summaryRows'    => $summaryRows,
            'submittedAt'    => (string) ($previewDraft['submitted_at'] ?? date('Y-m-d H:i:s')),
            'previewFormUrl' => base_url('graduate/pertanyaan_tracer_studi/pratinjau'),
            'editModeUrl'    => base_url('graduate/pertanyaan_tracer_studi'),
            'resetUrl'       => base_url('graduate/pertanyaan_tracer_studi/pratinjau/reset'),
        ]);
    }

    /**
     * Menghapus sementara draft jawaban simulasi pada mode pratinjau (B-19a).
     */
    public function resetPratinjau()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        if (strtolower($this->currentRequest()->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/pertanyaan_tracer_studi/pratinjau'));
        }

        session()->remove(['tracer_preview_draft', '_ci_old_input']);
        session()->setFlashdata('message', [
            'icon'  => 'info',
            'title' => 'Simulasi Direset',
            'text'  => 'Isian simulasi pada mode pratinjau telah dibersihkan. Tidak ada data yang disimpan ke hasil survei.',
        ]);

        return redirect()->to(base_url('graduate/pertanyaan_tracer_studi/pratinjau'));
    }

    /**
     * Halaman pemantauan status pengisian Tracer Studi Awal mahasiswa terplot (B-14).
     */
    public function pantauPengisian()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        session()->set('active_side', 'graduate');

        $db                = $this->jawabanModel->db ?? \Config\Database::connect();
        $daftarSemester    = [];
        $kodeSemesterAktif = '';

        try {
            $db->resetDataCache();
            if ($db->tableExists('semester_graduate')) {
                $semModel          = new SemesterGraduateModel($db);
                $daftarSemester    = $semModel->orderBy('kode', 'DESC')->findAll();
                $semAktifObj       = $semModel->getActiveSemester();
                $kodeSemesterAktif = ! empty($semAktifObj['kode']) ? (string) $semAktifObj['kode'] : '';
            }
        } catch (\Throwable $e) {
            $daftarSemester    = [];
            $kodeSemesterAktif = '';
        }

        $request     = $this->currentRequest();
        $rawSemester = $request->getGet('semester');
        if ($rawSemester === null) {
            $semesterAktif = $kodeSemesterAktif !== '' ? $kodeSemesterAktif : 'semua';
        } else {
            $semesterAktif = trim((string) $rawSemester);
            if ($semesterAktif === '' || strtolower($semesterAktif) === 'semua') {
                $semesterAktif = 'semua';
            }
        }

        $prodiScope      = $this->resolveProdiScope($context['user'], $context['roles']);
        $selectedProdiId = null;
        $daftarProdi     = [];

        $filters = [
            'q'        => trim((string) $request->getGet('q')),
            'status'   => strtolower(trim((string) $request->getGet('status'))),
            'semester' => $semesterAktif,
        ];
        if ($prodiScope['isScoped']) {
            $filters['id_prodi'] = $prodiScope['idProdi'] ?? -1;
        } else {
            $daftarProdi     = $this->fetchProgramStudiRows();
            $selectedProdiId = $this->parsePositiveInt($request->getGet('prodi'));
            if ($selectedProdiId !== null) {
                $filters['id_prodi'] = $selectedProdiId;
            }
        }

        $monitoring = $this->jawabanModel->getMonitoringTerplot($filters);
        $knownCodes = array_column($daftarSemester, 'kode');
        $addedExtra = false;
        foreach ($monitoring['semesterOptions'] as $plottedKode) {
            $plottedKode = (string) $plottedKode;
            if ($plottedKode !== '' && ! in_array($plottedKode, $knownCodes, true)) {
                $daftarSemester[] = [
                    'kode'   => $plottedKode,
                    'nama'   => SemesterGraduateModel::formatNama(null, $plottedKode),
                    'status' => '',
                ];
                $knownCodes[] = $plottedKode;
                $addedExtra   = true;
            }
        }
        if ($addedExtra) {
            usort($daftarSemester, static fn(array $a, array $b): int => strcmp((string) ($b['kode'] ?? ''), (string) ($a['kode'] ?? '')));
        }

        return view('The-Graduate-Web/tracer_studi/pantau_status', [
            'title'             => 'Pantau Status Tracer Study Awal',
            'user'              => $context['user'],
            'role'              => $context['roles'],
            'rows'              => $monitoring['rows'],
            'metrics'           => $monitoring['metrics'],
            'semesterOptions'   => $monitoring['semesterOptions'],
            'daftarSemester'    => $daftarSemester,
            'semesterAktif'     => $semesterAktif,
            'kodeSemesterAktif' => $kodeSemesterAktif,
            'tablesReady'       => $monitoring['tablesReady'],
            'filters'           => $filters,
            'scopedProdi'       => $prodiScope,
            'daftarProdi'       => $daftarProdi,
            'selectedProdiId'   => $selectedProdiId,
        ]);
    }

    /**
     * Menampilkan detail jawaban Tracer Studi Awal mahasiswa terplot tertentu (B-14).
     */
    public function detailJawaban(string $npm = '')
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $prodiScope = $this->resolveProdiScope($context['user'], $context['roles']);
        if ($prodiScope['isScoped'] && $prodiScope['idProdi'] === null) {
            return AccessControl::forbiddenResponse(
                $this->currentRequest(),
                'Akun Program Studi belum terhubung ke data program studi yang valid.',
                $context['roles']
            );
        }

        session()->set('active_side', 'graduate');

        $npm     = trim(urldecode($npm));
        $terplot = $npm !== '' ? $this->terplotModel->where('npm', $npm)->first() : null;
        if ($terplot === null) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Data Tidak Ditemukan',
                'text'  => 'Mahasiswa tidak ditemukan dalam daftar SK Yudisium terplot.',
            ]);

            return redirect()->to(base_url('graduate/tracer_studi_pantau'));
        }

        $terplotProdiId = $this->jawabanModel->resolveTerplotProdiId($terplot);
        if ($prodiScope['isScoped'] && $terplotProdiId !== $prodiScope['idProdi']) {
            return AccessControl::forbiddenResponse(
                $this->currentRequest(),
                'Anda tidak memiliki akses ke detail jawaban mahasiswa dari Program Studi lain.',
                $context['roles']
            );
        }

        $jawabanList = $this->jawabanModel->getDetailJawabanByNpm($npm);
        $submittedAt = $this->jawabanModel->getLastSubmittedAt($npm);
        $namaProdi   = $terplotProdiId !== null ? $this->findProdiNameById($terplotProdiId) : null;

        return view('The-Graduate-Web/tracer_studi/detail_jawaban', [
            'title'       => 'Detail Jawaban Tracer Study Awal',
            'user'        => $context['user'],
            'role'        => $context['roles'],
            'terplot'     => $terplot,
            'jawabanList' => $jawabanList,
            'sudahIsi'    => $jawabanList !== [],
            'submittedAt' => $submittedAt,
            'namaProdi'   => $namaProdi,
            'scopedProdi' => $prodiScope,
        ]);
    }

    /**
     * Halaman Bank Pertanyaan Tracer Study untuk Staf/Tendik dan Prodi.
     */
    public function bankPertanyaan()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        session()->set('active_side', 'graduate');

        $request          = $this->currentRequest();
        $filterKategori   = trim((string) $request->getGet('kategori'));
        $filterKeyword    = trim((string) $request->getGet('q'));
        $isBankTableReady = $this->bankModel->isTableReady();
        $isFormTableReady = $this->pertanyaanModel->isTableReady();

        if ($isBankTableReady && $isFormTableReady) {
            $this->pertanyaanModel->syncExclusiveWithBank(
                $this->bankModel,
                $this->resolveCreatorIdentifier($context['user'])
            );
        }

        $bankItems        = $this->bankModel->getDaftarBank($filterKategori, $filterKeyword);
        $kategoriList     = $this->bankModel->getKategoriList();
        $bankErrors       = session()->getFlashdata('bank_errors');
        $editBankErrors   = session()->getFlashdata('edit_bank_errors');
        $editBankId       = (int) (session()->getFlashdata('edit_bank_id') ?? 0);

        return view('The-Graduate-Web/tracer_studi/bank_pertanyaan', [
            'title'            => 'Bank Pertanyaan Tracer Study',
            'user'             => $context['user'],
            'role'             => $context['roles'],
            'bankItems'        => $bankItems,
            'kategoriList'     => $kategoriList,
            'isBankTableReady' => $isBankTableReady,
            'isFormTableReady' => $isFormTableReady,
            'filterKategori'   => $filterKategori,
            'filterKeyword'    => $filterKeyword,
            'bankErrors'       => is_array($bankErrors) ? $bankErrors : [],
            'editBankErrors'   => is_array($editBankErrors) ? $editBankErrors : [],
            'editBankId'       => $editBankId,
        ]);
    }

    /**
     * Menambahkan pertanyaan nonaktif baru ke Bank Pertanyaan Tracer Study.
     */
    public function storeBankPertanyaan()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request = $this->currentRequest();
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'));
        }

        $payload = $this->extractBankPayload($request);
        if ($this->pertanyaanModel->isTextActiveInForm((string) ($payload['teks_pertanyaan'] ?? ''))) {
            $dupMsg = 'Pertanyaan dengan teks yang sama saat ini sudah aktif di Pertanyaan Tracer Study. Nonaktifkan terlebih dahulu jika ingin memindahkannya ke Bank.';
            session()->setFlashdata('bank_errors', [$dupMsg]);
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Sudah Ada di Pertanyaan Aktif',
                'text'  => $dupMsg,
            ]);

            return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'))->withInput();
        }

        $result  = $this->bankModel->createBankPertanyaan(
            $payload,
            $this->resolveCreatorIdentifier($context['user'])
        );

        if (! $result['success']) {
            session()->setFlashdata('bank_errors', $result['errors']);
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Menambah ke Bank Pertanyaan',
                'text'  => implode(' ', $result['errors']),
            ]);

            return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'))->withInput();
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Ditambahkan ke Bank Pertanyaan',
            'text'  => 'Pertanyaan nonaktif baru berhasil ditambahkan ke Bank Pertanyaan Tracer Study.',
        ]);

        return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'));
    }

    /**
     * Memperbarui pertanyaan nonaktif di Bank Pertanyaan Tracer Study.
     */
    public function updateBankPertanyaan(?int $id = null)
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request = $this->currentRequest();
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'));
        }

        $targetId = $this->resolvePertanyaanId($id, $request);
        if ($targetId <= 0) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Mengubah Pertanyaan Bank',
                'text'  => 'ID item Bank Pertanyaan tidak valid.',
            ]);

            return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'));
        }

        $payload = $this->extractBankPayload($request);
        if ($this->pertanyaanModel->isTextActiveInForm((string) ($payload['teks_pertanyaan'] ?? ''))) {
            $dupMsg = 'Teks pertanyaan tersebut saat ini sudah aktif di Pertanyaan Tracer Study.';
            session()->setFlashdata('edit_bank_errors', [$dupMsg]);
            session()->setFlashdata('edit_bank_id', $targetId);
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Sudah Ada di Pertanyaan Aktif',
                'text'  => $dupMsg,
            ]);

            return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'))->withInput();
        }

        $result  = $this->bankModel->updateBankPertanyaan($targetId, $payload);

        if (! $result['success']) {
            if (empty($result['notFound'])) {
                session()->setFlashdata('edit_bank_errors', $result['errors']);
                session()->setFlashdata('edit_bank_id', $targetId);
            }
            session()->setFlashdata('message', [
                'icon'  => ! empty($result['notFound']) ? 'warning' : 'error',
                'title' => ! empty($result['notFound']) ? 'Data Tidak Ditemukan' : 'Gagal Mengubah Template',
                'text'  => implode(' ', $result['errors']),
            ]);

            return ! empty($result['notFound'])
                ? redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'))
                : redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'))->withInput();
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Template Diperbarui',
            'text'  => 'Template pertanyaan di Bank Pertanyaan berhasil diperbarui.',
        ]);

        return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'));
    }

    /**
     * Menghapus template pertanyaan dari Bank Pertanyaan tanpa mengubah form aktif.
     */
    public function deleteBankPertanyaan(?int $id = null)
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request = $this->currentRequest();
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'));
        }

        $targetId = $this->resolvePertanyaanId($id, $request);
        if ($targetId <= 0) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Menghapus Template',
                'text'  => 'ID item Bank Pertanyaan tidak valid.',
            ]);

            return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'));
        }

        $result = $this->bankModel->deleteBankPertanyaan($targetId);
        if (! $result['success']) {
            session()->setFlashdata('message', [
                'icon'  => ! empty($result['notFound']) ? 'warning' : 'error',
                'title' => ! empty($result['notFound']) ? 'Data Tidak Ditemukan' : 'Gagal Menghapus Template',
                'text'  => (string) ($result['error'] ?? 'Gagal menghapus item dari Bank Pertanyaan.'),
            ]);

            return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'));
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Template Dihapus',
            'text'  => 'Item berhasil dihapus dari Bank Pertanyaan. Pertanyaan yang sudah ada di form aktif maupun jawaban mahasiswa tetap aman.',
        ]);

        return redirect()->to(base_url('graduate/bank_pertanyaan_tracer_studi'));
    }

    /**
     * Mengambil satu atau banyak pertanyaan dari Bank Pertanyaan ke form Tracer Study Awal aktif.
     */
    public function ambilDariBank()
    {
        $context = $this->resolveManagerContext();
        if (! is_array($context)) {
            return $context;
        }

        $request   = $this->currentRequest();
        $returnUrl = $this->resolveBankReturnUrl($request);
        if (strtolower($request->getMethod()) !== 'post') {
            return redirect()->to($returnUrl);
        }

        if (! $this->bankModel->isTableReady()) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Migrasi Belum Dijalankan',
                'text'  => 'Tabel Bank Pertanyaan belum tersedia. Jalankan migrasi terlebih dahulu.',
            ]);

            return redirect()->to($returnUrl);
        }

        $bankIds    = $request->getPost('bank_ids');
        $singleId   = (int) $request->getPost('bank_id');
        $selectedId = is_array($bankIds) ? $bankIds : [];
        if ($singleId > 0) {
            $selectedId[] = $singleId;
        }

        $bankRows = $this->bankModel->getByIds($selectedId);
        if ($bankRows === []) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Belum Ada Pilihan',
                'text'  => 'Pilih minimal satu pertanyaan yang valid dari Bank Pertanyaan untuk dimasukkan ke form.',
            ]);

            return redirect()->to($returnUrl);
        }

        $result = $this->pertanyaanModel->activateFromBank($bankRows, $this->bankModel, [
            'override_wajib' => $request->getPost('override_wajib'),
        ]);

        if (! $result['success']) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Mengaktifkan Pertanyaan',
                'text'  => $result['errors'] !== []
                    ? implode(' ', $result['errors'])
                    : 'Gagal memindahkan pertanyaan dari Bank Pertanyaan ke daftar aktif.',
            ]);

            return redirect()->to($returnUrl);
        }

        $importedCount = (int) ($result['importedCount'] ?? 0);
        $skippedCount  = (int) ($result['skippedCount'] ?? 0);

        if ($importedCount > 0) {
            $text = sprintf(
                '%d pertanyaan berhasil diaktifkan ke daftar Pertanyaan Tracer Study dan dipindahkan dari Bank Pertanyaan.',
                $importedCount
            );
            if ($skippedCount > 0) {
                $text .= sprintf(' %d pertanyaan lainnya dilewati karena sudah aktif di form.', $skippedCount);
            }
            session()->setFlashdata('message', [
                'icon'  => 'success',
                'title' => 'Pertanyaan Diaktifkan dari Bank',
                'text'  => $text,
            ]);
        } else {
            session()->setFlashdata('message', [
                'icon'  => 'info',
                'title' => 'Sudah Ada di Form Aktif',
                'text'  => sprintf('Semua pertanyaan yang dipilih (%d item) sudah aktif di daftar Pertanyaan Tracer Study.', $skippedCount),
            ]);
        }

        return redirect()->to($returnUrl);
    }

    /**
     * Memindahkan satu pertanyaan dari form ke Bank Pertanyaan sebagai item nonaktif.
     */
    public function simpanKeBank(?int $id = null)
    {
        return $this->nonaktifkanKeBank($id);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractBankPayload(RequestInterface $request): array
    {
        return [
            'kategori'         => $request->getPost('kategori'),
            'teks_pertanyaan'  => $request->getPost('teks_pertanyaan'),
            'tipe_jawaban'     => $request->getPost('tipe_jawaban'),
            'opsi_jawaban'     => $request->getPost('opsi_jawaban'),
            'is_wajib_default' => $request->getPost('is_wajib_default') ?? $request->getPost('is_wajib') ?? '1',
        ];
    }

    protected function resolveBankReturnUrl(RequestInterface $request): string
    {
        $returnTo = strtolower(trim((string) ($request->getPost('return_to') ?? '')));
        if ($returnTo === 'bank') {
            return base_url('graduate/bank_pertanyaan_tracer_studi');
        }

        return base_url('graduate/pertanyaan_tracer_studi');
    }

    /**
     * @param array<string, mixed> $user
     */
    protected function resolveCreatorIdentifier(array $user): ?string
    {
        foreach (['nama', 'name', 'username', 'email', 'id'] as $key) {
            $val = trim((string) ($user[$key] ?? ''));
            if ($val !== '') {
                return mb_substr($val, 0, 100);
            }
        }

        return null;
    }

    /**
     * Validasi sesi & peran Staf/Prodi untuk manajemen pertanyaan (B-10 & B-11).
     *
     * @return array{user: array<string, mixed>, roles: string[]}|mixed
     */
    protected function resolveManagerContext()
    {
        $userData = session()->get('user_session');

        if (! is_array($userData) || $userData === []) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);

        if (! PortalRouter::canUseGraduate($roles) || ! self::canViewPertanyaan($roles)) {
            return AccessControl::forbiddenResponse($this->currentRequest(), 'Akses ditolak', $roles);
        }

        return [
            'user'  => $userData,
            'roles' => $roles,
        ];
    }

    /**
     * Menentukan cakupan program studi untuk pemantauan Tracer Study Awal.
     *
     * Admin/Tendik/Staf memantau semua prodi, sedangkan Prodi/Kaprodi hanya
     * dapat memantau mahasiswa dari program studinya sendiri (fail-closed bila
     * id_prodi pengguna tidak dapat dipetakan).
     *
     * @param array<string, mixed> $user
     * @param string[]             $roles
     *
     * @return array{isScoped: bool, idProdi: ?int, prodiName: ?string}
     */
    protected function resolveProdiScope(array $user, array $roles): array
    {
        if (AccessControl::isSuperAdmin($roles) || AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'fakultas'])) {
            return [
                'isScoped'  => false,
                'idProdi'   => null,
                'prodiName' => null,
            ];
        }

        if (! AccessControl::hasAnyRole($roles, ['prodi', 'kaprodi'])) {
            return [
                'isScoped'  => true,
                'idProdi'   => null,
                'prodiName' => null,
            ];
        }

        $resolved = $this->resolveUserProdiRecord($user);

        return [
            'isScoped'  => true,
            'idProdi'   => $resolved['id'] ?? null,
            'prodiName' => $resolved['nama'] ?? null,
        ];
    }

    /**
     * Memetakan session pengguna prodi/kaprodi ke baris program_studi.
     *
     * @param array<string, mixed> $user
     *
     * @return array{id: int, nama: ?string}|null
     */
    private function resolveUserProdiRecord(array $user): ?array
    {
        $prodiRows = $this->fetchProgramStudiRows();
        $byId      = [];
        foreach ($prodiRows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $byId[$id] = $row;
            }
        }

        $explicitId = $this->parsePositiveInt($user['id_prodi'] ?? null);
        if ($explicitId !== null) {
            return [
                'id'   => $explicitId,
                'nama' => isset($byId[$explicitId]['nama']) ? (string) $byId[$explicitId]['nama'] : null,
            ];
        }

        $email = strtolower(trim((string) ($user['email'] ?? '')));
        if ($email !== '') {
            $matched = $this->findProdiRowByField($prodiRows, 'email', $email);
            if ($matched !== null) {
                return $matched;
            }
        }

        $prodiHint = strtolower(trim((string) ($user['prodi'] ?? '')));
        if ($prodiHint !== '' && $prodiHint !== '-') {
            $matched = $this->findProdiRowByField($prodiRows, 'inisial', $prodiHint)
                ?? $this->findProdiRowByField($prodiRows, 'nama', $prodiHint);
            if ($matched !== null) {
                return $matched;
            }
        }

        $sessionId = $this->parsePositiveInt($user['id'] ?? null);
        if ($sessionId !== null && isset($byId[$sessionId])) {
            return [
                'id'   => $sessionId,
                'nama' => isset($byId[$sessionId]['nama']) ? (string) $byId[$sessionId]['nama'] : null,
            ];
        }

        return $this->resolveProdiViaDosenEmail($email, $byId);
    }

    /**
     * Mencari baris program studi berdasarkan kecocokan kolom teks (case-insensitive).
     *
     * @param array<int, array<string, mixed>> $prodiRows
     *
     * @return array{id: int, nama: ?string}|null
     */
    private function findProdiRowByField(array $prodiRows, string $field, string $needleLower): ?array
    {
        foreach ($prodiRows as $row) {
            $id  = (int) ($row['id'] ?? 0);
            $val = strtolower(trim((string) ($row[$field] ?? '')));
            if ($id > 0 && $val !== '' && $val === $needleLower) {
                return [
                    'id'   => $id,
                    'nama' => isset($row['nama']) ? (string) $row['nama'] : null,
                ];
            }
        }

        return null;
    }

    /**
     * Fallback lookup id_prodi melalui tabel dosen berdasarkan email login.
     *
     * @param array<int, array<string, mixed>> $prodiById
     *
     * @return array{id: int, nama: ?string}|null
     */
    private function resolveProdiViaDosenEmail(string $emailLower, array $prodiById): ?array
    {
        if ($emailLower === '') {
            return null;
        }

        try {
            $db = $this->jawabanModel->db ?? \Config\Database::connect();
            if (! $db->tableExists('dosen')) {
                return null;
            }

            $fields = array_map('strtolower', $db->getFieldNames('dosen'));
            if (! in_array('email', $fields, true) || ! in_array('id_prodi', $fields, true)) {
                return null;
            }

            $dosenRow = $db->table('dosen')
                ->select('id_prodi')
                ->where('LOWER(email)', $emailLower)
                ->get()
                ->getRowArray();

            $prodiId = $this->parsePositiveInt($dosenRow['id_prodi'] ?? null);
            if ($prodiId === null) {
                return null;
            }

            return [
                'id'   => $prodiId,
                'nama' => isset($prodiById[$prodiId]['nama']) ? (string) $prodiById[$prodiId]['nama'] : null,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Mengambil nama program studi dari tabel program_studi bila tersedia.
     */
    private function findProdiNameById(int $idProdi): ?string
    {
        if ($idProdi <= 0) {
            return null;
        }

        foreach ($this->fetchProgramStudiRows() as $row) {
            if ((int) ($row['id'] ?? 0) === $idProdi) {
                $nama = trim((string) ($row['nama'] ?? ''));

                return $nama !== '' ? $nama : null;
            }
        }

        return null;
    }

    /**
     * Mengambil daftar program studi dengan aman jika tabel belum tersedia.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchProgramStudiRows(): array
    {
        try {
            $db = $this->jawabanModel->db ?? \Config\Database::connect();
            if (! $db->tableExists('program_studi')) {
                return [];
            }

            return $db->table('program_studi')->orderBy('id', 'ASC')->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Mengonversi nilai scalar menjadi bilangan bulat positif atau null.
     *
     * @param mixed $value
     */
    private function parsePositiveInt($value): ?int
    {
        if (! is_scalar($value)) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '' || ! ctype_digit($trimmed)) {
            return null;
        }

        $parsed = (int) $trimmed;

        return $parsed > 0 ? $parsed : null;
    }

    /**
     * Menampilkan daftar & form pertanyaan Tracer Studi Awal untuk mahasiswa terplot.
     */
    public function formMahasiswa()
    {
        $context = $this->resolvePlottedMahasiswaContext(false);
        if (! is_array($context)) {
            return $context;
        }

        session()->set('active_side', 'graduate');

        $npm              = $context['npm'];
        $isTableReady     = $this->pertanyaanModel->isTableReady() && $this->jawabanModel->isTableReady();
        $daftarPertanyaan = $this->pertanyaanModel->getDaftarPertanyaan();
        $jawabanMap       = $this->jawabanModel->getMapByNpm($npm);
        $sudahDiisi       = $this->jawabanModel->hasCompletedByNpm($npm);
        $lastSubmittedAt  = $this->jawabanModel->getLastSubmittedAt($npm);
        $tracerErrors     = session()->getFlashdata('tracer_errors');

        return view('The-Graduate-Web/mahasiswa/tracer_studi', [
            'title'            => 'Tracer Study Awal',
            'user'             => $context['user'],
            'role'             => $context['roles'],
            'terplot'          => $context['terplot'],
            'daftarPertanyaan' => $daftarPertanyaan,
            'jawabanMap'       => $jawabanMap,
            'sudahDiisi'       => $sudahDiisi,
            'lastSubmittedAt'  => $lastSubmittedAt,
            'isTableReady'     => $isTableReady,
            'tracerErrors'     => is_array($tracerErrors) ? $tracerErrors : [],
        ]);
    }

    /**
     * Menyimpan jawaban Tracer Studi Awal mahasiswa terplot sebelum mengisi biodata wisuda.
     */
    public function submitMahasiswa()
    {
        $context = $this->resolvePlottedMahasiswaContext(true);
        if (! is_array($context)) {
            return $context;
        }

        if (! $this->jawabanModel->isTableReady()) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Tabel Belum Siap',
                'text'  => 'Tabel jawaban Tracer Study Awal belum tersedia. Silakan hubungi administrator untuk menjalankan migrasi database.',
            ]);
            return redirect()->to(base_url('graduate/tracer_studi'));
        }

        $daftarPertanyaan = $this->pertanyaanModel->getDaftarPertanyaan();
        if ($daftarPertanyaan === []) {
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Daftar Pertanyaan Kosong',
                'text'  => 'Belum ada pertanyaan Tracer Study Awal aktif saat ini.',
            ]);
            return redirect()->to(base_url('graduate/tracer_studi'));
        }

        $rawJawaban = $this->currentRequest()->getPost('jawaban');
        if (! is_array($rawJawaban)) {
            $rawJawaban = [];
        }

        $validation = JawabanTracerStudiAwalModel::validateJawabanSet($daftarPertanyaan, $rawJawaban);
        if ($validation['errors'] !== []) {
            session()->setFlashdata('tracer_errors', $validation['errors']);
            session()->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Isian Belum Lengkap',
                'text'  => 'Mohon lengkapi seluruh pertanyaan wajib Tracer Study Awal dengan pilihan yang valid.',
            ]);
            return redirect()->to(base_url('graduate/tracer_studi'))->withInput();
        }

        try {
            $kodeSemester = $context['terplot']['kode_semester'] ?? null;
            $this->jawabanModel->saveJawabanMahasiswa(
                $context['npm'],
                $daftarPertanyaan,
                $validation['answers'],
                is_string($kodeSemester) && $kodeSemester !== '' ? $kodeSemester : null
            );
        } catch (\Throwable $e) {
            log_message('error', '[TracerStudiController::submitMahasiswa] ' . $e->getMessage());
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Gagal Menyimpan',
                'text'  => 'Terjadi kesalahan saat menyimpan jawaban Tracer Study Awal. Silakan coba kembali.',
            ]);
            return redirect()->to(base_url('graduate/tracer_studi'))->withInput();
        }

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Tracer Study Awal Tersimpan',
            'text'  => 'Terima kasih. Jawaban Tracer Study Awal telah tersimpan. Silakan lanjutkan pengisian Biodata Mahasiswa.',
        ]);

        return redirect()->to(base_url('graduate/biodata_wisuda'));
    }

    /**
     * Hanya Staf/Tendik/Admin dan Prodi/Kaprodi yang diperbolehkan melihat daftar pertanyaan.
     *
     * @param string[] $roles
     */
    public static function canViewPertanyaan(array $roles): bool
    {
        if (AccessControl::isSuperAdmin($roles)) {
            return true;
        }

        return AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'fakultas', 'prodi', 'kaprodi']);
    }

    /**
     * Hanya peran Mahasiswa yang diperbolehkan mengisi form Tracer Studi Awal mahasiswa.
     *
     * @param string[] $roles
     */
    public static function canFillTracer(array $roles): bool
    {
        return in_array('mahasiswa', $roles, true);
    }

    protected function currentRequest(): RequestInterface
    {
        return $this->request ?? \Config\Services::request();
    }

    /**
     * Validasi sesi, peran mahasiswa, dan status plotting SK Yudisium (B-08b).
     *
     * @return array{user: array<string, mixed>, roles: string[], npm: string, terplot: array<string, mixed>}|mixed
     */
    protected function resolvePlottedMahasiswaContext(bool $isPost)
    {
        $userData = session()->get('user_session');

        if (! is_array($userData) || $userData === []) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! PortalRouter::canUseGraduate($roles) || ! self::canFillTracer($roles)) {
            return AccessControl::forbiddenResponse($this->currentRequest(), 'Akses ditolak', $roles);
        }

        $npm     = trim((string) ($userData['npm'] ?? $userData['id'] ?? ''));
        $terplot = $npm !== '' ? $this->terplotModel->where('npm', $npm)->first() : null;

        if ($terplot === null) {
            if ($isPost) {
                session()->setFlashdata('message', [
                    'icon'  => 'error',
                    'title' => 'Akses Ditolak',
                    'text'  => 'Anda belum terdaftar dalam SK Yudisium, sehingga belum dapat mengisi Tracer Study Awal.',
                ]);

                return redirect()->to(base_url('graduate/tracer_studi'));
            }

            session()->set('active_side', 'graduate');

            return view('The-Graduate-Web/mahasiswa/belum_terplot', [
                'title' => 'Akses Ditolak',
                'user'  => $userData,
                'role'  => $roles,
            ]);
        }

        return [
            'user'    => $userData,
            'roles'   => $roles,
            'npm'     => $npm,
            'terplot' => $terplot,
        ];
    }
}
