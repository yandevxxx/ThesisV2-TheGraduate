<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<style>
    /* Ikon PDF pada tabel SKL */
    .ikon-pdf { color: #dc3545; }        /* merah — file sudah terunggah */
    .ikon-pdf-kosong { color: #d1d3e2; } /* abu-abu — file masih kosong */
</style>

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari database menyusul:
 * - daftar berkas SKL + status unggahan dari tabel berkas mahasiswa
 * - berkas sudah terunggah otomatis tampil (status "sudah" + nama file)
 */
$daftarBerkas = [
    ['no' => 1, 'nama' => 'Artikel',              'status' => 'sudah', 'file' => 'artikel-ahmad-fauzi.pdf'],
    ['no' => 2, 'nama' => 'Lembar Pengesahan',    'status' => 'belum', 'file' => ''],
    ['no' => 3, 'nama' => 'Sertifikat Kompetensi', 'status' => 'belum', 'file' => ''],
];
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">SKL</h1>
</div>

<!-- Card Surat Keterangan Lulus -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Surat Keterangan Lulus</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr class="text-center">
                        <th style="width: 8%;">No</th>
                        <th style="width: 37%;">Berkas</th>
                        <th style="width: 30%;">File</th>
                        <th style="width: 25%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftarBerkas as $berkas) : ?>
                    <tr class="berkas-row" data-status="<?= esc($berkas['status']) ?>" data-file="<?= esc($berkas['file']) ?>">
                        <td class="text-center"><?= esc($berkas['no']) ?></td>
                        <td><?= esc($berkas['nama']) ?></td>
                        <td class="file-berkas text-center">
                            <?php if ($berkas['status'] === 'sudah') : ?>
                            <i class="fas fa-file-pdf ikon-pdf mr-1"></i><?= esc($berkas['file']) ?>
                            <?php else : ?>
                            <i class="fas fa-file-pdf ikon-pdf-kosong"></i>
                            <?php endif; ?>
                        </td>
                        <td class="aksi-berkas text-center">
                            <?php if ($berkas['status'] === 'sudah') : ?>
                            <span class="text-success font-weight-bold"><i class="fas fa-check"></i> Sudah Diunggah</span>
                            <button type="button" class="btn btn-danger btn-sm hapus-berkas ml-2" data-toggle="tooltip" data-placement="top" title="Hapus Berkas"><i class="fas fa-trash"></i></button>
                            <?php else : ?>
                            <button type="button" class="btn btn-primary btn-sm upload-berkas" data-toggle="tooltip" data-placement="top" title="Upload Berkas"><i class="fas fa-upload"></i> Upload</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-danger d-none mt-3 mb-0" id="alertKirim">
            <i class="fas fa-exclamation-circle fa-fw"></i> Lengkapi semua berkas terlebih dahulu.
        </div>
        <div class="alert alert-success d-none mt-3 mb-0" id="alertSukses">
            <i class="fas fa-check-circle fa-fw"></i> Data berhasil dikirim &mdash; menunggu verifikasi admin.
        </div>

        <div class="text-right mt-3">
            <button type="button" class="btn btn-success" id="btnKirim"><i class="fas fa-paper-plane fa-fw"></i> Kirim Data</button>
        </div>
    </div>
</div>

<!-- Modal Upload Berkas -->
<div class="modal fade" id="modalUploadBerkas" tabindex="-1" role="dialog" aria-labelledby="modalUploadBerkasLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUploadBerkasLabel">Upload Berkas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-2 font-weight-bold" id="uploadNamaBerkas">-</p>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="fileBerkas" accept=".pdf">
                    <label class="custom-file-label" for="fileBerkas">Pilih file...</label>
                </div>
                <small class="text-gray-500">Format: PDF, maks. 5 MB</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpanBerkas"><i class="fas fa-upload fa-fw"></i> Upload</button>
            </div>
        </div>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

    <script>
        // SKL sisi mahasiswa (dummy — endpoint controller/database menyusul saat integrasi)
        $(function () {

            var $modalUpload = $('#modalUploadBerkas');
            var $rowBerkas = null;
            var terkirim = false;

            // Render isi kolom File & Aksi sesuai status baris
            function renderBerkas($row) {
                var sudah = $row.data('status') === 'sudah';
                var namaFile = $row.attr('data-file');
                var $file = $row.find('.file-berkas');
                var $aksi = $row.find('.aksi-berkas');

                if (sudah) {
                    $file.html('<i class="fas fa-file-pdf ikon-pdf mr-1"></i>' + namaFile);
                    $aksi.html(
                        '<span class="text-success font-weight-bold"><i class="fas fa-check"></i> Sudah Diunggah</span>' +
                        ' <button type="button" class="btn btn-danger btn-sm hapus-berkas ml-2" data-toggle="tooltip" data-placement="top" title="Hapus Berkas"><i class="fas fa-trash"></i></button>'
                    );
                } else {
                    $file.html('<i class="fas fa-file-pdf ikon-pdf-kosong"></i>');
                    $aksi.html(
                        '<button type="button" class="btn btn-primary btn-sm upload-berkas" data-toggle="tooltip" data-placement="top" title="Upload Berkas"><i class="fas fa-upload"></i> Upload</button>'
                    );
                }

                $aksi.find('[data-toggle="tooltip"]').tooltip('dispose').tooltip();
            }

            // Klik Upload -> buka modal (judul = nama berkas)
            $(document).on('click', '.upload-berkas', function () {
                $rowBerkas = $(this).closest('tr');
                $('#uploadNamaBerkas').text($rowBerkas.find('td').eq(1).text().trim());
                $('#fileBerkas').val('').next('.custom-file-label').text('Pilih file...');
                $modalUpload.modal('show');
            });

            // Tampilkan nama file terpilih
            $(document).on('change', '#fileBerkas', function () {
                $(this).next('.custom-file-label').text(this.files.length ? this.files[0].name : 'Pilih file...');
            });

            // Simulasikan upload
            $(document).on('click', '#btnSimpanBerkas', function () {
                if (!$rowBerkas) return;

                var namaFile = $('#fileBerkas')[0].files.length
                    ? $('#fileBerkas')[0].files[0].name
                    : 'berkas-dummy.pdf';

                $rowBerkas.data('status', 'sudah').attr('data-status', 'sudah').attr('data-file', namaFile);
                renderBerkas($rowBerkas);

                $modalUpload.modal('hide');
            });

            // Hapus berkas -> kembali jadi tombol Upload
            $(document).on('click', '.hapus-berkas', function () {
                var $row = $(this).closest('tr');
                $row.data('status', 'belum').attr('data-status', 'belum').attr('data-file', '');
                renderBerkas($row);
            });

            // Tombol Kirim Data (dummy) — semua berkas wajib terunggah
            $(document).on('click', '#btnKirim', function () {
                if (terkirim) return;

                var total = $('.berkas-row').length;
                var sudah = $('.berkas-row[data-status="sudah"]').length;

                $('#alertKirim').addClass('d-none');
                $('#alertSukses').addClass('d-none');

                if (sudah < total) {
                    // Belum lengkap -> tampilkan alert peringatan
                    $('#alertKirim').removeClass('d-none');
                    $('html, body').animate({ scrollTop: $('#alertKirim').offset().top - 120 }, 'slow');
                    return;
                }

                // Lengkap -> simulasi kirim berhasil, kunci halaman
                terkirim = true;
                $('#alertSukses').removeClass('d-none');
                $('.hapus-berkas').addClass('d-none');
                $(this).prop('disabled', true).html('<i class="fas fa-check fa-fw"></i> Data Terkirim');
                $('html, body').animate({ scrollTop: $('#alertSukses').offset().top - 120 }, 'slow');
            });
        });
    </script>

<?= $this->endSection(); ?>
