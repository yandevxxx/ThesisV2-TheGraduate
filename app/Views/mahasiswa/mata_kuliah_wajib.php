<?= $this->extend("layout/template"); ?>

<?= $this->section("content"); ?>

<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">
        Status Mata Kuliah Wajib
    </h1>

    <p class="mb-4 text-gray-600">
        Halaman ini menampilkan status mata kuliah wajib yang harus
        diselesaikan sebagai salah satu syarat pengajuan tugas akhir.
    </p>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Daftar Mata Kuliah Wajib
            </h6>
        </div>

        <div class="card-body">

            <div id="loadingMataKuliah" class="text-center py-3">
                Memuat data mata kuliah...
            </div>

            <div id="pesanError" class="alert alert-danger d-none"></div>

            <div class="table-responsive d-none" id="containerTabel">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead class="text-center">
                        <tr>
                            <th>No</th>
                            <th>Kode Mata Kuliah</th>
                            <th>Nama Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody id="tabelMataKuliah">
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const loading = document.getElementById('loadingMataKuliah');
    const pesanError = document.getElementById('pesanError');
    const containerTabel = document.getElementById('containerTabel');
    const tabelMataKuliah = document.getElementById('tabelMataKuliah');

    fetch('<?= base_url("api/mahasiswa/mata-kuliah") ?>')
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal mengambil data mata kuliah.');
            }

            return response.json();
        })
        .then(response => {
            loading.classList.add('d-none');

            if (response.status !== 'success') {
                throw new Error(response.message || 'Data gagal dimuat.');
            }

            const data = response.data;
            const mataKuliah = data.mata_kuliah || [];
            const pilihan = data.pilihan_mahasiswa || {};

            const sudahLulus = (pilihan.mk_wajib_sudah_lulus || '')
                .split(',')
                .filter(kode => kode.trim() !== '');

            const sedangDiambil = (pilihan.mk_sedang_diambil || '')
                .split(',')
                .filter(kode => kode.trim() !== '');

            const akanDiambil = (pilihan.mk_akan_diambil || '')
                .split(',')
                .filter(kode => kode.trim() !== '');

            const mataKuliahWajib = mataKuliah.filter(mk =>
                mk.kategori === 'Wajib'
            );

            if (mataKuliahWajib.length === 0) {
                pesanError.classList.remove('d-none');
                pesanError.textContent = 'Tidak ada data mata kuliah wajib.';
                return;
            }

            mataKuliahWajib.forEach((mk, index) => {
                let status = '';
                let badge = '';

                if (sudahLulus.includes(mk.kode_matkul)) {
                    status = 'Lulus';
                    badge = 'success';
                } else if (sedangDiambil.includes(mk.kode_matkul)) {
                    status = 'Sedang Diambil';
                    badge = 'info';
                } else if (akanDiambil.includes(mk.kode_matkul)) {
                    status = 'Belum Lulus';
                    badge = 'warning';
                } else {
                    status = 'Data Status Belum Tersedia';
                    badge = 'secondary';
                }

                tabelMataKuliah.innerHTML += `
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td>${mk.kode_matkul}</td>
                        <td>${mk.nama_matkul}</td>
                        <td class="text-center">${mk.sks}</td>
                        <td class="text-center">
                            <span class="badge badge-${badge}">
                                ${status}
                            </span>
                        </td>
                    </tr>
                `;
            });

            containerTabel.classList.remove('d-none');
        })
        .catch(error => {
            loading.classList.add('d-none');
            pesanError.classList.remove('d-none');
            pesanError.textContent = error.message;
        });
});
</script>


<?= $this->endSection(); ?>