<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
$userSession = session()->get('user_session') ?? [];
$npm = (string) ($user['npm'] ?? $user['id'] ?? $userSession['npm'] ?? $userSession['id'] ?? '');

$berkasMap = [];
$pendaftaran = null;

if ($npm !== '') {
    try {
        $pendaftaran = (new \App\Models\PendaftaranWisudaModel())->getByNpm($npm);
        if ($pendaftaran !== null) {
            $berkasMap = (new \App\Models\BerkasPendaftaranWisudaModel())->getBerkasMapByPendaftaranId((int) $pendaftaran['id']);
        }
    } catch (\Throwable $e) {
        $berkasMap = [];
    }
}

$daftarPersyaratan = [];
try {
    $dbPersyaratan = (new \App\Models\PersyaratanWisudaModel())->getPersyaratanAktif();
    foreach ($dbPersyaratan as $item) {
        $namaSyarat = $item['nama'];
        $idSyarat   = $item['id'];
        $isUploaded = isset($berkasMap[$namaSyarat]) || isset($berkasMap['id_' . $idSyarat]);

        $daftarPersyaratan[] = [
            'id'        => $idSyarat,
            'nama'      => $namaSyarat,
            'status'    => $isUploaded ? 'sudah' : 'belum',
            'is_wajib'  => !empty($item['is_wajib']),
            'file_name' => $isUploaded ? ($berkasMap[$namaSyarat]['nama_file'] ?? ($berkasMap['id_' . $idSyarat]['nama_file'] ?? null)) : null,
        ];
    }
} catch (\Throwable $e) {
    $daftarPersyaratan = [];
}

// Ringkasan progress untuk kartu di atas tabel
$totalPersyaratan = count($daftarPersyaratan);
$terpenuhi = count(array_filter($daftarPersyaratan, function ($p) {
    return $p['status'] === 'sudah';
}));
$persentase = $totalPersyaratan > 0 ? round(($terpenuhi / $totalPersyaratan) * 100) : 0;
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-tachometer-alt text-primary mr-2"></i>Dashboard Mahasiswa</h1>
</div>

<!-- Kartu Progress Persyaratan Wisuda -->
<div class="card bg-gradient-primary shadow mb-4">
    <div class="card-body py-3">
        <div class="text-white-50 small text-uppercase font-weight-bold mb-1">Persyaratan Wisuda</div>
        <div class="h5 mb-2 font-weight-bold text-white"><?= $terpenuhi ?>/<?= $totalPersyaratan ?> Persyaratan Terpenuhi (<?= $persentase ?>%)</div>
        <div class="progress" style="height: 10px; background-color: rgba(255, 255, 255, .35);">
            <div class="progress-bar bg-white" role="progressbar" style="width: <?= $persentase ?>%;" aria-valuenow="<?= $persentase ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
</div>

<!-- Card Persyaratan Wisuda (murni informasi) -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Persyaratan Wisuda</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 6%;">No</th>
                        <th style="width: 54%;">Persyaratan Wisuda</th>
                        <th style="width: 15%;">Sifat</th>
                        <th style="width: 25%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarPersyaratan)): ?>
                    <tr class="text-center">
                        <td colspan="4" class="text-gray-500 py-3">Belum ada daftar persyaratan aktif.</td>
                    </tr>
                    <?php endif; ?>

                    <?php $no = 1; foreach ($daftarPersyaratan as $p): ?>
                    <tr class="text-center align-middle">
                        <td><?= $no++ ?></td>
                        <td class="text-left font-weight-bold text-gray-900"><?= esc($p['nama']) ?></td>
                        <td>
                            <?php if ($p['is_wajib']): ?>
                                <span class="badge badge-primary px-2 py-1">Wajib</span>
                            <?php else: ?>
                                <span class="badge badge-secondary px-2 py-1">Opsional</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['status'] === 'sudah'): ?>
                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Sudah Diunggah</span>
                                <?php if (!empty($p['file_name'])): ?>
                                    <a href="<?= base_url('graduate/berkas/view/' . $p['file_name']) ?>" target="_blank" class="btn btn-sm btn-info py-0 px-2 ml-1" title="Lihat Berkas">
                                        <i class="fas fa-eye fa-sm"></i>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-secondary px-2 py-1"><i class="fas fa-clock mr-1"></i> Belum Diunggah</span>
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
