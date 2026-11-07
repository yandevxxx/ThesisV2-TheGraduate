<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<style>
    /* Ikon PDF pada tabel SKL */
    .ikon-pdf { color: #dc3545; }        /* merah — file sudah terunggah */
    .ikon-pdf-kosong { color: #d1d3e2; } /* abu-abu — file masih kosong */
</style>

<?php
$flashMessage = session()->getFlashdata('message');
$flashIcon    = 'info';
$flashTitle   = '';
$flashText    = '';

if (is_array($flashMessage)) {
    $flashIcon  = (string) ($flashMessage['icon'] ?? 'info');
    $flashTitle = (string) ($flashMessage['title'] ?? '');
    $flashText  = (string) ($flashMessage['text'] ?? '');
} elseif (is_string($flashMessage) && trim($flashMessage) !== '') {
    $flashIcon  = 'info';
    $flashTitle = 'Informasi';
    $flashText  = $flashMessage;
} elseif (session()->getFlashdata('error')) {
    $flashIcon  = 'error';
    $flashTitle = 'Terjadi Kesalahan';
    $flashText  = (string) session()->getFlashdata('error');
} elseif (session()->getFlashdata('success')) {
    $flashIcon  = 'success';
    $flashTitle = 'Berhasil';
    $flashText  = (string) session()->getFlashdata('success');
}

$daftarPersyaratan = $daftarPersyaratan ?? [];
$berkasMap         = $berkasMap ?? [];
$pengajuan         = $pengajuan ?? null;

$statusPengajuan = $pengajuan['status'] ?? '';
$isDisetujui     = ($statusPengajuan === 'disetujui');
$isProses        = ($statusPengajuan === 'proses');
$isMenunggu      = ($statusPengajuan === 'menunggu');
$isDitolak       = ($statusPengajuan === 'ditolak');
$isTerkunci      = ($isDisetujui || $isProses);

// Hitung kelengkapan berkas wajib
$jumlahWajib    = 0;
$terunggahWajib = 0;
foreach ($daftarPersyaratan as $syarat) {
    if ((int) $syarat['is_wajib'] === 1) {
        $jumlahWajib++;
        if (isset($berkasMap[(int) $syarat['id']])) {
            $terunggahWajib++;
        }
    }
}
$isLengkap = ($jumlahWajib > 0 && $terunggahWajib >= $jumlahWajib);
?>

<?php if ($flashText !== '') : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= esc($flashIcon); ?></p>
        <p id="title" hidden><?= esc($flashTitle); ?></p>
        <p id="text" hidden><?= esc($flashText); ?></p>
    </div>
<?php endif; ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">SKL</h1>
    <div>
        <?php if ($isDisetujui): ?>
            <span class="badge badge-success px-3 py-2 font-weight-bold"><i class="fas fa-check-circle mr-1"></i> SKL Disetujui</span>
        <?php elseif ($isProses): ?>
            <span class="badge badge-info px-3 py-2 font-weight-bold"><i class="fas fa-cog fa-spin mr-1"></i> Sedang Diproses</span>
        <?php elseif ($isMenunggu): ?>
            <span class="badge badge-warning text-dark px-3 py-2 font-weight-bold"><i class="fas fa-hourglass-half mr-1"></i> Menunggu Verifikasi</span>
        <?php elseif ($isDitolak): ?>
            <span class="badge badge-danger px-3 py-2 font-weight-bold"><i class="fas fa-times-circle mr-1"></i> Perlu Revisi</span>
        <?php else: ?>
            <span class="badge badge-secondary px-3 py-2 font-weight-bold"><i class="fas fa-file-alt mr-1"></i> Belum Diajukan</span>
        <?php endif; ?>
    </div>
</div>

<!-- Banner Status Pengajuan SKL -->
<?php if ($isDisetujui): ?>
    <div class="alert alert-success border-left-success shadow-sm mb-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div class="d-flex align-items-center mb-3 mb-md-0">
                <i class="fas fa-check-circle fa-2x mr-3 text-success"></i>
                <div>
                    <h5 class="alert-heading font-weight-bold mb-1">Selamat! Pengajuan SKL Anda Telah Disetujui &amp; Diterbitkan</h5>
                    <p class="mb-1 text-gray-800">Dokumen persyaratan Anda telah dinyatakan sah. Surat Keterangan Lulus resmi telah diterbitkan oleh pimpinan fakultas.</p>
                    <div class="small text-muted">
                        <?php if (!empty($pengajuan['no_surat'])): ?>
                            <span class="mr-3"><i class="fas fa-barcode mr-1 text-primary"></i> Nomor Surat: <strong><?= esc($pengajuan['no_surat']) ?></strong></span>
                        <?php endif; ?>
                        <?php if (!empty($pengajuan['tgl_terbit'])): ?>
                            <span><i class="far fa-calendar-alt mr-1 text-primary"></i> Tanggal Terbit: <strong><?= date('d/m/Y', strtotime($pengajuan['tgl_terbit'])) ?></strong></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($pengajuan['file_skl'])): ?>
                <div>
                    <a href="<?= base_url('graduate/skl_download_resmi/' . $pengajuan['id']) ?>" class="btn btn-success btn-lg shadow-sm font-weight-bold text-nowrap">
                        <i class="fas fa-file-download mr-2"></i> Download SKL Resmi (PDF)
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($isProses): ?>
    <div class="alert alert-info border-left-info shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-cogs fa-2x mr-3 text-info"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Pengajuan SKL Sedang Diproses Bagian Akademik</h5>
                <p class="mb-0">Seluruh dokumen persyaratan Anda telah diverifikasi oleh tenaga kependidikan. Saat ini Surat Keterangan Lulus (SKL) resmi sedang disiapkan dan disahkan oleh pimpinan fakultas. Dokumen SKL resmi akan dapat langsung diunduh di halaman ini setelah dikirimkan oleh petugas.</p>
            </div>
        </div>
    </div>
<?php elseif ($isMenunggu): ?>
    <div class="alert alert-warning border-left-warning shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-hourglass-half fa-2x mr-3 text-warning"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Pengajuan SKL Sedang Menunggu Verifikasi</h5>
                <p class="mb-0">Dokumen persyaratan SKL Anda telah berhasil dikirim pada <strong><?= !empty($pengajuan['diajukan_pada']) ? date('d/m/Y H:i', strtotime($pengajuan['diajukan_pada'])) : '-' ?></strong> dan sedang dalam antrean pemeriksaan oleh tenaga kependidikan.</p>
            </div>
        </div>
    </div>
<?php elseif ($isDitolak): ?>
    <div class="alert alert-danger border-left-danger shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-2x mr-3 text-danger"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Pengajuan SKL Memerlukan Perbaikan / Ditolak</h5>
                <p class="mb-1"><strong>Catatan Petugas:</strong> <?= !empty($pengajuan['catatan']) ? esc($pengajuan['catatan']) : 'Silakan periksa dan perbarui kembali berkas yang Anda unggah.' ?></p>
                <small class="text-muted">Perbaiki berkas yang diminta di bawah ini, lalu klik tombol "Kirim Data" kembali.</small>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Card Surat Keterangan Lulus -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Persyaratan Pengambilan Surat Keterangan Lulus</h6>
        <span class="small text-muted"><i class="fas fa-info-circle mr-1"></i> Format: PDF (Sesuai ketentuan per dokumen)</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr class="text-center bg-light">
                        <th style="width: 6%;">No</th>
                        <th style="width: 34%;">Berkas Persyaratan</th>
                        <th style="width: 15%;">Ketentuan</th>
                        <th style="width: 25%;">File Terunggah</th>
                        <th style="width: 20%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarPersyaratan)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 py-4">Belum ada persyaratan SKL yang aktif dari fakultas.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($daftarPersyaratan as $syarat): ?>
                            <?php
                            $syaratId   = (int) $syarat['id'];
                            $berkasItem = $berkasMap[$syaratId] ?? ($berkasMap[$syarat['nama']] ?? null);
                            $sudahAda   = ($berkasItem !== null && !empty($berkasItem['nama_file']));
                            $fileName   = $sudahAda ? $berkasItem['nama_file'] : '';
                            $berkasId   = $sudahAda ? ($berkasItem['id'] ?? '') : '';
                            $isWajib    = ((int) $syarat['is_wajib'] === 1);
                            $maxMb      = round(($syarat['max_size_kb'] ?? 5120) / 1024, 1);
                            $tipeStr    = strtoupper($syarat['tipe_file'] ?? 'PDF');
                            ?>
                            <tr class="berkas-row"
                                data-id="<?= $syaratId ?>"
                                data-berkas-id="<?= esc($berkasId) ?>"
                                data-nama="<?= esc($syarat['nama']) ?>"
                                data-wajib="<?= $isWajib ? '1' : '0' ?>"
                                data-tipe="<?= esc(strtolower($syarat['tipe_file'] ?? 'pdf')) ?>"
                                data-max-kb="<?= (int) ($syarat['max_size_kb'] ?? 5120) ?>"
                                data-status="<?= $sudahAda ? 'sudah' : 'belum' ?>"
                                data-file="<?= esc($fileName) ?>">
                                <td class="text-center align-middle font-weight-bold"><?= $no++ ?></td>
                                <td class="align-middle">
                                    <div class="font-weight-bold text-gray-800"><?= esc($syarat['nama']) ?></div>
                                    <?php if (!empty($syarat['keterangan'])): ?>
                                        <small class="text-muted d-block"><?= esc($syarat['keterangan']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle small">
                                    <div><span class="badge badge-light border text-dark"><?= esc($tipeStr) ?></span> (Maks. <?= $maxMb ?> MB)</div>
                                    <div><?= $isWajib ? '<span class="badge badge-danger">Wajib</span>' : '<span class="badge badge-secondary">Opsional</span>' ?></div>
                                </td>
                                <td class="file-berkas text-center align-middle">
                                    <?php if ($sudahAda): ?>
                                        <a href="<?= base_url('graduate/skl/preview/' . rawurlencode($fileName)) ?>" target="_blank" class="text-danger text-decoration-none font-weight-bold" title="Klik untuk pratinjau">
                                            <i class="fas fa-file-pdf ikon-pdf mr-1"></i><?= esc($fileName) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 small"><i class="fas fa-file-pdf ikon-pdf-kosong mr-1"></i> Belum ada file</span>
                                    <?php endif; ?>
                                </td>
                                <td class="aksi-berkas text-center align-middle">
                                    <?php if ($sudahAda): ?>
                                        <span class="text-success font-weight-bold mr-1"><i class="fas fa-check"></i> Sudah Diunggah</span>
                                        <?php if (! $isTerkunci): ?>
                                            <button type="button" class="btn btn-danger btn-sm hapus-berkas ml-1" data-id="<?= $syaratId ?>" data-toggle="tooltip" data-placement="top" title="Hapus Berkas"><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if (! $isTerkunci): ?>
                                            <button type="button" class="btn btn-primary btn-sm upload-berkas" data-id="<?= $syaratId ?>" data-toggle="tooltip" data-placement="top" title="Upload Berkas"><i class="fas fa-upload"></i> Upload</button>
                                        <?php else: ?>
                                            <span class="badge badge-light text-muted">Terkunci</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-danger d-none mt-3 mb-0" id="alertKirim">
            <i class="fas fa-exclamation-circle fa-fw"></i> Harap lengkapi semua berkas persyaratan wajib terlebih dahulu sebelum mengirim data.
        </div>
        <div class="alert alert-success d-none mt-3 mb-0" id="alertSukses">
            <i class="fas fa-check-circle fa-fw"></i> Data berhasil dikirim &mdash; menunggu verifikasi tim akademik/tendik.
        </div>

        <?php if (! $isTerkunci): ?>
            <form id="formSubmitSkl" method="post" action="<?= base_url('graduate/sklSubmit') ?>">
                <?= csrf_field() ?>
                <div class="text-right mt-3">
                    <button type="submit" class="btn btn-success" id="btnKirim">
                        <i class="fas fa-paper-plane fa-fw"></i> <?= $isMenunggu ? 'Kirim Ulang Pembaruan' : 'Kirim Data Pengajuan SKL' ?>
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="text-right mt-3">
                <button type="button" class="btn btn-secondary" disabled>
                    <i class="fas fa-lock fa-fw mr-1"></i> <?= $isDisetujui ? 'Dokumen Terkunci (Disetujui)' : 'Dokumen Terkunci (Sedang Diproses)' ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Upload Berkas SKL -->
<div class="modal fade" id="modalUploadBerkas" tabindex="-1" role="dialog" aria-labelledby="modalUploadBerkasLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formUploadBerkasModal" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="persyaratan_id" id="uploadPersyaratanId" value="">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-primary" id="modalUploadBerkasLabel">Upload Dokumen SKL</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-1 text-gray-700">Persyaratan:</p>
                    <h6 class="font-weight-bold text-dark mb-3" id="uploadNamaBerkas">-</h6>
                    <div class="form-group mb-2">
                        <label class="small text-muted font-weight-bold">Pilih File:</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="fileBerkas" name="file_berkas" accept=".pdf">
                            <label class="custom-file-label text-truncate" for="fileBerkas">Pilih file PDF...</label>
                        </div>
                    </div>
                    <small class="text-gray-600 d-block" id="uploadInfoFormat">Format: PDF, maks. 5 MB</small>
                    <div id="uploadErrorMsg" class="alert alert-danger py-1 px-2 mt-2 d-none small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnSimpanBerkas"><i class="fas fa-upload fa-fw mr-1"></i> Upload File</button>
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
            var $modalUpload = $('#modalUploadBerkas');
            var $rowBerkas = null;

            // Update nama token & hash CSRF jika server merespons dengan token baru
            function updateCsrf(tokenName, tokenHash) {
                if (tokenName && tokenHash) {
                    $('input[name="' + tokenName + '"]').val(tokenHash);
                }
            }

            // Render isi kolom File & Aksi sesuai status baris
            function renderBerkas($row, fileUrl) {
                var sudah = $row.data('status') === 'sudah';
                var namaFile = $row.attr('data-file');
                var syaratId = $row.attr('data-id');
                var $file = $row.find('.file-berkas');
                var $aksi = $row.find('.aksi-berkas');

                if (sudah) {
                    var previewLink = fileUrl ? fileUrl : '<?= base_url('graduate/skl/preview') ?>/' + encodeURIComponent(namaFile);
                    $file.html('<a href="' + previewLink + '" target="_blank" class="text-danger text-decoration-none font-weight-bold" title="Klik untuk pratinjau"><i class="fas fa-file-pdf ikon-pdf mr-1"></i>' + $('<div>').text(namaFile).html() + '</a>');
                    $aksi.html(
                        '<span class="text-success font-weight-bold mr-1"><i class="fas fa-check"></i> Sudah Diunggah</span>' +
                        ' <button type="button" class="btn btn-danger btn-sm hapus-berkas ml-1" data-id="' + syaratId + '" data-toggle="tooltip" data-placement="top" title="Hapus Berkas"><i class="fas fa-trash"></i></button>'
                    );
                } else {
                    $file.html('<span class="text-gray-400 small"><i class="fas fa-file-pdf ikon-pdf-kosong mr-1"></i> Belum ada file</span>');
                    $aksi.html(
                        '<button type="button" class="btn btn-primary btn-sm upload-berkas" data-id="' + syaratId + '" data-toggle="tooltip" data-placement="top" title="Upload Berkas"><i class="fas fa-upload"></i> Upload</button>'
                    );
                }

                $aksi.find('[data-toggle="tooltip"]').tooltip();
            }

            // Klik Upload -> buka modal dinamis
            $(document).on('click', '.upload-berkas', function () {
                $rowBerkas = $(this).closest('tr');
                var syaratId = $rowBerkas.attr('data-id');
                var nama = $rowBerkas.attr('data-nama');
                var tipe = ($rowBerkas.attr('data-tipe') || 'pdf').toUpperCase();
                var maxKb = parseInt($rowBerkas.attr('data-max-kb'), 10) || 5120;
                var maxMb = (maxKb / 1024).toFixed(1);

                $('#uploadPersyaratanId').val(syaratId);
                $('#uploadNamaBerkas').text(nama);
                $('#uploadInfoFormat').text('Format: ' + tipe + ', maks. ' + maxMb + ' MB');

                // Atur accept filter file input
                var accepts = tipe.toLowerCase().split(',').map(function(ext) { return '.' + ext.trim(); }).join(',');
                $('#fileBerkas').attr('accept', accepts).val('').next('.custom-file-label').text('Pilih file ' + tipe + '...');
                $('#uploadErrorMsg').addClass('d-none').text('');

                $modalUpload.modal('show');
            });

            // Tampilkan nama file terpilih di label
            $(document).on('change', '#fileBerkas', function () {
                var fileName = this.files.length ? this.files[0].name : 'Pilih file...';
                $(this).next('.custom-file-label').text(fileName);
            });

            // AJAX Upload file berkas ke server
            $(document).on('click', '#btnSimpanBerkas', function () {
                var fileInput = $('#fileBerkas')[0];
                if (!fileInput.files.length) {
                    $('#uploadErrorMsg').removeClass('d-none').text('Harap pilih file terlebih dahulu.');
                    return;
                }

                var formData = new FormData($('#formUploadBerkasModal')[0]);
                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah...');
                $('#uploadErrorMsg').addClass('d-none').text('');

                $.ajax({
                    url: '<?= base_url('graduate/sklUploadBerkas') ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (res) {
                        $btn.prop('disabled', false).html('<i class="fas fa-upload fa-fw mr-1"></i> Upload File');
                        if (res.csrf_token && res.csrf_hash) {
                            updateCsrf(res.csrf_token, res.csrf_hash);
                        }

                        if (res.success) {
                            if ($rowBerkas) {
                                $rowBerkas.attr('data-status', 'sudah')
                                          .attr('data-file', res.nama_file)
                                          .attr('data-berkas-id', res.berkas_id || '');
                                renderBerkas($rowBerkas, res.file_url);
                            }
                            $modalUpload.modal('hide');
                            if (window.Swal) {
                                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1800, showConfirmButton: false });
                            }
                        } else {
                            $('#uploadErrorMsg').removeClass('d-none').text(res.message || 'Gagal mengunggah berkas.');
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false).html('<i class="fas fa-upload fa-fw mr-1"></i> Upload File');
                        $('#uploadErrorMsg').removeClass('d-none').text('Terjadi kesalahan jaringan atau server: ' + xhr.statusText);
                    }
                });
            });

            // AJAX Hapus Berkas
            $(document).on('click', '.hapus-berkas', function () {
                var $row = $(this).closest('tr');
                var syaratId = $row.attr('data-id');
                var berkasId = $row.attr('data-berkas-id');
                var nama = $row.attr('data-nama');

                var doDelete = function () {
                    var postData = {
                        persyaratan_id: syaratId,
                        berkas_id: berkasId
                    };
                    // Tambahkan csrf token
                    var csrfName = $('input[type="hidden"][name^="csrf"]').attr('name') || '<?= csrf_token() ?>';
                    var csrfVal  = $('input[name="' + csrfName + '"]').val();
                    postData[csrfName] = csrfVal;

                    $.ajax({
                        url: '<?= base_url('graduate/sklHapusBerkas') ?>',
                        type: 'POST',
                        data: postData,
                        dataType: 'json',
                        success: function (res) {
                            if (res.csrf_token && res.csrf_hash) {
                                updateCsrf(res.csrf_token, res.csrf_hash);
                            }
                            if (res.success) {
                                $row.attr('data-status', 'belum').attr('data-file', '').attr('data-berkas-id', '');
                                renderBerkas($row);
                                if (window.Swal) {
                                    Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message, timer: 1500, showConfirmButton: false });
                                }
                            } else {
                                if (window.Swal) {
                                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                                } else {
                                    alert(res.message);
                                }
                            }
                        },
                        error: function (xhr) {
                            alert('Gagal menghapus berkas: ' + xhr.statusText);
                        }
                    });
                };

                if (window.Swal) {
                    Swal.fire({
                        title: 'Hapus berkas ini?',
                        text: 'File ' + nama + ' akan dihapus dari server.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#858796',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            doDelete();
                        }
                    });
                } else {
                    if (confirm('Hapus berkas ' + nama + '?')) {
                        doDelete();
                    }
                }
            });

            // Validasi submit pengajuan SKL
            $('#formSubmitSkl').on('submit', function (e) {
                var belumLengkap = false;
                $('.berkas-row').each(function () {
                    var wajib = $(this).attr('data-wajib') === '1';
                    var status = $(this).attr('data-status');
                    if (wajib && status !== 'sudah') {
                        belumLengkap = true;
                    }
                });

                if (belumLengkap) {
                    e.preventDefault();
                    $('#alertKirim').removeClass('d-none');
                    $('html, body').animate({ scrollTop: $('#alertKirim').offset().top - 120 }, 'slow');
                    return false;
                }
                $('#alertKirim').addClass('d-none');
            });
        });
    </script>

<?= $this->endSection(); ?>
