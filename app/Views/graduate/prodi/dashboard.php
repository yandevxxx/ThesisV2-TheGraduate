<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari database menyusul pada modul wisuda.
 */
$ringkasan = [
    ['label' => 'Calon Wisudawan', 'value' => 20, 'icon' => 'fa-user-graduate', 'color' => 'primary'],
    ['label' => 'Mahasiswa Wisudawan', 'value' => 15, 'icon' => 'fa-graduation-cap', 'color' => 'success'],
    ['label' => 'SKL Menunggu Verifikasi', 'value' => 6, 'icon' => 'fa-certificate', 'color' => 'warning'],
    ['label' => 'Dosen Pembimbing', 'value' => 25, 'icon' => 'fa-chalkboard-teacher', 'color' => 'info'],
];
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dasbor Program Studi</h1>
</div>

<div class="alert alert-info shadow-sm" role="alert">
    <i class="fas fa-info-circle fa-fw"></i>
    Modul pemantauan kelulusan program studi sedang dalam pengembangan.
    Ringkasan di bawah masih menggunakan data contoh.
</div>

<div class="row">
    <?php foreach ($ringkasan as $item) : ?>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-<?= esc($item['color']) ?> shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-<?= esc($item['color']) ?> text-uppercase mb-1">
                                <?= esc($item['label']) ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int) $item['value'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas <?= esc($item['icon']) ?> fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Menu Program Studi</h6>
    </div>
    <div class="card-body">
        <p class="mb-1">Menu <strong>Calon Wisudawan</strong>, <strong>Mahasiswa Wisudawan</strong>, dan <strong>SKL</strong> akan tersedia pada modul berikutnya.</p>
        <p class="small text-gray-500 mb-0">Sementara ini gunakan menu utama untuk berpindah ke sisi TheSIS.</p>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>
