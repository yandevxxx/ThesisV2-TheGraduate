<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<?php
use App\Models\PertanyaanTracerStudiAwalModel;

$daftarPertanyaan     = $daftarPertanyaan ?? [];
$isTableReady         = $isTableReady ?? true;
$isBankTableReady     = $isBankTableReady ?? false;
$editPertanyaanErrors = $editPertanyaanErrors ?? [];
$editPertanyaanId     = (int) ($editPertanyaanId ?? 0);

$flashMessage = session()->getFlashdata('message');
$flashIcon    = is_array($flashMessage) ? ($flashMessage['icon'] ?? 'info') : '';
$flashTitle   = is_array($flashMessage) ? ($flashMessage['title'] ?? '') : '';
$flashText    = is_array($flashMessage) ? ($flashMessage['text'] ?? '') : '';

$hasEditErrors = $editPertanyaanErrors !== [];

$editOldId     = $hasEditErrors ? (int) (old('id') ?? $editPertanyaanId) : 0;
$editOldTeks   = $hasEditErrors ? (string) (old('teks_pertanyaan') ?? '') : '';
$editOldTipe   = $hasEditErrors ? strtoupper((string) (old('tipe_jawaban') ?? 'TEKS')) : 'TEKS';
$editOldOpsi   = $hasEditErrors ? (string) (old('opsi_jawaban') ?? '') : '';
$editOldUrutan = $hasEditErrors ? (string) (old('urutan') ?? '') : '';
$editOldWajib  = $hasEditErrors ? (string) (old('is_wajib') ?? '1') : '1';
$editOldStatus = $hasEditErrors ? strtoupper((string) (old('status') ?? 'AKTIF')) : 'AKTIF';

$totalAktif = 0;
foreach ($daftarPertanyaan as $row) {
    if (strtoupper((string) ($row['status'] ?? 'AKTIF')) === 'AKTIF') {
        $totalAktif++;
    }
}
?>

<?php if ($flashText !== '') : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= esc($flashIcon) ?></p>
        <p id="title" hidden><?= esc($flashTitle) ?></p>
        <p id="text" hidden><?= esc($flashText) ?></p>
    </div>
    <?php
    $alertClass = match ($flashIcon) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };
    ?>
    <div class="alert <?= $alertClass ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php if ($flashTitle !== '') : ?>
            <strong><?= esc($flashTitle) ?>:</strong>
        <?php endif; ?>
        <?= esc($flashText) ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<!-- Begin of Content -->
<div class="mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-clipboard-list text-primary mr-2"></i>Daftar Pertanyaan Tracer Study Awal
    </h1>
</div>

