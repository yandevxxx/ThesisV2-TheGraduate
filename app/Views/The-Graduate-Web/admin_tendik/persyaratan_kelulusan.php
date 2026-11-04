<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<style>
    .draggable-row {
        cursor: grab;
        transition: background-color 0.15s ease, opacity 0.15s ease;
    }
    .draggable-row:active {
        cursor: grabbing;
    }
    .draggable-row.dragging {
        opacity: 0.35 !important;
        background-color: #e8ecf7 !important;
    }
    .draggable-row.drag-over-top {
        border-top: 3px solid #4e73df !important;
    }
    .draggable-row.drag-over-bottom {
        border-bottom: 3px solid #4e73df !important;
    }
    .drag-handle {
        cursor: grab;
        padding: 2px 4px;
        color: #b7b9cc;
    }
    .drag-handle:hover {
        color: #4e73df !important;
    }
</style>

<!-- Begin of Content -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-clipboard-list text-primary mr-2"></i>Persyaratan Wisuda</h1>
    <button type="button" class="btn btn-primary btn-sm shadow-sm" data-toggle="modal" data-target="#modalTambahPersyaratan">
        <i class="fas fa-plus fa-fw mr-1"></i> Tambah Persyaratan
    </button>
</div>

<?php
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
<?php endif; ?>

