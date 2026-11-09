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

use App\Models\SemesterGraduateModel;

$daftarPengajuan    = $daftarPengajuan ?? [];
$persyaratanAktif   = $persyaratanAktif ?? [];
$semuaPersyaratan   = $semuaPersyaratan ?? [];
$berkasPerMahasiswa = $berkasPerMahasiswa ?? [];
$daftarSemester     = $daftarSemester ?? [];
$kodeSemesterAktif  = $kodeSemesterAktif ?? '';
$semesterAktif      = $semesterAktif ?? ($kodeSemesterAktif !== '' ? $kodeSemesterAktif : 'semua');
$pilihanStatus      = $pilihanStatus ?? '';
$kataCari           = $kataCari ?? '';

// Palet warna untuk tombol dokumen dinamis
$docColorPalette = ['primary', 'success', 'danger', 'info', 'warning', 'dark'];

// Opsi filter status
$daftarFilterStatus = [
    ''          => 'Semua Status',
    'menunggu'  => 'Menunggu',
    'proses'    => 'Diproses',
    'disetujui' => 'Disetujui',
    'ditolak'   => 'Ditolak',
];

$statusBadges = [
    'menunggu'  => ['label' => 'Menunggu',  'class' => 'badge-warning text-dark'],
    'proses'    => ['label' => 'Diproses',  'class' => 'badge-info'],
    'disetujui' => ['label' => 'Disetujui', 'class' => 'badge-success'],
    'ditolak'   => ['label' => 'Ditolak',   'class' => 'badge-danger'],
];
$activeTab = $activeTab ?? (($_GET['tab'] ?? '') === 'kelola' ? 'kelola' : 'mahasiswa');
$isKelola  = ($activeTab === 'kelola');
?>

<?php if ($flashText !== '') : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= esc($flashIcon); ?></p>
        <p id="title" hidden><?= esc($flashTitle); ?></p>
        <p id="text" hidden><?= esc($flashText); ?></p>
    </div>
<?php endif; ?>

<h1 class="h3 mb-4 text-gray-800">
    <i class="fas <?= $isKelola ? 'fa-cogs' : 'fa-user-graduate' ?> text-primary mr-2"></i><?= $isKelola ? 'SKL Kelola' : 'SKL Mahasiswa' ?>
</h1>

