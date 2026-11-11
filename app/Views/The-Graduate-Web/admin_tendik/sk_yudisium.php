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

$formatDate = static function (?string $date, string $format = 'd/m/Y'): string {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    $ts = strtotime($date);
    return ($ts !== false && $ts > 0) ? date($format, $ts) : '-';
};

$defaultSemester = !empty($semesterAktif['kode']) ? $semesterAktif['kode'] : 'SEMUA';
$isFiltered      = (($filters['q'] ?? '') !== '') 
    || (($filters['status'] ?? '') !== '') 
    || (($filters['semester'] ?? '') !== $defaultSemester);
$currentQueryString = http_build_query(array_filter([
    'semester' => $filters['semester'] ?? '',
    'status'   => $filters['status'] ?? '',
    'q'        => $filters['q'] ?? '',
], static fn ($v) => $v !== ''));
?>

<!-- Begin of Content -->

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">SK Yudisium</h1>
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

<!-- Card Daftar Mahasiswa Terplot SK Yudisium -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Mahasiswa Terplot SK Yudisium</h6>
    </div>
    <div class="card-body">
        <div class="bg-light border rounded p-2 mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <form action="<?= base_url('graduate/sk_yudisium') ?>" method="get" class="form-inline my-1">
                    <div class="input-group input-group-sm mr-2 my-1" style="min-width: 200px; max-width: 100%;">
                        <input type="text" class="form-control" id="filterQ" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Cari NPM / nama mahasiswa..." aria-label="Cari NPM atau nama mahasiswa">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                    <select class="custom-select custom-select-sm mr-2 my-1" id="filterSemester" name="semester" style="min-width: 210px;" aria-label="Filter semester">
                        <option value="SEMUA" <?= (($filters['semester'] ?? '') === 'SEMUA') ? 'selected' : '' ?>>Semua Semester</option>
                        <?php if (!empty($daftarSemester)) : ?>
                            <?php foreach ($daftarSemester as $sem) : ?>
                                <?php $semLabel = $semesterMap[$sem['kode']] ?? $formatSemester($sem['kode'], $semesterMap ?? []); ?>
                                <option value="<?= esc($sem['kode']) ?>" <?= ($filters['semester'] ?? '') === $sem['kode'] ? 'selected' : '' ?>>
                                    <?= esc($semLabel) ?><?= (($sem['status'] ?? '') === 'AKTIF') ? ' (Aktif)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <select class="custom-select custom-select-sm mr-2 my-1" id="filterStatus" name="status" style="width: 130px;" aria-label="Filter status">
                        <option value="">Semua Status</option>
                        <option value="baru" <?= ($filters['status'] ?? '') === 'baru' ? 'selected' : '' ?>>Baru</option>
                        <option value="transfer" <?= ($filters['status'] ?? '') === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary my-1"><i class="fas fa-filter fa-fw"></i> Filter</button>
                    <?php if ($isFiltered) : ?>
                    <a href="<?= base_url('graduate/sk_yudisium') ?>" class="btn btn-sm btn-link my-1 ml-1">Reset</a>
                    <?php endif; ?>
                </form>
                <div class="my-1">
                    <div class="dropdown d-inline-block my-1 mr-1">
                        <button class="btn btn-sm btn-info dropdown-toggle" type="button" id="btnBulkAction" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" disabled>
                            <i class="fas fa-tasks fa-fw mr-1"></i> Aksi Massal (<span id="bulkCount">0</span>)
                        </button>
                        <div class="dropdown-menu shadow animated--fade-in" aria-labelledby="btnBulkAction">
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalBulkEdit">
                                <i class="fas fa-edit fa-fw mr-2 text-info"></i> Edit Massal
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#" data-toggle="modal" data-target="#modalBulkDelete">
                                <i class="fas fa-trash fa-fw mr-2 text-danger"></i> Hapus Massal
                            </a>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary my-1 mr-1" data-toggle="modal" data-target="#modalTambahTerplot"><i class="fas fa-user-plus fa-fw"></i> Tambah Mahasiswa</button>
                    <a href="<?= base_url('graduate/skYudisiumDownloadTemplate') ?>" class="btn btn-sm btn-outline-primary my-1 mr-1"><i class="fas fa-file-download fa-fw"></i> Download Template</a>
                    <button type="button" class="btn btn-sm btn-success my-1" data-toggle="modal" data-target="#modalUnggahPlotYudisium" title="Unggah daftar terplot (.xlsx)"><i class="fas fa-file-upload fa-fw"></i> Unggah Excel</button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 4%;"><input type="checkbox" id="checkAll" title="Pilih Semua"></th>
                        <th style="width: 5%;">No</th>
                        <th style="width: 11%;">NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th style="width: 11%;">Semester</th>
                        <th style="width: 9%;">Status</th>
                        <th style="width: 14%;">Tanggal SK Yudisium</th>
                        <th style="width: 15%;">Diunggah Pada</th>
                        <th style="width: 16%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarTerplot)) : ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            <?= $isFiltered ? 'Tidak ada data mahasiswa terplot yang cocok dengan filter' : 'Belum ada data mahasiswa terplot' ?>
                        </td>
                    </tr>
                    <?php else : ?>
                    <?php
                    $startNo = isset($currentPage, $perPage) ? ((($currentPage - 1) * $perPage) + 1) : 1;
                    foreach ($daftarTerplot as $m) :
                    ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="check-npm" value="<?= esc($m['npm']) ?>" data-nama="<?= esc($m['nama']) ?>">
                        </td>
                        <td class="text-center"><?= $startNo++ ?></td>
                        <td><?= esc($m['npm']) ?></td>
                        <td><?= esc($m['nama']) ?></td>
                        <td class="text-center">
                            <?php if (!empty($m['kode_semester'])) : ?>
                                <span class="badge badge-info px-2 py-1 font-weight-normal"><?= esc($formatSemester($m['kode_semester'], $semesterMap ?? [])) ?></span>
                            <?php else : ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-<?= ($m['status'] === 'baru') ? 'success' : 'info' ?> px-2 py-1"><?= ucfirst(esc($m['status'])) ?></span>
                        </td>
                        <td class="text-center"><?= esc($formatDate($m['tanggal_sk_yudisium'] ?? null, 'd/m/Y')) ?></td>
                        <td class="text-center"><?= esc($formatDate($m['uploaded_at'] ?? null, 'd/m/Y H:i')) ?></td>
                        <td class="text-center text-nowrap">
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditStatusTerplot" data-id="<?= esc($m['id']) ?>" data-npm="<?= esc($m['npm']) ?>" data-nama="<?= esc($m['nama']) ?>" data-status="<?= esc($m['status']) ?>" data-semester="<?= esc($m['kode_semester'] ?? '') ?>" data-tanggal="<?= esc($m['tanggal_sk_yudisium'] ?? '') ?>" title="Edit Data Mahasiswa"><i class="fas fa-edit fa-fw"></i> Edit</button>
                            <?php 
                            $isStudentPastSemester = !empty($semesterAktif['kode']) 
                                && !empty($m['kode_semester']) 
                                && ((string) $m['kode_semester'] !== (string) $semesterAktif['kode']);
                            ?>
                            <?php if ($isStudentPastSemester) : ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Data mahasiswa periode lampau terkunci dan tidak dapat dihapus"><i class="fas fa-lock fa-fw"></i></button>
                            <?php else : ?>
                                <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modalHapusTerplot" data-id="<?= esc($m['id']) ?>" data-npm="<?= esc($m['npm']) ?>" data-nama="<?= esc($m['nama']) ?>" data-semester="<?= esc($m['kode_semester'] ?? '') ?>" title="Hapus data terplot"><i class="fas fa-trash fa-fw"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
            <div class="text-muted small">
                Menampilkan <?= count($daftarTerplot) ?> dari <?= (int) ($totalRows ?? count($daftarTerplot)) ?> data mahasiswa terplot
            </div>
            <div>
                <?php if (isset($pager)) : ?>
                    <?= $pager->links('terplot', 'bootstrap_pagination') ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Mahasiswa Terplot -->
