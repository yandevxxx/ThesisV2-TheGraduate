<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Pilih sistem: Thesis atau The Graduate">
    <meta name="author" content="">

    <title>Pilih Sistem | TheSIS &amp; The Graduate</title>

    <!-- Custom fonts for this template-->
    <link href="<?= base_url("assets/vendor/fontawesome-free/css/all.min.css") ?>" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="<?= base_url("assets/css/sb-admin-2.min.css") ?>" rel="stylesheet">

    <style>
        body.bg-gradient-dark {
            background: linear-gradient(135deg, #1a1c2e 0%, #2d2f45 50%, #1a1c2e 100%) !important;
            min-height: 100vh;
        }

        .choose-title {
            color: #ffffff;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .choose-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 400;
        }

        .choose-user {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.95rem;
        }

        /* Kartu pilihan */
        .choice-card {
            border: 0;
            border-radius: 1rem;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            overflow: hidden;
            text-decoration: none !important;
            display: block;
        }

        .choice-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.35) !important;
            text-decoration: none !important;
        }

        .choice-card .card-body {
            padding: 2.5rem 1.5rem;
        }

        .choice-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        .choice-title {
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: 1px;
        }

        .choice-desc {
            font-size: 0.95rem;
            color: #858796;
        }

        .card-thesis .choice-icon,
        .card-thesis .choice-title {
            color: #4e73df;
        }

        .card-graduate .choice-icon,
        .card-graduate .choice-title {
            color: #1cc88a;
        }

        .card-graduate {
            position: relative;
        }

        .card-graduate .badge-soon {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
    </style>

</head>

<body class="bg-gradient-dark">

    <div class="container">

        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">

            <div class="col-xl-10 col-lg-11 col-md-10">

                <!-- Header -->
                <div class="text-center mb-4">
                    <img src="<?= base_url("assets/img/logo-yarsi.png") ?>" alt="Logo YARSI" style="height: 4.5rem" class="mb-3">
                    <h1 class="choose-title">SILAKAN PILIH SISTEM</h1>
                    <p class="choose-subtitle">Anda masuk sebagai <span class="choose-user font-weight-bold"><?= esc($user['nama'] ?? $user['username'] ?? 'User') ?></span></p>
                </div>

                <!-- Dua Kartu Pilihan -->
                <div class="row justify-content-center">

                    <!-- KARTU 1: THESIS -->
                    <div class="col-lg-5 col-md-6 mb-4">
                        <a href="<?= base_url("graduate/enter/thesis") ?>" class="choice-card card card-thesis shadow">
                            <div class="card-body text-center">
                                <div class="choice-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <h2 class="choice-title">THESIS</h2>
                                <p class="choice-desc mb-0">
                                    Sistem Manajemen Skripsi:<br>
                                    Pengajuan, Seminar, Bimbingan,<br>
                                    Sidang &amp; Berkas Skripsi
                                </p>
                            </div>
                        </a>
                    </div>

                    <!-- KARTU 2: THE GRADUATE -->
                    <div class="col-lg-5 col-md-6 mb-4">
                        <a href="<?= base_url("graduate/enter/graduate") ?>" class="choice-card card card-graduate shadow">
                            <span class="badge badge-warning badge-soon shadow-sm">Segera Hadir</span>
                            <div class="card-body text-center">
                                <div class="choice-icon">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <h2 class="choice-title">THE GRADUATE</h2>
                                <p class="choice-desc mb-0">
                                    Tahap Kelulusan &amp; Alumni:<br>
                                    Status Kelulusan, Dokumen,<br>
                                    Wisuda &amp; Jejak Lulusan
                                </p>
                            </div>
                        </a>
                    </div>

                </div>

                <!-- Footer -->
                <div class="text-center mt-3">
                    <a href="<?= base_url("auth/logout") ?>" class="btn btn-outline-light btn-sm rounded-pill px-3">
                        <i class="fas fa-sign-out-alt fa-fw"></i> Keluar (Logout)
                    </a>
                </div>

            </div>

        </div>

    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="<?= base_url("assets/vendor/jquery/jquery.min.js") ?>"></script>
    <script src="<?= base_url("assets/vendor/bootstrap/js/bootstrap.bundle.min.js") ?>"></script>

</body>

</html>
