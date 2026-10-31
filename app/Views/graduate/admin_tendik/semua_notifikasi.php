<?= $this->extend('graduate/layout/template') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-4">
        <h1 class="h3 mb-2 mb-sm-0 text-gray-800">Riwayat Notifikasi Kelulusan &amp; Yudisium</h1>
        <a href="<?= base_url('graduate/sk_yudisium') ?>" class="btn btn-sm btn-primary shadow-sm mt-2 mt-sm-0">
            <i class="fas fa-tasks fa-sm text-white-50 mr-1"></i> Kelola SK Yudisium
        </a>
    </div>

    <!-- Tab Navigasi -->
    <ul class="nav nav-pills flex-column flex-sm-row mb-3" id="pills-tab" role="tablist">
        <li class="nav-item mb-1 mb-sm-0 mr-sm-2">
            <a class="nav-link active font-weight-bold" id="pills-yudisium-tab" data-toggle="pill" href="#pills-yudisium" role="tab" aria-controls="pills-yudisium" aria-selected="true">
                <i class="fas fa-graduation-cap fa-fw mr-1"></i> SK Yudisium (<?= count($skYudisium) ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link font-weight-bold" id="pills-calon-tab" data-toggle="pill" href="#pills-calon" role="tab" aria-controls="pills-calon" aria-selected="false">
                <i class="fas fa-user-graduate fa-fw mr-1"></i> Calon Wisudawan Lulus (<?= count($calonWisudawan) ?>)
            </a>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        <!-- Tab 1: SK Yudisium -->
        <div class="tab-pane fade show active" id="pills-yudisium" role="tabpanel" aria-labelledby="pills-yudisium-tab">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Mahasiswa Terplot SK Yudisium</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr class="text-center">
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 15%;">NPM</th>
                                    <th>Nama Mahasiswa</th>
                                    <th style="width: 12%;">Status</th>
                                    <th style="width: 20%;">Tanggal SK Yudisium</th>
                                    <th style="width: 15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($skYudisium)) : ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada riwayat mahasiswa terplot SK Yudisium.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php $no = 1; foreach ($skYudisium as $s) : ?>
                                        <tr class="<?= empty($s['tanggal_sk_yudisium']) ? 'font-weight-bold table-warning' : '' ?>">
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td><?= esc($s['npm']) ?></td>
                                            <td><?= esc($s['nama']) ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-<?= ($s['status'] === 'transfer') ? 'warning' : 'info' ?>"><?= esc(ucfirst($s['status'])) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if (! empty($s['tanggal_sk_yudisium'])) : ?>
                                                    <span class="badge badge-success"><?= esc(date('d/m/Y', strtotime($s['tanggal_sk_yudisium']))) ?></span>
                                                <?php else : ?>
                                                    <span class="badge badge-danger">Belum Ditetapkan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url('graduate/sk_yudisium') ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit fa-sm"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Calon Wisudawan -->
        <div class="tab-pane fade" id="pills-calon" role="tabpanel" aria-labelledby="pills-calon-tab">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Mahasiswa Lulus Sidang (Calon Wisudawan)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr class="text-center">
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 15%;">NPM</th>
                                    <th>Nama Mahasiswa</th>
                                    <th style="width: 20%;">Tanggal Selesai Skripsi</th>
                                    <th style="width: 15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($calonWisudawan)) : ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada riwayat calon wisudawan.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php $no = 1; foreach ($calonWisudawan as $c) : ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td><?= esc($c['npm']) ?></td>
                                            <td><?= esc($c['nama']) ?></td>
                                            <td class="text-center">
                                                <?= ! empty($c['tanggal_selesai_skripsi']) ? esc(date('d/m/Y', strtotime($c['tanggal_selesai_skripsi']))) : '-' ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= base_url('graduate/calon_wisudawan') ?>" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-user-check fa-sm"></i> Lihat
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

