<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<h1 class="h3 mb-2 text-gray-800"><?= $title ?></h1>

<?php if (session()->getFlashdata("message")) : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= session()->getFlashdata("message")["icon"]; ?></p>
        <p id="title" hidden><?= session()->getFlashdata("message")["title"]; ?></p>
        <p id="text" hidden><?= session()->getFlashdata("message")["text"]; ?></p>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <?= $title ?>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-start mb-3">
            <button class="btn btn-primary" data-toggle="modal" data-target="#tambahSemester">Tambahkan Semester</button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" id="tableSemester" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $dataList = $semester ?? $daftarSemester ?? [];
                    foreach ($dataList as $s): 
                    ?>
                        <tr>
                            <td></td>
                            <td class="nama-semester"><?= esc($s['nama']) ?></td>
                            <td class="status-semester"><?= esc($s['status']) ?></td>
                            <td>
                                <form action="<?= base_url("graduate/semesterDelete/" . esc($s['kode'], 'url')) ?>" method="post" class="d-inline formHapusSemester">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Hapus"><i class="fa fa-trash"></i></button>
                                </form>

                                <?php if ($s['status'] == 'NON-AKTIF') : ?>
                                    <form action="<?= base_url("graduate/semesterActivate/" . esc($s['kode'], 'url')) ?>" method="post" class="d-inline formAktivasiSemester">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="Aktifkan Semester"><i class="fas fa-check"></i></button>
                                    </form>
                                <?php elseif ($s['status'] == 'AKTIF'): ?>
                                    <form action="<?= base_url("graduate/semesterNonactivate/" . esc($s['kode'], 'url')) ?>" method="post" class="d-inline formNonaktivasiSemester">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-secondary" data-toggle="tooltip" data-placement="top" title="Nonaktifkan Semester"><i class="fas fa-times"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>    
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- modal tambah semester -->
<div class="modal fade" id="tambahSemester" tabindex="-1" role="dialog" aria-labelledby="tambahSemesterLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tambahSemesterLabel">Tambah Semester</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formTambahSemester" method="post" action="<?= base_url("graduate/semesterStore") ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tahunAkademik">Tahun Akademik</label>
                        <input type="text" class="form-control" id="tahunAkademik" name="tahunAkademik" placeholder="contoh: 2025">
                    </div>

                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select name="semester" id="semester" class="form-control">
                            <option selected disabled> - Pilih Semester - </option>
                            <option value="1">Ganjil</option>
                            <option value="2">Genap</option>
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

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>
<script>
$(function () {
    $("#formTambahSemester").validate({
        rules: {
            tahunAkademik: {
                required: true,
                digits: true,
                min: 2000,
                max: 2100,
            },
            semester: {
                required: true,
            },
        },
        errorClass: "text-danger",
        errorElement: "small",
        submitHandler: function (form) {
            form.submit();
        },
    });

    $(document).on("submit", ".formHapusSemester", function (e) {
        e.preventDefault();
        var form = this;

        Swal.fire({
            title: "Apakah anda ingin menghapus semester ini?",
            text: "Semester yang sudah dihapus tidak dapat dikembalikan",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    $(document).on("submit", ".formAktivasiSemester", function (e) {
        e.preventDefault();
        var form = this;

        Swal.fire({
            title: "Apakah anda ingin mengaktifkan semester ini?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, Aktfikan!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    $(document).on("submit", ".formNonaktivasiSemester", function (e) {
        e.preventDefault();
        var form = this;

        Swal.fire({
            title: "Apakah anda ingin menonaktifkan semester ini?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, Nonaktifkan!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // untuk sweetalert flashdata
    if ($("#flashdata").data("open") == true) {
        Swal.fire(
            $("#flashdata #title").text(),
            $("#flashdata #text").text(),
            $("#flashdata #icon").text().trim()
        );
    }

    let dtable = $("#tableSemester").DataTable({
        columnDefs: [
            {
                targets: [0, 2, 3],
                className: "text-center",
            },
            {
                targets: 3,
                searchable: false,
                orderable: false,
            },
            {
                targets: "_all",
                className: "align-middle",
            },
        ],
    });

    // auto generate number
    dtable
        .on("order.dt search.dt", function () {
            let i = 1;
            dtable
                .cells(null, 0, { search: "applied", order: "applied" })
                .every(function (cell) {
                    this.data(i++);
                });
        })
        .draw();
});
</script>
<?= $this->endSection(); ?>
