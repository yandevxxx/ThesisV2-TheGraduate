<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Semester</h1>
</div>

<?php if (session()->getFlashdata("message")) : ?>
<?php $msg = session()->getFlashdata("message"); ?>
<div class="alert alert-<?= ($msg['icon'] === 'success') ? 'success' : (($msg['icon'] === 'warning') ? 'warning' : (($msg['icon'] === 'info') ? 'info' : 'danger')) ?> alert-dismissible fade show" role="alert">
    <strong><?= esc($msg['title'] ?? '') ?></strong> <?= esc($msg['text'] ?? '') ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Semester (<?= count($daftarSemester ?? []) ?>)</h6>
        <button type="button" class="btn btn-primary btn-sm my-1" data-toggle="modal" data-target="#modalTambahSemester">
            <i class="fas fa-plus fa-fw"></i> Tambah Semester
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 15%;">Kode</th>
                        <th>Semester</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 25%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarSemester)): ?>
                    <tr class="text-center">
                        <td colspan="5" class="text-gray-500">Tidak ada data semester.</td>
                    </tr>
                    <?php else: ?>
                    <?php $no = 1; foreach ($daftarSemester as $s): ?>
                    <tr class="text-center">
                        <td><?= $no++ ?></td>
                        <td><code><?= esc($s['kode']) ?></code></td>
                        <td class="text-left font-weight-bold"><?= esc($s['nama']) ?></td>
                        <td>
                            <?php if ($s['status'] === 'AKTIF'): ?>
                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle fa-fw"></i> Aktif</span>
                            <?php else: ?>
                            <span class="badge badge-secondary px-2 py-1">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if ($s['status'] !== 'AKTIF'): ?>
                            <form action="<?= base_url('graduate/semesterActivate/' . esc($s['kode'], 'url')) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-success btn-sm" title="Jadikan semester aktif" onclick="return confirm('Aktifkan semester ini? Semester lain otomatis menjadi nonaktif.');">
                                    <i class="fas fa-power-off fa-fw"></i> Aktifkan
                                </button>
                            </form>
                            <?php else: ?>
                            <button type="button" class="btn btn-outline-success btn-sm" disabled title="Semester ini sedang aktif">
                                <i class="fas fa-star fa-fw"></i> Aktif
                            </button>
                            <?php endif; ?>

                            <?php
                            // Ekstrak tahun dan semester dari kode/nama untuk form edit
                            $kodeParts = explode('-', $s['kode']);
                            $tahunAwal = $kodeParts[0] ?? '';
                            $semKode   = $kodeParts[1] ?? '';
                            $tahunFormat = $tahunAwal !== '' ? ($tahunAwal . '/' . ((int)$tahunAwal + 1)) : '';
                            $semNama   = ($semKode === '1' || stripos($s['nama'], 'Ganjil') !== false) ? 'Ganjil' : 'Genap';
                            ?>
                            <button type="button" class="btn btn-primary btn-sm ubah-semester" 
                                data-kode="<?= esc($s['kode']) ?>" 
                                data-nama="<?= esc($s['nama']) ?>" 
                                data-tahun="<?= esc($tahunFormat) ?>" 
                                data-semester="<?= esc($semNama) ?>" 
                                title="Ubah Semester">
                                <i class="fas fa-pencil-alt fa-fw"></i> Ubah
                            </button>

                            <?php if ($s['status'] !== 'AKTIF'): ?>
                            <button type="button" class="btn btn-danger btn-sm hapus-semester" 
                                data-kode="<?= esc($s['kode']) ?>" 
                                data-nama="<?= esc($s['nama']) ?>" 
                                title="Hapus Semester">
                                <i class="fas fa-trash fa-fw"></i> Hapus
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
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
                <h5 class="modal-title" id="modalTambahSemesterLabel"><i class="fas fa-plus-circle text-primary mr-1"></i> Tambah Semester</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/semesterStore') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tahunAkademikTambah" class="font-weight-bold">Tahun Akademik <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tahunAkademikTambah" name="tahunAkademik" placeholder="contoh: 2025/2026 atau 2025" required>
                        <small class="form-text text-muted">Ketik tahun akademik berjalan, misalnya: 2025/2026.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label for="semesterTambah" class="font-weight-bold">Semester <span class="text-danger">*</span></label>
                        <select class="form-control" id="semesterTambah" name="semester" required>
                            <option value="" selected disabled>- Pilih Semester -</option>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save fa-fw"></i> Simpan</button>
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
                <h5 class="modal-title" id="modalEditSemesterLabel"><i class="fas fa-edit text-primary mr-1"></i> Edit Semester</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/semesterUpdate') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="kode" id="kodeSemesterEdit">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Kode Semester Saat Ini</label>
                        <input type="text" class="form-control bg-light" id="previewKodeEdit" readonly>
                    </div>
                    <div class="form-group">
                        <label for="tahunAkademikEdit" class="font-weight-bold">Tahun Akademik <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tahunAkademikEdit" name="tahunAkademik" placeholder="contoh: 2025/2026" required>
                    </div>
                    <div class="form-group mb-0">
                        <label for="semesterEdit" class="font-weight-bold">Semester <span class="text-danger">*</span></label>
                        <select class="form-control" id="semesterEdit" name="semester" required>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save fa-fw"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Semester -->
<div class="modal fade" id="modalHapusSemester" tabindex="-1" role="dialog" aria-labelledby="modalHapusSemesterLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="modalHapusSemesterLabel"><i class="fas fa-trash-alt mr-1"></i> Hapus Semester</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/semesterDelete') ?>" method="post" id="formHapusSemester">
                <?= csrf_field() ?>
                <input type="hidden" name="kode" id="kodeSemesterHapus">
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus data semester berikut?</p>
                    <p class="font-weight-bold mb-1" id="hapusNamaSemester"></p>
                    <p class="text-muted small mb-0">Kode: <code id="hapusKodeSemester"></code></p>
                    <div class="alert alert-warning mt-3 mb-0 small">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Semester yang sudah memiliki relasi data (skripsi, proposal, dll.) atau sedang aktif tidak dapat dihapus.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash fa-fw"></i> Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

    <script>
        $(document).on('click', '.ubah-semester', function () {
            var kode = $(this).data('kode');
            var tahun = $(this).data('tahun');
            var sem = $(this).data('semester');

            $('#kodeSemesterEdit').val(kode);
            $('#previewKodeEdit').val(kode);
            $('#tahunAkademikEdit').val(tahun);
            $('#semesterEdit').val(sem);
            $('#modalEditSemester').modal('show');
        });

        $(document).on('click', '.hapus-semester', function () {
            var kode = $(this).data('kode');
            var nama = $(this).data('nama');

            $('#kodeSemesterHapus').val(kode);
            $('#hapusKodeSemester').text(kode);
            $('#hapusNamaSemester').text(nama);
            $('#modalHapusSemester').modal('show');
        });
    </script>

<?= $this->endSection(); ?>
