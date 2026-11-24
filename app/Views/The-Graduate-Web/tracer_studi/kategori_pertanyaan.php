<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<?php
use App\Models\KategoriTracerStudiModel;

$kategoriList         = $kategoriList ?? [];
$isKategoriTableReady = $isKategoriTableReady ?? true;
$filterKeyword        = (string) ($filterKeyword ?? '');
$kategoriErrors       = $kategoriErrors ?? [];
$editKategoriErrors   = $editKategoriErrors ?? [];
$editKategoriId       = (int) ($editKategoriId ?? 0);

$flashMessage = session()->getFlashdata('message');
$flashIcon    = is_array($flashMessage) ? ($flashMessage['icon'] ?? 'info') : '';
$flashTitle   = is_array($flashMessage) ? ($flashMessage['title'] ?? '') : '';
$flashText    = is_array($flashMessage) ? ($flashMessage['text'] ?? '') : '';

$hasErrors     = $kategoriErrors !== [];
$hasEditErrors = $editKategoriErrors !== [];

$oldNama      = $hasErrors ? (string) (old('nama_kategori') ?? '') : '';
$oldDeskripsi = $hasErrors ? (string) (old('deskripsi') ?? '') : '';

$editOldId        = $hasEditErrors ? (int) (old('id') ?? $editKategoriId) : 0;
$editOldNama      = $hasEditErrors ? (string) (old('nama_kategori') ?? '') : '';
$editOldDeskripsi = $hasEditErrors ? (string) (old('deskripsi') ?? '') : '';
?>

<?php if ($flashText !== '') : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= esc($flashIcon) ?></p>
        <p id="title" hidden><?= esc($flashTitle) ?></p>
        <p id="text" hidden><?= esc($flashText) ?></p>
    </div>
    <?php
    $alertClass = match ($flashIcon) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };
    ?>
    <div class="alert <?= $alertClass ?> alert-dismissible fade show shadow-sm" role="alert">
        <?php if ($flashTitle !== '') : ?>
            <strong><?= esc($flashTitle) ?>:</strong>
        <?php endif; ?>
        <?= esc($flashText) ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<!-- Begin of Content -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
    <h1 class="h3 mb-2 mb-md-0 text-gray-800">
        <i class="fas fa-tags text-primary mr-2"></i>Master Kategori Tracer Study
    </h1>
</div>

<?php if (! $isKategoriTableReady) : ?>
    <div class="alert alert-warning shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Tabel database <code>kategori_tracer_studi</code> belum dibuat. Daftar di bawah menggunakan katalog bawaan (mode baca). Jalankan migrasi <code>php spark migrate</code> untuk mengaktifkan pengelolaan Master Kategori.
    </div>
<?php endif; ?>

