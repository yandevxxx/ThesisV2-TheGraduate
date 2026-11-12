<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>
<?php
$daftarPertanyaan = $daftarPertanyaan ?? [];
$jawabanMap       = $jawabanMap ?? [];
$sudahDiisi       = ! empty($sudahDiisi);
$lastSubmittedAt  = $lastSubmittedAt ?? null;
$isTableReady     = ! empty($isTableReady);
$tracerErrors     = $tracerErrors ?? [];
$terplot          = $terplot ?? [];
$isPreview        = ! empty($isPreview);
$previewBackUrl   = (string) ($previewBackUrl ?? base_url('graduate/pertanyaan_tracer_studi'));
$formActionUrl    = (string) ($formActionUrl ?? base_url('graduate/tracer_studi'));
$hasPreviewDraft  = ! empty($hasPreviewDraft);

$flashMessage = session()->getFlashdata('message');
$flashIcon    = is_array($flashMessage) ? ($flashMessage['icon'] ?? 'info') : '';
$flashTitle   = is_array($flashMessage) ? ($flashMessage['title'] ?? '') : '';
$flashText    = is_array($flashMessage) ? ($flashMessage['text'] ?? '') : '';

if (! $flashText && session()->getFlashdata('error')) {
    $flashIcon  = 'error';
    $flashTitle = 'Perhatian';
    $flashText  = (string) session()->getFlashdata('error');
} elseif (! $flashText && session()->getFlashdata('success')) {
    $flashIcon  = 'success';
    $flashTitle = 'Berhasil';
    $flashText  = (string) session()->getFlashdata('success');
}

$npmMhs  = (string) ($terplot['npm'] ?? $user['npm'] ?? $user['id'] ?? '-');
$namaMhs = (string) ($terplot['nama'] ?? $user['nama'] ?? $user['name'] ?? '-');
?>

<?php if ($flashText !== '') : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= esc($flashIcon) ?></p>
        <p id="title" hidden><?= esc($flashTitle) ?></p>
        <p id="text" hidden><?= esc($flashText) ?></p>
    </div>
    <?php
    $alertClass = $flashIcon === 'success' ? 'alert-success' : ($flashIcon === 'error' ? 'alert-danger' : ($flashIcon === 'warning' ? 'alert-warning' : 'alert-info'));
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

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
            Tracer Study Awal
            <?php if ($isPreview) : ?>
                <span class="badge badge-info align-middle ml-2 px-2 py-1" style="font-size: 0.45em;">
                    <i class="fas fa-eye mr-1"></i>Mode Pratinjau
                </span>
            <?php endif; ?>
        </h1>
        <p class="mb-0 text-muted small">
            Daftar pertanyaan tracer study awal calon wisudawan. Wajib dilengkapi sebelum mengisi Biodata Mahasiswa.
        </p>
    </div>
    <div class="mt-2 mt-sm-0 d-flex flex-wrap align-items-center">
        <?php if ($isPreview) : ?>
            <a href="<?= esc($previewBackUrl) ?>" class="btn btn-outline-secondary btn-sm shadow-sm font-weight-bold">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Mode Edit
            </a>
        <?php elseif ($sudahDiisi) : ?>
            <a href="<?= base_url('graduate/biodata_wisuda') ?>" class="btn btn-success btn-sm shadow-sm font-weight-bold">
                <i class="fas fa-id-card mr-1"></i> Lanjut Isi Biodata Mahasiswa
            </a>
        <?php else : ?>
            <span class="badge badge-warning text-dark px-3 py-2 shadow-sm">
                <i class="fas fa-lock mr-1"></i> Biodata Mahasiswa Masih Terkunci
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if (! $isTableReady && ! $isPreview) : ?>
    <div class="alert alert-warning border-left-warning shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Migrasi Diperlukan:</strong> Tabel penyimpanan Tracer Study Awal belum tersedia di database. Hubungi administrator untuk menjalankan migrasi (`php spark migrate`).
    </div>
<?php endif; ?>