<?php if ($isKelola): ?>

    <!-- =============================================================== -->
    <!-- SKL KELOLA (PERSYARATAN DINAMIS)                                -->
    <!-- =============================================================== -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2 mb-md-0">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-tasks mr-2"></i>Master Persyaratan Pengambilan SKL</h6>
                <small class="text-muted">Kelola daftar dokumen yang wajib maupun opsional diunggah mahasiswa untuk memperoleh Surat Keterangan Lulus.</small>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahPersyaratan">
                    <i class="fas fa-plus fa-sm mr-1"></i> Tambah Persyaratan SKL
                </button>
            </div>
        </div>
        <div class="card-body">

            <!-- Filter Cepat Pencarian -->
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-gray-400"></i></span>
                        </div>
                        <input type="text" id="cariPersyaratanSkl" class="form-control" placeholder="Cari nama atau keterangan...">
                    </div>
                </div>
                <div class="col-md-6 text-md-right mt-2 mt-md-0 small text-muted">
                    <i class="fas fa-info-circle mr-1 text-primary"></i> Klik tombol status untuk mengaktifkan / menonaktifkan persyaratan.
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="tablePersyaratanSkl" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th style="width: 7%;">Urutan</th>
                            <th style="width: 25%;">Nama Persyaratan Dokumen</th>
                            <th style="width: 28%;">Keterangan / Instruksi</th>
                            <th style="width: 14%;">Format &amp; Ukuran</th>
                            <th style="width: 8%;">Sifat</th>
                            <th style="width: 9%;">Status</th>
                            <th style="width: 9%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($semuaPersyaratan)): ?>
                            <tr class="text-center">
                                <td colspan="7" class="text-muted py-4">Belum ada persyaratan SKL yang dibuat. Klik tombol "Tambah Persyaratan SKL" untuk menambahkan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($semuaPersyaratan as $syarat): ?>
                                <?php
                                $idSyarat = (int) $syarat['id'];
                                $isAktif  = ($syarat['status'] === 'AKTIF');
                                $isWajib  = ((int) $syarat['is_wajib'] === 1);
                                $maxMb    = round(($syarat['max_size_kb'] ?? 5120) / 1024, 1);
                                ?>
                                <tr>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-light border px-2 py-1 font-weight-bold text-dark">
                                            <?= (int) $syarat['urutan'] ?>
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold text-gray-800">
                                            <i class="fas fa-file-alt text-primary mr-1"></i><?= esc($syarat['nama']) ?>
                                        </div>
                                    </td>
                                    <td class="align-middle small text-muted">
                                        <?= !empty($syarat['keterangan']) ? esc($syarat['keterangan']) : '<span class="text-muted font-italic">- Tidak ada keterangan khusus -</span>' ?>
                                    </td>
                                    <td class="text-center align-middle small">
                                        <span class="badge badge-secondary text-uppercase px-2 py-1"><?= strtoupper(esc($syarat['tipe_file'] ?? 'pdf')) ?></span>
                                        <div class="text-muted mt-1"><i class="fas fa-weight-hanging fa-xs mr-1"></i>Maks. <?= $maxMb ?> MB</div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php if ($isWajib): ?>
                                            <span class="badge badge-danger px-2 py-1"><i class="fas fa-asterisk fa-xs mr-1"></i>Wajib</span>
                                        <?php else: ?>
                                            <span class="badge badge-info px-2 py-1">Opsional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <form method="post" action="<?= base_url('graduate/persyaratanSklToggleStatus/' . $idSyarat) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-<?= $isAktif ? 'success' : 'secondary' ?> py-1 px-2" style="font-size: 0.75rem;" data-toggle="tooltip" title="Klik untuk <?= $isAktif ? 'nonaktifkan' : 'aktifkan' ?> syarat">
                                                <i class="fas fa-<?= $isAktif ? 'check-circle' : 'times-circle' ?> mr-1"></i><?= $isAktif ? 'Aktif' : 'Nonaktif' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center align-middle text-nowrap">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <!-- Tombol Edit -->
                                            <button type="button" class="btn btn-warning btn-edit-syarat"
                                                    data-id="<?= $idSyarat ?>"
                                                    data-nama="<?= esc($syarat['nama']) ?>"
                                                    data-keterangan="<?= esc($syarat['keterangan'] ?? '') ?>"
                                                    data-tipe="<?= esc($syarat['tipe_file'] ?? 'pdf') ?>"
                                                    data-max-kb="<?= (int) $syarat['max_size_kb'] ?>"
                                                    data-wajib="<?= $isWajib ? '1' : '0' ?>"
                                                    data-urutan="<?= (int) $syarat['urutan'] ?>"
                                                    data-status="<?= esc($syarat['status']) ?>"
                                                    data-toggle="tooltip" title="Edit Persyaratan">
                                                <i class="fas fa-edit fa-fw mr-1"></i>Edit
                                            </button>

                                            <!-- Tombol Hapus -->
                                            <button type="button" class="btn btn-danger btn-hapus-syarat"
                                                    data-id="<?= $idSyarat ?>"
                                                    data-nama="<?= esc($syarat['nama']) ?>"
                                                    data-toggle="tooltip" title="Hapus Persyaratan">
                                                <i class="fas fa-trash fa-fw"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Ringkasan Data -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top">
                <div class="text-muted small">
                    Menampilkan <strong><?= count($semuaPersyaratan) ?></strong> persyaratan SKL (<strong><?= count($persyaratanAktif) ?></strong> aktif, <strong><?= count($semuaPersyaratan) - count($persyaratanAktif) ?></strong> nonaktif)
                </div>
            </div>
        </div>
    </div>

