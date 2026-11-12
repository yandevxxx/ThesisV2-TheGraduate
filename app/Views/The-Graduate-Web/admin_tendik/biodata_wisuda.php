<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
$flashMessage = session()->getFlashdata('message');
$flashIcon    = 'info';
$flashTitle   = '';
$flashText    = '';

if (is_array($flashMessage)) {
    $flashIcon  = (string) ($flashMessage['icon'] ?? 'info');
    $flashTitle = (string) ($flashMessage['title'] ?? '');
    $flashText  = (string) ($flashMessage['text'] ?? '');
} elseif (is_string($flashMessage) && trim($flashMessage) !== '') {
    $flashIcon  = 'info';
    $flashTitle = 'Informasi';
    $flashText  = $flashMessage;
} elseif (session()->getFlashdata('error')) {
    $flashIcon  = 'error';
    $flashTitle = 'Terjadi Kesalahan';
    $flashText  = (string) session()->getFlashdata('error');
} elseif (session()->getFlashdata('success')) {
    $flashIcon  = 'success';
    $flashTitle = 'Berhasil';
    $flashText  = (string) session()->getFlashdata('success');
}
?>
<?php if ($flashText !== '') : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= esc($flashIcon); ?></p>
        <p id="title" hidden><?= esc($flashTitle); ?></p>
        <p id="text" hidden><?= esc($flashText); ?></p>
    </div>
<?php endif; ?>

<?php
use App\Models\SemesterGraduateModel;

$daftarBiodata  = $daftarBiodata ?? [];
$daftarSemester = is_array($daftarSemester ?? null) ? $daftarSemester : [];
$semesterAktif  = (string) ($semesterAktif ?? '');
$statusFilter   = (string) ($statusFilter ?? '');
$kataCari       = (string) ($kataCari ?? '');

$defaultSemester = 'semua';
foreach ($daftarSemester as $sem) {
    if (($sem['status'] ?? '') === 'AKTIF' && ! empty($sem['kode'])) {
        $defaultSemester = (string) $sem['kode'];
        break;
    }
}
$hasActiveFilter = $kataCari !== ''
    || ($statusFilter !== '' && $statusFilter !== 'semua')
    || ($semesterAktif !== '' && $semesterAktif !== $defaultSemester);

$exportColumns = is_array($exportColumns ?? null) && $exportColumns !== []
    ? $exportColumns
    : \App\Controllers\TheGraduateWeb\PendaftaranWisudaController::biodataExportColumns();

$exportColumnGroups = [];
foreach ($exportColumns as $colKey => $colMeta) {
    $grp = (string) ($colMeta['group'] ?? 'Lainnya');
    $exportColumnGroups[$grp][$colKey] = $colMeta;
}

$formatDate = static function (?string $date): string {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $time = strtotime($date);
    return ($time !== false && $time > 0) ? date('d/m/Y', $time) : '-';
};

