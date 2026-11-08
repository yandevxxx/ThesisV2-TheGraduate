<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<?php
$formatSemester = static function (?string $kode, array $map = []): string {
    if (empty($kode)) {
        return '-';
    }
    if (! empty($map[$kode])) {
        return $map[$kode];
    }
    return \App\Models\SemesterGraduateModel::formatNama(null, $kode);
};

$formatDate = static function (?string $date, string $format = 'd/m/Y'): ?string {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return null;
    }
    $ts = strtotime($date);
    return ($ts !== false && $ts > 0) ? date($format, $ts) : null;
};

$canManageSk = \App\Libraries\AccessControl::hasAnyRole($role ?? [], ['tendik', 'staf', 'admin']);
$kodeSemesterAktif = $kodeSemesterAktif ?? '';
$hasActiveFilter = (
    ($filters['q'] ?? '') !== '' ||
    ($filters['status'] ?? '') !== '' ||
    ($filters['sk'] ?? '') !== '' ||
    (($filters['semester'] ?? '') !== ($kodeSemesterAktif !== '' ? $kodeSemesterAktif : 'SEMUA'))
);

$flashMessage = session()->getFlashdata("message");
$flashError   = session()->getFlashdata("error");
$flashSuccess = session()->getFlashdata("success");
?>

<!-- Begin of Content -->
<div class="mb-4">
    <h1 class="h3 mb-0 text-gray-800">Mahasiswa Belum Mendaftar Wisuda</h1>
    <p class="text-muted small mb-0">Daftar mahasiswa yang telah di-plot dalam SK Yudisium namun belum mengajukan berkas pendaftaran wisuda.</p>
</div>

<?php if ($flashMessage) : ?>
<?php
    if (is_array($flashMessage)) {
        $msgIcon  = $flashMessage['icon'] ?? 'info';
        $msgTitle = $flashMessage['title'] ?? '';
        $msgText  = $flashMessage['text'] ?? '';
    } else {
        $msgIcon  = 'info';
        $msgTitle = '';
        $msgText  = (string) $flashMessage;
    }
    $alertClass = match ($msgIcon) {
        'success' => 'success',
        'warning' => 'warning',
        'info'    => 'info',
        default   => 'danger',
    };