<div class="modal fade" id="modalTambahTerplot" tabindex="-1" role="dialog" aria-labelledby="modalTambahTerplotLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahTerplotLabel">Tambah Mahasiswa Terplot</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumStore') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tambahNpm">NPM</label>
                        <input type="text" class="form-control" id="tambahNpm" name="npm" value="<?= old('npm', '') ?>" inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" required>
                        <small class="form-text text-muted">NPM harus berupa angka 10 digit dan belum terdaftar.</small>
                    </div>
                    <div class="form-group">
                        <label for="tambahNama">Nama Mahasiswa</label>
                        <input type="text" class="form-control" id="tambahNama" name="nama" value="<?= old('nama', '') ?>" maxlength="150" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Semester Kelulusan <span class="text-danger">*</span></label>
                        <?php if (!empty($semesterAktif)) : ?>
                            <input type="text" class="form-control bg-light font-weight-bold" value="<?= esc($semesterMap[$semesterAktif['kode']] ?? $formatSemester($semesterAktif['kode'], $semesterMap ?? [])) ?> (Sedang Aktif)" readonly>
                            <input type="hidden" name="kode_semester" value="<?= esc($semesterAktif['kode']) ?>">
                            <small class="form-text text-muted"><i class="fas fa-info-circle text-info mr-1"></i>Mahasiswa baru secara otomatis terdaftar pada semester yang sedang aktif. Periode lampau tidak dapat menerima pendaftaran baru.</small>
                        <?php else : ?>
                            <div class="alert alert-warning py-2 small mb-0">
                                <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Perhatian:</strong> Belum ada semester kelulusan yang aktif di The Graduate. Silakan aktifkan semester terlebih dahulu melalui menu <strong>Semester</strong> sebelum menambah mahasiswa terplot.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="tambahStatus">Status</label>
                        <select class="form-control" id="tambahStatus" name="status" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="baru" <?= old('status') === 'baru' ? 'selected' : '' ?>>Baru</option>
                            <option value="transfer" <?= old('status') === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label for="tambahTanggalSk" class="font-weight-bold">Tanggal SK Yudisium</label>
                        <input type="date" class="form-control" id="tambahTanggalSk" name="tanggal_sk_yudisium" value="<?= old('tanggal_sk_yudisium', '') ?>">
                        <small class="form-text text-muted">Format: YYYY-MM-DD. Kosongkan jika belum memiliki SK Yudisium (status pending).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" <?= empty($semesterAktif) ? 'disabled' : '' ?>><i class="fas fa-save fa-fw"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Unggah Daftar Terplot SK Yudisium -->
