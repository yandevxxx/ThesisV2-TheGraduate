<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<?php
use App\Models\BankPertanyaanTracerStudiModel;
use App\Models\PertanyaanTracerStudiAwalModel;

$bankItems        = $bankItems ?? [];
$kategoriList     = $kategoriList ?? BankPertanyaanTracerStudiModel::PRESET_KATEGORI;
$isBankTableReady = $isBankTableReady ?? true;
$isFormTableReady = $isFormTableReady ?? true;
$filterKategori   = (string) ($filterKategori ?? '');
$filterKeyword    = (string) ($filterKeyword ?? '');
$bankErrors       = $bankErrors ?? [];
$editBankErrors   = $editBankErrors ?? [];
$editBankId       = (int) ($editBankId ?? 0);

$flashMessage = session()->getFlashdata('message');
$flashIcon    = is_array($flashMessage) ? ($flashMessage['icon'] ?? 'info') : '';
$flashTitle   = is_array($flashMessage) ? ($flashMessage['title'] ?? '') : '';
$flashText    = is_array($flashMessage) ? ($flashMessage['text'] ?? '') : '';

$hasErrors     = $bankErrors !== [];
$hasEditErrors = $editBankErrors !== [];

$oldKategori = $hasErrors ? (string) (old('kategori') ?? BankPertanyaanTracerStudiModel::DEFAULT_KATEGORI) : BankPertanyaanTracerStudiModel::DEFAULT_KATEGORI;
$oldTeks     = $hasErrors ? (string) (old('teks_pertanyaan') ?? '') : '';
$oldTipe     = $hasErrors ? strtoupper((string) (old('tipe_jawaban') ?? 'TEKS')) : 'TEKS';
$oldOpsi     = $hasErrors ? (string) (old('opsi_jawaban') ?? '') : '';
$oldWajib    = $hasErrors ? (string) (old('is_wajib_default') ?? '1') : '1';

$editOldId       = $hasEditErrors ? (int) (old('id') ?? $editBankId) : 0;
$editOldKategori = $hasEditErrors ? (string) (old('kategori') ?? BankPertanyaanTracerStudiModel::DEFAULT_KATEGORI) : BankPertanyaanTracerStudiModel::DEFAULT_KATEGORI;
$editOldTeks     = $hasEditErrors ? (string) (old('teks_pertanyaan') ?? '') : '';
$editOldTipe     = $hasEditErrors ? strtoupper((string) (old('tipe_jawaban') ?? 'TEKS')) : 'TEKS';
$editOldOpsi     = $hasEditErrors ? (string) (old('opsi_jawaban') ?? '') : '';
$editOldWajib    = $hasEditErrors ? (string) (old('is_wajib_default') ?? '1') : '1';
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
        <i class="fas fa-database text-primary mr-2"></i>Bank Pertanyaan Tracer Study
    </h1>
</div>

<?php if (! $isBankTableReady) : ?>
    <div class="alert alert-warning shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Tabel database <code>bank_pertanyaan_tracer_studi</code> belum dibuat. Daftar di bawah menggunakan katalog default (mode baca). Jalankan migrasi <code>php spark migrate</code> untuk mengaktifkan pengelolaan Bank Pertanyaan &amp; pengambilan pertanyaan ke form aktif.
    </div>
<?php endif; ?>

