<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>
<?php
$summaryRows    = $summaryRows ?? [];
$submittedAt    = (string) ($submittedAt ?? date('Y-m-d H:i:s'));
$previewFormUrl = (string) ($previewFormUrl ?? base_url('graduate/pertanyaan_tracer_studi/pratinjau'));
$editModeUrl    = (string) ($editModeUrl ?? base_url('graduate/pertanyaan_tracer_studi'));
$resetUrl       = (string) ($resetUrl ?? base_url('graduate/pertanyaan_tracer_studi/pratinjau/reset'));

$flashMessage = session()->getFlashdata('message');
$flashIcon    = is_array($flashMessage) ? ($flashMessage['icon'] ?? 'info') : '';
$flashTitle   = is_array($flashMessage) ? ($flashMessage['title'] ?? '') : '';
$flashText    = is_array($flashMessage) ? ($flashMessage['text'] ?? '') : '';
?>

<?php if ($flashText !== '') : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= esc($flashIcon) ?></p>
        <p id="title" hidden><?= esc($flashTitle) ?></p>
        <p id="text" hidden><?= esc($flashText) ?></p>
    </div>
<?php endif; ?>

<div class="mb-4">
    <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
        Halaman Lanjutan Tracer Study Awal
        <span class="badge badge-info align-middle ml-2 px-2 py-1" style="font-size: 0.45em;">
            <i class="fas fa-eye mr-1"></i>Mode Pratinjau
        </span>
    </h1>
    <p class="mb-0 text-muted small">
        Simulasi perpindahan halaman setelah mahasiswa mengirimkan formulir Tracer Study Awal.
    </p>
</div>

<div class="alert alert-success border-left-success shadow-sm mb-4" role="alert">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
            <i class="fas fa-check-circle mr-2"></i>
            <strong>Simulasi Pengiriman Berhasil!</strong>
            Validasi wajib/opsional terpenuhi dan alur berhasil berpindah ke halaman berikutnya.
            <span class="d-block mt-1 small">
                <i class="fas fa-database mr-1"></i>
                <strong>Catatan Pratinjau:</strong> Tidak ada jawaban yang disimpan ke tabel hasil survei (<code>jawaban_tracer_studi_awal</code>).
            </span>
        </div>
        <span class="badge badge-success px-3 py-2 mt-2 mt-md-0">
            <i class="fas fa-unlock mr-1"></i> Simulasi: Biodata Mahasiswa Terbuka
        </span>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between bg-white">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list-alt mr-2"></i>Ringkasan Isian Simulasi (<?= count($summaryRows) ?> Pertanyaan)
        </h6>
        <span class="small text-muted mt-1 mt-md-0">
            <i class="far fa-clock mr-1"></i>Waktu simulasi: <?= esc($submittedAt) ?>
        </span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 70px;" class="text-center">No.</th>
                        <th>Pertanyaan</th>
                        <th style="width: 200px;">Tipe &amp; Sifat</th>
                        <th>Jawaban Simulasi Anda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($summaryRows === []) : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Tidak ada pertanyaan aktif pada ringkasan simulasi.
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($summaryRows as $row) : ?>
                            <tr>
                                <td class="text-center font-weight-bold">
                                    <span class="badge badge-primary"><?= (int) ($row['urutan'] ?? 0) ?></span>
                                </td>
                                <td class="text-dark"><?= esc((string) ($row['pertanyaan'] ?? '')) ?></td>
                                <td>
                                    <span class="badge badge-light border d-inline-block mb-1">
                                        <?= esc((string) ($row['tipe_label'] ?? '-')) ?>
                                    </span>
                                    <?php if (! empty($row['is_wajib'])) : ?>
                                        <span class="badge badge-danger d-inline-block">Wajib</span>
                                    <?php else : ?>
                                        <span class="badge badge-secondary d-inline-block">Opsional</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($row['jawaban'] ?? '') !== '') : ?>
                                        <span class="text-dark font-weight-bold"><?= nl2br(esc((string) $row['jawaban'])) ?></span>
                                    <?php else : ?>
                                        <span class="text-muted font-italic">Opsional — tidak diisi</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white d-flex flex-column flex-sm-row align-items-sm-center justify-content-between">
        <a href="<?= esc($previewFormUrl) ?>" class="btn btn-outline-primary font-weight-bold mb-2 mb-sm-0">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Form Pratinjau
        </a>
        <div class="d-flex flex-wrap align-items-center">
            <form action="<?= esc($resetUrl) ?>" method="post" class="d-inline mr-2 mb-2 mb-sm-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-warning text-dark font-weight-bold">
                    <i class="fas fa-undo mr-1"></i> Reset Simulasi
                </button>
            </form>
            <a href="<?= esc($editModeUrl) ?>" class="btn btn-primary font-weight-bold shadow-sm mb-2 mb-sm-0">
                <i class="fas fa-edit mr-1"></i> Kembali ke Mode Edit
            </a>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>
<script>
    $(function () {
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
