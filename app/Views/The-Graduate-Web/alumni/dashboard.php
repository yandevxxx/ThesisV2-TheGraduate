<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari database menyusul pada modul wisuda.
 */
$statusPendaftaran = 'belum';
$statusSkl = 'belum';
$berkas = [
    ['nama' => 'Ijazah SMA', 'status' => true],
    ['nama' => 'Akte Kelahiran', 'status' => true],
    ['nama' => 'Kartu Keluarga', 'status' => true],
    ['nama' => 'Pas Foto', 'status' => false],
    ['nama' => 'File Skripsi', 'status' => false],
];
$berkasSelesai = count(array_filter($berkas, function ($item) {
    return $item['status'];
}));
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dasbor Alumni</h1>
</div>

<div class="alert alert-info shadow-sm" role="alert">
    <i class="fas fa-info-circle fa-fw"></i>
    Modul pendaftaran wisuda, SKL, dan pengambilan ijazah sedang dalam pengembangan.
    Dasbor ini akan menampilkan status kelulusan Anda.
</div>

<div class="row">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Pendaftaran Wisuda</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $statusPendaftaran === 'sudah' ? 'Sudah Mendaftar' : 'Belum Mendaftar' ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-user-plus fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Surat Keterangan Lulus (SKL)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $statusSkl === 'sudah' ? 'Sudah Terbit' : 'Belum Terbit' ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-certificate fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Kelengkapan Berkas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $berkasSelesai ?> / <?= count($berkas) ?> Berkas</div>
                        <div class="progress progress-sm mt-2">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= (int) round(($berkasSelesai / count($berkas)) * 100) ?>%"></div>
                        </div>
                    </div>
                    <div class="col-auto"><i class="fas fa-folder-open fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Berkas Persyaratan</h6>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <?php foreach ($berkas as $item) : ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span><?= esc($item['nama']) ?></span>
                    <?php if ($item['status']) : ?>
                        <span class="badge badge-success">Sudah Diunggah</span>
                    <?php else : ?>
                        <span class="badge badge-secondary">Belum Diunggah</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>
