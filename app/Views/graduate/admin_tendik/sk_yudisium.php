<?= $this->extend("graduate/layout/template"); ?>

<?= $this->section("content"); ?>

<!-- Begin of Content -->

<?php
/*
 * Data dummy sementara (tahap front-end).
 * Sumber data dari database menyusul:
 * - Daftar mahasiswa diambil dari Calon Wisudawan yang telah disetujui
 * - Data SK Yudisium dari tabel sk_yudisium
 */
$daftarProdi = ['Teknik Informatika', 'Sistem Informasi'];
$daftarTahunAkademik = ['2025/2026', '2024/2025'];

$daftarMahasiswa = [
    [
        'no' => 1,
        'npm' => '2021010001',
        'nama' => 'Ahmad Fauzi',
        'prodi' => 'Teknik Informatika',
        'tgl_yudisium' => '2025-11-15',
        'no_sk' => '123/SK-YUD/VII/2026',
        'tgl_terbit_sk' => '2026-07-20',
        'file_sk' => 'SK_Yudisium_2021010001.pdf',
        'status' => 'sudah'
    ],
    [
        'no' => 2,
        'npm' => '2021010002',
        'nama' => 'Siti Nurhaliza',
        'prodi' => 'Teknik Informatika',
        'tgl_yudisium' => '2025-11-15',
        'no_sk' => '',
        'tgl_terbit_sk' => '',
        'file_sk' => '',
        'status' => 'belum'
    ],
];