<?php else: ?>

    <!-- =============================================================== -->
    <!-- SKL MAHASISWA (PENGAJUAN & MONITORING)                          -->
    <!-- =============================================================== -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Monitoring &amp; Verifikasi Pengajuan SKL Mahasiswa</h6>
        </div>
            <div class="card-body">

                <!-- Filter: Semester, Status, dan Pencarian Mahasiswa -->
                <form class="mb-4" method="get" action="<?= base_url('graduate/skl_mahasiswa') ?>">
                    <div class="form-row align-items-end justify-content-end">
                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="font-weight-bold text-gray-700 small mb-1" for="filterSemester">Pilih Semester</label>
                            <select class="custom-select custom-select-sm" id="filterSemester" name="semester">
                                <option value="semua" <?= ($semesterAktif === 'semua') ? 'selected' : '' ?>>Semua Semester</option>
                                <?php foreach ($daftarSemester as $sem): 
                                    $labelSem = SemesterGraduateModel::formatNama($sem['nama'] ?? '', $sem['kode'] ?? '');
                                ?>
                                <option value="<?= esc($sem['kode']) ?>" <?= ($semesterAktif === $sem['kode']) ? 'selected' : '' ?>>
                                    <?= esc($labelSem) ?><?= ($sem['status'] ?? '') === 'AKTIF' ? ' (Aktif)' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 col-sm-6 mb-2">
                            <label class="font-weight-bold text-gray-700 small mb-1" for="filterStatus">Status SKL</label>
                            <select class="custom-select custom-select-sm" id="filterStatus" name="status">
                                <?php foreach ($daftarFilterStatus as $val => $lbl): ?>
                                    <option value="<?= esc($val) ?>" <?= ($pilihanStatus === $val) ? 'selected' : '' ?>><?= esc($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3 col-sm-8 mb-2">
                            <label class="font-weight-bold text-gray-700 small mb-1" for="filterCari">Cari Mahasiswa</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="filterCari" name="cari" placeholder="Cari NPM / nama..." value="<?= esc($kataCari) ?>">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2 col-sm-4 mb-2">
                            <div class="d-flex">
                                <button type="submit" class="btn btn-sm btn-primary flex-grow-1 mr-1"><i class="fas fa-filter fa-sm mr-1"></i> Terapkan</button>
                                <?php if ($kataCari !== '' || $pilihanStatus !== '' || ($semesterAktif !== ($kodeSemesterAktif !== '' ? $kodeSemesterAktif : 'semua'))): ?>
                                    <a href="<?= base_url('graduate/skl_mahasiswa') ?>" class="btn btn-sm btn-outline-secondary" title="Reset filter"><i class="fas fa-undo fa-sm"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                        <thead>
                            <tr class="text-center bg-light">
                                <th style="width: 4%;">No</th>
                                <th style="width: 11%;">NPM</th>
                                <th style="width: 18%;">Nama Mahasiswa</th>
                                <th style="width: 14%;">Program Studi</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 31%;">Berkas Persyaratan Dinamis</th>
                                <th style="width: 12%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daftarPengajuan)): ?>
                                <tr class="text-center">
                                    <td colspan="7" class="text-gray-500 py-4">
                                        <?php if ($kataCari !== '' || $pilihanStatus !== '' || ($semesterAktif !== ($kodeSemesterAktif !== '' ? $kodeSemesterAktif : 'semua'))): ?>
                                            Tidak ditemukan pengajuan SKL dengan kriteria filter tersebut.
                                        <?php else: ?>
                                            Belum ada pengajuan SKL dari mahasiswa.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($daftarPengajuan as $p): ?>
                                    <?php
                                    $npm         = (string) $p['npm'];
                                    $statusKey   = strtolower($p['status'] ?? 'menunggu');
                                    $badgeInfo   = $statusBadges[$statusKey] ?? ['label' => ucfirst($statusKey), 'class' => 'badge-secondary'];
                                    $mhsBerkas   = $berkasPerMahasiswa[$npm] ?? [];
                                    $pengajuanId = (int) $p['id'];
                                    ?>
                                    <tr class="text-center align-middle">
                                        <td class="align-middle"><?= $no++ ?></td>
                                        <td class="align-middle font-weight-bold text-gray-800"><?= esc($npm) ?></td>
                                        <td class="text-left align-middle font-weight-bold">
                                            <div class="text-gray-900"><?= esc($p['nama']) ?></div>
                                            <?php if (!empty($p['kode_semester'])): ?>
                                                <small class="badge badge-light border text-muted">
                                                    <i class="far fa-calendar-alt mr-1 text-primary"></i><?= esc(SemesterGraduateModel::formatNama('', $p['kode_semester'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle small"><?= esc($p['nama_prodi'] ?? 'Teknik Informatika') ?></td>
                                        <td class="align-middle">
                                            <span class="badge <?= $badgeInfo['class'] ?> px-2 py-1"><?= esc($badgeInfo['label']) ?></span>
                                            <?php if ($statusKey === 'proses'): ?>
                                                <small class="d-block text-info mt-1 font-weight-bold"><i class="fas fa-file-alt mr-1"></i>Siap Kirim</small>
                                            <?php elseif ($statusKey === 'disetujui' && !empty($p['file_skl'])): ?>
                                                <small class="d-block text-success mt-1"><i class="fas fa-check-double mr-1"></i>SKL Terkirim</small>
                                            <?php elseif ($statusKey === 'ditolak' && !empty($p['catatan'])): ?>
                                                <i class="fas fa-info-circle text-danger ml-1" data-toggle="tooltip" data-placement="top" title="<?= esc($p['catatan']) ?>"></i>
                                            <?php endif; ?>
                                        </td>
                                        <!-- Kolom Berkas: Tombol dinamis sesuai Persyaratan SKL Aktif -->
                                        <td class="text-center align-middle">
                                            <?php if (empty($persyaratanAktif)): ?>
                                                <span class="text-muted small">Belum ada persyaratan aktif</span>
                                            <?php else: ?>
                                                <?php foreach ($persyaratanAktif as $sIdx => $syarat): ?>
                                                    <?php
                                                    $syaratId = (int) $syarat['id'];
                                                    $namaSyarat = $syarat['nama'];
                                                    $color = $docColorPalette[$sIdx % count($docColorPalette)];
                                                    $berkasData = $mhsBerkas[$syaratId] ?? ($mhsBerkas[$namaSyarat] ?? null);
                                                    ?>
                                                    <?php if ($berkasData !== null && !empty($berkasData['nama_file'])): ?>
                                                        <a href="<?= base_url('graduate/skl/preview/' . rawurlencode($berkasData['nama_file'])) ?>" target="_blank"
                                                           class="btn btn-sm btn-<?= $color ?> m-1 shadow-sm font-weight-bold"
                                                           data-toggle="tooltip" data-placement="top" title="<?= esc($namaSyarat) ?>: <?= esc($berkasData['nama_file']) ?> (Klik untuk lihat)">
                                                            <i class="fas fa-file-pdf mr-1"></i><?= esc($namaSyarat) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary m-1" disabled
                                                                data-toggle="tooltip" data-placement="top" title="<?= esc($namaSyarat) ?> belum diunggah">
                                                            <i class="fas fa-times-circle mr-1"></i><?= esc($namaSyarat) ?>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <!-- Kolom Aksi Verifikasi -->
                                        <td class="align-middle text-nowrap">
                                            <?php if ($statusKey === 'menunggu'): ?>
                                                <button type="button" class="btn btn-info btn-sm shadow-sm font-weight-bold btn-proses-skl"
                                                        data-id="<?= $pengajuanId ?>"
                                                        data-nama="<?= esc($p['nama']) ?>"
                                                        data-toggle="tooltip" title="Ubah status menjadi Diproses dan siapkan template SKL">
                                                    <i class="fas fa-cog mr-1"></i> Proses
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm shadow-sm btn-tolak-skl ml-1"
                                                        data-id="<?= $pengajuanId ?>"
                                                        data-nama="<?= esc($p['nama']) ?>"
                                                        data-catatan="<?= esc($p['catatan'] ?? '') ?>"
                                                        data-toggle="tooltip" title="Tolak dan minta revisi berkas">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif ($statusKey === 'proses'): ?>
                                                <a href="<?= base_url('graduate/skl_download_template/' . $pengajuanId) ?>"
                                                   class="btn btn-outline-primary btn-sm shadow-sm font-weight-bold mr-1"
                                                   data-toggle="tooltip" title="Download template draf SKL resmi format Word (.docx) untuk diedit nomor &amp; dicetak">
                                                    <i class="fas fa-file-word mr-1"></i> Template (DOCX)
                                                </a>
                                                <button type="button" class="btn btn-success btn-sm shadow-sm font-weight-bold btn-buka-kirim-skl"
                                                        data-id="<?= $pengajuanId ?>"
                                                        data-nama="<?= esc($p['nama']) ?>"
                                                        data-npm="<?= esc($npm) ?>"
                                                        data-no="<?= esc($p['no_surat'] ?? '') ?>"
                                                        data-toggle="tooltip" title="Unggah dan kirim file SKL resmi yang sudah disahkan ke mahasiswa">
                                                    <i class="fas fa-paper-plane mr-1"></i> Kirim SKL
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm shadow-sm btn-tolak-skl ml-1"
                                                        data-id="<?= $pengajuanId ?>"
                                                        data-nama="<?= esc($p['nama']) ?>"
                                                        data-catatan="<?= esc($p['catatan'] ?? '') ?>"
                                                        data-toggle="tooltip" title="Tolak dan minta revisi">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif ($statusKey === 'disetujui'): ?>
                                                <?php if (!empty($p['file_skl'])): ?>
                                                    <a href="<?= base_url('graduate/skl_download_resmi/' . $pengajuanId) ?>"
                                                       class="btn btn-outline-success btn-sm font-weight-bold mr-1 shadow-sm"
                                                       data-toggle="tooltip" title="Lihat / download file SKL resmi yang telah dikirim">
                                                        <i class="fas fa-file-pdf mr-1"></i> SKL
                                                    </a>
                                                <?php endif; ?>
                                                <div class="dropdown d-inline-block">
                                                    <button type="button" class="btn btn-light btn-sm border dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pilihan lainnya">
                                                        <i class="fas fa-ellipsis-v fa-sm"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right shadow">
                                                        <a class="dropdown-item small" href="<?= base_url('graduate/skl_download_template/' . $pengajuanId) ?>">
                                                            <i class="fas fa-file-word fa-fw mr-1 text-primary"></i> Download Template SKL (DOCX)
                                                        </a>
                                                        <a class="dropdown-item small btn-buka-kirim-skl" href="#"
                                                           data-id="<?= $pengajuanId ?>"
                                                           data-nama="<?= esc($p['nama']) ?>"
                                                           data-npm="<?= esc($npm) ?>"
                                                           data-no="<?= esc($p['no_surat'] ?? '') ?>">
                                                            <i class="fas fa-sync-alt fa-fw mr-1 text-success"></i> Kirim Ulang / Ralat SKL
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php else: // ditolak ?>
                                                <button type="button" class="btn btn-outline-info btn-sm shadow-sm font-weight-bold btn-proses-skl"
                                                        data-id="<?= $pengajuanId ?>"
                                                        data-nama="<?= esc($p['nama']) ?>"
                                                        data-toggle="tooltip" title="Proses ulang pengajuan SKL">
                                                    <i class="fas fa-redo-alt mr-1"></i> Proses Ulang
                                                </button>
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

<?php endif; ?>

<!-- =============================================================== -->
<!-- MODAL-MODAL OPERASIONAL                                        -->
<!-- =============================================================== -->

<!-- Modal Tambah Persyaratan SKL -->
<div class="modal fade" id="modalTambahPersyaratan" tabindex="-1" role="dialog" aria-labelledby="modalTambahPersyaratanLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="<?= base_url('graduate/persyaratanSklStore') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-primary" id="modalTambahPersyaratanLabel"><i class="fas fa-plus-circle mr-1"></i> Tambah Persyaratan SKL</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Nama Persyaratan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" placeholder="Contoh: Bebas Perpustakaan / Sertifikat TOEIC" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Keterangan / Instruksi</label>
                        <textarea class="form-control" name="keterangan" rows="2" placeholder="Penjelasan berkas atau pedoman bagi mahasiswa"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Format Ekstensi</label>
                            <input type="text" class="form-control" name="tipe_file" value="pdf" placeholder="pdf,jpg,png" required>
                            <small class="text-muted">Pisahkan dengan koma (contoh: pdf,jpg)</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Batas Ukuran (KB)</label>
                            <input type="number" class="form-control" name="max_size_kb" value="5120" min="500" max="20480" required>
                            <small class="text-muted">5120 KB = 5 MB</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Sifat Berkas</label>
                            <select class="custom-select" name="is_wajib">
                                <option value="1" selected>Wajib</option>
                                <option value="0">Opsional</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Urutan</label>
                            <input type="number" class="form-control" name="urutan" value="<?= count($semuaPersyaratan) + 1 ?>" min="1">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Status Awal</label>
                            <select class="custom-select" name="status">
                                <option value="AKTIF" selected>AKTIF</option>
                                <option value="NON-AKTIF">NON-AKTIF</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan Persyaratan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Persyaratan SKL -->
<div class="modal fade" id="modalEditPersyaratan" tabindex="-1" role="dialog" aria-labelledby="modalEditPersyaratanLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="<?= base_url('graduate/persyaratanSklUpdate') ?>" id="formEditPersyaratan">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="editSyaratId" value="">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-info" id="modalEditPersyaratanLabel"><i class="fas fa-edit mr-1"></i> Edit Persyaratan SKL</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Nama Persyaratan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" id="editNama" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Keterangan / Instruksi</label>
                        <textarea class="form-control" name="keterangan" id="editKeterangan" rows="2"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Format Ekstensi</label>
                            <input type="text" class="form-control" name="tipe_file" id="editTipeFile" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Batas Ukuran (KB)</label>
                            <input type="number" class="form-control" name="max_size_kb" id="editMaxSizeKb" min="500" max="20480" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Sifat Berkas</label>
                            <select class="custom-select" name="is_wajib" id="editIsWajib">
                                <option value="1">Wajib</option>
                                <option value="0">Opsional</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Urutan</label>
                            <input type="number" class="form-control" name="urutan" id="editUrutan" min="1">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Status</label>
                            <select class="custom-select" name="status" id="editStatus">
                                <option value="AKTIF">AKTIF</option>
                                <option value="NON-AKTIF">NON-AKTIF</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-check mr-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Verifikasi: Setujui SKL -->
<div class="modal fade" id="modalSetujuiSkl" tabindex="-1" role="dialog" aria-labelledby="modalSetujuiSklLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="<?= base_url('graduate/sklVerifikasi') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="setujuiPengajuanId" value="">
                <input type="hidden" name="status" value="disetujui">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-success" id="modalSetujuiSklLabel"><i class="fas fa-check-circle mr-1"></i> Setujui Pengajuan SKL</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin menyetujui seluruh dokumen kelayakan SKL untuk mahasiswa:</p>
                    <h5 class="font-weight-bold text-dark text-center py-2" id="setujuiNamaMahasiswa">-</h5>
                    <p class="small text-muted mb-0">Setelah disetujui, mahasiswa dinyatakan memenuhi syarat dan SKL dapat dicetak/diambil.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i> Ya, Setujui SKL</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Verifikasi: Tolak SKL -->
<div class="modal fade" id="modalTolakSkl" tabindex="-1" role="dialog" aria-labelledby="modalTolakSklLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="<?= base_url('graduate/sklVerifikasi') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="tolakPengajuanId" value="">
                <input type="hidden" name="status" value="ditolak">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-danger" id="modalTolakSklLabel"><i class="fas fa-times-circle mr-1"></i> Tolak / Minta Revisi SKL</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Menolak pengajuan SKL untuk mahasiswa:</p>
                    <h6 class="font-weight-bold text-dark" id="tolakNamaMahasiswa">-</h6>
                    <div class="form-group mt-3">
                        <label class="font-weight-bold text-gray-800">Alasan Penolakan / Catatan Perbaikan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="catatan" id="tolakCatatan" rows="3" placeholder="Sebutkan berkas yang belum valid atau petunjuk perbaikan bagi mahasiswa..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane mr-1"></i> Kirim Penolakan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi: Ubah ke Status Proses -->
<div class="modal fade" id="modalProsesSkl" tabindex="-1" role="dialog" aria-labelledby="modalProsesSklLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="<?= base_url('graduate/sklProses') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="prosesPengajuanId" value="">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold" id="modalProsesSklLabel"><i class="fas fa-cogs mr-2"></i> Proses Pengajuan SKL</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Ubah status pengajuan SKL menjadi <strong>DIPROSES</strong> untuk mahasiswa:</p>
                    <h5 class="font-weight-bold text-dark text-center py-2" id="prosesNamaMahasiswa">-</h5>
                    <div class="alert alert-light border small text-muted mb-0">
                        <i class="fas fa-info-circle text-info mr-1"></i> Setelah status diubah menjadi <strong>Diproses</strong>, Anda dapat mengunduh Template Surat SKL format Word (.docx) terisi data otomatis untuk diedit nomor suratnya dan disahkan.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info font-weight-bold"><i class="fas fa-play mr-1"></i> Mulai Proses SKL</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Kirim & Unggah SKL Resmi ke Mahasiswa -->
<div class="modal fade" id="modalKirimSkl" tabindex="-1" role="dialog" aria-labelledby="modalKirimSklLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="<?= base_url('graduate/sklKirim') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="kirimPengajuanId" value="">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold" id="modalKirimSklLabel"><i class="fas fa-paper-plane mr-2"></i> Kirim SKL Resmi ke Mahasiswa</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small text-muted mb-3">
                        <i class="fas fa-check-circle text-success mr-1"></i> Mengirimkan berkas SKL akan otomatis mengubah status pengajuan mahasiswa menjadi <strong>DISETUJUI</strong> dan mahasiswa dapat langsung mengunduh dokumen SKL resmi tersebut di akunnya.
                    </div>

                    <div class="card bg-light border-0 p-2 mb-3">
                        <div class="small text-muted">Mahasiswa Penerima:</div>
                        <div class="font-weight-bold text-gray-900" id="kirimNamaMahasiswa">-</div>
                        <div class="small text-gray-700" id="kirimNpmMahasiswa">-</div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold text-dark small mb-1">Tanggal Terbit Surat</label>
                        <input type="date" class="form-control form-control-sm" name="tgl_terbit" id="kirimTglTerbit" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold text-dark small mb-1">Upload Berkas SKL Resmi (PDF / Gambar) <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="kirimFileSkl" name="file_skl" accept=".pdf,.jpg,.jpeg,.png" required>
                            <label class="custom-file-label text-truncate" for="kirimFileSkl">Pilih file SKL resmi...</label>
                        </div>
                        <small class="text-muted">Format berkas: PDF, JPG, PNG (Maks. 5 MB)</small>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-dark small mb-1">Catatan Tambahan (Opsional)</label>
                        <textarea class="form-control form-control-sm" name="catatan" id="kirimCatatan" rows="2" placeholder="Informasi tambahan untuk mahasiswa (misal: jadwal pengambilan fisik di TU)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold"><i class="fas fa-paper-plane mr-1"></i> Kirim &amp; Setujui SKL</button>
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
            // Tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Filter Cepat Pencarian Tabel Persyaratan SKL
            $('#cariPersyaratanSkl').on('keyup', function () {
                var value = $(this).val().toLowerCase();
                $('#tablePersyaratanSkl tbody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            // Setujui SKL
            $(document).on('click', '.btn-setujui-skl', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                var nama = $(this).data('nama');

                $('#setujuiPengajuanId').val(id);
                $('#setujuiNamaMahasiswa').text(nama);
                $('#modalSetujuiSkl').modal('show');
            });

            // Tolak SKL
            $(document).on('click', '.btn-tolak-skl', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var catatan = $(this).data('catatan') || '';

                $('#tolakPengajuanId').val(id);
                $('#tolakNamaMahasiswa').text(nama);
                $('#tolakCatatan').val(catatan);
                $('#modalTolakSkl').modal('show');
            });

            // Modal Proses SKL
            $(document).on('click', '.btn-proses-skl', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                var nama = $(this).data('nama');

                $('#prosesPengajuanId').val(id);
                $('#prosesNamaMahasiswa').text(nama);
                $('#modalProsesSkl').modal('show');
            });

            // Modal Kirim SKL
            $(document).on('click', '.btn-buka-kirim-skl', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var npm = $(this).data('npm');
                var noSurat = $(this).data('no') || '';

                $('#kirimPengajuanId').val(id);
                $('#kirimNamaMahasiswa').text(nama);
                $('#kirimNpmMahasiswa').text('NPM: ' + npm);
                $('#modalKirimSkl').modal('show');
            });

            // Update label custom-file-input saat file dipilih
            $(document).on('change', '.custom-file-input', function () {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName || 'Pilih file...');
            });

            // Edit Persyaratan SKL
            $(document).on('click', '.btn-edit-syarat', function () {
                var $btn = $(this);
                $('#editSyaratId').val($btn.data('id'));
                $('#editNama').val($btn.data('nama'));
                $('#editKeterangan').val($btn.data('keterangan'));
                $('#editTipeFile').val($btn.data('tipe'));
                $('#editMaxSizeKb').val($btn.data('max-kb'));
                $('#editIsWajib').val($btn.data('wajib'));
                $('#editUrutan').val($btn.data('urutan'));
                $('#editStatus').val($btn.data('status'));

                $('#modalEditPersyaratan').modal('show');
            });

            // Hapus Persyaratan SKL
            $(document).on('click', '.btn-hapus-syarat', function () {
                var id = $(this).data('id');
                var nama = $(this).data('nama');

                var doDelete = function () {
                    var $form = $('<form method="post" action="<?= base_url('graduate/persyaratanSklDelete') ?>"></form>');
                    var csrfName = '<?= csrf_token() ?>';
                    var csrfHash = $('input[name="' + csrfName + '"]').val() || '<?= csrf_hash() ?>';
                    $form.append('<input type="hidden" name="' + csrfName + '" value="' + csrfHash + '">');
                    $form.append('<input type="hidden" name="id" value="' + id + '">');
                    $('body').append($form);
                    $form.submit();
                };

                if (window.Swal) {
                    Swal.fire({
                        title: 'Hapus Persyaratan SKL?',
                        text: 'Persyaratan "' + nama + '" akan dihapus permanen dari sistem.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#858796',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            doDelete();
                        }
                    });
                } else {
                    if (confirm('Hapus persyaratan SKL "' + nama + '"?')) {
                        doDelete();
                    }
                }
            });
        });
    </script>

<?= $this->endSection(); ?>
