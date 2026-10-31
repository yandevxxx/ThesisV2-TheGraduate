<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">SK Yudisium</h1>
</div>

<?php if (session()->getFlashdata("message")) : ?>
<?php
    $msg = session()->getFlashdata("message");
?>
<div class="alert alert-<?= ($msg['icon'] === 'success') ? 'success' : (($msg['icon'] === 'warning') ? 'warning' : (($msg['icon'] === 'info') ? 'info' : 'danger')) ?> alert-dismissible fade show" role="alert">
    <strong><?= esc($msg['title'] ?? '') ?></strong> <?= esc($msg['text'] ?? '') ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<!-- Card Daftar Mahasiswa Terplot SK Yudisium -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Mahasiswa Terplot SK Yudisium</h6>
    </div>
    <div class="card-body">
        <div class="bg-light border rounded p-2 mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <form action="<?= base_url('graduate/sk_yudisium') ?>" method="get" class="form-inline my-1">
                    <div class="input-group input-group-sm mr-2 my-1" style="min-width: 260px;">
                        <input type="text" class="form-control" id="filterQ" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Cari NPM / nama mahasiswa..." aria-label="Cari NPM atau nama mahasiswa">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                    <select class="custom-select custom-select-sm mr-2 my-1" id="filterStatus" name="status" style="width: 150px;" aria-label="Filter status">
                        <option value="">Semua Status</option>
                        <option value="baru" <?= ($filters['status'] ?? '') === 'baru' ? 'selected' : '' ?>>Baru</option>
                        <option value="transfer" <?= ($filters['status'] ?? '') === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary my-1"><i class="fas fa-filter fa-fw"></i> Filter</button>
                    <?php if (($filters['q'] ?? '') !== '' || ($filters['status'] ?? '') !== '') : ?>
                    <a href="<?= base_url('graduate/sk_yudisium') ?>" class="btn btn-sm btn-link my-1 ml-1">Reset</a>
                    <?php endif; ?>
                </form>
                <div class="my-1">
                    <button type="button" class="btn btn-sm btn-info my-1 mr-1" id="btnBulkSetTanggal" data-toggle="modal" data-target="#modalBulkSetTanggal" disabled><i class="fas fa-calendar-check fa-fw"></i> Set Tanggal Massal (<span id="bulkCount">0</span>)</button>
                    <button type="button" class="btn btn-sm btn-primary my-1 mr-1" data-toggle="modal" data-target="#modalTambahTerplot"><i class="fas fa-user-plus fa-fw"></i> Tambah Mahasiswa</button>
                    <a href="<?= base_url('graduate/skYudisiumDownloadTemplate') ?>" class="btn btn-sm btn-outline-primary my-1 mr-1"><i class="fas fa-file-download fa-fw"></i> Download Template</a>
                    <button type="button" class="btn btn-sm btn-success my-1" data-toggle="modal" data-target="#modalUnggahPlotYudisium" title="Unggah daftar terplot (.xlsx)"><i class="fas fa-file-upload fa-fw"></i> Unggah Excel</button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 4%;"><input type="checkbox" id="checkAll" title="Pilih Semua"></th>
                        <th style="width: 5%;">No</th>
                        <th style="width: 12%;">NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 15%;">Tanggal SK Yudisium</th>
                        <th style="width: 18%;">Diunggah Pada</th>
                        <th style="width: 20%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarTerplot)) : ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            <?= (($filters['q'] ?? '') !== '' || ($filters['status'] ?? '') !== '') ? 'Tidak ada data mahasiswa terplot yang cocok dengan filter' : 'Belum ada data mahasiswa terplot' ?>
                        </td>
                    </tr>
                    <?php else : ?>
                    <?php
                    $startNo = isset($currentPage, $perPage) ? ((($currentPage - 1) * $perPage) + 1) : 1;
                    foreach ($daftarTerplot as $m) :
                    ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="check-npm" value="<?= esc($m['npm']) ?>" data-nama="<?= esc($m['nama']) ?>">
                        </td>
                        <td class="text-center"><?= $startNo++ ?></td>
                        <td><?= esc($m['npm']) ?></td>
                        <td><?= esc($m['nama']) ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?= ($m['status'] === 'baru') ? 'success' : 'info' ?> px-2 py-1"><?= ucfirst(esc($m['status'])) ?></span>
                        </td>
                        <td class="text-center"><?= ! empty($m['tanggal_sk_yudisium']) ? esc(date('d/m/Y', strtotime($m['tanggal_sk_yudisium']))) : '-' ?></td>
                        <td class="text-center"><?= ! empty($m['uploaded_at']) ? esc(date('d/m/Y H:i', strtotime($m['uploaded_at']))) : '-' ?></td>
                        <td class="text-center text-nowrap">
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditStatusTerplot" data-id="<?= esc($m['id']) ?>" data-npm="<?= esc($m['npm']) ?>" data-nama="<?= esc($m['nama']) ?>" data-status="<?= esc($m['status']) ?>" title="Edit Status"><i class="fas fa-edit fa-fw"></i> Edit Status</button>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalSetTanggalSk" data-npm="<?= esc($m['npm']) ?>" data-nama="<?= esc($m['nama']) ?>" data-tanggal="<?= esc($m['tanggal_sk_yudisium'] ?? '') ?>" title="Input Tanggal SK"><i class="fas fa-calendar-alt fa-fw"></i> Input Tanggal SK</button>
                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modalHapusTerplot" data-id="<?= esc($m['id']) ?>" data-npm="<?= esc($m['npm']) ?>" data-nama="<?= esc($m['nama']) ?>" title="Hapus data terplot"><i class="fas fa-trash fa-fw"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
            <div class="text-muted small">
                Menampilkan <?= count($daftarTerplot) ?> dari <?= (int) ($totalRows ?? count($daftarTerplot)) ?> data mahasiswa terplot
            </div>
            <div>
                <?php if (isset($pager)) : ?>
                    <?= $pager->links('terplot', 'bootstrap_pagination') ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Mahasiswa Terplot -->
