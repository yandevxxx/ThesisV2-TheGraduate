
<?= $this->extend("layout/template"); ?>

<?= $this->section("content"); ?>

<h1 class="h3 mb-2 text-gray-800">Verifikasi Berkas Fisik</h1>
<p class="mb-4 text-gray-600">
    Kelola pemeriksaan kelengkapan berkas fisik mahasiswa yang mengajukan sidang skripsi.
</p>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            Daftar Verifikasi Berkas
        </h6>
    </div>

    <div class="card-body">

        <!-- Ringkasan Status -->
        <div class="row mb-4">

            <div class="col-md-3 mb-3">
                <div class="card border-left-secondary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                            Belum Diperiksa
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">2</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Sedang Diperiksa
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">1</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Berkas Lengkap
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">2</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Berkas Tidak Lengkap
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">1</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Filter -->
        <div class="row mb-3">

            <div class="col-md-4">
                <label for="filterStatus">Filter Status Verifikasi</label>
                <select class="form-control" id="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="Belum diperiksa">Belum diperiksa</option>
                    <option value="Sedang diperiksa">Sedang diperiksa</option>
                    <option value="Lengkap">Lengkap</option>
                    <option value="Tidak lengkap">Tidak lengkap</option>
                </select>
            </div>

        </div>

        <!-- Tabel -->
        <div class="table-responsive">

            <table class="table table-bordered" id="tabelVerifikasiBerkas"
                   width="100%" cellspacing="0">

                <thead class="text-center">
                    <tr>
                        <th>No</th>
                        <th>NPM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Jenis Pengajuan</th>
                        <th>Tanggal Penyerahan</th>
                        <th>Status Verifikasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php
                    $dataDummy = [
                        [
                            'npm' => '1402024056',
                            'nama' => 'Andi Pratama',
                            'jenis' => 'Sidang Skripsi',
                            'tanggal' => '28-11-2026',
                            'status' => 'Belum diperiksa'
                        ],
                        [
                            'npm' => '1402024057',
                            'nama' => 'Budi Santoso',
                            'jenis' => 'Sidang Skripsi',
                            'tanggal' => '27-11-2026',
                            'status' => 'Sedang diperiksa'
                        ],
                        [
                            'npm' => '1402024058',
                            'nama' => 'Citra Lestari',
                            'jenis' => 'Sidang Skripsi',
                            'tanggal' => '26-11-2026',
                            'status' => 'Lengkap'
                        ],
                        [
                            'npm' => '1402024059',
                            'nama' => 'Dewi Anggraini',
                            'jenis' => 'Sidang Skripsi',
                            'tanggal' => '25-11-2026',
                            'status' => 'Tidak lengkap'
                        ],
                        [
                            'npm' => '1402024060',
                            'nama' => 'Eko Saputra',
                            'jenis' => 'Sidang Skripsi',
                            'tanggal' => '24-11-2026',
                            'status' => 'Belum diperiksa'
                        ],
                        [
                            'npm' => '1402024061',
                            'nama' => 'Fitri Ramadhani',
                            'jenis' => 'Sidang Skripsi',
                            'tanggal' => '23-11-2026',
                            'status' => 'Lengkap'
                        ],
                    ];

                    $counter = 1;

                    foreach ($dataDummy as $data) :
                    ?>

                    <tr>
                        <td class="text-center"><?= $counter++ ?></td>
                        <td><?= $data['npm'] ?></td>
                        <td><?= $data['nama'] ?></td>
                        <td><?= $data['jenis'] ?></td>
                        <td class="text-center"><?= $data['tanggal'] ?></td>

                        <td class="text-center">
                            <?php if ($data['status'] == 'Belum diperiksa') : ?>
                                <span class="badge badge-secondary">
                                    Belum diperiksa
                                </span>
                            <?php elseif ($data['status'] == 'Sedang diperiksa') : ?>
                                <span class="badge badge-info">
                                    Sedang diperiksa
                                </span>
                            <?php elseif ($data['status'] == 'Lengkap') : ?>
                                <span class="badge badge-success">
                                    Lengkap
                                </span>
                            <?php else : ?>
                                <span class="badge badge-danger">
                                    Tidak lengkap
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center">
                            <a href="<?= base_url('TenagaKependidikan/detailVerifikasiBerkasFisik/' . $data['npm']) ?>"
                                  class="btn btn-primary btn-sm">
                             <i class="fas fa-search"></i>
                               Periksa
                            </a>
                        </td>
                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>
