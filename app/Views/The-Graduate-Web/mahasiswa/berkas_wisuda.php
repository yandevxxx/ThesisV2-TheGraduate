<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

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
?>
<?php if ($flashText !== '') : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= esc($flashIcon); ?></p>
        <p id="title" hidden><?= esc($flashTitle); ?></p>
        <p id="text" hidden><?= esc($flashText); ?></p>
    </div>
<?php endif; ?>

<?php
$pendaftaran          = $pendaftaran ?? null;
$persyaratanAktif     = $persyaratanAktif ?? [];
$berkasMap            = $berkasMap ?? [];
$jumlahBerkasDiunggah = $jumlahBerkasDiunggah ?? 0;

$statusBerkas = strtoupper((string) ($pendaftaran['status_berkas'] ?? 'DRAFT'));
$isDraft      = ($statusBerkas === 'DRAFT' || $statusBerkas === '');
$isPending    = ($statusBerkas === 'MENUNGGU');
$isApproved   = ($statusBerkas === 'DISETUJUI');
$isRejected   = ($statusBerkas === 'DITOLAK');

// Hitung berapa syarat wajib yang sudah dan belum diunggah
$totalWajib    = 0;
$wajibTerpenuhi = 0;
foreach ($persyaratanAktif as $syarat) {
    if (!empty($syarat['is_wajib'])) {
        $totalWajib++;
        $namaS = $syarat['nama'];
        $idS   = $syarat['id'];
        if (isset($berkasMap[$namaS]) || isset($berkasMap['id_' . $idS])) {
            $wajibTerpenuhi++;
        }
    }
}
$semuaWajibTerpenuhi = ($totalWajib > 0 && $wajibTerpenuhi >= $totalWajib);
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-folder-open text-primary mr-2"></i>Berkas Persyaratan Wisuda</h1>
</div>

<!-- Banner Status Verifikasi Berkas -->
<?php if ($isDraft): ?>
    <div class="alert alert-info border-left-info shadow-sm mb-4" id="bannerDraft">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-2x mr-3 text-info"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Status Berkas: Draft</h5>
                <p class="mb-0">Anda telah mengunggah <strong id="jumlahBerkasInfo"><?= (int) $jumlahBerkasDiunggah ?></strong> berkas. Silakan unggah seluruh dokumen persyaratan wajib di bawah ini, kemudian klik tombol <strong>Kirim Berkas Persyaratan</strong> agar berkas dapat diverifikasi oleh Tendik / Akademik.</p>
            </div>
        </div>
    </div>
<?php elseif ($isPending): ?>
    <div class="alert alert-warning border-left-warning shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-hourglass-half fa-2x mr-3 text-warning"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Berkas Sedang Menunggu Verifikasi Tendik</h5>
                <p class="mb-0">Seluruh dokumen persyaratan wisuda yang Anda kirimkan sedang dalam proses pemeriksaan dan verifikasi oleh tim akademik/tendik. Anda tetap dapat melihat dokumen yang telah diunggah.</p>
            </div>
        </div>
    </div>
<?php elseif ($isApproved): ?>
    <div class="alert alert-success border-left-success shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x mr-3 text-success"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Selamat! Berkas Persyaratan Wisuda Telah Disetujui</h5>
                <p class="mb-0">Seluruh berkas persyaratan wisuda Anda telah dinyatakan lengkap, valid, dan disetujui oleh tim akademik/tendik.</p>
            </div>
        </div>
    </div>