$calonDisetujui = [
    ['npm' => '2021010003', 'nama' => 'Rizky Pratama', 'prodi' => 'Teknik Informatika'],
    ['npm' => '2021010004', 'nama' => 'Putri Aulia', 'prodi' => 'Sistem Informasi'],
];
?>

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
                        <th style="width: 15%;">NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 18%;">Diunggah Pada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarTerplot)) : ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Belum ada data mahasiswa terplot</td>
                    </tr>
                    <?php else : ?>
                    <?php $no = 1; foreach ($daftarTerplot as $m) : ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= esc($m['npm']) ?></td>
                        <td><?= esc($m['nama']) ?></td>
                        <td class="text-center"><?= esc($m['status']) ?></td>
                        <td class="text-center"><?= ! empty($m['uploaded_at']) ? esc(date('d/m/Y H:i', strtotime($m['uploaded_at']))) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Card Daftar Mahasiswa SK Yudisium -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Mahasiswa SK Yudisium</h6>
        <button type="button" class="btn btn-success btn-sm mt-2 mt-md-0" data-toggle="modal" data-target="#modalTambahMahasiswa"><i class="fas fa-plus fa-fw"></i> Tambah Mahasiswa</button>
    </div>
    <div class="card-body">
        <form class="form-inline mb-3 flex-wrap" method="get" action="#">
            <div class="form-group mr-2 mb-2">
                <select class="form-control form-control-sm" id="filterProdi" name="prodi">
                    <option value="">Pilih Prodi</option>
                    <?php foreach ($daftarProdi as $prodi): ?>
                    <option value="<?= esc($prodi) ?>"><?= esc($prodi) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mr-2 mb-2">
                <select class="form-control form-control-sm" id="filterTahunAkademik" name="tahunAkademik">
                    <option value="">Pilih Tahun Akademik</option>
                    <?php foreach ($daftarTahunAkademik as $ta): ?>
                    <option value="<?= esc($ta) ?>"><?= esc($ta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mr-2 mb-2">
                <input type="text" class="form-control form-control-sm" id="cariMahasiswa" name="cari" placeholder="Cari NPM / Nama Mahasiswa">
            </div>
            <button type="button" class="btn btn-primary btn-sm mr-2 mb-2"><i class="fas fa-search fa-fw"></i> Cari</button>
            <button type="button" class="btn btn-secondary btn-sm mb-2"><i class="fas fa-redo fa-fw"></i> Reset</button>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th style="width: 5%;">No</th>
                        <th style="width: 12%;">NPM</th>
                        <th style="width: 20%;">Nama Mahasiswa</th>
                        <th style="width: 14%;">Prodi</th>
                        <th style="width: 12%;">Tanggal Yudisium</th>
                        <th style="width: 15%;">No. SK Yudisium</th>
                        <th style="width: 12%;">Tanggal Terbit SK</th>
                        <th style="width: 10%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftarMahasiswa as $mhs) : ?>
                    <tr>
                        <td class="text-center"><?= esc($mhs['no']) ?></td>
                        <td><?= esc($mhs['npm']) ?></td>
                        <td><?= esc($mhs['nama']) ?></td>
                        <td><?= esc($mhs['prodi']) ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($mhs['tgl_yudisium'])) ?></td>
                        <td class="<?= empty($mhs['no_sk']) ? 'text-center text-muted' : '' ?>"><?= empty($mhs['no_sk']) ? '-' : esc($mhs['no_sk']) ?></td>
                        <td class="text-center <?= empty($mhs['tgl_terbit_sk']) ? 'text-muted' : '' ?>"><?= empty($mhs['tgl_terbit_sk']) ? '-' : date('d/m/Y', strtotime($mhs['tgl_terbit_sk'])) ?></td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i> Aksi
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <?php if ($mhs['status'] === 'sudah') : ?>
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalInputSK"><i class="fas fa-edit text-primary fa-fw"></i> Input SK</a>
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalLihatSK"><i class="fas fa-eye text-info fa-fw"></i> Lihat SK</a>
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalInputSK"><i class="fas fa-pen text-warning fa-fw"></i> Edit SK</a>
                                    <?php else : ?>
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalInputSK"><i class="fas fa-edit text-primary fa-fw"></i> Input SK</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Mahasiswa SK Yudisium -->
<div class="modal fade" id="modalTambahMahasiswa" tabindex="-1" role="dialog" aria-labelledby="modalTambahMahasiswaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahMahasiswaLabel">Tambah Mahasiswa ke Daftar SK Yudisium</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-2 text-muted">Pilih mahasiswa dari daftar Calon Wisudawan yang telah disetujui.</p>
                <div class="form-group">
                    <input type="text" class="form-control" id="cariCalon" placeholder="Cari NPM / Nama Mahasiswa">
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr class="text-center">
                                <th style="width: 5%;">Pilih</th>
                                <th style="width: 15%;">NPM</th>
                                <th>Nama Mahasiswa</th>
                                <th style="width: 20%;">Prodi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calonDisetujui as $calon) : ?>
                            <tr>
                                <td class="text-center"><input type="checkbox"></td>
                                <td><?= esc($calon['npm']) ?></td>
                                <td><?= esc($calon['nama']) ?></td>
                                <td><?= esc($calon['prodi']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success">Tambahkan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Input SK Yudisium -->
<div class="modal fade" id="modalInputSK" tabindex="-1" role="dialog" aria-labelledby="modalInputSKLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalInputSKLabel">Input SK Yudisium</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="#" method="post">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="inputNpm">NPM</label>
                            <input type="text" class="form-control" id="inputNpm" value="2021010001" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inputNama">Nama Mahasiswa</label>
                            <input type="text" class="form-control" id="inputNama" value="Ahmad Fauzi" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inputProdi">Program Studi</label>
                            <input type="text" class="form-control" id="inputProdi" value="Teknik Informatika" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inputTglYudisium">Tanggal Yudisium</label>
                            <input type="date" class="form-control" id="inputTglYudisium" value="2025-11-15">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inputNoSk">Nomor SK Yudisium</label>
                            <input type="text" class="form-control" id="inputNoSk" placeholder="Contoh: 123/SK-YUD/VII/2026" value="123/SK-YUD/VII/2026">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inputTglTerbit">Tanggal Terbit SK</label>
                            <input type="date" class="form-control" id="inputTglTerbit" value="2026-07-20">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="inputFileSk">File SK Yudisium <small class="text-muted">(opsional)</small></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="inputFileSk" accept=".pdf">
                                <label class="custom-file-label" for="inputFileSk">Pilih file PDF...</label>
                            </div>
                            <small class="text-muted">Format .PDF, file SK bersifat opsional.</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lihat SK Yudisium -->
<div class="modal fade" id="modalLihatSK" tabindex="-1" role="dialog" aria-labelledby="modalLihatSKLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLihatSKLabel">Lihat SK Yudisium</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>NPM</label>
                        <input type="text" class="form-control" value="2021010001" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Nama Mahasiswa</label>
                        <input type="text" class="form-control" value="Ahmad Fauzi" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Program Studi</label>
                        <input type="text" class="form-control" value="Teknik Informatika" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Tanggal Yudisium</label>
                        <input type="text" class="form-control" value="15/11/2025" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Nomor SK Yudisium</label>
                        <input type="text" class="form-control" value="123/SK-YUD/VII/2026" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Tanggal Terbit SK</label>
                        <input type="text" class="form-control" value="20/07/2026" readonly>
                    </div>
                    <div class="form-group col-md-12">
                        <label>File SK Yudisium</label>
                        <div class="alert alert-secondary mb-0">
                            <i class="fas fa-file-pdf text-danger"></i> SK_Yudisium_2021010001.pdf <a href="#" class="ml-2">Unduh</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
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

<!-- End of Content -->

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

<script>
    $(function () {
        $(document).on('change', '.custom-file-input', function () {
            var fileName = $(this).val().split('\\').pop();
            if (fileName) {
                $(this).next('.custom-file-label').html(fileName);
            } else {
                $(this).next('.custom-file-label').html('Pilih file PDF...');
            }
        });
    });
</script>

<?= $this->endSection(); ?>
