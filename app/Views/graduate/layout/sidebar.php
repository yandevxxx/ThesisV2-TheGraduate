<?php // Sidebar khusus sisi "The Graduate" ?>

<?php
// Safe roles helper (disamakan dengan sisi Thesis)
$session = session()->get('user_session');
$roles = [];
if ($session) {
    if (isset($session['roles']) && is_array($session['roles'])) {
        $roles = array_map('strtolower', $session['roles']);
    } elseif (isset($session['role'])) {
        $roles = [strtolower($session['role'])];
    }
}
?>

<!-- Sidebar -->

<ul class="navbar-nav bg-gradient-dark sidebar sidebar-dark accordion" id="accordionSidebar">

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

    <!-- Menu pindah sisi (Sisi Thesis) kini ada di navbar (topbar) -->

    <!-- Divider -->

    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->
