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
    $registeredNpms = ['2021010001', '2021010002', '2021010003', '2021010004', '2021010005', '2021010006'];
    $semuaTerplot   = $terplotModel->orderBy('nama', 'ASC')->findAll();
    $daftarBelumDaftar = [];
    foreach ($semuaTerplot as $mhs) {
        if (! in_array((string) ($mhs['npm'] ?? ''), $registeredNpms, true)) {
            $daftarBelumDaftar[] = $mhs;
        }
    }
    $totalBelumDaftar = count($daftarBelumDaftar);
}

$selectedSemester = $_GET['semester'] ?? ($semesterAktif['kode'] ?? 'Ganjil 2025/2026');
$data = [
    'Ganjil 2025/2026' => ['pendaftaran' => 28, 'calon' => 20, 'wisudawan' => 15, 'dosen' => 25],
    'Genap 2025/2026'  => ['pendaftaran' => 18, 'calon' => 12, 'wisudawan' => 9,  'dosen' => 22],
    'Ganjil 2024/2025' => ['pendaftaran' => 35, 'calon' => 30, 'wisudawan' => 28, 'dosen' => 24],
    'Genap 2024/2025'  => ['pendaftaran' => 16, 'calon' => 11, 'wisudawan' => 10, 'dosen' => 21],
];
$d = $data[$selectedSemester] ?? ['pendaftaran' => 28, 'calon' => 20, 'wisudawan' => 15, 'dosen' => 25];
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div class="mb-2 mb-sm-0">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-tachometer-alt text-primary mr-2"></i>Dasbor The Graduate</h1>
    </div>
    <form class="form-inline flex-wrap mb-0" method="get" action="<?= base_url('graduate/admin_tendik') ?>">
        <label class="my-1 mr-2 font-weight-bold text-gray-700" for="semester">Pilih Semester</label>
        <select class="custom-select custom-select-sm my-1 mr-2" id="semester" name="semester">
            <?php if (! empty($daftarSemester)): ?>
                <?php foreach ($daftarSemester as $sem): ?>
                    <option value="<?= esc($sem['kode']) ?>" <?= ($selectedSemester === $sem['kode'] || ($sem['status'] === 'AKTIF' && empty($_GET['semester']))) ? 'selected' : '' ?>>
                        <?= esc($sem['nama']) ?> <?= ($sem['status'] === 'AKTIF') ? ' (Aktif)' : '' ?>
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

<!-- Content Row: 5 Cards Grid -->
<div class="row">
    <!-- Card 1: Mahasiswa Yudisium (Realtime dari Database) -->
    <div class="col-xl-4 col-md-6 mb-4">
        <a href="<?= base_url('graduate/sk_yudisium') ?>" class="text-decoration-none">
            <div class="card shadow h-100 py-2" style="border-left: 0.25rem solid #6f42c1 !important;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: #6f42c1;">
                                Mahasiswa Yudisium (Terplot)
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

    <!-- Card 2: Calon Wisudawan -->
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

    <!-- Card 3: Pendaftaran Wisuda yang Masuk -->
    <div class="col-xl-4 col-md-6 mb-4">
        <a href="<?= base_url('graduate/pendaftaran_wisuda') ?>" class="text-decoration-none">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Pendaftaran Wisuda Masuk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $d['pendaftaran'] ?> <span class="h6 font-weight-normal text-muted">Berkas</span></div>
                            <div class="text-xs text-muted mt-1">Perlu verifikasi tendik</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-user-plus fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Card 4: Mahasiswa Wisudawan (Lulus Validasi) -->
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

    <!-- Card 5: Dosen Pembimbing -->
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

    <!-- Card 6: Belum Mendaftar Wisuda -->
    <div class="col-xl-4 col-md-6 mb-4">
        <a href="<?= base_url('graduate/belum_mendaftar') ?>" class="text-decoration-none">
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
</div>