// Siapkan data detail JSON untuk modal preview
$dataDetail = [];
foreach ($daftarBiodata as $p) {
    $dataDetail[$p['id']] = [
        'id'               => $p['id'],
        'npm'              => $p['npm'] ?? '-',
        'nama'             => $p['nama'] ?? '-',
        'nik'              => $p['nik'] ?? '-',
        'tempatLahir'      => $p['tempat_lahir'] ?? '-',
        'tanggalLahir'     => $formatDate($p['tanggal_lahir'] ?? null),
        'jenisKelamin'     => $p['jenis_kelamin'] ?? '-',
        'alamat'           => $p['alamat'] ?? '-',
        'hp'               => $p['no_hp'] ?? '-',
        'telepon'          => $p['no_telepon'] ?? '-',
        'email'            => $p['email'] ?? '-',
        'prodi'            => $p['prodi'] ?? '-',
        'totalSks'         => $p['total_sks'] ?? '-',
        'ipk'              => $p['ipk'] ?? '-',
        'semester'         => SemesterGraduateModel::formatNama('', $p['kode_semester'] ?? ''),
        'tglLulus'         => $formatDate($p['tanggal_lulus'] ?? null),
        'jenisTugasAkhir'  => $p['jenis_tugas_akhir'] ?? 'Skripsi',
        'judul'            => $p['judul_skripsi'] ?? '-',
        'dospem1'          => $p['pembimbing1'] ?? '-',
        'dospem2'          => $p['pembimbing2'] ?? '-',
        'dospemAgama'      => $p['pembimbing_agama'] ?? '-',
        'penguji'          => $p['dosen_penguji'] ?? '-',
        'status'           => $p['status_biodata'] ?? 'MENUNGGU',
        'catatan'          => $p['catatan_biodata'] ?? '',
        'foto'             => !empty($p['pas_foto']) ? base_url('graduate/berkas/view/' . basename($p['pas_foto'])) : base_url('assets/img/undraw_profile.svg'),
    ];
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-id-card text-primary mr-2"></i>Verifikasi Biodata Wisuda</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
            <h6 class="m-0 font-weight-bold text-primary">Antrean Verifikasi Biodata Mahasiswa</h6>
            <small class="text-muted d-block mt-1">Menampilkan pendaftaran yang telah dikirimkan oleh mahasiswa (<?= count($daftarBiodata) ?> data)</small>
        </div>
        <div class="mt-2 mt-md-0 d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-success btn-sm shadow-sm font-weight-bold" data-toggle="modal" data-target="#modalExportBiodataExcel">
                <i class="fas fa-file-excel fa-fw mr-1"></i> Download Excel
            </button>
        </div>
    </div>
    <div class="card-body">

        <!-- Filter: Pencarian, Status, dan Semester -->
        <div class="bg-light border rounded p-3 mb-3">
            <form method="get" action="<?= base_url('graduate/biodata_wisuda_verifikasi') ?>" class="form-row align-items-end mb-0">
                <div class="form-group col-12 col-md-6 col-lg mb-2 mb-lg-0">
                    <label class="small font-weight-bold text-gray-700 mb-1" for="filterCari">Cari Mahasiswa</label>
                    <div class="input-group input-group-sm">
                        <input type="search" class="form-control" id="filterCari" name="cari" placeholder="Cari NPM atau nama..." value="<?= esc($kataCari, 'attr') ?>">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>

                <div class="form-group col-12 col-md-6 col-lg-3 mb-2 mb-lg-0">
                    <label class="small font-weight-bold text-gray-700 mb-1" for="filterStatus">Status Biodata</label>
                    <select class="custom-select custom-select-sm" id="filterStatus" name="status">
                        <option value="" <?= ($statusFilter === '' || $statusFilter === 'semua') ? 'selected' : '' ?>>Semua Status</option>
                        <option value="MENUNGGU" <?= ($statusFilter === 'MENUNGGU') ? 'selected' : '' ?>>Menunggu</option>
                        <option value="DISETUJUI" <?= ($statusFilter === 'DISETUJUI') ? 'selected' : '' ?>>Disetujui</option>
                        <option value="DITOLAK" <?= ($statusFilter === 'DITOLAK') ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>

                <div class="form-group col-12 col-md-6 col-lg-3 mb-2 mb-md-0">
                    <label class="small font-weight-bold text-gray-700 mb-1" for="filterSemester">Pilih Semester</label>
                    <select class="custom-select custom-select-sm" id="filterSemester" name="semester">
                        <option value="semua" <?= ($semesterAktif === 'semua' || $semesterAktif === '') ? 'selected' : '' ?>>Semua Semester</option>
                        <?php foreach ($daftarSemester as $sem) :
                            $labelSem = SemesterGraduateModel::formatNama($sem['nama'] ?? '', $sem['kode'] ?? '');
                        ?>
                            <option value="<?= esc($sem['kode'], 'attr') ?>" <?= ($semesterAktif === (string) ($sem['kode'] ?? '')) ? 'selected' : '' ?>>
                                <?= esc($labelSem) ?><?= (($sem['status'] ?? '') === 'AKTIF') ? ' (Aktif)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group col-12 col-md-6 col-lg-auto mb-0 d-flex align-items-center">
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold mr-2">
                        <i class="fas fa-filter fa-xs mr-1"></i> Filter
                    </button>
                    <?php if ($hasActiveFilter) : ?>
                        <a href="<?= base_url('graduate/biodata_wisuda_verifikasi') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 12%;">NPM</th>
                        <th style="width: 24%;">Nama Mahasiswa</th>
                        <th style="width: 16%;">Prodi</th>
                        <th style="width: 12%;">Tanggal Yudisium</th>
                        <th style="width: 13%;">Status Biodata</th>
                        <th style="width: 8%;">Foto</th>
                        <th style="width: 10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarBiodata)): ?>
                    <tr class="text-center">
                        <td colspan="8" class="text-gray-500 py-4">
                            <i class="fas fa-user-graduate fa-2x mb-2 text-gray-300 d-block"></i>
                            Tidak ada data pendaftaran biodata wisuda yang sesuai filter.
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php $no = 1; foreach ($daftarBiodata as $p): ?>
                    <tr class="text-center align-middle">
                        <td><?= $no++ ?></td>
                        <td class="font-weight-bold"><?= esc($p['npm']) ?></td>
                        <td class="text-left">
                            <div class="font-weight-bold text-gray-900"><?= esc($p['nama']) ?></div>
                            <small class="badge badge-light border text-muted"><i class="fas fa-book-reader mr-1"></i><?= esc($p['jenis_tugas_akhir'] ?? 'Skripsi') ?></small>
                        </td>
                        <td><?= esc($p['prodi'] ?? '-') ?></td>
                        <td><?= esc($formatDate($p['tanggal_lulus'] ?? null)) ?></td>
                        <td>
                            <?php 
                            $st = $p['status_biodata'] ?? 'MENUNGGU';
                            if ($st === 'DISETUJUI'): ?>
                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Disetujui</span>
                            <?php elseif ($st === 'DITOLAK'): ?>
                                <span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i> Ditolak</span>
                            <?php else: ?>
                                <span class="badge badge-warning px-2 py-1 text-dark"><i class="fas fa-hourglass-half mr-1"></i> Menunggu</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $fotoUrl = !empty($p['pas_foto']) ? base_url('graduate/berkas/view/' . basename($p['pas_foto'])) : base_url('assets/img/undraw_profile.svg');
                            ?>
                            <img src="<?= $fotoUrl ?>" alt="Foto <?= esc($p['nama']) ?>" class="rounded-circle shadow-sm" width="38" height="38" style="object-fit: cover;">
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm lihat-biodata" data-toggle="tooltip" data-placement="top" title="Periksa Biodata" data-id="<?= esc($p['id']) ?>">
                                <i class="fas fa-eye"></i> Detail
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Modal Detail & Verifikasi Biodata -->
<div class="modal fade" id="modalDetailBiodata" tabindex="-1" role="dialog" aria-labelledby="modalDetailBiodataLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetailBiodataLabel"><i class="fas fa-id-card mr-2"></i> Detail &amp; Verifikasi Biodata Wisuda</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Foto & Status -->
                    <div class="col-md-4 text-center mb-3">
                        <a id="detailFotoLink" href="<?= base_url('assets/img/undraw_profile.svg') ?>" target="_blank" title="Klik untuk membuka pas foto ukuran penuh">
                            <img id="detailFoto" src="<?= base_url('assets/img/undraw_profile.svg') ?>" alt="Pas Foto Mahasiswa" class="img-thumbnail rounded mb-2 shadow-sm" style="max-height: 220px; width: 170px; object-fit: cover; cursor: pointer;">
                        </a>
                        <small class="d-block text-muted mb-2"><i class="fas fa-search-plus mr-1"></i>Klik foto untuk memperbesar</small>
                        <div>
                            <span id="detailStatusBadge" class="badge badge-warning px-3 py-2 text-dark font-weight-bold">MENUNGGU</span>
                        </div>
                    </div>

                    <!-- DATA DIRI -->
                    <div class="col-md-8">
                        <div class="card border-left-primary shadow-sm mb-3">
                            <div class="card-header py-2 bg-light">
                                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user mr-1"></i> Data Diri</h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-striped mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="font-weight-bold" style="width: 38%;">NPM</td>
                                            <td>: <span id="detailNpm">-</span></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Nama Lengkap</td>
                                            <td>: <span id="detailNama">-</span></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">NIK (KTP)</td>
                                            <td>: <span id="detailNik">-</span></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Tempat, Tanggal Lahir</td>
                                            <td>: <span id="detailTtl">-</span></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Jenis Kelamin</td>
                                            <td>: <span id="detailJk">-</span></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">No. HP / WA</td>
                                            <td>: <span id="detailHp">-</span></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">No. Telepon Alternatif</td>
                                            <td>: <span id="detailTelepon">-</span></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">E-mail</td>
                                            <td>: <span id="detailEmail">-</span></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">Alamat Lengkap</td>
                                            <td>: <span id="detailAlamat">-</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DATA AKADEMIK -->
                <div class="card border-left-info shadow-sm mb-3">
                    <div class="card-header py-2 bg-light">
                        <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-graduation-cap mr-1"></i> Data Akademik</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <tbody>
                                <tr>
                                    <td class="font-weight-bold" style="width: 25%;">Program Studi</td>
                                    <td>: <span id="detailProdi">-</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Total SKS / IPK</td>
                                    <td>: <span id="detailSks">-</span> SKS &nbsp;|&nbsp; IPK: <span id="detailIpk">-</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Semester Kelulusan</td>
                                    <td>: <span id="detailSemester">-</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Tanggal Yudisium</td>
                                    <td>: <span id="detailTglLulus">-</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Jenis Tugas Akhir</td>
                                    <td>: <span id="detailJenisTa">Skripsi</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Judul Tugas Akhir</td>
                                    <td>: <span id="detailJudul">-</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Pembimbing Ilmu 1</td>
                                    <td>: <span id="detailDospem1">-</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Pembimbing Ilmu 2</td>
                                    <td>: <span id="detailDospem2">-</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Pembimbing Agama</td>
                                    <td>: <span id="detailDospemAgama">-</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Dosen Penguji Sidang</td>
                                    <td>: <span id="detailPenguji">-</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form Verifikasi Tendik -->
                <form id="formVerifikasiBiodata" method="post" action="<?= base_url('graduate/biodataWisudaVerifikasi') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="verifikasiPendaftaranId" value="">
                    <input type="hidden" name="status" id="verifikasiStatusInput" value="">
                    <input type="hidden" name="redirect_to" value="<?= esc($currentUrlWithQuery ?? current_url()) ?>">

                    <div class="form-group mb-2">
                        <label for="catatanBiodata" class="font-weight-bold text-gray-800">
                            Catatan Verifikasi Biodata <small class="text-muted">(Wajib diisi jika menolak biodata)</small>
                        </label>
                        <textarea class="form-control" id="catatanBiodata" name="catatan_biodata" rows="3" placeholder="Tuliskan catatan perbaikan biodata atau catatan persetujuan..."></textarea>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end align-items-center mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-secondary mr-auto my-1" data-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-warning mr-2 my-1" id="btnResetBiodata">
                            <i class="fas fa-undo mr-1"></i> Kembalikan ke Menunggu
                        </button>
                        <button type="button" class="btn btn-danger mr-2 my-1" id="btnTolakBiodata">
                            <i class="fas fa-times-circle mr-1"></i> Tolak Biodata
                        </button>
                        <button type="button" class="btn btn-success my-1" id="btnSetujuiBiodata">
                            <i class="fas fa-check-circle mr-1"></i> Setujui Biodata
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- Modal Export Excel Biodata Mahasiswa -->
<div class="modal fade" id="modalExportBiodataExcel" tabindex="-1" role="dialog" aria-labelledby="modalExportBiodataExcelLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalExportBiodataExcelLabel">
                    <i class="fas fa-file-excel mr-2"></i> Download Excel Biodata Mahasiswa
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formExportBiodataExcel" method="get" action="<?= base_url('graduate/biodata_wisuda_verifikasi/export') ?>">
                <input type="hidden" name="redirect_to" value="<?= esc($currentUrlWithQuery ?? current_url(), 'attr') ?>">
                <div class="modal-body">
                    <div class="bg-light border rounded p-3 mb-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                            <span class="small font-weight-bold text-gray-800 text-uppercase">
                                <i class="fas fa-filter text-primary mr-1"></i> Filter Data Mahasiswa yang Diambil
                            </span>
                            <small class="text-muted">Otomatis mengikuti filter tabel saat ini</small>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4 mb-2 mb-md-0">
                                <label class="small font-weight-bold text-gray-700 mb-1" for="exportSemester">Semester</label>
                                <select class="custom-select custom-select-sm" id="exportSemester" name="semester">
                                    <option value="semua" <?= ($semesterAktif === 'semua' || $semesterAktif === '') ? 'selected' : '' ?>>Semua Semester</option>
                                    <?php foreach ($daftarSemester as $sem) :
                                        $labelSem = SemesterGraduateModel::formatNama($sem['nama'] ?? '', $sem['kode'] ?? '');
                                    ?>
                                        <option value="<?= esc($sem['kode'], 'attr') ?>" <?= ($semesterAktif === (string) ($sem['kode'] ?? '')) ? 'selected' : '' ?>>
                                            <?= esc($labelSem) ?><?= (($sem['status'] ?? '') === 'AKTIF') ? ' (Aktif)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-4 mb-2 mb-md-0">
                                <label class="small font-weight-bold text-gray-700 mb-1" for="exportStatus">Status Biodata</label>
                                <select class="custom-select custom-select-sm" id="exportStatus" name="status">
                                    <option value="" <?= ($statusFilter === '' || $statusFilter === 'semua') ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="MENUNGGU" <?= ($statusFilter === 'MENUNGGU') ? 'selected' : '' ?>>Menunggu</option>
                                    <option value="DISETUJUI" <?= ($statusFilter === 'DISETUJUI') ? 'selected' : '' ?>>Disetujui</option>
                                    <option value="DITOLAK" <?= ($statusFilter === 'DITOLAK') ? 'selected' : '' ?>>Ditolak</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4 mb-0">
                                <label class="small font-weight-bold text-gray-700 mb-1" for="exportCari">Cari Mahasiswa</label>
                                <input type="text" class="form-control form-control-sm" id="exportCari" name="cari" placeholder="Opsional: NPM / nama / prodi..." value="<?= esc($kataCari, 'attr') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                        <div>
                            <span class="small font-weight-bold text-gray-800 text-uppercase">
                                <i class="fas fa-columns text-success mr-1"></i> Pilih Kolom Data yang Diambil
                            </span>
                            <span class="badge badge-success badge-pill ml-1 px-2 py-1" id="exportSelectedCount">Dipilih: 0 kolom</span>
                        </div>
                        <div class="btn-group btn-group-sm mt-2 mt-sm-0" role="group" aria-label="Preset Pilihan Kolom">
                            <button type="button" class="btn btn-outline-primary" id="btnExportPilihSemua">Pilih Semua</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnExportDataUtama">Data Utama</button>
                            <button type="button" class="btn btn-outline-danger" id="btnExportKosongkan">Kosongkan</button>
                        </div>
                    </div>

                    <div class="mb-3 pb-2 border-bottom">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="exportIncludeNo" name="include_no" value="1" checked>
                            <label class="custom-control-label small font-weight-bold text-gray-700" for="exportIncludeNo">
                                Sertakan kolom No. Urut (otomatis 1, 2, 3...)
                            </label>
                        </div>
                    </div>

                    <?php foreach ($exportColumnGroups as $groupName => $cols) : ?>
                        <div class="mb-3">
                            <div class="small font-weight-bold text-primary mb-2 border-bottom pb-1">
                                <?= esc($groupName) ?>
                            </div>
                            <div class="row">
                                <?php foreach ($cols as $colKey => $colMeta) :
                                    $cbId      = 'exportCol_' . preg_replace('/[^A-Za-z0-9_]/', '_', (string) $colKey);
                                    $isDefault = ! empty($colMeta['default']);
                                ?>
                                    <div class="col-sm-6 col-md-4 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input export-field-checkbox"
                                                id="<?= esc($cbId, 'attr') ?>"
                                                name="fields[]"
                                                value="<?= esc($colKey, 'attr') ?>"
                                                data-default="<?= $isDefault ? '1' : '0' ?>"
                                                <?= $isDefault ? 'checked' : '' ?>
                                            >
                                            <label class="custom-control-label small text-gray-800" for="<?= esc($cbId, 'attr') ?>">
                                                <?= esc($colMeta['label']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold" id="btnSubmitExportExcel">
                        <i class="fas fa-download mr-1"></i> Unduh Excel (.xlsx)
                    </button>
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
            var dataDetail = <?= json_encode($dataDetail, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

            // SweetAlert flashdata popup
            var flashData = $('#flashdata');
            if (flashData.length && flashData.data('open')) {
                var icon  = $('#icon').text();
                var title = $('#title').text();
                var text  = $('#text').text();
                if (text && typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: icon || 'info',
                        title: title || (icon === 'success' ? 'Berhasil' : (icon === 'error' ? 'Gagal' : 'Informasi')),
                        text: text,
                        confirmButtonColor: '#1C476C'
                    });
                }
            }

            // Buka Modal Detail Biodata
            $('.lihat-biodata').on('click', function () {
                var id = $(this).data('id');
                var d  = dataDetail[id];
                if (!d) return;

                $('#verifikasiPendaftaranId').val(d.id);
                $('#detailFoto').attr('src', d.foto);
                $('#detailFotoLink').attr('href', d.foto);
                $('#detailNpm').text(d.npm);
                $('#detailNama').text(d.nama);
                $('#detailNik').text(d.nik);
                $('#detailTtl').text(d.tempatLahir + ', ' + d.tanggalLahir);
                $('#detailJk').text(d.jenisKelamin);
                $('#detailProdi').text(d.prodi);
                $('#detailSks').text(d.totalSks);
                $('#detailIpk').text(d.ipk);
                $('#detailSemester').text(d.semester);
                $('#detailTglLulus').text(d.tglLulus);
                $('#detailHp').text(d.hp);
                $('#detailTelepon').text(d.telepon || '-');
                $('#detailEmail').text(d.email);
                $('#detailAlamat').text(d.alamat);

                $('#detailJenisTa').text(d.jenisTugasAkhir || 'Skripsi');
                $('#detailJudul').text(d.judul);
                $('#detailDospem1').text(d.dospem1);
                $('#detailDospem2').text(d.dospem2);
                $('#detailDospemAgama').text(d.dospemAgama);
                $('#detailPenguji').text(d.penguji);

                $('#catatanBiodata').val(d.catatan || '');

                var badge = $('#detailStatusBadge');
                badge.removeClass('badge-success badge-danger badge-warning');
                if (d.status === 'DISETUJUI') {
                    badge.addClass('badge-success').text('DISETUJUI');
                    $('#btnResetBiodata').show();
                } else if (d.status === 'DITOLAK') {
                    badge.addClass('badge-danger').text('DITOLAK');
                    $('#btnResetBiodata').show();
                } else {
                    badge.addClass('badge-warning text-dark').text('MENUNGGU');
                    $('#btnResetBiodata').hide();
                }

                $('#modalDetailBiodata').modal('show');
            });

            // Action Setujui Biodata
            $('#btnSetujuiBiodata').on('click', function () {
                var nama = $('#detailNama').text();
                Swal.fire({
                    title: 'Setujui Biodata?',
                    text: 'Apakah Anda yakin ingin menyetujui biodata wisuda mahasiswa ' + nama + '?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Setujui',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#verifikasiStatusInput').val('DISETUJUI');
                        $('#formVerifikasiBiodata').submit();
                    }
                });
            });

            // Action Tolak Biodata
            $('#btnTolakBiodata').on('click', function () {
                var catatan = $.trim($('#catatanBiodata').val());
                if (catatan === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Catatan Wajib Diisi',
                        text: 'Harap berikan catatan perbaikan atau alasan penolakan biodata mahasiswa.',
                        confirmButtonColor: '#1C476C'
                    });
                    $('#catatanBiodata').focus();
                    return;
                }

                var nama = $('#detailNama').text();
                Swal.fire({
                    title: 'Tolak Biodata?',
                    text: 'Apakah Anda yakin ingin menolak biodata wisuda mahasiswa ' + nama + '?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Tolak',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#verifikasiStatusInput').val('DITOLAK');
                        $('#formVerifikasiBiodata').submit();
                    }
                });
            });

            // Action Kembalikan ke Menunggu
            $('#btnResetBiodata').on('click', function () {
                var nama = $('#detailNama').text();
                Swal.fire({
                    title: 'Kembalikan ke Menunggu?',
                    text: 'Status biodata mahasiswa ' + nama + ' akan dikembalikan menjadi MENUNGGU verifikasi.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Kembalikan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#verifikasiStatusInput').val('MENUNGGU');
                        $('#formVerifikasiBiodata').submit();
                    }
                });
            });

            // Export Excel Biodata Mahasiswa: hitung & sinkronisasi pilihan kolom
            function syncExportCount() {
                var totalChecked = $('.export-field-checkbox:checked').length;
                $('#exportSelectedCount').text('Dipilih: ' + totalChecked + ' kolom');
            }

            syncExportCount();

            $('#modalExportBiodataExcel').on('show.bs.modal', function () {
                if ($('#filterSemester').length) {
                    $('#exportSemester').val($('#filterSemester').val());
                }
                if ($('#filterStatus').length) {
                    $('#exportStatus').val($('#filterStatus').val());
                }
                if ($('#filterCari').length) {
                    $('#exportCari').val($('#filterCari').val());
                }
                syncExportCount();
            });

            $(document).on('change', '.export-field-checkbox', syncExportCount);

            $('#btnExportPilihSemua').on('click', function () {
                $('.export-field-checkbox').prop('checked', true);
                syncExportCount();
            });

            $('#btnExportDataUtama').on('click', function () {
                $('.export-field-checkbox').each(function () {
                    $(this).prop('checked', $(this).data('default') === 1 || $(this).attr('data-default') === '1');
                });
                $('#exportIncludeNo').prop('checked', true);
                syncExportCount();
            });

            $('#btnExportKosongkan').on('click', function () {
                $('.export-field-checkbox').prop('checked', false);
                syncExportCount();
            });

            $('#formExportBiodataExcel').on('submit', function (e) {
                if ($('.export-field-checkbox:checked').length === 0) {
                    e.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Pilih Minimal 1 Data',
                            text: 'Harap pilih minimal satu kolom data biodata yang ingin diunduh.',
                            confirmButtonColor: '#1C476C'
                        });
                    } else {
                        alert('Harap pilih minimal satu kolom data biodata yang ingin diunduh.');
                    }
                    return false;
                }
                $('#modalExportBiodataExcel').modal('hide');
            });
        });
    </script>

<?= $this->endSection(); ?>

