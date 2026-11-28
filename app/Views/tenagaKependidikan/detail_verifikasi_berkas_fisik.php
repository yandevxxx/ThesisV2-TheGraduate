<?= $this->extend("layout/template"); ?>

<?= $this->section("content"); ?>

<style>
    .verification-card {
        border: 1px solid #e3eaf3;
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 20px;
        background: #fff;
    }

    .verification-card-header {
        background: #f4f8ff;
        padding: 16px 20px;
        border-bottom: 1px solid #e3eaf3;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .verification-card-header h6 {
        margin: 0;
        font-weight: 700;
        color: #344054;
    }

    .verification-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: #3569f6;
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }

    .verification-card-body {
        padding: 20px;
    }

    .info-label {
        font-size: 12px;
        color: #718096;
        margin-bottom: 4px;
    }

    .info-value {
        font-weight: 600;
        color: #344054;
    }

    #tabelBerkasFisik thead th {
        background: #f8fafc;
        color: #475467;
        font-size: 12px;
        vertical-align: middle;
    }

    #tabelBerkasFisik tbody td {
        vertical-align: middle;
        font-size: 13px;
    }

    .badge-verifikasi {
        background: #e7f9f1;
        color: #079669;
        border: 1px solid #b8f0d7;
        border-radius: 20px;
        padding: 6px 10px;
        font-size: 11px;
    }

    .badge-belum {
        background: #fff4e5;
        color: #b7791f;
        border: 1px solid #ffe0b2;
        border-radius: 20px;
        padding: 6px 10px;
        font-size: 11px;
    }

    .checkbox-berkas {
        width: 17px;
        height: 17px;
        cursor: pointer;
        accent-color: #3569f6;
    }

    .alert-kesimpulan {
        border-radius: 10px;
        padding: 15px;
    }

    .alert-kesimpulan.lengkap {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #047857;
    }

    .alert-kesimpulan.tidak-lengkap {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #c2410c;
    }

    .btn-simpan-verifikasi {
        background: #102b4c;
        color: white;
        border-radius: 9px;
        padding: 9px 18px;
    }

    .btn-simpan-verifikasi:hover {
        background: #1b416b;
        color: white;
    }

    .btn-kembali {
        border: 1px solid #d5deeb;
        background: white;
        color: #526174;
        border-radius: 9px;
        padding: 9px 18px;
    }

    .btn-kembali:hover {
        background: #f8fafc;
    }
</style>

<h1 class="h4 mb-2 text-gray-800 font-weight-bold">
    Pemeriksaan Berkas Fisik Sidang
</h1>

<p class="mb-4 text-gray-600">
    Lakukan pemeriksaan berkas fisik dan tandai status verifikasi sesuai dengan tanda terima.
</p>


<!-- DATA MAHASISWA -->
<div class="verification-card">

    <div class="verification-card-header">
        <div class="d-flex align-items-center">
            <span class="verification-icon">
                <i class="fas fa-user"></i>
            </span>

            <h6>Data Mahasiswa</h6>
        </div>
    </div>

    <div class="verification-card-body">

        <div class="row">

            <div class="col-md-3 mb-3">
                <div class="info-label">Nama Mahasiswa</div>
                <div class="info-value" id="namaMahasiswa">
          <?= esc($mahasiswa['nama']) ?>
            </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="info-label">NPM</div>
                <div class="info-value" id="npmMahasiswa">
             <?= esc($mahasiswa['npm']) ?>
            </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="info-label">Program Studi</div>
                <div class="info-value">
               <?= esc($mahasiswa['prodi']) ?>
            </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="info-label">Tahun Angkatan</div>
                <div class="info-value">
              <?= esc($mahasiswa['angkatan']) ?>
            </div>
            </div>

        </div>

    </div>
</div>


<!-- DAFTAR BERKAS -->
<div class="verification-card">

    <div class="verification-card-header">

        <div class="d-flex align-items-center">
            <span class="verification-icon">
                <i class="fas fa-file-alt"></i>
            </span>

            <h6>Daftar Persyaratan Berkas Fisik</h6>
        </div>

        <span class="badge badge-light border">
            <span id="jumlahBerkas">5</span> Berkas Wajib
        </span>

    </div>

    <div class="table-responsive">

        <table class="table table-bordered mb-0" id="tabelBerkasFisik">

            <thead>
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th width="38%">Persyaratan</th>
                    <th class="text-center" width="13%">Tanda Terima</th>
                    <th class="text-center" width="20%">Status Verifikasi</th>
                    <th class="text-center" width="15%">Keterangan</th>
                </tr>
            </thead>

            <tbody>

                <?php
                $persyaratan = [
                    [
                        'nama' => 'Bukti Pembayaran',
                        'detail' => '(Biaya Sidang)'
                    ],
                    [
                        'nama' => 'Bebas Pinjam Buku',
                        'detail' => '(Perpustakaan)'
                    ],
                    [
                        'nama' => 'Tanda Bimbingan',
                        'detail' => '(Dosen Pembimbing)'
                    ],
                    [
                        'nama' => 'Formulir Pendaftaran Sidang',
                        'detail' => ''
                    ],
                    [
                        'nama' => 'Kartu Identitas',
                        'detail' => '(KTM / KTP)'
                    ]
                ];

                foreach ($persyaratan as $index => $berkas) :
                ?>

                <tr>

                    <td class="text-center">
                        <?= $index + 1 ?>
                    </td>

                    <td>
                        <span class="font-weight-bold text-gray-800">
                            <?= $berkas['nama'] ?>
                        </span>

                        <?php if (!empty($berkas['detail'])) : ?>
                            <small class="text-muted">
                                <?= $berkas['detail'] ?>
                            </small>
                        <?php endif; ?>
                    </td>

                    <td class="text-center">
                        <input type="checkbox"
                               class="checkbox-berkas"
                               checked>
                    </td>

                    <td class="text-center status-berkas">
                        <span class="badge-verifikasi">
                            <i class="fas fa-check-circle mr-1"></i>
                            Terverifikasi
                        </span>
                    </td>

                    <td class="text-center keterangan-berkas">
                        Sesuai
                    </td>

                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>
