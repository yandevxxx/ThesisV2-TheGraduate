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
    <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Mahasiswa Terplot SK Yudisium (Excel)</h6>
        <div class="mt-2 mt-md-0">
            <a href="<?= base_url('graduate/skYudisiumDownloadTemplate') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-download fa-fw"></i> Download Template</a>
            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalUnggahPlotYudisium"><i class="fas fa-file-upload fa-fw"></i> Unggah Daftar Terplot (.xlsx)</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 12%;">NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 15%;">Tanggal SK Yudisium</th>
                        <th style="width: 18%;">Diunggah Pada</th>
                        <th style="width: 12%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarTerplot)) : ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada data mahasiswa terplot</td>
                    </tr>
                    <?php else : ?>
                    <?php $no = 1; foreach ($daftarTerplot as $m) : ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= esc($m['npm']) ?></td>
                        <td><?= esc($m['nama']) ?></td>
                        <td class="text-center"><?= esc($m['status']) ?></td>
                        <td class="text-center"><?= ! empty($m['tanggal_sk_yudisium']) ? esc(date('d/m/Y', strtotime($m['tanggal_sk_yudisium']))) : '-' ?></td>
                        <td class="text-center"><?= ! empty($m['uploaded_at']) ? esc(date('d/m/Y H:i', strtotime($m['uploaded_at']))) : '-' ?></td>
                        <td class="text-center text-nowrap">
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalSetTanggalSk" data-npm="<?= esc($m['npm']) ?>" data-nama="<?= esc($m['nama']) ?>" data-tanggal="<?= esc($m['tanggal_sk_yudisium'] ?? '') ?>"><i class="fas fa-calendar-alt fa-fw"></i> Input Tanggal SK</button>
                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modalHapusTerplot" data-id="<?= esc($m['id']) ?>" data-npm="<?= esc($m['npm']) ?>" data-nama="<?= esc($m['nama']) ?>" title="Hapus data terplot"><i class="fas fa-trash fa-fw"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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

<!-- Modal Set Tanggal SK Yudisium -->
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
