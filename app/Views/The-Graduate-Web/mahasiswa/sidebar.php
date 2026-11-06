<?php
// Sidebar role mahasiswa (sisi "The Graduate")
$sidebarUser = session()->get('user_session') ?: [];
$sidebarNpm  = $sidebarUser['npm'] ?? $sidebarUser['id'] ?? null;
$isPlottedStudent = false;
$isBiodataApproved = false;
if ($sidebarNpm) {
    $db = \Config\Database::connect();
    if ($db->tableExists('mahasiswa_terplot_sk_yudisium')) {
        $isPlottedStudent = $db->table('mahasiswa_terplot_sk_yudisium')
            ->where('npm', $sidebarNpm)
            ->countAllResults() > 0;
    }
    if ($db->tableExists('pendaftaran_wisuda')) {
        $rowPendaftaran = $db->table('pendaftaran_wisuda')
            ->where('npm', $sidebarNpm)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();
        $isBiodataApproved = (strtoupper((string) ($rowPendaftaran['status_biodata'] ?? '')) === 'DISETUJUI');
    }
}
?>

<!-- Sidebar -->

<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" style="background-color: #1C476C;">

    <!-- Sidebar - Brand -->

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= base_url('graduate/index') ?>">

        <div class="sidebar-brand-icon">

            <img src="<?= base_url("assets/img/skripsi.png") ?>" alt="Logo" style="height: 3.5rem">

        </div>

        <div class="sidebar-brand-text mx-3">THE GRADUATE</div>

    </a>

    <!-- Divider -->

    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        Beranda
    </div>

    <li class="nav-item <?= (uri_string() === 'graduate/index' || uri_string() === 'graduate') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/index") ?>">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dasbor</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        The Graduate
    </div>

    <li class="nav-item <?= (uri_string() === 'graduate/biodata_wisuda' || uri_string() === 'graduate/pendaftaran_wisuda') ? 'active' : '' ?>">
        <a class="nav-link py-2 d-flex align-items-center justify-content-between" href="<?= base_url("graduate/biodata_wisuda") ?>" <?= ! $isPlottedStudent ? 'title="Belum terdaftar di SK Yudisium (Terkunci)"' : '' ?>>
            <span>
                <i class="fas fa-fw fa-id-card"></i>
                <span>Biodata Mahasiswa</span>
            </span>
            <?php if (! $isPlottedStudent) : ?>
                <span class="badge badge-warning text-dark ml-1" title="Terkunci: Belum masuk SK Yudisium"><i class="fas fa-lock fa-xs"></i></span>
            <?php endif; ?>
        </a>
    </li>

    <li class="nav-item <?= (uri_string() === 'graduate/berkas_wisuda') ? 'active' : '' ?>">
        <a class="nav-link py-2 d-flex align-items-center justify-content-between" href="<?= base_url("graduate/berkas_wisuda") ?>" <?= ! $isPlottedStudent ? 'title="Belum terdaftar di SK Yudisium (Terkunci)"' : '' ?>>
            <span>
                <i class="fas fa-fw fa-file-alt"></i>
                <span>Berkas Wisuda</span>
            </span>
            <?php if (! $isPlottedStudent) : ?>
                <span class="badge badge-warning text-dark ml-1" title="Terkunci: Belum masuk SK Yudisium"><i class="fas fa-lock fa-xs"></i></span>
            <?php endif; ?>
        </a>
    </li>

    <?php
    $sklLocked = (! $isPlottedStudent || ! $isBiodataApproved);
    $sklLockTitle = ! $isPlottedStudent 
        ? 'Belum terdaftar di SK Yudisium (Terkunci)' 
        : (! $isBiodataApproved ? 'Biodata belum disetujui (Terkunci)' : '');
    ?>
    <li class="nav-item <?= (uri_string() === 'graduate/skl') ? 'active' : '' ?>">
        <a class="nav-link py-2 d-flex align-items-center justify-content-between" href="<?= base_url("graduate/skl") ?>" <?= $sklLocked ? 'title="' . esc($sklLockTitle) . '"' : '' ?>>
            <span>
                <i class="fas fa-fw fa-certificate"></i>
                <span>SKL</span>
            </span>
            <?php if ($sklLocked) : ?>
                <span class="badge badge-warning text-dark ml-1" title="<?= esc($sklLockTitle) ?>"><i class="fas fa-lock fa-xs"></i></span>
            <?php endif; ?>
        </a>
    </li>

    <li class="nav-item <?= (uri_string() === 'graduate/pengambilan_ijazah') ? 'active' : '' ?>">
        <a class="nav-link py-2 d-flex align-items-center justify-content-between" href="<?= base_url("graduate/pengambilan_ijazah") ?>" <?= ! $isPlottedStudent ? 'title="Belum terdaftar di SK Yudisium (Terkunci)"' : '' ?>>
            <span>
                <i class="fas fa-fw fa-book-open"></i>
                <span>Pengambilan Ijazah</span>
            </span>
            <?php if (! $isPlottedStudent) : ?>
                <span class="badge badge-warning text-dark ml-1" title="Terkunci: Belum masuk SK Yudisium"><i class="fas fa-lock fa-xs"></i></span>
            <?php endif; ?>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->
