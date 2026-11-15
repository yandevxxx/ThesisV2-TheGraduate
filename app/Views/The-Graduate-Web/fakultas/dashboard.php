<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-university text-primary mr-2"></i>Dasbor Fakultas</h1>
</div>

<?php
$semesterAktif = (new \App\Models\SemesterGraduateModel())->getActiveSemester();
$namaSemesterAktif = $semesterAktif ? $semesterAktif['nama'] : 'Belum Ditentukan';
?>

<div class="row">
    <!-- Semester Aktif Card -->
    <div class="col-xl-6 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Semester Aktif
                        </div>
                        <div class="h6 mb-0 font-weight-bold text-gray-800"><?= esc($namaSemesterAktif) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modul Kelola Semester Card -->
    <div class="col-xl-6 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Pengelolaan Periode
                        </div>
                        <div class="h6 mb-0 font-weight-bold text-gray-800">
                            <a href="<?= base_url('graduate/semester') ?>" class="text-success text-decoration-none">
                                Kelola Semester &raquo;
                            </a>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle mr-1"></i> Selamat Datang di Sisi The Graduate</h6>
    </div>
    <div class="card-body">
        <p class="mb-2">Sebagai perwakilan <strong>Fakultas</strong>, Anda memiliki wewenang untuk:</p>
        <ul class="mb-3">
            <li><strong>Kelola Semester:</strong> Menambah, mengaktifkan, dan menonaktifkan periode akademik yang berlaku di The Graduate dan Thesis.</li>
            <li><strong>Tracer Study:</strong> Mengelola pertanyaan, bank pertanyaan, serta memantau pengisian kuesioner Tracer Study mahasiswa dari seluruh program studi.</li>
        </ul>
        <a href="<?= base_url('graduate/semester') ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-calendar-alt fa-fw mr-1"></i> Buka Pengaturan Semester
        </a>
        <a href="<?= base_url('graduate/tracer_studi_pantau') ?>" class="btn btn-outline-primary btn-sm ml-2">
            <i class="fas fa-clipboard-list fa-fw mr-1"></i> Pantau Tracer Study
        </a>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

