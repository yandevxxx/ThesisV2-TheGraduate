<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari database menyusul.
 */
$daftarPendaftaran = [
    [
        'npm' => '2021010001', 'nama' => 'Ahmad Fauzi', 'prodi' => 'Teknik Informatika',
        'tglLulus' => '15/11/2025', 'hp' => '0812-3456-7890',
        'judul' => 'Sistem Pendukung Keputusan Penentuan Beasiswa Mahasiswa Menggunakan Metode TOPSIS',
        'dospem1' => 'Dr. Budi Santoso, S.T., M.T.', 'dospem2' => 'Rina Wulandari, S.Kom., M.Kom.',
        'jadwal' => 'Gelombang 1 - 15 November 2025',
    ],
    [
        'npm' => '2021010002', 'nama' => 'Siti Rahmawati', 'prodi' => 'Sistem Informasi',
        'tglLulus' => '15/11/2025', 'hp' => '0813-4567-8901',
        'judul' => 'Rancang Bangun Sistem Informasi Absensi dan Laporan Kerja Praktek Berbasis Web',
        'dospem1' => 'Dr. Agus Setiawan, S.Kom., M.Kom.', 'dospem2' => 'Dewi Lestari, S.T., M.T.',
        'jadwal' => 'Gelombang 1 - 15 November 2025',
    ],
    [
        'npm' => '2021010003', 'nama' => 'Rizky Pratama', 'prodi' => 'Teknik Informatika',
        'tglLulus' => '15/11/2025', 'hp' => '0814-5678-9012',
        'judul' => 'Klasifikasi Sentimen Ulasan Produk pada E-Commerce Menggunakan Algoritma Naive Bayes',
        'dospem1' => 'Dr. Budi Santoso, S.T., M.T.', 'dospem2' => 'Rina Wulandari, S.Kom., M.Kom.',
        'jadwal' => 'Gelombang 1 - 15 November 2025',
    ],
    [
        'npm' => '2021010004', 'nama' => 'Dinda Permata', 'prodi' => 'Desain Komunikasi Visual',
        'tglLulus' => '15/11/2025', 'hp' => '0815-6789-0123',
        'judul' => 'Perancangan Identitas Visual dan Media Promosi Digital untuk UMKM Kuliner',
        'dospem1' => 'Yuni Kartika, S.Ds., M.Ds.', 'dospem2' => 'Hendra Wijaya, S.Ds., M.Ds.',
        'jadwal' => 'Gelombang 1 - 15 November 2025',
    ],
    [
        'npm' => '2021010005', 'nama' => 'Bagus Setiawan', 'prodi' => 'Sistem Informasi',
        'tglLulus' => '15/12/2025', 'hp' => '0816-7890-1234',
        'judul' => 'Pengembangan Aplikasi Monitoring Stok Inventaris Berbasis Android pada PT Sinar Jaya',
        'dospem1' => 'Dewi Lestari, S.T., M.T.', 'dospem2' => 'Dr. Agus Setiawan, S.Kom., M.Kom.',
        'jadwal' => 'Gelombang 2 - 15 Desember 2025',
    ],
    [
        'npm' => '2021010006', 'nama' => 'Nadia Aulia', 'prodi' => 'Teknik Informatika',
        'tglLulus' => '15/12/2025', 'hp' => '0817-8901-2345',
        'judul' => 'Deteksi Dini Penyakit Daun Tomat Menggunakan Convolutional Neural Network',
        'dospem1' => 'Rina Wulandari, S.Kom., M.Kom.', 'dospem2' => 'Dr. Budi Santoso, S.T., M.T.',
        'jadwal' => 'Gelombang 2 - 15 Desember 2025',
    ],
];

// Detail pendaftaran per NPM untuk modal preview (di-JSON untuk JS)
$dataDetail = [];
foreach ($daftarPendaftaran as $p) {
    $dataDetail[$p['npm']] = [
        'nama' => $p['nama'], 'prodi' => $p['prodi'], 'tglLulus' => $p['tglLulus'], 'hp' => $p['hp'],
        'judul' => $p['judul'], 'dospem1' => $p['dospem1'], 'dospem2' => $p['dospem2'], 'jadwal' => $p['jadwal'],
    ];
}

// Filter Tahun Akademik (data dummy — pilihan hanya bertahan, belum memengaruhi baris)
$tahunAkademikAktif = $_GET['tahunAkademik'] ?? 'Ganjil 2025/2026';
$daftarTahunAkademik = ['Ganjil 2025/2026', 'Genap 2025/2026', 'Ganjil 2024/2025', 'Genap 2024/2025'];

