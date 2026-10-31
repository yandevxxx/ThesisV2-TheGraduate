<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari database menyusul.
 *
 * status: 'menunggu' | 'disetujui' | 'ditolak'
 * berkas: true (sudah diunggah / warna) | false (belum / outline abu)
 */
$daftarSkl = [
    [
        'npm' => '2021010001', 'nama' => 'Ahmad Fauzi', 'prodi' => 'Teknik Informatika', 'status' => 'disetujui',
        'berkas' => ['artikel' => true, 'lembar_pengesahan' => true, 'sertifikat_kompetensi' => true],
    ],
    [
        'npm' => '2021010002', 'nama' => 'Siti Rahmawati', 'prodi' => 'Sistem Informasi', 'status' => 'menunggu',
        'berkas' => ['artikel' => true, 'lembar_pengesahan' => false, 'sertifikat_kompetensi' => false],
    ],
    [
        'npm' => '2021010003', 'nama' => 'Rizky Pratama', 'prodi' => 'Teknik Informatika', 'status' => 'disetujui',
        'berkas' => ['artikel' => true, 'lembar_pengesahan' => true, 'sertifikat_kompetensi' => true],
    ],
    [
        'npm' => '2021010004', 'nama' => 'Dinda Permata', 'prodi' => 'Desain Komunikasi Visual', 'status' => 'ditolak',
        'berkas' => ['artikel' => true, 'lembar_pengesahan' => false, 'sertifikat_kompetensi' => false],
    ],
    [
        'npm' => '2021010005', 'nama' => 'Bagus Setiawan', 'prodi' => 'Sistem Informasi', 'status' => 'menunggu',
        'berkas' => ['artikel' => false, 'lembar_pengesahan' => false, 'sertifikat_kompetensi' => false],
    ],
    [
        'npm' => '2021010006', 'nama' => 'Nadia Aulia', 'prodi' => 'Teknik Informatika', 'status' => 'disetujui',
        'berkas' => ['artikel' => true, 'lembar_pengesahan' => true, 'sertifikat_kompetensi' => true],
    ],
];

// Badge status
$statusSkl = [
    'menunggu'  => ['label' => 'Menunggu',  'class' => 'badge-warning'],
    'disetujui' => ['label' => 'Disetujui', 'class' => 'badge-success'],
    'ditolak'   => ['label' => 'Ditolak',   'class' => 'badge-danger'],
];

// Label + warna button per jenis dokumen (kolom Berkas)
$dokumenSkl = [
    'artikel'             => ['label' => 'Artikel',             'color' => 'primary'],
    'lembar_pengesahan'   => ['label' => 'Lembar Pengesahan',   'color' => 'success'],
    'sertifikat_kompetensi' => ['label' => 'Sertifikat Kompetensi', 'color' => 'danger'],
];

// Opsi filter status (urut sesuai mockup)
$daftarFilterStatus = [
    ''             => 'Semua Status',
    'menunggu'     => 'Menunggu',
    'ditolak'      => 'Ditolak',
    'disetujui'    => 'Disetujui',
];

// Filter status (lewat query string)
$pilihanStatus = $_GET['status'] ?? '';
if (array_key_exists($pilihanStatus, $statusSkl)) {
    $daftarSkl = array_values(array_filter($daftarSkl, function ($s) use ($pilihanStatus) {
        return $s['status'] === $pilihanStatus;
    }));
}