<?php if ($sudahDiisi) : ?>
    <div class="alert alert-success border-left-success shadow-sm d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4" role="alert">
        <div>
            <i class="fas fa-check-circle mr-2"></i>
            <strong>Tracer Study Awal Sudah Diisi.</strong>
            Menu <strong>Biodata Mahasiswa</strong> sekarang sudah terbuka.
            <?php if (! empty($lastSubmittedAt)) : ?>
                <span class="d-block d-md-inline text-muted small ml-md-2">(Terkirim: <?= esc($lastSubmittedAt) ?>)</span>
            <?php endif; ?>
        </div>
        <a href="<?= base_url('graduate/biodata_wisuda') ?>" class="btn btn-sm btn-success font-weight-bold mt-2 mt-md-0">
            Buka Biodata Mahasiswa <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
<?php else : ?>
    <div class="alert alert-info border-left-info shadow-sm mb-4" role="alert">
        <i class="fas fa-info-circle mr-2"></i>
        <strong>Prasyarat Pendaftaran Wisuda:</strong> Silakan baca dan isi seluruh pertanyaan bertanda <span class="badge badge-danger">Wajib</span> di bawah ini, lalu kirim jawaban untuk membuka akses form <strong>Biodata Mahasiswa</strong>.
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between bg-white">
        <div>
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-clipboard-list mr-2"></i>Formulir Pertanyaan Tracer Study Awal (<?= count($daftarPertanyaan) ?> Pertanyaan)
            </h6>
        </div>
        <div class="small text-muted mt-1 mt-md-0">
            <span class="mr-3"><strong>Nama:</strong> <?= esc($namaMhs) ?></span>
            <span><strong>NPM:</strong> <?= esc($npmMhs) ?></span>
        </div>
    </div>

    <div class="card-body">
        <?php if ($daftarPertanyaan === []) : ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                Belum ada daftar pertanyaan Tracer Study Awal yang aktif.
            </div>
        <?php else : ?>
            <form action="<?= esc($formActionUrl) ?>" method="post" id="formTracerStudiAwal" novalidate>
                <?= csrf_field() ?>

                <?php foreach ($daftarPertanyaan as $idx => $pertanyaan) : ?>
                    <?php
                    $qid         = (int) ($pertanyaan['id'] ?? $pertanyaan['urutan'] ?? ($idx + 1));
                    $urutan      = (int) ($pertanyaan['urutan'] ?? ($idx + 1));
                    $teks        = (string) ($pertanyaan['teks_pertanyaan'] ?? '');
                    $tipe        = strtoupper((string) ($pertanyaan['tipe_jawaban'] ?? 'TEKS'));
                    $opsiList    = \App\Models\PertanyaanTracerStudiAwalModel::parseOpsiJawaban($pertanyaan['opsi_jawaban'] ?? null);
                    $isRadioType = in_array($tipe, ['PILIHAN_GANDA', 'SKALA'], true) && $opsiList !== [];
                    $isWajib     = ! empty($pertanyaan['is_wajib']);
                    $savedVal    = (string) ($jawabanMap[$qid]['jawaban'] ?? '');
                    $oldVal      = old('jawaban.' . $qid);
                    $currentVal  = $oldVal !== null ? (string) $oldVal : $savedVal;
                    $fieldError  = $tracerErrors[$qid] ?? null;
                    ?>
                    <div class="card mb-3 <?= $fieldError ? 'border-danger' : 'border-left-primary' ?>">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-start justify-content-between mb-2">
                                <label class="font-weight-bold text-dark mb-1 mr-2"<?= ! $isRadioType ? ' for="q_' . $qid . '"' : '' ?>>
                                    <span class="badge badge-primary mr-2"><?= $urutan ?></span>
                                    <?= esc($teks) ?>
                                </label>
                                <div class="text-nowrap">
                                    <?php if ($isWajib) : ?>
                                        <span class="badge badge-danger">Wajib</span>
                                    <?php else : ?>
                                        <span class="badge badge-secondary">Opsional</span>
                                    <?php endif; ?>
                                    <span class="badge badge-light border ml-1">
                                        <?= esc(\App\Models\PertanyaanTracerStudiAwalModel::formatTipeJawaban($tipe)) ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($isRadioType) : ?>
                                <div class="mt-2">
                                    <?php foreach ($opsiList as $optIdx => $opsi) : ?>
                                        <?php
                                        $inputId = 'q_' . $qid . '_opt_' . $optIdx;
                                        $checked = ($currentVal === (string) $opsi) ? 'checked' : '';
                                        $reqAttr = ($isWajib && $optIdx === 0) ? 'required' : '';
                                        ?>
                                        <div class="custom-control custom-radio mb-2">
                                            <input
                                                type="radio"
                                                id="<?= esc($inputId) ?>"
                                                name="jawaban[<?= $qid ?>]"
                                                value="<?= esc($opsi) ?>"
                                                class="custom-control-input <?= $fieldError ? 'is-invalid' : '' ?>"
                                                <?= $checked ?>
                                                <?= $reqAttr ?>
                                            >
                                            <label class="custom-control-label text-dark" for="<?= esc($inputId) ?>">
                                                <?= esc($opsi) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($tipe === 'ISIAN_SINGKAT') : ?>
                                <input
                                    type="text"
                                    id="q_<?= $qid ?>"
                                    name="jawaban[<?= $qid ?>]"
                                    class="form-control <?= $fieldError ? 'is-invalid' : '' ?>"
                                    value="<?= esc($currentVal) ?>"
                                    placeholder="Tuliskan jawaban singkat Anda di sini..."
                                    <?= $isWajib ? 'required' : '' ?>
                                >
                            <?php else : ?>
                                <textarea
                                    id="q_<?= $qid ?>"
                                    name="jawaban[<?= $qid ?>]"
                                    rows="3"
                                    class="form-control <?= $fieldError ? 'is-invalid' : '' ?>"
                                    placeholder="Tuliskan jawaban Anda di sini..."
                                    <?= $isWajib ? 'required' : '' ?>
                                ><?= esc($currentVal) ?></textarea>
                            <?php endif; ?>

                            <?php if ($fieldError) : ?>
                                <div class="text-danger small mt-2 font-weight-bold">
                                    <i class="fas fa-exclamation-circle mr-1"></i><?= esc($fieldError) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between pt-3 border-top">
                    <?php if ($isPreview) : ?>
                        <span class="text-muted small mb-2 mb-sm-0">
                            <i class="fas fa-eye mr-1"></i> Mode pratinjau tampilan mahasiswa — isian simulasi tidak disimpan ke hasil survei.
                        </span>
                        <div class="d-flex flex-wrap align-items-center">
                            <?php if ($hasPreviewDraft) : ?>
                                <button type="submit" form="formResetPratinjau" class="btn btn-outline-secondary font-weight-bold mr-2 mb-2 mb-sm-0">
                                    <i class="fas fa-undo mr-1"></i> Reset Simulasi
                                </button>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary px-4 font-weight-bold shadow-sm">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Kirim Simulasi &amp; Lanjut Halaman
                            </button>
                        </div>
                    <?php else : ?>
                        <span class="text-muted small mb-2 mb-sm-0">
                            <i class="fas fa-shield-alt mr-1"></i> Data tracer study digunakan sebagai prasyarat pengisian Biodata Mahasiswa.
                        </span>
                        <button type="submit" class="btn btn-primary px-4 font-weight-bold shadow-sm" <?= ! $isTableReady ? 'disabled' : '' ?>>
                            <i class="fas fa-paper-plane mr-2"></i>
                            <?= $sudahDiisi ? 'Perbarui Jawaban & Lanjut ke Biodata' : 'Simpan Tracer Study & Lanjut ke Biodata' ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php if ($isPreview && $hasPreviewDraft) : ?>
    <form id="formResetPratinjau" action="<?= base_url('graduate/pertanyaan_tracer_studi/pratinjau/reset') ?>" method="post" class="d-none">
        <?= csrf_field() ?>
    </form>
<?php endif; ?>
<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>
<script>
    $(function () {
        var $firstErrorCard = $('.card.border-danger').first();
        if ($firstErrorCard.length) {
            $('html, body').animate({
                scrollTop: Math.max(0, $firstErrorCard.offset().top - 24)
            }, 300);
        }
    });
</script>
<?= $this->endSection(); ?>
