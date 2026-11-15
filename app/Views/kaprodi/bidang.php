<?= $this->extend("layout/template"); ?>

<?= $this->section("content"); ?>

<h1 class="h3 mb-2 text-gray-800">
    <?= esc($title) ?>
</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <?= esc($title) ?>
    </div>

    <div class="card-body">

        <div class="d-flex justify-content-start mb-3">
            <button type="button"
                    class="btn btn-primary"
                    data-toggle="modal"
                    data-target="#tambahBidang">
                Tambahkan Bidang
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered"
                   id="tableBidang"
                   width="100%"
                   cellspacing="0">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Bidang</th>
                        <th>Inisial</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody id="dataBidang">
                    <tr>
                        <td colspan="4" class="text-center">
                            Memuat data bidang...
                        </td>
                    </tr>
                </tbody>

            </table>
        </div>

    </div>
</div>


<!-- MODAL TAMBAH BIDANG -->
<div class="modal fade"
     id="tambahBidang"
     tabindex="-1"
     role="dialog"
     aria-labelledby="tambahBidangLabel"
     aria-hidden="true">

    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="tambahBidangLabel">
                    Tambah Bidang
                </h5>

                <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formTambahBidang">
                <?= csrf_field() ?>

                <div class="modal-body">

                    <div class="form-group">
                        <label for="namaBidang">Nama Bidang</label>

                        <input type="text"
                               class="form-control"
                               id="namaBidang"
                               name="nama"
                               maxlength="150"
                               placeholder="Masukkan nama bidang"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="inisialBidang">Inisial</label>

                        <input type="text"
                               class="form-control"
                               id="inisialBidang"
                               name="inisial"
                               maxlength="20"
                               placeholder="Masukkan inisial bidang"
                               required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit"
                            class="btn btn-primary"
                            id="btnSimpan">
                        Simpan
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>


<!-- MODAL EDIT BIDANG -->
<div class="modal fade"
     id="editBidang"
     tabindex="-1"
     role="dialog"
     aria-labelledby="editBidangLabel"
     aria-hidden="true">

    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="editBidangLabel">
                    Edit Bidang
                </h5>

                <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formEditBidang">

                <input type="hidden" id="editIdBidang">

                <div class="modal-body">

                    <div class="form-group">
                        <label for="editNamaBidang">Nama Bidang</label>

                        <input type="text"
                               class="form-control"
                               id="editNamaBidang"
                               name="nama"
                               maxlength="150"
                               placeholder="Masukkan nama bidang"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="editInisialBidang">Inisial</label>

                        <input type="text"
                               class="form-control"
                               id="editInisialBidang"
                               name="inisial"
                               maxlength="20"
                               placeholder="Masukkan inisial bidang"
                               required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit"
                            class="btn btn-primary"
                            id="btnUpdate">
                        Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?= $this->endSection(); ?>


