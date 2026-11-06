<?= $this->extend("The-Graduate-Web/layout/template"); ?>

<?= $this->section("content"); ?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-certificate text-primary mr-2"></i>Layanan SKL (Surat Keterangan Lulus)</h1>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow mb-4 border-left-warning">
            <div class="card-body text-center py-5 px-4">
                <div class="mb-4">
                    <span class="fa-stack fa-2x text-warning">
                        <i class="fas fa-circle fa-stack-2x" style="opacity: 0.15;"></i>
                        <i class="fas fa-lock fa-stack-1x text-warning"></i>
                    </span>
                </div>
                
                <h4 class="font-weight-bold text-gray-800 mb-3">Layanan SKL Belum Dapat Diakses</h4>
                
                <?php if (empty($pendaftaran) || $statusBiodata === 'DRAFT' || $statusBiodata === '') : ?>
                    <div class="alert alert-warning text-left d-inline-block col-md-10 mb-4">
                        <h6 class="font-weight-bold mb-1"><i class="fas fa-info-circle mr-1"></i> Biodata Wisuda Belum Dikirimkan</h6>
                        <p class="mb-0 small text-gray-700">
                            Untuk dapat mengakses layanan Surat Keterangan Lulus (SKL), Anda diwajibkan mengisi dan mengirimkan <strong>Biodata Wisuda</strong> terlebih dahulu untuk diverifikasi dan disetujui oleh Tenaga Kependidikan (Tendik).
                        </p>
                    </div>
                    <div>
                        <a href="<?= base_url('graduate/biodata_wisuda') ?>" class="btn btn-primary px-4 shadow-sm">
                            <i class="fas fa-id-card fa-fw mr-1"></i> Isi Biodata Wisuda Sekarang
                        </a>
                    </div>
                <?php elseif ($statusBiodata === 'MENUNGGU') : ?>
                    <div class="alert alert-info text-left d-inline-block col-md-10 mb-4">
                        <h6 class="font-weight-bold mb-1"><i class="fas fa-hourglass-half mr-1"></i> Biodata Wisuda Menunggu Verifikasi</h6>
                        <p class="mb-0 small text-gray-700">
                            Biodata Wisuda Anda telah dikirim dan saat ini <strong>sedang menunggu proses verifikasi &amp; persetujuan</strong> oleh Tenaga Kependidikan (Tendik). Layanan SKL akan otomatis terbuka segera setelah biodata Anda disetujui.
                        </p>
                    </div>
                    <div>
                        <a href="<?= base_url('graduate/biodata_wisuda') ?>" class="btn btn-info px-4 shadow-sm">
                            <i class="fas fa-id-card fa-fw mr-1"></i> Cek Status Biodata Wisuda
                        </a>
                    </div>
                <?php elseif ($statusBiodata === 'DITOLAK') : ?>
                    <div class="alert alert-danger text-left d-inline-block col-md-10 mb-4">
                        <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-circle mr-1"></i> Biodata Wisuda Perlu Perbaikan</h6>
                        <p class="mb-0 small text-gray-700">
                            Biodata Wisuda Anda telah ditinjau oleh Tendik dan memerlukan perbaikan. Silakan periksa catatan revisi dari Tendik dan kirimkan kembali biodata yang telah diperbarui.
                        </p>
                        <?php if (!empty($pendaftaran['catatan_biodata'])) : ?>
                            <div class="mt-2 p-2 bg-white rounded border border-danger small text-danger font-weight-bold">
                                Catatan Tendik: <?= esc($pendaftaran['catatan_biodata']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="<?= base_url('graduate/biodata_wisuda') ?>" class="btn btn-danger px-4 shadow-sm">
                            <i class="fas fa-edit fa-fw mr-1"></i> Perbaiki Biodata Wisuda
                        </a>
                    </div>
                <?php endif; ?>

                <div class="mt-4 pt-3 border-top">
                    <a href="<?= base_url('graduate/choose') ?>" class="btn btn-light text-secondary border btn-sm shadow-sm">
                        <i class="fas fa-th-large fa-fw mr-1"></i> Kembali ke Menu Utama
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>
