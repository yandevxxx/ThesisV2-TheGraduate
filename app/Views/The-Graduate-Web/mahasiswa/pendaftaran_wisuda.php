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
use App\Models\SemesterGraduateModel;

$pendaftaran      = $pendaftaran ?? null;
$prefill          = $prefill ?? [];
$persyaratanAktif = $persyaratanAktif ?? [];
$berkasMap        = $berkasMap ?? [];
$daftarDosen      = $daftarDosen ?? [];
$gelombangWisuda  = $gelombangWisuda ?? [];
$daftarSemester   = $daftarSemester ?? [];
$daftarJenisTugasAkhir = $daftarJenisTugasAkhir ?? ['Skripsi', 'Publikasi Artikel Ilmiah', 'Proyek Akhir'];

$statusVerifikasi = strtoupper((string) ($pendaftaran['status_verifikasi'] ?? ''));
$isDraft          = ($pendaftaran === null || $statusVerifikasi === 'DRAFT' || $statusVerifikasi === '');
$isSubmitted      = ($pendaftaran !== null && $statusVerifikasi !== 'DRAFT');
$isApproved       = ($statusVerifikasi === 'DISETUJUI');
$isRejected       = ($statusVerifikasi === 'DITOLAK');
$isPending        = ($statusVerifikasi === 'MENUNGGU');

$daftarJenisKelamin = ['Laki-laki', 'Perempuan'];

$formatDate = static function (?string $date): string {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $time = strtotime($date);
    return ($time !== false && $time > 0) ? date('d/m/Y', $time) : '-';
};
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Pendaftaran Wisuda</h1>
</div>

<!-- Banner Status Verifikasi -->
<?php if ($isDraft && !empty($berkasMap)): ?>
    <div class="alert alert-info border-left-info shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-2x mr-3 text-info"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Dokumen Berhasil Disimpan (Status: Draft)</h5>
                <p class="mb-0">Anda telah mengunggah <?= count($berkasMap) ?> berkas persyaratan. Harap lengkapi biodata pada <strong>Formulir Pendaftaran Wisuda</strong> di bawah, lalu klik tombol <strong>Kirim Pendaftaran Wisuda</strong> agar pendaftaran Anda masuk ke antrean verifikasi Tendik/Akademik.</p>
            </div>
        </div>
    </div>
<?php elseif ($isPending): ?>
    <div class="alert alert-warning border-left-warning shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-hourglass-half fa-2x mr-3 text-warning"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Pendaftaran Sedang Menunggu Verifikasi</h5>
                <p class="mb-0">Formulir pendaftaran wisuda Anda telah berhasil dikirim dan sedang dalam proses verifikasi oleh tim akademik/tendik. Anda tetap dapat melengkapi atau memeriksa dokumen persyaratan di bawah ini.</p>
            </div>
        </div>
    </div>
<?php elseif ($isApproved): ?>
    <div class="alert alert-success border-left-success shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x mr-3 text-success"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Selamat! Pendaftaran Wisuda Anda Telah Disetujui</h5>
                <p class="mb-0">Seluruh data dan berkas pendaftaran wisuda Anda telah dinyatakan valid dan memenuhi persyaratan. Silakan pantau pengumuman jadwal gladi dan pelaksanaan wisuda.</p>
            </div>
        </div>
    </div>
<?php elseif ($isRejected): ?>
    <div class="alert alert-danger border-left-danger shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-times-circle fa-2x mr-3 text-danger"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Pendaftaran Wisuda Ditolak</h5>
                <p class="mb-1"><strong>Alasan / Catatan Perbaikan:</strong> <?= esc($pendaftaran['catatan_verifikasi'] ?? 'Data atau berkas belum memenuhi syarat.') ?></p>
                <small class="text-gray-700">Silakan perbaiki data pada formulir di bawah ini dan unggah kembali berkas yang diminta, lalu klik tombol <strong>Perbarui Pendaftaran</strong>.</small>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Tampilan Ringkasan (Readonly) jika status MENUNGGU atau DISETUJUI -->
