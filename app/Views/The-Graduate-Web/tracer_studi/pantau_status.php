<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<?php
$rows              = $rows ?? [];
$metrics           = $metrics ?? ['total' => 0, 'sudah' => 0, 'belum' => 0];
$semesterOptions   = $semesterOptions ?? [];
$daftarSemester    = $daftarSemester ?? [];
$kodeSemesterAktif = (string) ($kodeSemesterAktif ?? '');
$defaultSemester   = $kodeSemesterAktif !== '' ? $kodeSemesterAktif : 'semua';
$tablesReady       = $tablesReady ?? true;
$filters           = $filters ?? ['q' => '', 'status' => '', 'semester' => ''];
$semesterAktif     = (string) ($semesterAktif ?? (($filters['semester'] ?? '') !== '' ? $filters['semester'] : $defaultSemester));
$landingUrl        = $landingUrl ?? 'graduate/index';

if ($daftarSemester === [] && $semesterOptions !== []) {
    foreach ($semesterOptions as $semKode) {
        $semKode = (string) $semKode;
        if ($semKode !== '') {
            $daftarSemester[] = [
                'kode'   => $semKode,
                'nama'   => \App\Models\SemesterGraduateModel::formatNama(null, $semKode),
                'status' => '',
            ];
        }
    }
}

$flashMessage = session()->getFlashdata('message');
$flashIcon    = is_array($flashMessage) ? ($flashMessage['icon'] ?? 'info') : '';
$flashTitle   = is_array($flashMessage) ? ($flashMessage['title'] ?? '') : '';
$flashText    = is_array($flashMessage) ? ($flashMessage['text'] ?? '') : '';
?>

<?php if ($flashText !== '') : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= esc($flashIcon) ?></p>
        <p id="title" hidden><?= esc($flashTitle) ?></p>
        <p id="text" hidden><?= esc($flashText) ?></p>
    </div>
    <?php
    $alertClass = match ($flashIcon) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };
    ?>
    <div class="alert <?= $alertClass ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php if ($flashTitle !== '') : ?>
            <strong><?= esc($flashTitle) ?>:</strong>
        <?php endif; ?>
        <?= esc($flashText) ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">
            <i class="fas fa-tasks text-primary mr-2"></i>Pantau Status Pengisian Tracer Study Awal
        </h1>
        <p class="mb-0 text-muted small">
            Pemantauan mahasiswa terplot SK Yudisium yang sudah dan belum mengisi form Tracer Study Awal.
        </p>
    </div>
    <div class="mt-2 mt-sm-0">
        <a href="<?= base_url('graduate/pertanyaan_tracer_studi') ?>" class="btn btn-sm btn-outline-primary shadow-sm mr-1">
            <i class="fas fa-clipboard-list fa-sm mr-1"></i> Kelola Pertanyaan
        </a>
        <a href="<?= base_url($landingUrl) ?>" class="btn btn-sm btn-outline-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> Dasbor
        </a>
    </div>
</div>

