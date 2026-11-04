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

$daftarPendaftaran = $daftarPendaftaran ?? [];
$daftarSemester    = $daftarSemester ?? [];
$semesterAktif     = $semesterAktif ?? '';
$statusFilter      = $statusFilter ?? '';
$kataCari          = $kataCari ?? '';
$berkasPerPendaftaran = $berkasPerPendaftaran ?? [];

$formatDate = static function (?string $date): string {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $time = strtotime($date);
    return ($time !== false && $time > 0) ? date('d/m/Y', $time) : '-';
};

// Siapkan data detail JSON untuk modal preview
$dataDetail = [];
foreach ($daftarPendaftaran as $p) {
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
        'judul'            => $p['judul_skripsi'] ?? '-',
        'dospem1'          => $p['pembimbing1'] ?? '-',
        'dospem2'          => $p['pembimbing2'] ?? '-',
        'dospemAgama'      => $p['pembimbing_agama'] ?? '-',
        'penguji'          => $p['dosen_penguji'] ?? '-',
        'status'           => $p['status_verifikasi'] ?? 'MENUNGGU',
        'catatan'          => $p['catatan_verifikasi'] ?? '',
        'foto'             => !empty($p['pas_foto']) ? base_url($p['pas_foto']) : base_url('assets/img/undraw_profile.svg'),
        'berkas'           => $berkasPerPendaftaran[$p['id']] ?? [],
    ];
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Pendaftaran Wisuda</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Pendaftaran Wisuda</h6>
    </div>
    <div class="card-body">

        <!-- Filter: Semester, Status, dan Pencarian -->
        <form class="mb-4" method="get" action="<?= base_url('graduate/pendaftaran_wisuda') ?>">
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
                    <label class="font-weight-bold text-gray-700 small mb-1" for="filterStatus">Status Verifikasi</label>
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
                        <th style="width: 12%;">Tanggal Lulus</th>
                        <th style="width: 13%;">Status</th>
                        <th style="width: 8%;">Foto</th>
                        <th style="width: 10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarPendaftaran)): ?>
                    <tr class="text-center">
                        <td colspan="8" class="text-gray-500 py-4">
                            <i class="fas fa-user-graduate fa-2x mb-2 text-gray-300 d-block"></i>
                            Tidak ada data pendaftaran wisuda yang sesuai filter.
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php $no = 1; foreach ($daftarPendaftaran as $p): ?>
                    <tr class="text-center align-middle">
                        <td><?= $no++ ?></td>
                        <td class="font-weight-bold"><?= esc($p['npm']) ?></td>
                        <td class="text-left"><?= esc($p['nama']) ?></td>
                        <td><?= esc($p['prodi'] ?? '-') ?></td>
                        <td><?= esc($formatDate($p['tanggal_lulus'] ?? null)) ?></td>
                        <td>
                            <?php 
                            $st = $p['status_verifikasi'] ?? 'MENUNGGU';
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
                            $fotoUrl = !empty($p['pas_foto']) ? base_url($p['pas_foto']) : base_url('assets/img/undraw_profile.svg');
                            ?>
                            <img src="<?= $fotoUrl ?>" alt="Foto <?= esc($p['nama']) ?>" class="rounded-circle shadow-sm" width="38" height="38" style="object-fit: cover;">
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm lihat-pendaftaran" data-toggle="tooltip" data-placement="top" title="Lihat & Verifikasi" data-id="<?= esc($p['id']) ?>">
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

