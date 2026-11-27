<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-Equiv="Cache-Control" Content="no-cache" />
    <meta http-Equiv="Pragma" Content="no-cache" />
    <meta http-Equiv="Expires" Content="0" />

    <title><?= $title ?> | TheSIS</title>

    <link href="<?= base_url("assets/vendor/fontawesome-free/css/all.min.css") ?>" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="<?= base_url("assets/css/sb-admin-2.min.css") ?>" rel="stylesheet">
    
    <link href="<?= base_url("assets/vendor/datatables/DataTables-1.12.1/css/dataTables.bootstrap4.min.css") ?>" rel="stylesheet">
    <link href="<?= base_url("assets/vendor/datatables/Buttons-2.2.3/css/buttons.bootstrap4.min.css") ?>" rel="stylesheet">
    <link href="<?= base_url("assets/vendor/fullcalendar/main.css") ?>" rel="stylesheet" />
    <link href="<?= base_url("assets/vendor/daterangepicker/daterangepicker.css") ?>" rel="stylesheet" />
    <link href="<?= base_url("assets/css/style.css") ?>" rel="stylesheet">

</head>

<body id="page-top">
    <div id="wrapper">
        <?= $this->include("layout/sidebar_baru"); ?> 
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <?php
                    // Tombol Menu Utama (kembali ke halaman pilihan Thesis / The Graduate)
                    $topbarRoles = session()->get("user_session")['roles'] ?? [];
                    ?>
                    <?php if (! empty($topbarRoles)) : ?>
                        <a href="<?= base_url('graduate/choose') ?>" class="btn btn-primary btn-sm rounded-pill shadow-sm ml-1 mr-2">
                            <i class="fas fa-book fa-fw"></i>
                            <span class="d-none d-md-inline">Menu Utama</span>
                        </a>
                    <?php endif; ?>

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <span class="badge badge-danger badge-counter notif-count-hidden" id="navbar-notif-count">0</span>
                            </a>
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in notif-dropdown-menu"
                            aria-labelledby="alertsDropdown">
                                <div id="navbar-notif-list"
                                data-fetch-url="<?= esc(base_url('TenagaKependidikan/fetchNotif')) ?>"
                                data-mark-read-url="<?= esc(base_url('TenagaKependidikan/markAsRead')) ?>"
                                data-all-notif-url="<?= esc(base_url('TenagaKependidikan/semua_notifikasi')) ?>">
                                <div class="p-3 text-center small text-gray-500">Memuat...</div>
                            </div>

                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= esc(session()->get("user_session")['nama'] ?? '') ?></span>
                                <img class="img-profile rounded-circle" src="<?= base_url("assets/img/undraw_profile.svg") ?>">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <?php 
                                    // Logic Role Link Profile (Dari template-aku.php)
                                    $roles = session()->get("user_session")['roles'] ?? [];
                                    $profileLink = "#"; 
                                    if (in_array("mahasiswa", $roles) || in_array("wisudawan", $roles)) $profileLink = base_url("Mahasiswa/profile");
                                    elseif (in_array("dosen", $roles) || in_array("dospem eksternal", $roles)) $profileLink = base_url("Dosen/profile");
                                    elseif (in_array("tendik", $roles)) $profileLink = base_url("TenagaKependidikan/profile");
                                    elseif (in_array("kaprodi", $roles)) $profileLink = base_url("Kaprodi/profile");
                                    elseif (in_array("fakultas", $roles)) $profileLink = base_url("Fakultas/profile");
                                    elseif (in_array("admin", $roles)) $profileLink = base_url("admin/dashboard");
                                ?>

                                <?php if(!in_array("tamu", $roles)): ?>
                                <a class="dropdown-item" href="<?= $profileLink ?>">
                                    <i class="fas fa-user-cog fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Ubah Data Diri
                                </a>
                                <?php endif; ?>

                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <?= $this->renderSection('content'); ?>
                </div>
            </div>
            
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span><strong class="text-dark">© Thesis-NG</strong></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Yakin ingin keluar?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Pilih "Logout" di bawah jika Anda siap untuk mengakhiri sesi saat ini.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    
                <form action="<?= base_url('auth/logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">Logout</button>
                </form>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url("assets/vendor/jquery/jquery.min.js") ?>"></script>
    <script src="<?= base_url("assets/vendor/bootstrap/js/bootstrap.bundle.min.js") ?>"></script>
    <script src="<?= base_url("assets/vendor/jquery-easing/jquery.easing.min.js") ?>"></script>
    <script src="<?= base_url("assets/js/sb-admin-2.min.js") ?>"></script>
    
    <script src="<?= base_url("assets/js/constant.js") ?>"></script> 

    <script src="<?= base_url("assets/vendor/datatables/DataTables-1.12.1/js/jquery.dataTables.min.js") ?>"></script>
    <script src="<?= base_url("assets/vendor/datatables/DataTables-1.12.1/js/dataTables.bootstrap4.min.js") ?>"></script>
    
    <script src="<?= base_url("assets/vendor/sweetalert2/sweetalert2.all.js") ?>"></script>
    <script src="<?= base_url("assets/vendor/jquery-validate/jquery.validate.min.js") ?>"></script>
    <script src="<?= base_url("assets/vendor/jquery-validate/additional-methods.min.js") ?>"></script>
    <script src="<?= base_url("assets/vendor/jquery-validate/localization/messages_id.js") ?>"></script>

    <script src="<?= base_url("assets/vendor/moment/moment.min.js") ?>"></script>
    <script src="<?= base_url("assets/vendor/daterangepicker/daterangepicker.js") ?>"></script>
    <script src="<?= base_url('assets/js/layout.js') ?>"></script>
    <script src="<?= base_url('assets/js/notifikasi.js') ?>"></script>
    <script src="<?= base_url('assets/js/flashdata.js') ?>"></script>

<?= $this->renderSection('scripts'); ?>

</body>
</html>