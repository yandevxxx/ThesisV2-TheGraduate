<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari database menyusul.
 *
 * status: 'sudah' (Sudah Diunggah) | 'belum' (Belum Diunggah)
 */
$daftarPersyaratan = [
    ['nama' => 'Fotocopy Berwarna Ijazah SMA', 'status' => 'sudah'],
    ['nama' => 'Fotocopy Berwarna Akte Kelahiran', 'status' => 'sudah'],
    ['nama' => 'Fotocopy Berwarna Kartu Keluarga', 'status' => 'sudah'],
    ['nama' => 'Fotocopy Berwarna KTP yang berlaku (diperbesar 4 kali)', 'status' => 'sudah'],
    ['nama' => 'Fotocopy Berwarna KTM yang berlaku (diperbesar 4 kali)', 'status' => 'sudah'],
    ['nama' => 'Pas Foto Berwarna', 'status' => 'sudah'],
    ['nama' => 'Surat Keterangan Kelulusan (SKL)', 'status' => 'belum'],
    ['nama' => 'Sertifikat TOEIC', 'status' => 'belum'],
    ['nama' => 'File Skripsi', 'status' => 'belum'],
];

// Ringkasan progress untuk kartu di atas tabel
$totalPersyaratan = count($daftarPersyaratan);
$terpenuhi = count(array_filter($daftarPersyaratan, function ($p) {
    return $p['status'] === 'sudah';
}));
$persentase = $totalPersyaratan > 0 ? round(($terpenuhi / $totalPersyaratan) * 100) : 0;
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard Mahasiswa</h1>
</div>

<!-- Kartu Progress Persyaratan Wisuda -->
<div class="card bg-gradient-primary shadow mb-4">
    <div class="card-body py-3">
        <div class="text-white-50 small text-uppercase font-weight-bold mb-1">Persyaratan Wisuda</div>
        <div class="h5 mb-2 font-weight-bold text-white"><?= $terpenuhi ?>/<?= $totalPersyaratan ?> Persyaratan Terpenuhi</div>
        <div class="progress" style="height: 10px; background-color: rgba(255, 255, 255, .35);">
            <div class="progress-bar bg-white" role="progressbar" style="width: <?= $persentase ?>%;" aria-valuenow="<?= $persentase ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
</div>

<!-- Card Persyaratan Wisuda (murni informasi — tanpa aksi) -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Persyaratan Wisuda</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr class="text-center">
                        <th style="width: 6%;">No</th>
                        <th style="width: 64%;">Persyaratan Wisuda</th>
                        <th style="width: 30%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($daftarPersyaratan as $p): ?>
                    <tr class="text-center">
                        <td><?= $no++ ?></td>
                        <td class="text-left"><?= esc($p['nama']) ?></td>
                        <td>
                            <?php if ($p['status'] === 'sudah'): ?>
                            <span class="badge badge-success">Sudah Diunggah</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">Belum Diunggah</span>
                            <?php endif; ?>
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