<?php if (! $tablesReady) : ?>
    <div class="alert alert-warning shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Tabel <code>jawaban_tracer_studi_awal</code> belum dimigrasi. Status seluruh mahasiswa saat ini terbaca sebagai <strong>Belum</strong> hingga migrasi dijalankan.
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-4 mb-3 mb-md-0">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body py-2">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Mahasiswa Terplot</div>
                <div class="h4 mb-0 font-weight-bold text-gray-800"><?= (int) ($metrics['total'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body py-2">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sudah Mengisi Tracer Awal</div>
                <div class="h4 mb-0 font-weight-bold text-success"><?= (int) ($metrics['sudah'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body py-2">
                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Belum Mengisi Tracer Awal</div>
                <div class="h4 mb-0 font-weight-bold text-danger"><?= (int) ($metrics['belum'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between bg-white">
        <h6 class="m-0 font-weight-bold text-primary mb-2 mb-lg-0">
            <i class="fas fa-users mr-1"></i> Daftar Mahasiswa Terplot &amp; Status Pengisian (<?= count($rows) ?> Ditampilkan)
        </h6>
        <form action="<?= base_url('graduate/tracer_studi_pantau') ?>" method="get" class="form-inline flex-wrap">
            <input
                type="text"
                name="q"
                class="form-control form-control-sm mr-2 my-1"
                placeholder="Cari NPM atau Nama..."
                value="<?= esc((string) ($filters['q'] ?? '')) ?>"
            >
            <select name="status" class="custom-select custom-select-sm mr-2 my-1" aria-label="Filter status pengisian">
                <option value="">Semua Status</option>
                <option value="sudah" <?= ($filters['status'] ?? '') === 'sudah' ? 'selected' : '' ?>>Sudah Mengisi</option>
                <option value="belum" <?= ($filters['status'] ?? '') === 'belum' ? 'selected' : '' ?>>Belum Mengisi</option>
            </select>
            <select name="semester" class="custom-select custom-select-sm mr-2 my-1" aria-label="Filter semester">
                <option value="semua" <?= ($semesterAktif === 'semua') ? 'selected' : '' ?>>Semua Semester</option>
                <?php foreach ($daftarSemester as $sem) :
                    $kodeSem  = (string) ($sem['kode'] ?? '');
                    $labelSem = \App\Models\SemesterGraduateModel::formatNama($sem['nama'] ?? '', $kodeSem);
                    $isAktif  = ($sem['status'] ?? '') === 'AKTIF';
                ?>
                    <option value="<?= esc($kodeSem) ?>" <?= ($semesterAktif === $kodeSem) ? 'selected' : '' ?>>
                        <?= esc($labelSem) ?><?= $isAktif ? ' (Aktif)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary my-1 mr-1">
                <i class="fas fa-search fa-xs mr-1"></i> Filter
            </button>
            <?php if (($filters['q'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || $semesterAktif !== $defaultSemester) : ?>
                <a href="<?= base_url('graduate/tracer_studi_pantau') ?>" class="btn btn-sm btn-light border my-1">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 14%;">NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th style="width: 16%;">Program Studi</th>
                        <th style="width: 10%;">Status SK</th>
                        <th style="width: 11%;">Semester</th>
                        <th style="width: 14%;">Status Tracer</th>
                        <th style="width: 14%;">Jawaban</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Tidak ada data mahasiswa terplot yang sesuai filter.
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($rows as $idx => $row) : ?>
                            <?php $sudah = ! empty($row['sudah_mengisi']); ?>
                            <tr>
                                <td class="text-center align-middle"><?= $idx + 1 ?></td>
                                <td class="text-center align-middle font-weight-bold"><?= esc($row['npm'] ?? '') ?></td>
                                <td class="align-middle"><?= esc($row['nama'] ?? '') ?></td>
                                <td class="align-middle"><?= esc($row['nama_prodi'] ?? '-') ?></td>
                                <td class="text-center align-middle">
                                    <?php $mhsStatus = strtolower((string) ($row['status_mahasiswa'] ?? '')); ?>
                                    <span class="badge badge-<?= $mhsStatus === 'baru' ? 'info' : 'secondary' ?> px-2 py-1">
                                        <?= $mhsStatus !== '' ? esc(ucfirst($mhsStatus)) : '-' ?>
                                    </span>
                                </td>
                                <td class="text-center align-middle"><?= esc(($row['kode_semester'] ?? '') !== '' ? $row['kode_semester'] : '-') ?></td>
                                <td class="text-center align-middle">
                                    <?php if ($sudah) : ?>
                                        <span class="badge badge-success px-2 py-1">
                                            <i class="fas fa-check-circle mr-1"></i>Sudah
                                        </span>
                                        <?php if (! empty($row['submitted_at'])) : ?>
                                            <div class="small text-muted mt-1"><?= esc($row['submitted_at']) ?></div>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="badge badge-danger px-2 py-1">
                                            <i class="fas fa-clock mr-1"></i>Belum
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle">
                                    <?php if ($sudah) : ?>
                                        <a href="<?= base_url('graduate/tracer_studi_pantau/detail/' . rawurlencode((string) $row['npm'])) ?>"
                                            class="btn btn-sm btn-primary shadow-sm">
                                            <i class="fas fa-eye fa-xs mr-1"></i> Lihat Jawaban
                                        </a>
                                    <?php else : ?>
                                        <span class="text-muted small">Belum ada jawaban</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>
<script>
    $(function () {
        var flashData = $('#flashdata');
        if (flashData.length && flashData.data('open')) {
            var icon  = $('#icon').text();
            var title = $('#title').text();
            var text  = $('#text').text();
            if (text && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: icon || 'info',
                    title: title || 'Informasi',
                    text: text,
                    confirmButtonColor: '#1C476C'
                });
            }
        }
    });
</script>
<?= $this->endSection(); ?>
