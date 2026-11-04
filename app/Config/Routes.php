<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Auth');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

// Authentication Routes
$routes->get('auth/login', 'Auth::login');
$routes->post('auth/signInAccount', 'Auth::signInAccount', ['filter' => 'csrf']);

// Password Reset Routes
$routes->get('auth/lupa-password', 'Auth::forgotPassword');
$routes->post('auth/forgotSubmit', 'Auth::forgotSubmit');

$routes->get('test-email', 'Auth::testEmail');

$routes->get('auth/resetPassword/(:any)', 'Auth::resetPassword/$1');
$routes->post('auth/forgotSubmit', 'Auth::forgotSubmit');

// User Password Update Route
$routes->get('user/updatePassword', 'Auth::updatePassword');
$routes->post('auth/updatePasswordSubmit', 'Auth::updatePasswordSubmit');

// Route Halaman Admin
$routes->get('admin/dashboard', 'Admin::dashboard');

// ADMIN ROUTES
$routes->get('admin/dashboard', 'Admin::dashboard');
$routes->get('admin/users', 'Admin::manageUsers');
$routes->post('admin/changeRole', 'Admin::changeRole');

// ROUTE KHUSUS FAKULTAS (
$routes->group('fakultas', function ($routes) {
    $routes->get('/', 'Fakultas::dashboard');
    $routes->get('dashboard', 'Fakultas::dashboard');
    $routes->get('repository', 'Fakultas::dashboard');
});

// JALUR UNTUK RESET PASSWORD PAKSA
$routes->post('admin/forceReset', 'Admin::forceResetPassword');


// Dashboard routes


/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Auth::index');

// ROUTE SISI THE GRADUATE (pilihan sisi setelah login & dashboard per role)
$routes->group('graduate', ['namespace' => 'App\Controllers\TheGraduateWeb'], function ($routes) {
    $routes->get('choose', 'Graduate::choose');
    $routes->get('enter/(:segment)', 'Graduate::enter/$1');
    $routes->get('index', 'Graduate::index');
    $routes->get('admin_tendik', 'Graduate::admin_tendik');
    $routes->get('fakultas', 'Graduate::fakultas');
    $routes->get('prodi', 'Graduate::prodi');
    $routes->get('wisudawan', 'Graduate::wisudawan');
    $routes->get('dosen', 'Graduate::dosen');
    $routes->get('mahasiswa', 'Graduate::mahasiswa');
    $routes->get('fetchNotif', 'NotificationController::fetchNotif');
    $routes->get('markAsRead', 'NotificationController::markAsRead');
    $routes->get('semua_notifikasi', 'NotificationController::semua_notifikasi');
    $routes->get('profile', 'ProfileController::profile');
    $routes->post('saveProfile', 'ProfileController::saveProfile');
    $routes->get('semester', 'SemesterController::index');
    $routes->post('semesterStore', 'SemesterController::store');
    $routes->post('semesterUpdate', 'SemesterController::update');
    $routes->post('semesterActivate/(:segment)', 'SemesterController::activate/$1');
    $routes->post('semesterNonactivate/(:segment)', 'SemesterController::nonactivate/$1');
    $routes->post('semesterDelete/(:segment)', 'SemesterController::delete/$1');
    $routes->post('semesterDelete', 'SemesterController::delete');

    // Menu halaman & modul operasional The Graduate
    $routes->get('berkas', 'Graduate::berkas');
    $routes->get('calon_wisudawan', 'Graduate::calon_wisudawan');
    $routes->get('mahasiswa_wisudawan', 'Graduate::mahasiswa_wisudawan');
    $routes->get('dosen_pembimbing', 'Graduate::dosen_pembimbing');
    $routes->get('persyaratan', 'Graduate::persyaratan');
    $routes->get('pendaftaran_wisuda', 'Graduate::pendaftaran_wisuda');
    $routes->get('belum_mendaftar', 'Graduate::belum_mendaftar');
    $routes->get('tracer_studi', 'Graduate::tracer_studi');
    $routes->get('skl', 'Graduate::skl');
    $routes->get('pengambilan_ijazah', 'Graduate::pengambilan_ijazah');
    $routes->get('sk_yudisium', 'SkYudisiumController::index');
    $routes->get('skYudisiumDownloadTemplate', 'SkYudisiumController::downloadTemplate');
    $routes->post('skYudisiumUpload', 'SkYudisiumController::upload');
    $routes->get('skYudisiumPreview', 'SkYudisiumController::preview');
    $routes->post('skYudisiumSave', 'SkYudisiumController::save');
    $routes->post('skYudisiumClearPreview', 'SkYudisiumController::clearPreview');
    $routes->post('skYudisiumSetTanggal', 'SkYudisiumController::setTanggal');
    $routes->post('skYudisiumBulkSetTanggal', 'SkYudisiumController::bulkSetTanggal');
    $routes->post('skYudisiumBulkEdit', 'SkYudisiumController::bulkEdit');
    $routes->post('skYudisiumUpdateStatus', 'SkYudisiumController::updateStatus');
    $routes->post('skYudisiumDelete', 'SkYudisiumController::delete');
    $routes->post('skYudisiumStore', 'SkYudisiumController::store');

    // Generic fallback for any additional Graduate endpoints
    $routes->add('(:segment)', 'Graduate::$1');
    $routes->add('(:segment)/(:any)', 'Graduate::$1/$2');
});

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}