<?php if ($flashError) : ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Gagal!</strong> <?= esc(is_array($flashError) ? implode(', ', $flashError) : (string) $flashError) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<?php if ($flashSuccess) : ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Berhasil!</strong> <?= esc((string) $flashSuccess) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<!-- Card Tabel Persyaratan Wisuda -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Dokumen Persyaratan Wisuda</h6>
        <span class="badge badge-light border text-muted px-2 py-1 small">
            <i class="fas fa-arrows-alt-v mr-1 text-primary"></i>Tarik baris tabel untuk mengatur urutan
        </span>
    </div>
    <div class="card-body">
        <!-- Form Filter & Pencarian -->
        <div class="bg-light border rounded p-2 mb-3">
            <form action="<?= base_url('graduate/persyaratan') ?>" method="get" class="form-inline flex-wrap">
                <div class="input-group input-group-sm mr-2 my-1" style="min-width: 240px;">
                    <input type="text" class="form-control" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Cari nama atau keterangan dokumen...">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                </div>

                <select class="custom-select custom-select-sm mr-2 my-1" name="sifat" style="width: 140px;">
                    <option value="">Semua Sifat</option>
                    <option value="wajib" <?= ($filters['sifat'] ?? '') === 'wajib' ? 'selected' : '' ?>>Wajib</option>
                    <option value="opsional" <?= ($filters['sifat'] ?? '') === 'opsional' ? 'selected' : '' ?>>Opsional</option>
                </select>

                <select class="custom-select custom-select-sm mr-2 my-1" name="status" style="width: 140px;">
                    <option value="">Semua Status</option>
                    <option value="AKTIF" <?= ($filters['status'] ?? '') === 'AKTIF' ? 'selected' : '' ?>>Aktif</option>
                    <option value="NON-AKTIF" <?= ($filters['status'] ?? '') === 'NON-AKTIF' ? 'selected' : '' ?>>Nonaktif</option>
                </select>

                <button type="submit" class="btn btn-sm btn-primary my-1 mr-1">
                    <i class="fas fa-filter fa-fw"></i> Filter
                </button>

                <?php if (($filters['q'] ?? '') !== '' || ($filters['sifat'] ?? '') !== '' || ($filters['status'] ?? '') !== '') : ?>
                <a href="<?= base_url('graduate/persyaratan') ?>" class="btn btn-sm btn-link my-1">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="tablePersyaratan" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 9%;">Urutan</th>
                        <th style="width: 25%;">Nama Persyaratan</th>
                        <th>Ketentuan Berkas</th>
                        <th style="width: 14%;">Format & Ukuran</th>
                        <th style="width: 9%;">Sifat</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 14%;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbodyPersyaratan">
                    <?php if (empty($daftarPersyaratan)) : ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <?= (($filters['q'] ?? '') !== '' || ($filters['sifat'] ?? '') !== '' || ($filters['status'] ?? '') !== '') ? 'Tidak ada persyaratan wisuda yang cocok dengan filter.' : 'Belum ada data persyaratan wisuda.' ?>
                        </td>
                    </tr>
                    <?php else : ?>
                    <?php
                    $startNo = isset($currentPage, $perPage) ? ((($currentPage - 1) * $perPage) + 1) : 1;
                    foreach ($daftarPersyaratan as $p) :
                        $isWajib = (int) ($p['is_wajib'] ?? 1) === 1;
                        $isAktif = ($p['status'] ?? 'AKTIF') === 'AKTIF';
                    ?>
                    <tr class="draggable-row" data-id="<?= esc($p['id']) ?>">
                        <td class="text-center font-weight-bold text-muted col-no"><?= $startNo++ ?></td>
                        <td class="text-center align-middle">
                            <div class="d-inline-flex align-items-center">
                                <span class="drag-handle mr-1" title="Tahan dan tarik untuk mengubah urutan">
                                    <i class="fas fa-grip-vertical fa-sm"></i>
                                </span>
                                <span class="badge badge-light border px-2 py-1 badge-urutan"><?= (int) ($p['urutan'] ?? 0) ?></span>
                            </div>
                        </td>
                        <td class="font-weight-bold text-gray-800">
                            <?= esc($p['nama']) ?>
                        </td>
                        <td class="small text-muted">
                            <?= !empty($p['keterangan']) ? esc($p['keterangan']) : '<span class="text-muted font-italic">- Tidak ada keterangan khusus -</span>' ?>
                        </td>
                        <td class="small text-center">
                            <span class="badge badge-secondary text-uppercase px-2 py-1"><?= esc($p['tipe_file'] ?? 'PDF') ?></span>
                            <div class="text-muted mt-1">Maks. <?= number_format(((int) ($p['max_size_kb'] ?? 2048)) / 1024, 1) ?> MB</div>
                        </td>
                        <td class="text-center">
                            <?php if ($isWajib) : ?>
                                <span class="badge badge-danger px-2 py-1"><i class="fas fa-asterisk fa-xs mr-1"></i>Wajib</span>
                            <?php else : ?>
                                <span class="badge badge-info px-2 py-1">Opsional</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <form action="<?= base_url('graduate/persyaratanToggleStatus/' . esc($p['id'], 'url')) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-<?= $isAktif ? 'success' : 'secondary' ?> py-0 px-2" style="font-size: 0.75rem;" title="Klik untuk mengubah status">
                                    <i class="fas fa-<?= $isAktif ? 'check-circle' : 'times-circle' ?> mr-1"></i><?= $isAktif ? 'Aktif' : 'Nonaktif' ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-center text-nowrap">
                            <button type="button" class="btn btn-warning btn-sm btn-edit-persyaratan" 
                                data-toggle="modal" 
                                data-target="#modalEditPersyaratan"
                                data-id="<?= esc($p['id']) ?>"
                                data-nama="<?= esc($p['nama']) ?>"
                                data-keterangan="<?= esc($p['keterangan'] ?? '') ?>"
                                data-tipe="<?= esc($p['tipe_file'] ?? 'pdf,jpg,jpeg,png') ?>"
                                data-size="<?= esc($p['max_size_kb'] ?? 2048) ?>"
                                data-wajib="<?= esc($p['is_wajib'] ?? 1) ?>"
                                data-urutan="<?= esc($p['urutan'] ?? 0) ?>"
                                data-status="<?= esc($p['status'] ?? 'AKTIF') ?>"
                                title="Edit Persyaratan">
                                <i class="fas fa-edit fa-fw"></i> Edit
                            </button>
                            <button type="button" class="btn btn-danger btn-sm btn-hapus-persyaratan"
                                data-toggle="modal"
                                data-target="#modalHapusPersyaratan"
                                data-id="<?= esc($p['id']) ?>"
                                data-nama="<?= esc($p['nama']) ?>"
                                title="Hapus Persyaratan">
                                <i class="fas fa-trash fa-fw"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
            <div class="text-muted small">
                Menampilkan <strong><?= count($daftarPersyaratan) ?></strong> dari <strong><?= (int) ($totalRows ?? count($daftarPersyaratan)) ?></strong> data persyaratan wisuda
            </div>
            <div>
                <?php if (isset($pager)) : ?>
                    <?= $pager->links('persyaratan', 'bootstrap_pagination') ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Persyaratan -->