<div class="modal fade" id="modalTambahTerplot" tabindex="-1" role="dialog" aria-labelledby="modalTambahTerplotLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahTerplotLabel">Tambah Mahasiswa Terplot</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumStore') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tambahNpm">NPM</label>
                        <input type="text" class="form-control" id="tambahNpm" name="npm" value="<?= old('npm', '') ?>" inputmode="numeric" pattern="[0-9]{8,12}" minlength="8" maxlength="12" required>
                        <small class="form-text text-muted">NPM harus berupa angka 8-12 digit dan belum terdaftar.</small>
                    </div>
                    <div class="form-group">
                        <label for="tambahNama">Nama Mahasiswa</label>
                        <input type="text" class="form-control" id="tambahNama" name="nama" value="<?= old('nama', '') ?>" maxlength="150" required>
                    </div>
                    <div class="form-group mb-0">
                        <label for="tambahStatus">Status</label>
                        <select class="form-control" id="tambahStatus" name="status" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="baru" <?= old('status') === 'baru' ? 'selected' : '' ?>>Baru</option>
                            <option value="transfer" <?= old('status') === 'transfer' ? 'selected' : '' ?>>Transfer</option>
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

<!-- Modal Unggah Daftar Terplot SK Yudisium -->
<div class="modal fade" id="modalUnggahPlotYudisium" tabindex="-1" role="dialog" aria-labelledby="modalUnggahPlotYudisiumLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUnggahPlotYudisiumLabel">Unggah Daftar Mahasiswa Terplot (.xlsx)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumUpload') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p class="mb-2">Kolom wajib: <strong>nama</strong>, <strong>npm</strong>, <strong>status</strong> (nilai: <code>baru</code> atau <code>transfer</code>)</p>
                    <p class="mb-3 text-muted small">NPM yang sudah terplot tidak akan disimpan dua kali saat diunggah ulang.</p>
                    <div class="form-group">
                        <label for="filePlot">Berkas Excel (.xls / .xlsx)</label>
                        <input type="file" class="form-control-file" id="filePlot" name="filePlot" accept=".xls,.xlsx" required>
                        <small class="form-text text-muted">Maksimal 5MB</small>
                    </div>
                    <a href="<?= base_url('graduate/skYudisiumDownloadTemplate') ?>" class="btn btn-link btn-sm pl-0"><i class="fas fa-file-download"></i> Download Template Excel</a>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Unggah & Pratinjau</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Set Tanggal SK Yudisium (Individu) -->
<div class="modal fade" id="modalSetTanggalSk" tabindex="-1" role="dialog" aria-labelledby="modalSetTanggalSkLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSetTanggalSkLabel">Input Tanggal SK Yudisium</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumSetTanggal') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="npm" id="skNpm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Mahasiswa</label>
                        <input type="text" class="form-control" id="skNama" readonly>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_sk_yudisium">Tanggal SK Yudisium</label>
                        <input type="date" class="form-control" id="tanggal_sk_yudisium" name="tanggal_sk_yudisium" required>
                        <small class="form-text text-muted">Tanggal harus berformat valid (YYYY-MM-DD)</small>
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

<!-- Modal Set Tanggal SK Yudisium Massal (Bulk Update) -->
<div class="modal fade" id="modalBulkSetTanggal" tabindex="-1" role="dialog" aria-labelledby="modalBulkSetTanggalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBulkSetTanggalLabel"><i class="fas fa-calendar-check mr-2 text-info"></i>Set Tanggal SK Yudisium Massal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumBulkSetTanggal') ?>" method="post" id="formBulkSetTanggal">
                <?= csrf_field() ?>
                <div id="bulkNpmInputs"></div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        Tanggal SK Yudisium akan diterapkan secara bersamaan ke <strong id="bulkSelectedCount">0</strong> mahasiswa terpilih.
                    </div>
                    <div class="form-group">
                        <label for="bulk_tanggal_sk_yudisium" class="font-weight-bold">Tanggal SK Yudisium</label>
                        <input type="date" class="form-control" id="bulk_tanggal_sk_yudisium" name="tanggal_sk_yudisium" required>
                        <small class="form-text text-muted">Tanggal harus berformat valid (YYYY-MM-DD)</small>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Daftar Mahasiswa Terpilih:</label>
                        <div id="bulkSelectedList" class="bg-light border rounded p-2 small" style="max-height: 140px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info" id="btnSubmitBulk"><i class="fas fa-save fa-fw"></i> Terapkan ke Semua</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Status Mahasiswa Terplot -->
