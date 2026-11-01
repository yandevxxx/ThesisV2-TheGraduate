<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<?php
$fitur = $fitur ?? 'Form Tracer Studi Awal dan Form Pendaftaran Wisuda';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Akses Ditolak</h1>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-body text-center py-5">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h4 class="text-gray-800 mb-2">Anda Belum Terdaftar Dalam Daftar Terplot SK Yudisium</h4>
                <p class="text-gray-600 mb-4">Hanya mahasiswa yang terdaftar dalam daftar mahasiswa terplot SK Yudisium yang dapat mengakses <?= esc($fitur) ?>.</p>
                <p class="text-gray-600 mb-4">Silakan hubungi Tenaga Kependidikan untuk informasi lebih lanjut.</p>
                <a href="<?= base_url('graduate/choose') ?>" class="btn btn-primary"><i class="fas fa-th-large fa-fw"></i> Kembali ke Menu Utama</a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>
