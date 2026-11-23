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

$semGraduateModel   = new SemesterGraduateModel();
$tahunSemesterAktif = $semGraduateModel->getActiveSemesterYear();
$maxTglLahir        = ($tahunSemesterAktif - 15) . '-12-31';

$pendaftaran           = $pendaftaran ?? null;
$prefill               = $prefill ?? [];
$daftarDosen           = $daftarDosen ?? [];
$daftarSemester        = $daftarSemester ?? [];
$daftarJenisTugasAkhir = $daftarJenisTugasAkhir ?? ['Skripsi', 'Publikasi Artikel Ilmiah', 'Proyek Akhir'];

$statusBiodata = strtoupper((string) ($pendaftaran['status_biodata'] ?? 'DRAFT'));
$isDraft       = ($statusBiodata === 'DRAFT' || $statusBiodata === '');
$isPending     = ($statusBiodata === 'MENUNGGU');
$isApproved    = ($statusBiodata === 'DISETUJUI');
$isRejected    = ($statusBiodata === 'DITOLAK');

$hasOldInput   = (old('nik') !== null || old('nama') !== null || old('alamat') !== null);
$showRingkasan = ($isPending || $isApproved) && ! $hasOldInput;
$showForm      = (! $isPending && ! $isApproved) || $hasOldInput;

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
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-id-card text-primary mr-2"></i>Biodata Mahasiswa</h1>
</div>

<!-- Banner Status Verifikasi Biodata -->
<?php if ($isDraft): ?>
    <div class="alert alert-info border-left-info shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-2x mr-3 text-info"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Status Biodata: Draft</h5>
                <p class="mb-0">Silakan lengkapi data pada <strong>Formulir Biodata Wisuda</strong> di bawah ini, lalu klik tombol <strong>Kirim Biodata Wisuda</strong> agar biodata Anda dapat diverifikasi oleh Tendik / Akademik.</p>
            </div>
        </div>
    </div>
<?php elseif ($isPending): ?>
    <div class="alert alert-warning border-left-warning shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-hourglass-half fa-2x mr-3 text-warning"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Biodata Sedang Menunggu Verifikasi Tendik</h5>
                <p class="mb-0">Formulir biodata wisuda Anda telah berhasil dikirim dan sedang dalam proses peninjauan oleh tim akademik/tendik. Anda tetap dapat melihat ringkasan data Anda di bawah ini.</p>
            </div>
        </div>
    </div>
<?php elseif ($isApproved): ?>
    <div class="alert alert-success border-left-success shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x mr-3 text-success"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Selamat! Biodata Wisuda Anda Telah Disetujui</h5>
                <p class="mb-0">Seluruh data biodata wisuda Anda telah dinyatakan valid dan disetujui oleh Tendik. Pastikan juga Anda sudah melengkapi dan mengirimkan <strong>Berkas Persyaratan Wisuda</strong>.</p>
            </div>
        </div>
    </div>
<?php elseif ($isRejected): ?>
    <div class="alert alert-danger border-left-danger shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-times-circle fa-2x mr-3 text-danger"></i>
            <div>
                <h5 class="alert-heading font-weight-bold mb-1">Biodata Wisuda Ditolak / Perlu Perbaikan</h5>
                <p class="mb-1"><strong>Alasan / Catatan Perbaikan:</strong> <?= esc($pendaftaran['catatan_biodata'] ?? 'Terdapat data biodata yang belum sesuai.') ?></p>
                <small class="text-gray-700">Silakan perbaiki data pada formulir di bawah ini lalu klik tombol <strong>Perbarui Biodata Wisuda</strong>.</small>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Tampilan Ringkasan (Readonly) jika status MENUNGGU atau DISETUJUI -->