<div class="modal fade" id="modalTambahPersyaratan" tabindex="-1" role="dialog" aria-labelledby="modalTambahPersyaratanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-primary" id="modalTambahPersyaratanLabel">
                    <i class="fas fa-plus-circle mr-2"></i>Tambah Persyaratan Wisuda
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/persyaratanStore') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tambahNama" class="font-weight-bold">Nama Persyaratan Dokumen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tambahNama" name="nama" placeholder="Contoh: Fotocopy Berwarna Ijazah SMA" maxlength="255" required>
                    </div>

                    <div class="form-group">
                        <label for="tambahKeterangan" class="font-weight-bold">Keterangan / Ketentuan Berkas</label>
                        <textarea class="form-control" id="tambahKeterangan" name="keterangan" rows="3" placeholder="Contoh: Scan berwarna dari ijazah asli, terlihat nomor seri dengan jelas"></textarea>
                        <small class="form-text text-muted">Instruksi petunjuk yang akan dibaca mahasiswa saat mengunggah berkas.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="tambahTipeFile" class="font-weight-bold">Format File yang Diizinkan</label>
                            <input type="text" class="form-control" id="tambahTipeFile" name="tipe_file" value="pdf,jpg,jpeg,png" placeholder="pdf,jpg,jpeg,png" required>
                            <small class="form-text text-muted">Pisahkan dengan tanda koma (contoh: <code>pdf,jpg,png</code>)</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="tambahMaxSize" class="font-weight-bold">Ukuran Maksimal Berkas (KB)</label>
                            <input type="number" class="form-control" id="tambahMaxSize" name="max_size_kb" value="2048" min="256" max="25600" step="256" required>
                            <small class="form-text text-muted">2048 KB = 2 MB | 5120 KB = 5 MB | 10240 KB = 10 MB</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="tambahIsWajib" class="font-weight-bold">Sifat Dokumen</label>
                            <select class="form-control" id="tambahIsWajib" name="is_wajib" required>
                                <option value="1" selected>Wajib Diunggah</option>
                                <option value="0">Opsional</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="tambahUrutan" class="font-weight-bold">Urutan Tampilan</label>
                            <input type="number" class="form-control" id="tambahUrutan" name="urutan" value="<?= count($daftarPersyaratan) + 1 ?>" min="0" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="tambahStatus" class="font-weight-bold">Status Persyaratan</label>
                            <select class="form-control" id="tambahStatus" name="status" required>
                                <option value="AKTIF" selected>Aktif (Berlaku)</option>
                                <option value="NON-AKTIF">Nonaktif (Arsip)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save fa-fw mr-1"></i> Simpan Persyaratan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Persyaratan -->
<div class="modal fade" id="modalEditPersyaratan" tabindex="-1" role="dialog" aria-labelledby="modalEditPersyaratanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-warning" id="modalEditPersyaratanLabel">
                    <i class="fas fa-edit mr-2"></i>Edit Persyaratan Wisuda
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/persyaratanUpdate') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="editId">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editNama" class="font-weight-bold">Nama Persyaratan Dokumen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editNama" name="nama" maxlength="255" required>
                    </div>

                    <div class="form-group">
                        <label for="editKeterangan" class="font-weight-bold">Keterangan / Ketentuan Berkas</label>
                        <textarea class="form-control" id="editKeterangan" name="keterangan" rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="editTipeFile" class="font-weight-bold">Format File yang Diizinkan</label>
                            <input type="text" class="form-control" id="editTipeFile" name="tipe_file" required>
                            <small class="form-text text-muted">Pisahkan dengan tanda koma (contoh: <code>pdf,jpg,png</code>)</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editMaxSize" class="font-weight-bold">Ukuran Maksimal Berkas (KB)</label>
                            <input type="number" class="form-control" id="editMaxSize" name="max_size_kb" min="256" max="25600" step="256" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="editIsWajib" class="font-weight-bold">Sifat Dokumen</label>
                            <select class="form-control" id="editIsWajib" name="is_wajib" required>
                                <option value="1">Wajib Diunggah</option>
                                <option value="0">Opsional</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="editUrutan" class="font-weight-bold">Urutan Tampilan</label>
                            <input type="number" class="form-control" id="editUrutan" name="urutan" min="0" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="editStatus" class="font-weight-bold">Status Persyaratan</label>
                            <select class="form-control" id="editStatus" name="status" required>
                                <option value="AKTIF">Aktif (Berlaku)</option>
                                <option value="NON-AKTIF">Nonaktif (Arsip)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save fa-fw mr-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Persyaratan -->
