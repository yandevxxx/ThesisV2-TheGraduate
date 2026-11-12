<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<?php
$terplot     = $terplot ?? [];
$jawabanList = $jawabanList ?? [];
$sudahIsi    = ! empty($sudahIsi);
$submittedAt = $submittedAt ?? null;
$namaProdi   = trim((string) ($namaProdi ?? ''));
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-file-alt text-primary mr-2"></i>Detail Jawaban Tracer Study Awal
        </h1>
        <p class="mb-0 text-muted small">
            Ringkasan isian formulir Tracer Study Awal mahasiswa terplot.
        </p>
    </div>
    <a href="<?= base_url('graduate/tracer_studi_pantau') ?>" class="btn btn-sm btn-outline-secondary shadow-sm mt-2 mt-sm-0">
        <i class="fas fa-arrow-left fa-sm mr-1"></i> Kembali ke Pemantauan
    </a>
</div>

<div class="card shadow mb-4 border-left-<?= $sudahIsi ? 'success' : 'warning' ?>">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="small text-muted">Nama Mahasiswa</div>
                <div class="font-weight-bold text-gray-800"><?= esc((string) ($terplot['nama'] ?? '-')) ?></div>
                <?php if ($namaProdi !== ''): ?>
                    <div class="small text-muted mt-1"><i class="fas fa-university mr-1"></i><?= esc($namaProdi) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <div class="small text-muted">NPM</div>
                <div class="font-weight-bold text-gray-800"><?= esc((string) ($terplot['npm'] ?? '-')) ?></div>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <div class="small text-muted">Status SK / Semester</div>
                <div class="font-weight-bold text-gray-800">
                    <?= esc(ucfirst(strtolower((string) ($terplot['status'] ?? '-')))) ?>
                    <?php if (! empty($terplot['kode_semester'])) : ?>
                        (<?= esc((string) $terplot['kode_semester']) ?>)
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Status Pengisian</div>
                <?php if ($sudahIsi) : ?>
                    <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Sudah Mengisi</span>
                    <?php if (! empty($submittedAt)) : ?>
                        <div class="small text-muted mt-1">Dikirim: <?= esc($submittedAt) ?></div>
                    <?php endif; ?>
                <?php else : ?>
                    <span class="badge badge-danger px-2 py-1"><i class="fas fa-clock mr-1"></i>Belum Mengisi</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 bg-white">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list-alt mr-1"></i> Jawaban Tracer Study Awal (<?= count($jawabanList) ?> Pertanyaan)
        </h6>
    </div>
    <div class="card-body">
        <?php if (! $sudahIsi) : ?>
            <div class="alert alert-warning mb-0" role="alert">
                <i class="fas fa-info-circle mr-2"></i>
                Mahasiswa ini belum mengirimkan formulir Tracer Study Awal.
            </div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-bordered mb-0" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width: 8%;">No</th>
                            <th style="width: 48%;">Pertanyaan</th>
                            <th>Jawaban Mahasiswa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jawabanList as $idx => $item) : ?>
                            <?php
                            $urutan  = (int) ($item['urutan'] ?? ($idx + 1));
                            $teks    = (string) ($item['teks_pertanyaan'] ?? '');
                            $jawaban = trim((string) ($item['jawaban'] ?? ''));
                            ?>
                            <tr>
                                <td class="text-center align-middle">
                                    <span class="badge badge-primary px-2 py-1"><?= $urutan ?></span>
                                </td>
                                <td class="align-middle font-weight-bold text-gray-800">
                                    <?= esc($teks) ?>
                                </td>
                                <td class="align-middle">
                                    <?php if ($jawaban !== '') : ?>
                                        <?= nl2br(esc($jawaban)) ?>
                                    <?php else : ?>
                                        <span class="text-muted font-italic">- (Tidak diisi / opsional)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection(); ?>
