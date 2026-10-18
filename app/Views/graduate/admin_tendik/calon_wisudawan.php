<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Calon Wisudawan</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <p class="mb-1">Modul <strong>Calon Wisudawan</strong> (role: <strong>admin/tendik</strong>) — sedang dikembangkan.</p>
        <p class="small text-gray-500 mb-3">File: app/Views/graduate/admin_tendik/calon_wisudawan.php</p>
        <a href="<?= base_url('graduate/index') ?>" class="btn btn-secondary btn-sm">Kembali ke Dasbor</a>
    </div>
</div>
<!-- End of Content -->

<?= $this->endSection(); ?>