// Pencarian mahasiswa — NPM atau nama, tidak case-sensitive
$kataCari = trim($_GET['cari'] ?? '');
if ($kataCari !== '') {
    $daftarPendaftaran = array_values(array_filter($daftarPendaftaran, function ($p) use ($kataCari) {
        return stripos($p['npm'], $kataCari) !== false || stripos($p['nama'], $kataCari) !== false;
    }));
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Pendaftaran Wisuda</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Form Pendaftaran Wisuda</h6>
    </div>
    <div class="card-body">

        <!-- Filter: Tahun Akademik (atas) + Pencarian Mahasiswa (bawah) — di dalam Form Pendaftaran Wisuda -->
        <div class="text-right mb-3">
            <form class="form-inline justify-content-end mb-2" method="get" action="<?= base_url('graduate/pendaftaran_wisuda') ?>">
                <label class="my-1 mr-2" for="tahunAkademik">Pilih Tahun Akademik</label>
                <select class="custom-select custom-select-sm my-1 mr-sm-2" id="tahunAkademik" name="tahunAkademik">
                    <?php foreach ($daftarTahunAkademik as $ta): ?>
                    <option value="<?= esc($ta) ?>" <?= ($tahunAkademikAktif === $ta) ? 'selected' : '' ?>><?= esc($ta) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary my-1">Filter</button>
            </form>

            <form class="form-inline justify-content-end mb-0" method="get" action="<?= base_url('graduate/pendaftaran_wisuda') ?>">
                <label class="my-1 mr-2" for="cari">Cari Mahasiswa</label>
                <div class="input-group input-group-sm my-1 mr-sm-2" style="min-width: 220px;">
                    <input type="text" class="form-control" id="cari" name="cari" placeholder="Cari NPM / nama..." value="<?= esc($kataCari) ?>">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-primary my-1">Cari</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 12%;">NPM</th>
                        <th style="width: 28%;">Nama Mahasiswa</th>
                        <th style="width: 18%;">Prodi</th>
                        <th style="width: 15%;">Tanggal Lulus</th>
                        <th style="width: 12%;">Foto</th>
                        <th style="width: 10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarPendaftaran)): ?>
                    <tr class="text-center">
                        <td colspan="7" class="text-gray-500">Mahasiswa tidak ditemukan.</td>
                    </tr>
                    <?php endif; ?>

                    <?php $no = 1; foreach ($daftarPendaftaran as $p): ?>
                    <tr class="text-center">
                        <td><?= $no++ ?></td>
                        <td><?= esc($p['npm']) ?></td>
                        <td class="text-left"><?= esc($p['nama']) ?></td>
                        <td><?= esc($p['prodi']) ?></td>
                        <td><?= esc($p['tglLulus']) ?></td>
                        <td>
                            <img src="<?= base_url('assets/img/undraw_profile.svg') ?>" alt="Foto <?= esc($p['nama']) ?>" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                        </td>
                        <td>
                            <button type="button" class="btn btn-success btn-sm lihat-pendaftaran" data-toggle="tooltip" data-placement="top" title="Lihat Form Pendaftaran" data-npm="<?= esc($p['npm']) ?>"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form Pendaftaran (preview) -->
<div class="modal fade" id="modalFormPendaftaran" tabindex="-1" role="dialog" aria-labelledby="modalFormPendaftaranLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFormPendaftaranLabel">Form Pendaftaran Wisuda</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <div class="d-flex align-items-center mb-3">
                    <img src="<?= base_url('assets/img/undraw_profile.svg') ?>" alt="Foto Mahasiswa" class="rounded-circle mr-3" width="64" height="64" style="object-fit: cover;">
                    <div>
                        <h6 class="mb-0 font-weight-bold" id="pfNama">-</h6>
                        <small class="text-gray-600">NPM: <span id="pfNpm">-</span></small>
                    </div>
                </div>

                <table class="table table-bordered mb-0">
                    <tbody>
                        <tr>
                            <th style="width: 35%;">Prodi</th>
                            <td id="pfProdi">-</td>
                        </tr>
                        <tr>
                            <th>Tanggal Lulus</th>
                            <td id="pfTglLulus">-</td>
                        </tr>
                        <tr>
                            <th>No. HP</th>
                            <td id="pfHp">-</td>
                        </tr>
                        <tr>
                            <th>Judul Skripsi</th>
                            <td id="pfJudul">-</td>
                        </tr>
                        <tr>
                            <th>Dosen Pembimbing 1</th>
                            <td id="pfDospem1">-</td>
                        </tr>
                        <tr>
                            <th>Dosen Pembimbing 2</th>
                            <td id="pfDospem2">-</td>
                        </tr>
                        <tr>
                            <th>Jadwal Wisuda</th>
                            <td id="pfJadwal">-</td>
                        </tr>
                    </tbody>
                </table>

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
        // Data detail pendaftaran per NPM (dari array dummy di view)
        var dataPendaftaran = <?= json_encode($dataDetail, JSON_HEX_TAG) ?>;

        // Tombol Aksi membuka modal preview form pendaftaran
        $(document).on('click', '.lihat-pendaftaran', function () {
            var npm = $(this).data('npm');
            var d = dataPendaftaran[npm];

            if (!d) return;

            $('#pfNpm').text(npm);
            $('#pfNama').text(d.nama);
            $('#pfProdi').text(d.prodi);
            $('#pfTglLulus').text(d.tglLulus);
            $('#pfHp').text(d.hp);
            $('#pfJudul').text(d.judul);
            $('#pfDospem1').text(d.dospem1);
            $('#pfDospem2').text(d.dospem2);
            $('#pfJadwal').text(d.jadwal);
            $('#modalFormPendaftaran').modal('show');
        });
    </script>

<?= $this->endSection(); ?>