<div class="alert alert-info border-left-info shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-center">
        <i class="fas fa-info-circle fa-lg text-info mr-3"></i>
        <div>
            <strong>Daftar Pertanyaan Form Tracer Study Awal:</strong>
            <?php if ($isBankTableReady) : ?>
                Halaman ini khusus menampilkan pertanyaan yang sedang <strong>aktif</strong> di formulir mahasiswa (sama untuk semua Program Studi). Pertanyaan yang dinonaktifkan otomatis dipindahkan ke <strong>Bank Pertanyaan</strong>.
            <?php else : ?>
                Pertanyaan berikut <strong>sama untuk semua Program Studi</strong> dan ditampilkan sesuai urutan serta status tampil pada formulir yang diisi mahasiswa menjelang kelulusan.
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (! $isTableReady) : ?>
    <div class="alert alert-warning shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Tabel database <code>pertanyaan_tracer_studi_awal</code> belum dimigrasi. Menampilkan daftar pertanyaan standar awal (penambahan pertanyaan baru dinonaktifkan hingga migrasi dijalankan).
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <form action="<?= base_url('graduate/pertanyaan_tracer_studi/tampil') ?>" method="post" id="formPengaturanTampil">
        <?= csrf_field() ?>
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary mr-3 my-1">
                <i class="fas fa-list-ol mr-1"></i> Daftar Pertanyaan &amp; Pengaturan Tampil Form Tracer Study Awal
            </h6>
            <div class="d-flex flex-wrap align-items-center my-1">
                <a href="<?= base_url('graduate/bank_pertanyaan_tracer_studi') ?>"
                    class="btn btn-outline-primary btn-sm shadow-sm mr-2 mb-1 mb-sm-0 font-weight-bold"
                    title="Kelola Bank Pertanyaan Tracer Study">
                    <i class="fas fa-layer-group fa-sm mr-1"></i> Bank Pertanyaan
                </a>
                <a href="<?= base_url('graduate/pertanyaan_tracer_studi/pratinjau') ?>"
                    class="btn btn-info btn-sm shadow-sm mb-1 mb-sm-0 font-weight-bold"
                    title="Lihat pratinjau formulir seperti yang tampil ke mahasiswa">
                    <i class="fas fa-eye fa-sm mr-1"></i> Pratinjau
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th style="width: 10%;">
                                <?php if ($isBankTableReady) : ?>
                                    <span class="font-weight-bold" title="Matikan toggle untuk menonaktifkan pertanyaan dan memindahkannya langsung ke Bank Pertanyaan">Aktif</span>
                                <?php else : ?>
                                    <div class="custom-control custom-checkbox d-inline-block">
                                        <input type="checkbox" class="custom-control-input" id="checkAllTampil" <?= (! empty($daftarPertanyaan) && $totalAktif === count($daftarPertanyaan)) ? 'checked' : '' ?>>
                                        <label class="custom-control-label font-weight-bold" for="checkAllTampil">Tampil</label>
                                    </div>
                                <?php endif; ?>
                            </th>
                            <th style="width: 11%;" title="Ubah nomor atau seret baris untuk mengatur urutan">Urutan</th>
                            <th>Teks Pertanyaan</th>
                            <th style="width: 12%;">Tipe Jawaban</th>
                            <th style="width: 15%;">Sifat (Wajib Diisi)</th>
                            <th style="width: 11%;">Status Tampil</th>
                            <th style="width: 13%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyPertanyaanTracer">
                        <?php if (empty($daftarPertanyaan)) : ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Belum ada pertanyaan tracer study awal yang terdaftar.
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($daftarPertanyaan as $index => $item) : ?>
                                <?php
                                $qid          = (int) ($item['id'] ?? ($index + 1));
                                $urutan       = (int) ($item['urutan'] ?? ($index + 1));
                                $teks         = (string) ($item['teks_pertanyaan'] ?? '');
                                $tipeRaw      = strtoupper(trim((string) ($item['tipe_jawaban'] ?? 'TEKS')));
                                $tipeLabel    = PertanyaanTracerStudiAwalModel::formatTipeJawaban($tipeRaw);
                                $opsiList     = PertanyaanTracerStudiAwalModel::parseOpsiJawaban($item['opsi_jawaban'] ?? null);
                                $opsiJsonAttr = json_encode($opsiList, JSON_UNESCAPED_UNICODE) ?: '[]';
                                $isWajib      = (int) ($item['is_wajib'] ?? 1) === 1;
                                $statusItem   = strtoupper((string) ($item['status'] ?? 'AKTIF'));
                                $isAktif      = $statusItem === 'AKTIF';
                                ?>
                                <tr class="sortable-pertanyaan-row"
                                    data-id="<?= $qid ?>"
                                    draggable="<?= ($isTableReady && ! empty($daftarPertanyaan)) ? 'true' : 'false' ?>">
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-switch d-inline-block">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input checkbox-tampil-pertanyaan"
                                                id="tampil_<?= $qid ?>"
                                                name="tampil_ids[]"
                                                value="<?= $qid ?>"
                                                data-id="<?= $qid ?>"
                                                <?= $isAktif ? 'checked' : '' ?>
                                                <?= ! $isTableReady ? 'disabled' : '' ?>
                                            >
                                            <label class="custom-control-label" for="tampil_<?= $qid ?>" title="<?= $isBankTableReady ? 'Matikan toggle untuk menonaktifkan dan memindahkan pertanyaan ke Bank Pertanyaan' : 'Centang untuk menampilkan di form tracer study awal' ?>"></label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle text-nowrap">
                                        <div class="d-inline-flex align-items-center justify-content-center">
                                            <span class="drag-handle text-muted mr-2" title="Seret baris untuk mengubah urutan" style="cursor: grab;">
                                                <i class="fas fa-grip-vertical"></i>
                                            </span>
                                            <input
                                                type="number"
                                                min="1"
                                                step="1"
                                                class="form-control form-control-sm text-center font-weight-bold input-urutan-pertanyaan"
                                                style="width: 64px;"
                                                name="urutan_map[<?= $qid ?>]"
                                                value="<?= $urutan ?>"
                                                data-id="<?= $qid ?>"
                                                aria-label="Nomor urut pertanyaan <?= $qid ?>"
                                                <?= ! $isTableReady ? 'disabled' : '' ?>
                                            >
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold text-gray-800"><?= esc($teks) ?></div>
                                        <?php if ($opsiList !== []) : ?>
                                            <div class="mt-2 small text-muted">
                                                <span class="font-weight-bold text-gray-600">Pilihan / Skala:</span>
                                                <ul class="mb-0 pl-3 mt-1">
                                                    <?php foreach ($opsiList as $opsi) : ?>
                                                        <li><?= esc($opsi) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-light border text-gray-700 px-2 py-1"><?= esc($tipeLabel) ?></span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-switch d-inline-block">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input switch-wajib-pertanyaan"
                                                id="wajib_<?= $qid ?>"
                                                data-id="<?= $qid ?>"
                                                data-next-wajib="<?= $isWajib ? '0' : '1' ?>"
                                                <?= $isWajib ? 'checked' : '' ?>
                                                <?= ! $isTableReady ? 'disabled' : '' ?>
                                            >
                                            <label class="custom-control-label" for="wajib_<?= $qid ?>" title="Klik untuk mengaktifkan atau mematikan Wajib Diisi">
                                                <?php if ($isWajib) : ?>
                                                    <span class="badge badge-danger px-2 py-1">Wajib</span>
                                                <?php else : ?>
                                                    <span class="badge badge-secondary px-2 py-1">Opsional</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle status-tampil-cell">
                                        <?php if ($isAktif) : ?>
                                            <span class="badge badge-success px-2 py-1">Tampil (Aktif)</span>
                                        <?php else : ?>
                                            <span class="badge badge-secondary px-2 py-1">Disembunyikan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle text-nowrap">
                                        <button
                                            type="button"
                                            class="btn btn-warning btn-sm btn-edit-pertanyaan font-weight-bold"
                                            data-toggle="modal"
                                            data-target="#modalEditPertanyaan"
                                            data-id="<?= $qid ?>"
                                            data-teks="<?= esc($teks, 'attr') ?>"
                                            data-tipe="<?= esc($tipeRaw, 'attr') ?>"
                                            data-opsi="<?= esc($opsiJsonAttr, 'attr') ?>"
                                            data-urutan="<?= $urutan ?>"
                                            data-wajib="<?= $isWajib ? '1' : '0' ?>"
                                            data-status="<?= esc($statusItem, 'attr') ?>"
                                            title="Ubah pertanyaan"
                                            <?= ! $isTableReady ? 'disabled' : '' ?>>
                                            <i class="fas fa-edit fa-fw"></i> Ubah
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm btn-hapus-pertanyaan ml-1"
                                            data-toggle="modal"
                                            data-target="#modalHapusPertanyaan"
                                            data-id="<?= $qid ?>"
                                            data-urutan="<?= $urutan ?>"
                                            data-teks="<?= esc($teks, 'attr') ?>"
                                            title="Hapus pertanyaan"
                                            <?= ! $isTableReady ? 'disabled' : '' ?>>
                                            <i class="fas fa-trash fa-fw"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex flex-column flex-md-row align-items-md-center justify-content-between py-3">
            <small class="text-muted mb-2 mb-md-0 mr-md-3">
                <i class="fas fa-info-circle mr-1"></i>
                <?= $isBankTableReady
                    ? 'Seret baris atau ubah nomor urut lalu tekan Simpan. Mematikan toggle Aktif akan langsung menonaktifkan pertanyaan dan memindahkannya ke Bank Pertanyaan tanpa menghapus jawaban mahasiswa.'
                    : 'Seret baris atau ubah nomor urut untuk mengatur urutan pertanyaan, lalu tekan Simpan. Jawaban mahasiswa yang sudah tersimpan tidak berubah.' ?>
            </small>
            <div class="d-flex flex-wrap align-items-center">
                <button type="submit"
                    id="btnSimpanUrutan"
                    formaction="<?= base_url('graduate/pertanyaan_tracer_studi/urutan') ?>"
                    class="btn btn-outline-primary btn-sm font-weight-bold shadow-sm px-3 mr-2 mb-1 mb-sm-0"
                    <?= (! $isTableReady || empty($daftarPertanyaan)) ? 'disabled' : '' ?>>
                    <i class="fas fa-sort-numeric-down mr-1"></i> Simpan Urutan
                </button>
                <button type="submit"
                    id="btnSimpanPengaturanTampil"
                    class="btn btn-success btn-sm font-weight-bold shadow-sm px-3"
                    <?= (! $isTableReady || empty($daftarPertanyaan)) ? 'disabled' : '' ?>>
                    <i class="fas fa-check-circle mr-1"></i> Simpan Pengaturan Tampil &amp; Urutan
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Form Toggle Sifat Wajib Diisi Per Pertanyaan (B-17) -->
<form action="<?= base_url('graduate/pertanyaan_tracer_studi/toggle_wajib') ?>" method="post" id="formToggleWajib" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="toggleWajibId" value="">
    <input type="hidden" name="is_wajib" id="toggleWajibValue" value="">
