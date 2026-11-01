<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari database menyusul.
 */
$daftarCalon = [
    [
        'no' => 1,
        'npm' => '2021010001',
        'nik' => '3201234567890001',
        'nama' => 'Ahmad Fauzi',
        'prodi' => 'Teknik Informatika',
        'tempatLahir' => 'Kota Yogyakarta',
        'tanggalLahir' => '12/05/1999',
        'jenisKelamin' => 'Laki-laki',
        'alamat' => 'Jl. Cikutra No. 45, Bandung',
        'email' => 'ahmad.fauzi@example.ac.id',
        'hp' => '0812-3456-7890',
        'telp' => '022-1234567',
        'sks' => '144',
    ],
    [
        'no' => 2,
        'npm' => '2021010002',
        'nik' => '3201234567890002',
        'nama' => 'Siti Rahmawati',
        'prodi' => 'Sistem Informasi',
        'tempatLahir' => 'Bandung',
        'tanggalLahir' => '21/08/1999',
        'jenisKelamin' => 'Perempuan',
        'alamat' => 'Jl. Merdeka No. 10, Bandung',
        'email' => 'siti.rahmawati@example.ac.id',
        'hp' => '0813-4567-8901',
        'telp' => '022-2345678',
        'sks' => '146',
    ],
    [
        'no' => 3,
        'npm' => '2021010003',
        'nik' => '3201234567890003',
        'nama' => 'Rizky Pratama',
        'prodi' => 'Teknik Informatika',
        'tempatLahir' => 'Jakarta',
        'tanggalLahir' => '03/03/2000',
        'jenisKelamin' => 'Laki-laki',
        'alamat' => 'Jl. Dago No. 22, Bandung',
        'email' => 'rizky.pratama@example.ac.id',
        'hp' => '0814-5678-9012',
        'telp' => '022-3456789',
        'sks' => '144',
    ],
];

$kataCari = trim($_GET['cari'] ?? '');
if ($kataCari !== '') {
    $daftarCalon = array_values(array_filter($daftarCalon, function ($m) use ($kataCari) {
        return stripos($m['npm'], $kataCari) !== false
            || stripos($m['nik'], $kataCari) !== false
            || stripos($m['nama'], $kataCari) !== false;
    }));
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Calon Wisudawan</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Calon Wisudawan</h6>
    </div>
    <div class="card-body">

        <div class="d-flex flex-wrap justify-content-end align-items-center mb-3">
            <form class="form-inline flex-wrap justify-content-end mb-0" method="get" action="<?= base_url('graduate/calon_wisudawan') ?>">
                <label class="my-1 mr-2 font-weight-bold text-gray-700" for="cariCalon">Cari</label>
                <div class="input-group input-group-sm my-1 mr-2" style="min-width: 240px; max-width: 100%;">
                    <input type="text" class="form-control" id="cariCalon" name="cari" placeholder="Cari NPM / NIK / Nama..." value="<?= esc($kataCari) ?>">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-primary my-1 mr-1">Cari</button>
                <?php if ($kataCari !== ''): ?>
                <a href="<?= base_url('graduate/calon_wisudawan') ?>" class="btn btn-sm btn-secondary my-1">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead>
                    <tr class="text-center">
                        <th style="width: 4%;">No</th>
                        <th style="width: 10%;">NPM</th>
                        <th style="width: 12%;">NIK</th>
                        <th style="width: 16%;">Nama Mahasiswa</th>
                        <th style="width: 12%;">Program Studi</th>
                        <th style="width: 16%;">Tempat/Tanggal Lahir</th>
                        <th style="width: 9%;">Jenis Kelamin</th>
                        <th style="width: 20%;">Alamat</th>
                        <th style="width: 14%;">Email</th>
                        <th style="width: 10%;">No HP</th>
                        <th style="width: 10%;">No Telepon</th>
                        <th style="width: 8%;">Total SKS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarCalon)): ?>
                    <tr class="text-center">
                        <td colspan="12" class="text-gray-500">Data tidak ditemukan.</td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($daftarCalon as $m): ?>
                    <tr>
                        <td class="text-center"><?= esc($m['no']) ?></td>
                        <td><?= esc($m['npm']) ?></td>
                        <td><?= esc($m['nik']) ?></td>
                        <td><?= esc($m['nama']) ?></td>
                        <td><?= esc($m['prodi']) ?></td>
                        <td><?= esc($m['tempatLahir']) ?>, <?= esc($m['tanggalLahir']) ?></td>
                        <td class="text-center"><?= esc($m['jenisKelamin']) ?></td>
                        <td><?= esc($m['alamat']) ?></td>
                        <td><?= esc($m['email']) ?></td>
                        <td><?= esc($m['hp']) ?></td>
                        <td><?= esc($m['telp']) ?></td>
                        <td class="text-center"><?= esc($m['sks']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>
