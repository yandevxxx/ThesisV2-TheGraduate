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
$daftarSemester = $daftarSemester ?? [];
$semesterAktif  = $semesterAktif ?? '';
$statusFilter   = $statusFilter ?? '';
$kataCari       = $kataCari ?? '';

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
        'semester'         => $p['kode_semester'] ?? '-',
        'tglLulus'         => $formatDate($p['tanggal_lulus'] ?? null),
        'gelombang'        => $p['gelombang_wisuda'] ?? '-',
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
    <div class="card-header py-3 d-sm-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Antrean Verifikasi Biodata Mahasiswa</h6>
        <span class="small text-muted">Menampilkan pendaftaran yang telah dikirimkan oleh mahasiswa</span>
    </div>
    <div class="card-body">

        <!-- Filter: Semester, Status, dan Pencarian -->
        <form class="mb-4" method="get" action="<?= base_url('graduate/biodata_wisuda_verifikasi') ?>">
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
                    <label class="font-weight-bold text-gray-700 small mb-1" for="filterStatus">Status Biodata</label>
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
                        <img id="detailFoto" src="<?= base_url('assets/img/undraw_profile.svg') ?>" alt="Pas Foto Mahasiswa" class="img-thumbnail rounded mb-2 shadow-sm" style="max-height: 220px; width: 170px; object-fit: cover;">
                        <div>
                            <span id="detailStatusBadge" class="badge badge-warning px-3 py-2 text-dark font-weight-bold">MENUNGGU</span>
                        </div>
                    </div>

                    <!-- Data Mahasiswa -->
                    <div class="col-md-8">
                        <table class="table table-sm table-striped">
                            <tbody>
                                <tr>
                                    <th style="width: 35%;">NPM</th>
                                    <td>: <strong id="detailNpm" class="text-primary">-</strong></td>
                                </tr>
                                <tr>
                                    <th>Nama Lengkap</th>
                                    <td>: <strong id="detailNama" class="text-gray-900">-</strong></td>
                                </tr>
                                <tr>
                                    <th>NIK (KTP)</th>
                                    <td>: <span id="detailNik">-</span></td>
                                </tr>
                                <tr>
                                    <th>Tempat, Tanggal Lahir</th>
                                    <td>: <span id="detailTtl">-</span></td>
                                </tr>
                                <tr>
                                    <th>Jenis Kelamin</th>
                                    <td>: <span id="detailJk">-</span></td>
                                </tr>
                                <tr>
                                    <th>Program Studi</th>
                                    <td>: <span id="detailProdi">-</span></td>
                                </tr>
                                <tr>
                                    <th>Total SKS / IPK</th>
                                    <td>: <span id="detailSks">-</span> SKS &nbsp;|&nbsp; IPK: <strong id="detailIpk" class="text-success">-</strong></td>
                                </tr>
                                <tr>
                                    <th>Semester Kelulusan</th>
                                    <td>: <span id="detailSemester">-</span></td>
                                </tr>
                                <tr>
                                    <th>Tanggal Yudisium</th>
                                    <td>: <span id="detailTglLulus" class="badge badge-secondary">-</span></td>
                                </tr>
                                <tr>
                                    <th>Gelombang Wisuda</th>
                                    <td>: <span id="detailGelombang">-</span></td>
                                </tr>
                                <tr>
                                    <th>No. HP / WA</th>
                                    <td>: <span id="detailHp">-</span></td>
                                </tr>
                                <tr>
                                    <th>E-mail</th>
                                    <td>: <span id="detailEmail">-</span></td>
                                </tr>
                                <tr>
                                    <th>Alamat Lengkap</th>
                                    <td>: <span id="detailAlamat">-</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bagian Tugas Akhir & Pembimbing -->
                <div class="card bg-light border-0 mt-2 mb-3">
                    <div class="card-body py-2 px-3">
                        <h6 class="font-weight-bold text-gray-800 mb-1"><i class="fas fa-book mr-1"></i> Data Tugas Akhir</h6>
                        <p class="mb-1"><strong>Jenis TA:</strong> <span id="detailJenisTa" class="badge badge-light border">Skripsi</span></p>
                        <p class="mb-2"><strong>Judul:</strong> &ldquo;<span id="detailJudul" class="font-weight-bold text-gray-900">-</span>&rdquo;</p>
                        <div class="row small">
                            <div class="col-sm-6">
                                <strong>Pembimbing 1:</strong> <span id="detailDospem1">-</span><br>
                                <strong>Pembimbing 2:</strong> <span id="detailDospem2">-</span>
                            </div>
                            <div class="col-sm-6">
                                <strong>Pembimbing Agama:</strong> <span id="detailDospemAgama">-</span><br>
                                <strong>Dosen Penguji:</strong> <span id="detailPenguji">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Verifikasi Tendik -->
                <form id="formVerifikasiBiodata" method="post" action="<?= base_url('graduate/biodataWisudaVerifikasi') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="verifikasiPendaftaranId" value="">
                    <input type="hidden" name="status" id="verifikasiStatusInput" value="">

                    <div class="form-group mb-2">
                        <label for="catatanBiodata" class="font-weight-bold text-gray-800">
                            Catatan Verifikasi Biodata <small class="text-muted">(Wajib diisi jika menolak biodata)</small>
                        </label>
                        <textarea class="form-control" id="catatanBiodata" name="catatan_biodata" rows="3" placeholder="Tuliskan catatan perbaikan biodata atau catatan persetujuan..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end align-items-center mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-secondary mr-auto" data-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-danger mr-2" id="btnTolakBiodata">
                            <i class="fas fa-times-circle mr-1"></i> Tolak Biodata
                        </button>
                        <button type="button" class="btn btn-success" id="btnSetujuiBiodata">
                            <i class="fas fa-check-circle mr-1"></i> Setujui Biodata
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

            // Buka Modal Detail Biodata
            $('.lihat-biodata').on('click', function () {
                var id = $(this).data('id');
                var d  = dataDetail[id];
                if (!d) return;

                $('#verifikasiPendaftaranId').val(d.id);
                $('#detailFoto').attr('src', d.foto);
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
                $('#detailGelombang').text(d.gelombang);
                $('#detailHp').text(d.hp);
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
                } else if (d.status === 'DITOLAK') {
                    badge.addClass('badge-danger').text('DITOLAK');
                } else {
                    badge.addClass('badge-warning text-dark').text('MENUNGGU');
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
        });
    </script>

<?= $this->endSection(); ?>

