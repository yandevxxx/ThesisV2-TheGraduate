<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Lulus (SKL) - <?= esc($mhs['nama'] ?? 'Mahasiswa') ?></title>
    <style>
        @page {
            margin: 1.8cm 2.2cm 1.8cm 2.2cm;
            size: A4 portrait;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.35;
            color: #000;
        }

        /* HEADER / KOP SURAT */
        .kop-header {
            text-align: center;
            border-bottom: 2.5px solid #000;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }
        .kop-logo {
            height: 80px;
            width: auto;
        }

        /* JUDUL SURAT */
        .judul-container {
            text-align: center;
            margin-bottom: 18px;
        }
        .judul-surat {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .nomor-surat {
            font-size: 10.5pt;
            margin-top: 3px;
        }

        /* ISI SURAT */
        .paragraf {
            text-align: justify;
            margin-bottom: 10px;
            text-indent: 35px;
        }
        .paragraf-tanpa-indent {
            text-align: justify;
            margin-bottom: 10px;
        }

        /* TABEL BIODATA */
        .tabel-biodata {
            width: 100%;
            margin-left: 20px;
            margin-bottom: 14px;
            border-collapse: collapse;
        }
        .tabel-biodata td {
            vertical-align: top;
            padding: 3px 0;
            font-size: 11pt;
        }
        .col-key {
            width: 185px;
        }
        .col-titik {
            width: 15px;
            text-align: center;
        }
        .col-val {
            font-weight: bold;
        }

        /* BOX KELULUSAN */
        .box-kelulusan {
            margin: 10px 0 16px 0;
            text-align: justify;
        }

        /* TANDA TANGAN */
        .ttd-container {
            width: 100%;
            margin-top: 25px;
        }
        .ttd-box {
            float: right;
            width: 48%;
            text-align: left;
        }
        .ttd-space {
            height: 70px;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }
        .ttd-nip {
            margin-top: 2px;
            font-size: 10pt;
        }

        /* WATERMARK / DRAFT NOTICE JIKA BELUM ADA NOMOR */
        .watermark-draft {
            font-size: 9pt;
            color: #6c757d;
            font-style: italic;
            margin-top: 25px;
            border-top: 1px dashed #ccc;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT RESMI -->
    <div class="kop-header">
        <?php if (!empty($logo_src)): ?>
            <img class="kop-logo" src="<?= $logo_src ?>" alt="Kop Surat FTI">
        <?php else: ?>
            <div style="font-size: 14pt; font-weight: bold; text-transform: uppercase;">Universitas YARSI</div>
            <div style="font-size: 12pt; font-weight: bold;">Fakultas Teknologi Informasi</div>
            <div style="font-size: 9pt;">Menara YARSI, Jl. Letjend Suprapto Cempaka Putih, Jakarta Pusat 10510</div>
        <?php endif; ?>
    </div>

    <!-- JUDUL & NOMOR SURAT -->
    <div class="judul-container">
        <div class="judul-surat">SURAT KETERANGAN LULUS</div>
        <div class="nomor-surat">Nomor: <?= esc($no_surat ?? ('... / FTI-UY / SKL / ' . date('m') . ' / ' . date('Y'))) ?></div>
    </div>

    <!-- PEMBUKA -->
    <div class="paragraf-tanpa-indent">
        Dekan Fakultas Teknologi Informasi Universitas YARSI dengan ini menerangkan dengan sebenarnya bahwa:
    </div>

    <!-- BIODATA MAHASISWA -->
    <table class="tabel-biodata">
        <tr>
            <td class="col-key">Nama Lengkap</td>
            <td class="col-titik">:</td>
            <td class="col-val"><?= esc($mhs['nama'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="col-key">Nomor Pokok Mahasiswa (NPM)</td>
            <td class="col-titik">:</td>
            <td class="col-val"><?= esc($mhs['npm'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="col-key">Tempat, Tanggal Lahir</td>
            <td class="col-titik">:</td>
            <td class="col-val"><?= esc($mhs['ttl'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="col-key">Program Studi</td>
            <td class="col-titik">:</td>
            <td class="col-val"><?= esc($mhs['prodi'] ?? 'Teknik Informatika') ?></td>
        </tr>
        <tr>
            <td class="col-key">Jenjang Pendidikan</td>
            <td class="col-titik">:</td>
            <td class="col-val">Strata Satu (S1)</td>
        </tr>
        <tr>
            <td class="col-key">Tanggal Yudisium / Kelulusan</td>
            <td class="col-titik">:</td>
            <td class="col-val"><?= esc($mhs['tanggal_lulus'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="col-key">Indeks Prestasi Kumulatif (IPK)</td>
            <td class="col-titik">:</td>
            <td class="col-val"><?= esc($mhs['ipk'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="col-key">Judul Tugas Akhir / Skripsi</td>
            <td class="col-titik">:</td>
            <td class="col-val" style="font-weight: normal; font-style: italic;">"<?= esc($mhs['judul_skripsi'] ?? '-') ?>"</td>
        </tr>
    </table>

    <!-- KETERANGAN KELULUSAN -->
    <div class="box-kelulusan">
        <p class="paragraf">
            Yang bersangkutan telah menyelesaikan seluruh beban studi kurikulum yang dipersyaratkan serta dinyatakan <strong>LULUS</strong> pada Sidang Yudisium Fakultas Teknologi Informasi Universitas YARSI. Yang bersangkutan berhak menyandang gelar akademik <strong><?= esc($mhs['gelar'] ?? 'Sarjana Komputer (S.Kom.)') ?></strong> beserta segala hak dan wewenang yang melekat pada gelar tersebut.
        </p>
        <p class="paragraf">
            Surat Keterangan Lulus (SKL) ini diterbitkan sebagai bukti kelulusan yang sah untuk dipergunakan sebagaimana mestinya, menunggu diterbitkannya Ijazah dan Transkrip Akademik resmi.
        </p>
    </div>

    <!-- TANDA TANGAN PEJABAT -->
    <div class="ttd-container">
        <div class="ttd-box">
            <div>Jakarta, <?= esc($tanggal_surat ?? date('d F Y')) ?></div>
            <div style="margin-top: 4px;">Dekan Fakultas Teknologi Informasi,</div>
            <div class="ttd-space"></div>
            <div class="ttd-nama"><?= esc($pejabat['nama'] ?? 'Dr. Ummi Azizah Rachmawati, S.Kom., M.Cs.') ?></div>
            <div class="ttd-nip">NIP / NIDN: <?= esc($pejabat['nidn'] ?? '0317097301') ?></div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <!-- FOOTER KETERANGAN DRAF -->
    <div class="watermark-draft">
        * Dokumen ini merupakan draf template resmi sistem The Graduate - FTI Universitas YARSI. Berikan nomor surat resmi, tanda tangan, dan stempel fakultas sebelum mengunggah kembali ke sistem untuk dikirimkan kepada mahasiswa.
    </div>

</body>
</html>
