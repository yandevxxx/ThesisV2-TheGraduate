<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Semester</h1>
</div>

<?php
use App\Models\SemesterGraduateModel;

$flashMessage = session()->getFlashdata("message");
$flashError   = session()->getFlashdata("error");
$flashSuccess = session()->getFlashdata("success");
?>

<?php if ($flashMessage) : ?>
<?php
    if (is_array($flashMessage)) {
        $msgIcon  = $flashMessage['icon'] ?? 'info';
        $msgTitle = $flashMessage['title'] ?? '';
        $msgText  = $flashMessage['text'] ?? '';
    } else {
        $msgIcon  = 'info';
        $msgTitle = '';
        $msgText  = (string) $flashMessage;
    }
    $alertClass = match ($msgIcon) {
        'success' => 'success',
        'warning' => 'warning',
        'info'    => 'info',
        default   => 'danger',
    };
?>
<div class="alert alert-<?= $alertClass ?> alert-dismissible fade show" role="alert">
    <?php if ($msgTitle !== '') : ?><strong><?= esc($msgTitle) ?></strong> <?php endif; ?>
    <?= esc($msgText) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php elseif ($flashError) : ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Gagal!</strong> <?= esc($flashError) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php elseif ($flashSuccess) : ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Berhasil!</strong> <?= esc($flashSuccess) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Form Semester</h6>
        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#tambahSemester">
            <i class="fas fa-plus fa-fw mr-1"></i> Tambah Semester
        </button>
    </div>
    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="tableSemester" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 8%;">No</th>
                        <th style="width: 47%;">Semester</th>
                        <th style="width: 25%;">Status</th>
                        <th style="width: 20%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $dataList = $semester ?? $daftarSemester ?? [];
                    if (empty($dataList)): 
                    ?>
                    <tr class="text-center">
                        <td colspan="4" class="text-gray-500">Tidak ada data semester.</td>
                    </tr>
                    <?php else: ?>
                    <?php 
                    $startNo = isset($currentPage, $perPage) ? ((($currentPage - 1) * $perPage) + 1) : 1;
                    foreach ($dataList as $s): 
                        $isAktif     = ($s['status'] === 'AKTIF');
                        $labelFormat = SemesterGraduateModel::formatNama($s['nama'], $s['kode']);

                        // Ekstrak tahun dan semester dari kode untuk edit
                        $tahunMulai = '';
                        $semNumber  = '1';
                        if (preg_match('/^(\d{4})-(\d+)$/', (string) $s['kode'], $km)) {
                            $tahunMulai = $km[1];
                            $semNumber  = $km[2];
                        }
                    ?>
                    <tr class="text-center align-middle">
                        <td><?= $startNo++ ?></td>
                        <td class="text-left font-weight-bold text-gray-800">
                            <?= esc($labelFormat) ?>
                            <span class="badge badge-light border text-muted ml-1"><?= esc($s['kode']) ?></span>
                        </td>
                        <td>
                            <?php if ($isAktif): ?>
                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-secondary px-2 py-1">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-warning btn-sm btnUbahSemester" 
                                data-kode="<?= esc($s['kode']) ?>" 
                                data-tahun="<?= esc($tahunMulai) ?>" 
                                data-semester="<?= esc($semNumber) ?>"
                                data-toggle="tooltip" data-placement="top" title="Ubah Semester">
                                <i class="fas fa-edit fa-fw"></i>
                            </button>

                            <form action="<?= base_url("graduate/semesterDelete/" . esc($s['kode'], 'url')) ?>" method="post" class="d-inline formHapusSemester">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Hapus">
                                    <i class="fas fa-trash fa-fw"></i>
                                </button>
                            </form>

                            <?php if (! $isAktif) : ?>
                                <form action="<?= base_url("graduate/semesterActivate/" . esc($s['kode'], 'url')) ?>" method="post" class="d-inline formAktivasiSemester">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-primary btn-sm" data-toggle="tooltip" data-placement="top" title="Aktifkan Semester">
                                        <i class="fas fa-check fa-fw"></i> Aktifkan
                                    </button>
                                </form>
                            <?php else: ?>
                                <form action="<?= base_url("graduate/semesterNonactivate/" . esc($s['kode'], 'url')) ?>" method="post" class="d-inline formNonaktivasiSemester">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-secondary btn-sm" data-toggle="tooltip" data-placement="top" title="Nonaktifkan Semester">
                                        <i class="fas fa-times fa-fw"></i> Nonaktifkan
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>    
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginasi Server-side khas The Graduate -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
            <div class="text-muted small">
                Menampilkan <?= count($dataList) ?> dari <?= (int) ($totalRows ?? count($dataList)) ?> data semester
            </div>
            <div>
                <?php if (isset($pager)) : ?>
                    <?= $pager->links('semester', 'bootstrap_pagination') ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Modal Tambah Semester -->
