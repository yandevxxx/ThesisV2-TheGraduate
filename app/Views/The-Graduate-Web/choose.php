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
            background: #ffffff !important;
            min-height: 100vh;
        }

        .choose-title {
            color: #1a1c2e;
            font-weight: 800;
            letter-spacing: 2px;
            font-size: 2rem;
            line-height: 1.3;
        }

        .choose-subtitle {
            color: #858796;
            font-weight: 400;
            font-size: 1rem;
            word-break: break-word;
        }

        .choose-user {
            color: #5a5c69;
            font-size: 0.95rem;
        }

        .choose-logo {
            height: 4.5rem;
            max-width: 100%;
            object-fit: contain;
        }

        /* Kartu pilihan */
        .choice-card {
            border: 1px solid #e3e6f0;
            border-radius: 1rem;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            overflow: hidden;
            text-decoration: none !important;
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
        }

        .choice-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15) !important;
            text-decoration: none !important;
        }

        /* Reset supaya <button> tampil sama seperti kartu */
        button.choice-card {
            font: inherit;
            color: inherit;
            text-align: center;
            background-color: #fff;
            cursor: pointer;
            border: 1px solid #e3e6f0;
        }

        button.choice-card:focus {
            outline: none;
        }

        .choice-card .card-body {
            padding: 2.5rem 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
        }

        .choice-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        .choice-title {
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .choice-desc {
            font-size: 0.95rem;
            color: #858796;
            line-height: 1.5;
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

        /* ---------- FORM PILIH SISTEM ---------- */
        #formPilihSistem.form-box {
            background-color: #ffffff;
            border: 2px solid #4e73df;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
            padding: 2.5rem 2rem;
            margin: auto;
        }

        /* ---------- RESPONSIVENESS ALL DEVICES ---------- */
        /* Layar HP Kecil & Standar (Mobile < 576px) */
        @media (max-width: 575.98px) {
            #formPilihSistem.form-box {
                padding: 1.5rem 1rem !important;
                border-width: 1.5px;
                border-radius: 0.75rem;
            }

            .choose-title {
                font-size: 1.35rem !important;
                letter-spacing: 1px !important;
            }

            .choose-subtitle {
                font-size: 0.85rem !important;
            }

            .choose-logo {
                height: 3.5rem !important;
            }

            .choice-card .card-body {
                padding: 1.5rem 1rem !important;
            }

            .choice-icon {
                font-size: 2.5rem !important;
                margin-bottom: 0.75rem !important;
            }

            .choice-title {
                font-size: 1.25rem !important;
            }

            .choice-desc {
                font-size: 0.85rem !important;
            }

            .choice-desc br {
                display: none; /* Flow text naturally on small mobile screens */
            }
        }

        /* Tablet Portrait / Layar Sedang (576px - 767.98px) */
        @media (min-width: 576px) and (max-width: 767.98px) {
            #formPilihSistem.form-box {
                padding: 2rem 1.5rem;
            }

            .choose-title {
                font-size: 1.65rem;
            }
        }

        /* Hover animation disabled for touch devices */
        @media (hover: none) {
            .choice-card:hover {
                transform: none;
            }
        }
    </style>

</head>

<body class="bg-gradient-dark">

    <div class="container py-4 py-md-5">

        <div class="row justify-content-center align-items-center" style="min-height: 85vh;">

            <div class="col-xl-10 col-lg-11 col-md-11 col-12">

                <form id="formPilihSistem" method="get" action="#" class="form-box">

                    <!-- Header (di dalam form) -->
                    <div class="text-center mb-4">
                        <img src="<?= base_url("assets/img/logo-yarsi.png") ?>" alt="Logo YARSI" class="choose-logo mb-3">
                        <h1 class="choose-title">SILAKAN PILIH SISTEM</h1>
                        <p class="choose-subtitle mb-0">Anda masuk sebagai <span class="choose-user font-weight-bold"><?= esc($user['nama'] ?? $user['username'] ?? 'User') ?></span>
                            <span class="badge badge-light border ml-1"><?= esc($roleLabel ?? 'Pengguna') ?></span>
                        </p>
                    </div>

                    <?php if (empty($canUseGraduate)) : ?>
                        <div class="alert alert-info small text-center" role="alert">
                            <i class="fas fa-info-circle fa-fw"></i>
                            Akun Anda saat ini hanya memiliki akses ke TheSIS. Silakan pilih <strong>THESIS</strong> untuk melanjutkan.
                        </div>
                    <?php endif; ?>

                    <!-- Dua Kartu Pilihan -->
                    <div class="row justify-content-center align-items-stretch">

                        <!-- KARTU 1: THESIS -->
                        <div class="col-lg-5 col-md-6 col-12 mb-4 d-flex">
                            <button type="submit" formmethod="get" formaction="<?= base_url("graduate/enter/thesis") ?>" name="sisi" value="thesis" class="choice-card card card-thesis shadow">
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
                            </button>
                        </div>

                        <?php if (! empty($canUseGraduate)) : ?>
                            <!-- KARTU 2: THE GRADUATE -->
                            <div class="col-lg-5 col-md-6 col-12 mb-4 d-flex">
                                <button type="submit" formmethod="get" formaction="<?= base_url("graduate/enter/graduate") ?>" name="sisi" value="graduate" class="choice-card card card-graduate shadow">
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
                                </button>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Footer (di dalam form) -->
                    <div class="text-center mt-2">
                        <a href="<?= base_url("auth/logout") ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2">
                            <i class="fas fa-sign-out-alt fa-fw"></i> Keluar (Logout)
                        </a>
                    </div>

                </form>

            </div>

        </div>

    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="<?= base_url("assets/vendor/jquery/jquery.min.js") ?>"></script>
    <script src="<?= base_url("assets/vendor/bootstrap/js/bootstrap.bundle.min.js") ?>"></script>

</body>

</html>