<!-- Card: Daftar Mahasiswa Terplot Belum Mendaftar Wisuda -->
<div class="card shadow mb-4" id="tabelBelumDaftar">
    <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div class="mb-2 mb-md-0">
            <h6 class="m-0 font-weight-bold text-danger">
                <i class="fas fa-user-clock mr-2"></i>Daftar Mahasiswa Terplot Belum Mendaftar Wisuda
            </h6>
            <small class="text-muted">Mahasiswa yang sudah terdaftar di SK Yudisium namun belum mengajukan berkas pendaftaran wisuda.</small>
        </div>
        <div>
            <a href="<?= base_url('graduate/belum_mendaftar') ?>" class="btn btn-sm btn-outline-danger mr-2">
                <i class="fas fa-external-link-alt mr-1"></i> Halaman Lengkap
            </a>
            <span class="badge badge-danger badge-pill px-3 py-2 font-weight-bold">
                <i class="fas fa-exclamation-triangle mr-1"></i> <?= (int) $totalBelumDaftar ?> Belum Daftar
            </span>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($daftarBelumDaftar)): ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-check-circle text-success fa-3x"></i>
                </div>
                <h5 class="text-gray-800 font-weight-bold mb-1">Semua Mahasiswa Terplot Sudah Mendaftar</h5>
                <p class="text-muted mb-0 small">
                    <?= ($totalYudisium > 0) ? 'Tidak ada mahasiswa terplot SK Yudisium yang menunggak pendaftaran wisuda.' : 'Belum ada data mahasiswa terplot SK Yudisium di sistem.' ?>
                </p>
                <?php if ($totalYudisium === 0): ?>
                    <a href="<?= base_url('graduate/sk_yudisium') ?>" class="btn btn-sm btn-primary mt-3">
                        <i class="fas fa-plus mr-1"></i> Plot Mahasiswa SK Yudisium
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3">
                <div class="small text-muted mb-2 mb-sm-0">
                    Menampilkan <strong><?= count($daftarBelumDaftar) ?></strong> mahasiswa yang perlu ditindaklanjuti
                </div>
                <div class="form-inline">
                    <div class="input-group input-group-sm" style="min-width: 250px;">
                        <input type="text" class="form-control" id="searchBelumDaftar" placeholder="Cari NPM atau Nama..." aria-label="Cari mahasiswa belum daftar">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search text-gray-400"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tableBelumDaftar" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th style="width: 5%;">No</th>
                            <th style="width: 15%;">NPM</th>
                            <th>Nama Mahasiswa</th>
                            <th style="width: 12%;">Status</th>
                            <th style="width: 18%;">Tanggal SK Yudisium</th>
                            <th style="width: 18%;">Status Pendaftaran</th>
                            <th style="width: 12%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyBelumDaftar">
                        <?php $no = 1; foreach ($daftarBelumDaftar as $mhs): ?>
                        <tr>
                            <td class="text-center font-weight-bold text-muted"><?= $no++ ?></td>
                            <td class="font-weight-bold text-gray-800"><?= esc($mhs['npm']) ?></td>
                            <td><?= esc($mhs['nama']) ?></td>
                            <td class="text-center">
                                <span class="badge badge-<?= ($mhs['status'] === 'baru') ? 'success' : 'info' ?> px-2 py-1">
                                    <?= ucfirst(esc($mhs['status'])) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if (! empty($mhs['tanggal_sk_yudisium'])): ?>
                                    <span class="text-dark"><i class="far fa-calendar-alt mr-1 text-primary"></i> <?= esc(date('d/m/Y', strtotime($mhs['tanggal_sk_yudisium']))) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-secondary px-2 py-1">Belum Ada SK</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-danger px-2 py-1 font-weight-normal">
                                    <i class="fas fa-times-circle mr-1"></i> Belum Mendaftar
                                </span>
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="<?= base_url('graduate/sk_yudisium?q=' . urlencode($mhs['npm'])) ?>" class="btn btn-outline-primary btn-sm" title="Lihat di SK Yudisium">
                                    <i class="fas fa-external-link-alt fa-sm mr-1"></i> Kelola
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="emptyRowSearch" style="display: none;">
                            <td colspan="7" class="text-center py-4 text-muted">
                                Tidak ditemukan mahasiswa yang cocok dengan kata kunci pencarian.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>
<script>
    $(function () {
        // Live search filter untuk tabel mahasiswa belum mendaftar
        $('#searchBelumDaftar').on('keyup', function () {
            var query = $(this).val().toLowerCase().trim();
            var matched = 0;
            $('#tbodyBelumDaftar tr:not(#emptyRowSearch)').each(function () {
                var text = $(this).text().toLowerCase();
                if (text.indexOf(query) > -1) {
                    $(this).show();
                    matched++;
                } else {
                    $(this).hide();
                }
            });
            if (matched === 0 && query !== '') {
                $('#emptyRowSearch').show();
            } else {
                $('#emptyRowSearch').hide();
            }
        });

        // Smooth scroll saat kartu belum mendaftar diklik
        $('#cardBelumDaftarLink').on('click', function (e) {
            var target = $('#tabelBelumDaftar');
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 500);
            }
        });
    });
</script>
<?= $this->endSection(); ?>
