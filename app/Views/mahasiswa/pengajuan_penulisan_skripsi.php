<?= $this->extend("layout/template"); ?>

<?= $this->section("content"); ?>

<?php
$disabled = ($mahasiswa['status_persetujuan_skripsi'] ?? '') === 'Disetujui';
$mataKuliah = $mataKuliah ?? [];

// Mendukung data lama (nama mata kuliah) dan data baru (kode mata kuliah).
$mkSedangTersimpan = array_values(array_filter(array_map('trim', explode(',', $mahasiswa['mk_sedang_diambil'] ?? ''))));
$mkLulusTersimpan = trim($mahasiswa['mk_wajib_sudah_lulus'] ?? '');
$mkSudahLulusDipilih = $mkLulusTersimpan === 'Sudah lulus semua'
    ? array_column($mataKuliah, 'kode_matkul')
    : array_values(array_filter(array_map('trim', explode(',', $mkLulusTersimpan))));

$mkSedangDipilih = [];
$mkLulusKodeDipilih = [];
foreach ($mataKuliah as $mk) {
    $kode = (string) ($mk['kode_matkul'] ?? '');
    $nama = (string) ($mk['nama_matkul'] ?? '');
    if (in_array($kode, $mkSedangTersimpan, true) || in_array($nama, $mkSedangTersimpan, true)) {
        $mkSedangDipilih[] = $kode;
    }
    if (in_array($kode, $mkSudahLulusDipilih, true) || in_array($nama, $mkSudahLulusDipilih, true)) {
        $mkLulusKodeDipilih[] = $kode;
    }
}

$sksTerhitung = 0;
foreach ($mataKuliah as $mk) {
    if (in_array((string) ($mk['kode_matkul'] ?? ''), $mkLulusKodeDipilih, true)) {
        $sksTerhitung += (int) ($mk['sks'] ?? 0);
    }
}
?>

<!-- NOTIFIKASI -->
<?php if (session()->getFlashdata("message")) : ?>
    <div id="flashdata" data-open="true">
        <p id="icon" hidden><?= session()->getFlashdata("message")["icon"]; ?></p>
        <p id="title" hidden><?= session()->getFlashdata("message")["title"]; ?></p>
        <p id="text" hidden><?= session()->getFlashdata("message")["text"]; ?></p>
    </div>
<?php endif; ?>


<!-- JUDUL HALAMAN -->
<h1 class="h3 mb-2 text-gray-800">
    <?= esc($title) ?>
</h1>




<!-- STATUS PENGAJUAN -->
<div class="row">
    <div class="col-lg-12">

        <?php
        $statusPengajuan = trim($mahasiswa['status_persetujuan_skripsi'] ?? '');

        $persyaratanLengkap =
            !empty($mahasiswa['file_khs']) &&
            !empty($mahasiswa['file_krs']) &&
            !empty($mahasiswa['file_persetujuan_skripsi']) &&
            !empty($mahasiswa['sks_lulus']) &&
            !empty($mahasiswa['pembimbing_akademik']) &&
            !empty($mahasiswa['mk_sedang_diambil']) &&
            !empty($mahasiswa['mk_akan_diambil']);
        ?>

        <?php if ($statusPengajuan === 'Disetujui'): ?>

            <div class="alert alert-success" role="alert">
                <small>
                    <strong>Pengajuan penulisan skripsi</strong> Anda
                    <strong>disetujui</strong>. Silakan kumpulkan
                    <strong>proposal</strong> Anda pada
                    <a href="<?= base_url("mahasiswa/proposal") ?>">menu berikut</a>.
                </small>
            </div>

        <?php elseif ($statusPengajuan === 'Ditolak'): ?>

            <div class="alert alert-danger d-flex justify-content-between align-items-center flex-wrap" role="alert">
                <small>
                    <strong>Pengajuan penulisan skripsi</strong> Anda
                    <strong>ditolak</strong>. Silakan perbaiki dan kirim ulang pengajuan Anda.
                </small>

            <button
                type="button"
                class="btn btn-sm"
                id="btnLihatAlasan">
                <i class="fas fa-eye"></i> Lihat Alasan
            </button>

        <?php elseif ($persyaratanLengkap): ?>

            <div class="alert alert-info" role="alert">
                <small>
                    <strong>Pengajuan Penulisan Skripsi</strong> Anda sedang
                    <strong>diproses</strong>.
                </small>
            </div>

        <?php else: ?>

            <div class="alert alert-danger" role="alert">
                <small>
                    Lengkapi data berikut sebagai syarat
                    <strong>wajib</strong> untuk dapat mengikuti kegiatan skripsi.
                </small>
            </div>

        <?php endif; ?>

    </div>
