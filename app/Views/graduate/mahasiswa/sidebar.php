<?php // Sidebar role mahasiswa (sisi "The Graduate") ?>

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

    <li class="nav-item <?= (uri_string() === 'graduate/pendaftaran_wisuda') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/pendaftaran_wisuda") ?>">
            <i class="fas fa-fw fa-user-plus"></i>
            <span>Pendaftaran Wisuda</span>
        </a>
    </li>

    <li class="nav-item <?= (uri_string() === 'graduate/skl') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/skl") ?>">
            <i class="fas fa-fw fa-certificate"></i>
            <span>SKL</span>
        </a>
    </li>

    <li class="nav-item <?= (uri_string() === 'graduate/pengambilan_ijazah') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/pengambilan_ijazah") ?>">
            <i class="fas fa-fw fa-book-open"></i>
            <span>Pengambilan Ijazah</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->