<?php if ($isPending || $isApproved): ?>
<div class="card shadow mb-4 <?= ! $showRingkasan ? 'd-none' : '' ?>" id="cardRingkasan">
    <div class="card-header py-3 d-sm-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-id-card mr-1"></i> Data Biodata Mahasiswa</h6>
        <div>
            <?php if ($isApproved): ?>
                <span class="badge badge-success px-3 py-2"><i class="fas fa-check-circle mr-1"></i> Disetujui</span>
            <?php else: ?>
                <span class="badge badge-warning px-3 py-2 text-dark"><i class="fas fa-hourglass-half mr-1"></i> Menunggu Verifikasi</span>
                <button type="button" class="btn btn-outline-primary btn-sm ml-2" id="btnBukaEdit">
                    <i class="fas fa-edit mr-1"></i> Ubah Biodata
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-3 col-md-4 text-center mb-4 mb-md-0 d-flex flex-column align-items-center">
                <?php 
                $fotoSrc = !empty($pendaftaran['pas_foto']) ? base_url('graduate/berkas/view/' . basename($pendaftaran['pas_foto'])) : '';
                $isRealFoto = ($fotoSrc !== '');
                if (!$isRealFoto) {
                    $fotoSrc = base_url('assets/img/undraw_profile.svg');
                }
                ?>
                <div class="position-relative mb-2">
                    <img src="<?= $fotoSrc ?>" alt="Pas Foto Mahasiswa" class="img-thumbnail rounded shadow-sm" style="max-height: 220px; width: 170px; object-fit: cover;" onerror="this.src='<?= base_url('assets/img/undraw_profile.svg') ?>'">
                </div>
                <small class="text-muted font-italic"><?= $isRealFoto ? 'Pas Foto Resmi' : 'Pas Foto Belum Ada' ?></small>
            </div>
            <div class="col-lg-9 col-md-8">
                <!-- DATA DIRI -->
                <div class="card border-left-primary shadow-sm mb-3">
                    <div class="card-header py-2 bg-light">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user mr-1"></i> Data Diri</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <tbody>
                                    <tr>
                                        <td class="font-weight-bold" style="width: 28%;">NPM</td>
                                        <td>: <?= esc($pendaftaran['npm'] ?? $prefill['npm'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Nama Lengkap</td>
                                        <td>: <?= esc($pendaftaran['nama'] ?? $prefill['nama'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">NIK (KTP)</td>
                                        <td>: <?= esc($pendaftaran['nik'] ?? $prefill['nik'] ?? '-') ?></td>
                                    </tr>
                                    <?php 
                                    $tempatLahirVal = $pendaftaran['tempat_lahir'] ?? $prefill['tempatLahir'] ?? '';
                                    $tglLahirVal    = $pendaftaran['tanggal_lahir'] ?? $prefill['tanggalLahir'] ?? null;
                                    $ttlParts       = [];
                                    if (!empty($tempatLahirVal)) $ttlParts[] = $tempatLahirVal;
                                    if (!empty($tglLahirVal) && $tglLahirVal !== '0000-00-00') $ttlParts[] = $formatDate($tglLahirVal);
                                    $ttlDisplay     = !empty($ttlParts) ? implode(', ', $ttlParts) : '-';
                                    ?>
                                    <tr>
                                        <td class="font-weight-bold">Tempat, Tanggal Lahir</td>
                                        <td>: <?= esc($ttlDisplay) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Jenis Kelamin</td>
                                        <td>: <?= esc($pendaftaran['jenis_kelamin'] ?? $prefill['jenisKelamin'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">No. Handphone / WA</td>
                                        <td>: <?= esc($pendaftaran['no_hp'] ?? $prefill['noHp'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">No. Telepon Rumah / Alternatif</td>
                                        <td>: <?= esc(!empty($pendaftaran['no_telepon']) ? $pendaftaran['no_telepon'] : (!empty($prefill['noTelepon']) ? $prefill['noTelepon'] : '-')) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">E-mail</td>
                                        <td>: <?= esc($pendaftaran['email'] ?? $prefill['email'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Alamat Rumah</td>
                                        <td>: <?= esc($pendaftaran['alamat'] ?? $prefill['alamat'] ?? '-') ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- DATA AKADEMIK -->
                <div class="card border-left-info shadow-sm mb-0">
                    <div class="card-header py-2 bg-light">
                        <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-graduation-cap mr-1"></i> Data Akademik</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <tbody>
                                    <tr>
                                        <td class="font-weight-bold" style="width: 28%;">Program Studi</td>
                                        <td>: <?= esc($pendaftaran['prodi'] ?? $prefill['prodi'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Total SKS / IPK</td>
                                        <td>: <?= esc($pendaftaran['total_sks'] ?? $prefill['totalSks'] ?? '-') ?> SKS &nbsp;|&nbsp; IPK: <?= esc($pendaftaran['ipk'] ?? $prefill['ipk'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Semester Kelulusan</td>
                                        <td>: <?= esc(SemesterGraduateModel::formatNama('', $pendaftaran['kode_semester'] ?? $prefill['kode_semester'] ?? '-')) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Tanggal Yudisium</td>
                                        <td>: <?= esc($formatDate($pendaftaran['tanggal_lulus'] ?? $prefill['tanggalLulus'] ?? null)) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Jenis Tugas Akhir</td>
                                        <td>: <?= esc($pendaftaran['jenis_tugas_akhir'] ?? $prefill['jenisTugasAkhir'] ?? 'Skripsi') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Judul Tugas Akhir</td>
                                        <td>: <?= esc($pendaftaran['judul_skripsi'] ?? $prefill['judulSkripsi'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Pembimbing Ilmu 1</td>
                                        <td>: <?= esc($pendaftaran['pembimbing1'] ?? $prefill['pembimbing1'] ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Pembimbing Ilmu 2</td>
                                        <td>: <?= esc(!empty($pendaftaran['pembimbing2']) ? $pendaftaran['pembimbing2'] : (!empty($prefill['pembimbing2']) ? $prefill['pembimbing2'] : '-')) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Pembimbing Agama</td>
                                        <td>: <?= esc(!empty($pendaftaran['pembimbing_agama']) ? $pendaftaran['pembimbing_agama'] : (!empty($prefill['pembimbingAgama']) ? $prefill['pembimbingAgama'] : '-')) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Dosen Penguji Sidang</td>
                                        <td>: <?= esc(!empty($pendaftaran['dosen_penguji']) ? $pendaftaran['dosen_penguji'] : (!empty($prefill['dosenPenguji']) ? $prefill['dosenPenguji'] : '-')) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulir Biodata Wisuda -->
<div class="card shadow mb-4 <?= ! $showForm ? 'd-none' : '' ?>" id="cardFormulir">
    <div class="card-header py-3 d-sm-flex align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-edit mr-1"></i> Formulir Biodata Mahasiswa</h6>
        <?php if ($isPending || $isApproved): ?>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTutupEdit">
                <i class="fas fa-times mr-1"></i> Tutup Form Ubah Data
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form action="<?= base_url('graduate/biodataWisudaSubmit') ?>" method="post" enctype="multipart/form-data" id="formBiodata">
            <?= csrf_field() ?>
            <input type="hidden" name="from_biodata" value="1">

            <div class="form-row">

                <!-- HEADER 1: DATA DIRI -->
                <div class="col-12 mb-3">
                    <h6 class="font-weight-bold text-primary border-bottom pb-2">
                        <i class="fas fa-user mr-1"></i> Data Diri Mahasiswa
                    </h6>
                </div>

                <!-- NPM -->
                <div class="form-group col-md-6">
                    <label for="npm" class="font-weight-bold">NPM <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" id="npm" name="npm" value="<?= esc($prefill['npm'] ?? '') ?>" readonly required>
                </div>

                <!-- Nama Lengkap -->
                <div class="form-group col-md-6">
                    <label for="nama" class="font-weight-bold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= esc(old('nama', $prefill['nama'] ?? '')) ?>" required>
                    <small class="form-text text-muted">Sesuai ijazah pendidikan sebelumnya atau akta kelahiran.</small>
                </div>

                <!-- NIK -->
                <div class="form-group col-md-6">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="nik" class="font-weight-bold mb-0">NIK (Nomor Induk Kependudukan) <span class="text-danger">*</span></label>
                        <small id="nikCounter" class="text-muted font-weight-bold">0 / 16 digit</small>
                    </div>
                    <input type="text" class="form-control mt-1" id="nik" name="nik" 
                           value="<?= esc(old('nik', $prefill['nik'] ?? '')) ?>" 
                           maxlength="16" minlength="16" inputmode="numeric" 
                           pattern="\d{16}" placeholder="Contoh: 3201xxxxxxxxxxxx" required>
                    <div class="invalid-feedback" id="nikFeedback">NIK harus terdiri dari tepat 16 digit angka.</div>
                    <small class="form-text text-muted"><i class="fas fa-id-card mr-1 text-primary"></i> Wajib tepat <strong>16 digit angka</strong> sesuai KTP/KK.</small>
                </div>

                <!-- Tempat Lahir -->
                <div class="form-group col-md-3 col-sm-6">
                    <label for="tempatLahir" class="font-weight-bold">Tempat Lahir <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="tempatLahir" name="tempatLahir" value="<?= esc(old('tempatLahir', $prefill['tempatLahir'] ?? '')) ?>" required>
                </div>

                <!-- Tanggal Lahir -->
                <div class="form-group col-md-3 col-sm-6">
                    <label for="tanggalLahir" class="font-weight-bold">Tanggal Lahir <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="tanggalLahir" name="tanggalLahir"
                           max="<?= esc($maxTglLahir) ?>"
                           data-tahun-semester="<?= esc($tahunSemesterAktif) ?>"
                           value="<?= esc(old('tanggalLahir', $prefill['tanggalLahir'] ?? '')) ?>" required>
                    <div class="invalid-feedback" id="tglLahirFeedback">Usia mahasiswa minimal 15 tahun dihitung dari tahun semester aktif (<?= esc($tahunSemesterAktif) ?>).</div>
                    <small class="form-text text-muted"><i class="fas fa-calendar-alt mr-1 text-primary"></i> Minimal 15 tahun (maks. tahun lahir <?= esc($tahunSemesterAktif - 15) ?>).</small>
                </div>

                <!-- Jenis Kelamin -->
                <div class="form-group col-md-6">
                    <label for="jenisKelamin" class="font-weight-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select class="form-control" id="jenisKelamin" name="jenisKelamin" required>
                        <option value="">Pilih jenis kelamin...</option>
                        <?php 
                        $selectedJk = old('jenisKelamin', $prefill['jenisKelamin'] ?? '');
                        foreach ($daftarJenisKelamin as $jk): ?>
                        <option value="<?= esc($jk) ?>" <?= ($selectedJk === $jk) ? 'selected' : '' ?>><?= esc($jk) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- No HP / WA -->
                <div class="form-group col-md-6">
                    <label for="noHp" class="font-weight-bold">No. Handphone / WhatsApp Aktif <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="noHp" name="noHp" value="<?= esc(old('noHp', $prefill['noHp'] ?? '')) ?>" placeholder="08xxxxxxxxxx" required>
                </div>

                <!-- No Telepon -->
                <div class="form-group col-md-6">
                    <label for="noTelepon" class="font-weight-bold">No. Telepon Rumah / Alternatif</label>
                    <input type="text" class="form-control" id="noTelepon" name="noTelepon" value="<?= esc(old('noTelepon', $prefill['noTelepon'] ?? '')) ?>" placeholder="Nomor telepon lainnya">
                </div>

                <!-- E-mail -->
                <div class="form-group col-md-6">
                    <label for="email" class="font-weight-bold">E-mail Aktif <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= esc(old('email', $prefill['email'] ?? '')) ?>" required>
                </div>

                <!-- Alamat -->
                <div class="form-group col-md-12">
                    <label for="alamat" class="font-weight-bold">Alamat Lengkap Sesuai KTP <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2" placeholder="Nama jalan, RT/RW, Kelurahan, Kecamatan, Kota/Kabupaten, Kode Pos" required><?= esc(old('alamat', $prefill['alamat'] ?? '')) ?></textarea>
                </div>

                <!-- HEADER 2: DATA AKADEMIK -->
                <div class="col-12 mt-4 mb-3">
                    <h6 class="font-weight-bold text-info border-bottom pb-2">
                        <i class="fas fa-graduation-cap mr-1"></i> Data Akademik &amp; Kelulusan
                    </h6>
                </div>

                <!-- Program Studi (Readonly) -->
                <div class="form-group col-md-6">
                    <label for="prodi" class="font-weight-bold">Program Studi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" id="prodi" name="prodi" readonly value="<?= esc($prefill['prodi'] ?? '') ?>" required>
                    <small class="form-text text-muted"><i class="fas fa-lock mr-1 text-secondary"></i> Mengikuti data program studi resmi mahasiswa.</small>
                </div>

                <!-- Pas Foto -->
                <div class="form-group col-md-6">
                    <label for="pas_foto" class="font-weight-bold">Pas Foto Resmi Wisuda <span class="small text-muted">(JPG/PNG, maks. 2MB)</span></label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="pas_foto" name="pas_foto" accept=".jpg,.jpeg,.png">
                        <label class="custom-file-label" for="pas_foto">Pilih pas foto resmi...</label>
                    </div>
                </div>

                <!-- Total SKS -->
                <div class="form-group col-md-3 col-sm-6">
                    <label for="totalSks" class="font-weight-bold">Total SKS Lulus <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="totalSks" name="totalSks" value="<?= esc(old('totalSks', $prefill['totalSks'] ?? '144')) ?>" required>
                </div>

                <!-- IPK -->
                <div class="form-group col-md-3 col-sm-6">
                    <label for="ipk" class="font-weight-bold">IPK Terakhir <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="2.00" max="4.00" class="form-control" id="ipk" name="ipk" value="<?= esc(old('ipk', $prefill['ipk'] ?? '3.50')) ?>" required>
                </div>

                <!-- Semester Kelulusan (Otomatis dari SK Yudisium - Readonly) -->
                <div class="form-group col-md-6">
                    <label for="kode_semester" class="font-weight-bold">Semester Kelulusan <span class="text-danger">*</span></label>
                    <?php 
                    $semCode = old('kode_semester', $prefill['kode_semester'] ?? '');
                    $semDisplay = SemesterGraduateModel::formatNama('', $semCode);
                    ?>
                    <input type="hidden" name="kode_semester" value="<?= esc($semCode) ?>">
                    <input type="text" class="form-control bg-light" id="kode_semester_display" readonly value="<?= esc($semDisplay) ?>" required>
                    <small class="form-text text-muted"><i class="fas fa-lock mr-1 text-secondary"></i> Sesuai semester penetapan SK Yudisium.</small>
                </div>

                <!-- Tanggal Yudisium (Otomatis dari SK Yudisium - Readonly) -->
                <div class="form-group col-md-6">
                    <label for="tanggalLulus" class="font-weight-bold">Tanggal Yudisium <span class="text-danger">*</span></label>
                    <input type="date" class="form-control bg-light" id="tanggalLulus" name="tanggalLulus" readonly value="<?= esc($prefill['tanggalLulus'] ?? '') ?>" required>
                    <small class="form-text text-muted"><i class="fas fa-lock mr-1 text-secondary"></i> Tanggal resmi sesuai penetapan SK Yudisium oleh Fakultas/Tendik.</small>
                </div>

                <!-- Pemilihan Jenis Tugas Akhir -->
                <div class="form-group col-md-12">
                    <label for="jenis_tugas_akhir" class="font-weight-bold">Pemilihan Tugas Akhir <span class="text-danger">*</span></label>
                    <select class="form-control" id="jenis_tugas_akhir" name="jenis_tugas_akhir" required>
                        <?php 
                        $selectedJta = old('jenis_tugas_akhir', $prefill['jenisTugasAkhir'] ?? 'Skripsi');
                        foreach ($daftarJenisTugasAkhir as $jta): ?>
                        <option value="<?= esc($jta) ?>" <?= ($selectedJta === $jta) ? 'selected' : '' ?>>
                            <?= esc($jta) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Judul Skripsi / Tugas Akhir (1 Baris & Full Kapital) -->
                <div class="form-group col-md-12">
                    <label for="judulSkripsi" class="font-weight-bold" id="labelJudulTa">Judul Tugas Akhir / Skripsi / Artikel Ilmiah <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" id="judulSkripsi" name="judulSkripsi" 
                           value="<?= esc(strtoupper((string) old('judulSkripsi', $prefill['judulSkripsi'] ?? ''))) ?>" 
                           placeholder="JUDUL LENGKAP SESUAI NASKAH FINAL (HURUF BESAR/KAPITAL)" 
                           style="text-transform: uppercase;" required>
                    <small class="form-text text-muted"><i class="fas fa-info-circle mr-1 text-primary"></i> Judul harus <strong>FULL KAPITAL</strong> dan ditulis dalam <strong>1 baris</strong> (tidak dapat dienter ke bawah).</small>
                </div>

                <!-- Pembimbing Ilmu 1 -->
                <div class="form-group col-md-6">
                    <label for="pembimbing1" class="font-weight-bold">Pembimbing Ilmu 1 <span class="text-danger">*</span></label>
                    <select class="form-control pilih-dosen" id="pembimbing1" name="pembimbing1" required>
                        <option value="">Pilih dosen pembimbing 1...</option>
                        <?php 
                        $selectedP1 = old('pembimbing1', $prefill['pembimbing1'] ?? '');
                        foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= ($selectedP1 === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                        <?php if (!empty($selectedP1) && !in_array($selectedP1, $daftarDosen)): ?>
                        <option value="<?= esc($selectedP1) ?>" selected><?= esc($selectedP1) ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Pembimbing Ilmu 2 -->
                <div class="form-group col-md-6">
                    <label for="pembimbing2" class="font-weight-bold">Pembimbing Ilmu 2</label>
                    <select class="form-control pilih-dosen" id="pembimbing2" name="pembimbing2">
                        <option value="">Pilih dosen pembimbing 2 (opsional)...</option>
                        <?php 
                        $selectedP2 = old('pembimbing2', $prefill['pembimbing2'] ?? '');
                        foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= ($selectedP2 === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                        <?php if (!empty($selectedP2) && !in_array($selectedP2, $daftarDosen)): ?>
                        <option value="<?= esc($selectedP2) ?>" selected><?= esc($selectedP2) ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Pembimbing Agama -->
                <div class="form-group col-md-6">
                    <label for="pembimbingAgama" class="font-weight-bold">Pembimbing Agama <span class="text-danger">*</span></label>
                    <select class="form-control pilih-dosen" id="pembimbingAgama" name="pembimbingAgama" required>
                        <option value="">Pilih dosen pembimbing agama...</option>
                        <?php 
                        $selectedPa = old('pembimbingAgama', $prefill['pembimbingAgama'] ?? '');
                        foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= ($selectedPa === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                        <?php if (!empty($selectedPa) && !in_array($selectedPa, $daftarDosen)): ?>
                        <option value="<?= esc($selectedPa) ?>" selected><?= esc($selectedPa) ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Dosen Penguji Sidang -->
                <div class="form-group col-md-6">
                    <label for="dosenPenguji" class="font-weight-bold">Dosen Penguji Sidang <span class="text-danger">*</span></label>
                    <select class="form-control pilih-dosen" id="dosenPenguji" name="dosenPenguji" required>
                        <option value="">Pilih dosen penguji sidang...</option>
                        <?php 
                        $selectedPenguji = old('dosenPenguji', $prefill['dosenPenguji'] ?? '');
                        foreach ($daftarDosen as $d): ?>
                        <option value="<?= esc($d) ?>" <?= ($selectedPenguji === $d) ? 'selected' : '' ?>><?= esc($d) ?></option>
                        <?php endforeach; ?>
                        <?php if (!empty($selectedPenguji) && !in_array($selectedPenguji, $daftarDosen)): ?>
                        <option value="<?= esc($selectedPenguji) ?>" selected><?= esc($selectedPenguji) ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Tombol Submit -->
                <div class="form-group col-md-12 text-right mt-3">
                    <button type="submit" class="btn btn-success px-4" id="btnSubmitBiodata">
                        <i class="fas fa-paper-plane fa-fw mr-1"></i> <?= $isRejected ? 'Perbarui Biodata Wisuda' : 'Kirim Biodata Wisuda' ?>
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

    <script>
        $(function () {
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

            // Toggle form ubah data
            $(document).on('click', '#btnBukaEdit', function () {
                $('#cardRingkasan').addClass('d-none');
                $('#cardFormulir').removeClass('d-none');
            });

            $(document).on('click', '#btnTutupEdit', function () {
                $('#cardFormulir').addClass('d-none');
                $('#cardRingkasan').removeClass('d-none');
            });

            // Update label custom-file saat file foto dipilih
            $('#pas_foto').on('change', function () {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass('selected').text(fileName || 'Pilih pas foto resmi...');
            });

            // NIK 16 digit counter & restriction
            function updateNikCounter() {
                var rawVal = $('#nik').val() || '';
                var cleanVal = rawVal.replace(/\D/g, '').substring(0, 16);
                if (rawVal !== cleanVal) {
                    $('#nik').val(cleanVal);
                }
                var len = cleanVal.length;
                $('#nikCounter').text(len + ' / 16 digit');
                if (len === 16) {
                    $('#nikCounter').removeClass('text-danger text-warning').addClass('text-success');
                    $('#nik').removeClass('is-invalid').addClass('is-valid');
                } else {
                    $('#nikCounter').removeClass('text-success').addClass(len === 0 ? 'text-muted' : 'text-danger');
                    $('#nik').removeClass('is-valid');
                }
            }
            $('#nik').on('input keyup paste', updateNikCounter);
            updateNikCounter();

            // Judul TA 1 baris & uppercase
            $('#judulSkripsi').on('input', function () {
                var val = $(this).val();
                var oneLine = val.replace(/[\r\n]+/g, ' ');
                var upper = oneLine.toUpperCase();
                if (val !== upper) {
                    $(this).val(upper);
                }
            }).on('keydown', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    return false;
                }
            });

            // Dynamic label judul TA
            $('#jenis_tugas_akhir').on('change', function () {
                var selected = $(this).val() || '';
                $('#labelJudulTa')
                    .text('Judul ' + selected + ' ')
                    .append($('<span>', { class: 'text-danger', text: '*' }));
            });

            // Real-time validasi duplikasi dosen
            function checkDuplicateDosen() {
                var selects = [
                    $('#pembimbing1'),
                    $('#pembimbing2'),
                    $('#pembimbingAgama'),
                    $('#dosenPenguji')
                ];
                var values = {};
                var duplicates = {};

                selects.forEach(function ($sel) {
                    $sel.removeClass('is-invalid');
                    var val = $sel.val();
                    if (val) {
                        if (values[val]) {
                            duplicates[val] = true;
                        }
                        values[val] = true;
                    }
                });

                selects.forEach(function ($sel) {
                    var val = $sel.val();
                    if (val && duplicates[val]) {
                        $sel.addClass('is-invalid');
                    }
                });
            }

            $('#pembimbing1, #pembimbing2, #pembimbingAgama, #dosenPenguji').on('change', checkDuplicateDosen);

            // Validasi batas minimal usia tanggal lahir (15 tahun dihitung dari tahun semester aktif)
            function validateTanggalLahir() {
                var $tgl = $('#tanggalLahir');
                var val = $tgl.val();
                if (!val) {
                    $tgl.removeClass('is-invalid');
                    return true;
                }

                var parts = val.split('-');
                if (parts.length < 1) {
                    $tgl.removeClass('is-invalid');
                    return true;
                }
                var birthYear = parseInt(parts[0], 10);
                var semYear = parseInt($tgl.data('tahun-semester') || <?= (int) $tahunSemesterAktif ?>, 10);

                if (isNaN(birthYear) || isNaN(semYear)) {
                    $tgl.removeClass('is-invalid');
                    return true;
                }

                var usia = semYear - birthYear;
                if (usia < 15) {
                    $tgl.addClass('is-invalid');
                    return false;
                } else {
                    $tgl.removeClass('is-invalid');
                    return true;
                }
            }

            $('#tanggalLahir').on('change blur input', validateTanggalLahir);

            // Validasi submit form
            $('#formBiodata').on('submit', function (e) {
                if (!validateTanggalLahir()) {
                    e.preventDefault();
                    $('#tanggalLahir').focus();
                    var semYear = $('#tanggalLahir').data('tahun-semester') || <?= (int) $tahunSemesterAktif ?>;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Tanggal Lahir Tidak Memenuhi Syarat',
                            text: 'Usia mahasiswa minimal 15 tahun dihitung dari tahun semester aktif (' + semYear + '). Maksimal tahun kelahiran yang diizinkan adalah ' + (semYear - 15) + '.',
                            confirmButtonColor: '#1C476C'
                        });
                    } else {
                        alert('Usia mahasiswa minimal 15 tahun dihitung dari tahun semester aktif (' + semYear + ').');
                    }
                    return false;
                }

                var nik = ($('#nik').val() || '').replace(/\D/g, '');
                if (nik.length !== 16) {
                    e.preventDefault();
                    $('#nik').addClass('is-invalid').focus();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'NIK Tidak Valid',
                            text: 'NIK harus terdiri dari tepat 16 digit angka (tidak boleh kurang atau lebih).',
                            confirmButtonColor: '#1C476C'
                        });
                    } else {
                        alert('NIK harus terdiri dari tepat 16 digit angka.');
                    }
                    return false;
                }

                var p1 = $('#pembimbing1').val() || '';
                var p2 = $('#pembimbing2').val() || '';
                var pa = $('#pembimbingAgama').val() || '';
                var penguji = $('#dosenPenguji').val() || '';

                if (!p1 || !pa || !penguji) {
                    e.preventDefault();
                    if (!p1) $('#pembimbing1').focus();
                    else if (!pa) $('#pembimbingAgama').focus();
                    else $('#dosenPenguji').focus();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Data Belum Lengkap',
                            text: 'Pembimbing Ilmu 1, Pembimbing Agama, dan Dosen Penguji Sidang wajib dipilih.',
                            confirmButtonColor: '#1C476C'
                        });
                    } else {
                        alert('Pembimbing Ilmu 1, Pembimbing Agama, dan Dosen Penguji Sidang wajib dipilih.');
                    }
                    return false;
                }

                // Cek duplikasi dosen
                var dosenArr = [
                    { role: 'Pembimbing Ilmu 1', val: p1, el: '#pembimbing1' },
                    { role: 'Pembimbing Ilmu 2', val: p2, el: '#pembimbing2' },
                    { role: 'Pembimbing Agama', val: pa, el: '#pembimbingAgama' },
                    { role: 'Dosen Penguji Sidang', val: penguji, el: '#dosenPenguji' }
                ].filter(function (item) { return item.val !== ''; });

                var seen = {};
                var duplicateName = '';
                for (var i = 0; i < dosenArr.length; i++) {
                    if (seen[dosenArr[i].val]) {
                        duplicateName = dosenArr[i].val;
                        break;
                    }
                    seen[dosenArr[i].val] = true;
                }

                if (duplicateName) {
                    e.preventDefault();
                    checkDuplicateDosen();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Dosen Tidak Boleh Sama',
                            text: 'Pembimbing Ilmu 1, Pembimbing Ilmu 2, Pembimbing Agama, dan Dosen Penguji Sidang tidak boleh ada yang sama. Silakan pilih dosen yang berbeda.',
                            confirmButtonColor: '#1C476C'
                        });
                    } else {
                        alert('Pembimbing Ilmu 1, Pembimbing Ilmu 2, Pembimbing Agama, dan Dosen Penguji Sidang tidak boleh ada yang sama.');
                    }
                    return false;
                }
            });
        });
    </script>

<?= $this->endSection(); ?>

