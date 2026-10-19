<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari session/database menyusul:
 * - NPM & Nama otomatis dari data akun mahasiswa
 * - Daftar dosen dari tabel dosen
 * - Dokumen: status unggahan dari database
 */
$dataPendaftaran = [
    'npm' => '2021010001',
    'nama' => 'Ahmad Fauzi',
    'nik' => '3201234567890001',
    'tempatLahir' => 'Bandung',
    'tanggalLahir' => '1999-05-12',
    'jenisKelamin' => 'Laki-laki',
    'alamat' => 'Jl. Contoh Raya No. 123, Bandung',
    'noHp' => '0812-3456-7890',
    'noTelepon' => '022-1234567',
    'email' => 'ahmad.fauzi@example.ac.id',
    'prodi' => 'Teknik Informatika',
    'totalSks' => '144',
    'ipk' => '3.52',
    'tahunAkademik' => '2025/2026',
    'semester' => 'Ganjil',
    'tanggalLulus' => '2025-11-15',
    'gelombangWisuda' => '1',
    'pembimbing1' => 'Dr. Budi Santoso, S.T., M.T.',
    'pembimbing2' => 'Rina Wulandari, S.Kom., M.Kom.',
    'pembimbingAgama' => 'Dr. Agus Setiawan, S.Kom., M.Kom.',
    'dosenPenguji' => 'Dewi Lestari, S.T., M.T.',
    'judulSkripsi' => 'Sistem Pendukung Keputusan Penentuan Beasiswa Mahasiswa Menggunakan Metode TOPSIS',
];

$daftarDosen = [
    'Dr. Budi Santoso, S.T., M.T.',
    'Rina Wulandari, S.Kom., M.Kom.',
    'Dr. Agus Setiawan, S.Kom., M.Kom.',
    'Dewi Lestari, S.T., M.T.',
    'Yuni Kartika, S.Ds., M.Ds.',
    'Hendra Wijaya, S.Ds., M.Ds.',
];

$gelombangWisuda = [
    '1' => 'Gelombang 1 — 15 November 2025',
    '2' => 'Gelombang 2 — 15 Desember 2025',
];

// Opsi lain (dropdown)
$daftarTahunAkademik = ['2025/2026', '2024/2025'];
$daftarSemester      = ['Ganjil', 'Genap'];
$daftarJenisKelamin  = ['Laki-laki', 'Perempuan'];

// 9 dokumen persyaratan wisuda (semua belum terunggah — dummy)
$daftarDokumen = [
    'FotoCopy Berwarna Ijazah SMA',
    'FotoCopy Berwarna Akte Kelahiran',
    'FotoCopy Berwarna Kartu Keluarga',
    'Fotocopy Berwarna KTP yang berlaku',
    'Fotocopy Berwarna KTM yang berlaku',
    'Pas Foto Berwarna',
    'Surat Keterangan Kelulusan (SKL)',
    'Sertifikat TOEIC',
    'File Skripsi',
];
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Pendaftaran Wisuda</h1>
</div>

