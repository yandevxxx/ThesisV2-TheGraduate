<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
$terplotModel   = new \App\Models\MahasiswaTerplotModel();
$totalYudisium  = $jumlahYudisium ?? $terplotModel->countAllResults();
$totalAdaSk     = $jumlahYudisiumSelesai ?? (new \App\Models\MahasiswaTerplotModel())->where('tanggal_sk_yudisium IS NOT NULL')->countAllResults();
$totalBelumSk   = $jumlahYudisiumPending ?? (new \App\Models\MahasiswaTerplotModel())->where('tanggal_sk_yudisium IS NULL')->countAllResults();

// Mahasiswa belum mendaftar wisuda
if (isset($jumlahBelumDaftar)) {
    $totalBelumDaftar  = (int) $jumlahBelumDaftar;
    $daftarBelumDaftar = $mahasiswaBelumDaftar ?? [];
} else {
    try {
        $pendaftaranModel = new \App\Models\PendaftaranWisudaModel();
        $registeredNpms   = $pendaftaranModel->getRegisteredNpms();
    } catch (\Throwable $e) {
        $registeredNpms = ['2021010001', '2021010002', '2021010003', '2021010004', '2021010005', '2021010006'];
    }
    $semuaTerplot   = $terplotModel->orderBy('nama', 'ASC')->findAll();
    $daftarBelumDaftar = [];
    foreach ($semuaTerplot as $mhs) {
        if (! in_array((string) ($mhs['npm'] ?? ''), $registeredNpms, true)) {
            $daftarBelumDaftar[] = $mhs;
        }
    }
    $totalBelumDaftar = count($daftarBelumDaftar);
}

$selectedSemester = $_GET['semester'] ?? ($selectedSemester ?? ($semesterAktif['kode'] ?? 'Ganjil 2025/2026'));
$semesterParam    = ($selectedSemester && $selectedSemester !== 'SEMUA') ? '?semester=' . urlencode($selectedSemester) : '';
$data = [
    'Ganjil 2025/2026' => ['pendaftaran' => 28, 'calon' => 20, 'wisudawan' => 15, 'dosen' => 25],
    'Genap 2025/2026'  => ['pendaftaran' => 18, 'calon' => 12, 'wisudawan' => 9,  'dosen' => 22],
    'Ganjil 2024/2025' => ['pendaftaran' => 35, 'calon' => 30, 'wisudawan' => 28, 'dosen' => 24],
    'Genap 2024/2025'  => ['pendaftaran' => 16, 'calon' => 11, 'wisudawan' => 10, 'dosen' => 21],
];
$d = $data[$selectedSemester] ?? ['pendaftaran' => 28, 'calon' => 20, 'wisudawan' => 15, 'dosen' => 25];

$totalPendaftaran = $jumlahPendaftaran ?? ($d['pendaftaran'] ?? 0);
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div class="mb-2 mb-sm-0">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-tachometer-alt text-primary mr-2"></i>Dasbor The Graduate</h1>
    </div>
    <form class="form-inline flex-wrap mb-0" method="get" action="<?= base_url('graduate/admin_tendik') ?>">
        <label class="my-1 mr-2 font-weight-bold text-gray-700" for="semester">Pilih Semester</label>
        <select class="custom-select custom-select-sm my-1 mr-2" id="semester" name="semester">
            <option value="SEMUA" <?= ($selectedSemester === 'SEMUA') ? 'selected' : '' ?>>Semua Semester</option>
            <?php if (! empty($daftarSemester)): ?>
                <?php foreach ($daftarSemester as $sem): ?>
                    <option value="<?= esc($sem['kode']) ?>" <?= ($selectedSemester === $sem['kode'] || ($sem['status'] === 'AKTIF' && empty($_GET['semester']))) ? 'selected' : '' ?>>
                        <?= esc(\App\Models\SemesterGraduateModel::formatNama($sem['nama'], $sem['kode'])) ?><?= ($sem['status'] === 'AKTIF') ? ' (Aktif)' : '' ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="Ganjil 2025/2026" <?= ($selectedSemester === 'Ganjil 2025/2026') ? 'selected' : '' ?>>Ganjil 2025/2026</option>
                <option value="Genap 2025/2026" <?= ($selectedSemester === 'Genap 2025/2026') ? 'selected' : '' ?>>Genap 2025/2026</option>
                <option value="Ganjil 2024/2025" <?= ($selectedSemester === 'Ganjil 2024/2025') ? 'selected' : '' ?>>Ganjil 2024/2025</option>
                <option value="Genap 2024/2025" <?= ($selectedSemester === 'Genap 2024/2025') ? 'selected' : '' ?>>Genap 2024/2025</option>
            <?php endif; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary my-1"><i class="fas fa-filter fa-sm mr-1"></i> Filter</button>
    </form>