</div>

<!-- FORM PENGAJUAN -->
<div class="card shadow mb-4">

    <div class="card-header py-3">
        Persyaratan Pengajuan Penulisan Skripsi
    </div>

    <div class="card-body">

        <form
            action="<?= base_url("mahasiswa/insertPengajuanPenulisanSkripsi/" . $mahasiswa['npm']) ?>"
            method="post"
            id="formDataPersetujuanSkripsi"
            enctype="multipart/form-data"
        >

            <?= csrf_field() ?>


            <!-- BARIS PERTAMA: TIGA KOLOM -->
            <div class="row">

                <div class="col-lg-4">
                    <div class="form-group">
                        <label for="sks_lulus">SKS yang Sudah Lulus</label>

                        <input
                            type="text"
                            class="form-control"
                            id="sks_lulus"
                            name="sks_lulus_tampilan"
                            value="<?= esc($sksTerhitung) ?>"
                            readonly
                            >
                        </div>
                </div>


                <!-- DOSEN PEMBIMBING -->
                <div class="col-lg-4">
                    <div class="form-group">

                        <label for="pembimbing_akademik">
                            Dosen Pembimbing Akademik
                        </label>

                        <select
                            class="form-control"
                            name="pembimbing_akademik"
                            id="pembimbing_akademik"
                            <?= $disabled ? 'disabled' : '' ?>
                        >

                            <option
                                value=""
                                disabled
                                <?= empty($mahasiswa['pembimbing_akademik']) ? 'selected' : '' ?>
                            >
                                Pilih Dosen
                            </option>

                            <?php foreach ($dosen as $d): ?>

                                <option
                                    value="<?= esc($d['id']) ?>"
                                    <?= ($mahasiswa['pembimbing_akademik'] ?? '') == $d['id'] ? 'selected' : '' ?>
                                >
                                    <?= esc($d['inisial'] . ' | ' . $d['nama']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>
                </div>


                <!-- JENIS TUGAS AKHIR -->
                <div class="col-lg-4">
                    <div class="form-group">

                        <label for="jenis_ta">
                            Jenis Tugas Akhir
                        </label>

                        <select
                            class="form-control"
                            name="jenis_ta"
                            id="jenis_ta"
                            <?= $disabled ? 'disabled' : '' ?>
                        >

                            <option value="" selected disabled>
                             Pilih TA
                            </option>

                            <?php foreach ($jenisTugasAkhir as $jenis): ?>
    <option
        value="<?= esc($jenis['id_jenis_tugas_akhir']) ?>"
        <?= ($mahasiswa['id_jenis_tugas_akhir'] ?? '') == $jenis['id_jenis_tugas_akhir'] ? 'selected' : '' ?>
    >
        <?= esc($jenis['nama_jenis_tugas_akhir']) ?>
    </option>
<?php endforeach; ?>
                        </select>
                        <small class="text-danger d-none" id="errorJenisTA">
                            Kolom ini harus diisi.
                        </small>

                    </div>
                </div>

            </div>


            <!-- BARIS KEDUA: DUA DROPDOWN MATA KULIAH -->
            <div class="row mt-3">

                <!-- KIRI: MATA KULIAH YANG SUDAH LULUS -->
                <div class="col-lg-6">
                    <div class="form-group">
                        <label>Mata Kuliah yang Sudah Lulus</label>
                        <div class="dropdown-mk" id="dropdownWajib">
                            <button type="button" class="form-control dropdown-mk-button" <?= $disabled ? 'disabled' : '' ?>>
                                <span class="dropdown-mk-text">Pilih Mata Kuliah</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-mk-content">
                                <input type="text" class="form-control dropdown-mk-search" placeholder="Cari mata kuliah...">
                                <div class="dropdown-mk-select-all px-3 py-2 border-bottom">
                                    <label class="mb-0 font-weight-bold">
                                        <input type="checkbox" id="pilihSemuaMKWajib" <?= $disabled ? 'disabled' : '' ?>>
                                        Pilih Semua Mata Kuliah
                                    </label>
                                    <small class="d-block text-muted">Hilangkan centang pada mata kuliah yang belum lulus.</small>
                                </div>
                                <div class="dropdown-mk-list">
                                    <?php if (!empty($mataKuliah)): ?>
                                        <?php foreach ($mataKuliah as $mk): ?>
                                            <label class="dropdown-mk-option">
                                                <input
                                                    type="checkbox"
                                                    name="mk_wajib_sudah_lulus[]"
                                                    value="<?= esc($mk['kode_matkul']) ?>"
                                                    data-sks="<?= esc($mk['sks'] ?? 0) ?>"
                                                    data-kategori="<?= esc($mk['kategori'] ?? '') ?>"
                                                    <?= in_array((string) $mk['kode_matkul'], $mkLulusKodeDipilih, true) ? 'checked' : '' ?>
                                                    <?= $disabled ? 'disabled' : '' ?>
                                                >
                                                <span class="d-flex flex-column">
                                                    <span><?= esc($mk['nama_matkul']) ?></span>
                                                    <small class="text-muted"><?= esc($mk['kategori'] ?? '-') ?> · <?= esc($mk['sks'] ?? 0) ?> SKS</small>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted small">Belum ada mata kuliah untuk program studi Anda.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown-mk-footer">
                                    <button type="button" class="btn btn-sm btn-light dropdown-mk-close">Selesai</button>
                                </div>
                            </div>
                        </div>
                        <small class="text-danger d-none" id="errorMKWajib">Kolom ini harus diisi.</small>
                        <small class="text-muted">Pilih mata kuliah Wajib maupun Pilihan yang sudah Anda lulus.</small>
                    </div>
                </div>


                <!-- KANAN: SEMUA MATA KULIAH YANG SEDANG DIAMBIL -->
                <div class="col-lg-6">

                    <div class="form-group">

                        <label>
                            Mata Kuliah yang Sedang Berjalan
                        </label>

                        <div class="dropdown-mk" id="dropdownSedang">

                            <button
                                type="button"
                                class="form-control dropdown-mk-button"
                                <?= $disabled ? 'disabled' : '' ?>
                            >

                                <span class="dropdown-mk-text">
                                    Pilih Mata Kuliah
                                </span>

                                <i class="fas fa-chevron-down"></i>

                            </button>


                            <div class="dropdown-mk-content">

                                <input
                                    type="text"
                                    class="form-control dropdown-mk-search"
                                    placeholder="Cari mata kuliah..."
                                >

                                <div class="dropdown-mk-list">

                                    <?php if (!empty($mataKuliah)): ?>

                                        <?php foreach ($mataKuliah as $mk): ?>

                                            <label class="dropdown-mk-option">

                                                <input
                                                    type="checkbox"
                                                    name="mk_sedang_diambil[]"
                                                    value="<?= esc($mk['kode_matkul']) ?>"
                                                    <?= in_array((string) $mk['kode_matkul'], $mkSedangDipilih, true) ? 'checked' : '' ?>
                                                    <?= $disabled ? 'disabled' : '' ?>
                                                >

                                                <span class="d-flex flex-column">
                                                    <span><?= esc($mk['nama_matkul']) ?></span>
                                                    <small class="text-muted"><?= esc($mk['kategori'] ?? '-') ?> · <?= esc($mk['sks'] ?? 0) ?> SKS</small>
                                                </span>

                                            </label>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <p class="text-muted small">
                                            Belum ada mata kuliah untuk program studi Anda.
                                        </p>

                                    <?php endif; ?>

                                </div>


                                <div class="dropdown-mk-footer">

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-light dropdown-mk-close"
                                    >
                                        Selesai
                                    </button>

                                </div>

                            </div>

                        </div>

                        <small class="text-danger d-none" id="errorMKSedang">
                            Kolom ini harus diisi.
                        </small>

                        <small class="text-muted">
                            Anda dapat memilih lebih dari satu mata kuliah.
                        </small>

                    </div>

                </div>




            </div>


            <!-- DOKUMEN PERSYARATAN -->
            <div class="row mt-4">

                <div class="col-lg-12">

                    <h6 class="font-weight-bold mb-3">
                        Dokumen Persyaratan
                    </h6>

                </div>


                <!-- FILE KHS -->
                <div class="col-lg-12 my-2">

                    <label>
                        File Kartu Hasil Studi (KHS)
                    </label>

                    <?php if ($mahasiswa['file_khs'] == null): ?>

                        <small class="text-muted d-block">
                            Unggah KHS dalam format PDF dengan ukuran maksimal 10 MB.
                        </small>

                        <div class="input-group">

                            <div class="custom-file">

                                <input type="file" class="custom-file-input"
                                id="inputKhs" name="inputKhs">

                                <label
                                    class="custom-file-label"
                                    for="inputKhs"
                                >
                                    Pilih File Kartu Hasil Studi
                                </label>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="input-group">

                            <input
                                type="text"
                                class="form-control"
                                value="<?= esc($mahasiswa['file_khs']) ?>"
                                readonly
                            >

                            <div class="input-group-append">

                                <a
                                    role="button"
                                    class="btn btn-outline-primary"
                                    id="tinjauKhs"
                                    data-npm="<?= esc($mahasiswa['npm']) ?>"
                                >
                                    Tinjau
                                </a>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- FILE KRS -->
                <div class="col-lg-12 my-2">

                    <label>
                        File Kartu Rencana Studi (KRS)
                    </label>

                    <?php if ($mahasiswa['file_krs'] == null): ?>

                        <small class="text-muted d-block">
                            Unggah KRS dalam format PDF dengan ukuran maksimal 10 MB.
                        </small>

                        <div class="input-group">

                            <div class="custom-file">

                                <input type="file" class="custom-file-input"
                                id="inputKrs" name="inputKrs">
                                <label
                                    class="custom-file-label"
                                    for="inputKrs"
                                >
                                    Pilih File Kartu Rencana Studi
                                </label>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="input-group">

                            <input
                                type="text"
                                class="form-control"
                                value="<?= esc($mahasiswa['file_krs']) ?>"
                                readonly
                            >

                            <div class="input-group-append">

                                <a
                                    role="button"
                                    class="btn btn-outline-primary"
                                    id="tinjauKrs"
                                    data-npm="<?= esc($mahasiswa['npm']) ?>"
                                >
                                    Tinjau
                                </a>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- FILE PERSETUJUAN -->
                <div class="col-lg-12 my-2">

                    <label>
                        File Persetujuan Tugas Akhir
                    </label>

                    <?php if ($mahasiswa['file_persetujuan_skripsi'] == null): ?>

                        <small class="text-muted d-block">
                            Unggah persetujuan penyusunan tugas akhir dalam format PDF
                            dengan ukuran maksimal 10 MB.
                        </small>

                        <div class="input-group">

                            <div class="custom-file">

                                <input type="file" class="custom-file-input"
                                id="inputPersetujuanSkripsi" name="inputPersetujuanSkripsi">

                                <label
                                    class="custom-file-label"
                                    for="inputPersetujuanSkripsi"
                                >
                                    Pilih File Persetujuan Tugas Akhir
                                </label>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="input-group">

                            <input
                                type="text"
                                class="form-control"
                                value="<?= esc($mahasiswa['file_persetujuan_skripsi']) ?>"
                                readonly
                            >

                            <div class="input-group-append">

                                <a
                                    role="button"
                                    class="btn btn-outline-primary"
                                    id="tinjauPersetujuan"
                                    data-npm="<?= esc($mahasiswa['npm']) ?>"
                                >
                                    Tinjau
                                </a>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- PANDUAN PEMINDAIAN DOKUMEN -->
                <div class="col-lg-12 my-2">
                    <div class="card border-left-info shadow-sm">
                        <div class="card-header py-2" id="headingPanduanScan">
                            <button
                                class="btn btn-link text-left text-decoration-none p-0"
                                type="button"
                                data-toggle="collapse"
                                data-target="#panduanScanDokumen"
                                aria-expanded="false"
                                aria-controls="panduanScanDokumen"
                            >
                                <i class="fas fa-info-circle mr-2"></i>
                                **Panduan Pemindaian Dokumen**
                                <i class="fas fa-chevron-down ml-2"></i>
                            </button>
                        </div>

                        <div
                            id="panduanScanDokumen"
                            class="collapse"
                            aria-labelledby="headingPanduanScan"
                        >
                            <div class="card-body">
                                <p class="mb-2">
                                    Agar dokumen dapat diperiksa dengan mudah, pastikan hasil pemindaian memenuhi ketentuan berikut:
                                </p>
                                <ul class="mb-3 pl-4">
                                    <li>Gunakan aplikasi pemindai dokumen, seperti CamScanner atau aplikasi sejenis.</li>
                                    <li>Pastikan seluruh bagian dan halaman dokumen terlihat, tidak terpotong, serta tidak miring.</li>
                                    <li>Pastikan tulisan, angka, tanda tangan, dan stempel terlihat jelas serta tidak buram.</li>
                                    <li>Hindari hasil pindai yang gelap, terkena bayangan, atau terlalu silau.</li>
                                    <li>Simpan dokumen dalam format PDF sebelum diunggah.</li>
                                </ul>

                                <p class="font-weight-bold mb-1">Periksa kembali setiap dokumen:</p>
                                <ul class="mb-0 pl-4">
                                    <li><strong>KHS:</strong> seluruh halaman dan nilai mata kuliah dapat dibaca.</li>
                                    <li><strong>KRS:</strong> seluruh halaman dan daftar mata kuliah terlihat jelas.</li>
                                    <li><strong>Persetujuan PA:</strong> isi dokumen serta tanda tangan persetujuan terlihat jelas.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- TOMBOL SIMPAN -->
                <?php if (!$disabled): ?>

                    <div class="col-lg-12 d-flex justify-content-end my-2">

                        <button
                            class="btn btn-primary"
                            type="submit"
                        >
                            Kirim Pengajuan
                        </button>

                    </div>

                <?php endif; ?>

            </div>

        </form>

    </div>

</div>


<!-- MODAL PREVIEW FILE -->
<div
    class="modal fade"
    id="filePreview"
    tabindex="-1"
    role="dialog"
    aria-labelledby="filePreviewLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg" role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="filePreviewLabel"
                ></h5>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <span aria-hidden="true">&times;</span>
                </button>

            </div>


            <div class="modal-body">

                <div class="row">

                    <div class="col-lg-12">

                        <div class="form-group">

                            <div
                                class="embed-responsive embed-responsive-16by9"
                                id="previewContainer"
                            >

                                <iframe
                                    class="embed-responsive-item"
                                    src=""
                                ></iframe>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal"
                    >
                        Kembali
                    </button>


                    <form
                        action=""
                        id="formUnduh"
                        method="post"
                    >

                        <button
                            class="btn btn-primary"
                            type="submit"
                        >
                            Unduh
                        </button>

                    </form>


                    <?php if (!$disabled): ?>

                        <form
                            action=""
                            id="formHapus"
                            method="post"
                        >

                            <button
                                class="btn btn-danger"
                                type="submit"
                            >
                                Hapus
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- MODAL ALASAN PENOLAKAN -->
<div class="modal fade" id="modalAlasanPenolakan" tabindex="-1"
     role="dialog" aria-labelledby="modalAlasanLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalAlasanLabel">
                    Alasan Pengajuan Ditolak
                </h5>

                <button type="button" class="close" data-dismiss="modal"
                        aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <p id="isiAlasanPenolakan" class="mb-0">
                    Memuat alasan penolakan...
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary"
                        data-dismiss="modal">
                    Mengerti
                </button>
            </div>

        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<!-- JAVASCRIPT -->
<?= $this->section("scripts"); ?>

<style>
    /* Tata letak dropdown mata kuliah agar tetap rapi meskipun daftar panjang. */
    .dropdown-mk {
        position: relative;
        width: 100%;
        min-width: 0;
    }
    .dropdown-mk .dropdown-mk-button {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        min-width: 0;
        min-height: 76px;
        height: auto;
        text-align: left;
        white-space: normal;
        overflow: visible;
    }
    .dropdown-mk .dropdown-mk-text {
        display: block;
        flex: 1 1 auto;
        min-width: 0;
        max-height: 64px;
        overflow-y: auto;
        overflow-x: hidden;
        white-space: normal;
        overflow-wrap: anywhere;
        line-height: 1.45;
        padding-right: 5px;
    }
    .dropdown-mk .dropdown-mk-button i {
        flex: 0 0 auto;
    }
    .dropdown-mk .dropdown-mk-content {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        z-index: 1050;
    }
    .dropdown-mk .dropdown-mk-list {
        max-height: 380px;
        overflow-y: auto;
        overflow-x: hidden;
    }
    .dropdown-mk .dropdown-mk-option {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        width: 100%;
        padding: 9px 10px;
        margin: 0;
        white-space: normal;
        overflow-wrap: anywhere;
        cursor: pointer;
    }
    .dropdown-mk .dropdown-mk-option input {
        flex: 0 0 auto;
        margin-top: 4px;
    }
    .dropdown-mk .dropdown-mk-option > span {
        min-width: 0;
        line-height: 1.35;
    }
    .dropdown-mk .dropdown-mk-option small {
        display: block;
        margin-top: 3px;
        font-size: 11px;
    }
    @media (max-width: 991.98px) {
        .dropdown-mk .dropdown-mk-content {
            left: 0;
            right: auto;
        }
    }

    #btnLihatAlasan {
    background-color: #0D6EFD !important;
    color: #FFFFFF !important;
    border: 1px solid #0D6EFD !important;
    padding: 7px 14px;
    font-size: 13px;
    font-weight: 500;
    border-radius: 5px;
}

