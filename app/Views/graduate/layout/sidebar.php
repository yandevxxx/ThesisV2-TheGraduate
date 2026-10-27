<?php
// Sidebar sisi "The Graduate" - memilih file sidebar sesuai folder role.
// Pemetaan role terpusat di App\Libraries\PortalRouter.

$userSession = session()->get('user_session');
$roles = $userSession ? \App\Libraries\PortalRouter::normalizeRoles($userSession) : [];
$sidebarFolder = $userSession ? \App\Libraries\PortalRouter::graduateFolder($roles) : null;
?>

<?php if ($sidebarFolder !== null): ?>

<?= $this->include("graduate/{$sidebarFolder}/sidebar") ?>

<?php else: ?>

<!-- Role tanpa akses Graduate: sidebar minimal (logo + tombol toggle) -->

<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" style="background-color: #1C476C;">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= base_url('graduate/choose') ?>">

        <div class="sidebar-brand-icon">

            <img src="<?= base_url("assets/img/skripsi.png") ?>" alt="Logo" style="height: 3.5rem">

        </div>

        <div class="sidebar-brand-text mx-3">THE GRADUATE</div>

    </a>

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->

<?php endif; ?>
