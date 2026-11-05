<?php // Sidebar role admin & tendik (sisi "The Graduate") ?>

<?php
$sidebarUser  = session()->get('user_session') ?: [];
$sidebarRoles = isset($sidebarUser['roles']) && is_array($sidebarUser['roles']) ? $sidebarUser['roles'] : [];
$showSkYudisium = \App\Libraries\AccessControl::hasAnyRole($sidebarRoles, ['tendik', 'staf', 'admin']);
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

    <li class="nav-item <?= (uri_string() === 'graduate/berkas') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/berkas") ?>">
            <i class="fas fa-fw fa-folder"></i>
            <span>Berkas</span>
        </a>
    </li>

    <?php $uri = uri_string(); $pendaftaranOpen = ($uri === 'graduate/pendaftaran_wisuda' || $uri === 'graduate/sk_yudisium' || $uri === 'graduate/persyaratan' || $uri === 'graduate/belum_mendaftar'); ?>
    <li class="nav-item <?= $pendaftaranOpen ? 'active' : '' ?>">
        <a class="nav-link py-2 <?= $pendaftaranOpen ? '' : 'collapsed' ?>" href="#" data-toggle="collapse" data-target="#collapsePendaftaran" aria-expanded="<?= $pendaftaranOpen ? 'true' : 'false' ?>" aria-controls="collapsePendaftaran">
            <i class="fas fa-fw fa-user-plus"></i>
            <span>Pendaftaran Wisuda</span>
        </a>
        <div id="collapsePendaftaran" class="collapse <?= $pendaftaranOpen ? 'show' : '' ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if ($showSkYudisium) : ?>
                <a class="collapse-item <?= ($uri === 'graduate/sk_yudisium') ? 'active' : '' ?>" href="<?= base_url('graduate/sk_yudisium') ?>">SK Yudisium</a>
                <?php endif; ?>
                <a class="collapse-item <?= ($uri === 'graduate/persyaratan') ? 'active' : '' ?>" href="<?= base_url('graduate/persyaratan') ?>">Persyaratan Wisuda</a>
                <a class="collapse-item <?= ($uri === 'graduate/pendaftaran_wisuda') ? 'active' : '' ?>" href="<?= base_url('graduate/pendaftaran_wisuda') ?>">Pendaftaran Wisuda</a>
                <a class="collapse-item <?= ($uri === 'graduate/belum_mendaftar') ? 'active' : '' ?>" href="<?= base_url('graduate/belum_mendaftar') ?>" title="Daftar Mahasiswa Belum Mendaftar">Belum Mendaftar</a>
            </div>
        </div>
    </li>

    <?php
    $sklOpen = ($uri === 'graduate/skl' || $uri === 'graduate/skl_kelola' || $uri === 'graduate/skl_mahasiswa' || $uri === 'graduate/sklKelola' || $uri === 'graduate/sklMahasiswa');
    $isSklKelola = ($uri === 'graduate/skl_kelola' || $uri === 'graduate/sklKelola' || ($uri === 'graduate/skl' && ($tabParam ?? '') === 'kelola'));
    $isSklMahasiswa = ($uri === 'graduate/skl_mahasiswa' || $uri === 'graduate/sklMahasiswa' || ($uri === 'graduate/skl' && ($tabParam ?? '') !== 'kelola'));
    ?>
    <li class="nav-item <?= $sklOpen ? 'active' : '' ?>">
        <a class="nav-link py-2 <?= $sklOpen ? '' : 'collapsed' ?>" href="#" data-toggle="collapse" data-target="#collapseSkl" aria-expanded="<?= $sklOpen ? 'true' : 'false' ?>" aria-controls="collapseSkl">
            <i class="fas fa-fw fa-certificate"></i>
            <span>SKL</span>
        </a>
        <div id="collapseSkl" class="collapse <?= $sklOpen ? 'show' : '' ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item <?= $isSklKelola ? 'active' : '' ?>" href="<?= base_url('graduate/skl_kelola') ?>">SKL Kelola</a>
                <a class="collapse-item <?= $isSklMahasiswa ? 'active' : '' ?>" href="<?= base_url('graduate/skl_mahasiswa') ?>">SKL Mahasiswa</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?= (uri_string() === 'graduate/calon_wisudawan') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/calon_wisudawan") ?>">
            <i class="fas fa-fw fa-user-graduate"></i>
            <span>Calon Wisudawan</span>
        </a>
    </li>

    <!-- Divider -->

    <li class="nav-item <?= (uri_string() === 'graduate/mahasiswa_wisudawan') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url('graduate/mahasiswa_wisudawan') ?>">
            <i class="fas fa-fw fa-graduation-cap"></i>
            <span>Mahasiswa Wisudawan</span>
        </a>
    </li>

    <li class="nav-item <?= (uri_string() === 'graduate/dosen_pembimbing') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url('graduate/dosen_pembimbing') ?>">
            <i class="fas fa-fw fa-chalkboard-teacher"></i>
            <span>Dosen Pembimbing</span>
        </a>
    </li>
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->
