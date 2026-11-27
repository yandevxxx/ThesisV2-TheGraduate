<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keterangan Lulus (SKL) Resmi</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 620px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
                    <!-- HEADER -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 30px 25px; text-align: center; color: #ffffff;">
                            <h1 style="margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px;">UNIVERSITAS YARSI</h1>
                            <p style="margin: 6px 0 0 0; font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 500;">The Graduate &bull; Layanan SKL</p>
                        </td>
                    </tr>

                    <!-- BODY CONTENT -->
                    <tr>
                        <td style="padding: 30px 30px 20px 30px;">
                            <div style="background-color: #ecfdf5; border-left: 4px solid #10b981; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px;">
                                <p style="margin: 0; color: #065f46; font-size: 15px; font-weight: 600;">
                                    ✓ Surat Keterangan Lulus (SKL) Resmi Anda Telah Terbit!
                                </p>
                            </div>

                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #334155;">
                                Yth. Sdr/i <strong><?= esc($nama) ?></strong>,
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Selamat atas kelulusan Anda! Kami informasikan bahwa pengajuan <strong>Surat Keterangan Lulus (SKL)</strong> Anda telah selesai diverifikasi dan diterbitkan secara resmi oleh Bagian Akademik / Tenaga Kependidikan.
                            </p>

                            <!-- DETAIL TABLE -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; margin-bottom: 24px; font-size: 14px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                                <tr style="background-color: #f8fafc;">
                                    <td style="padding: 10px 14px; font-weight: 600; color: #475569; width: 35%; border-bottom: 1px solid #e2e8f0;">Nama Mahasiswa</td>
                                    <td style="padding: 10px 14px; color: #1e293b; border-bottom: 1px solid #e2e8f0;"><strong><?= esc($nama) ?></strong></td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">NPM</td>
                                    <td style="padding: 10px 14px; color: #1e293b; border-bottom: 1px solid #e2e8f0;"><?= esc($npm) ?></td>
                                </tr>
                                <tr style="background-color: #f8fafc;">
                                    <td style="padding: 10px 14px; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">Program Studi</td>
                                    <td style="padding: 10px 14px; color: #1e293b; border-bottom: 1px solid #e2e8f0;"><?= esc($prodi) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">Tanggal Terbit</td>
                                    <td style="padding: 10px 14px; color: #1e293b; border-bottom: 1px solid #e2e8f0;"><?= esc($tgl_terbit) ?></td>
                                </tr>
                                <?php if (! empty($catatan)): ?>
                                <tr style="background-color: #f8fafc;">
                                    <td style="padding: 10px 14px; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">Catatan Tendik</td>
                                    <td style="padding: 10px 14px; color: #64748b; font-style: italic; border-bottom: 1px solid #e2e8f0;"><?= nl2br(esc($catatan)) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>

                            <!-- INFORMATION NOTICE -->
                            <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px;">
                                <p style="margin: 0; color: #1e40af; font-size: 13px; line-height: 1.5;">
                                    ℹ️ <strong>Pemberitahuan Unduh Dokumen:</strong> Berkas fisik Surat Keterangan Lulus (SKL) resmi tidak dilampirkan pada email ini. Anda dapat melihat dan mengunduh berkas SKL resmi secara langsung melalui portal <strong>The Graduate</strong> pada menu <em>Layanan SKL</em> setelah masuk ke akun mahasiswa Anda.
                                </p>
                            </div>

                            <p style="margin: 0 0 6px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Semoga Surat Keterangan Lulus ini bermanfaat untuk kebutuhan karir maupun studi lanjut Anda.
                            </p>
                            <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Salam hangat,<br>
                                <strong>Bagian Akademik &amp; Kemahasiswaan / Tendik</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 30px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8; line-height: 1.5;">
                                Email ini dikirimkan secara otomatis oleh sistem <strong>The Graduate</strong> Universitas YARSI.<br>
                                Harap tidak membalas email ini secara langsung.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