#btnLihatAlasan:hover {
    background-color: #0B5ED7 !important;
    border-color: #0B5ED7 !important;
    color: #FFFFFF !important;
}
</style>

<script src="<?= base_url("assets/vendor/daterangepicker/daterangepicker.js") ?>"></script>

<script src="<?= base_url("assets/js/mahasiswa/pengajuanPenulisanSkripsi.js") ?>"></script>


<script>
document.addEventListener('DOMContentLoaded', function () {

    // =========================================
    // DROPDOWN MATA KULIAH
    // =========================================

    document.querySelectorAll('.dropdown-mk').forEach(function (dropdown) {

        const button = dropdown.querySelector('.dropdown-mk-button');
        const search = dropdown.querySelector('.dropdown-mk-search');
        const closeButton = dropdown.querySelector('.dropdown-mk-close');

        if (!button) return;


        // Membuka dropdown.
        button.addEventListener('click', function (e) {

            e.stopPropagation();

            document.querySelectorAll('.dropdown-mk').forEach(function (other) {

                if (other !== dropdown) {
                    other.classList.remove('active');
                }

            });

            dropdown.classList.toggle('active');

            if (search && dropdown.classList.contains('active')) {
                search.focus();
            }

        });


        // Menutup dropdown.
        if (closeButton) {

            closeButton.addEventListener('click', function () {
                dropdown.classList.remove('active');
            });

        }


        // Pencarian mata kuliah.
        if (search) {

            search.addEventListener('input', function () {

                const keyword = this.value.toLowerCase();

                dropdown.querySelectorAll('.dropdown-mk-option').forEach(function (option) {

                    const nama = option.textContent.toLowerCase();

                    option.style.display = nama.includes(keyword) ? 'flex' : 'none';

                });

            });

        }


        // Perubahan pilihan.
        dropdown.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                updateDropdownText(dropdown);
                if (dropdown.id === 'dropdownWajib') updateTotalSKS();
            });
        });

        updateDropdownText(dropdown);

    });


    // Pilih semua mata kuliah pada daftar yang sudah lulus.
    const pilihSemuaMK = document.getElementById('pilihSemuaMKWajib');
    const dropdownLulus = document.getElementById('dropdownWajib');

    if (pilihSemuaMK && dropdownLulus) {
        const daftarMKLulus = dropdownLulus.querySelectorAll(
            '.dropdown-mk-list .dropdown-mk-option input[type="checkbox"]'
        );

        function perbaruiStatusPilihSemua() {
            const aktif = Array.from(daftarMKLulus).filter(function (item) {
                return !item.disabled;
            });
            pilihSemuaMK.checked = aktif.length > 0 && aktif.every(function (item) {
                return item.checked;
            });
            pilihSemuaMK.indeterminate = aktif.some(function (item) {
                return item.checked;
            }) && !pilihSemuaMK.checked;
        }

        pilihSemuaMK.addEventListener('change', function () {
            daftarMKLulus.forEach(function (item) {
                if (!item.disabled) item.checked = this.checked;
            }, this);
            updateDropdownText(dropdownLulus);
            updateTotalSKS();
            if (pilihSemuaMK.checked) {
                const error = document.getElementById('errorMKWajib');
                if (error) error.classList.add('d-none');
            }
        });

        daftarMKLulus.forEach(function (item) {
            item.addEventListener('change', perbaruiStatusPilihSemua);
        });
        perbaruiStatusPilihSemua();
    }

    // =========================================
    // UPDATE TEKS DROPDOWN
    // =========================================

    function updateDropdownText(dropdown) {

        const selected = Array.from(
            dropdown.querySelectorAll('.dropdown-mk-option input:checked')
        ).map(function (checkbox) {
            // Value checkbox tetap kode_matkul untuk proses penyimpanan.
            // Yang ditampilkan pada tombol dropdown adalah nama mata kuliah.
            const label = checkbox.closest('.dropdown-mk-option');
            const namaMK = label ? label.querySelector('span span') : null;
            return namaMK ? namaMK.textContent.trim() : checkbox.value;
        });

        const text = dropdown.querySelector('.dropdown-mk-text');

        if (!text) return;

        // Pertahankan teks jika mahasiswa memilih opsi sudah lulus semua.
        const checkboxLulusSemua = document.getElementById('sudahLulusSemua');

        if (dropdown.id === 'dropdownWajib' && checkboxLulusSemua && checkboxLulusSemua.checked) {
            text.textContent = 'Sudah lulus semua';
        } else if (selected.length === 0) {
            text.textContent = 'Pilih Mata Kuliah';
        } else {
            // Tampilkan nama mata kuliah yang dipilih; area tombol dapat digeser vertikal.
            text.textContent = selected.join(', ');
            text.title = selected.join(', ');
        }

    }
    // Hitung total SKS dari mata kuliah yang sudah lulus.
    function updateTotalSKS() {
        let total = 0;
        document.querySelectorAll('#dropdownWajib input[type="checkbox"]:checked').forEach(function (checkbox) {
            total += parseInt(checkbox.dataset.sks || '0', 10);
        });
        const inputSKS = document.getElementById('sks_lulus');
        if (inputSKS) inputSKS.value = total;
    }

    updateTotalSKS();

    // BINDING STATE JENIS TUGAS AKHIR

    const jenisTA = document.getElementById('jenis_ta');

    let selectedJenisTA = jenisTA ? jenisTA.value : '';

    if (jenisTA) {
        jenisTA.addEventListener('change', function () {
        selectedJenisTA = this.value;
        console.log('Jenis TA dipilih:', selectedJenisTA);
        });
    }
    


    // =========================================
    // TUTUP DROPDOWN KETIKA KLIK DI LUAR
    // =========================================

    document.addEventListener('click', function (e) {

        if (!e.target.closest('.dropdown-mk')) {

            document.querySelectorAll('.dropdown-mk').forEach(function (dropdown) {
                dropdown.classList.remove('active');
            });

        }

    });


    // =========================================
    // LABEL FILE UPLOAD
    // =========================================

    document.querySelectorAll('.custom-file-input').forEach(function (input) {

        input.addEventListener('change', function () {

            const fileName = this.files[0]
                ? this.files[0].name
                : 'Pilih File';

            const label = this.nextElementSibling;

            if (label) {
                label.textContent = fileName;
            }

        });

    });

    // Hilangkan pesan Jenis TA setelah pengguna memilih.
    if (jenisTA && errorJenisTA) {
        jenisTA.addEventListener('change', function () {
            if (this.value) errorJenisTA.classList.add('d-none');
        });
    }

    // Hilangkan pesan Mata Kuliah Wajib setelah ada pilihan.
    document.querySelectorAll('#dropdownWajib .dropdown-mk-option input').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            if (document.querySelectorAll('#dropdownWajib .dropdown-mk-option input:checked').length > 0) {
                errorMKWajib.classList.add('d-none');
            }
        });
    });

    // Hilangkan pesan Mata Kuliah Sedang Berjalan setelah ada pilihan.
    document.querySelectorAll('#dropdownSedang .dropdown-mk-option input').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            if (getSedangDipilih().length > 0) errorMKSedang.classList.add('d-none');
        });
    });


    // =========================================
    // AMBIL ALASAN PENOLAKAN DARI API
    // =========================================

    const btnLihatAlasan = document.getElementById('btnLihatAlasan');
    const isiAlasanPenolakan = document.getElementById('isiAlasanPenolakan');

    if (btnLihatAlasan) {
        btnLihatAlasan.addEventListener('click', async function () {

            isiAlasanPenolakan.textContent = 'Memuat alasan penolakan...';

            $('#modalAlasanPenolakan').modal('show');

            try {
                const response = await fetch(
                    '<?= base_url("api/mahasiswa/alasan-penolakan") ?>',
                    {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    }
                );

                const hasil = await response.json();

                if (!response.ok || hasil.status !== 'success') {
                    throw new Error(
                        hasil.message || 'Gagal mengambil alasan penolakan.'
                    );
                }

                isiAlasanPenolakan.textContent =
                    hasil.data.alasan_penolakan || 'Tidak ada alasan penolakan.';

            } catch (error) {
                console.error('Error alasan penolakan:', error);

                isiAlasanPenolakan.textContent =
                    'Alasan penolakan gagal dimuat. Silakan coba kembali.';
            }
        });
    }


});
</script>


<?= $this->endSection(); ?>