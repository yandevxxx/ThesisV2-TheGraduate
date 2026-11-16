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

    <div class="card shadow mb-4">

        <div class="card-header py-3">

            <?= $title ?>

        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-lg-5"></div>

                <div class="col-lg-7 d-flex justify-content-end align-items-center">

                    <form class="form-inline" action="<?= base_url("TenagaKependidikan/pengajuanSkripsi") ?>" method="get">

                        <div class="form-group mr-2">

                            <label for="dari" class="form-control-label mr-1">Dari</label>

                            <input type="date" id="dari" name="dari" class="form-control" placeholder="dari">

                        </div>

                        <div class="form-group mr-2">

                            <label for="hingga" class="form-control-label mr-1">Hingga</label>

                            <input type="date" class="form-control" id="hingga" name="hingga" placeholder="hingga">

                        </div>

                        <button type="submit" class="btn btn-primary" data-toggle="tooltip" title="Filter Table" id="filterTable"><i class="fas fa-filter"></i></button>

                    </form>

                </div>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered" id="pengajuanSkripsi" width="100%" cellspacing="0">

                    <thead>
    <thead>
    <tr>
        <th>No</th>
        <th>NPM</th>
        <th>Nama</th>
        <th>Program Studi</th>
        <th>SKS Lulus</th>
        <th>Pemb. Akademik</th>
        <th>Mata Kuliah Sudah Lulus</th>
        <th>Mata Kuliah Wajib Belum Lulus</th>
        <th>Mata Kuliah Sedang Berjalan</th>
        <th>Jenis Tugas Akhir</th>
        <th>Tanggal Pengajuan</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>
</thead>

                    <tbody>

    <?php
    $counter = 1;

    foreach ($pengajuanPenulisanSkripsi as $pps) :
    ?>

        <tr>

            <td><?= $counter; ?></td>

            <td class="npm"><?= $pps['npm'] ?></td>

            <td><?= $pps['nama_mahasiswa'] ?></td>

            <td
                data-toggle="tooltip"
                data-placement="top"
                title="<?= $pps['nama_prodi'] ?>"
            >
                <?= $pps['inisial_prodi'] ?>
            </td>

            <td><?= $pps['sks_lulus'] ?></td>

            <td
                data-toggle="tooltip"
                data-placement="top"
                title="<?= $pps['nama_pembimbing_akademik'] ?>"
            >
                <?= $pps['inisial_pembimbing_akademik'] ?>
            </td>

            <!-- MATA KULIAH SUDAH LULUS -->
        <td>
            <?php if (!empty($pps['mk_sudah_lulus_list'])): ?>

                <?php
                    $idCollapse = 'mkLulus' . $counter;
                    $mkTampil = array_slice($pps['mk_sudah_lulus_list'], 0, 2);
                    $mkLainnya = array_slice($pps['mk_sudah_lulus_list'], 2);
                ?>

                <ul class="mb-0 pl-3">
                    <?php foreach ($mkTampil as $mk): ?>
                        <li><?= esc($mk) ?></li>
                    <?php endforeach; ?>
                </ul>

                <?php if (!empty($mkLainnya)): ?>

                    <div class="collapse" id="<?= $idCollapse ?>">
                        <ul class="mb-0 pl-3">
                            <?php foreach ($mkLainnya as $mk): ?>
                                <li><?= esc($mk) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <a
                        class="small text-primary"
                        data-toggle="collapse"
                        href="#<?= $idCollapse ?>"
                        role="button"
                        aria-expanded="false"
                        aria-controls="<?= $idCollapse ?>"
                    >
                        Lihat selengkapnya
            </a>

        <?php endif; ?>

    <?php else: ?>
        -
    <?php endif; ?>
</td>

<!-- MATA KULIAH WAJIB BELUM LULUS -->
<td>
    <?php if (!empty($pps['mk_wajib_belum_lulus_list'])): ?>
        <ul class="mb-0 pl-3">
            <?php foreach ($pps['mk_wajib_belum_lulus_list'] as $mk): ?>
                <li><?= esc($mk) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        -
    <?php endif; ?>
</td>

<!-- MATA KULIAH SEDANG BERJALAN -->
<td>
    <?php if (!empty($pps['mk_sedang_berjalan_list'])): ?>
        <ul class="mb-0 pl-3">
            <?php foreach ($pps['mk_sedang_berjalan_list'] as $mk): ?>
                <li><?= esc($mk) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        -
    <?php endif; ?>
</td>

            <!-- JENIS TUGAS AKHIR -->
            <td><?= esc($pps['nama_jenis_tugas_akhir'] ?? '-') ?></td>

            <td>
                <?= date_format(
                    date_create($pps['tanggal_pengajuan_skripsi']),
                    "d-m-Y"
                ) ?>
            </td>

            <td class="status">
                <?= ($pps['status_persetujuan_skripsi'] == null)
                    ? "-"
                    : $pps['status_persetujuan_skripsi'] ?>
            </td>

            <td class="aksi">

                <a
                    role="button"
                    class="btn btn-primary"
                    href="<?= base_url("TenagaKependidikan/detailPengajuanSkripsi/" . $pps['npm']) ?>"
                    data-toggle="tooltip"
                    title="Detail"
                >
                    <i class="fas fa-info-circle"></i>
                </a>

            </td>

        </tr>

        <?php $counter++; ?>

    <?php endforeach; ?>

</tbody>

                </table>

            </div>

        </div>

    </div>

<?= $this->endSection(); ?>



<?= $this->section("scripts"); ?>

    <script src="<?= base_url("assets/js/tenagakependidikan/pengajuanSkripsi.js");?>"></script>

<?= $this->endSection(); ?>