?>
<div class="alert alert-<?= $alertClass ?> alert-dismissible fade show" role="alert">
    <?php if ($msgTitle !== '') : ?><strong><?= esc($msgTitle) ?></strong> <?php endif; ?>
    <?= esc($msgText) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<?php if ($flashError) : ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Gagal!</strong> <?= esc(is_array($flashError) ? implode(', ', $flashError) : (string) $flashError) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<?php if ($flashSuccess) : ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Berhasil!</strong> <?= esc((string) $flashSuccess) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<!-- Card Tabel Mahasiswa Belum Mendaftar -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-danger">
            <i class="fas fa-user-clock mr-2"></i>Daftar Mahasiswa Terplot Belum Mendaftar Wisuda
        </h6>
        <span class="badge badge-danger badge-pill px-3 py-2 font-weight-bold">
            <i class="fas fa-exclamation-triangle mr-1"></i> <?= count($daftarBelumDaftar) ?> Mahasiswa
        </span>
    </div>
    <div class="card-body">

        <!-- Filter Box -->
        <div class="bg-light border rounded p-3 mb-3">
            <form action="<?= base_url('graduate/belum_mendaftar') ?>" method="get" class="form-inline flex-wrap">
                <div class="input-group input-group-sm mr-2 my-1" style="min-width: 240px;">
                    <input type="text" class="form-control" name="q" id="filterQ" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Cari NPM atau nama...">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                </div>
                <select class="custom-select custom-select-sm mr-2 my-1" name="status" style="width: 140px;">
                    <option value="">Semua Status</option>
                    <option value="baru" <?= (($filters['status'] ?? '') === 'baru') ? 'selected' : '' ?>>Baru</option>
                    <option value="transfer" <?= (($filters['status'] ?? '') === 'transfer') ? 'selected' : '' ?>>Transfer</option>
                </select>
                <select class="custom-select custom-select-sm mr-2 my-1" name="sk" style="width: 160px;">
                    <option value="">Semua SK</option>
                    <option value="ada" <?= (($filters['sk'] ?? '') === 'ada') ? 'selected' : '' ?>>Sudah Ada SK</option>
                    <option value="belum" <?= (($filters['sk'] ?? '') === 'belum') ? 'selected' : '' ?>>Belum Ada SK</option>
                </select>
                <select class="custom-select custom-select-sm mr-2 my-1" name="semester" style="min-width: 210px;">
                    <option value="SEMUA" <?= (($filters['semester'] ?? '') === 'SEMUA') ? 'selected' : '' ?>>Semua Semester</option>
                    <?php if (! empty($daftarSemester)): ?>
                        <?php foreach ($daftarSemester as $sem): ?>
                            <option value="<?= esc($sem['kode']) ?>" <?= (($filters['semester'] ?? '') === $sem['kode']) ? 'selected' : '' ?>>
                                <?= esc(\App\Models\SemesterGraduateModel::formatNama($sem['nama'] ?? null, $sem['kode'] ?? null)) ?><?= (($sem['status'] ?? '') === 'AKTIF') ? ' (Aktif)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary my-1 mr-1">
                    <i class="fas fa-filter fa-fw"></i> Filter
                </button>
                <?php if ($hasActiveFilter): ?>
                    <a href="<?= base_url('graduate/belum_mendaftar') ?>" class="btn btn-sm btn-link my-1">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($daftarBelumDaftar)): ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-check-circle text-success fa-3x"></i>
                </div>
                <h5 class="text-gray-800 font-weight-bold mb-1">
                    <?= $hasActiveFilter ? 'Tidak Ada Data yang Cocok' : 'Semua Mahasiswa Terplot Sudah Mendaftar' ?>
                </h5>
                <p class="text-muted mb-0 small">
                    <?= $hasActiveFilter ? 'Silakan ubah filter atau kata kunci pencarian Anda.' : 'Luar biasa! Tidak ada mahasiswa terplot SK Yudisium yang menunggak pendaftaran wisuda.' ?>
                </p>
                <?php if ($hasActiveFilter): ?>
                    <a href="<?= base_url('graduate/belum_mendaftar') ?>" class="btn btn-sm btn-outline-primary mt-3">Reset Filter</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tableBelumDaftar" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th style="width: 5%;">No</th>
                            <th style="width: 13%;">NPM</th>
                            <th>Nama Mahasiswa</th>
                            <th style="width: 15%;">Semester</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 15%;">Tanggal SK Yudisium</th>
                            <th style="width: 15%;">Status Pendaftaran</th>
                            <th style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyBelumDaftar">
                        <?php $no = 1; foreach ($daftarBelumDaftar as $mhs): ?>
                        <tr>
                            <td class="text-center font-weight-bold text-muted"><?= $no++ ?></td>
                            <td class="font-weight-bold text-gray-800"><?= esc($mhs['npm']) ?></td>
                            <td><?= esc($mhs['nama']) ?></td>
                            <td class="text-center">
                                <?php if (! empty($mhs['kode_semester'])): ?>
                                    <span class="badge badge-info px-2 py-1 font-weight-normal">
                                        <?= esc($formatSemester($mhs['kode_semester'], $semesterMap ?? [])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-<?= ($mhs['status'] === 'baru') ? 'success' : 'info' ?> px-2 py-1">
                                    <?= ucfirst(esc($mhs['status'])) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php $tglSk = $formatDate($mhs['tanggal_sk_yudisium'] ?? null); ?>
                                <?php if ($tglSk !== null): ?>
                                    <span class="text-dark"><i class="far fa-calendar-alt mr-1 text-primary"></i> <?= esc($tglSk) ?></span>
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
                                <?php if ($canManageSk): ?>
                                    <a href="<?= base_url('graduate/sk_yudisium?q=' . urlencode($mhs['npm'])) ?>" class="btn btn-outline-primary btn-sm" title="Lihat di SK Yudisium">
                                        <i class="fas fa-external-link-alt fa-sm mr-1"></i> Kelola
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="emptyRowClientSearch" style="display: none;">
                            <td colspan="8" class="text-center py-4 text-muted">
                                Tidak ditemukan data mahasiswa yang cocok dengan pencarian instan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-muted small mt-3">
                Menampilkan <strong><?= count($daftarBelumDaftar) ?></strong> dari <strong><?= (int) $totalBelumDaftar ?></strong> mahasiswa belum mendaftar.
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>
<script>
    $(function () {
        // Live client-side instant filter saat mengetik di input pencarian
        $('#filterQ').on('keyup', function () {
            var query = $(this).val().toLowerCase().trim();
            var matched = 0;
            $('#tbodyBelumDaftar tr:not(#emptyRowClientSearch)').each(function () {
                var text = $(this).text().toLowerCase();
                if (text.indexOf(query) > -1) {
                    $(this).show();
                    matched++;
                } else {
                    $(this).hide();
                }
            });
            if (matched === 0 && query !== '') {
                $('#emptyRowClientSearch').show();
            } else {
                $('#emptyRowClientSearch').hide();
            }
        });
    });
</script>
<?= $this->endSection(); ?>