<?php elseif ($isRejected): ?>
    <div class="alert alert-danger border-left-danger shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-times-circle fa-2x mr-3 text-danger"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Berkas Persyaratan Wisuda Ditolak / Perlu Perbaikan</h5>
                <p class="mb-1"><strong>Alasan / Catatan Perbaikan:</strong> <?= esc($pendaftaran['catatan_berkas'] ?? 'Terdapat berkas yang belum sesuai atau buram.') ?></p>
                <small class="text-gray-700">Silakan ganti atau unggah kembali berkas yang diminta, kemudian klik tombol <strong>Kirim Ulang Berkas Persyaratan</strong> di bagian bawah.</small>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Card Tabel Berkas Persyaratan Wisuda -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-sm-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-alt mr-1"></i> Daftar Dokumen Persyaratan Wisuda</h6>
        <span class="small text-muted">Format: PDF / JPG / PNG (Maksimal 5 MB per berkas)</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 45%;">Nama Dokumen</th>
                        <th style="width: 15%;">Sifat</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 20%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($persyaratanAktif as $syarat): 
                        $namaSyarat = $syarat['nama'];
                        $idSyarat   = $syarat['id'];
                        $berkasRow  = $berkasMap[$namaSyarat] ?? $berkasMap['id_' . $idSyarat] ?? null;
                        $sudahUpload = ($berkasRow !== null);
                    ?>
                    <tr class="dokumen-row text-center align-middle" 
                        data-persyaratan-id="<?= esc($idSyarat) ?>" 
                        data-nama-persyaratan="<?= esc($namaSyarat) ?>"
                        data-berkas-id="<?= $sudahUpload ? esc($berkasRow['id']) : '' ?>"
                        data-status="<?= $sudahUpload ? 'sudah' : 'belum' ?>"
                        data-is-wajib="<?= !empty($syarat['is_wajib']) ? '1' : '0' ?>">
                        <td><?= $no++ ?></td>
                        <td class="text-left">
                            <span class="font-weight-bold text-gray-900"><?= esc($namaSyarat) ?></span>
                            <?php if (!empty($syarat['keterangan'])): ?>
                                <small class="d-block text-muted"><?= esc($syarat['keterangan']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($syarat['is_wajib'])): ?>
                                <span class="badge badge-primary px-2 py-1">Wajib</span>
                            <?php else: ?>
                                <span class="badge badge-secondary px-2 py-1">Opsional</span>
                            <?php endif; ?>
                        </td>
                        <td class="status-cell">
                            <?php if ($sudahUpload): ?>
                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Diunggah</span>
                            <?php else: ?>
                                <span class="badge badge-secondary px-2 py-1">Belum</span>
                            <?php endif; ?>
                        </td>
                        <td class="aksi-cell">
                            <?php if ($sudahUpload): ?>
                                <a href="<?= base_url('graduate/berkas/view/' . $berkasRow['nama_file']) ?>" target="_blank" class="btn btn-sm btn-info py-1 px-2" title="Lihat Berkas">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (!$isApproved): ?>
                                    <button type="button" class="btn btn-sm btn-warning py-1 px-2 upload-dokumen-trigger ml-1" title="Ganti File">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger py-1 px-2 hapus-dokumen-trigger ml-1" title="Hapus Berkas">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-primary py-1 px-3 upload-dokumen-trigger">
                                    <i class="fas fa-upload mr-1"></i> Upload
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tombol Kirim Berkas Persyaratan ke Tendik -->
        <div class="mt-4 pt-3 border-top">
            <div class="d-sm-flex align-items-center justify-content-between">
                <div>
                    <span class="text-gray-700 font-weight-bold">Status Dokumen Wajib:</span>
                    <span class="badge <?= $semuaWajibTerpenuhi ? 'badge-success' : 'badge-warning text-dark' ?> ml-1 px-2 py-1" id="badgeWajibStatus">
                        <?= $wajibTerpenuhi ?> dari <?= $totalWajib ?> Wajib Terunggah
                    </span>
                    <small class="d-block text-muted mt-1">
                        <i class="fas fa-info-circle mr-1"></i> Pastikan seluruh dokumen wajib bertanda <strong>Diunggah</strong> sebelum mengirimkan berkas persyaratan.
                    </small>
                </div>
                <div class="mt-3 mt-sm-0">
                    <?php if ($isDraft || $isRejected): ?>
                        <form action="<?= base_url('graduate/berkasWisudaSubmit') ?>" method="post" id="formSubmitBerkas">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-success px-4" id="btnKirimBerkas">
                                <i class="fas fa-paper-plane mr-1"></i> <?= $isRejected ? 'Kirim Ulang Berkas Persyaratan' : 'Kirim Berkas Persyaratan' ?>
                            </button>
                        </form>
                    <?php elseif ($isPending): ?>
                        <button type="button" class="btn btn-secondary px-4" disabled>
                            <i class="fas fa-hourglass-half mr-1"></i> Berkas Sedang Diverifikasi
                        </button>
                    <?php elseif ($isApproved): ?>
                        <button type="button" class="btn btn-success px-4" disabled>
                            <i class="fas fa-check-circle mr-1"></i> Berkas Telah Disetujui
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Upload Dokumen Persyaratan -->
<div class="modal fade" id="modalUploadDokumen" tabindex="-1" role="dialog" aria-labelledby="modalUploadDokumenLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalUploadDokumenLabel"><i class="fas fa-upload mr-2"></i> Unggah Dokumen Persyaratan</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formUploadModal" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="persyaratan_id" id="modalPersyaratanId" value="">
                <input type="hidden" name="nama_persyaratan" id="modalNamaPersyaratan" value="">

                <div class="modal-body">
                    <p class="mb-1 text-muted small">Dokumen yang akan diunggah:</p>
                    <h6 class="mb-3 font-weight-bold text-gray-900" id="uploadNamaDokumen">-</h6>

                    <div class="form-group mb-2">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="fileDokumenInput" name="file_berkas" accept=".pdf,.jpg,.jpeg,.png" required>
                            <label class="custom-file-label" for="fileDokumenInput">Pilih file berkas...</label>
                        </div>
                    </div>
                    <small class="text-muted d-block"><i class="fas fa-info-circle mr-1"></i> Format: PDF / JPG / PNG, ukuran maksimal 5 MB.</small>

                    <div class="progress mt-3 d-none" id="uploadProgressBar">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%">Mengunggah...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSimpanDokumen"><i class="fas fa-upload fa-fw mr-1"></i> Upload Sekarang</button>
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
            // CSRF Token Manager untuk AJAX multi-upload
            var currentCsrfToken = '<?= csrf_token() ?>';
            var currentCsrfHash  = '<?= csrf_hash() ?>';

            function getLatestCsrfHash() {
                var match = document.cookie.match(new RegExp('(^|;\\s*)csrf_cookie_name=([^;]*)'));
                if (match && match[2]) {
                    return decodeURIComponent(match[2]);
                }
                return currentCsrfHash;
            }

            function updateCsrf(newToken, newHash) {
                if (newToken) {
                    currentCsrfToken = newToken;
                }
                if (newHash) {
                    currentCsrfHash = newHash;
                } else {
                    currentCsrfHash = getLatestCsrfHash();
                }
                $('input[name="' + currentCsrfToken + '"]').val(currentCsrfHash);
            }

            // SweetAlert flashdata popup
            var flashData = $('#flashdata');
            if (flashData.length && flashData.data('open')) {
                var icon  = $('#icon').text();
                var title = $('#title').text();
                var text  = $('#text').text();
                if (text && typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: icon || 'info',
                        title: title || (icon === 'success' ? 'Berhasil' : (icon === 'error' ? 'Gagal' : 'Informasi')),
                        text: text,
                        confirmButtonColor: '#1C476C'
                    });
                }
            }

            // Update label custom-file saat file dipilih di modal
            $('#fileDokumenInput').on('change', function () {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass('selected').html(fileName || 'Pilih file berkas...');
            });

            // Trigger Modal Upload
            var activeTargetRow = null;
            $(document).on('click', '.upload-dokumen-trigger', function () {
                var row = $(this).closest('tr.dokumen-row');
                activeTargetRow = row;

                var idSyarat   = row.data('persyaratan-id');
                var namaSyarat = row.data('nama-persyaratan');

                $('#modalPersyaratanId').val(idSyarat);
                $('#modalNamaPersyaratan').val(namaSyarat);
                $('#uploadNamaDokumen').text(namaSyarat);
                $('#fileDokumenInput').val('');
                $('#fileDokumenInput').next('.custom-file-label').removeClass('selected').html('Pilih file berkas...');
                $('#uploadProgressBar').addClass('d-none');
                $('#btnSimpanDokumen').prop('disabled', false);

                $('#modalUploadDokumen').modal('show');
            });

            // Submit AJAX Upload Dokumen
            $('#formUploadModal').on('submit', function (e) {
                e.preventDefault();

                var fileInput = document.getElementById('fileDokumenInput');
                if (!fileInput.files || fileInput.files.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'File Belum Dipilih',
                        text: 'Silakan pilih file berkas terlebih dahulu.',
                        confirmButtonColor: '#1C476C'
                    });
                    return;
                }

                var formData = new FormData(this);
                formData.set(currentCsrfToken, getLatestCsrfHash());

                $('#uploadProgressBar').removeClass('d-none');
                $('#btnSimpanDokumen').prop('disabled', true);

                $.ajax({
                    url: '<?= base_url('graduate/pendaftaranWisudaUploadBerkas') ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (res) {
                        $('#uploadProgressBar').addClass('d-none');
                        $('#btnSimpanDokumen').prop('disabled', false);

                        if (res.csrf_token && res.csrf_hash) {
                            updateCsrf(res.csrf_token, res.csrf_hash);
                        } else {
                            updateCsrf();
                        }

                        if (res.success) {
                            $('#modalUploadDokumen').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil Diunggah',
                                text: res.message || 'Dokumen berhasil disimpan.',
                                timer: 1800,
                                showConfirmButton: false
                            });

                            if (activeTargetRow) {
                                activeTargetRow.attr('data-status', 'sudah');
                                activeTargetRow.attr('data-berkas-id', res.berkas_id);

                                activeTargetRow.find('.status-cell').html(
                                    '<span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Diunggah</span>'
                                );

                                var aksiHtml = '<a href="' + res.url + '" target="_blank" class="btn btn-sm btn-info py-1 px-2" title="Lihat Berkas">' +
                                               '<i class="fas fa-eye"></i></a> ' +
                                               '<button type="button" class="btn btn-sm btn-warning py-1 px-2 upload-dokumen-trigger ml-1" title="Ganti File">' +
                                               '<i class="fas fa-sync-alt"></i></button> ' +
                                               '<button type="button" class="btn btn-sm btn-danger py-1 px-2 hapus-dokumen-trigger ml-1" title="Hapus Berkas">' +
                                               '<i class="fas fa-trash"></i></button>';

                                activeTargetRow.find('.aksi-cell').html(aksiHtml);
                            }

                            if ($('#jumlahBerkasInfo').length && res.total_berkas !== undefined) {
                                $('#jumlahBerkasInfo').text(res.total_berkas);
                            }

                            recheckWajibStatus();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Upload',
                                text: res.message || 'Terjadi kesalahan saat mengunggah berkas.',
                                confirmButtonColor: '#1C476C'
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        $('#uploadProgressBar').addClass('d-none');
                        $('#btnSimpanDokumen').prop('disabled', false);
                        updateCsrf();

                        Swal.fire({
                            icon: 'error',
                            title: 'Kesalahan Server',
                            text: 'Gagal menghubungi server: ' + (error || 'Terjadi kendala jaringan'),
                            confirmButtonColor: '#1C476C'
                        });
                    }
                });
            });

            // Trigger Hapus Dokumen
            $(document).on('click', '.hapus-dokumen-trigger', function () {
                var row = $(this).closest('tr.dokumen-row');
                var berkasId   = row.attr('data-berkas-id');
                var namaSyarat = row.data('nama-persyaratan');

                if (!berkasId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Tidak Ditemukan',
                        text: 'ID berkas tidak ditemukan.',
                        confirmButtonColor: '#1C476C'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Hapus Dokumen?',
                    text: 'Apakah Anda yakin ingin menghapus berkas "' + namaSyarat + '"?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var postData = {
                            berkas_id: berkasId
                        };
                        postData[currentCsrfToken] = getLatestCsrfHash();

                        $.ajax({
                            url: '<?= base_url('graduate/pendaftaranWisudaHapusBerkas') ?>/' + berkasId,
                            type: 'POST',
                            data: postData,
                            dataType: 'json',
                            success: function (res) {
                                if (res.csrf_token && res.csrf_hash) {
                                    updateCsrf(res.csrf_token, res.csrf_hash);
                                } else {
                                    updateCsrf();
                                }

                                if (res.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Dihapus',
                                        text: res.message || 'Berkas berhasil dihapus.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });

                                    row.attr('data-status', 'belum');
                                    row.removeAttr('data-berkas-id');

                                    row.find('.status-cell').html(
                                        '<span class="badge badge-secondary px-2 py-1">Belum</span>'
                                    );

                                    row.find('.aksi-cell').html(
                                        '<button type="button" class="btn btn-sm btn-primary py-1 px-3 upload-dokumen-trigger">' +
                                        '<i class="fas fa-upload mr-1"></i> Upload</button>'
                                    );

                                    if ($('#jumlahBerkasInfo').length && res.total_berkas !== undefined) {
                                        $('#jumlahBerkasInfo').text(res.total_berkas);
                                    }

                                    recheckWajibStatus();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal Hapus',
                                        text: res.message || 'Gagal menghapus berkas.',
                                        confirmButtonColor: '#1C476C'
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                updateCsrf();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Kesalahan Server',
                                    text: 'Gagal menghubungi server saat menghapus berkas.',
                                    confirmButtonColor: '#1C476C'
                                });
                            }
                        });
                    }
                });
            });

            // Helper recheck status wajib
            function recheckWajibStatus() {
                var totalWajib = 0;
                var wajibTerpenuhi = 0;

                $('tr.dokumen-row').each(function () {
                    var isWajib = $(this).attr('data-is-wajib') === '1';
                    var status = $(this).attr('data-status');
                    if (isWajib) {
                        totalWajib++;
                        if (status === 'sudah') {
                            wajibTerpenuhi++;
                        }
                    }
                });

                var badge = $('#badgeWajibStatus');
                if (badge.length) {
                    badge.text(wajibTerpenuhi + ' dari ' + totalWajib + ' Wajib Terunggah');
                    if (totalWajib > 0 && wajibTerpenuhi >= totalWajib) {
                        badge.removeClass('badge-warning text-dark').addClass('badge-success');
                    } else {
                        badge.removeClass('badge-success').addClass('badge-warning text-dark');
                    }
                }
            }

            // Validasi submit berkas
            $('#formSubmitBerkas').on('submit', function (e) {
                var missingWajib = [];
                $('tr.dokumen-row').each(function () {
                    var isWajib = $(this).attr('data-is-wajib') === '1';
                    var status = $(this).attr('data-status');
                    if (isWajib && status !== 'sudah') {
                        var nama = $(this).data('nama-persyaratan');
                        missingWajib.push(nama);
                    }
                });

                if (missingWajib.length > 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Dokumen Wajib Belum Lengkap',
                        html: '<p class="mb-2">Harap unggah seluruh dokumen persyaratan wajib berikut sebelum mengirimkan:</p>' +
                              '<ul class="text-left font-weight-bold text-danger"><li>' + missingWajib.join('</li><li>') + '</li></ul>',
                        confirmButtonColor: '#1C476C'
                    });
                    return false;
                }
            });
        });
    </script>

<?= $this->endSection(); ?>

