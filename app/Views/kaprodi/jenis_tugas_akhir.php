
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
                   id="tableJenisTugasAkhir"
                   width="100%"
                   cellspacing="0">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Jenis Tugas Akhir</th>
                    </tr>
                </thead>

                <tbody id="dataJenisTugasAkhir">
                    <tr>
                        <td colspan="2" class="text-center">
                            Memuat data...
                        </td>
                    </tr>
                </tbody>

            </table>
        </div>

    </div>
</div>


<!-- MODAL TAMBAH JENIS TUGAS AKHIR -->
<div class="modal fade"
     id="tambahJenisTugasAkhir"
     tabindex="-1"
     role="dialog"
     aria-labelledby="tambahJenisTugasAkhirLabel"
     aria-hidden="true">

    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title"
                    id="tambahJenisTugasAkhirLabel">
                    Tambah Jenis Tugas Akhir
                </h5>

                <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close">
                    <span aria-hidden="true">&times;</span>
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

<?= $this->endSection(); ?>


<!-- JAVASCRIPT -->
<?= $this->section("scripts"); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {

    const urlJenisTugasAkhir =
        "<?= base_url('api/kaprodi/jenis-tugas-akhir') ?>";


    // Mengamankan teks sebelum ditampilkan
    function escapeHtml(value) {
        return $('<div>').text(value ?? '-').html();
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
                            </tr>
                        `;

                    });

                } else {

                    html = `
                        <tr>
                            <td colspan="2" class="text-center">
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
                        <td colspan="2"
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

        const tombol = $('#btnSimpanJenisTugasAkhir');

        tombol.prop('disabled', true).text('Menyimpan...');

        $.ajax({

            url: urlJenisTugasAkhir,

            type: "POST",

            data: $(this).serialize(),

            dataType: "JSON",

            success: function (response) {

                $('#tambahJenisTugasAkhir').modal('hide');

                $('#formTambahJenisTugasAkhir')[0].reset();

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

                let pesan = 'Jenis tugas akhir gagal ditambahkan.';

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

            },

            complete: function () {

                tombol.prop('disabled', false).text('Simpan');

            }

        });

    });


    // =========================================
    // RESET FORM SAAT MODAL DITUTUP
    // =========================================

    $('#tambahJenisTugasAkhir').on('hidden.bs.modal', function () {

        $('#formTambahJenisTugasAkhir')[0].reset();

    });


    // =========================================
    // MEMUAT DATA SAAT HALAMAN DIBUKA
    // =========================================

    loadJenisTugasAkhir();

});
</script>

<?= $this->endSection(); ?>