<div class="alert alert-info border-left-info shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-center">
        <i class="fas fa-info-circle fa-lg text-info mr-3"></i>
        <div>
            <strong>Kelola Kategori Pertanyaan:</strong>
            Kategori yang terdaftar di sini menjadi pilihan resmi pada <strong>Bank Pertanyaan</strong> dan <strong>Pertanyaan Tracer Study Aktif</strong>.
            Mengubah nama kategori akan otomatis memperbarui seluruh pertanyaan terkait, sedangkan kategori yang masih dipakai pertanyaan dilindungi agar tidak terhapus.
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
            <h6 class="m-0 font-weight-bold text-primary">Daftar Kategori Pertanyaan</h6>
            <small class="text-muted">Total kategori tampil: <strong><?= count($kategoriList) ?></strong></small>
        </div>
        <div class="mt-2 mt-md-0">
            <button type="button"
                class="btn btn-primary btn-sm shadow-sm font-weight-bold"
                data-toggle="modal"
                data-target="#modalTambahKategori"
                <?= ! $isKategoriTableReady ? 'disabled title="Jalankan migrasi terlebih dahulu"' : '' ?>>
                <i class="fas fa-plus fa-sm mr-1"></i> Tambah Kategori
            </button>
        </div>
    </div>

    <div class="card-body">
        <form method="get" action="<?= base_url('graduate/kategori_tracer_studi') ?>" class="form-row align-items-end mb-3 pb-3 border-bottom">
            <div class="form-group col-md-7 mb-2 mb-md-0">
                <label for="filterKeywordKategori" class="small font-weight-bold text-gray-700 mb-1">Cari Kategori</label>
                <input type="search" id="filterKeywordKategori" name="q" class="form-control form-control-sm" value="<?= esc($filterKeyword, 'attr') ?>" placeholder="Cari berdasarkan nama kategori atau deskripsi...">
            </div>
            <div class="form-group col-md-5 mb-0 d-flex">
                <button type="submit" class="btn btn-primary btn-sm mr-2 font-weight-bold">
                    <i class="fas fa-search mr-1"></i> Cari
                </button>
                <a href="<?= base_url('graduate/kategori_tracer_studi') ?>" class="btn btn-outline-secondary btn-sm">
                    Reset
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" width="100%">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 55px;" class="text-center">No</th>
                        <th style="width: 240px;">Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th style="width: 220px;" class="text-center">Pemakaian Pertanyaan</th>
                        <th style="width: 150px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kategoriList)) : ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Tidak ada kategori yang sesuai dengan pencarian saat ini.
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($kategoriList as $idx => $kat) : ?>
                            <?php
                            $kid         = (int) ($kat['id'] ?? ($idx + 1));
                            $namaKat     = (string) ($kat['nama_kategori'] ?? '');
                            $deskripsi   = trim((string) ($kat['deskripsi'] ?? ''));
                            $jumlahAktif = (int) ($kat['jumlah_aktif'] ?? 0);
                            $jumlahBank  = (int) ($kat['jumlah_bank'] ?? 0);
                            $totalUsed   = (int) ($kat['total_digunakan'] ?? ($jumlahAktif + $jumlahBank));
                            $canDelete   = ! empty($kat['can_delete']) && $isKategoriTableReady;

                            $deleteBlockReason = '';
                            if (! $isKategoriTableReady) {
                                $deleteBlockReason = 'Jalankan migrasi terlebih dahulu.';
                            } elseif ($totalUsed > 0) {
                                $deleteBlockReason = sprintf(
                                    'Tidak dapat dihapus karena masih digunakan oleh %d pertanyaan aktif dan %d di Bank Pertanyaan.',
                                    $jumlahAktif,
                                    $jumlahBank
                                );
                            }
                            ?>
                            <tr>
                                <td class="text-center align-middle font-weight-bold"><?= $idx + 1 ?></td>
                                <td class="align-middle">
                                    <span class="badge badge-info px-2 py-1" style="font-size: 0.85rem;"><?= esc($namaKat) ?></span>
                                </td>
                                <td class="align-middle text-gray-700 small">
                                    <?= $deskripsi !== '' ? nl2br(esc($deskripsi)) : '<span class="text-muted font-italic">-</span>' ?>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge badge-success px-2 py-1 mr-1" title="Jumlah di Daftar Pertanyaan Aktif">
                                        Aktif: <?= $jumlahAktif ?>
                                    </span>
                                    <span class="badge badge-secondary px-2 py-1" title="Jumlah di Bank Pertanyaan">
                                        Bank: <?= $jumlahBank ?>
                                    </span>
                                </td>
                                <td class="text-center align-middle text-nowrap">
                                    <button type="button"
                                        class="btn btn-warning btn-sm btn-edit-kategori font-weight-bold"
                                        data-toggle="modal"
                                        data-target="#modalEditKategori"
                                        data-id="<?= $kid ?>"
                                        data-nama="<?= esc($namaKat, 'attr') ?>"
                                        data-deskripsi="<?= esc($deskripsi, 'attr') ?>"
                                        data-aktif="<?= $jumlahAktif ?>"
                                        data-bank="<?= $jumlahBank ?>"
                                        title="Ubah kategori"
                                        <?= ! $isKategoriTableReady ? 'disabled' : '' ?>>
                                        <i class="fas fa-edit fa-fw"></i> Ubah
                                    </button>
                                    <?php if ($canDelete) : ?>
                                        <button type="button"
                                            class="btn btn-danger btn-sm btn-hapus-kategori ml-1"
                                            data-toggle="modal"
                                            data-target="#modalHapusKategori"
                                            data-id="<?= $kid ?>"
                                            data-nama="<?= esc($namaKat, 'attr') ?>"
                                            title="Hapus kategori">
                                            <i class="fas fa-trash fa-fw"></i>
                                        </button>
                                    <?php else : ?>
                                        <button type="button"
                                            class="btn btn-outline-secondary btn-sm ml-1"
                                            disabled
                                            title="<?= esc($deleteBlockReason, 'attr') ?>">
                                            <i class="fas fa-lock fa-fw"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" role="dialog" aria-labelledby="modalTambahKategoriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-primary" id="modalTambahKategoriLabel">
                    <i class="fas fa-plus-circle mr-2"></i>Tambah Kategori Tracer Study
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/kategori_tracer_studi') ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <div class="modal-body">
                    <?php if ($hasErrors) : ?>
                        <div class="alert alert-danger" role="alert">
                            <strong><i class="fas fa-exclamation-circle mr-1"></i>Periksa Kembali Isian:</strong>
                            <ul class="mb-0 pl-3 mt-1">
                                <?php foreach ($kategoriErrors as $errMsg) : ?>
                                    <li><?= esc($errMsg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="tambahNamaKategori" class="font-weight-bold">
                            Nama Kategori <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            id="tambahNamaKategori"
                            name="nama_kategori"
                            class="form-control"
                            maxlength="<?= KategoriTracerStudiModel::MAX_NAMA_LENGTH ?>"
                            value="<?= esc($oldNama, 'attr') ?>"
                            placeholder="Contoh: Kegiatan Wirausaha"
                            required>
                        <small class="form-text text-muted">Maksimal <?= KategoriTracerStudiModel::MAX_NAMA_LENGTH ?> karakter dan harus unik.</small>
                    </div>

                    <div class="form-group mb-0">
                        <label for="tambahDeskripsiKategori" class="font-weight-bold">Deskripsi (Opsional)</label>
                        <textarea id="tambahDeskripsiKategori"
                            name="deskripsi"
                            rows="3"
                            maxlength="<?= KategoriTracerStudiModel::MAX_DESC_LENGTH ?>"
                            class="form-control"
                            placeholder="Penjelasan singkat ruang lingkup pertanyaan pada kategori ini..."><?= esc($oldDeskripsi) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEditKategori" tabindex="-1" role="dialog" aria-labelledby="modalEditKategoriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-warning" id="modalEditKategoriLabel">
                    <i class="fas fa-edit mr-2"></i>Ubah Kategori Tracer Study
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/kategori_tracer_studi/update') ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="editKategoriId" value="<?= $editOldId > 0 ? $editOldId : '' ?>">
                <div class="modal-body">
                    <?php if ($hasEditErrors) : ?>
                        <div class="alert alert-danger edit-kategori-error-alert" role="alert">
                            <strong><i class="fas fa-exclamation-circle mr-1"></i>Periksa Kembali Isian:</strong>
                            <ul class="mb-0 pl-3 mt-1">
                                <?php foreach ($editKategoriErrors as $errMsg) : ?>
                                    <li><?= esc($errMsg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-light border small text-muted mb-3 d-none" id="editKategoriCascadeInfo">
                        <i class="fas fa-sync-alt mr-1 text-primary"></i>
                        Kategori ini sedang dipakai oleh <strong id="editKatUsageInfo"></strong>. Mengubah nama akan otomatis memperbarui kategori pada seluruh pertanyaan terkait.
                    </div>

                    <div class="form-group">
                        <label for="editNamaKategori" class="font-weight-bold">
                            Nama Kategori <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            id="editNamaKategori"
                            name="nama_kategori"
                            class="form-control"
                            maxlength="<?= KategoriTracerStudiModel::MAX_NAMA_LENGTH ?>"
                            value="<?= esc($editOldNama, 'attr') ?>"
                            required>
                    </div>

                    <div class="form-group mb-0">
                        <label for="editDeskripsiKategori" class="font-weight-bold">Deskripsi (Opsional)</label>
                        <textarea id="editDeskripsiKategori"
                            name="deskripsi"
                            rows="3"
                            maxlength="<?= KategoriTracerStudiModel::MAX_DESC_LENGTH ?>"
                            class="form-control"><?= esc($editOldDeskripsi) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Kategori -->
<div class="modal fade" id="modalHapusKategori" tabindex="-1" role="dialog" aria-labelledby="modalHapusKategoriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-danger" id="modalHapusKategoriLabel">
                    <i class="fas fa-trash mr-2"></i>Konfirmasi Hapus Kategori
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= base_url('graduate/kategori_tracer_studi/delete') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="hapusKategoriId" value="">
                <div class="modal-body">
                    <p class="mb-2">Apakah Anda yakin ingin menghapus kategori berikut dari Master Kategori Tracer Study?</p>
                    <div class="alert alert-warning py-2 mb-2">
                        <span class="badge badge-info px-2 py-1" id="hapusNamaKategori">-</span>
                    </div>
                    <p class="text-muted small mb-0">
                        Hanya kategori yang belum digunakan oleh pertanyaan di Form Aktif maupun Bank Pertanyaan yang dapat dihapus.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger font-weight-bold">
                        <i class="fas fa-trash fa-fw mr-1"></i> Ya, Hapus Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>
<script>
    $(function () {
        $(document).on('click', '.btn-edit-kategori', function () {
            var $btn       = $(this);
            var jmlAktif   = parseInt($btn.attr('data-aktif') || '0', 10) || 0;
            var jmlBank    = parseInt($btn.attr('data-bank') || '0', 10) || 0;
            var $namaInput = $('#editNamaKategori');

            $('.edit-kategori-error-alert').addClass('d-none');
            $('#editKategoriId').val($btn.attr('data-id') || '');
            $namaInput.val($btn.attr('data-nama') || '').prop('readonly', false).removeClass('bg-light');
            $('#editDeskripsiKategori').val($btn.attr('data-deskripsi') || '');

            if (jmlAktif + jmlBank > 0) {
                $('#editKatUsageInfo').text(jmlAktif + ' pertanyaan aktif & ' + jmlBank + ' di Bank Pertanyaan');
                $('#editKategoriCascadeInfo').removeClass('d-none');
            } else {
                $('#editKategoriCascadeInfo').addClass('d-none');
            }
        });

        $(document).on('click', '.btn-hapus-kategori', function () {
            var $btn = $(this);
            $('#hapusKategoriId').val($btn.attr('data-id') || '');
            $('#hapusNamaKategori').text($btn.attr('data-nama') || '-');
        });

        <?php if ($hasErrors) : ?>
        $('#modalTambahKategori').modal('show');
        <?php endif; ?>

        <?php if ($hasEditErrors) : ?>
        $('#modalEditKategori').modal('show');
        <?php endif; ?>

        var flashData = $('#flashdata');
        if (flashData.length && flashData.data('open')) {
            var icon  = $('#icon').text();
            var title = $('#title').text();
            var text  = $('#text').text();
            if (text && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: icon || 'info',
                    title: title || 'Informasi',
                    text: text,
                    confirmButtonColor: '#1C476C'
                });
            }
        }
    });
</script>
<?= $this->endSection(); ?>
