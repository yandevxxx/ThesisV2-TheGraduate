<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<?php if (session()->getFlashdata("message")) : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= esc(session()->getFlashdata("message")["icon"]); ?></p>
        <p id="title" hidden><?= esc(session()->getFlashdata("message")["title"]); ?></p>
        <p id="text" hidden><?= esc(session()->getFlashdata("message")["text"]); ?></p>
    </div>
<?php endif; ?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= esc($title) ?></h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between" style="background-color: #1C476C;">
                    <h6 class="m-0 font-weight-bold text-white">
                        <i class="fas fa-user-edit mr-2"></i><?= esc($title) ?> (The Graduate)
                    </h6>
                    <span class="badge badge-light px-2 py-1"><?= esc($dataDiri['roleLabel'] ?? 'Pengguna') ?></span>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url("graduate/saveProfile") ?>" method="post" id="formGraduateProfile">
                        <?= csrf_field() ?>

                        <?php if (! empty($dataDiri['extra_label'])) : ?>
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-gray-700"><?= esc($dataDiri['extra_label']) ?></label>
                                <input type="text" class="form-control bg-light" value="<?= esc($dataDiri['extra_value']) ?>" disabled>
                            </div>
                        <?php endif; ?>

                        <?php if (! empty($dataDiri['extra_label2'])) : ?>
                            <div class="form-group mb-3">
                                <label for="inisial" class="font-weight-bold text-gray-700"><?= esc($dataDiri['extra_label2']) ?></label>
                                <input type="text" class="form-control" id="inisial" name="inisial" value="<?= esc($dataDiri['extra_value2']) ?>">
                            </div>
                        <?php endif; ?>

                        <?php if (! empty($dataDiri['prodi']) && $dataDiri['prodi'] !== '-') : ?>
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-gray-700">Program Studi</label>
                                <input type="text" class="form-control bg-light" value="<?= esc($dataDiri['prodi']) ?>" disabled>
                            </div>
                        <?php endif; ?>

                        <?php if (! empty($dataDiri['tanggal_sk_yudisium'])) : ?>
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-gray-700">Tanggal SK Yudisium</label>
                                <input type="text" class="form-control bg-light" value="<?= esc(date('d/m/Y', strtotime($dataDiri['tanggal_sk_yudisium']))) ?>" disabled>
                                <small class="form-text text-muted">Tanggal kelulusan SK Yudisium resmi yang diinput oleh Tenaga Kependidikan.</small>
                            </div>
                        <?php endif; ?>

                        <div class="form-group mb-3">
                            <label for="nama" class="font-weight-bold text-gray-700">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= esc($dataDiri['nama']) ?>" required maxlength="150">
                        </div>

                        <div class="form-group mb-4">
                            <label for="email" class="font-weight-bold text-gray-700">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= esc($dataDiri['email']) ?>" required maxlength="120">
                            <small class="form-text text-muted">Email ini digunakan untuk login dan korespondensi akun Anda.</small>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('graduate/index') ?>" class="btn btn-outline-secondary">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-primary px-4" style="background-color: #1C476C; border-color: #1C476C;">
                                <i class="fas fa-save mr-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>
<script>
$(document).ready(function() {
    if ($("#flashdata").data("open") === true || $("#flashdata").data("open") === "true") {
        var icon = $("#flashdata #icon").text();
        var title = $("#flashdata #title").text();
        var text = $("#flashdata #text").text();
        Swal.fire({
            icon: icon,
            title: title,
            text: text,
            confirmButtonColor: '#1C476C'
        });
    }

    $("#formGraduateProfile").validate({
        rules: {
            nama: {
                required: true,
                maxlength: 150
            },
            email: {
                required: true,
                email: true,
                maxlength: 120
            }
        },
        errorClass: "text-danger small mt-1",
        errorElement: "div",
        highlight: function(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        }
    });
});
</script>
<?= $this->endSection(); ?>

