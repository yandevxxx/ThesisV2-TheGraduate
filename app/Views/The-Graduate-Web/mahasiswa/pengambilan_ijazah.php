<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari session/database menyusul.
 */
$dataIjazah = [
    'npm'           => '1402024054',
    'nama'          => 'zild jian',
    'agama'         => 'Islam',
    'tempatLahir'   => 'Kota Yogyakarta',
    'tanggalLahir'  => '',
    'jenisKelamin'  => 'Laki-laki',
    'alamat'        => 'Jl. Cikutra No. 45, Bandung',
    'handphone'     => '089522703131',
    'prodi'         => 'Teknik Informatika',
    'jenjang'       => 'S1',
    'ipk'           => '',
    'tanggalLulus'  => '',
    'namaAyah'      => '',
    'namaIbu'       => '',
    'telpOrtu'      => '',
    'alamatOrtu'    => '',
];
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Formulir Pengambilan Ijazah</h1>
</div>

<!-- Card Formulir Surat Pernyataan Pengambilan Ijazah -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Formulir Surat Pernyataan Pengambilan Ijazah</h6>
    </div>
    <div class="card-body">
        <form action="#" method="post">
            <div class="form-row">

                <!-- NPM -->
                <div class="form-group col-md-6">
                    <label for="npm">NPM</label>
                    <input type="text" class="form-control" id="npm" name="npm" value="<?= esc($dataIjazah['npm']) ?>">
                </div>

                <!-- Nama Mahasiswa -->
                <div class="form-group col-md-6">
                    <label for="nama">Nama Mahasiswa</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= esc($dataIjazah['nama']) ?>">
                </div>

                <!-- Agama -->
                <div class="form-group col-md-6">
                    <label for="agama">Agama</label>
                    <select class="form-control" id="agama" name="agama">
                        <option value="Islam" <?= ($dataIjazah['agama'] === 'Islam') ? 'selected' : '' ?>>Islam</option>
                        <option value="Kristen" <?= ($dataIjazah['agama'] === 'Kristen') ? 'selected' : '' ?>>Kristen</option>
                        <option value="Katolik" <?= ($dataIjazah['agama'] === 'Katolik') ? 'selected' : '' ?>>Katolik</option>
                        <option value="Hindu" <?= ($dataIjazah['agama'] === 'Hindu') ? 'selected' : '' ?>>Hindu</option>
                        <option value="Buddha" <?= ($dataIjazah['agama'] === 'Buddha') ? 'selected' : '' ?>>Buddha</option>
                        <option value="Konghucu" <?= ($dataIjazah['agama'] === 'Konghucu') ? 'selected' : '' ?>>Konghucu</option>
                    </select>
                </div>

                <!-- Tempat Lahir -->
                <div class="form-group col-md-6">
                    <label for="tempatLahir">Tempat Lahir</label>
                    <input type="text" class="form-control" id="tempatLahir" name="tempatLahir" value="<?= esc($dataIjazah['tempatLahir']) ?>">
                </div>

                <!-- Tanggal Lahir -->
                <div class="form-group col-md-6">
                    <label for="tanggalLahir">Tanggal Lahir</label>
                    <input type="date" class="form-control" id="tanggalLahir" name="tanggalLahir" value="<?= esc($dataIjazah['tanggalLahir']) ?>">
                </div>

                <!-- Jenis Kelamin -->
                <div class="form-group col-md-6">
                    <label for="jenisKelamin">Jenis Kelamin</label>
                    <select class="form-control" id="jenisKelamin" name="jenisKelamin">
                        <option value="Laki-laki" <?= ($dataIjazah['jenisKelamin'] === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="Perempuan" <?= ($dataIjazah['jenisKelamin'] === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <!-- Alamat -->
                <div class="form-group col-md-6">
                    <label for="alamat">Alamat</label>
                    <input type="text" class="form-control" id="alamat" name="alamat" value="<?= esc($dataIjazah['alamat']) ?>">
                </div>

                <!-- Handphone -->
                <div class="form-group col-md-6">
                    <label for="handphone">Handphone</label>
                    <input type="text" class="form-control" id="handphone" name="handphone" value="<?= esc($dataIjazah['handphone']) ?>">
                </div>

                <!-- Program Studi -->
                <div class="form-group col-md-6">
                    <label for="prodi">Program Studi</label>
                    <input type="text" class="form-control" id="prodi" name="prodi" value="<?= esc($dataIjazah['prodi']) ?>">
                </div>

                <!-- Jenjang -->
                <div class="form-group col-md-6">
                    <label for="jenjang">Jenjang</label>
                    <select class="form-control" id="jenjang" name="jenjang">
                        <option value="S1" <?= ($dataIjazah['jenjang'] === 'S1') ? 'selected' : '' ?>>S1</option>
                        <option value="S2" <?= ($dataIjazah['jenjang'] === 'S2') ? 'selected' : '' ?>>S2</option>
                        <option value="S3" <?= ($dataIjazah['jenjang'] === 'S3') ? 'selected' : '' ?>>S3</option>
                    </select>
                </div>

                <!-- IPK -->
                <div class="form-group col-md-6">
                    <label for="ipk">IPK</label>
                    <input type="number" step="0.01" class="form-control" id="ipk" name="ipk" value="<?= esc($dataIjazah['ipk']) ?>">
                </div>

                <!-- Tanggal Lulus -->
                <div class="form-group col-md-6">
                    <label for="tanggalLulus">Tanggal Lulus</label>
                    <input type="date" class="form-control" id="tanggalLulus" name="tanggalLulus" value="<?= esc($dataIjazah['tanggalLulus']) ?>">
                </div>

                <!-- Nama Ayah -->
                <div class="form-group col-md-6">
                    <label for="namaAyah">Nama Ayah</label>
                    <input type="text" class="form-control" id="namaAyah" name="namaAyah" placeholder="Nama Ayah" value="<?= esc($dataIjazah['namaAyah']) ?>">
                </div>

                <!-- Nama Ibu -->
                <div class="form-group col-md-6">
                    <label for="namaIbu">Nama Ibu</label>
                    <input type="text" class="form-control" id="namaIbu" name="namaIbu" placeholder="Nama Ibu" value="<?= esc($dataIjazah['namaIbu']) ?>">
                </div>

                <!-- Telpon Orang Tua -->
                <div class="form-group col-md-6">
                    <label for="telpOrtu">Telpon Orang Tua</label>
                    <input type="text" class="form-control" id="telpOrtu" name="telpOrtu" placeholder="Telepon Orang Tua" value="<?= esc($dataIjazah['telpOrtu']) ?>">
                </div>

                <!-- Alamat Orang Tua -->
                <div class="form-group col-md-6">
                    <label for="alamatOrtu">Alamat Orang Tua</label>
                    <input type="text" class="form-control" id="alamatOrtu" name="alamatOrtu" placeholder="Alamat Orang Tua" value="<?= esc($dataIjazah['alamatOrtu']) ?>">
                </div>

                <!-- Alert Simpan -->
                <div class="form-group col-md-12 mb-0">
                    <div class="alert alert-success d-none mb-0" id="alertSimpan">
                        <i class="fas fa-check-circle fa-fw"></i> Formulir berhasil disimpan.
                    </div>
                </div>

                <!-- Tombol -->
                <div class="form-group col-md-12 text-right mb-0">
                    <button type="button" class="btn btn-success" id="btnSimpan"><i class="fas fa-save fa-fw"></i> Simpan</button>
                    <button type="button" class="btn btn-primary"><i class="fas fa-print fa-fw"></i> Cetak Formulir</button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

    <script>
        // Formulir pengambilan ijazah sisi mahasiswa (dummy —
        // endpoint controller/database menyusul saat integrasi)
        $(function () {

            // Tombol Simpan (dummy) -> tampilkan alert sukses
            $(document).on('click', '#btnSimpan', function () {
                $('#alertSimpan').removeClass('d-none');
                $('html, body').animate({ scrollTop: $('#alertSimpan').offset().top - 120 }, 'slow');
            });
        });
    </script>

<?= $this->endSection(); ?>