<?php if ($isPending || $isApproved): ?>
<div class="card shadow mb-4" id="cardRingkasan">
    <div class="card-header py-3 d-sm-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-id-card mr-1"></i> Data Pendaftaran Wisuda Anda</h6>
        <div>
            <?php if ($isApproved): ?>
                <span class="badge badge-success px-3 py-2"><i class="fas fa-check-circle mr-1"></i> Disetujui</span>
            <?php else: ?>
                <span class="badge badge-warning px-3 py-2 text-dark"><i class="fas fa-hourglass-half mr-1"></i> Menunggu Verifikasi</span>
                <button type="button" class="btn btn-outline-primary btn-sm ml-2" id="btnBukaEdit">
                    <i class="fas fa-edit mr-1"></i> Ubah Data
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-3 col-md-4 text-center mb-4 mb-md-0 d-flex flex-column align-items-center">
                <?php 
                $fotoSrc = !empty($pendaftaran['pas_foto']) ? base_url('graduate/berkas/view/' . basename($pendaftaran['pas_foto'])) : '';
                // Fallback otomatis jika belum tersimpan di field pas_foto tapi ada di berkas persyaratan
                if ($fotoSrc === '' && !empty($berkasMap)) {
                    foreach ($berkasMap as $namaSyarat => $berkas) {
                        if (stripos($namaSyarat, 'foto') !== false && !empty($berkas['nama_file'])) {
                            $ext = strtolower(pathinfo($berkas['nama_file'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                                $fotoSrc = base_url('graduate/berkas/view/' . $berkas['nama_file']);
                                break;
                            }
                        }
                    }
                }
                $isRealFoto = ($fotoSrc !== '');
                if (! $isRealFoto) {
                    $fotoSrc = base_url('assets/img/undraw_profile.svg');
                }
                ?>
                <div class="card p-2 shadow-sm border bg-light w-100" style="max-width: 260px;">
                    <div class="position-relative overflow-hidden rounded bg-white border" style="aspect-ratio: 3/4; width: 100%; min-height: 280px; max-height: 340px;">
                        <img src="<?= $fotoSrc ?>" alt="Pas Foto Mahasiswa" class="w-100 h-100" style="object-fit: cover; object-position: center top;">
                        <?php if ($isRealFoto): ?>
                            <a href="<?= $fotoSrc ?>" target="_blank" class="btn btn-sm btn-dark position-absolute shadow-sm" style="bottom: 8px; right: 8px; opacity: 0.85;" title="Lihat Foto Ukuran Asli">
                                <i class="fas fa-search-plus"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 text-center">
                        <span class="badge badge-primary px-3 py-2">
                            <i class="fas fa-camera mr-1"></i> Pas Foto Resmi Wisuda
                        </span>
                        <small class="d-block text-muted font-italic mt-1">Ukuran standar pas foto 3x4 / 4x6</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-9 col-md-8">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light"><tr><th colspan="2">Biodata Mahasiswa</th></tr></thead>
                            <tbody>
                                <tr><th style="width: 40%;">NPM</th><td><?= esc($pendaftaran['npm']) ?></td></tr>
                                <tr><th>Nama Mahasiswa</th><td class="font-weight-bold"><?= esc($pendaftaran['nama']) ?></td></tr>
                                <tr><th>NIK</th><td><?= esc($pendaftaran['nik'] ?? '-') ?></td></tr>
                                <tr><th>Tempat, Tgl Lahir</th><td><?= esc($pendaftaran['tempat_lahir'] ?? '-') ?>, <?= esc($formatDate($pendaftaran['tanggal_lahir'] ?? null)) ?></td></tr>
                                <tr><th>Jenis Kelamin</th><td><?= esc($pendaftaran['jenis_kelamin'] ?? '-') ?></td></tr>
                                <tr><th>No. Handphone</th><td><?= esc($pendaftaran['no_hp'] ?? '-') ?></td></tr>
                                <tr><th>E-mail</th><td><?= esc($pendaftaran['email'] ?? '-') ?></td></tr>
                                <tr><th>Alamat</th><td><?= esc($pendaftaran['alamat'] ?? '-') ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light"><tr><th colspan="2">Data Akademik & Skripsi</th></tr></thead>
                            <tbody>
                                <tr><th style="width: 40%;">Program Studi</th><td><?= esc($pendaftaran['prodi'] ?? '-') ?></td></tr>
                                <tr><th>Jenis Tugas Akhir</th><td><span class="badge badge-info px-2 py-1"><?= esc($pendaftaran['jenis_tugas_akhir'] ?? 'Skripsi') ?></span></td></tr>
                                <tr><th>Total SKS</th><td><?= esc($pendaftaran['total_sks'] ?? '-') ?></td></tr>
                                <tr><th>IPK</th><td><?= esc($pendaftaran['ipk'] ?? '-') ?></td></tr>
                                <tr><th>Tanggal Yudisium</th><td><?= esc($formatDate($pendaftaran['tanggal_lulus'] ?? null)) ?></td></tr>
                                <tr><th>Gelombang Wisuda</th><td><?= esc($pendaftaran['gelombang_wisuda'] ?? '-') ?></td></tr>
                                <tr><th>Pembimbing 1</th><td><?= esc($pendaftaran['pembimbing1'] ?? '-') ?></td></tr>
                                <tr><th>Pembimbing 2</th><td><?= esc($pendaftaran['pembimbing2'] ?? '-') ?></td></tr>
                                <tr><th>Pembimbing Agama</th><td><?= esc($pendaftaran['pembimbing_agama'] ?? '-') ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="card border-left-primary shadow-sm">
                            <div class="card-body py-2">
                                <div class="small font-weight-bold text-primary text-uppercase mb-1">Judul <?= esc($pendaftaran['jenis_tugas_akhir'] ?? 'Tugas Akhir') ?></div>
                                <div class="h6 mb-0 text-gray-800 text-uppercase font-weight-bold"><?= esc(mb_strtoupper((string) ($pendaftaran['judul_skripsi'] ?? '-'), 'UTF-8')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Form Pendaftaran Wisuda (Aktif jika belum daftar, ditolak, atau klik Ubah Data) -->
<div class="card shadow mb-4 <?= ($isPending || $isApproved) ? 'd-none' : '' ?>" id="cardFormPendaftaran">
    <div class="card-header py-3 d-sm-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-edit mr-1"></i> <?= $isRejected ? 'Perbarui Formulir Pendaftaran Wisuda' : 'Formulir Pendaftaran Wisuda' ?>
        </h6>
        <?php if ($isPending): ?>
            <button type="button" class="btn btn-secondary btn-sm" id="btnTutupEdit">
                <i class="fas fa-arrow-left mr-1"></i> Batal Edit & Lihat Ringkasan
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form action="<?= base_url('graduate/pendaftaranWisudaSubmit') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-row">

                <!-- NPM (otomatis dari akun) -->
                <div class="form-group col-md-6">
                    <label for="npm" class="font-weight-bold">NPM <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="npm" name="npm" value="<?= esc($prefill['npm'] ?? '') ?>" readonly>
                </div>

                <!-- Nama Mahasiswa (otomatis dari akun) -->
                <div class="form-group col-md-6">
                    <label for="nama" class="font-weight-bold">Nama Mahasiswa <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= esc($prefill['nama'] ?? '') ?>" readonly>
                </div>

                <!-- NIK (Wajib Tepat 16 Digit Angka) -->
                <div class="form-group col-md-6">
                    <label for="nik" class="font-weight-bold">NIK (Nomor Induk Kependudukan) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nik" name="nik" 
                           value="<?= esc($prefill['nik'] ?? '') ?>" 
                           placeholder="16 digit NIK sesuai KTP" 
                           minlength="16" maxlength="16" pattern="[0-9]{16}" 
                           title="NIK harus terdiri dari tepat 16 digit angka" 
                           inputmode="numeric" required>
                    <small class="form-text text-muted" id="nikCounter"><i class="fas fa-id-card mr-1"></i> Harus tepat 16 digit angka.</small>
                </div>

                <!-- Tempat Lahir -->
                <div class="form-group col-md-3 col-sm-6">
                    <label for="tempatLahir" class="font-weight-bold">Tempat Lahir <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="tempatLahir" name="tempatLahir" value="<?= esc($prefill['tempatLahir'] ?? '') ?>" placeholder="Kota kelahiran" required>
                </div>

                <!-- Tanggal Lahir -->
                <div class="form-group col-md-3 col-sm-6">
                    <label for="tanggalLahir" class="font-weight-bold">Tanggal Lahir <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="tanggalLahir" name="tanggalLahir" value="<?= esc($prefill['tanggalLahir'] ?? '') ?>" required>
                </div>

                <!-- Jenis Kelamin -->
                <div class="form-group col-md-6">
                    <label for="jenisKelamin" class="font-weight-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select class="form-control" id="jenisKelamin" name="jenisKelamin" required>
                        <?php foreach ($daftarJenisKelamin as $jk): ?>
                        <option value="<?= esc($jk) ?>" <?= (($prefill['jenisKelamin'] ?? '') === $jk) ? 'selected' : '' ?>><?= esc($jk) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Program Studi -->
                <div class="form-group col-md-6">
                    <label for="prodi" class="font-weight-bold">Program Studi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="prodi" name="prodi" value="<?= esc($prefill['prodi'] ?? '') ?>" required>
                </div>

                <!-- Alamat -->
                <div class="form-group col-md-12">
                    <label for="alamat" class="font-weight-bold">Alamat Lengkap (KTP / Domisili) <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota/kabupaten" required><?= esc($prefill['alamat'] ?? '') ?></textarea>
                </div>

                <!-- No Handphone -->
                <div class="form-group col-md-6">
                    <label for="noHp" class="font-weight-bold">No. Handphone (WhatsApp) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="noHp" name="noHp" value="<?= esc($prefill['noHp'] ?? '') ?>" placeholder="08xxxxxxxxxx" required>
                </div>

                <!-- No Telepon -->
                <div class="form-group col-md-6">
                    <label for="noTelepon" class="font-weight-bold">No. Telepon Rumah / Alternatif</label>
                    <input type="text" class="form-control" id="noTelepon" name="noTelepon" value="<?= esc($prefill['noTelepon'] ?? '') ?>" placeholder="Nomor telepon lainnya">
                </div>

                <!-- E-mail -->
                <div class="form-group col-md-6">
                    <label for="email" class="font-weight-bold">E-mail Aktif <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= esc($prefill['email'] ?? '') ?>" required>
                </div>

                <!-- Total SKS -->
                <div class="form-group col-md-3 col-sm-6">
                    <label for="totalSks" class="font-weight-bold">Total SKS Lulus <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="totalSks" name="totalSks" value="<?= esc($prefill['totalSks'] ?? '144') ?>" required>
                </div>

                <!-- IPK -->
                <div class="form-group col-md-3 col-sm-6">
                    <label for="ipk" class="font-weight-bold">IPK Terakhir <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="2.00" max="4.00" class="form-control" id="ipk" name="ipk" value="<?= esc($prefill['ipk'] ?? '3.50') ?>" required>
                </div>

                <!-- Semester Kelulusan -->
                <div class="form-group col-md-6">
                    <label for="kode_semester" class="font-weight-bold">Semester Kelulusan <span class="text-danger">*</span></label>
                    <select class="form-control" id="kode_semester" name="kode_semester" required>
                        <?php foreach ($daftarSemester as $sem): 
                            $labelSem = SemesterGraduateModel::formatNama($sem['nama'] ?? '', $sem['kode'] ?? '');
                        ?>
                        <option value="<?= esc($sem['kode']) ?>" <?= (($prefill['kode_semester'] ?? '') === $sem['kode']) ? 'selected' : '' ?>>
                            <?= esc($labelSem) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tanggal Yudisium (Otomatis dari SK Yudisium - Readonly) -->
                <div class="form-group col-md-6">
                    <label for="tanggalLulus" class="font-weight-bold">Tanggal Yudisium <span class="text-danger">*</span></label>
                    <input type="date" class="form-control bg-light" id="tanggalLulus" name="tanggalLulus" readonly value="<?= esc($prefill['tanggalLulus'] ?? '') ?>" required>
                    <small class="form-text text-muted"><i class="fas fa-lock mr-1 text-secondary"></i> Tanggal resmi sesuai penetapan SK Yudisium oleh Fakultas/Tendik.</small>
                </div>

                <!-- Tanggal Wisuda (gelombang) -->
                <div class="form-group col-md-6">
                    <label for="gelombangWisuda" class="font-weight-bold">Jadwal / Gelombang Wisuda <span class="text-danger">*</span></label>
                    <select class="form-control" id="gelombangWisuda" name="gelombangWisuda" required>
                        <?php foreach ($gelombangWisuda as $nilai => $label): ?>
                        <option value="<?= esc($nilai) ?>" <?= (($prefill['gelombangWisuda'] ?? '') === $nilai) ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Pas Foto -->
                <div class="form-group col-md-6">
                    <label for="pas_foto" class="font-weight-bold">Pas Foto Resmi Wisuda <span class="small text-muted">(JPG/PNG, maks. 2MB)</span></label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="pas_foto" name="pas_foto" accept=".jpg,.jpeg,.png">
                        <label class="custom-file-label" for="pas_foto">Pilih pas foto resmi...</label>
                    </div>
                </div>

                <!-- Pemilihan Jenis Tugas Akhir -->
                <div class="form-group col-md-6">
                    <label for="jenis_tugas_akhir" class="font-weight-bold">Pemilihan Tugas Akhir <span class="text-danger">*</span></label>
                    <select class="form-control" id="jenis_tugas_akhir" name="jenis_tugas_akhir" required>
                        <?php foreach ($daftarJenisTugasAkhir as $jta): ?>
                        <option value="<?= esc($jta) ?>" <?= (($prefill['jenisTugasAkhir'] ?? 'Skripsi') === $jta) ? 'selected' : '' ?>>
                            <?= esc($jta) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Pembimbing Ilmu 1 -->
                <div class="form-group col-md-6">
                    <label for="pembimbing1" class="font-weight-bold">Pembimbing Ilmu 1 <span class="text-danger">*</span></label>
                    <select class="form-control pilih-dosen" id="pembimbing1" name="pembimbing1" required>
                        <option value="">Pilih dosen pembimbing 1...</option>
                        <?php foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= (($prefill['pembimbing1'] ?? '') === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                        <?php if (!empty($prefill['pembimbing1']) && !in_array($prefill['pembimbing1'], $daftarDosen)): ?>
                        <option value="<?= esc($prefill['pembimbing1']) ?>" selected><?= esc($prefill['pembimbing1']) ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Pembimbing Ilmu 2 -->
                <div class="form-group col-md-6">
                    <label for="pembimbing2" class="font-weight-bold">Pembimbing Ilmu 2</label>
                    <select class="form-control pilih-dosen" id="pembimbing2" name="pembimbing2">
                        <option value="">Pilih dosen pembimbing 2 (opsional)...</option>
                        <?php foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= (($prefill['pembimbing2'] ?? '') === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                        <?php if (!empty($prefill['pembimbing2']) && !in_array($prefill['pembimbing2'], $daftarDosen)): ?>
                        <option value="<?= esc($prefill['pembimbing2']) ?>" selected><?= esc($prefill['pembimbing2']) ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Pembimbing Agama -->
                <div class="form-group col-md-6">
                    <label for="pembimbingAgama" class="font-weight-bold">Pembimbing Agama</label>
                    <select class="form-control pilih-dosen" id="pembimbingAgama" name="pembimbingAgama">
                        <option value="">Pilih dosen pembimbing agama...</option>
                        <?php foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= (($prefill['pembimbingAgama'] ?? '') === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                        <?php if (!empty($prefill['pembimbingAgama']) && !in_array($prefill['pembimbingAgama'], $daftarDosen)): ?>
                        <option value="<?= esc($prefill['pembimbingAgama']) ?>" selected><?= esc($prefill['pembimbingAgama']) ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Dosen Penguji -->
                <div class="form-group col-md-6">
                    <label for="dosenPenguji" class="font-weight-bold">Dosen Penguji Sidang</label>
                    <select class="form-control" id="dosenPenguji" name="dosenPenguji">
                        <option value="">Pilih dosen penguji...</option>
                        <?php foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= (($prefill['dosenPenguji'] ?? '') === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Judul Skripsi / Tugas Akhir (1 Baris & Full Kapital) -->
                <div class="form-group col-md-12">
                    <label for="judulSkripsi" class="font-weight-bold" id="labelJudulTa">Judul Tugas Akhir / Skripsi / Artikel Ilmiah <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" id="judulSkripsi" name="judulSkripsi" 
                           value="<?= esc(strtoupper((string) ($prefill['judulSkripsi'] ?? ''))) ?>" 
                           placeholder="JUDUL LENGKAP SESUAI NASKAH FINAL (HURUF BESAR/KAPITAL)" 
                           style="text-transform: uppercase;" required>
                    <small class="form-text text-muted"><i class="fas fa-info-circle mr-1 text-primary"></i> Judul harus <strong>FULL KAPITAL</strong> dan ditulis dalam <strong>1 baris</strong> (tidak dapat dienter ke bawah).</small>
                </div>

                <!-- Tombol Submit -->
                <div class="form-group col-md-12 text-right mt-3">
                    <button type="submit" class="btn btn-success px-4" id="btnSubmitForm">
                        <i class="fas fa-paper-plane fa-fw mr-1"></i> <?= $isRejected ? 'Perbarui Pendaftaran Wisuda' : 'Kirim Pendaftaran Wisuda' ?>
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- Card Dokumen Persyaratan Wisuda (Selalu Tampil) -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-sm-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-folder-open mr-1"></i> Dokumen Persyaratan Wisuda</h6>
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
                        data-status="<?= $sudahUpload ? 'sudah' : 'belum' ?>">
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
    </div>
</div>

<!-- Modal Upload Dokumen Persyaratan -->
<div class="modal fade" id="modalUploadDokumen" tabindex="-1" role="dialog" aria-labelledby="modalUploadDokumenLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalUploadDokumenLabel"><i class="fas fa-upload mr-2"></i> Unggah Dokumen</h5>
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
                if (newHash) {
                    currentCsrfHash = newHash;
                }
                if (newToken) {
                    currentCsrfToken = newToken;
                }
                var effectiveHash = getLatestCsrfHash();
                $('input[name="' + currentCsrfToken + '"]').val(effectiveHash);
            }

            // Update custom file label on change
            $(document).on('change', '.custom-file-input', function () {
                var fileName = this.files.length ? this.files[0].name : 'Pilih file...';
                $(this).next('.custom-file-label').text(fileName);
            });

            // Sinkronisasi label judul tugas akhir sesuai pilihan jenis
            $('#jenis_tugas_akhir').on('change', function () {
                var val = $(this).val();
                $('#labelJudulTa').html('Judul ' + val + ' <span class="text-danger">*</span>');
                $('#judulSkripsi').attr('placeholder', ('JUDUL ' + val + ' LENGKAP SESUAI NASKAH FINAL').toUpperCase());
            });

            // Auto uppercase & cegah baris baru / enter pada judul
            $('#judulSkripsi').on('input', function () {
                var pos = this.selectionStart;
                this.value = this.value.toUpperCase();
                this.setSelectionRange(pos, pos);
            }).on('keydown', function (e) {
                if (e.which === 13 || e.keyCode === 13) {
                    e.preventDefault();
                    return false;
                }
            });

            // Filter dan validasi real-time NIK (tepat 16 digit angka)
            $('#nik').on('input', function () {
                var clean = this.value.replace(/\D/g, '');
                if (clean.length > 16) {
                    clean = clean.slice(0, 16);
                }
                this.value = clean;

                var len = clean.length;
                if (len === 16) {
                    $('#nikCounter').html('<span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> NIK valid (tepat 16 digit).</span>');
                } else if (len > 0) {
                    $('#nikCounter').html('<span class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i> ' + len + '/16 digit (harus tepat 16 digit angka).</span>');
                } else {
                    $('#nikCounter').html('<i class="fas fa-id-card mr-1"></i> Harus tepat 16 digit angka.');
                }
            });

            if ($('#nik').val()) {
                $('#nik').trigger('input');
            }

            // Toggle view edit vs ringkasan
            $('#btnBukaEdit').on('click', function () {
                $('#cardRingkasan').addClass('d-none');
                $('#cardFormPendaftaran').removeClass('d-none');
                $('html, body').animate({ scrollTop: $('#cardFormPendaftaran').offset().top - 70 }, 'slow');
            });

            $('#btnTutupEdit').on('click', function () {
                $('#cardFormPendaftaran').addClass('d-none');
                $('#cardRingkasan').removeClass('d-none');
                $('html, body').animate({ scrollTop: $('#cardRingkasan').offset().top - 70 }, 'slow');
            });

            // Upload Dokumen Persyaratan via Modal
            var $activeRow = null;

            $(document).on('click', '.upload-dokumen-trigger', function () {
                $activeRow = $(this).closest('tr');
                var persyaratanId   = $activeRow.data('persyaratan-id');
                var namaPersyaratan = $activeRow.data('nama-persyaratan');

                $('#modalPersyaratanId').val(persyaratanId);
                $('#modalNamaPersyaratan').val(namaPersyaratan);
                $('#uploadNamaDokumen').text(namaPersyaratan);
                $('#fileDokumenInput').val('').next('.custom-file-label').text('Pilih file berkas...');
                $('#uploadProgressBar').addClass('d-none');

                $('#modalUploadDokumen').modal('show');
            });

            // Submit upload via AJAX
            $('#formUploadModal').on('submit', function (e) {
                e.preventDefault();

                var fileInput = $('#fileDokumenInput')[0];
                if (!fileInput.files.length) {
                    alert('Silakan pilih berkas terlebih dahulu.');
                    return;
                }

                var selectedFile = fileInput.files[0];
                if (selectedFile.size > 10 * 1024 * 1024) {
                    var errorMsg = 'Ukuran berkas melebihi 10 MB (' + (selectedFile.size / (1024 * 1024)).toFixed(1) + ' MB). Harap kompres berkas Anda terlebih dahulu.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Ukuran Berkas Terlalu Besar',
                            text: errorMsg
                        });
                    } else {
                        alert(errorMsg);
                    }
                    return;
                }

                // Perbarui token CSRF pada form sebelum FormData dibuat
                updateCsrf();
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
                    success: function (res) {
                        $('#uploadProgressBar').addClass('d-none');
                        $('#btnSimpanDokumen').prop('disabled', false);
                        $('#modalUploadDokumen').modal('hide');

                        if (res.csrf_hash) {
                            updateCsrf(res.csrf_token, res.csrf_hash);
                        } else {
                            updateCsrf();
                        }

                        if (res.success) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'success',
                                    title: res.message,
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                            }

                            // Update active row
                            if ($activeRow) {
                                $activeRow.attr('data-status', 'sudah').data('status', 'sudah');
                                $activeRow.attr('data-berkas-id', res.berkas_id).data('berkas-id', res.berkas_id);

                                $activeRow.find('.status-cell').html('<span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Diunggah</span>');

                                var aksiHtml = '<a href="' + res.url + '" target="_blank" class="btn btn-sm btn-info py-1 px-2" title="Lihat Berkas"><i class="fas fa-eye"></i></a> ' +
                                    '<button type="button" class="btn btn-sm btn-warning py-1 px-2 upload-dokumen-trigger ml-1" title="Ganti File"><i class="fas fa-sync-alt"></i></button> ' +
                                    '<button type="button" class="btn btn-sm btn-danger py-1 px-2 hapus-dokumen-trigger ml-1" title="Hapus Berkas"><i class="fas fa-trash"></i></button>';

                                $activeRow.find('.aksi-cell').html(aksiHtml);
                            }
                        } else {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal Mengunggah',
                                    text: res.message || 'Terjadi kesalahan saat mengunggah berkas.'
                                });
                            } else {
                                alert(res.message || 'Gagal mengunggah berkas.');
                            }
                        }
                    },
                    error: function (xhr) {
                        $('#uploadProgressBar').addClass('d-none');
                        $('#btnSimpanDokumen').prop('disabled', false);
                        updateCsrf();

                        var errTitle = 'Gagal Mengunggah';
                        var errMsg   = 'Terjadi kesalahan saat menghubungi server.';

                        if (xhr.status === 403) {
                            errTitle = 'Sesi Keamanan Diperbarui';
                            errMsg   = 'Sesi keamanan (CSRF) telah disegarkan oleh server. Silakan klik tombol Upload sekali lagi untuk melanjutkan.';
                        } else if (xhr.status === 413) {
                            errTitle = 'Ukuran Berkas Terlalu Besar';
                            errMsg   = 'Ukuran berkas melebihi batas upload maksimal server (HTTP 413 Payload Too Large).';
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg   = xhr.responseJSON.message;
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: errTitle,
                                text: errMsg
                            });
                        } else {
                            alert(errTitle + ': ' + errMsg);
                        }
                    }
                });
            });

            // Hapus berkas via AJAX
            $(document).on('click', '.hapus-dokumen-trigger', function () {
                var $row = $(this).closest('tr');
                var berkasId = $row.data('berkas-id');
                var nama = $row.data('nama-persyaratan');

                if (!berkasId) return;

                var lakukanHapus = function () {
                    updateCsrf();
                    var postData = {
                        berkas_id: berkasId
                    };
                    postData[currentCsrfToken] = getLatestCsrfHash();

                    $.ajax({
                        url: '<?= base_url('graduate/pendaftaranWisudaHapusBerkas') ?>/' + berkasId,
                        type: 'POST',
                        data: postData,
                        success: function (res) {
                            if (res.csrf_hash) {
                                updateCsrf(res.csrf_token, res.csrf_hash);
                            } else {
                                updateCsrf();
                            }

                            if (res.success) {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        toast: true,
                                        position: 'top-end',
                                        icon: 'success',
                                        title: res.message,
                                        showConfirmButton: false,
                                        timer: 3000
                                    });
                                }

                                $row.attr('data-status', 'belum').data('status', 'belum');
                                $row.attr('data-berkas-id', '').data('berkas-id', '');

                                $row.find('.status-cell').html('<span class="badge badge-secondary px-2 py-1">Belum</span>');
                                $row.find('.aksi-cell').html('<button type="button" class="btn btn-sm btn-primary py-1 px-3 upload-dokumen-trigger"><i class="fas fa-upload mr-1"></i> Upload</button>');
                            } else {
                                alert(res.message || 'Gagal menghapus berkas.');
                            }
                        },
                        error: function (xhr) {
                            updateCsrf();
                            var errText = 'Gagal menghapus berkas dari server.';
                            if (xhr.status === 403) {
                                errText = 'Sesi keamanan CSRF kedaluwarsa. Silakan refresh halaman atau coba kembali.';
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errText = xhr.responseJSON.message;
                            }
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'error', title: 'Gagal Menghapus', text: errText });
                            } else {
                                alert(errText);
                            }
                        }
                    });
                };

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Hapus Berkas?',
                        text: 'Anda yakin ingin menghapus berkas ' + nama + '?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e74a3b',
                        cancelButtonColor: '#858796',
                        confirmButtonText: 'Ya, Hapus',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            lakukanHapus();
                        }
                    });
                } else {
                    if (confirm('Anda yakin ingin menghapus berkas ' + nama + '?')) {
                        lakukanHapus();
                    }
                }
            });
        });
    </script>

<?= $this->endSection(); ?>