<!-- Modal Form Pendaftaran & Verifikasi -->
<div class="modal fade" id="modalFormPendaftaran" tabindex="-1" role="dialog" aria-labelledby="modalFormPendaftaranLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalFormPendaftaranLabel"><i class="fas fa-user-graduate mr-2"></i> Detail & Verifikasi Pendaftaran Wisuda</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">

                <!-- Header Info Mahasiswa -->
                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-4 border">
                    <div class="d-flex align-items-center">
                        <img id="pfFoto" src="<?= base_url('assets/img/undraw_profile.svg') ?>" alt="Foto Mahasiswa" class="rounded-circle mr-3 border" width="70" height="70" style="object-fit: cover;">
                        <div>
                            <h5 class="mb-1 font-weight-bold text-gray-900" id="pfNama">-</h5>
                            <span class="text-gray-600 mr-3">NPM: <strong id="pfNpm">-</strong></span>
                            <span class="text-gray-600">Prodi: <strong id="pfProdi">-</strong></span>
                        </div>
                    </div>
                    <div id="pfStatusBadge">
                        <span class="badge badge-warning px-3 py-2">Menunggu</span>
                    </div>
                </div>

                <!-- Alert Catatan jika ditolak -->
                <div class="alert alert-danger d-none" id="pfCatatanAlert">
                    <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Catatan Verifikasi:</h6>
                    <p class="mb-0" id="pfCatatanText">-</p>
                </div>

                <ul class="nav nav-tabs mb-3" id="pendaftaranTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="biodata-tab" data-toggle="tab" href="#tabBiodata" role="tab" aria-selected="true"><i class="fas fa-user mr-1"></i> Biodata & Akademik</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="berkas-tab" data-toggle="tab" href="#tabBerkas" role="tab" aria-selected="false"><i class="fas fa-file-alt mr-1"></i> Berkas Persyaratan (<span id="berkasCount">0</span>)</a>
                    </li>
                </ul>

                <div class="tab-content" id="pendaftaranTabContent">
                    <!-- Tab 1: Biodata & Akademik -->
                    <div class="tab-pane fade show active" id="tabBiodata" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr><th colspan="2">Informasi Pribadi</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><th style="width: 40%;">NIK</th><td id="pfNik">-</td></tr>
                                        <tr><th>Tempat, Tgl Lahir</th><td id="pfTtl">-</td></tr>
                                        <tr><th>Jenis Kelamin</th><td id="pfJk">-</td></tr>
                                        <tr><th>No. Handphone</th><td id="pfHp">-</td></tr>
                                        <tr><th>No. Telepon</th><td id="pfTelp">-</td></tr>
                                        <tr><th>E-mail</th><td id="pfEmail">-</td></tr>
                                        <tr><th>Alamat</th><td id="pfAlamat">-</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr><th colspan="2">Informasi Akademik & Skripsi</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><th style="width: 40%;">Program Studi</th><td id="pfProdiAkademik">-</td></tr>
                                        <tr><th>Total SKS</th><td id="pfTotalSks">-</td></tr>
                                        <tr><th>IPK</th><td id="pfIpk">-</td></tr>
                                        <tr><th>Tanggal Lulus</th><td id="pfTglLulus">-</td></tr>
                                        <tr><th>Jadwal / Gelombang</th><td id="pfJadwal">-</td></tr>
                                        <tr><th>Pembimbing 1</th><td id="pfDospem1">-</td></tr>
                                        <tr><th>Pembimbing 2</th><td id="pfDospem2">-</td></tr>
                                        <tr><th>Pembimbing Agama</th><td id="pfDospemAgama">-</td></tr>
                                        <tr><th>Dosen Penguji</th><td id="pfPenguji">-</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-12 mt-2">
                                <div class="card border-left-primary shadow-sm">
                                    <div class="card-body py-2">
                                        <div class="small font-weight-bold text-primary text-uppercase mb-1">Judul Skripsi</div>
                                        <div class="h6 mb-0 text-gray-800" id="pfJudul">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Dokumen Berkas Persyaratan -->
                    <div class="tab-pane fade" id="tabBerkas" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="tableModalBerkas">
                                <thead class="thead-light">
                                    <tr class="text-center">
                                        <th style="width: 5%;">No</th>
                                        <th style="width: 45%;">Nama Dokumen</th>
                                        <th style="width: 20%;">Ukuran</th>
                                        <th style="width: 15%;">Status</th>
                                        <th style="width: 15%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="berkasListBody">
                                    <tr>
                                        <td colspan="5" class="text-center text-gray-500 py-3">Tidak ada berkas yang diunggah.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Form Verifikasi -->
                <hr class="my-4">
                <div class="card bg-light border">
                    <div class="card-header bg-white py-2 font-weight-bold text-gray-800">
                        <i class="fas fa-clipboard-check mr-1 text-primary"></i> Tindakan Verifikasi Tendik / Admin
                    </div>
                    <div class="card-body py-3">
                        <form id="formVerifikasi" method="post" action="<?= base_url('graduate/pendaftaranWisudaVerifikasi') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" id="verifikasiPendaftaranId" value="">

                            <div class="form-group mb-3">
                                <label for="catatanVerifikasi" class="font-weight-bold small text-gray-700">
                                    Catatan Verifikasi <span class="text-muted font-weight-normal">(Wajib diisi jika menolak pendaftaran)</span>
                                </label>
                                <textarea class="form-control" id="catatanVerifikasi" name="catatan_verifikasi" rows="2" placeholder="Tuliskan catatan perbaikan atau alasan penolakan jika berkas belum lengkap..."></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" name="status" value="DITOLAK" class="btn btn-danger btn-sm mr-2" id="btnTolakPendaftaran">
                                    <i class="fas fa-times-circle mr-1"></i> Tolak Pendaftaran
                                </button>
                                <button type="submit" name="status" value="DISETUJUI" class="btn btn-success btn-sm" id="btnSetujuiPendaftaran">
                                    <i class="fas fa-check-circle mr-1"></i> Setujui Pendaftaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

    <script>
        var dataPendaftaran = <?= json_encode($dataDetail, JSON_HEX_TAG) ?>;

        $(document).on('click', '.lihat-pendaftaran', function () {
            var id = $(this).data('id');
            var d = dataPendaftaran[id];

            if (!d) return;

            $('#verifikasiPendaftaranId').val(d.id);
            $('#pfNpm').text(d.npm);
            $('#pfNama').text(d.nama);
            $('#pfProdi').text(d.prodi);
            $('#pfProdiAkademik').text(d.prodi);
            $('#pfNik').text(d.nik);
            $('#pfTtl').text(d.tempatLahir + ', ' + d.tanggalLahir);
            $('#pfJk').text(d.jenisKelamin);
            $('#pfHp').text(d.hp);
            $('#pfTelp').text(d.telepon);
            $('#pfEmail').text(d.email);
            $('#pfAlamat').text(d.alamat);
            $('#pfTotalSks').text(d.totalSks);
            $('#pfIpk').text(d.ipk);
            $('#pfTglLulus').text(d.tglLulus);
            $('#pfJadwal').text(d.gelombang);
            $('#pfJudul').text(d.judul);
            $('#pfDospem1').text(d.dospem1);
            $('#pfDospem2').text(d.dospem2);
            $('#pfDospemAgama').text(d.dospemAgama);
            $('#pfPenguji').text(d.penguji);
            $('#pfFoto').attr('src', d.foto);
            $('#catatanVerifikasi').val(d.catatan);

            // Status Badge
            var badgeHtml = '';
            if (d.status === 'DISETUJUI') {
                badgeHtml = '<span class="badge badge-success px-3 py-2"><i class="fas fa-check-circle mr-1"></i> Disetujui</span>';
            } else if (d.status === 'DITOLAK') {
                badgeHtml = '<span class="badge badge-danger px-3 py-2"><i class="fas fa-times-circle mr-1"></i> Ditolak</span>';
            } else {
                badgeHtml = '<span class="badge badge-warning px-3 py-2 text-dark"><i class="fas fa-hourglass-half mr-1"></i> Menunggu Verifikasi</span>';
            }
            $('#pfStatusBadge').html(badgeHtml);

            // Catatan alert
            if (d.status === 'DITOLAK' && d.catatan) {
                $('#pfCatatanText').text(d.catatan);
                $('#pfCatatanAlert').removeClass('d-none');
            } else {
                $('#pfCatatanAlert').addClass('d-none');
            }

            // Render list berkas
            var berkas = d.berkas || [];
            $('#berkasCount').text(berkas.length);

            var $tbody = $('#berkasListBody');
            $tbody.empty();

            if (berkas.length === 0) {
                $tbody.append('<tr><td colspan="5" class="text-center text-gray-500 py-3">Tidak ada berkas yang diunggah oleh mahasiswa ini.</td></tr>');
            } else {
                $.each(berkas, function(idx, b) {
                    var fileUrl = '<?= base_url('uploads/graduate/berkas/') ?>/' + b.nama_file;
                    var rowHtml = '<tr class="text-center">' +
                        '<td>' + (idx + 1) + '</td>' +
                        '<td class="text-left font-weight-bold">' + (b.nama_persyaratan || '-') + '</td>' +
                        '<td>' + (b.ukuran_kb ? b.ukuran_kb + ' KB' : '-') + '</td>' +
                        '<td><span class="badge badge-success">Diunggah</span></td>' +
                        '<td>' +
                            '<a href="' + fileUrl + '" target="_blank" class="btn btn-sm btn-info py-0 px-2">' +
                                '<i class="fas fa-external-link-alt fa-sm"></i> Buka' +
                            '</a>' +
                        '</td>' +
                    '</tr>';
                    $tbody.append(rowHtml);
                });
            }

            $('#modalFormPendaftaran').modal('show');
        });

        // Validasi tombol Tolak mewajibkan catatan verifikasi
        $('#formVerifikasi').on('submit', function (e) {
            var clickedBtn = $(document.activeElement).val();
            if (clickedBtn === 'DITOLAK') {
                var catatan = $.trim($('#catatanVerifikasi').val());
                if (!catatan) {
                    e.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Catatan Wajib Diisi',
                            text: 'Harap berikan catatan alasan penolakan agar mahasiswa dapat memperbaiki berkas atau data.',
                        });
                    } else {
                        alert('Harap berikan catatan alasan penolakan agar mahasiswa dapat memperbaiki berkas.');
                    }
                    $('#catatanVerifikasi').focus();
                    return false;
                }
            }
        });
    </script>

<?= $this->endSection(); ?>