<div class="modal fade" id="modalUnggahPlotYudisium" tabindex="-1" role="dialog" aria-labelledby="modalUnggahPlotYudisiumLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUnggahPlotYudisiumLabel">Unggah Daftar Mahasiswa Terplot (.xlsx)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumUpload') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <?php if (!empty($semesterAktif)) : ?>
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="fas fa-info-circle mr-1"></i> Seluruh mahasiswa pada berkas ini akan didaftarkan pada <strong><?= esc($semesterMap[$semesterAktif['kode']] ?? $formatSemester($semesterAktif['kode'], $semesterMap ?? [])) ?> (Sedang Aktif)</strong>.
                        </div>
                    <?php else : ?>
                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Perhatian:</strong> Belum ada semester kelulusan yang aktif di The Graduate. Silakan aktifkan semester terlebih dahulu melalui menu <strong>Semester</strong> sebelum mengunggah berkas Excel.
                        </div>
                    <?php endif; ?>
                    <p class="mb-2">Format kolom template: <strong>nama</strong>, <strong>npm</strong> (10 digit), <strong>status</strong> (<code>baru</code> / <code>transfer</code>), dan <strong>tanggal_yudisium</strong> (opsional: <code>YYYY-MM-DD</code>)</p>
                    <p class="mb-3 text-muted small">NPM yang kurang atau lebih dari 10 digit akan terdeteksi sebagai data bermasalah. NPM yang sudah terplot tidak akan disimpan dua kali.</p>
                    <div class="form-group">
                        <label for="filePlot">Berkas Excel (.xls / .xlsx)</label>
                        <input type="file" class="form-control-file" id="filePlot" name="filePlot" accept=".xls,.xlsx" required <?= empty($semesterAktif) ? 'disabled' : '' ?>>
                        <small class="form-text text-muted">Maksimal 5MB</small>
                    </div>
                    <a href="<?= base_url('graduate/skYudisiumDownloadTemplate') ?>" class="btn btn-link btn-sm pl-0"><i class="fas fa-file-download"></i> Download Template Excel</a>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" <?= empty($semesterAktif) ? 'disabled' : '' ?>><i class="fas fa-file-upload mr-1"></i> Unggah & Pratinjau</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Massal (Status & Tanggal SK Yudisium) -->
