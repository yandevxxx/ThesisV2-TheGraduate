<?php // Sidebar sisi "The Graduate" — hanya memilih file sidebar sesuai role.
// Setiap role punya file sendiri:
//   admin & tendik -> graduate/admin_tendik/sidebar.php  (isinya sama, jadi satu folder)
//   dosen          -> graduate/dosen/sidebar.php         (dospem eksternal ikut ke sini)
//   mahasiswa      -> graduate/mahasiswa/sidebar.php

$session = session()->get('user_session');
$roles = [];
if ($session) {
    if (isset($session['roles']) && is_array($session['roles'])) {
        $roles = array_map('strtolower', $session['roles']);
    } elseif (isset($session['role'])) {
        $roles = [strtolower($session['role'])];
    }
}

// Prioritas: admin & tendik > dosen > mahasiswa
$sidebarFolder = null;

if (in_array('admin', $roles) || in_array('tendik', $roles)) {
    $sidebarFolder = 'admin_tendik';
} elseif (in_array('dosen', $roles) || in_array('dospem eksternal', $roles)) {
    $sidebarFolder = 'dosen';
} elseif (in_array('mahasiswa', $roles)) {
    $sidebarFolder = 'mahasiswa';
}
?>

<?php if ($sidebarFolder !== null): ?>

<?= $this->include("graduate/{$sidebarFolder}/sidebar") ?>

<?php else: ?>

<!-- Role di luar 4 role utama: sidebar minimal (logo + tombol toggle) -->

<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" style="background-color: #1C476C;">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= base_url('graduate/index') ?>">

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