<div class="modal fade" id="modalEditStatusTerplot" tabindex="-1" role="dialog" aria-labelledby="modalEditStatusTerplotLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditStatusTerplotLabel"><i class="fas fa-edit mr-2 text-warning"></i>Edit Status Mahasiswa Terplot</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumUpdateStatus') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="editStatusId">
                <div class="modal-body">
                    <div class="form-group">
                        <label>NPM Mahasiswa</label>
                        <input type="text" class="form-control bg-light" id="editStatusNpm" readonly>
                    </div>
                    <div class="form-group">
                        <label>Nama Mahasiswa</label>
                        <input type="text" class="form-control bg-light" id="editStatusNama" readonly>
                    </div>
                    <div class="form-group mb-0">
                        <label for="editStatus" class="font-weight-bold">Status Mahasiswa <span class="text-danger">*</span></label>
                        <select class="form-control" id="editStatus" name="status" required>
                            <option value="baru">Baru</option>
                            <option value="transfer">Transfer</option>
                        </select>
                        <small class="form-text text-muted">Hanya kolom status (baru/transfer) yang dapat diubah.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save fa-fw"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Mahasiswa Terplot -->
<div class="modal fade" id="modalHapusTerplot" tabindex="-1" role="dialog" aria-labelledby="modalHapusTerplotLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalHapusTerplotLabel">Hapus Mahasiswa Terplot</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/skYudisiumDelete') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="hapusTerplotId">
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus data mahasiswa terplot berikut?</p>
                    <p class="mb-1"><strong id="hapusTerplotNama"></strong></p>
                    <p class="text-muted mb-0" id="hapusTerplotNpm"></p>
                    <div class="alert alert-warning mt-3 mb-0 small">
                        Mahasiswa ini akan kehilangan akses ke pendaftaran wisuda dan tracer studi sampai datanya diunggah kembali.
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
    $(function () {
        <?php if (old('npm') !== null || old('nama') !== null || old('status') !== null) : ?>
        $('#modalTambahTerplot').modal('show');
        <?php endif; ?>

        // Bulk Checkbox Handling
        function updateBulkButton() {
            var checked = $('.check-npm:checked');
            var count = checked.length;
            $('#bulkCount').text(count);
            $('#btnBulkSetTanggal').prop('disabled', count === 0);
        }

        $('#checkAll').on('change', function () {
            $('.check-npm').prop('checked', $(this).is(':checked'));
            updateBulkButton();
        });

        $(document).on('change', '.check-npm', function () {
            if (!$(this).is(':checked')) {
                $('#checkAll').prop('checked', false);
            } else if ($('.check-npm:checked').length === $('.check-npm').length) {
                $('#checkAll').prop('checked', true);
            }
            updateBulkButton();
        });

        $('#modalBulkSetTanggal').on('show.bs.modal', function () {
            var checked = $('.check-npm:checked');
            var count = checked.length;
            $('#bulkSelectedCount').text(count);
            var inputsHtml = '';
            var listHtml = '<ul class="mb-0 pl-3">';
            checked.each(function () {
                var npm = $(this).val();
                var nama = $(this).data('nama');
                inputsHtml += '<input type="hidden" name="npms[]" value="' + npm + '">';
                listHtml += '<li><strong>' + npm + '</strong> - ' + nama + '</li>';
            });
            listHtml += '</ul>';
            $('#bulkNpmInputs').html(inputsHtml);
            $('#bulkSelectedList').html(listHtml);
        });

        // Single Set Tanggal Modal
        $('#modalSetTanggalSk').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var npm = button.data('npm');
            var nama = button.data('nama');
            var tanggal = button.data('tanggal');
            var modal = $(this);
            modal.find('#skNpm').val(npm);
            modal.find('#skNama').val(nama);
            modal.find('#tanggal_sk_yudisium').val(tanggal);
        });

        // Edit Status Modal
        $('#modalEditStatusTerplot').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var npm = button.data('npm');
            var nama = button.data('nama');
            var status = button.data('status');
            var modal = $(this);
            modal.find('#editStatusId').val(id);
            modal.find('#editStatusNpm').val(npm);
            modal.find('#editStatusNama').val(nama);
            modal.find('#editStatus').val(status);
        });

        // Single Delete Modal
        $('#modalHapusTerplot').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            modal.find('#hapusTerplotId').val(button.data('id'));
            modal.find('#hapusTerplotNama').text(button.data('nama'));
            modal.find('#hapusTerplotNpm').text(button.data('npm'));
        });
    });
</script>

<?= $this->endSection(); ?>
