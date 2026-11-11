<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>
<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow border-left-warning">
                <div class="card-body p-4 p-md-5 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                        style="width: 72px; height: 72px; background-color: #fef3c7; color: #d97706;">
                        <i class="fas fa-clipboard-list fa-2x"></i>
                    </div>
                    <h4 class="font-weight-bold text-dark mb-2">Isi Tracer Study Awal Terlebih Dahulu</h4>
                    <p class="text-muted mb-4">
                        Sebelum membuka dan mengisi formulir <strong>Biodata Mahasiswa</strong>, Anda wajib melihat serta mengisi daftar pertanyaan <strong>Tracer Study Awal</strong> terlebih dahulu.
                    </p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center align-items-center">
                        <a href="<?= base_url('graduate/tracer_studi') ?>" class="btn btn-primary px-4 font-weight-bold shadow-sm mb-2 mb-sm-0 mr-sm-2">
                            <i class="fas fa-edit mr-2"></i>Isi Tracer Study Awal Sekarang
                        </a>
                        <a href="<?= base_url('graduate/choose') ?>" class="btn btn-light text-secondary border px-4 shadow-sm">
                            <i class="fas fa-th-large mr-1"></i> Menu Utama
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>
