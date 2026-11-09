<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Lulus - <?= esc($mhs['nama'] ?? 'Mahasiswa') ?></title>
    <style>
        @page {
            margin: 2.2cm 2.6cm 2.2cm 2.6cm;
            size: A4 portrait;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.35;
            color: #000;
        }

        /* KOP SURAT LOGO */
        .kop-container {
            margin-bottom: 25px;
        }
        .kop-logo {
            width: 190px;
            height: auto;
            display: block;
        }

        /* JUDUL SURAT & NOMOR */
        .judul-container {
            text-align: center;
            margin-top: 15px;
            margin-bottom: 30px;
        }
        .judul-surat {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13.5pt;
            font-weight: bold;
            display: inline-block;
            border-bottom: 1.5px solid #000;
            padding-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .nomor-surat {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            margin-top: 5px;
        }

        /* PARAGRAF ISI */
        .paragraf {
            text-align: justify;
            margin-bottom: 16px;
            line-height: 1.45;
        }

        /* DATA DIRI MAHASISWA */
        .tabel-data {
            width: 100%;
            margin: 18px 0 24px 0;
            border-collapse: collapse;
        }
        .tabel-data td {
            vertical-align: top;
            padding: 3.5px 0;
            font-size: 12pt;
            font-weight: bold;
        }
        .col-label {
            width: 185px;
            white-space: nowrap;
        }
        .col-colon {
            width: 18px;
            text-align: left;
        }
        .col-isi {
            text-align: left;
        }

        /* BLOK TANDA TANGAN */
        .ttd-wrapper {
            width: 100%;
            margin-top: 35px;
        }
        .ttd-box {
            margin-left: 54%;
            width: 46%;
            text-align: left;
            line-height: 1.35;
        }
        .ttd-space {
            height: 75px;
        }
        .ttd-nama {
            font-weight: bold;
        }

        /* TEMBUSAN */
        .tembusan-box {
            margin-top: 45px;
            font-size: 10pt;
            line-height: 1.35;
        }
        .tembusan-box .tembusan-title {
            margin-bottom: 3px;
        }
        .tembusan-box ol {
            margin: 0;
            padding-left: 18px;
        }
    </style>
</head>
<body>

    <!-- KOP LOGO FTI UNIVERSITAS YARSI -->
    <div class="kop-container">
        <?php if (!empty($logo_src)): ?>
            <img class="kop-logo" src="<?= $logo_src ?>" alt="Universitas YARSI - Fakultas Teknologi Informasi">
        <?php else: ?>
            <div style="font-family: Arial, sans-serif; font-size: 14pt; font-weight: bold; color: #08793c;">UNIVERSITAS YARSI</div>
            <div style="font-family: Arial, sans-serif; font-size: 11pt; font-weight: bold; color: #e67e22;">FAKULTAS TEKNOLOGI INFORMASI</div>
        <?php endif; ?>
    </div>

    <!-- JUDUL DAN NOMOR SURAT -->
    <div class="judul-container">
        <div class="judul-surat">SURAT KETERANGAN LULUS</div>
        <div class="nomor-surat">No. <?= esc($no_surat) ?></div>
    </div>

    <!-- PEMBUKA -->
    <div class="paragraf">
        Yang bertanda tangan di bawah ini, Dekan&nbsp; Fakultas Teknologi Informasi Universitas YARSI&nbsp; menerangkan bahwa :
    </div>

    <!-- DATA DIRI MAHASISWA -->
    <table class="tabel-data">
        <tr>
            <td class="col-label">N a m a</td>
            <td class="col-colon">:</td>
            <td class="col-isi"><?= esc($mhs['nama'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="col-label">N P M</td>
            <td class="col-colon">:</td>
            <td class="col-isi"><?= esc($mhs['npm'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="col-label">Tempat, tanggal lahir</td>
            <td class="col-colon">:</td>
            <td class="col-isi"><?= esc($mhs['ttl'] ?? '-') ?></td>
        </tr>
    </table>

    <!-- KETERANGAN KELULUSAN & GELAR -->
    <div class="paragraf">
        Sesuai dengan Hasil Rapat Yudisium <?= esc($mhs['nama_prodi_resmi'] ?? 'Program Studi Teknik Informatika Program Sarjana') ?> tanggal <?= esc($mhs['tanggal_lulus'] ?? '-') ?> dinyatakan <strong>LULUS</strong> dari <?= esc($mhs['nama_prodi_resmi'] ?? 'Program Studi Teknik Informatika Program Sarjana') ?> Fakultas Teknologi Informasi Universitas YARSI, berhak memperoleh gelar akademis <strong><?= esc($mhs['gelar'] ?? 'Sarjana Komputer (S.Kom.)') ?></strong>.
    </div>

    <!-- PENUTUP -->
    <div class="paragraf">
        Demikian surat keterangan lulus ini dibuat untuk kepentingan yang bersangkutan dan berlaku sampai dikeluarkan ijazah asli.
    </div>

    <!-- BLOK TANDA TANGAN DEKAN -->
    <div class="ttd-wrapper">
        <div class="ttd-box">
            <div>Jakarta, <?= esc($tanggal_surat ?? date('j F Y')) ?></div>
            <div><?= esc($pejabat['jabatan'] ?? 'DEKAN FAKULTAS TEKNOLOGI INFORMASI') ?></div>
            <div class="ttd-space"></div>
            <div class="ttd-nama"><?= esc($pejabat['nama'] ?? 'Dr. Ummi Azizah Rachmawati, S.Kom, M.Kom.') ?></div>
        </div>
    </div>

    <!-- TEMBUSAN -->
    <div class="tembusan-box">
        <div class="tembusan-title">Tembusan :</div>
        <div>1. <?= esc($tembusan_prodi ?? 'Kepala Prodi TI') ?></div>
        <div>2. Arsip</div>
    </div>

</body>
</html>
