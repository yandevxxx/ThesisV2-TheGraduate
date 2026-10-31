<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari database (model semester) menyusul.
 *
 * Nilai status mengikuti konvensi tabel semester yang sudah ada:
 *   'AKTIF' | 'NON-AKTIF'
 */
$daftarSemester = [
    ['nama' => 'Ganjil 2025/2026', 'status' => 'AKTIF'],
    ['nama' => 'Genap 2025/2026', 'status' => 'NON-AKTIF'],
    ['nama' => 'Ganjil 2024/2025', 'status' => 'NON-AKTIF'],
    ['nama' => 'Genap 2024/2025', 'status' => 'NON-AKTIF'],
    ['nama' => 'Ganjil 2023/2024', 'status' => 'NON-AKTIF'],
    ['nama' => 'Genap 2023/2024', 'status' => 'NON-AKTIF'],
];
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Semester</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Form Semester</h6>
    </div>
    <div class="card-body">

        <!-- Tombol Tambah Semester (di atas tabel, rata kanan) -->
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambahSemester">
                <i class="fas fa-plus fa-fw"></i> Tambah Semester
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr class="text-center">
                        <th style="width: 8%;">No</th>
                        <th style="width: 45%;">Semester</th>
                        <th style="width: 25%;">Status</th>
                        <th style="width: 22%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarSemester)): ?>
                    <tr class="text-center">
                        <td colspan="4" class="text-gray-500">Tidak ada data semester.</td>
                    </tr>
                    <?php endif; ?>

                    <?php $no = 1; foreach ($daftarSemester as $s): ?>
                    <tr class="text-center">
                        <td><?= $no++ ?></td>
                        <td><?= esc($s['nama']) ?></td>
                        <td>
                            <?php if ($s['status'] === 'AKTIF'): ?>
                            <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm ubah-semester" data-toggle="tooltip" data-placement="top" title="Ubah" data-semester="<?= esc($s['nama']) ?>"><i class="fas fa-pencil-alt"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Semester -->
<div class="modal fade" id="modalTambahSemester" tabindex="-1" role="dialog" aria-labelledby="modalTambahSemesterLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahSemesterLabel">Tambah Semester</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- Action disesuaikan saat integrasi controller -->
            <form action="<?= base_url('graduate/semester') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tahunAkademikTambah">Tahun Akademik</label>
                        <input type="text" class="form-control" id="tahunAkademikTambah" name="tahunAkademik" placeholder="contoh: 2025/2026">
                    </div>
                    <div class="form-group mb-0">
                        <label for="semesterTambah">Semester</label>
                        <select class="form-control" id="semesterTambah" name="semester">
                            <option selected disabled>- Pilih Semester -</option>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
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

<!-- Modal Edit Semester -->
<div class="modal fade" id="modalEditSemester" tabindex="-1" role="dialog" aria-labelledby="modalEditSemesterLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditSemesterLabel">Edit Semester</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- Action disesuaikan saat integrasi controller -->
            <form action="<?= base_url('graduate/semester') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tahunAkademikEdit">Tahun Akademik</label>
                        <input type="text" class="form-control" id="tahunAkademikEdit" name="tahunAkademik">
                    </div>
                    <div class="form-group mb-0">
                        <label for="semesterEdit">Semester</label>
                        <select class="form-control" id="semesterEdit" name="semester">
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
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

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

    <script>
        // Tombol Edit membuka modal dalam keadaan terisi (data dummy — sumber data menyusul)
        $(document).on('click', '.ubah-semester', function () {
            var semester = $(this).data('semester'); // "Ganjil 2025/2026"
            var bagian = semester.toString().split(' ');

            $('#tahunAkademikEdit').val(bagian[1]);
            $('#semesterEdit').val(bagian[0]);
            $('#modalEditSemester').modal('show');
        });
    </script>

<?= $this->endSection(); ?>
