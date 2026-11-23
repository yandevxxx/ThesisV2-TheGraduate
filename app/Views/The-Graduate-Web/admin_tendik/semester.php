<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-calendar-alt text-primary mr-2"></i>Semester
        </h1>
        <p class="mb-0 text-muted small">
            Daftar periode semester akademik aktif dan riwayat semester pada sistem The Graduate.
        </p>
    </div>
</div>

<?php
use App\Models\SemesterGraduateModel;

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
<div class="alert alert-<?= $alertClass ?> alert-dismissible fade show shadow-sm" role="alert">
    <?php if ($msgTitle !== '') : ?><strong><?= esc($msgTitle) ?>:</strong> <?php endif; ?>
    <?= esc($msgText) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php elseif ($flashError) : ?>
<div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
    <strong>Gagal!</strong> <?= esc($flashError) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php elseif ($flashSuccess) : ?>
<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
    <strong>Berhasil!</strong> <?= esc($flashSuccess) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between bg-white">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list mr-1"></i> Daftar Semester
        </h6>
        <span class="badge badge-light border text-muted px-2 py-1">
            <i class="fas fa-info-circle mr-1 text-info"></i> Mode Lihat (Read-Only)
        </span>
    </div>
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="tableSemesterTendik" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 8%;">No</th>
                        <th style="width: 62%;">Semester</th>
                        <th style="width: 30%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $dataList = $semester ?? $daftarSemester ?? [];
                    if (empty($dataList)): 
                    ?>
                    <tr class="text-center">
                        <td colspan="3" class="text-gray-500 py-4">
                            <i class="fas fa-info-circle mr-1"></i> Tidak ada data semester.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php 
                    $startNo = isset($currentPage, $perPage) ? ((($currentPage - 1) * $perPage) + 1) : 1;
                    foreach ($dataList as $s): 
                        $isAktif     = ($s['status'] === 'AKTIF');
                        $labelFormat = SemesterGraduateModel::formatNama($s['nama'], $s['kode']);
                    ?>
                    <tr class="text-center align-middle">
                        <td><?= $startNo++ ?></td>
                        <td class="text-left font-weight-bold text-gray-800">
                            <?= esc($labelFormat) ?>
                            <span class="badge badge-light border text-muted ml-1"><?= esc($s['kode']) ?></span>
                        </td>
                        <td>
                            <?php if ($isAktif): ?>
                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-secondary px-2 py-1">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                    </tr>    
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginasi Server-side khas The Graduate -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
            <div class="text-muted small">
                Menampilkan <?= count($dataList) ?> dari <?= (int) ($totalRows ?? count($dataList)) ?> data semester
            </div>
            <div>
                <?php if (isset($pager)) : ?>
                    <?= $pager->links('semester', 'bootstrap_pagination') ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>