<div class="modal fade" id="modalBulkEdit" tabindex="-1" role="dialog" aria-labelledby="modalBulkEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBulkEditLabel"><i class="fas fa-edit mr-2 text-info"></i>Edit Status & Tanggal Massal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumBulkEdit') ?>" method="post" id="formBulkEdit">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_query" value="<?= esc($currentQueryString) ?>">
                <div id="bulkNpmInputs"></div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        Perubahan status dan/atau tanggal SK Yudisium akan diterapkan ke <strong id="bulkSelectedCount">0</strong> mahasiswa terpilih.
                    </div>
                    <div class="form-group">
                        <label for="bulk_semester" class="font-weight-bold">Semester</label>
                        <select class="form-control" id="bulk_semester" name="kode_semester">
                            <option value="">-- Tetap (Tidak Mengubah Semester) --</option>
                            <?php if (!empty($daftarSemester)) : ?>
                                <?php foreach ($daftarSemester as $sem) : ?>
                                    <?php 
                                    $semLabel = $semesterMap[$sem['kode']] ?? $formatSemester($sem['kode'], $semesterMap ?? []); 
                                    $isAktif = (($sem['status'] ?? '') === 'AKTIF');
                                    ?>
                                    <option value="<?= esc($sem['kode']) ?>" <?= ! $isAktif ? 'disabled' : '' ?>>
                                        <?= esc($semLabel) ?><?= $isAktif ? ' (Sedang Aktif)' : ' (Periode Tutup)' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="form-text text-muted">Pilih jika ingin memindahkan semester secara serentak (hanya dapat dipindahkan ke semester aktif).</small>
                    </div>
                    <div class="form-group">
                        <label for="bulk_status" class="font-weight-bold">Status Mahasiswa</label>
                        <select class="form-control" id="bulk_status" name="status">
                            <option value="">-- Tetap (Tidak Mengubah Status) --</option>
                            <option value="baru">Baru</option>
                            <option value="transfer">Transfer</option>
                        </select>
                        <small class="form-text text-muted">Pilih jika ingin mengubah status secara serentak.</small>
                    </div>
                    <div class="form-group">
                        <label for="bulk_tanggal_sk_yudisium" class="font-weight-bold">Tanggal SK Yudisium</label>
                        <input type="date" class="form-control" id="bulk_tanggal_sk_yudisium" name="tanggal_sk_yudisium">
                        <small class="form-text text-muted">Format: YYYY-MM-DD. Kosongkan jika tidak ingin mengubah tanggal SK.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Daftar Mahasiswa Terpilih:</label>
                        <div id="bulkSelectedList" class="bg-light border rounded p-2 small" style="max-height: 140px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info" id="btnSubmitBulk"><i class="fas fa-save fa-fw"></i> Terapkan ke Semua</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Massal Mahasiswa Terplot -->
<div class="modal fade" id="modalBulkDelete" tabindex="-1" role="dialog" aria-labelledby="modalBulkDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalBulkDeleteLabel"><i class="fas fa-trash-alt mr-2"></i>Konfirmasi Hapus Massal</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumBulkDelete') ?>" method="post" id="formBulkDelete">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_query" value="<?= esc($currentQueryString) ?>">
                <div id="bulkDeleteNpmInputs"></div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus <strong id="bulkDeleteSelectedCount">0</strong> data mahasiswa terplot yang dipilih?</p>
                    <div class="alert alert-warning py-2 small">
                        <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Perhatian:</strong> Mahasiswa yang tercatat pada periode lampau yang sudah tutup atau sudah memiliki riwayat berkas pendaftaran wisuda / SKL akan otomatis dilewati dan tidak akan dihapus demi menjaga integritas data.
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Daftar Mahasiswa yang Dipilih:</label>
                        <div id="bulkDeleteSelectedList" class="bg-light border rounded p-2 small" style="max-height: 150px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="btnSubmitBulkDelete"><i class="fas fa-trash fa-fw"></i> Ya, Hapus Terpilih</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Status Mahasiswa Terplot -->