</div>

<!-- Content Row: 6 Cards Grid Sesuai Urutan yang Diminta -->
<div class="row">
    <!-- Card 1: Mahasiswa Yudisium -->
    <div class="col-xl-4 col-md-6 mb-4">
        <a href="<?= base_url('graduate/sk_yudisium') ?>" class="text-decoration-none">
            <div class="card shadow h-100 py-2" style="border-left: 0.25rem solid #6f42c1 !important;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: #6f42c1;">
                                Mahasiswa Yudisium
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= (int) $totalYudisium ?> <span class="h6 font-weight-normal text-muted">Mahasiswa</span>
                            </div>
                            <div class="text-xs text-muted mt-2 d-flex align-items-center flex-wrap">
                                <span class="text-success font-weight-bold mr-3">
                                    <i class="fas fa-check-circle mr-1"></i> <?= (int) $totalAdaSk ?> Ada SK
                                </span>
                                <span class="text-secondary font-weight-bold">
                                    <i class="fas fa-clock mr-1"></i> <?= (int) $totalBelumSk ?> Belum SK
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-signature fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 2: Pendaftaran Wisuda -->
    <div class="col-xl-4 col-md-6 mb-4">
        <a href="<?= base_url('graduate/pendaftaran_wisuda' . $semesterParam) ?>" class="text-decoration-none">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Pendaftaran Wisuda</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int) $totalPendaftaran ?> <span class="h6 font-weight-normal text-muted">Berkas</span></div>
                            <div class="text-xs text-muted mt-1">Perlu verifikasi tendik</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-user-plus fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 3: Belum Mendaftar Wisuda -->
    <div class="col-xl-4 col-md-6 mb-4">
        <a href="<?= base_url('graduate/belum_mendaftar' . $semesterParam) ?>" class="text-decoration-none">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Belum Mendaftar Wisuda</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int) $totalBelumDaftar ?> <span class="h6 font-weight-normal text-muted">Mahasiswa</span></div>
                            <div class="text-xs text-muted mt-1"><i class="fas fa-exclamation-circle text-danger mr-1"></i>Sudah terplot, belum mendaftar</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-user-clock fa-2x text-danger opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 4: Calon Wisudawan -->
    <div class="col-xl-4 col-md-6 mb-4">
        <a href="<?= base_url('graduate/calon_wisudawan') ?>" class="text-decoration-none">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Calon Wisudawan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $d['calon'] ?> <span class="h6 font-weight-normal text-muted">Mahasiswa</span></div>
                            <div class="text-xs text-muted mt-1">Siap mendaftar wisuda</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-user-graduate fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 5: Mahasiswa Wisudawan -->
    <div class="col-xl-4 col-md-6 mb-4">
        <a href="<?= base_url('graduate/mahasiswa_wisudawan') ?>" class="text-decoration-none">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Mahasiswa Wisudawan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $d['wisudawan'] ?> <span class="h6 font-weight-normal text-muted">Mahasiswa</span></div>
                            <div class="text-xs text-muted mt-1">Sudah terverifikasi wisuda</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-graduation-cap fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 6: Dosen Pembimbing -->
    <div class="col-xl-4 col-md-6 mb-4">
        <a href="<?= base_url('graduate/dosen_pembimbing') ?>" class="text-decoration-none">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Dosen Pembimbing</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $d['dosen'] ?> <span class="h6 font-weight-normal text-muted">Dosen</span></div>
                            <div class="text-xs text-muted mt-1">Membimbing mahasiswa wisuda</div>
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
