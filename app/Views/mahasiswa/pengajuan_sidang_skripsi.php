<?= $this->extend("layout/template"); ?>



<?= $this->section("content"); ?>

<?php if (session()->getFlashdata("message")) : ?>

    <div id="flashdata" data-open="true">

        <p id="icon" hidden><?= session()->getFlashdata("message")["icon"]; ?></p>

        <p id="title" hidden><?= session()->getFlashdata("message")["title"]; ?></p>

        <p id="text" hidden><?= session()->getFlashdata("message")["text"]; ?></p>

    </div>

<?php endif; ?>

    <h1 class="h3 mb-2 text-gray-800"><?= $title ?></h1>

    <div class="row">

        <div class="col-lg-12">

            <?php if ($lastSkripsi == null): ?>

                <div class="alert alert-danger" role="alert">

                    <small>

                        <strong>Tambahkan Skripsi</strong> terlebih dahulu pada <a href="<?= base_url("mahasiswa/skripsi") ?>">Menu Berikut</a>.

                    </small>

                </div>

            <?php elseif ($seminarPrasidang == null || $seminarPrasidang['status'] != 'LAYAK SIDANG'): ?>

                <div class="alert alert-danger" role="alert">

                    <small>

                        Lakukan <strong>Seminar Prasidang</strong> terlebih dahulu dan pastikan Anda dinyatakan <strong>Layak Sidang</strong>.

                    </small>

                </div>

            <?php elseif ($lastSkripsi != null && ($lastSkripsi['jumlah_bimbingan_ilmu'] < 8 || $lastSkripsi['jumlah_bimbingan_agama'] < 4)): ?>

                <div class="alert alert-danger" role="alert">

                    <small>

                        Pengajuan Sidang Skripsi hanya dapat dibuat jika telah melakukan setidaknya <strong>8 kali Bimbingan Ilmu</strong> dan <strong>4 kali Bimbingan Agama</strong>.

                    </small>

                </div>

            <?php elseif ($lastSkripsi != null && count($pengajuanSidangSkripsi) > 0 && $pengajuanSidangSkripsi[count($pengajuanSidangSkripsi)-1]['id_skripsi'] == $lastSkripsi['id'] && $pengajuanSidangSkripsi[count($pengajuanSidangSkripsi)-1]['status'] == 'TERTUNDA'): ?>

                <div class="alert alert-info" role="alert">

                    <small>

                        <strong>Pengajuan Sidang Skripsi</strong> anda telah berhasil <strong>Terkirim</strong>.

                    </small>

                </div>

            <?php elseif ($lastSkripsi != null && count($pengajuanSidangSkripsi) > 0 && $pengajuanSidangSkripsi[count($pengajuanSidangSkripsi)-1]['id_skripsi'] == $lastSkripsi['id'] && $pengajuanSidangSkripsi[count($pengajuanSidangSkripsi)-1]['status'] == 'DISETUJUI'): ?>

                <div class="alert alert-success" role="alert">

                    <small>

                        <strong>Pengajuan Sidang Skripsi</strong> anda telah <strong>Disetujui</strong>.

                    </small>

                </div>

            <?php elseif ($lastSkripsi != null && count($pengajuanSidangSkripsi) > 0 && $pengajuanSidangSkripsi[count($pengajuanSidangSkripsi)-1]['id_skripsi'] == $lastSkripsi['id'] && $pengajuanSidangSkripsi[count($pengajuanSidangSkripsi)-1]['status'] == 'DITOLAK'): ?>

                <div class="alert alert-danger" role="alert">

                    <small>

                        <strong>Pengajuan Sidang Skripsi</strong> anda <strong>Ditolak</strong>. Silahkan <strong>Perbaiki Pengajuan Terakhir</strong> Anda.

                    </small>

                </div>

            <?php elseif ($seminarPrasidang != null && $seminarPrasidang['status'] == 'LAYAK SIDANG'): ?>

                <div class="alert alert-info" role="alert">

                    <small>

                        Silahkan buat <strong>Pengajuan Sidang Skripsi</strong> anda.

                    </small>

                </div>

            

            <?php endif; ?>

        </div>

    </div>

    <div class="card shadow mb-4">

        <div class="card-header py-3">

            <?= $title ?>

        </div>

        <div class="card-body">

            <?php if (isset($is_open) && $is_open == false) : ?>
            <div class="alert alert-danger mb-4">
                <i class="fas fa-lock"></i> <strong>Pendaftaran Ditutup.</strong>
                <br>Periode pengajuan sidang skripsi belum dibuka atau sudah berakhir.
            </div>
            <?php elseif (isset($deadline)) : ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-clock"></i> Batas waktu pengajuan: <strong><?= date('d F Y H:i', strtotime($deadline)); ?></strong>
                </div>
            <?php endif; ?>

            <div class="row mb-3">

                <div class="col-lg-3 d-flex align-items-center">

                    <?php
                    // Syarat Akademik
                    $syaratTerpenuhi = !(
                        $lastSkripsi == null ||
                        $seminarPrasidang == null ||
                        $seminarPrasidang['status'] != 'LAYAK SIDANG' ||
                        ($lastSkripsi != null && count($pengajuanSidangSkripsi) > 0 && $pengajuanSidangSkripsi[count($pengajuanSidangSkripsi)-1]['id_skripsi'] == $lastSkripsi['id']) ||
                        $lastSkripsi['jumlah_bimbingan_ilmu'] < 8 ||
                        $lastSkripsi['jumlah_bimbingan_agama'] < 4
                    );

                    // Syarat Jadwal
                    $jadwalBuka = (isset($is_open) && $is_open == true);
                    ?>

                    <?php if ($syaratTerpenuhi && $jadwalBuka): ?>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#tambahPengajuan">Tambahkan Pengajuan</button>
                    <?php else: ?>
                        <?php
                            $tooltip = "";
                            if (!$jadwalBuka) $tooltip = " (Jadwal Tutup)";
                            else $tooltip = " (Syarat belum terpenuhi / Pengajuan sebelumnya belum selesai)";
                        ?>
                        <button class="btn btn-secondary" disabled title="Tidak dapat mengajukan<?= $tooltip ?>">Tambahkan Pengajuan</button>
                    <?php endif; ?>

                </div>

            </div>



            <div class="table-responsive">

                <table class="table table-bordered" id="tablePengajuan" width="100%" cellspacing="0">

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>Judul</th>

                            <th>Bidang</th>

                            <th>Sifat Penelitian</th>

                            <th>Sumber Penelitian</th>

                            <th>Tanggal Pengajuan</th>

                            <th>Status</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php

                        $counter = 1;

                        foreach($pengajuanSidangSkripsi as $pss):

                        ?>

                            <tr>

                                <td><?= $counter ?></td>

                                <td><?= $pss['judul'] ?></td>

                                <td><?= $pss['nama_bidang'] ?></td>

                                <td><?= $pss['sifat'] ?></td>

                                <td><?= $pss['sumber'] ?></td>

                                <td><?= date_format(date_create(strval($pss['tanggal_pengajuan'])), 'd-m-Y') ?></td>

                                <td><?= $pss['status'] ?></td>

                                <td>

                                    <a role="button" class="btn btn-primary" href="<?= base_url("mahasiswa/detailPengajuanSidangSkripsi/".$pss['id']) ?>" data-toggle="tooltip" title="Detail"><i class="fas fa-info-circle"></i></a>

                                </td>

                            </tr>

                        <?php 

                        $counter++;

                        endforeach;

                        ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>



    <div class="modal fade" id="tambahPengajuan" tabindex="-1" role="dialog" aria-labelledby="tambahPengajuanLabel" aria-hidden="true">

        <div class="modal-dialog" role="document">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="tambahPengajuanLabel">Tambah Pengajuan</h5>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                    <span aria-hidden="true">&times;</span>

                    </button>

                </div>

                <form action="<?= base_url("mahasiswa/insertPengajuanSidangSkripsi") ?>" method="post" id="formTambahPengajuan" enctype="multipart/form-data">

                    <div class="modal-body">

                        <div class="row">

                            <?php if ($lastSkripsi != null) : ?>

                                <input type="hidden" id="id_skripsi" name="id_skripsi" value="<?= $lastSkripsi['id'] ?>">

                            <?php endif; ?>

                            <input type="hidden" id="npm" name="npm" value="<?= $dataAkun['npm'] ?>">

                            <div class="col-lg-12">

                                <div class="form-group">

                                    <label for="judul">Judul</label>

                                    <?php if ($lastSkripsi != null) : ?>

                                        <textarea class="form-control" id="judul" name="judul" rows="2" disabled><?= $lastSkripsi['judul'] ?></textarea>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="col-lg-12">

                                <div class="form-group">

                                    <label for="bidang">Bidang</label>

                                    <?php if ($lastSkripsi != null) : ?>

                                        <input type="text" class="form-control" id="bidang" name="bidang" value="<?= $lastSkripsi   ['nama_bidang'] ?>" disabled>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="col-lg-6">

                                <div class="form-group">

                                    <label for="sifat">Sifat</label>

                                    <?php if ($lastSkripsi != null) : ?>

                                        <input type="text" class="form-control" id="sifat" name="sifat" value="<?= $lastSkripsi['sifat'] ?>" disabled>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="col-lg-6">

                                <div class="form-group">

                                    <label for="sumber">Sumber</label>

                                    <?php if ($lastSkripsi != null) : ?>

                                        <input type="text" class="form-control" id="sumber" name="sumber" value="<?= $lastSkripsi['sumber'] ?>" disabled>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="col-lg-12 mb-3">

                                <label class="mb-0">Draft Final Skripsi</label>

                                <small class="text-muted d-block">Unggah Draft Final Skripsi dalam format PDF dengan ukuran maksimal 10 MB</small>

                                <div class="input-group">

                                    <div class="custom-file">

                                        <input type="file" class="custom-file-input" id="file_draft_final" name="file_draft_final">

                                        <label class="custom-file-label" for="file_draft_final">Pilih File Draft Final Skripsi</label>

                                    </div>

                                </div>

                            </div>

                            <div class="col-lg-12 mb-3">

                                <label class="mb-0">Formulir Bimbingan Skripsi</label>

                                <small class="text-muted d-block">Unggah Formulir Bimbingan dalam format PDF dengan ukuran maksimal 10 MB</small>

                                <div class="input-group">

                                    <div class="custom-file">

                                        <input type="file" class="custom-file-input" id="file_form_bimbingan" name="file_form_bimbingan">

                                        <label class="custom-file-label" for="file_form_bimbingan">Pilih File Formulir Bimbingan Skripsi</label>

                                    </div>

                                </div>

                            </div>

                            <div class="col-lg-12 mb-3">

                                <label class="mb-0">Form Persyaratan Sidang Skripsi</label>

                                <small class="text-muted d-block">Unggah Persyaratan Sidang Skripsi dalam format PDF dengan ukuran maksimal 10 MB</small>

                                <div class="input-group">

                                    <div class="custom-file">

                                        <input type="file" class="custom-file-input" id="file_persyaratan_sidang" name="file_persyaratan_sidang">

                                        <label class="custom-file-label" for="file_persyaratan_sidang">Pilih File Form Persyaratan Sidang Skripsi</label>

                                    </div>

                                </div>

                            </div>

                        </div>