<!-- Card Form Pendaftaran Wisuda -->
<div class="card shadow mb-4" id="cardFormPendaftaran">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Form Pendaftaran Wisuda</h6>
    </div>
    <div class="card-body">
        <form action="<?= base_url('graduate/pendaftaran_wisuda') ?>" method="post">
            <div class="form-row">

                <!-- NPM (otomatis dari akun) -->
                <div class="form-group col-md-6">
                    <label for="npm">NPM</label>
                    <input type="text" class="form-control" id="npm" name="npm" value="<?= esc($dataPendaftaran['npm']) ?>" readonly>
                </div>

                <!-- Nama Mahasiswa (otomatis dari akun) -->
                <div class="form-group col-md-6">
                    <label for="nama">Nama Mahasiswa</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= esc($dataPendaftaran['nama']) ?>" readonly>
                </div>

                <!-- NIK -->
                <div class="form-group col-md-6">
                    <label for="nik">NIK</label>
                    <input type="text" class="form-control" id="nik" name="nik" value="<?= esc($dataPendaftaran['nik']) ?>">
                </div>

                <!-- Tempat Lahir -->
                <div class="form-group col-md-6">
                    <label for="tempatLahir">Tempat Lahir</label>
                    <input type="text" class="form-control" id="tempatLahir" name="tempatLahir" value="<?= esc($dataPendaftaran['tempatLahir']) ?>">
                </div>

                <!-- Tanggal Lahir -->
                <div class="form-group col-md-6">
                    <label for="tanggalLahir">Tanggal Lahir</label>
                    <input type="date" class="form-control" id="tanggalLahir" name="tanggalLahir" value="<?= esc($dataPendaftaran['tanggalLahir']) ?>">
                </div>

                <!-- Jenis Kelamin -->
                <div class="form-group col-md-6">
                    <label for="jenisKelamin">Jenis Kelamin</label>
                    <select class="form-control" id="jenisKelamin" name="jenisKelamin">
                        <?php foreach ($daftarJenisKelamin as $jk): ?>
                        <option value="<?= esc($jk) ?>" <?= ($dataPendaftaran['jenisKelamin'] === $jk) ? 'selected' : '' ?>><?= esc($jk) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Alamat -->
                <div class="form-group col-md-12">
                    <label for="alamat">Alamat</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2"><?= esc($dataPendaftaran['alamat']) ?></textarea>
                </div>

                <!-- No Handphone -->
                <div class="form-group col-md-6">
                    <label for="noHp">No. Handphone</label>
                    <input type="text" class="form-control" id="noHp" name="noHp" value="<?= esc($dataPendaftaran['noHp']) ?>">
                </div>

                <!-- No Telepon -->
                <div class="form-group col-md-6">
                    <label for="noTelepon">No. Telepon</label>
                    <input type="text" class="form-control" id="noTelepon" name="noTelepon" value="<?= esc($dataPendaftaran['noTelepon']) ?>">
                </div>

                <!-- E-mail -->
                <div class="form-group col-md-6">
                    <label for="email">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= esc($dataPendaftaran['email']) ?>">
                </div>

                <!-- Program Studi -->
                <div class="form-group col-md-6">
                    <label for="prodi">Program Studi</label>
                    <input type="text" class="form-control" id="prodi" name="prodi" value="<?= esc($dataPendaftaran['prodi']) ?>">
                </div>

                <!-- Total SKS -->
                <div class="form-group col-md-6">
                    <label for="totalSks">Total SKS</label>
                    <input type="number" class="form-control" id="totalSks" name="totalSks" value="<?= esc($dataPendaftaran['totalSks']) ?>">
                </div>

                <!-- IPK -->
                <div class="form-group col-md-6">
                    <label for="ipk">IPK</label>
                    <input type="number" step="0.01" class="form-control" id="ipk" name="ipk" value="<?= esc($dataPendaftaran['ipk']) ?>">
                </div>

                <!-- Tahun Akademik -->
                <div class="form-group col-md-6">
                    <label for="tahunAkademik">Tahun Akademik</label>
                    <select class="form-control" id="tahunAkademik" name="tahunAkademik">
                        <?php foreach ($daftarTahunAkademik as $ta): ?>
                        <option value="<?= esc($ta) ?>" <?= ($dataPendaftaran['tahunAkademik'] === $ta) ? 'selected' : '' ?>><?= esc($ta) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Semester -->
                <div class="form-group col-md-6">
                    <label for="semester">Semester</label>
                    <select class="form-control" id="semester" name="semester">
                        <?php foreach ($daftarSemester as $sm): ?>
                        <option value="<?= esc($sm) ?>" <?= ($dataPendaftaran['semester'] === $sm) ? 'selected' : '' ?>><?= esc($sm) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tanggal Lulus -->
                <div class="form-group col-md-6">
                    <label for="tanggalLulus">Tanggal Lulus</label>
                    <input type="date" class="form-control" id="tanggalLulus" name="tanggalLulus" value="<?= esc($dataPendaftaran['tanggalLulus']) ?>">
                </div>

                <!-- Tanggal Wisuda (gelombang) -->
                <div class="form-group col-md-6">
                    <label for="gelombangWisuda">Tanggal Wisuda</label>
                    <select class="form-control" id="gelombangWisuda" name="gelombangWisuda">
                        <?php foreach ($gelombangWisuda as $nilai => $label): ?>
                        <option value="<?= esc($nilai) ?>" <?= ($dataPendaftaran['gelombangWisuda'] == $nilai) ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Pembimbing Ilmu 1 -->
                <div class="form-group col-md-6">
                    <label for="pembimbing1">Pembimbing Ilmu 1</label>
                    <select class="form-control pilih-dosen" id="pembimbing1" name="pembimbing1" data-nama="namaPembimbing1">
                        <option value="">Pilih dosen...</option>
                        <?php foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= ($dataPendaftaran['pembimbing1'] === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nama Pembimbing Ilmu 1 (otomatis) -->
                <div class="form-group col-md-6">
                    <label for="namaPembimbing1">Nama Pembimbing Ilmu 1</label>
                    <input type="text" class="form-control" id="namaPembimbing1" name="namaPembimbing1" value="<?= esc($dataPendaftaran['pembimbing1']) ?>" readonly>
                </div>

                <!-- Pembimbing Ilmu 2 -->
                <div class="form-group col-md-6">
                    <label for="pembimbing2">Pembimbing Ilmu 2</label>
                    <select class="form-control pilih-dosen" id="pembimbing2" name="pembimbing2" data-nama="namaPembimbing2">
                        <option value="">Pilih dosen...</option>
                        <?php foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= ($dataPendaftaran['pembimbing2'] === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nama Pembimbing Ilmu 2 (otomatis) -->
                <div class="form-group col-md-6">
                    <label for="namaPembimbing2">Nama Pembimbing Ilmu 2</label>
                    <input type="text" class="form-control" id="namaPembimbing2" name="namaPembimbing2" value="<?= esc($dataPendaftaran['pembimbing2']) ?>" readonly>
                </div>

                <!-- Pembimbing Agama -->
                <div class="form-group col-md-6">
                    <label for="pembimbingAgama">Pembimbing Agama</label>
                    <select class="form-control pilih-dosen" id="pembimbingAgama" name="pembimbingAgama" data-nama="namaPembimbingAgama">
                        <option value="">Pilih dosen...</option>
                        <?php foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= ($dataPendaftaran['pembimbingAgama'] === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nama Pembimbing Agama (otomatis) -->
                <div class="form-group col-md-6">
                    <label for="namaPembimbingAgama">Nama Pembimbing Agama</label>
                    <input type="text" class="form-control" id="namaPembimbingAgama" name="namaPembimbingAgama" value="<?= esc($dataPendaftaran['pembimbingAgama']) ?>" readonly>
                </div>

                <!-- Dosen Penguji -->
                <div class="form-group col-md-6">
                    <label for="dosenPenguji">Dosen Penguji</label>
                    <select class="form-control" id="dosenPenguji" name="dosenPenguji">
                        <option value="">Pilih dosen...</option>
                        <?php foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= ($dataPendaftaran['dosenPenguji'] === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Judul Skripsi -->
                <div class="form-group col-md-12">
                    <label for="judulSkripsi">Judul Skripsi</label>
                    <textarea class="form-control" id="judulSkripsi" name="judulSkripsi" rows="2"><?= esc($dataPendaftaran['judulSkripsi']) ?></textarea>
                </div>

                <!-- Tombol Daftar -->
                <div class="form-group col-md-12 text-right">
                    <button type="button" class="btn btn-success" id="btnDaftar"><i class="fas fa-paper-plane fa-fw"></i> Daftar</button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- Card Dokumen Persyaratan Wisuda (selalu tampil) -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Dokumen Persyaratan Wisuda</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr class="text-center">
                        <th style="width: 70%;">Dokumen</th>
                        <th style="width: 30%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftarDokumen as $dokumen): ?>
                    <tr class="dokumen-row" data-status="belum" data-file="">
                        <td><?= esc($dokumen) ?></td>
                        <td class="aksi-dokumen text-center">
                            <button type="button" class="btn btn-primary btn-sm upload-dokumen" data-toggle="tooltip" data-placement="top" title="Upload Berkas"><i class="fas fa-upload"></i> Upload</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Card Ringkasan Pendaftaran (muncul setelah klik Daftar) -->
<div class="card shadow mb-4 d-none" id="cardRingkasan">
    <div class="card-header py-3 d-sm-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Pendaftaran Wisuda</h6>
        <span class="badge badge-warning mt-2 mt-sm-0">Menunggu Verifikasi</span>
    </div>
    <div class="card-body">

        <div class="alert alert-warning mb-3">
            <i class="fas fa-hourglass-half fa-fw"></i> Pendaftaran Anda sedang menunggu verifikasi oleh admin.
        </div>

        <table class="table table-bordered mb-0">
            <tbody>
                <tr>
                    <th style="width: 35%;">NPM</th>
                    <td id="rkNpm">-</td>
                </tr>
                <tr>
                    <th>Nama Mahasiswa</th>
                    <td id="rkNama">-</td>
                </tr>
                <tr>
                    <th>NIK</th>
                    <td id="rkNik">-</td>
                </tr>
                <tr>
                    <th>Tempat, Tanggal Lahir</th>
                    <td id="rkTtl">-</td>
                </tr>
                <tr>
                    <th>Jenis Kelamin</th>
                    <td id="rkJk">-</td>
                </tr>
                <tr>
                    <th>Alamat</th>
                    <td id="rkAlamat">-</td>
                </tr>
                <tr>
                    <th>No. Handphone</th>
                    <td id="rkHp">-</td>
                </tr>
                <tr>
                    <th>No. Telepon</th>
                    <td id="rkTelp">-</td>
                </tr>
                <tr>
                    <th>E-mail</th>
                    <td id="rkEmail">-</td>
                </tr>
                <tr>
                    <th>Program Studi</th>
                    <td id="rkProdi">-</td>
                </tr>
                <tr>
                    <th>Total SKS</th>
                    <td id="rkSks">-</td>
                </tr>
                <tr>
                    <th>IPK</th>
                    <td id="rkIpk">-</td>
                </tr>
                <tr>
                    <th>Tahun Akademik</th>
                    <td id="rkTa">-</td>
                </tr>
                <tr>
                    <th>Semester</th>
                    <td id="rkSemester">-</td>
                </tr>
                <tr>
                    <th>Tanggal Lulus</th>
                    <td id="rkTglLulus">-</td>
                </tr>
                <tr>
                    <th>Tanggal Wisuda</th>
                    <td id="rkGelombang">-</td>
                </tr>
                <tr>
                    <th>Pembimbing Ilmu 1</th>
                    <td id="rkPembimbing1">-</td>
                </tr>
                <tr>
                    <th>Pembimbing Ilmu 2</th>
                    <td id="rkPembimbing2">-</td>
                </tr>
                <tr>
                    <th>Pembimbing Agama</th>
                    <td id="rkPembimbingAgama">-</td>
                </tr>
                <tr>
                    <th>Dosen Penguji</th>
                    <td id="rkPenguji">-</td>
                </tr>
                <tr>
                    <th>Judul Skripsi</th>
                    <td id="rkJudul">-</td>
                </tr>
            </tbody>
        </table>

    </div>
</div>

<!-- Modal Upload Dokumen -->
<div class="modal fade" id="modalUploadDokumen" tabindex="-1" role="dialog" aria-labelledby="modalUploadDokumenLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUploadDokumenLabel">Upload Dokumen</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-2 font-weight-bold" id="uploadNamaDokumen">-</p>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="fileDokumen" accept=".pdf,.jpg,.jpeg,.png">
                    <label class="custom-file-label" for="fileDokumen">Pilih file...</label>
                </div>
                <small class="text-gray-500">Format: PDF / JPG / PNG, maks. 5 MB</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpanDokumen"><i class="fas fa-upload fa-fw"></i> Upload</button>
            </div>
        </div>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

    <script>
        // Pendaftaran wisuda sisi mahasiswa (dummy —
        // endpoint controller/database menyusul saat integrasi)
        $(function () {

            // Pilih dosen -> nama otomatis terisi (read-only)
            $(document).on('change', '.pilih-dosen', function () {
                var namaId = $(this).data('nama'); // id input nama tujuan
                $('#' + namaId).val($(this).val() || '-');
            });

            // Tanggal yyyy-mm-dd -> dd/mm/yyyy
            function formatTanggal(nilai) {
                if (!nilai) return '-';
                var bagian = nilai.split('-');
                return bagian.length === 3 ? bagian[2] + '/' + bagian[1] + '/' + bagian[0] : nilai;
            }

            // Tombol Daftar -> sembunyikan form, tampilkan ringkasan + status Menunggu Verifikasi
            $(document).on('click', '#btnDaftar', function () {
                var map = {
                    rkNpm: '#npm',
                    rkNama: '#nama',
                    rkNik: '#nik',
                    rkTtl: null, // digabung manual (tempat + tanggal)
                    rkJk: '#jenisKelamin',
                    rkAlamat: '#alamat',
                    rkHp: '#noHp',
                    rkTelp: '#noTelepon',
                    rkEmail: '#email',
                    rkProdi: '#prodi',
                    rkSks: '#totalSks',
                    rkIpk: '#ipk',
                    rkTa: '#tahunAkademik',
                    rkSemester: '#semester',
                    rkTglLulus: null, // manual (format)
                    rkGelombang: '#gelombangWisuda',
                    rkPembimbing1: '#namaPembimbing1',
                    rkPembimbing2: '#namaPembimbing2',
                    rkPembimbingAgama: '#namaPembimbingAgama',
                    rkPenguji: '#dosenPenguji',
                    rkJudul: '#judulSkripsi'
                };

                $.each(map, function (idTujuan, pemilih) {
                    if (pemilih) {
                        $('#' + idTujuan).text($(pemilih).val() || '-');
                    }
                });

                // Tempat, Tanggal Lahir
                $('#rkTtl').text(
                    ($('#tempatLahir').val() || '-') + ', ' + formatTanggal($('#tanggalLahir').val())
                );

                // Tanggal Lulus
                $('#rkTglLulus').text(formatTanggal($('#tanggalLulus').val()));

                // Sembunyikan form, tampilkan ringkasan
                $('#cardFormPendaftaran').addClass('d-none');
                $('#cardRingkasan').removeClass('d-none');

                // Kembali ke atas
                $('html, body').animate({ scrollTop: 0 }, 'slow');
            });

            // ===== Upload dokumen persyaratan (dummy) =====

            var $modalUpload = $('#modalUploadDokumen');
            var $rowDokumen = null;

            // Render isi kolom Aksi sesuai status baris
            function renderAksiDokumen($row) {
                var sudah = $row.data('status') === 'sudah';
                var $aksi = $row.find('.aksi-dokumen');

                if (sudah) {
                    $aksi.html(
                        '<span class="text-success font-weight-bold"><i class="fas fa-check"></i> Sudah Diunggah</span>' +
                        ' <button type="button" class="btn btn-danger btn-sm hapus-dokumen ml-2" data-toggle="tooltip" data-placement="top" title="Hapus Berkas"><i class="fas fa-trash"></i></button>'
                    );
                } else {
                    $aksi.html(
                        '<button type="button" class="btn btn-primary btn-sm upload-dokumen" data-toggle="tooltip" data-placement="top" title="Upload Berkas"><i class="fas fa-upload"></i> Upload</button>'
                    );
                }

                $aksi.find('[data-toggle="tooltip"]').tooltip('dispose').tooltip();
            }

            // Klik Upload (termasuk yang digenerate ulang) -> buka modal
            $(document).on('click', '.upload-dokumen', function () {
                $rowDokumen = $(this).closest('tr');
                $('#uploadNamaDokumen').text($rowDokumen.find('td').eq(0).text());
                $('#fileDokumen').val('').next('.custom-file-label').text('Pilih file...');
                $modalUpload.modal('show');
            });

            // Tampilkan nama file terpilih
            $(document).on('change', '#fileDokumen', function () {
                $(this).next('.custom-file-label').text(this.files.length ? this.files[0].name : 'Pilih file...');
            });

            // Simulasikan upload
            $(document).on('click', '#btnSimpanDokumen', function () {
                if (!$rowDokumen) return;

                var namaFile = $('#fileDokumen')[0].files.length
                    ? $('#fileDokumen')[0].files[0].name
                    : 'dokumen-dummy.pdf';

                $rowDokumen.data('status', 'sudah').attr('data-status', 'sudah').attr('data-file', namaFile);
                renderAksiDokumen($rowDokumen);

                $modalUpload.modal('hide');
            });

            // Hapus berkas -> kembali jadi tombol Upload
            $(document).on('click', '.hapus-dokumen', function () {
                var $row = $(this).closest('tr');
                $row.data('status', 'belum').attr('data-status', 'belum').attr('data-file', '');
                renderAksiDokumen($row);
            });
        });
    </script>

<?= $this->endSection(); ?>
