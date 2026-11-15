
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
                    data-target="#tambahJenisTugasAkhir">
                Tambahkan Jenis Tugas Akhir
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered"
                   width="100%"
                   cellspacing="0">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Jenis Tugas Akhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody id="dataJenisTugasAkhir">
                    <tr>
                        <td colspan="3" class="text-center">
                            Memuat data...
                        </td>
                    </tr>
                </tbody>

            </table>
        </div>

    </div>
</div>


<!-- MODAL TAMBAH -->
<div class="modal fade"
     id="tambahJenisTugasAkhir"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Tambah Jenis Tugas Akhir
                </h5>

                <button type="button"
                        class="close"
                        data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <form id="formTambahJenisTugasAkhir">
                <?= csrf_field() ?>

                <div class="modal-body">

                    <div class="form-group">
                        <label for="namaJenisTugasAkhir">
                            Nama Jenis Tugas Akhir
                        </label>

                        <input type="text"
                               class="form-control"
                               id="namaJenisTugasAkhir"
                               name="nama_jenis_tugas_akhir"
                               maxlength="100"
                               placeholder="Masukkan nama jenis tugas akhir"
                               required>

                        <small class="text-danger error-nama"></small>
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
                            id="btnSimpanJenisTugasAkhir">
                        Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>


<!-- MODAL EDIT -->
<div class="modal fade"
     id="editJenisTugasAkhir"
     tabindex="-1"
     role="dialog"
     aria-hidden="true">

    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Edit Jenis Tugas Akhir
                </h5>

                <button type="button"
                        class="close"
                        data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <form id="formEditJenisTugasAkhir">

                <div class="modal-body">

                    <input type="hidden"
                           id="editIdJenisTugasAkhir">

                    <div class="form-group">
                        <label for="editNamaJenisTugasAkhir">
                            Nama Jenis Tugas Akhir
                        </label>

                        <input type="text"
                               class="form-control"
                               id="editNamaJenisTugasAkhir"
                               maxlength="100"
                               placeholder="Masukkan nama jenis tugas akhir"
                               required>

                        <small class="text-danger error-nama"></small>
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
                            id="btnUpdateJenisTugasAkhir">
                        Simpan Perubahan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?= $this->endSection(); ?>


