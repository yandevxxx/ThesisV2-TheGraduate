
<?= $this->extend("layout/template"); ?>

<?= $this->section("content"); ?>

<?php if (session()->getFlashdata("message")) : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= session()->getFlashdata("message")["icon"]; ?></p>
        <p id="title" hidden><?= session()->getFlashdata("message")["title"]; ?></p>
        <p id="text" hidden><?= session()->getFlashdata("message")["text"]; ?></p>
    </div>
<?php endif; ?>

<style>
    .detail-info-label {
        font-size: 13px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 7px;
    }

    .detail-info-value {
        background-color: #f8f9fc;
        border: 1px solid #dce1e8;
        border-radius: 6px;
        padding: 10px 12px;
        min-height: 40px;
        color: #495057;
        font-size: 14px;
        overflow-wrap: anywhere;
    }

    .mk-card {
        border: 1px solid #e1e5eb;
        border-radius: 8px;
        height: 100%;
        background-color: #fff;
        overflow: hidden;
    }

    .mk-card-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e1e5eb;
        padding: 13px 15px;
        font-size: 14px;
        font-weight: 600;
        color: #343a40;
    }

    .mk-card-body {
        padding: 12px 15px;
        height: 230px;
        overflow-y: auto;
    }

    .mk-card-body ul {
        padding-left: 20px;
        margin-bottom: 0;
    }

    .mk-card-body li {
        font-size: 13px;
        color: #495057;
        margin-bottom: 8px;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .mk-empty {
        color: #858796;
        font-size: 13px;
        font-style: italic;
    }

    .document-title {
        font-weight: 600;
        font-size: 15px;
        color: #343a40;
        margin-bottom: 12px;
    }

    .document-preview {
        width: 100%;
        height: 520px;
        border: 1px solid #e1e5eb;
        border-radius: 6px;
        overflow: hidden;
    }

    .document-preview iframe {
        width: 100%;
        height: 100%;
        border: 0;
    }

    @media (max-width: 767px) {
        .mk-card-body {
            height: 200px;
        }

        .document-preview {
            height: 400px;
        }
    }
</style>

<h1 class="h3 mb-3 text-gray-800"><?= esc($title) ?></h1>

<div class="card shadow mb-4">

    <!-- HEADER -->
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
        <span class="font-weight-bold text-primary">
            Detail Pengajuan Penulisan Skripsi
        </span>

        <?php if ($detailPengajuan['status_persetujuan_skripsi'] == null): ?>
            <span class="badge badge-secondary p-2">TERTUNDA</span>
        <?php elseif ($detailPengajuan['status_persetujuan_skripsi'] == 'Ditolak'): ?>
            <span class="badge badge-danger p-2">DITOLAK</span>
        <?php elseif ($detailPengajuan['status_persetujuan_skripsi'] == 'Disetujui'): ?>
            <span class="badge badge-success p-2">DISETUJUI</span>
        <?php endif; ?>
    </div>

    <div class="card-body">

        <!-- INFORMASI MAHASISWA -->
        <div class="mb-4">
            <h6 class="font-weight-bold text-dark mb-3">
                Informasi Mahasiswa
            </h6>

            <div class="row">

                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="detail-info-label">Nama Mahasiswa</div>
                    <div class="detail-info-value">
                        <?= esc($detailPengajuan['nama_mahasiswa'] ?? '-') ?>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="detail-info-label">NPM</div>
                    <div class="detail-info-value">
                        <?= esc($detailPengajuan['npm'] ?? '-') ?>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="detail-info-label">Program Studi</div>
                    <div class="detail-info-value">
                        <?= esc($detailPengajuan['nama_prodi'] ?? '-') ?>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="detail-info-label">SKS yang Sudah Lulus</div>
                    <div class="detail-info-value">
                        <?= esc($detailPengajuan['sks_lulus'] ?? '0') ?> SKS
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="detail-info-label">Dosen Pembimbing Akademik</div>
                    <div class="detail-info-value">
                        <?= esc($detailPengajuan['nama_pembimbing_akademik'] ?? '-') ?>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="detail-info-label">Jenis Tugas Akhir</div>
                    <div class="detail-info-value">
                        <?= esc($detailPengajuan['nama_jenis_tugas_akhir'] ?? '-') ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- INFORMASI MATA KULIAH -->
        <div class="mb-4">

            <h6 class="font-weight-bold text-dark mb-3">
                Informasi Mata Kuliah
            </h6>

            <div class="row">

                <!-- SUDAH LULUS -->
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="mk-card">

                        <div class="mk-card-header">
                            <i class="fas fa-check-circle text-success mr-2"></i>
                            Mata Kuliah Sudah Lulus
                            <span class="badge badge-success float-right">
                                <?= count($detailPengajuan['mk_sudah_lulus_list'] ?? []) ?>
                            </span>
                        </div>

                        <div class="mk-card-body">
                            <?php if (!empty($detailPengajuan['mk_sudah_lulus_list'])): ?>
                                <ul>
                                    <?php foreach ($detailPengajuan['mk_sudah_lulus_list'] as $mk): ?>
                                        <li><?= esc($mk) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="mk-empty">Belum ada mata kuliah.</span>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <!-- WAJIB BELUM LULUS -->
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="mk-card">

                        <div class="mk-card-header">
                            <i class="fas fa-exclamation-circle text-warning mr-2"></i>
                            Mata Kuliah Wajib Belum Lulus
                            <span class="badge badge-warning float-right">
                                <?= count($detailPengajuan['mk_wajib_belum_lulus_list'] ?? []) ?>
                            </span>
                        </div>

                        <div class="mk-card-body">
                            <?php if (!empty($detailPengajuan['mk_wajib_belum_lulus_list'])): ?>
                                <ul>
                                    <?php foreach ($detailPengajuan['mk_wajib_belum_lulus_list'] as $mk): ?>
                                        <li><?= esc($mk) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="mk-empty">Tidak ada mata kuliah wajib yang belum lulus.</span>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <!-- SEDANG BERJALAN -->
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="mk-card">

                        <div class="mk-card-header">
                            <i class="fas fa-book-open text-primary mr-2"></i>
                            Mata Kuliah Sedang Berjalan
                            <span class="badge badge-primary float-right">
                                <?= count($detailPengajuan['mk_sedang_berjalan_list'] ?? []) ?>
                            </span>
                        </div>

                        <div class="mk-card-body">
                            <?php if (!empty($detailPengajuan['mk_sedang_berjalan_list'])): ?>
                                <ul>
                                    <?php foreach ($detailPengajuan['mk_sedang_berjalan_list'] as $mk): ?>
                                        <li><?= esc($mk) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="mk-empty">Tidak ada mata kuliah yang sedang berjalan.</span>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

            </div>
        </div>
        
<!-- DOKUMEN PERSYARATAN -->
<div class="col-lg-12 mb-3">
    <div class="form-group">
        <label for="file_khs">File Kartu Hasil Studi</label>

        <div class="embed-responsive embed-responsive-16by9 mx-auto"
             id="file_khs"
             style="width: 90%">

            <iframe
                class="embed-responsive-item"
                src="<?= base_url("folderKHS/".$detailPengajuan['file_khs']) ?>">
            </iframe>

        </div>
    </div>
</div>

<div class="col-lg-12 mb-3">
    <div class="form-group">
        <label for="file_krs">File Kartu Rencana Studi</label>

        <div class="embed-responsive embed-responsive-16by9 mx-auto"
             id="file_krs"
             style="width: 90%">

            <iframe
                class="embed-responsive-item"
                src="<?= base_url("folderKRS/".$detailPengajuan['file_krs']) ?>">
            </iframe>

        </div>
    </div>
</div>

<div class="col-lg-12">
    <div class="form-group">
        <label for="file_persetujuan_skripsi">
            File Persetujuan Penulisan Skripsi
        </label>

        <div class="embed-responsive embed-responsive-16by9 mx-auto"
             id="file_persetujuan_skripsi"
             style="width: 90%">

            <iframe
                class="embed-responsive-item"
                src="<?= base_url("folderPersetujuanSkripsi/".$detailPengajuan['file_persetujuan_skripsi']) ?>">
            </iframe>

        </div>
    </div>
</div>

        
<!-- TOMBOL AKSI -->
<div class="col-lg-12 d-flex justify-content-end">

    <a
        role="button"
        href="<?= base_url("TenagaKependidikan/pengajuanSkripsi") ?>"
        class="btn btn-secondary mr-2">
        Kembali
    </a>

    <form
        action="<?= base_url("TenagaKependidikan/tolakPengajuanSkripsi/".$detailPengajuan['npm']) ?>"
        method="post"
        class="d-inline">

        <button class="btn btn-danger mr-2" type="submit">
            Tolak Pengajuan
        </button>

    </form>

    <form
        action="<?= base_url("TenagaKependidikan/terimaPengajuanSkripsi/".$detailPengajuan['npm']) ?>"
        method="post"
        class="d-inline">

        <button class="btn btn-success" type="submit">
            Setujui Pengajuan
        </button>

    </form>

</div>

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

<script src="<?= base_url('assets/js/tenagakependidikan/detailPengajuanSkripsi.js'); ?>"></script>

<?= $this->endSection(); ?>