</form>

<!-- Form Nonaktifkan Pertanyaan dan Pindahkan ke Bank Pertanyaan -->
<form action="<?= base_url('graduate/pertanyaan_tracer_studi/nonaktifkan') ?>" method="post" id="formNonaktifkanKeBank" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="nonaktifkanKeBankId" value="">
</form>

<!-- Modal Ubah Pertanyaan Tracer Study Awal (B-15) -->
<div class="modal fade" id="modalEditPertanyaan" tabindex="-1" role="dialog" aria-labelledby="modalEditPertanyaanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-warning" id="modalEditPertanyaanLabel">
                    <i class="fas fa-edit mr-2"></i>Ubah Pertanyaan Tracer Study Awal
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/pertanyaan_tracer_studi/update') ?>" method="post" id="formEditPertanyaan" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="editId" value="<?= $editOldId > 0 ? $editOldId : '' ?>">
                <div class="modal-body">
                    <?php if ($hasEditErrors) : ?>
                        <div class="alert alert-danger edit-error-alert" role="alert">
                            <strong><i class="fas fa-exclamation-circle mr-1"></i>Periksa Kembali Isian:</strong>
                            <ul class="mb-0 pl-3 mt-1">
                                <?php foreach ($editPertanyaanErrors as $errMsg) : ?>
                                    <li><?= esc($errMsg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-light border small text-muted mb-3">
                        <i class="fas fa-shield-alt mr-1 text-primary"></i>
                        Perubahan berlaku pada form tracer study awal berikutnya; jawaban mahasiswa yang sudah tersimpan tetap utuh di halaman pemantauan.
                    </div>

                    <div class="form-group">
                        <label for="editTeksPertanyaan" class="font-weight-bold">
                            Teks Pertanyaan <span class="text-danger">*</span>
                        </label>
                        <textarea
                            id="editTeksPertanyaan"
                            name="teks_pertanyaan"
                            rows="3"
                            class="form-control"
                            placeholder="Masukkan teks pertanyaan tracer study awal..."
                        ><?= esc($editOldTeks) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="editTipeJawaban" class="font-weight-bold">
                            Tipe Jawaban <span class="text-danger">*</span>
                        </label>
                        <select id="editTipeJawaban" name="tipe_jawaban" class="form-control">
                            <option value="TEKS" <?= $editOldTipe === 'TEKS' ? 'selected' : '' ?>>Uraian Teks (Paragraf)</option>
                            <option value="ISIAN_SINGKAT" <?= $editOldTipe === 'ISIAN_SINGKAT' ? 'selected' : '' ?>>Isian Singkat (Satu Baris)</option>
                            <option value="PILIHAN_GANDA" <?= $editOldTipe === 'PILIHAN_GANDA' ? 'selected' : '' ?>>Pilihan Ganda</option>
                            <option value="SKALA" <?= $editOldTipe === 'SKALA' ? 'selected' : '' ?>>Skala Penilaian (1 - 5)</option>
                        </select>
                    </div>

                    <div class="form-group <?= in_array($editOldTipe, ['PILIHAN_GANDA', 'SKALA'], true) ? '' : 'd-none' ?>" id="editGroupOpsiJawaban">
                        <label for="editOpsiJawaban" class="font-weight-bold">
                            Daftar Pilihan / Skala Jawaban
                            <span class="text-danger <?= $editOldTipe === 'PILIHAN_GANDA' ? '' : 'd-none' ?>" id="editOpsiRequiredMark">*</span>
                        </label>
                        <textarea
                            id="editOpsiJawaban"
                            name="opsi_jawaban"
                            rows="4"
                            class="form-control"
                            placeholder="Masukkan satu pilihan per baris atau pisahkan dengan titik koma (;)"
                        ><?= esc($editOldOpsi) ?></textarea>
                        <small class="form-text text-muted">
                            Untuk Pilihan Ganda wajib minimal 2 pilihan. Untuk Skala Penilaian, jika dikosongkan otomatis memakai skala 1 s.d. 5 standar.
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="editUrutan" class="font-weight-bold">Urutan Tampil</label>
                            <input
                                type="number"
                                id="editUrutan"
                                name="urutan"
                                min="1"
                                class="form-control"
                                value="<?= esc($editOldUrutan) ?>"
                            >
                        </div>
                        <div class="form-group col-md-4">
                            <label for="editIsWajib" class="font-weight-bold">Sifat Pengisian</label>
                            <select id="editIsWajib" name="is_wajib" class="form-control">
                                <option value="1" <?= $editOldWajib !== '0' ? 'selected' : '' ?>>Wajib Diisi</option>
                                <option value="0" <?= $editOldWajib === '0' ? 'selected' : '' ?>>Opsional</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="editStatus" class="font-weight-bold">Pengaturan Tampil (B-12)</label>
                            <select id="editStatus" name="status" class="form-control">
                                <option value="AKTIF" <?= $editOldStatus !== 'NON-AKTIF' ? 'selected' : '' ?>>Tampil (Aktif)</option>
                                <option value="NON-AKTIF" <?= $editOldStatus === 'NON-AKTIF' ? 'selected' : '' ?>><?= $isBankTableReady ? 'Nonaktif (Pindah ke Bank)' : 'Disembunyikan (Non-Aktif)' ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Pertanyaan Tracer Study Awal (B-16) -->
<div class="modal fade" id="modalHapusPertanyaan" tabindex="-1" role="dialog" aria-labelledby="modalHapusPertanyaanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-danger" id="modalHapusPertanyaanLabel">
                    <i class="fas fa-trash mr-2"></i>Konfirmasi Hapus Pertanyaan
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/pertanyaan_tracer_studi/delete') ?>" method="post" id="formHapusPertanyaan">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="hapusId">
                <div class="modal-body">
                    <p class="mb-2">Apakah Anda yakin ingin menghapus pertanyaan nomor <strong>#<span id="hapusUrutan">-</span></strong> berikut dari form tracer study awal?</p>
                    <div class="alert alert-warning py-2 mb-3">
                        <div class="font-weight-bold text-gray-900" id="hapusTeks">-</div>
                    </div>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-info-circle mr-1"></i>
                        Pertanyaan yang dihapus tidak lagi tampil pada daftar pertanyaan maupun formulir mahasiswa, sedangkan jawaban mahasiswa yang sudah tersimpan tetap utuh di halaman pemantauan.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger font-weight-bold">
                        <i class="fas fa-trash fa-fw mr-1"></i> Ya, Hapus Pertanyaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>
<script>
    $(function () {
        var defaultSkalaText = <?= json_encode(implode("\n", PertanyaanTracerStudiAwalModel::defaultSkalaOpsi())) ?>;

        function syncOpsiField($select, $group, $mark, $textarea, allowAutoSkala) {
            var tipe = ($select.val() || '').toUpperCase();

            if (tipe === 'PILIHAN_GANDA' || tipe === 'SKALA') {
                $group.removeClass('d-none');
                if (tipe === 'PILIHAN_GANDA') {
                    $mark.removeClass('d-none');
                    if ($textarea.data('auto-skala')) {
                        $textarea.val('');
                        $textarea.data('auto-skala', false);
                    }
                } else {
                    $mark.addClass('d-none');
                    if (allowAutoSkala && $.trim($textarea.val()) === '') {
                        $textarea.val(defaultSkalaText);
                        $textarea.data('auto-skala', true);
                    }
                }
            } else {
                $group.addClass('d-none');
                $mark.addClass('d-none');
                if ($textarea.data('auto-skala')) {
                    $textarea.val('');
                    $textarea.data('auto-skala', false);
                }
            }
        }

        function bindOpsiForm(selectId, groupId, markId, textareaId) {
            var $select   = $(selectId);
            var $group    = $(groupId);
            var $mark     = $(markId);
            var $textarea = $(textareaId);

            $textarea.on('input', function () {
                $(this).data('auto-skala', false);
            });
            $select.on('change', function () {
                syncOpsiField($select, $group, $mark, $textarea, true);
            });
            syncOpsiField($select, $group, $mark, $textarea, false);
        }

        bindOpsiForm('#editTipeJawaban', '#editGroupOpsiJawaban', '#editOpsiRequiredMark', '#editOpsiJawaban');

        $('#modalEditPertanyaan').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            if (!button || !button.length) {
                syncOpsiField($('#editTipeJawaban'), $('#editGroupOpsiJawaban'), $('#editOpsiRequiredMark'), $('#editOpsiJawaban'), false);
                return;
            }

            var modal = $(this);
            modal.find('.edit-error-alert').addClass('d-none');
            var rawOpsiAttr = button.attr('data-opsi') || '[]';
            var opsiText = '';
            try {
                var parsed = JSON.parse(rawOpsiAttr);
                if (Array.isArray(parsed)) {
                    opsiText = parsed.join("\n");
                }
            } catch (e) {
                opsiText = rawOpsiAttr;
            }

            modal.find('#editId').val(button.attr('data-id') || '');
            modal.find('#editTeksPertanyaan').val(button.attr('data-teks') || '');
            modal.find('#editTipeJawaban').val((button.attr('data-tipe') || 'TEKS').toUpperCase());
            modal.find('#editOpsiJawaban').data('auto-skala', false).val(opsiText);
            modal.find('#editUrutan').val(button.attr('data-urutan') || '');
            modal.find('#editIsWajib').val(String(button.attr('data-wajib') || '1'));
            modal.find('#editStatus').val((button.attr('data-status') || 'AKTIF').toUpperCase());

            syncOpsiField($('#editTipeJawaban'), $('#editGroupOpsiJawaban'), $('#editOpsiRequiredMark'), $('#editOpsiJawaban'), false);
        });

        $('#modalHapusPertanyaan').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            if (!button || !button.length) {
                return;
            }

            var modal = $(this);
            modal.find('#hapusId').val(button.attr('data-id') || '');
            modal.find('#hapusUrutan').text(button.attr('data-urutan') || '-');
            modal.find('#hapusTeks').text(button.attr('data-teks') || '-');
        });

        var isBankTableReady = <?= $isBankTableReady ? 'true' : 'false' ?>;

        function refreshTampilCounters() {
            var $boxes = $('.checkbox-tampil-pertanyaan');
            var checkedCount = $boxes.filter(':checked').length;
            if ($boxes.length > 0 && $('#checkAllTampil').length) {
                $('#checkAllTampil').prop('checked', checkedCount === $boxes.length);
            }
        }

        $(document).on('change', '.checkbox-tampil-pertanyaan', function () {
            var $input    = $(this);
            var isChecked = $input.is(':checked');

            if (isBankTableReady && !isChecked) {
                var qid = $input.attr('data-id') || $input.val() || '';
                if ($('.checkbox-tampil-pertanyaan').length <= 1) {
                    $input.prop('checked', true);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Tidak Dapat Menonaktifkan',
                            text: 'Minimal satu pertanyaan aktif harus tetap berada pada form Tracer Study Awal.',
                            confirmButtonColor: '#1C476C'
                        });
                    } else {
                        alert('Minimal satu pertanyaan aktif harus tetap berada pada form Tracer Study Awal.');
                    }
                    return;
                }

                if (qid) {
                    $input.prop('disabled', true);
                    $('#nonaktifkanKeBankId').val(qid);
                    $('#formNonaktifkanKeBank').trigger('submit');
                    return;
                }
            }

            var $cell = $input.closest('tr').find('.status-tampil-cell');
            if (isChecked) {
                $cell.html('<span class="badge badge-success px-2 py-1">Tampil (Aktif)</span>');
            } else {
                $cell.html('<span class="badge badge-secondary px-2 py-1">Disembunyikan</span>');
            }
            refreshTampilCounters();
        });

        $('#checkAllTampil').on('change', function () {
            var checkAll = $(this).is(':checked');
            $('.checkbox-tampil-pertanyaan:not(:disabled)').prop('checked', checkAll).trigger('change');
        });

        $(document).on('change', '.switch-wajib-pertanyaan', function () {
            var $input = $(this);
            var qid    = $input.attr('data-id') || '';
            var next   = $input.is(':checked') ? '1' : '0';
            if (!qid) {
                return;
            }
            $input.prop('disabled', true);
            $('#toggleWajibId').val(qid);
            $('#toggleWajibValue').val(next);
            $('#formToggleWajib').submit();
        });

        // Drag-and-drop pengurutan baris pertanyaan (B-18)
        var tracerTbody = document.getElementById('tbodyPertanyaanTracer');
        if (tracerTbody) {
            var draggedPertanyaanRow = null;

            function renumberUrutanInputs() {
                var rows = tracerTbody.querySelectorAll('tr.sortable-pertanyaan-row');
                rows.forEach(function (row, idx) {
                    var input = row.querySelector('.input-urutan-pertanyaan');
                    if (input) {
                        input.value = idx + 1;
                    }
                });
            }

            $(tracerTbody).on('mousedown focus', 'input, button, select, textarea, label, .custom-switch', function () {
                $(this).closest('tr.sortable-pertanyaan-row').attr('draggable', 'false');
            });
            $(document).on('mouseup blur', '#tbodyPertanyaanTracer input, #tbodyPertanyaanTracer button, #tbodyPertanyaanTracer label', function () {
                $('#tbodyPertanyaanTracer tr.sortable-pertanyaan-row').attr('draggable', 'true');
            });

            tracerTbody.addEventListener('dragstart', function (e) {
                var row = e.target.closest('tr.sortable-pertanyaan-row');
                if (!row) {
                    return;
                }
                draggedPertanyaanRow = row;
                row.style.opacity = '0.55';
                row.style.backgroundColor = '#eef5ff';
                if (e.dataTransfer) {
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', row.getAttribute('data-id') || '');
                }
            });

            tracerTbody.addEventListener('dragover', function (e) {
                if (!draggedPertanyaanRow) {
                    return;
                }
                e.preventDefault();
                var targetRow = e.target.closest('tr.sortable-pertanyaan-row');
                if (!targetRow || targetRow === draggedPertanyaanRow) {
                    return;
                }
                var rect = targetRow.getBoundingClientRect();
                var afterTarget = (e.clientY - rect.top) > (rect.height / 2);
                tracerTbody.insertBefore(draggedPertanyaanRow, afterTarget ? targetRow.nextSibling : targetRow);
            });

            tracerTbody.addEventListener('dragend', function () {
                if (draggedPertanyaanRow) {
                    draggedPertanyaanRow.style.opacity = '';
                    draggedPertanyaanRow.style.backgroundColor = '';
                    draggedPertanyaanRow = null;
                    renumberUrutanInputs();
                }
            });
        }

        <?php if ($hasEditErrors) : ?>
        $('#modalEditPertanyaan').modal('show');
        <?php endif; ?>

        var flashData = $('#flashdata');
        if (flashData.length && flashData.data('open')) {
            var icon  = $('#icon').text();
            var title = $('#title').text();
            var text  = $('#text').text();
            if (text && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: icon || 'info',
                    title: title || 'Informasi',
                    text: text,
                    confirmButtonColor: '#1C476C'
                });
            }
        }
    });
</script>
<?= $this->endSection(); ?>