</div>


<!-- KESIMPULAN -->
<div id="kesimpulanVerifikasi"
     class="alert-kesimpulan lengkap mb-4">

    <div class="d-flex align-items-start">

        <i class="fas fa-check-circle fa-lg mr-3 mt-1"></i>

        <div>
            <strong id="judulKesimpulan">
                Semua berkas telah diterima dan terverifikasi.
            </strong>

            <div class="small mt-1" id="deskripsiKesimpulan">
                Mahasiswa memenuhi seluruh syarat kelengkapan administrasi berkas fisik sidang skripsi.
            </div>
        </div>

    </div>

</div>


<!-- TOMBOL -->
<div class="d-flex justify-content-end mb-4">

    <a href="<?= base_url('TenagaKependidikan/verifikasiBerkasFisik') ?>"
       class="btn btn-kembali mr-2">

        <i class="fas fa-chevron-left mr-1"></i>
        Kembali

    </a>

    <button type="button"
            class="btn btn-simpan-verifikasi"
            id="btnSimpanDetailVerifikasi">

        <i class="fas fa-save mr-1"></i>
        Simpan Verifikasi

    </button>

</div>


<?= $this->endSection(); ?>


<?= $this->section("scripts"); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const checklist = document.querySelectorAll('.checkbox-berkas');
    const kesimpulan = document.getElementById('kesimpulanVerifikasi');

    function updateVerifikasi() {

        let total = checklist.length;
        let lengkap = 0;

        checklist.forEach(function (checkbox, index) {

            const row = checkbox.closest('tr');
            const statusCell = row.querySelector('.status-berkas');
            const keteranganCell = row.querySelector('.keterangan-berkas');

            if (checkbox.checked) {

                lengkap++;

                statusCell.innerHTML = `
                    <span class="badge-verifikasi">
                        <i class="fas fa-check-circle mr-1"></i>
                        Terverifikasi
                    </span>
                `;

                keteranganCell.textContent = 'Sesuai';

            } else {

                statusCell.innerHTML = `
                    <span class="badge-belum">
                        <i class="fas fa-clock mr-1"></i>
                        Belum terverifikasi
                    </span>
                `;

                keteranganCell.textContent = 'Belum diterima';

            }

        });

        if (lengkap === total) {

            kesimpulan.className = 'alert-kesimpulan lengkap mb-4';

            kesimpulan.innerHTML = `
                <div class="d-flex align-items-start">
                    <i class="fas fa-check-circle fa-lg mr-3 mt-1"></i>
                    <div>
                        <strong>Semua berkas telah diterima dan terverifikasi.</strong>
                        <div class="small mt-1">
                            Mahasiswa memenuhi seluruh syarat kelengkapan administrasi berkas fisik sidang skripsi.
                        </div>
                    </div>
                </div>
            `;

        } else {

            kesimpulan.className = 'alert-kesimpulan tidak-lengkap mb-4';

            kesimpulan.innerHTML = `
                <div class="d-flex align-items-start">
                    <i class="fas fa-exclamation-circle fa-lg mr-3 mt-1"></i>
                    <div>
                        <strong>${lengkap} dari ${total} berkas telah diterima.</strong>
                        <div class="small mt-1">
                            Masih terdapat ${total - lengkap} berkas yang belum diterima atau diverifikasi.
                        </div>
                    </div>
                </div>
            `;

        }

    }


    checklist.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateVerifikasi);
    });


    document.getElementById('btnSimpanDetailVerifikasi')
        .addEventListener('click', function () {

            const total = checklist.length;
            let lengkap = 0;

            checklist.forEach(function (checkbox) {
                if (checkbox.checked) {
                    lengkap++;
                }
            });

            const status = lengkap === total
                ? 'Lengkap'
                : 'Tidak lengkap';

            Swal.fire({
                icon: status === 'Lengkap' ? 'success' : 'warning',
                title: 'Verifikasi Berhasil Disimpan',
                html: `
                    Data pemeriksaan berkas fisik
                    <strong>${document.getElementById('namaMahasiswa').textContent}</strong>
                    berhasil diperbarui.<br><br>
                    Status: <strong>${status}</strong>
                `,
                confirmButtonColor: '#102b4c'
            }).then(function () {

                window.location.href =
                    '<?= base_url("TenagaKependidikan/verifikasiBerkasFisik") ?>';

            });

        });


    updateVerifikasi();

});
</script>

<?= $this->endSection(); ?>