<div class="modal fade" id="modalHapusPersyaratan" tabindex="-1" role="dialog" aria-labelledby="modalHapusPersyaratanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-danger" id="modalHapusPersyaratanLabel">
                    <i class="fas fa-trash mr-2"></i>Hapus Persyaratan Wisuda
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/persyaratanDelete') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="hapusId">
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus persyaratan wisuda berikut?</p>
                    <div class="alert alert-warning py-2">
                        <strong id="hapusNama" class="text-gray-900"></strong>
                    </div>
                    <p class="text-muted small mb-0">Persyaratan yang dihapus tidak akan lagi muncul pada form pengunggahan berkas wisuda mahasiswa.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash fa-fw mr-1"></i> Hapus</button>
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
        // Modal Edit Event Handler
        $('#modalEditPersyaratan').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            modal.find('#editId').val(button.data('id'));
            modal.find('#editNama').val(button.data('nama'));
            modal.find('#editKeterangan').val(button.data('keterangan'));
            modal.find('#editTipeFile').val(button.data('tipe'));
            modal.find('#editMaxSize').val(button.data('size'));
            modal.find('#editIsWajib').val(button.data('wajib'));
            modal.find('#editUrutan').val(button.data('urutan'));
            modal.find('#editStatus').val(button.data('status'));
        });

        // Modal Hapus Event Handler
        $('#modalHapusPersyaratan').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            modal.find('#hapusId').val(button.data('id'));
            modal.find('#hapusNama').text(button.data('nama'));
        });

        // Drag and Drop Urutan Persyaratan
        var csrfName = '<?= csrf_token() ?>';
        var csrfHash = '<?= csrf_hash() ?>';
        var tbody = document.getElementById('tbodyPersyaratan');

        if (tbody) {
            var draggedRow = null;

            function refreshRowNumbers() {
                var rows = tbody.querySelectorAll('.draggable-row');
                rows.forEach(function (row, idx) {
                    var noCell = row.querySelector('.col-no');
                    var urutanBadge = row.querySelector('.badge-urutan');
                    if (noCell) noCell.textContent = idx + 1;
                    if (urutanBadge) urutanBadge.textContent = idx + 1;
                });
            }

            function saveNewOrder() {
                var rows = tbody.querySelectorAll('.draggable-row');
                var orders = [];
                rows.forEach(function (row) {
                    orders.push(row.getAttribute('data-id'));
                });

                if (orders.length === 0) return;

                var postData = {
                    orders: orders
                };
                postData[csrfName] = csrfHash;

                $.ajax({
                    url: '<?= base_url("graduate/persyaratanReorder") ?>',
                    type: 'POST',
                    data: postData,
                    dataType: 'JSON',
                    success: function (res) {
                        if (res.csrf_token && res.csrf_hash) {
                            csrfName = res.csrf_token;
                            csrfHash = res.csrf_hash;
                            $('input[name="' + res.csrf_token + '"]').val(res.csrf_hash);
                        }
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Urutan persyaratan berhasil disimpan',
                                showConfirmButton: false,
                                timer: 1800,
                                timerProgressBar: true
                            });
                        }
                    },
                    error: function () {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: 'Gagal menyimpan urutan',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        }
                    }
                });
            }

            tbody.querySelectorAll('.draggable-row').forEach(function (row) {
                row.setAttribute('draggable', 'true');

                row.addEventListener('dragstart', function (e) {
                    draggedRow = row;
                    row.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', row.dataset.id);
                });

                row.addEventListener('dragend', function () {
                    row.classList.remove('dragging');
                    tbody.querySelectorAll('.draggable-row').forEach(function (r) {
                        r.classList.remove('drag-over-top', 'drag-over-bottom');
                    });
                    draggedRow = null;
                    refreshRowNumbers();
                    saveNewOrder();
                });

                row.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    if (!draggedRow || draggedRow === row) return;

                    var rect = row.getBoundingClientRect();
                    var midY = rect.top + (rect.height / 2);
                    if (e.clientY < midY) {
                        row.classList.add('drag-over-top');
                        row.classList.remove('drag-over-bottom');
                        tbody.insertBefore(draggedRow, row);
                    } else {
                        row.classList.add('drag-over-bottom');
                        row.classList.remove('drag-over-top');
                        tbody.insertBefore(draggedRow, row.nextSibling);
                    }
                });

                row.addEventListener('dragleave', function () {
                    row.classList.remove('drag-over-top', 'drag-over-bottom');
                });
            });
        }
    });
</script>
<?= $this->endSection(); ?>
