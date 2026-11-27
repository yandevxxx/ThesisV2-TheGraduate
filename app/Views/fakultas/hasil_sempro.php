
<?= $this->extend("layout/template"); ?>

<?= $this->section("content"); ?>

<h1 class="h3 mb-2 text-gray-800">
    <?= esc($title) ?>
</h1>

<p class="mb-4 text-gray-600">
    Halaman ini digunakan untuk melihat hasil seminar proposal mahasiswa.
</p>

<!-- Filter Semester -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            Filter Semester
        </h6>
    </div>

    <div class="card-body">
        <form method="get" action="<?= base_url('fakultas/hasil-sempro') ?>">
            <div class="form-row align-items-end">
                <div class="col-md-5">
                    <label for="semester">Semester</label>
                    <select name="semester" id="semester" class="form-control">
                        <?php foreach ($semesterList as $semester): ?>
                            <option
                                value="<?= esc($semester['kode']) ?>"
                                <?= ($currentSemester['kode'] ?? '') == $semester['kode'] ? 'selected' : '' ?>>
                                <?= esc($semester['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 mt-2">
                    <button type="submit" class="btn btn-primary">
                        Tampilkan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Daftar Hasil Sempro -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            Daftar Hasil Seminar Proposal
        </h6>
    </div>

    <div class="card-body">

        <?php if (empty($currentSemester)): ?>

            <div class="alert alert-info">
                Belum ada semester yang tersedia.
            </div>

        <?php elseif (empty($hasilSempro)): ?>

            <div class="alert alert-info">
                Belum ada hasil seminar proposal pada semester ini.
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped"
                       width="100%"
                       cellspacing="0">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NPM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Program Studi</th>
                            <th>Judul Proposal</th>
                            <th>Jenis Tugas Akhir</th>
                            <th>Tanggal Sempro</th>
                            <th>Ruangan</th>
                            <th>Penguji 1</th>
                            <th>Penguji 2</th>
                            <th>Hasil Sempro</th>
                            <th>Catatan Penguji 1</th>
                            <th>Catatan Penguji 2</th>
                            <th>Proposal</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $no = 1; ?>

                        <?php foreach ($hasilSempro as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>

                                <td><?= esc($row['npm'] ?? '-') ?></td>

                                <td><?= esc($row['nama_mahasiswa'] ?? '-') ?></td>

                                <td><?= esc($row['nama_prodi'] ?? '-') ?></td>

                                <td><?= esc($row['judul'] ?? '-') ?></td>

                                <td><?= esc($row['jenis_skripsi'] ?? '-') ?></td>

                                <td>
                                    <?php if (!empty($row['tanggal'])): ?>
                                        <?= esc(date('d-m-Y H:i', strtotime($row['tanggal']))) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td><?= esc($row['ruangan'] ?? '-') ?></td>

                                <td><?= esc($row['nama_penguji1'] ?? '-') ?></td>

                                <td><?= esc($row['nama_penguji2'] ?? '-') ?></td>

                                <td>
                                    <?= (!empty($row['status']) && $row['status'] !== 'TERTUNDA')
                                        ? esc($row['status'])
                                        : '-' ?>
                                </td>

                                <td>
                                    <?= !empty($row['komentar'])
                                        ? nl2br(esc($row['komentar']))
                                        : '-' ?>
                                </td>

                                <td>
                                    <?= !empty($row['komentar_2'])
                                        ? nl2br(esc($row['komentar_2']))
                                        : '-' ?>
                                </td>

                                <td class="text-center">
                                    <?php if (!empty($row['file_proposal'])): ?>
                                        <form action="<?= base_url('mahasiswa/downloadProposal/' . $row['id_proposal']) ?>" method="post">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-primary btn-sm" type="submit"
                                                    title="Unduh Proposal">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled
                                                title="File proposal tidak tersedia">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>
</div>

<?= $this->endSection(); ?>
