<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->
<?php
use App\Models\SemesterGraduateModel;

$daftarWisudawan  = $daftarWisudawan ?? [];
$daftarSemester   = $daftarSemester ?? [];
$selectedSemester = $selectedSemester ?? 'SEMUA';
$kataCari         = $kataCari ?? '';

$formatDate = static function (?string $date): string {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $time = strtotime($date);
    return ($time !== false && $time > 0) ? date('d/m/Y', $time) : '-';
};
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-graduation-cap text-primary mr-2"></i>Daftar Mahasiswa Wisudawan</h1>
    <a href="<?= base_url('graduate/admin_tendik') ?>" class="btn btn-sm btn-outline-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm mr-1"></i> Kembali ke Dasbor
    </a>
</div>

<!-- Card Filter: Semester dan Pencarian -->
<div class="card shadow mb-4">
    <div class="card-body py-3">
        <form method="get" action="<?= base_url('graduate/mahasiswa_wisudawan') ?>" class="form-row align-items-end justify-content-between">
            <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                <label class="font-weight-bold text-gray-700 small mb-1" for="filterSemester">Pilih Semester</label>
                <select class="custom-select custom-select-sm" id="filterSemester" name="semester">
                    <option value="SEMUA" <?= ($selectedSemester === 'SEMUA') ? 'selected' : '' ?>>Semua Semester</option>
                    <?php foreach ($daftarSemester as $sem): 
                        $labelSem = SemesterGraduateModel::formatNama($sem['nama'] ?? '', $sem['kode'] ?? '');
                    ?>
                        <option value="<?= esc($sem['kode']) ?>" <?= ($selectedSemester === $sem['kode']) ? 'selected' : '' ?>>
                            <?= esc($labelSem) ?><?= ($sem['status'] ?? '') === 'AKTIF' ? ' (Aktif)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-5 col-sm-6 mb-2 mb-md-0">
                <label class="font-weight-bold text-gray-700 small mb-1" for="cariWisudawan">Cari Mahasiswa</label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="cariWisudawan" name="q" value="<?= esc($kataCari) ?>" placeholder="Cari NPM, nama, atau prodi...">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search fa-sm"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-12 text-md-right mb-2 mb-md-0">
                <button type="submit" class="btn btn-sm btn-primary shadow-sm mr-1">
                    <i class="fas fa-filter fa-sm mr-1"></i> Terapkan
                </button>
                <?php if ($selectedSemester !== 'SEMUA' || $kataCari !== ''): ?>
                    <a href="<?= base_url('graduate/mahasiswa_wisudawan') ?>" class="btn btn-sm btn-outline-secondary shadow-sm">
                        <i class="fas fa-undo fa-sm mr-1"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Card Tabel Mahasiswa Wisudawan -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-sm-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-user-check mr-1"></i> Mahasiswa yang Telah Terverifikasi Wisuda
        </h6>
        <span class="badge badge-success px-3 py-2 font-weight-bold">
            Total: <?= count($daftarWisudawan) ?> Wisudawan
        </span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle" width="100%" cellspacing="0">
                <thead class="thead-light text-center">
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 7%;">Foto</th>
                        <th style="width: 15%;">NPM</th>
                        <th style="width: 25%;">Nama Lengkap</th>
                        <th style="width: 18%;">Program Studi</th>
                        <th style="width: 12%;">Semester</th>
                        <th style="width: 10%;">Tgl. Lulus</th>
                        <th style="width: 8%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarWisudawan)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-info-circle fa-2x d-block mb-2 text-gray-400"></i>
                                Belum ada mahasiswa yang berstatus <strong>DISETUJUI</strong> wisuda pada filter semester ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($daftarWisudawan as $m): ?>
                            <tr>
                                <td class="text-center align-middle font-weight-bold"><?= $no++ ?></td>
                                <td class="text-center align-middle">
                                    <?php if (! empty($m['pas_foto'])): ?>
                                        <img src="<?= base_url($m['pas_foto']) ?>" alt="Foto" class="img-profile rounded-circle" style="width: 38px; height: 38px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center text-secondary border" style="width: 38px; height: 38px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle font-weight-bold text-gray-900"><?= esc($m['npm']) ?></td>
                                <td class="align-middle font-weight-bold text-gray-800"><?= esc($m['nama']) ?></td>
                                <td class="align-middle"><?= esc($m['prodi'] ?? '-') ?></td>
                                <td class="text-center align-middle">
                                    <span class="badge badge-light border text-dark px-2 py-1"><?= esc($m['kode_semester'] ?? '-') ?></span>
                                </td>
                                <td class="text-center align-middle small"><?= $formatDate($m['tanggal_lulus'] ?? null) ?></td>
                                <td class="text-center align-middle">
                                    <span class="badge badge-success px-2 py-1 shadow-sm">
                                        <i class="fas fa-check-circle mr-1"></i> Disetujui
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>