<!-- DROPDOWN PANDUAN UNGGAH DOKUMEN SIDANG -->
<div class="col-lg-12 my-3">
    <div class="card border-left-info shadow-sm">

        <div class="card-header py-2" id="headingPanduanSidang">
            <button
                class="btn btn-link text-left text-decoration-none p-0"
                type="button"
                data-toggle="collapse"
                data-target="#panduanSidang"
                aria-expanded="false"
                aria-controls="panduanSidang"
            >
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Panduan Unggah Dokumen Sidang Tugas Akhir</strong>
                <i class="fas fa-chevron-down ml-2"></i>
            </button>
        </div>

        <div
            id="panduanSidang"
            class="collapse"
            aria-labelledby="headingPanduanSidang"
        >
            <div class="card-body">

                <p class="mb-2">
                    Sebelum mengunggah dokumen, pastikan file yang
                    disiapkan telah memenuhi ketentuan berikut:
                </p>

                <p class="font-weight-bold mb-1">
                    1. Draft Final Skripsi
                </p>
                <ul class="mb-3 pl-4">
                    <li>Dokumen harus dalam format PDF.</li>
                    <li>File dapat dibuat langsung dari Microsoft Word menggunakan fitur Save as PDF atau Export to PDF.</li>
                    <li>Pastikan seluruh isi dokumen, tabel, dan gambar terlihat jelas serta dapat dibaca.</li>
                    <li>Ukuran file maksimal 10 MB.</li>
                </ul>

                <p class="font-weight-bold mb-1">
                    2. Formulir Bimbingan Skripsi
                </p>
                <ul class="mb-3 pl-4">
                    <li>Pastikan seluruh riwayat bimbingan yang diperlukan sudah tercantum.</li>
                    <li>Jika formulir berbentuk fisik, pindai seluruh halaman menggunakan scanner atau aplikasi pemindai di HP.</li>
                    <li>Simpan hasil pemindaian dalam format PDF.</li>
                    <li>Pastikan tulisan dan tanda tangan terlihat jelas serta tidak terpotong.</li>
                    <li>Ukuran file maksimal 10 MB.</li>
                </ul>

                <p class="font-weight-bold mb-1">
                    3. Form Persyaratan Sidang Skripsi
                </p>
                <ul class="mb-3 pl-4">
                    <li>Pastikan formulir telah diisi sesuai ketentuan yang berlaku.</li>
                    <li>Jika dokumen berbentuk fisik, pindai menggunakan scanner atau aplikasi pemindai di HP.</li>
                    <li>Simpan hasil pemindaian dalam format PDF.</li>
                    <li>Pastikan seluruh informasi dan tanda tangan terlihat jelas serta tidak terpotong.</li>
                    <li>Ukuran file maksimal 10 MB.</li>
                </ul>

                <div class="alert alert-info mb-0">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Periksa kembali seluruh dokumen sebelum menekan tombol
                    <strong>Simpan</strong>. Pastikan file yang diunggah
                    sudah benar dan dapat dibuka.
                </div>

            </div>
        </div>

    </div>
</div>


                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>

                        <button type="submit" class="btn btn-primary">Simpan</button>

                    </div>

                </form>

            </div>

        </div>

    </div>

<?= $this->endSection(); ?>



<?= $this->section("scripts"); ?>

    <script src="<?= base_url("assets/js/mahasiswa/pengajuanSidangSkripsi.js");?>"></script>

<?= $this->endSection(); ?>