</div>

<!-- Modal Pemeriksaan Berkas -->
<div class="modal fade" id="modalPeriksaBerkas" tabindex="-1" role="dialog"
     aria-labelledby="judulModalPeriksa" aria-hidden="true">

    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="judulModalPeriksa">
                    <i class="fas fa-clipboard-check mr-2"></i>
                    Pemeriksaan Berkas Fisik
                </h5>

                <button type="button" class="close text-white" data-dismiss="modal"
                        aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <!-- Informasi Mahasiswa -->
                <div class="card border-left-primary shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <small class="text-muted">NPM</small>
                                <div class="font-weight-bold" id="detailNpm">-</div>
                            </div>

                            <div class="col-md-8 mb-2">
                                <small class="text-muted">Nama Mahasiswa</small>
                                <div class="font-weight-bold" id="detailNama">-</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">Jenis Pengajuan</small>
                                <div class="font-weight-bold">Sidang Skripsi</div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted">Status Verifikasi</small>
                                <div id="detailStatus">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Checklist Berkas -->
                <h6 class="font-weight-bold text-primary mb-3">
                    Checklist Kelengkapan Berkas
                </h6>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-1"></i>
                    Periksa berkas fisik mahasiswa dan tandai berkas yang sudah lengkap.
                </div>

                <div id="daftarChecklistBerkas">

                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input checklist-berkas"
                               id="berkas1">
                        <label class="custom-control-label" for="berkas1">
                            Formulir pengajuan sidang skripsi
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input checklist-berkas"
                               id="berkas2">
                        <label class="custom-control-label" for="berkas2">
                            Lembar persetujuan pembimbing
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input checklist-berkas"
                               id="berkas3">
                        <label class="custom-control-label" for="berkas3">
                            Bukti persyaratan akademik
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input checklist-berkas"
                               id="berkas4">
                        <label class="custom-control-label" for="berkas4">
                            Dokumen naskah skripsi
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input checklist-berkas"
                               id="berkas5">
                        <label class="custom-control-label" for="berkas5">
                            Bukti administrasi yang diperlukan
                        </label>
                    </div>

                </div>

                <!-- Catatan -->
                <div class="form-group mt-4">
                    <label for="catatanVerifikasi" class="font-weight-bold">
                        Catatan Pemeriksaan
                    </label>

                    <textarea class="form-control" id="catatanVerifikasi" rows="3"
                              placeholder="Tuliskan catatan jika terdapat berkas yang kurang atau perlu diperbaiki..."></textarea>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>
                    Batal
                </button>

                <button type="button" class="btn btn-success" id="btnSimpanVerifikasi">
                    <i class="fas fa-save mr-1"></i>
                    Simpan Verifikasi
                </button>
            </div>

        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section("scripts"); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    let tombolAktif = null;

    // ==============================
    // TOMBOL PERIKSA
    // ==============================
    document.querySelectorAll('.btn-periksa').forEach(function (button) {

        button.addEventListener('click', function () {

            tombolAktif = this;

            const npm = this.getAttribute('data-npm');
            const nama = this.getAttribute('data-nama');
            const status = this.getAttribute('data-status');

            document.getElementById('detailNpm').textContent = npm;
            document.getElementById('detailNama').textContent = nama;

            tampilkanStatus(status);

            document.querySelectorAll('.checklist-berkas')
                .forEach(function (checkbox) {
                    checkbox.checked = false;
                });

            document.getElementById('catatanVerifikasi').value = '';

            // Tampilkan modal Bootstrap
            window.jQuery('#modalPeriksaBerkas').modal('show');

        });

    });


    // ==============================
    // TAMPILKAN STATUS
    // ==============================
    function tampilkanStatus(status) {

        const detailStatus = document.getElementById('detailStatus');

        let classBadge = 'badge-secondary';

        if (status === 'Sedang diperiksa') {
            classBadge = 'badge-info';
        } else if (status === 'Lengkap') {
            classBadge = 'badge-success';
        } else if (status === 'Tidak lengkap') {
            classBadge = 'badge-danger';
        }

        detailStatus.innerHTML =
            '<span class="badge ' + classBadge + '">' + status + '</span>';

    }


    // ==============================
    // SIMPAN VERIFIKASI
    // ==============================
    document.getElementById('btnSimpanVerifikasi')
        .addEventListener('click', function () {

            if (!tombolAktif) {
                return;
            }

            const checklist = document.querySelectorAll('.checklist-berkas');
            const totalBerkas = checklist.length;

            let berkasLengkap = 0;

            checklist.forEach(function (checkbox) {
                if (checkbox.checked) {
                    berkasLengkap++;
                }
            });

            const catatan = document.getElementById('catatanVerifikasi').value.trim();

            const statusBaru = berkasLengkap === totalBerkas
                ? 'Lengkap'
                : 'Tidak lengkap';

            // Update status tombol
            tombolAktif.setAttribute('data-status', statusBaru);

            // Update badge pada tabel
            const baris = tombolAktif.closest('tr');
            const kolomStatus = baris.querySelector('td:nth-child(6)');

            let classBadge = statusBaru === 'Lengkap'
                ? 'badge-success'
                : 'badge-danger';

            kolomStatus.innerHTML =
                '<span class="badge ' + classBadge + '">' +
                statusBaru +
                '</span>';

            // Update ringkasan
            hitungRingkasan();

            // Tutup modal
            window.jQuery('#modalPeriksaBerkas').modal('hide');

            // Notifikasi
            Swal.fire({
                icon: statusBaru === 'Lengkap' ? 'success' : 'warning',
                title: 'Verifikasi Berhasil',
                html:
                    '<strong>' +
                    document.getElementById('detailNama').textContent +
                    '</strong><br>' +
                    'Status berkas: <strong>' + statusBaru + '</strong>' +
                    (catatan
                        ? '<br><br><small>Catatan: ' + catatan + '</small>'
                        : ''),
                confirmButtonColor: '#4e73df'
            });

        });


    // ==============================
    // HITUNG RINGKASAN
    // ==============================
    function hitungRingkasan() {

        let belumDiperiksa = 0;
        let sedangDiperiksa = 0;
        let lengkap = 0;
        let tidakLengkap = 0;

        document.querySelectorAll('#tabelVerifikasiBerkas tbody tr')
            .forEach(function (baris) {

                const tombol = baris.querySelector('.btn-periksa');
                const status = tombol.getAttribute('data-status');

                if (status === 'Belum diperiksa') {
                    belumDiperiksa++;
                } else if (status === 'Sedang diperiksa') {
                    sedangDiperiksa++;
                } else if (status === 'Lengkap') {
                    lengkap++;
                } else if (status === 'Tidak lengkap') {
                    tidakLengkap++;
                }

            });

        const ringkasan = document.querySelectorAll(
            '.card-body .row.mb-4 .col-md-3 .h5'
        );

        ringkasan[0].textContent = belumDiperiksa;
        ringkasan[1].textContent = sedangDiperiksa;
        ringkasan[2].textContent = lengkap;
        ringkasan[3].textContent = tidakLengkap;

    }

});
</script>

<?= $this->endSection(); ?>