// Pencarian mahasiswa — NPM atau nama, tidak case-sensitive
$kataCari = trim($_GET['cari'] ?? '');
if ($kataCari !== '') {
    $daftarSkl = array_values(array_filter($daftarSkl, function ($s) use ($kataCari) {
        return stripos($s['npm'], $kataCari) !== false || stripos($s['nama'], $kataCari) !== false;
    }));
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">SKL</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Form SKL</h6>
    </div>
    <div class="card-body">

        <!-- Filter: Status (atas) + Pencarian Mahasiswa (bawah) — di dalam Form SKL -->
        <div class="d-flex flex-column align-items-end mb-3">
            <form class="form-inline flex-wrap justify-content-end mb-2" method="get" action="<?= base_url('graduate/skl') ?>">
                <label class="my-1 mr-2" for="status">Pilih Status</label>
                <select class="custom-select custom-select-sm my-1 mr-sm-2" id="status" name="status">
                    <?php foreach ($daftarFilterStatus as $value => $label): ?>
                    <option value="<?= esc($value) ?>" <?= ($pilihanStatus === $value) ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary my-1">Filter</button>
            </form>

            <form class="form-inline flex-wrap justify-content-end mb-0" method="get" action="<?= base_url('graduate/skl') ?>">
                <label class="my-1 mr-2" for="cari">Cari Mahasiswa</label>
                <div class="input-group input-group-sm my-1 mr-sm-2" style="min-width: 200px; max-width: 100%;">
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
                        <th style="width: 4%;">No</th>
                        <th style="width: 11%;">NPM</th>
                        <th style="width: 18%;">Nama Mahasiswa</th>
                        <th style="width: 15%;">Prodi</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 30%;">Berkas</th>
                        <th style="width: 12%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarSkl)): ?>
                    <tr class="text-center">
                        <td colspan="7" class="text-gray-500">
                            <?php if ($kataCari !== ''): ?>
                            Mahasiswa tidak ditemukan.
                            <?php elseif ($pilihanStatus !== ''): ?>
                            Tidak ada data dengan status tersebut.
                            <?php else: ?>
                            Tidak ada data SKL.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php $no = 1; foreach ($daftarSkl as $s): ?>
                    <tr class="text-center">
                        <td><?= $no++ ?></td>
                        <td><?= esc($s['npm']) ?></td>
                        <td class="text-left"><?= esc($s['nama']) ?></td>
                        <td><?= esc($s['prodi']) ?></td>

                        <!-- Status -->
                        <td>
                            <span class="badge <?= $statusSkl[$s['status']]['class'] ?>"><?= esc($statusSkl[$s['status']]['label']) ?></span>
                        </td>

                        <!-- Kolom Berkas: 3 button per dokumen (warna = sudah diunggah) -->
                        <td>
                            <?php foreach ($dokumenSkl as $key => $doc): ?>
                                <?php if (! empty($s['berkas'][$key])): ?>
                                <button type="button" class="btn btn-sm btn-<?= $doc['color'] ?> m-1" title="<?= esc($doc['label']) ?> sudah diunggah"><?= esc($doc['label']) ?></button>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary m-1" disabled title="<?= esc($doc['label']) ?> belum diunggah"><?= esc($doc['label']) ?></button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>

                        <!-- Kolom Aksi: 1 button dropdown (Setujui / Tolak) -->
                        <td>
                            <div class="dropdown d-inline-block">
                                <button type="button" class="btn btn-primary btn-sm dropdown-toggle aksi-skl-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h fa-fw"></i> Aksi
                                </button>
                                <div class="dropdown-menu text-left">
                                    <a class="dropdown-item text-success setujui-skl" href="#"><i class="fas fa-check fa-fw mr-1"></i>Setujui</a>
                                    <a class="dropdown-item text-danger tolak-skl" href="#"><i class="fas fa-times fa-fw mr-1"></i>Tolak</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

    <script>
        // Aksi Setujui/Tolak dari dropdown mengubah badge status (dummy —
        // endpoint controller menyusul saat integrasi database)
        $(document).on('click', '.setujui-skl, .tolak-skl', function (e) {
            e.preventDefault();

            var $item = $(this);
            var $row = $item.closest('tr');
            var disetujui = $item.hasClass('setujui-skl');

            $row.find('td').eq(4).find('.badge')
                .removeClass('badge-warning badge-success badge-danger')
                .addClass(disetujui ? 'badge-success' : 'badge-danger')
                .text(disetujui ? 'Disetujui' : 'Ditolak');

            $row.find('.aksi-skl-dropdown').prop('disabled', true);
        });
    </script>

<?= $this->endSection(); ?>