$routes->group('TenagaKependidikan', ['filter' => 'role:tendik'], function($routes) {
    // Rute Halaman Utama (US 13, 14, 15)
    $routes->get('pengajuanSkripsi', 'TenagaKependidikan::pengajuanSkripsi');
    $routes->get('pengajuanPrasidang', 'TenagaKependidikan::pengajuanPrasidang');
    $routes->get('pengajuanSidangSkripsi', 'TenagaKependidikan::pengajuanSidangSkripsi');

    // Rute Notifikasi (AJAX)
    $routes->get('fetchNotif', 'TenagaKependidikan::fetchNotif');
    $routes->get('markAsRead', 'TenagaKependidikan::markAsRead');

    // Rute Halaman Riwayat
    $routes->get('semua_notifikasi', 'TenagaKependidikan::semua_notifikasi');

    // Fitur Hardcover (GET)
    $routes->get('verifikasiHardcover', 'TenagaKependidikan::verifikasiHardcover');
    $routes->get('detailVerifikasiHardcover/(:num)', 'TenagaKependidikan::detailVerifikasiHardcover/$1');
    $routes->get('unggahHardcover', 'TenagaKependidikan::unggahHardcover');

    // Pembimbing Eksternal
    $routes->get('pembimbingEksternal', 'TenagaKependidikan::pembimbingEksternal');
    $routes->post('uploadDokumen', 'TenagaKependidikan::uploadDokumen');
    $routes->get('hapusDokumen/(:num)/(:segment)', 'TenagaKependidikan::hapusDokumen/$1/$2');

    // Hardcover Actions
    $routes->post('setujuiHardcover/(:num)', 'TenagaKependidikan::setujuiHardcover/$1');
    $routes->post('tolakHardcover/(:num)', 'TenagaKependidikan::tolakHardcover/$1');
    $routes->post('simpanHardcoverManual', 'TenagaKependidikan::simpanHardcoverManual');

    // Cetak Berita Acara
    $routes->post('cetakBeritaAcaraSempro', 'TenagaKependidikan::cetakBeritaAcaraSempro');
    $routes->post('cetakBeritaAcaraSidang', 'TenagaKependidikan::cetakBeritaAcaraSidang');
    $routes->post('updateAndCetakBASidang', 'TenagaKependidikan::updateAndCetakBASidang');
});