<?= $this->section("scripts"); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {

    const urlJenisTugasAkhir =
        "<?= base_url('api/kaprodi/jenis-tugas-akhir') ?>";


    // Mengamankan teks sebelum ditampilkan
    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }


    // Menghapus pesan error
    function hapusError(form) {
        $(form).find('.error-nama').text('');
        $(form).find('input').removeClass('is-invalid');
    }


    // Menampilkan pesan error
    function tampilkanError(form, pesan) {
        const input = $(form).find('input[type="text"]');

        input.addClass('is-invalid');

        $(form).find('.error-nama').text(pesan);
    }


    // Validasi nama wajib diisi
    function validasiNama(form) {
        const input = $(form).find('input[type="text"]');
        const nama = input.val().trim();

        hapusError(form);

        if (nama === '') {
            tampilkanError(form, 'Kolom ini harus diisi.');
            input.focus();
            return false;
        }

        input.val(nama);

        return true;
    }


    // Mengambil pesan error dari API
    function tampilkanErrorAPI(xhr, form) {

        let pesan = 'Terjadi kesalahan. Silakan coba kembali.';

        if (xhr.responseJSON && xhr.responseJSON.message) {
            pesan = xhr.responseJSON.message;
        }

        if (
            pesan.toLowerCase().includes('sudah') ||
            pesan.toLowerCase().includes('terdaftar') ||
            pesan.toLowerCase().includes('duplicate')
        ) {
            tampilkanError(
                form,
                'Nama jenis tugas akhir sudah tersedia.'
            );
        } else {
            tampilkanError(form, pesan);
        }
    }


    // =========================================
    // MENGAMBIL DATA JENIS TUGAS AKHIR
    // =========================================

    function loadJenisTugasAkhir() {

        $.ajax({

            url: urlJenisTugasAkhir,
            type: "GET",
            dataType: "JSON",

            success: function (response) {

                let html = '';

                if (
                    response.status === 'success' &&
                    response.data &&
                    response.data.length > 0
                ) {

                    response.data.forEach(function (jenis, index) {

                        html += `
                            <tr>
                                <td>${index + 1}</td>

                                <td>
                                    ${escapeHtml(jenis.nama_jenis_tugas_akhir)}
                                </td>

                                <td class="text-nowrap">

                                    <!-- Tombol Edit -->
                                    <button type="button"
                                            class="btn btn-primary btn-sm btn-edit"
                                            title="Edit"
                                            data-id="${escapeHtml(jenis.id_jenis_tugas_akhir)}"
                                            data-nama="${escapeHtml(jenis.nama_jenis_tugas_akhir)}">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <button type="button"
                                            class="btn btn-danger btn-sm btn-hapus"
                                            title="Hapus"
                                            data-id="${escapeHtml(jenis.id_jenis_tugas_akhir)}"
                                            data-nama="${escapeHtml(jenis.nama_jenis_tugas_akhir)}">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                </td>
                            </tr>
                        `;

                    });

                } else {

                    html = `
                        <tr>
                            <td colspan="3" class="text-center">
                                Belum ada jenis tugas akhir.
                            </td>
                        </tr>
                    `;

                }

                $('#dataJenisTugasAkhir').html(html);

            },

            error: function (xhr) {

                let pesan = 'Gagal mengambil data jenis tugas akhir.';

                if (xhr.status === 401) {
                    pesan = 'Silakan login sebagai Kaprodi.';
                } else if (xhr.status === 403) {
                    pesan = 'Akses ditolak atau program studi tidak ditemukan.';
                }

                $('#dataJenisTugasAkhir').html(`
                    <tr>
                        <td colspan="3"
                            class="text-center text-danger">
                            ${pesan}
                        </td>
                    </tr>
                `);

            }

        });

    }


    // =========================================
    // MENAMBAHKAN JENIS TUGAS AKHIR
    // =========================================

    $('#formTambahJenisTugasAkhir').on('submit', function (e) {

        e.preventDefault();

        const form = this;

        if (!validasiNama(form)) return;

        const tombol = $('#btnSimpanJenisTugasAkhir');

        tombol.prop('disabled', true).text('Menyimpan...');

        $.ajax({

            url: urlJenisTugasAkhir,
            type: "POST",
            data: $(form).serialize(),
            dataType: "JSON",

            success: function (response) {

                $('#tambahJenisTugasAkhir').modal('hide');

                form.reset();

                hapusError(form);

                loadJenisTugasAkhir();

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message ||
                        'Jenis tugas akhir berhasil ditambahkan.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4e73df'
                });

            },

            error: function (xhr) {

                tampilkanErrorAPI(xhr, form);

            },

            complete: function () {

                tombol.prop('disabled', false).text('Simpan');

            }

        });

    });


    // =========================================
    // MEMBUKA MODAL EDIT DAN MENGISI DATA LAMA
    // =========================================

    $(document).on('click', '.btn-edit', function () {

        const id = $(this).attr('data-id');
        const nama = $(this).attr('data-nama');

        $('#editIdJenisTugasAkhir').val(id);

        $('#editNamaJenisTugasAkhir').val(nama);

        hapusError('#formEditJenisTugasAkhir');

        $('#editJenisTugasAkhir').modal('show');

    });


    // =========================================
    // MENYIMPAN PERUBAHAN MELALUI API PUT
    // =========================================

    $('#formEditJenisTugasAkhir').on('submit', function (e) {

        e.preventDefault();

        const form = this;

        if (!validasiNama(form)) return;

        const id = $('#editIdJenisTugasAkhir').val();

        const nama = $('#editNamaJenisTugasAkhir').val().trim();

        const tombol = $('#btnUpdateJenisTugasAkhir');

        tombol.prop('disabled', true).text('Menyimpan...');

        $.ajax({

            url: urlJenisTugasAkhir + '/' + encodeURIComponent(id),

            type: "PUT",

            contentType: 'application/json',

            data: JSON.stringify({
                nama_jenis_tugas_akhir: nama
            }),

            dataType: "JSON",

            success: function (response) {

                $('#editJenisTugasAkhir').modal('hide');

                hapusError(form);

                loadJenisTugasAkhir();

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message ||
                        'Jenis tugas akhir berhasil diperbarui.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4e73df'
                });

            },

            error: function (xhr) {

                tampilkanErrorAPI(xhr, form);

            },

            complete: function () {

                tombol.prop('disabled', false)
                      .text('Simpan Perubahan');

            }

        });

    });


    // =========================================
    // MENGHAPUS JENIS TUGAS AKHIR MELALUI API DELETE
    // =========================================

    $(document).on('click', '.btn-hapus', function () {

        const id = $(this).attr('data-id');

        const nama = $(this).attr('data-nama');

        Swal.fire({

            title: 'Apakah Anda yakin?',

            text: `Jenis tugas akhir "${nama}" akan dihapus.`,

            icon: 'warning',

            showCancelButton: true,

            confirmButtonColor: '#e74a3b',

            cancelButtonColor: '#858796',

            confirmButtonText: 'Ya, Hapus!',

            cancelButtonText: 'Batal'

        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({

                    url: urlJenisTugasAkhir + '/' + encodeURIComponent(id),

                    type: "DELETE",

                    dataType: "JSON",

                    success: function (response) {

                        loadJenisTugasAkhir();

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message ||
                                'Jenis tugas akhir berhasil dihapus.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#4e73df'
                        });

                    },

                    error: function (xhr) {

                        let pesan = 'Jenis tugas akhir gagal dihapus.';

                        if (
                            xhr.responseJSON &&
                            xhr.responseJSON.message
                        ) {
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

    $('#tambahJenisTugasAkhir').on('hidden.bs.modal', function () {

        $('#formTambahJenisTugasAkhir')[0].reset();

        hapusError('#formTambahJenisTugasAkhir');

    });


    $('#editJenisTugasAkhir').on('hidden.bs.modal', function () {

        $('#formEditJenisTugasAkhir')[0].reset();

        $('#editIdJenisTugasAkhir').val('');

        hapusError('#formEditJenisTugasAkhir');

    });


    // =========================================
    // HILANGKAN ERROR SAAT PENGGUNA MULAI MENGETIK
    // =========================================

    $('#namaJenisTugasAkhir, #editNamaJenisTugasAkhir')
        .on('input', function () {

            $(this).removeClass('is-invalid');

            $(this).closest('.form-group')
                   .find('.error-nama')
                   .text('');

        });


    // =========================================
    // MEMUAT DATA SAAT HALAMAN DIBUKA
    // =========================================

    loadJenisTugasAkhir();

});
</script>

<?= $this->endSection(); ?>