<div class="modal fade" id="modalEditStatusTerplot" tabindex="-1" role="dialog" aria-labelledby="modalEditStatusTerplotLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditStatusTerplotLabel"><i class="fas fa-edit mr-2 text-warning"></i>Edit Mahasiswa Terplot</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumUpdateStatus') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="editStatusId">
                <input type="hidden" name="redirect_query" value="<?= esc($currentQueryString) ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>NPM Mahasiswa</label>
                        <input type="text" class="form-control bg-light font-weight-bold" id="editStatusNpm" readonly>
                        <small class="form-text text-muted">NPM adalah identitas unik mahasiswa dan tidak dapat diubah.</small>
                    </div>
                    <div class="form-group">
                        <label for="editStatusNama" class="font-weight-bold">Nama Mahasiswa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editStatusNama" name="nama" maxlength="150" required>
                    </div>
                    <div class="form-group">
                        <label for="editSemester" class="font-weight-bold">Semester Kelulusan <span class="text-danger">*</span></label>
                        <select class="form-control" id="editSemester" name="kode_semester" required>
                            <?php if (!empty($daftarSemester)) : ?>
                                <?php foreach ($daftarSemester as $sem) : ?>
                                    <?php 
                                    $semLabel = $semesterMap[$sem['kode']] ?? $formatSemester($sem['kode'], $semesterMap ?? []); 
                                    $isAktif = (($sem['status'] ?? '') === 'AKTIF');
                                    ?>
                                    <option value="<?= esc($sem['kode']) ?>" data-aktif="<?= $isAktif ? '1' : '0' ?>">
                                        <?= esc($semLabel) ?><?= $isAktif ? ' (Sedang Aktif)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small id="editSemesterHelp" class="form-text text-muted"></small>
                    </div>
                    <div class="form-group">
                        <label for="editStatus" class="font-weight-bold">Status Mahasiswa <span class="text-danger">*</span></label>
                        <select class="form-control" id="editStatus" name="status" required>
                            <option value="baru">Baru</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label for="editTanggalSk" class="font-weight-bold">Tanggal SK Yudisium</label>
                        <input type="date" class="form-control" id="editTanggalSk" name="tanggal_sk_yudisium">
                        <small class="form-text text-muted">Format: YYYY-MM-DD. Kosongkan jika belum memiliki SK Yudisium.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save fa-fw"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Mahasiswa Terplot -->
<div class="modal fade" id="modalHapusTerplot" tabindex="-1" role="dialog" aria-labelledby="modalHapusTerplotLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHapusTerplotLabel">Hapus Mahasiswa Terplot</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumDelete') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="hapusTerplotId">
                <input type="hidden" name="redirect_query" value="<?= esc($currentQueryString) ?>">
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus data mahasiswa terplot berikut?</p>
                    <p class="mb-1"><strong id="hapusTerplotNama"></strong></p>
                    <p class="text-muted mb-0" id="hapusTerplotNpm"></p>
                    <div class="alert alert-warning mt-3 mb-0 small">
                        <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Perhatian:</strong> Mahasiswa ini akan kehilangan akses ke pendaftaran wisuda dan tracer study. Mahasiswa yang sudah memiliki berkas pendaftaran wisuda atau pengajuan SKL tidak dapat dihapus.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash fa-fw"></i> Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

