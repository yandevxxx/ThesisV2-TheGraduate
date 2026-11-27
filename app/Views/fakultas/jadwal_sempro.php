
<?= $this->extend("layout/template"); ?>

<?= $this->section("content"); ?>

<h1 class="h3 mb-2 text-gray-800">
    <?= esc($title) ?>
</h1>

<p class="mb-4 text-gray-600">
    Halaman ini digunakan untuk melihat jadwal seminar proposal mahasiswa di lingkungan fakultas.
</p>

<!-- Filter Semester -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('fakultas/jadwal-sempro') ?>">
            <div class="form-row align-items-end">
                <div class="col-md-4">
                    <label for="semester">Semester</label>
                    <select name="semester" id="semester" class="form-control">
                        <?php foreach ($semesterList as $semester): ?>
                            <option
                                value="<?= esc($semester['kode']) ?>"
                                <?= ($currentSemester && $currentSemester['kode'] == $semester['kode']) ? 'selected' : '' ?>>
                                <?= esc($semester['nama'] ?? $semester['kode']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-auto mt-3 mt-md-0">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tampilkan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Jadwal Sempro -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            Daftar Jadwal Seminar Proposal
        </h6>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>No</th>
                        <th>NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Program Studi</th>
                        <th>Judul Proposal</th>
                        <th>Tanggal</th>
                        <th>Ruangan</th>
                        <th>Dosen Penguji 1</th>
                        <th>Dosen Penguji 2</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($jadwalSempro)): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($jadwalSempro as $jadwal): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= esc($jadwal['npm'] ?? '-') ?></td>
                                <td><?= esc($jadwal['nama_mahasiswa'] ?? '-') ?></td>
                                <td><?= esc($jadwal['nama_prodi'] ?? '-') ?></td>
                                <td><?= esc($jadwal['judul'] ?? '-') ?></td>
                                <td>
                                    <?= !empty($jadwal['tanggal'])
                                        ? date('d-m-Y H:i', strtotime($jadwal['tanggal']))
                                        : '-' ?>
                                </td>
                                <td><?= esc($jadwal['ruangan'] ?? '-') ?></td>
                                <td><?= esc($jadwal['nama_penguji1'] ?? '-') ?></td>
                                <td><?= esc($jadwal['nama_penguji2'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">
                                Belum ada jadwal seminar proposal pada semester ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>