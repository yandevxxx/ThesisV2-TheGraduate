<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
$semester = $_GET['semester'] ?? 'Ganjil 2025/2026';
$data = [
    'Ganjil 2025/2026' => ['pendaftaran' => 28, 'calon' => 20, 'wisudawan' => 15, 'dosen' => 25],
    'Genap 2025/2026'  => ['pendaftaran' => 18, 'calon' => 12, 'wisudawan' => 9,  'dosen' => 22],
    'Ganjil 2024/2025' => ['pendaftaran' => 35, 'calon' => 30, 'wisudawan' => 28, 'dosen' => 24],
    'Genap 2024/2025'  => ['pendaftaran' => 16, 'calon' => 11, 'wisudawan' => 10, 'dosen' => 21],
];
$d = $data[$semester] ?? ['pendaftaran' => 0, 'calon' => 0, 'wisudawan' => 0, 'dosen' => 0];
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div class="mb-2 mb-sm-0">
        <h1 class="h3 mb-0 text-gray-800">Dasbor The Graduate</h1>
    </div>
    <form class="form-inline flex-wrap mb-0" method="get" action="<?= base_url('graduate/index') ?>">
        <label class="my-1 mr-2 font-weight-bold text-gray-700" for="semester">Pilih Semester</label>
        <select class="custom-select custom-select-sm my-1 mr-2" id="semester" name="semester">
            <option value="Ganjil 2025/2026" <?= ($semester === 'Ganjil 2025/2026') ? 'selected' : '' ?>>Ganjil 2025/2026</option>
            <option value="Genap 2025/2026" <?= ($semester === 'Genap 2025/2026') ? 'selected' : '' ?>>Genap 2025/2026</option>
            <option value="Ganjil 2024/2025" <?= ($semester === 'Ganjil 2024/2025') ? 'selected' : '' ?>>Ganjil 2024/2025</option>
            <option value="Genap 2024/2025" <?= ($semester === 'Genap 2024/2025') ? 'selected' : '' ?>>Genap 2024/2025</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary my-1">Filter</button>
    </form>
</div>

<!-- Content Row -->
<div class="row">
    <!-- Card 1 -->
    <div class="col-xl-3 col-md-6 col-sm-6 col-12 mb-4">
        <a href="<?= base_url('graduate/pendaftaran_wisuda') ?>" class="text-decoration-none">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Pendaftaran Wisuda yang masuk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $d['pendaftaran'] ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-user-plus fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 2 -->
    <div class="col-xl-3 col-md-6 col-sm-6 col-12 mb-4">
        <a href="<?= base_url('graduate/calon_wisudawan') ?>" class="text-decoration-none">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Calon Wisudawan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $d['calon'] ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-user-graduate fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 3 -->
    <div class="col-xl-3 col-md-6 col-sm-6 col-12 mb-4">
        <a href="<?= base_url('graduate/calon_wisudawan') ?>" class="text-decoration-none">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Mahasiswa Wisudawan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $d['wisudawan'] ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-graduation-cap fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 4 -->
    <div class="col-xl-3 col-md-6 col-sm-6 col-12 mb-4">
        <a href="<?= base_url('graduate/dosen_pembimbing') ?>" class="text-decoration-none">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Dosen Pembimbing</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $d['dosen'] ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>