<script>
    $(function () {
        <?php if (old('npm') !== null || old('nama') !== null || old('status') !== null) : ?>
        $('#modalTambahTerplot').modal('show');
        <?php endif; ?>

        // Auto-clean non-digits on NPM input in modal tambah
        $('#tambahNpm').on('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Bulk Checkbox Handling
        function updateBulkButton() {
            var checked = $('.check-npm:checked');
            var count = checked.length;
            $('#bulkCount').text(count);
            $('#btnBulkAction, #btnBulkEdit').prop('disabled', count === 0);
        }

        $('#checkAll').on('change', function () {
            $('.check-npm').prop('checked', $(this).is(':checked'));
            updateBulkButton();
        });

        $(document).on('change', '.check-npm', function () {
            if (!$(this).is(':checked')) {
                $('#checkAll').prop('checked', false);
            } else if ($('.check-npm:checked').length === $('.check-npm').length) {
                $('#checkAll').prop('checked', true);
            }
            updateBulkButton();
        });

        $('#modalBulkEdit').on('show.bs.modal', function () {
            var checked = $('.check-npm:checked');
            var count = checked.length;
            $('#bulkSelectedCount').text(count);
            var $inputs = $('#bulkNpmInputs').empty();
            var $list = $('<ul class="mb-0 pl-3"></ul>');
            checked.each(function () {
                var npm = $(this).val();
                var nama = $(this).data('nama');
                $('<input type="hidden" name="npms[]">').val(npm).appendTo($inputs);
                $('<li></li>')
                    .append($('<strong></strong>').text(npm))
                    .append(document.createTextNode(' - ' + nama))
                    .appendTo($list);
            });
            $('#bulkSelectedList').empty().append($list);
        });

        $('#modalBulkDelete').on('show.bs.modal', function () {
            var checked = $('.check-npm:checked');
            var count = checked.length;
            $('#bulkDeleteSelectedCount').text(count);
            var $inputs = $('#bulkDeleteNpmInputs').empty();
            var $list = $('<ul class="mb-0 pl-3"></ul>');
            checked.each(function () {
                var npm = $(this).val();
                var nama = $(this).data('nama');
                $('<input type="hidden" name="npms[]">').val(npm).appendTo($inputs);
                $('<li></li>')
                    .append($('<strong></strong>').text(npm))
                    .append(document.createTextNode(' - ' + nama))
                    .appendTo($list);
            });
            $('#bulkDeleteSelectedList').empty().append($list);
        });

        // Edit Status Modal
        $('#modalEditStatusTerplot').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var npm = button.attr('data-npm') || button.data('npm');
            var nama = button.data('nama');
            var status = button.data('status');
            var semester = String(button.data('semester') || '');
            var tanggal = button.data('tanggal') || '';
            var modal = $(this);
            modal.find('#editStatusId').val(id);
            modal.find('#editStatusNpm').val(npm);
            modal.find('#editStatusNama').val(nama);
            modal.find('#editStatus').val(status);
            modal.find('#editSemester').val(semester);
            modal.find('#editTanggalSk').val(tanggal);

            var activeSemesterCode = '<?= esc($semesterAktif['kode'] ?? '') ?>';
            var $semSelect = modal.find('#editSemester');
            var $helpText = modal.find('#editSemesterHelp');

            if (semester !== '' && activeSemesterCode !== '' && semester !== activeSemesterCode) {
                // Mahasiswa berada di periode lampau: kunci semester agar tidak dapat diubah
                $semSelect.find('option').each(function () {
                    $(this).prop('disabled', $(this).val() !== semester);
                });
                $helpText.html('<span class="text-warning"><i class="fas fa-lock mr-1"></i>Mahasiswa ini tercatat pada periode lampau. Semester dikunci demi menjaga integritas arsip kelulusan.</span>');
            } else {
                // Mahasiswa aktif: hanya boleh tetap atau pilih semester aktif
                $semSelect.find('option').each(function () {
                    var isOptionAktif = $(this).data('aktif') == 1 || $(this).val() === activeSemesterCode;
                    $(this).prop('disabled', !isOptionAktif);
                });
                $helpText.text('Mahasiswa aktif hanya dapat didaftarkan pada semester yang sedang berjalan.');
            }
        });

        // Single Delete Modal
        $('#modalHapusTerplot').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            modal.find('#hapusTerplotId').val(button.data('id'));
            modal.find('#hapusTerplotNama').text(button.data('nama'));
            modal.find('#hapusTerplotNpm').text(button.attr('data-npm') || button.data('npm'));
        });
    });
</script>

<?= $this->endSection(); ?>