<!-- Tabel Bank Pertanyaan + Filter + Batch Picker -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
            <h6 class="m-0 font-weight-bold text-primary">Daftar Pertanyaan Nonaktif (Bank Tracer Study)</h6>
            <small class="text-muted">Halaman ini khusus menyimpan pertanyaan Tracer Study berstatus nonaktif. Centang beberapa lalu tekan <strong>Aktifkan Terpilih ke Form</strong>, atau tekan <strong>Aktifkan</strong> pada baris.</small>
        </div>
        <div class="mt-2 mt-md-0 d-flex flex-wrap align-items-center">
            <button type="button"
                class="btn btn-primary btn-sm shadow-sm mb-1 mb-sm-0 font-weight-bold"
                data-toggle="modal"
                data-target="#modalTambahBank"
                <?= ! $isBankTableReady ? 'disabled title="Jalankan migrasi terlebih dahulu"' : '' ?>>
                <i class="fas fa-plus fa-sm mr-1"></i> Tambah Pertanyaan
            </button>
        </div>
    </div>

    <div class="card-body">
        <!-- Filter Bar (Standalone GET form, sibling of POST form) -->
        <form method="get" action="<?= base_url('graduate/bank_pertanyaan_tracer_studi') ?>" class="form-row align-items-end mb-3 pb-3 border-bottom">
            <div class="form-group col-md-4 mb-2 mb-md-0">
                <label for="filterKategoriBank" class="small font-weight-bold text-gray-700 mb-1">Filter Kategori</label>
                <select id="filterKategoriBank" name="kategori" class="form-control form-control-sm">
                    <option value="">-- Semua Kategori --</option>
                    <?php foreach ($kategoriList as $katOpt) : ?>
                        <option value="<?= esc($katOpt, 'attr') ?>" <?= strcasecmp($filterKategori, $katOpt) === 0 ? 'selected' : '' ?>>
                            <?= esc($katOpt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-5 mb-2 mb-md-0">
                <label for="filterKeywordBank" class="small font-weight-bold text-gray-700 mb-1">Kata Kunci Pertanyaan</label>
                <input type="search" id="filterKeywordBank" name="q" class="form-control form-control-sm" value="<?= esc($filterKeyword, 'attr') ?>" placeholder="Cari berdasarkan teks pertanyaan atau kategori...">
            </div>
            <div class="form-group col-md-3 mb-0 d-flex">
                <button type="submit" class="btn btn-primary btn-sm mr-2 font-weight-bold">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="<?= base_url('graduate/bank_pertanyaan_tracer_studi') ?>" class="btn btn-outline-secondary btn-sm">
                    Reset
                </a>
            </div>
        </form>

        <form action="<?= base_url('graduate/bank_pertanyaan_tracer_studi/ambil') ?>" method="post" id="formAmbilDariBank">
            <?= csrf_field() ?>
            <input type="hidden" name="return_to" value="bank">

            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 bg-light border rounded px-3 py-2">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox"
                        class="custom-control-input"
                        id="chkPilihSemuaBank"
                        <?= (! $isBankTableReady || ! $isFormTableReady) ? 'disabled' : '' ?>>
                    <label class="custom-control-label small font-weight-bold text-gray-700" for="chkPilihSemuaBank">
                        Pilih semua pertanyaan nonaktif di bank ini
                    </label>
                </div>
                <div class="d-flex flex-wrap align-items-center mt-2 mt-sm-0">
                    <span class="badge badge-light border text-gray-700 mr-2 px-2 py-1" id="badgeCountSelectedBank">Dipilih: 0</span>
                    <button type="submit"
                        id="btnAmbilTerpilihBank"
                        class="btn btn-success btn-sm font-weight-bold shadow-sm"
                        disabled
                        <?= (! $isBankTableReady || ! $isFormTableReady) ? 'title="Jalankan migrasi terlebih dahulu"' : '' ?>>
                        <i class="fas fa-plus-circle mr-1"></i> Aktifkan Terpilih ke Form
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 48px;" class="text-center">Pilih</th>
                            <th style="width: 185px;">Kategori</th>
                            <th>Teks Pertanyaan &amp; Opsi</th>
                            <th style="width: 140px;" class="text-center">Tipe Jawaban</th>
                            <th style="width: 100px;" class="text-center">Sifat</th>
                            <th style="width: 120px;" class="text-center">Status</th>
                            <th style="width: 215px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bankItems)) : ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Tidak ada pertanyaan nonaktif di Bank Pertanyaan yang sesuai filter saat ini.
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($bankItems as $idx => $item) : ?>
                                <?php
                                $bid          = (int) ($item['id'] ?? ($idx + 1));
                                $kategori     = (string) ($item['kategori'] ?? BankPertanyaanTracerStudiModel::DEFAULT_KATEGORI);
                                $teks         = (string) ($item['teks_pertanyaan'] ?? '');
                                $tipeRaw      = strtoupper(trim((string) ($item['tipe_jawaban'] ?? 'TEKS')));
                                $tipeLabel    = PertanyaanTracerStudiAwalModel::formatTipeJawaban($tipeRaw);
                                $opsiList     = PertanyaanTracerStudiAwalModel::parseOpsiJawaban($item['opsi_jawaban'] ?? null);
                                $opsiJsonAttr = json_encode($opsiList, JSON_UNESCAPED_UNICODE) ?: '[]';
                                $isWajib      = ! empty($item['is_wajib_default']);
                                ?>
                                <tr>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-checkbox d-inline-block">
                                            <input type="checkbox"
                                                class="custom-control-input chk-bank-item"
                                                id="bankPick_<?= $bid ?>"
                                                name="bank_ids[]"
                                                value="<?= $bid ?>"
                                                <?= (! $isBankTableReady || ! $isFormTableReady) ? 'disabled' : '' ?>>
                                            <label class="custom-control-label" for="bankPick_<?= $bid ?>"></label>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge badge-info px-2 py-1"><?= esc($kategori) ?></span>
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
                                        <?php if ($isWajib) : ?>
                                            <span class="badge badge-danger px-2 py-1">Wajib</span>
                                        <?php else : ?>
                                            <span class="badge badge-secondary px-2 py-1">Opsional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-secondary px-2 py-1">Nonaktif</span>
                                    </td>
                                    <td class="text-center align-middle text-nowrap">
                                        <button type="button"
                                            class="btn btn-success btn-sm btn-quick-ambil-bank font-weight-bold"
                                            data-id="<?= $bid ?>"
                                            title="Aktifkan pertanyaan ini dan pindahkan ke Pertanyaan Tracer Study"
                                            <?= (! $isBankTableReady || ! $isFormTableReady) ? 'disabled' : '' ?>>
                                            <i class="fas fa-plus fa-fw"></i> Aktifkan
                                        </button>
                                        <button type="button"
                                            class="btn btn-warning btn-sm btn-edit-bank ml-1"
                                            data-toggle="modal"
                                            data-target="#modalEditBank"
                                            data-id="<?= $bid ?>"
                                            data-kategori="<?= esc($kategori, 'attr') ?>"
                                            data-teks="<?= esc($teks, 'attr') ?>"
                                            data-tipe="<?= esc($tipeRaw, 'attr') ?>"
                                            data-opsi="<?= esc($opsiJsonAttr, 'attr') ?>"
                                            data-wajib="<?= $isWajib ? '1' : '0' ?>"
                                            title="Ubah pertanyaan nonaktif"
                                            <?= ! $isBankTableReady ? 'disabled' : '' ?>>
                                            <i class="fas fa-edit fa-fw"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-danger btn-sm btn-hapus-bank ml-1"
                                            data-toggle="modal"
                                            data-target="#modalHapusBank"
                                            data-id="<?= $bid ?>"
                                            data-kategori="<?= esc($kategori, 'attr') ?>"
                                            data-teks="<?= esc($teks, 'attr') ?>"
                                            title="Hapus dari Bank Pertanyaan"
                                            <?= ! $isBankTableReady ? 'disabled' : '' ?>>
                                            <i class="fas fa-trash fa-fw"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<!-- Form Quick-Pick 1 Pertanyaan dari Bank (Non-nested) -->
<form action="<?= base_url('graduate/bank_pertanyaan_tracer_studi/ambil') ?>" method="post" id="formQuickAmbilBank" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="return_to" value="bank">
    <input type="hidden" name="bank_id" id="quickAmbilBankId" value="">
</form>

<!-- Modal Tambah Template Bank Pertanyaan -->
<div class="modal fade" id="modalTambahBank" tabindex="-1" role="dialog" aria-labelledby="modalTambahBankLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-primary" id="modalTambahBankLabel">
                    <i class="fas fa-plus-circle mr-2"></i>Tambah Pertanyaan Nonaktif ke Bank
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/bank_pertanyaan_tracer_studi') ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-light border small text-muted mb-3">
                        <i class="fas fa-info-circle mr-1 text-primary"></i>
                        Pertanyaan yang ditambahkan di sini berstatus <strong>nonaktif</strong> dan hanya muncul di halaman Bank Pertanyaan hingga diaktifkan ke daftar Pertanyaan Tracer Study.
                    </div>
                    <?php if ($hasErrors) : ?>
                        <div class="alert alert-danger" role="alert">
                            <strong><i class="fas fa-exclamation-circle mr-1"></i>Periksa Kembali Isian:</strong>
                            <ul class="mb-0 pl-3 mt-1">
                                <?php foreach ($bankErrors as $errMsg) : ?>
                                    <li><?= esc($errMsg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="tambahBankKategori" class="font-weight-bold">Kategori <span class="text-danger">*</span></label>
                        <select id="tambahBankKategori" name="kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategoriList as $katOpt) : ?>
                                <option value="<?= esc($katOpt, 'attr') ?>" <?= strcasecmp($oldKategori, (string) $katOpt) === 0 ? 'selected' : '' ?>>
                                    <?= esc($katOpt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Perlu menambah atau mengubah nama kategori? Kelola di menu <a href="<?= base_url('graduate/kategori_tracer_studi') ?>">Master Kategori</a>.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="tambahBankTeks" class="font-weight-bold">Teks Pertanyaan <span class="text-danger">*</span></label>
                        <textarea id="tambahBankTeks" name="teks_pertanyaan" rows="3" class="form-control" placeholder="Tulis kalimat pertanyaan template..."><?= esc($oldTeks) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label for="tambahBankTipe" class="font-weight-bold">Tipe Jawaban <span class="text-danger">*</span></label>
                            <select id="tambahBankTipe" name="tipe_jawaban" class="form-control">
                                <option value="TEKS" <?= $oldTipe === 'TEKS' ? 'selected' : '' ?>>Uraian Teks (Paragraf)</option>
                                <option value="ISIAN_SINGKAT" <?= $oldTipe === 'ISIAN_SINGKAT' ? 'selected' : '' ?>>Isian Singkat (Satu Baris)</option>
                                <option value="PILIHAN_GANDA" <?= $oldTipe === 'PILIHAN_GANDA' ? 'selected' : '' ?>>Pilihan Ganda</option>
                                <option value="SKALA" <?= $oldTipe === 'SKALA' ? 'selected' : '' ?>>Skala Penilaian (1 - 5)</option>
                            </select>
                        </div>
                        <div class="form-group col-md-5">
                            <label for="tambahBankWajib" class="font-weight-bold">Sifat Default Saat Diambil</label>
                            <select id="tambahBankWajib" name="is_wajib_default" class="form-control">
                                <option value="1" <?= $oldWajib !== '0' ? 'selected' : '' ?>>Wajib Diisi</option>
                                <option value="0" <?= $oldWajib === '0' ? 'selected' : '' ?>>Opsional</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group <?= in_array($oldTipe, ['PILIHAN_GANDA', 'SKALA'], true) ? '' : 'd-none' ?>" id="groupTambahBankOpsi">
                        <label for="tambahBankOpsi" class="font-weight-bold">
                            Daftar Pilihan / Skala Jawaban
                            <span class="text-danger <?= $oldTipe === 'PILIHAN_GANDA' ? '' : 'd-none' ?>" id="markTambahBankOpsi">*</span>
                        </label>
                        <textarea id="tambahBankOpsi" name="opsi_jawaban" rows="4" class="form-control" placeholder="Masukkan satu pilihan per baris atau pisahkan dengan titik koma (;)"><?= esc($oldOpsi) ?></textarea>
                        <small class="form-text text-muted">
                            Untuk Pilihan Ganda wajib minimal 2 pilihan. Untuk Skala Penilaian, jika dikosongkan otomatis memakai skala 1 s.d. 5 standar.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan ke Bank
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ubah Template Bank Pertanyaan -->
<div class="modal fade" id="modalEditBank" tabindex="-1" role="dialog" aria-labelledby="modalEditBankLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-warning" id="modalEditBankLabel">
                    <i class="fas fa-edit mr-2"></i>Ubah Template Bank Pertanyaan
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/bank_pertanyaan_tracer_studi/update') ?>" method="post" id="formEditBank" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="editBankId" value="<?= $editOldId > 0 ? $editOldId : '' ?>">
                <div class="modal-body">
                    <?php if ($hasEditErrors) : ?>
                        <div class="alert alert-danger" role="alert">
                            <strong><i class="fas fa-exclamation-circle mr-1"></i>Periksa Kembali Isian:</strong>
                            <ul class="mb-0 pl-3 mt-1">
                                <?php foreach ($editBankErrors as $errMsg) : ?>
                                    <li><?= esc($errMsg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="editBankKategori" class="font-weight-bold">Kategori <span class="text-danger">*</span></label>
                        <select id="editBankKategori" name="kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategoriList as $katOpt) : ?>
                                <option value="<?= esc($katOpt, 'attr') ?>" <?= strcasecmp($editOldKategori, (string) $katOpt) === 0 ? 'selected' : '' ?>>
                                    <?= esc($katOpt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Kelola daftar kategori di menu <a href="<?= base_url('graduate/kategori_tracer_studi') ?>">Master Kategori</a>.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="editBankTeks" class="font-weight-bold">Teks Pertanyaan <span class="text-danger">*</span></label>
                        <textarea id="editBankTeks" name="teks_pertanyaan" rows="3" class="form-control"><?= esc($editOldTeks) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label for="editBankTipe" class="font-weight-bold">Tipe Jawaban <span class="text-danger">*</span></label>
                            <select id="editBankTipe" name="tipe_jawaban" class="form-control">
                                <option value="TEKS" <?= $editOldTipe === 'TEKS' ? 'selected' : '' ?>>Uraian Teks (Paragraf)</option>
                                <option value="ISIAN_SINGKAT" <?= $editOldTipe === 'ISIAN_SINGKAT' ? 'selected' : '' ?>>Isian Singkat (Satu Baris)</option>
                                <option value="PILIHAN_GANDA" <?= $editOldTipe === 'PILIHAN_GANDA' ? 'selected' : '' ?>>Pilihan Ganda</option>
                                <option value="SKALA" <?= $editOldTipe === 'SKALA' ? 'selected' : '' ?>>Skala Penilaian (1 - 5)</option>
                            </select>
                        </div>
                        <div class="form-group col-md-5">
                            <label for="editBankWajib" class="font-weight-bold">Sifat Default Saat Diambil</label>
                            <select id="editBankWajib" name="is_wajib_default" class="form-control">
                                <option value="1" <?= $editOldWajib !== '0' ? 'selected' : '' ?>>Wajib Diisi</option>
                                <option value="0" <?= $editOldWajib === '0' ? 'selected' : '' ?>>Opsional</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group <?= in_array($editOldTipe, ['PILIHAN_GANDA', 'SKALA'], true) ? '' : 'd-none' ?>" id="groupEditBankOpsi">
                        <label for="editBankOpsi" class="font-weight-bold">
                            Daftar Pilihan / Skala Jawaban
                            <span class="text-danger <?= $editOldTipe === 'PILIHAN_GANDA' ? '' : 'd-none' ?>" id="markEditBankOpsi">*</span>
                        </label>
                        <textarea id="editBankOpsi" name="opsi_jawaban" rows="4" class="form-control" placeholder="Masukkan satu pilihan per baris atau pisahkan dengan titik koma (;)"><?= esc($editOldOpsi) ?></textarea>
                        <small class="form-text text-muted">
                            Untuk Pilihan Ganda wajib minimal 2 pilihan. Untuk Skala Penilaian, jika dikosongkan otomatis memakai skala 1 s.d. 5 standar.
                        </small>
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

<!-- Modal Konfirmasi Hapus Template Bank -->
<div class="modal fade" id="modalHapusBank" tabindex="-1" role="dialog" aria-labelledby="modalHapusBankLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-danger" id="modalHapusBankLabel">
                    <i class="fas fa-trash mr-2"></i>Hapus Item Bank Pertanyaan
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/bank_pertanyaan_tracer_studi/delete') ?>" method="post" id="formHapusBank">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="hapusBankId" value="">
                <div class="modal-body">
                    <p class="mb-2">Apakah Anda yakin ingin menghapus template pertanyaan kategori <strong id="hapusBankKategori">-</strong> berikut dari Bank Pertanyaan?</p>
                    <div class="alert alert-warning py-2 mb-3">
                        <div class="font-weight-bold text-gray-900" id="hapusBankTeks">-</div>
                    </div>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-info-circle mr-1"></i>
                        Menghapus item dari Bank Pertanyaan tidak akan menghapus pertanyaan yang sudah dipasang pada form aktif maupun jawaban mahasiswa.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger font-weight-bold">
                        <i class="fas fa-trash fa-fw mr-1"></i> Ya, Hapus dari Bank
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
                } else {
                    $mark.addClass('d-none');
                    if (allowAutoSkala && $.trim($textarea.val()) === '') {
                        $textarea.val(defaultSkalaText);
                    }
                }
            } else {
                $group.addClass('d-none');
                $mark.addClass('d-none');
            }
        }

        function bindOpsiForm(selectSel, groupSel, markSel, textareaSel) {
            var $select = $(selectSel);
            var $group = $(groupSel);
            var $mark = $(markSel);
            var $textarea = $(textareaSel);

            $select.on('change', function () {
                syncOpsiField($select, $group, $mark, $textarea, true);
            });
            syncOpsiField($select, $group, $mark, $textarea, false);
        }

        bindOpsiForm('#tambahBankTipe', '#groupTambahBankOpsi', '#markTambahBankOpsi', '#tambahBankOpsi');
        bindOpsiForm('#editBankTipe', '#groupEditBankOpsi', '#markEditBankOpsi', '#editBankOpsi');

        function refreshSelectedCount() {
            var count = $('.chk-bank-item:checked:not(:disabled)').length;
            $('#badgeCountSelectedBank').text('Dipilih: ' + count);
            $('#btnAmbilTerpilihBank').prop('disabled', count === 0);
        }

        $('#chkPilihSemuaBank').on('change', function () {
            var checked = $(this).is(':checked');
            $('.chk-bank-item:not(:disabled)').prop('checked', checked);
            refreshSelectedCount();
        });

        $('.chk-bank-item').on('change', refreshSelectedCount);

        $('.btn-quick-ambil-bank').on('click', function () {
            var bankId = $(this).data('id');
            if (!bankId) {
                return;
            }
            $('#quickAmbilBankId').val(bankId);
            $('#formQuickAmbilBank').trigger('submit');
        });

        $('.btn-edit-bank').on('click', function () {
            var $btn = $(this);
            var rawOpsi = $btn.attr('data-opsi') || '[]';
            var opsiArr = [];
            try {
                opsiArr = JSON.parse(rawOpsi);
                if (!Array.isArray(opsiArr)) {
                    opsiArr = [];
                }
            } catch (err) {
                opsiArr = [];
            }

            var katVal = $btn.attr('data-kategori') || '';
            var $editKatSelect = $('#editBankKategori');
            if (katVal !== '' && $editKatSelect.find('option').filter(function () { return this.value === katVal; }).length === 0) {
                $editKatSelect.append($('<option>', { value: katVal, text: katVal }));
            }
            $('#editBankId').val($btn.data('id'));
            $editKatSelect.val(katVal);
            $('#editBankTeks').val($btn.data('teks'));
            $('#editBankTipe').val($btn.data('tipe'));
            $('#editBankOpsi').val(opsiArr.join("\n"));
            $('#editBankWajib').val(String($btn.data('wajib')) === '0' ? '0' : '1');
            syncOpsiField($('#editBankTipe'), $('#groupEditBankOpsi'), $('#markEditBankOpsi'), $('#editBankOpsi'), false);
        });

        $('.btn-hapus-bank').on('click', function () {
            var $btn = $(this);
            $('#hapusBankId').val($btn.data('id'));
            $('#hapusBankKategori').text($btn.data('kategori') || '-');
            $('#hapusBankTeks').text($btn.data('teks') || '-');
        });

        <?php if ($hasErrors) : ?>
        $('#modalTambahBank').modal('show');
        <?php endif; ?>

        <?php if ($hasEditErrors) : ?>
        $('#modalEditBank').modal('show');
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