<!-- JAVASCRIPT -->
<?= $this->section("scripts"); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {

    const urlBidang = "<?= base_url('api/kaprodi/bidang') ?>";

    // Mengamankan teks sebelum ditampilkan
    function escapeHtml(value) {
        return $('<div>').text(value ?? '-').html();
    }


    // =========================================
    // MENGAMBIL DATA BIDANG DARI API GET
    // =========================================

    function loadBidang() {

        $.ajax({
            url: urlBidang,
            type: "GET",
            dataType: "JSON",

            success: function (response) {

                let html = '';

                if (
                    response.status === 'success' &&
                    response.data &&
                    response.data.length > 0
                ) {

                    response.data.forEach(function (bidang, index) {

    html += `
        <tr>
            <td>${index + 1}</td>
            <td>${escapeHtml(bidang.nama)}</td>
            <td>${escapeHtml(bidang.inisial)}</td>

            <td class="text-nowrap">

                <!-- Tombol Edit -->
                <button type="button"
                        class="btn btn-primary btn-sm btnEditBidang"
                        title="Edit"
                        data-id="${escapeHtml(bidang.id)}"
                        data-nama="${escapeHtml(bidang.nama)}"
                        data-inisial="${escapeHtml(bidang.inisial)}">
                    <i class="fas fa-edit"></i>
                </button>

                <!-- Tombol Hapus -->
                <button type="button"
                        class="btn btn-danger btn-sm btnHapusBidang"
                        title="Hapus"
                        data-id="${escapeHtml(bidang.id)}"
                        data-nama="${escapeHtml(bidang.nama)}">
                    <i class="fas fa-trash"></i>
                </button>

            </td>
        </tr>
    `;

});
                } else {

                    html = `
                        <tr>
                            <td colspan="4" class="text-center">
                                Belum ada data bidang.
                            </td>
                        </tr>
                    `;

                }

                $('#dataBidang').html(html);

            },

            error: function (xhr) {

                let pesan = 'Gagal mengambil data bidang.';

                if (xhr.status === 401) {
                    pesan = 'Silakan login sebagai Kaprodi.';
                } else if (xhr.status === 403) {
                    pesan = 'Akses ditolak atau prodi tidak ditemukan.';
                }

                $('#dataBidang').html(`
                    <tr>
                        <td colspan="4" class="text-center text-danger">
                            ${pesan}
                        </td>
                    </tr>
                `);

            }
        });

    }


    // =========================================
    // MENAMBAHKAN DATA BIDANG MELALUI API POST
    // =========================================

    $('#formTambahBidang').on('submit', function (e) {

        e.preventDefault();

        const tombol = $('#btnSimpan');

        tombol.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: urlBidang,
            type: "POST",
            data: $(this).serialize(),
            dataType: "JSON",

            success: function (response) {

                $('#tambahBidang').modal('hide');
                $('#formTambahBidang')[0].reset();

                loadBidang();

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message || 'Bidang berhasil disimpan.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4e73df'
                });

            },

            error: function (xhr) {

                let pesan = 'Bidang gagal disimpan.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    pesan = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: pesan,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4e73df'
                });

            },

            complete: function () {
                tombol.prop('disabled', false).text('Simpan');
            }
        });

    });


    // =========================================
    // MEMBUKA MODAL EDIT DAN MENGISI DATA LAMA
    // =========================================

    $(document).on('click', '.btnEditBidang', function () {

        const id = $(this).data('id');
        const nama = $(this).attr('data-nama');
        const inisial = $(this).attr('data-inisial');

        $('#editIdBidang').val(id);
        $('#editNamaBidang').val(nama);
        $('#editInisialBidang').val(inisial);

        $('#editBidang').modal('show');

    });


    // =========================================
    // MENYIMPAN PERUBAHAN MELALUI API PUT
    // =========================================

    $('#formEditBidang').on('submit', function (e) {

        e.preventDefault();

        const id = $('#editIdBidang').val();
        const tombol = $('#btnUpdate');

        tombol.prop('disabled', true).text('Menyimpan...');

        $.ajax({

            url: urlBidang + '/' + encodeURIComponent(id),

            type: "PUT",

            data: {
                nama: $('#editNamaBidang').val(),
                inisial: $('#editInisialBidang').val()
            },

            dataType: "JSON",

            success: function (response) {

                $('#editBidang').modal('hide');

                loadBidang();

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message || 'Bidang berhasil diperbarui.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4e73df'
                });

            },

            error: function (xhr) {

                let pesan = 'Bidang gagal diperbarui.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    pesan = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: pesan,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4e73df'
                });

            },

            complete: function () {
                tombol.prop('disabled', false).text('Simpan Perubahan');
            }

        });

    });


    // =========================================
    // MENGHAPUS DATA BIDANG MELALUI API DELETE
    // =========================================

    $(document).on('click', '.btnHapusBidang', function () {

        const id = $(this).data('id');
        const nama = $(this).attr('data-nama');

        Swal.fire({

            title: 'Apakah Anda yakin?',

            text: `Bidang "${nama}" akan dihapus.`,

            icon: 'warning',

            showCancelButton: true,

            confirmButtonColor: '#e74a3b',

            cancelButtonColor: '#858796',

            confirmButtonText: 'Ya, Hapus!',

            cancelButtonText: 'Batal'

        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({

                    url: urlBidang + '/' + encodeURIComponent(id),

                    type: "DELETE",

                    dataType: "JSON",

                    success: function (response) {

                        loadBidang();

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message || 'Bidang berhasil dihapus.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#4e73df'
                        });

                    },

                    error: function (xhr) {

                        let pesan = 'Bidang gagal dihapus.';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            pesan = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: pesan,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#4e73df'
                        });

                    }

                });

            }

        });

    });


    // =========================================
    // RESET FORM SAAT MODAL DITUTUP
    // =========================================

    $('#tambahBidang').on('hidden.bs.modal', function () {
        $('#formTambahBidang')[0].reset();
    });

    $('#editBidang').on('hidden.bs.modal', function () {
        $('#formEditBidang')[0].reset();
        $('#editIdBidang').val('');
    });


    // =========================================
    // MEMUAT DATA SAAT HALAMAN DIBUKA
    // =========================================

    loadBidang();

});
</script>

<?= $this->endSection(); ?>
