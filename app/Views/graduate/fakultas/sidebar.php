<?php // Sidebar role fakultas (sisi "The Graduate") ?>

<!-- Sidebar -->

<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" style="background-color: #1C476C;">

    <!-- Sidebar - Brand -->

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= base_url('graduate/fakultas') ?>">

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

    <li class="nav-item <?= (uri_string() === 'graduate/fakultas') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/fakultas") ?>">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dasbor</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        Pengelolaan
    </div>

    <li class="nav-item <?= (uri_string() === 'graduate/semester') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/semester") ?>">
            <i class="fas fa-fw fa-calendar-alt"></i>
            <span>Semester</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        Monitoring & Laporan
    </div>

    <li class="nav-item <?= (uri_string() === 'graduate/calon_wisudawan') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/calon_wisudawan") ?>">
            <i class="fas fa-fw fa-user-graduate"></i>
            <span>Calon Wisudawan</span>
        </a>
    </li>

    <li class="nav-item <?= (uri_string() === 'graduate/skl') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/skl") ?>">
            <i class="fas fa-fw fa-certificate"></i>
            <span>SKL</span>
        </a>
    </li>

    <li class="nav-item <?= (uri_string() === 'graduate/berkas') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/berkas") ?>">
            <i class="fas fa-fw fa-folder"></i>
            <span>Berkas</span>
        </a>
    </li>

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