<div class="modal fade" id="tambahSemester" tabindex="-1" role="dialog" aria-labelledby="tambahSemesterLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="tambahSemesterLabel"><i class="fas fa-plus mr-2"></i> Tambah Semester</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formTambahSemester" method="post" action="<?= base_url("graduate/semesterStore") ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tahunAkademik" class="font-weight-bold">Tahun Akademik</label>
                        <input type="text" class="form-control" id="tahunAkademik" name="tahunAkademik" placeholder="contoh: 2025" required>
                        <small class="form-text text-muted">Masukkan tahun awal periode akademik (antara 2000 - 2100).</small>
                    </div>

                    <div class="form-group mb-0">
                        <label for="semester" class="font-weight-bold">Semester</label>
                        <select name="semester" id="semester" class="form-control" required>
                            <option selected disabled value="">- Pilih Semester -</option>
                            <option value="1">Ganjil</option>
                            <option value="2">Genap</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save fa-fw mr-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Semester -->
<div class="modal fade" id="modalEditSemester" tabindex="-1" role="dialog" aria-labelledby="modalEditSemesterLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title font-weight-bold" id="modalEditSemesterLabel"><i class="fas fa-edit mr-2"></i> Edit Semester</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formEditSemester" method="post" action="<?= base_url("graduate/semesterUpdate") ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="kode" id="editKodeLama" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editTahunAkademik" class="font-weight-bold">Tahun Akademik</label>
                        <input type="text" class="form-control" id="editTahunAkademik" name="tahunAkademik" placeholder="contoh: 2025" required>
                        <small class="form-text text-muted">Tahun awal periode akademik (antara 2000 - 2100).</small>
                    </div>

                    <div class="form-group mb-0">
                        <label for="editSemester" class="font-weight-bold">Semester</label>
                        <select name="semester" id="editSemester" class="form-control" required>
                            <option value="1">Ganjil</option>
                            <option value="2">Genap</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-save fa-fw mr-1"></i> Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>
<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip();

    // Event tombol Ubah Semester
    $(document).on("click", ".btnUbahSemester", function () {
        var kode     = $(this).data("kode");
        var tahun    = $(this).data("tahun");
        var semester = $(this).data("semester");

        $("#editKodeLama").val(kode);
        $("#editTahunAkademik").val(tahun);
        $("#editSemester").val(semester);
        $("#modalEditSemester").modal("show");
    });

    // SweetAlert konfirmasi Hapus
    $(document).on("submit", ".formHapusSemester", function (e) {
        e.preventDefault();
        var form = this;

        Swal.fire({
            title: "Apakah anda ingin menghapus semester ini?",
            text: "Semester yang sudah dihapus tidak dapat dikembalikan",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#e74a3b",
            cancelButtonColor: "#858796",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // SweetAlert konfirmasi Aktivasi
    $(document).on("submit", ".formAktivasiSemester", function (e) {
        e.preventDefault();
        var form = this;

        Swal.fire({
            title: "Apakah anda ingin mengaktifkan semester ini?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#4e73df",
            cancelButtonColor: "#858796",
            confirmButtonText: "Ya, Aktifkan!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // SweetAlert konfirmasi Nonaktivasi
    $(document).on("submit", ".formNonaktivasiSemester", function (e) {
        e.preventDefault();
        var form = this;

        Swal.fire({
            title: "Apakah anda ingin menonaktifkan semester ini?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#858796",
            cancelButtonColor: "#d1d3e2",
            confirmButtonText: "Ya, Nonaktifkan!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
<?= $this->endSection(); ?>

