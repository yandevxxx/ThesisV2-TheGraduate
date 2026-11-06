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

$daftarBerkasPendaftaran = $daftarBerkasPendaftaran ?? [];
$berkasPerPendaftaran    = $berkasPerPendaftaran ?? [];
$daftarSemester          = $daftarSemester ?? [];
$semesterAktif           = $semesterAktif ?? '';
$statusFilter            = $statusFilter ?? '';
$kataCari                = $kataCari ?? '';

$formatDate = static function (?string $date): string {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $time = strtotime($date);
    return ($time !== false && $time > 0) ? date('d/m/Y', $time) : '-';
};

// Siapkan data detail JSON untuk modal preview berkas
$dataDetail = [];
foreach ($daftarBerkasPendaftaran as $p) {
    $dataDetail[$p['id']] = [
        'id'       => $p['id'],
        'npm'      => $p['npm'] ?? '-',
        'nama'     => $p['nama'] ?? '-',
        'prodi'    => $p['prodi'] ?? '-',
        'status'   => $p['status_berkas'] ?? 'MENUNGGU',
        'catatan'  => $p['catatan_berkas'] ?? '',
        'berkas'   => $berkasPerPendaftaran[$p['id']] ?? [],
    ];
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-folder-open text-primary mr-2"></i>Verifikasi Berkas Persyaratan Wisuda</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-sm-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Antrean Verifikasi Berkas Mahasiswa</h6>
        <span class="small text-muted">Menampilkan mahasiswa yang telah mengirimkan berkas persyaratan</span>
    </div>
    <div class="card-body">

        <!-- Filter: Semester, Status, dan Pencarian -->
        <form class="mb-4" method="get" action="<?= base_url('graduate/berkas_wisuda_verifikasi') ?>">
            <div class="form-row align-items-end justify-content-end">
                <div class="col-md-3 col-sm-6 mb-2">
                    <label class="font-weight-bold text-gray-700 small mb-1" for="filterSemester">Pilih Semester</label>
                    <select class="custom-select custom-select-sm" id="filterSemester" name="semester">
                        <option value="semua" <?= ($semesterAktif === 'semua' || $semesterAktif === '') ? 'selected' : '' ?>>Semua Semester</option>
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
                    <label class="font-weight-bold text-gray-700 small mb-1" for="filterStatus">Status Berkas</label>
                    <select class="custom-select custom-select-sm" id="filterStatus" name="status">
                        <option value="" <?= ($statusFilter === '' || $statusFilter === 'semua') ? 'selected' : '' ?>>Semua Status</option>
                        <option value="MENUNGGU" <?= ($statusFilter === 'MENUNGGU') ? 'selected' : '' ?>>Menunggu</option>
                        <option value="DISETUJUI" <?= ($statusFilter === 'DISETUJUI') ? 'selected' : '' ?>>Disetujui</option>
                        <option value="DITOLAK" <?= ($statusFilter === 'DITOLAK') ? 'selected' : '' ?>>Ditolak</option>
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
                    <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-filter fa-sm mr-1"></i> Terapkan</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 12%;">NPM</th>
                        <th style="width: 26%;">Nama Mahasiswa</th>
                        <th style="width: 16%;">Prodi</th>
                        <th style="width: 12%;">Tanggal Yudisium</th>
                        <th style="width: 13%;">Jumlah Berkas</th>
                        <th style="width: 14%;">Status Berkas</th>
                        <th style="width: 12%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarBerkasPendaftaran)): ?>
                    <tr class="text-center">
                        <td colspan="8" class="text-gray-500 py-4">
                            <i class="fas fa-folder-open fa-2x mb-2 text-gray-300 d-block"></i>
                            Tidak ada data berkas persyaratan wisuda yang sesuai filter.
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php $no = 1; foreach ($daftarBerkasPendaftaran as $p): 
                        $files = $berkasPerPendaftaran[$p['id']] ?? [];
                        $totalFiles = count($files);
                    ?>
                    <tr class="text-center align-middle">
                        <td><?= $no++ ?></td>
                        <td class="font-weight-bold"><?= esc($p['npm']) ?></td>
                        <td class="text-left font-weight-bold text-gray-900"><?= esc($p['nama']) ?></td>
                        <td><?= esc($p['prodi'] ?? '-') ?></td>
                        <td><?= esc($formatDate($p['tanggal_lulus'] ?? null)) ?></td>
                        <td>
                            <span class="badge badge-primary px-2 py-1"><i class="fas fa-file-alt mr-1"></i> <?= $totalFiles ?> Berkas</span>
                        </td>
                        <td>
                            <?php 
                            $st = $p['status_berkas'] ?? 'MENUNGGU';
                            if ($st === 'DISETUJUI'): ?>
                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Disetujui</span>
                            <?php elseif ($st === 'DITOLAK'): ?>
                                <span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i> Ditolak</span>
                            <?php else: ?>
                                <span class="badge badge-warning px-2 py-1 text-dark"><i class="fas fa-hourglass-half mr-1"></i> Menunggu</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm periksa-berkas" data-toggle="tooltip" data-placement="top" title="Periksa Berkas" data-id="<?= esc($p['id']) ?>">
                                <i class="fas fa-search mr-1"></i> Periksa
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Modal Periksa & Verifikasi Berkas -->
<div class="modal fade" id="modalPeriksaBerkas" tabindex="-1" role="dialog" aria-labelledby="modalPeriksaBerkasLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalPeriksaBerkasLabel"><i class="fas fa-folder-open mr-2"></i> Periksa Dokumen Persyaratan Wisuda</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Info Singkat Mahasiswa -->
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body py-2 px-3">
                        <div class="row align-items-center">
                            <div class="col-sm-8">
                                <h6 class="font-weight-bold text-gray-900 mb-0" id="berkasModalNama">-</h6>
                                <small class="text-muted">NPM: <strong id="berkasModalNpm" class="text-primary">-</strong> | Prodi: <span id="berkasModalProdi">-</span></small>
                            </div>
                            <div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
                                <span id="berkasModalStatusBadge" class="badge badge-warning px-3 py-2 text-dark font-weight-bold">MENUNGGU</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Dokumen Terunggah -->
                <h6 class="font-weight-bold text-gray-800 mb-2"><i class="fas fa-list-alt mr-1"></i> Berkas yang Diunggah Mahasiswa</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm table-hover mb-0">
                        <thead class="thead-light text-center">
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 45%;">Nama Persyaratan</th>
                                <th style="width: 20%;">Ukuran File</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 15%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detailBerkasTbody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Form Verifikasi Berkas -->
                <form id="formVerifikasiBerkas" method="post" action="<?= base_url('graduate/berkasWisudaVerifikasi') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="verifikasiBerkasPendaftaranId" value="">
                    <input type="hidden" name="status" id="verifikasiBerkasStatusInput" value="">

                    <div class="form-group mb-2">
                        <label for="catatanBerkas" class="font-weight-bold text-gray-800">
                            Catatan Verifikasi Berkas <small class="text-muted">(Wajib diisi jika menolak berkas)</small>
                        </label>
                        <textarea class="form-control" id="catatanBerkas" name="catatan_berkas" rows="3" placeholder="Tuliskan catatan perbaikan berkas (misal: scan ijazah buram, toefl kadaluarsa, dsb.)..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end align-items-center mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-secondary mr-auto" data-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-danger mr-2" id="btnTolakBerkas">
                            <i class="fas fa-times-circle mr-1"></i> Tolak Berkas
                        </button>
                        <button type="button" class="btn btn-success" id="btnSetujuiBerkas">
                            <i class="fas fa-check-circle mr-1"></i> Setujui Seluruh Berkas
                        </button>
                    </div>
                </form>

            </div>
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

            // Buka Modal Periksa Berkas
            $('.periksa-berkas').on('click', function () {
                var id = $(this).data('id');
                var d  = dataDetail[id];
                if (!d) return;

                $('#verifikasiBerkasPendaftaranId').val(d.id);
                $('#berkasModalNama').text(d.nama);
                $('#berkasModalNpm').text(d.npm);
                $('#berkasModalProdi').text(d.prodi);
                $('#catatanBerkas').val(d.catatan || '');

                var badge = $('#berkasModalStatusBadge');
                badge.removeClass('badge-success badge-danger badge-warning');
                if (d.status === 'DISETUJUI') {
                    badge.addClass('badge-success').text('DISETUJUI');
                } else if (d.status === 'DITOLAK') {
                    badge.addClass('badge-danger').text('DITOLAK');
                } else {
                    badge.addClass('badge-warning text-dark').text('MENUNGGU');
                }

                var tbody = $('#detailBerkasTbody');
                tbody.empty();

                if (!d.berkas || d.berkas.length === 0) {
                    tbody.append('<tr><td colspan="5" class="text-center text-muted py-3">Belum ada berkas yang diunggah.</td></tr>');
                } else {
                    $.each(d.berkas, function (idx, b) {
                        var viewUrl = '<?= base_url('graduate/berkas/view') ?>/' + encodeURIComponent(b.nama_file);
                        var sizeText = b.ukuran_kb ? (b.ukuran_kb + ' KB') : '-';
                        var stBadge = '<span class="badge badge-success px-2 py-1">Diunggah</span>';

                        var rowHtml = '<tr class="text-center align-middle">' +
                                      '<td>' + (idx + 1) + '</td>' +
                                      '<td class="text-left font-weight-bold text-gray-900">' + $('<div>').text(b.nama_persyaratan || '-').html() + '</td>' +
                                      '<td>' + sizeText + '</td>' +
                                      '<td>' + stBadge + '</td>' +
                                      '<td><a href="' + viewUrl + '" target="_blank" class="btn btn-sm btn-info py-0 px-2" title="Lihat Berkas"><i class="fas fa-eye mr-1"></i> Buka</a></td>' +
                                      '</tr>';
                        tbody.append(rowHtml);
                    });
                }

                $('#modalPeriksaBerkas').modal('show');
            });

            // Action Setujui Berkas
            $('#btnSetujuiBerkas').on('click', function () {
                var nama = $('#berkasModalNama').text();
                Swal.fire({
                    title: 'Setujui Berkas?',
                    text: 'Apakah Anda yakin ingin menyetujui seluruh berkas persyaratan wisuda mahasiswa ' + nama + '?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Setujui',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#verifikasiBerkasStatusInput').val('DISETUJUI');
                        $('#formVerifikasiBerkas').submit();
                    }
                });
            });

            // Action Tolak Berkas
            $('#btnTolakBerkas').on('click', function () {
                var catatan = $.trim($('#catatanBerkas').val());
                if (catatan === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Catatan Wajib Diisi',
                        text: 'Harap berikan catatan perbaikan atau alasan penolakan berkas mahasiswa.',
                        confirmButtonColor: '#1C476C'
                    });
                    $('#catatanBerkas').focus();
                    return;
                }

                var nama = $('#berkasModalNama').text();
                Swal.fire({
                    title: 'Tolak Berkas?',
                    text: 'Apakah Anda yakin ingin menolak berkas persyaratan wisuda mahasiswa ' + nama + '?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Tolak',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#verifikasiBerkasStatusInput').val('DITOLAK');
                        $('#formVerifikasiBerkas').submit();
                    }
                });
            });
        });
    </script>

<?= $this->endSection(); ?>

