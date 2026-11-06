<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<?php
$formatDate = static function (?string $date, string $format = 'd/m/Y'): string {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    $ts = strtotime($date);
    return ($ts !== false && $ts > 0) ? date($format, $ts) : '-';
};
?>

<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-4">
    <h1 class="h3 mb-2 mb-sm-0 text-gray-800">Pratinjau Daftar Terplot SK Yudisium</h1>
    <div>
        <form action="<?= base_url('graduate/skYudisiumClearPreview') ?>" method="post" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left fa-fw"></i> Kembali</button>
        </form>
    </div>
</div>

<?php
$flashMessage = session()->getFlashdata("message");
$flashError   = session()->getFlashdata("error");
$flashSuccess = session()->getFlashdata("success");
?>

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

<?php 
$targetSemesterLabel = $preview['semester_nama'] 
    ?? (!empty($semesterAktif) ? \App\Models\SemesterGraduateModel::formatNama($semesterAktif['nama'] ?? null, $semesterAktif['kode'] ?? null) : 'Semester Berjalan');
?>
<div class="alert alert-info py-2 mb-3 shadow-sm d-flex align-items-center justify-content-between flex-wrap">
    <div>
        <i class="fas fa-calendar-alt text-primary mr-2"></i>
        <span>Semester Kelulusan Tujuan Plotting: <strong><?= esc($targetSemesterLabel) ?> (Sedang Aktif)</strong></span>
    </div>
    <span class="badge badge-primary px-2 py-1 mt-1 mt-sm-0">Semester Aktif</span>
</div>

<div class="row mb-3">
    <div class="col-12 col-sm-4 mb-3 mb-sm-0">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Data Valid</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int) ($preview['countValid'] ?? count($preview['valid'] ?? [])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3 mb-sm-0">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Data Bermasalah</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int) ($preview['countError'] ?? count($preview['errors'] ?? [])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4 mb-3 mb-sm-0">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Baris Dibaca</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int) (($preview['countValid'] ?? 0) + ($preview['countError'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
        <h6 class="m-0 font-weight-bold text-success">Data Valid (Dapat Disimpan)</h6>
        <?php if (! empty($preview['valid'])) : ?>
        <form action="<?= base_url('graduate/skYudisiumSave') ?>" method="post" class="mt-2 mt-md-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save fa-fw"></i> Simpan Yang Valid</button>
        </form>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 13%;">NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th style="width: 18%;">Semester</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 16%;">Tanggal Yudisium</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($preview['valid'])) : ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Tidak ada data valid</td>
                    </tr>
                    <?php else : ?>
                    <?php $no = 1; foreach ($preview['valid'] as $row) : ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= esc($row['npm']) ?></td>
                        <td><?= esc($row['nama']) ?></td>
                        <td class="text-center"><span class="badge badge-info px-2 py-1 font-weight-normal"><?= esc($targetSemesterLabel) ?></span></td>
                        <td class="text-center">
                            <span class="badge badge-<?= ($row['status'] === 'baru') ? 'success' : 'info' ?> px-2 py-1"><?= ucfirst(esc($row['status'])) ?></span>
                        </td>
                        <td class="text-center">
                            <?= ($formatDate($row['tanggal_sk_yudisium'] ?? null) !== '-') ? esc($formatDate($row['tanggal_sk_yudisium'] ?? null)) : '<span class="text-muted">-</span>' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-danger">Data Bermasalah (Tidak Disimpan)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 8%;">Baris Excel</th>
                        <th style="width: 15%;">NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th style="width: 12%;">Status</th>
                        <th>Penyebab</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($preview['errors'])) : ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Tidak ada data bermasalah</td>
                    </tr>
                    <?php else : ?>
                    <?php foreach ($preview['errors'] as $err) : ?>
                    <tr>
                        <td class="text-center"><?= esc($err['baris']) ?></td>
                        <td><?= esc($err['npm']) ?></td>
                        <td><?= esc($err['nama']) ?></td>
                        <td class="text-center"><?= esc($err['status']) ?></td>
                        <td class="text-danger font-weight-bold"><?= esc($err['pesan']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>
