<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari database menyusul.
 *
 * Status per mahasiswa:
 *   berkas[...]  = true (sudah diunggah / warna) | false (belum / outline abu)
 *   Kolom Upload tidak memuat SKL (sesuai ketentuan).
 */
$mahasiswaBerkas = [
    [
        'npm'    => '2021010001',
        'nama'   => 'Ahmad Fauzi',
        'berkas' => ['berita_acara' => true, 'undangan' => true, 'surat_tugas' => true, 'skl' => false, 'surat_tugas_penguji' => false],
    ],
    [
        'npm'    => '2021010002',
        'nama'   => 'Siti Rahmawati',
        'berkas' => ['berita_acara' => true, 'undangan' => true, 'surat_tugas' => false, 'skl' => false, 'surat_tugas_penguji' => false],
    ],
    [
        'npm'    => '2021010003',
        'nama'   => 'Rizky Pratama',
        'berkas' => ['berita_acara' => true, 'undangan' => true, 'surat_tugas' => true, 'skl' => true, 'surat_tugas_penguji' => true],
    ],
    [
        'npm'    => '2021010004',
        'nama'   => 'Dinda Permata',
        'berkas' => ['berita_acara' => true, 'undangan' => false, 'surat_tugas' => true, 'skl' => true, 'surat_tugas_penguji' => true],
    ],
    [
        'npm'    => '2021010005',
        'nama'   => 'Bagus Setiawan',
        'berkas' => ['berita_acara' => false, 'undangan' => false, 'surat_tugas' => false, 'skl' => false, 'surat_tugas_penguji' => false],
    ],
    [
        'npm'    => '2021010006',
        'nama'   => 'Nadia Aulia',
        'berkas' => ['berita_acara' => true, 'undangan' => true, 'surat_tugas' => true, 'skl' => true, 'surat_tugas_penguji' => false],
    ],
];

// Label + warna button per jenis dokumen (kolom Berkas)
$dokumenBerkas = [
    'berita_acara'       => ['label' => 'Berita Acara',       'color' => 'success'],
    'undangan'           => ['label' => 'Undangan',           'color' => 'primary'],
    'surat_tugas'        => ['label' => 'Surat Tugas',        'color' => 'info'],
    'skl'                => ['label' => 'SKL',                'color' => 'warning'],
    'surat_tugas_penguji' => ['label' => 'Surat Tugas Penguji', 'color' => 'danger'],
];

// Dokumen pada dropdown Upload (SKL tidak termasuk)
$dokumenUpload = ['berita_acara', 'undangan', 'surat_tugas', 'surat_tugas_penguji'];

// Daftar lengkap untuk dropdown filter (sebelum data difilter)
$daftarMahasiswa = $mahasiswaBerkas;

// Filter mahasiswa (opsional, lewat query string)
$pilihanMahasiswa = $_GET['mahasiswa'] ?? '';
if ($pilihanMahasiswa !== '') {
    $mahasiswaBerkas = array_values(array_filter($mahasiswaBerkas, function ($m) use ($pilihanMahasiswa) {
        return $m['npm'] === $pilihanMahasiswa;
    }));
}

$semesterAktif = $_GET['semester'] ?? 'Ganjil 2025/2026';
$daftarSemester = ['Ganjil 2025/2026', 'Genap 2025/2026', 'Ganjil 2024/2025', 'Genap 2024/2025'];
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Berkas</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Form Berkas</h6>
    </div>
    <div class="card-body">

        <!-- Filter: Semester (atas) + Mahasiswa (bawah) — di dalam Form Berkas -->
        <div class="text-right mb-3">
            <form class="form-inline justify-content-end mb-2" method="get" action="<?= base_url('graduate/berkas') ?>">
                <label class="my-1 mr-2" for="semester">Pilih Semester</label>
                <select class="custom-select custom-select-sm my-1 mr-sm-2" id="semester" name="semester">
                    <?php foreach ($daftarSemester as $s): ?>
                    <option value="<?= esc($s) ?>" <?= ($semesterAktif === $s) ? 'selected' : '' ?>><?= esc($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary my-1">Filter</button>
            </form>

            <form class="form-inline justify-content-end mb-0" method="get" action="<?= base_url('graduate/berkas') ?>">
                <label class="my-1 mr-2" for="mahasiswa">Pilih Mahasiswa</label>
                <select class="custom-select custom-select-sm my-1 mr-sm-2" id="mahasiswa" name="mahasiswa">
                    <option value="" <?= ($pilihanMahasiswa === '') ? 'selected' : '' ?>>Semua Mahasiswa</option>
                    <?php foreach ($daftarMahasiswa as $m): ?>
                    <option value="<?= esc($m['npm']) ?>" <?= ($pilihanMahasiswa === $m['npm']) ? 'selected' : '' ?>><?= esc($m['nama']) ?> (<?= esc($m['npm']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary my-1">Filter</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 12%;">NPM</th>
                        <th style="width: 25%;">Nama Mahasiswa</th>
                        <th style="width: 48%;">Berkas</th>
                        <th style="width: 10%;">Upload</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mahasiswaBerkas)): ?>
                    <tr class="text-center">
                        <td colspan="5" class="text-gray-500">Tidak ada data mahasiswa.</td>
                    </tr>
                    <?php endif; ?>

                    <?php $no = 1; foreach ($mahasiswaBerkas as $m): ?>
                    <tr class="text-center">
                        <td><?= $no++ ?></td>
                        <td><?= esc($m['npm']) ?></td>
                        <td class="text-left"><?= esc($m['nama']) ?></td>

                        <!-- Kolom Berkas: button per dokumen (warna = sudah diunggah) -->
                        <td>
                            <?php foreach ($dokumenBerkas as $key => $doc): ?>
                                <?php if (! empty($m['berkas'][$key])): ?>
                                <button type="button" class="btn btn-sm btn-<?= $doc['color'] ?> m-1" title="<?= esc($doc['label']) ?> sudah diunggah"><?= esc($doc['label']) ?></button>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary m-1" disabled title="<?= esc($doc['label']) ?> belum diunggah"><?= esc($doc['label']) ?></button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>

                        <!-- Kolom Upload: 1 button dropdown berisi 4 dokumen -->
                        <td>
                            <div class="dropdown d-inline-block">
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-upload fa-fw"></i> Upload
                                </button>
                                <div class="dropdown-menu text-left">
                                    <?php foreach ($dokumenUpload as $key): $doc = $dokumenBerkas[$key]; ?>
                                        <?php if (! empty($m['berkas'][$key])): ?>
                                        <a class="dropdown-item text-muted" href="#"><i class="fas fa-check fa-fw mr-1"></i><?= esc($doc['label']) ?></a>
                                        <?php else: ?>
                                        <a class="dropdown-item" href="#"><i class="fas fa-upload fa-fw mr-1"></i><?= esc($doc['label']) ?></a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
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
