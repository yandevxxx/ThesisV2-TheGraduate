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
$routes->post('auth/logout', 'Auth::logout', ['filter' => 'csrf']);
$routes->get('auth/logout', 'Auth::logoutMethodNotAllowed');
$routes->get('Auth/logout', 'Auth::logoutMethodNotAllowed');

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
    $routes->get('hasil-sempro', 'Fakultas::hasilSempro');
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
    $routes->get('alumni', 'Graduate::alumni');
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
    $routes->post('calon_wisudawan/approve/(:num)', 'PendaftaranWisudaController::approveWisudawan/$1');
    $routes->post('calon_wisudawan/batal/(:num)', 'PendaftaranWisudaController::batalWisudawan/$1');
    $routes->get('mahasiswa_wisudawan', 'Graduate::mahasiswa_wisudawan');
    $routes->get('dosen_pembimbing', 'Graduate::dosen_pembimbing');
    $routes->get('persyaratan', 'PersyaratanWisudaController::index');
    $routes->post('persyaratanStore', 'PersyaratanWisudaController::store');
    $routes->post('persyaratanUpdate', 'PersyaratanWisudaController::update');
    $routes->post('persyaratanDelete/(:segment)', 'PersyaratanWisudaController::delete/$1');
    $routes->post('persyaratanDelete', 'PersyaratanWisudaController::delete');
    $routes->post('persyaratanToggleStatus/(:segment)', 'PersyaratanWisudaController::toggleStatus/$1');
    $routes->post('persyaratanToggleStatus', 'PersyaratanWisudaController::toggleStatus');
    $routes->post('persyaratanReorder', 'PersyaratanWisudaController::reorder');
    $routes->get('pendaftaran_wisuda', 'PendaftaranWisudaController::index');
    $routes->post('pendaftaran_wisuda', 'PendaftaranWisudaController::submit');
    $routes->post('pendaftaranWisudaSubmit', 'PendaftaranWisudaController::submit');
    $routes->post('pendaftaranWisudaVerifikasi', 'PendaftaranWisudaController::verifikasi');
    $routes->post('pendaftaranWisudaUploadBerkas', 'PendaftaranWisudaController::uploadBerkas');
    $routes->post('pendaftaranWisudaHapusBerkas/(:num)', 'PendaftaranWisudaController::hapusBerkas/$1');
    $routes->post('pendaftaranWisudaHapusBerkas', 'PendaftaranWisudaController::hapusBerkas');
    $routes->get('berkas/view/(:segment)', 'PendaftaranWisudaController::previewBerkas/$1');
    $routes->get('foto/view/(:segment)', 'PendaftaranWisudaController::previewBerkas/$1');

    // Biodata Wisuda (Mahasiswa & Tendik)
    $routes->get('biodata_wisuda', 'PendaftaranWisudaController::biodata');
    $routes->post('biodata_wisuda', 'PendaftaranWisudaController::submitBiodata');
    $routes->post('biodata_wisuda/submit', 'PendaftaranWisudaController::submitBiodata');
    $routes->post('biodataWisudaSubmit', 'PendaftaranWisudaController::submitBiodata');
    $routes->get('biodata_wisuda_verifikasi', 'PendaftaranWisudaController::indexBiodataTendik');
    $routes->get('biodata_wisuda_verifikasi/export', 'PendaftaranWisudaController::exportBiodataExcel');
    $routes->get('biodataWisudaExport', 'PendaftaranWisudaController::exportBiodataExcel');
    $routes->post('biodataWisudaVerifikasi', 'PendaftaranWisudaController::verifikasiBiodata');

    // Berkas Persyaratan Wisuda (Mahasiswa & Tendik)
    $routes->get('berkas_wisuda', 'PendaftaranWisudaController::berkasWisuda');
    $routes->post('berkas_wisuda', 'PendaftaranWisudaController::submitBerkas');
    $routes->post('berkas_wisuda/submit', 'PendaftaranWisudaController::submitBerkas');
    $routes->post('berkasWisudaSubmit', 'PendaftaranWisudaController::submitBerkas');
    $routes->get('berkas_wisuda_verifikasi', 'PendaftaranWisudaController::indexBerkasTendik');
    $routes->post('berkasWisudaVerifikasi', 'PendaftaranWisudaController::verifikasiBerkas');
    $routes->get('belum_mendaftar', 'Graduate::belum_mendaftar');
    $routes->get('tracer_studi', 'TracerStudiController::formMahasiswa');
    $routes->post('tracer_studi', 'TracerStudiController::submitMahasiswa');
    $routes->post('tracerStudiSubmit', 'TracerStudiController::submitMahasiswa');
    $routes->get('pertanyaan_tracer_studi', 'TracerStudiController::daftarPertanyaan');
    $routes->post('pertanyaan_tracer_studi', 'TracerStudiController::storePertanyaan');
    $routes->post('pertanyaan_tracer_studi/store', 'TracerStudiController::storePertanyaan');
    $routes->post('pertanyaanTracerStudiStore', 'TracerStudiController::storePertanyaan');
    $routes->post('pertanyaan_tracer_studi/tampil', 'TracerStudiController::simpanTampilPertanyaan');
    $routes->post('pertanyaanTracerStudiTampil', 'TracerStudiController::simpanTampilPertanyaan');
    $routes->post('pertanyaan_tracer_studi/urutan', 'TracerStudiController::simpanUrutanPertanyaan');
    $routes->post('pertanyaanTracerStudiUrutan', 'TracerStudiController::simpanUrutanPertanyaan');
    $routes->post('pertanyaan_tracer_studi/update', 'TracerStudiController::updatePertanyaan');
    $routes->post('pertanyaan_tracer_studi/update/(:num)', 'TracerStudiController::updatePertanyaan/$1');
    $routes->post('pertanyaanTracerStudiUpdate', 'TracerStudiController::updatePertanyaan');
    $routes->post('pertanyaan_tracer_studi/delete', 'TracerStudiController::deletePertanyaan');
    $routes->post('pertanyaan_tracer_studi/delete/(:num)', 'TracerStudiController::deletePertanyaan/$1');
    $routes->post('pertanyaanTracerStudiDelete', 'TracerStudiController::deletePertanyaan');
    $routes->post('pertanyaan_tracer_studi/toggle_wajib', 'TracerStudiController::toggleWajibPertanyaan');
    $routes->post('pertanyaan_tracer_studi/toggle_wajib/(:num)', 'TracerStudiController::toggleWajibPertanyaan/$1');
    $routes->post('pertanyaanTracerStudiToggleWajib', 'TracerStudiController::toggleWajibPertanyaan');
    $routes->get('pertanyaan_tracer_studi/pratinjau', 'TracerStudiController::pratinjau');
    $routes->get('pertanyaanTracerStudiPratinjau', 'TracerStudiController::pratinjau');
    $routes->post('pertanyaan_tracer_studi/pratinjau', 'TracerStudiController::submitPratinjau');
    $routes->get('pertanyaan_tracer_studi/pratinjau/lanjut', 'TracerStudiController::pratinjauLanjut');
    $routes->post('pertanyaan_tracer_studi/pratinjau/reset', 'TracerStudiController::resetPratinjau');
    $routes->post('pertanyaan_tracer_studi/ambil_bank', 'TracerStudiController::ambilDariBank');
    $routes->post('pertanyaanTracerStudiAmbilBank', 'TracerStudiController::ambilDariBank');
    $routes->post('pertanyaan_tracer_studi/simpan_ke_bank', 'TracerStudiController::simpanKeBank');
    $routes->post('pertanyaan_tracer_studi/simpan_ke_bank/(:num)', 'TracerStudiController::simpanKeBank/$1');
    $routes->post('pertanyaanTracerStudiSimpanKeBank', 'TracerStudiController::simpanKeBank');
    $routes->post('pertanyaan_tracer_studi/nonaktifkan', 'TracerStudiController::nonaktifkanKeBank');
    $routes->post('pertanyaan_tracer_studi/nonaktifkan/(:num)', 'TracerStudiController::nonaktifkanKeBank/$1');
    $routes->post('pertanyaanTracerStudiNonaktifkan', 'TracerStudiController::nonaktifkanKeBank');
    $routes->get('bank_pertanyaan_tracer_studi', 'TracerStudiController::bankPertanyaan');
    $routes->get('bankPertanyaanTracerStudi', 'TracerStudiController::bankPertanyaan');
    $routes->post('bank_pertanyaan_tracer_studi', 'TracerStudiController::storeBankPertanyaan');
    $routes->post('bank_pertanyaan_tracer_studi/store', 'TracerStudiController::storeBankPertanyaan');
    $routes->post('bankPertanyaanTracerStudiStore', 'TracerStudiController::storeBankPertanyaan');
    $routes->post('bank_pertanyaan_tracer_studi/update', 'TracerStudiController::updateBankPertanyaan');
    $routes->post('bank_pertanyaan_tracer_studi/update/(:num)', 'TracerStudiController::updateBankPertanyaan/$1');
    $routes->post('bankPertanyaanTracerStudiUpdate', 'TracerStudiController::updateBankPertanyaan');
    $routes->post('bank_pertanyaan_tracer_studi/delete', 'TracerStudiController::deleteBankPertanyaan');
    $routes->post('bank_pertanyaan_tracer_studi/delete/(:num)', 'TracerStudiController::deleteBankPertanyaan/$1');
    $routes->post('bankPertanyaanTracerStudiDelete', 'TracerStudiController::deleteBankPertanyaan');
    $routes->post('bank_pertanyaan_tracer_studi/ambil', 'TracerStudiController::ambilDariBank');
    $routes->get('tracer_studi_pantau', 'TracerStudiController::pantauPengisian');
    $routes->get('tracer_studi_pantau/detail/(:segment)', 'TracerStudiController::detailJawaban/$1');

    // Modul SKL (Surat Keterangan Lulus) & Persyaratan Dinamis
    $routes->get('skl', 'SklController::index');
    $routes->get('skl_kelola', 'SklController::sklKelola');
    $routes->get('skl_mahasiswa', 'SklController::sklMahasiswa');
    $routes->get('sklKelola', 'SklController::sklKelola');
    $routes->get('sklMahasiswa', 'SklController::sklMahasiswa');
    $routes->post('sklUploadBerkas', 'SklController::uploadBerkas');
    $routes->post('sklHapusBerkas/(:num)', 'SklController::hapusBerkas/$1');
    $routes->post('sklHapusBerkas', 'SklController::hapusBerkas');
    $routes->post('sklSubmit', 'SklController::submit');
    $routes->post('sklVerifikasi', 'SklController::verifikasi');
    $routes->post('sklProses', 'SklController::ubahKeProses');
    $routes->get('sklDownloadTemplate/(:num)/(:segment)', 'SklController::downloadTemplateSkl/$1/$2');
    $routes->get('skl_download_template/(:num)/(:segment)', 'SklController::downloadTemplateSkl/$1/$2');
    $routes->get('sklDownloadTemplate/(:num)', 'SklController::downloadTemplateSkl/$1');
    $routes->get('skl_download_template/(:num)', 'SklController::downloadTemplateSkl/$1');
    $routes->post('sklKirim', 'SklController::kirimSkl');
    $routes->get('sklDownloadResmi/(:num)', 'SklController::downloadSklResmi/$1');
    $routes->get('skl_download_resmi/(:num)', 'SklController::downloadSklResmi/$1');
    $routes->get('sklDownloadResmi', 'SklController::downloadSklResmi');
    $routes->get('skl_download_resmi', 'SklController::downloadSklResmi');
    $routes->get('skl/preview/(:segment)', 'SklController::previewBerkas/$1');
    $routes->post('persyaratanSklStore', 'SklController::storePersyaratan');
    $routes->post('persyaratanSklUpdate', 'SklController::updatePersyaratan');
    $routes->post('persyaratanSklDelete/(:segment)', 'SklController::deletePersyaratan/$1');
    $routes->post('persyaratanSklDelete', 'SklController::deletePersyaratan');
    $routes->post('persyaratanSklToggleStatus/(:segment)', 'SklController::toggleStatusPersyaratan/$1');
    $routes->post('persyaratanSklToggleStatus', 'SklController::toggleStatusPersyaratan');

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
    $routes->post('skYudisiumBulkDelete', 'SkYudisiumController::bulkDelete');
    $routes->post('skYudisiumUpdateStatus', 'SkYudisiumController::updateStatus');
    $routes->post('skYudisiumDelete', 'SkYudisiumController::delete');
    $routes->post('skYudisiumStore', 'SkYudisiumController::store');

    // Generic fallback for any additional Graduate endpoints
    $routes->add('(:segment)', 'Graduate::$1');
    $routes->add('(:segment)/(:any)', 'Graduate::$1/$2');
});

/*
|--------------------------------------------------------------------------
| ROUTE KELOLA MATA KULIAH KAPRODI
|--------------------------------------------------------------------------
*/

// Halaman daftar mata kuliah
$routes->get('Kaprodi/mataKuliah', 'Kaprodi::mataKuliah');

// API GET: mengambil daftar mata kuliah prodi
$routes->get(
    'api/kaprodi/mata-kuliah',
    'Kaprodi::apiMataKuliah'
);

// API GET: mengambil daftar jenis tugas akhir berdasarkan prodi Kaprodi
$routes->get(
    'api/kaprodi/jenis-tugas-akhir',
    'Kaprodi::apiJenisTugasAkhir'
);

// API POST: menambahkan mata kuliah ke prodi
$routes->post(
    'api/kaprodi/mata-kuliah',
    'Kaprodi::apiSimpanMataKuliah'
);

// API PUT: memperbarui mata kuliah
$routes->put(
    'api/kaprodi/mata-kuliah/(:segment)',
    'Kaprodi::apiUpdateMataKuliah/$1'
);

// API DELETE: menghapus mata kuliah
$routes->delete(
    'api/kaprodi/mata-kuliah/(:segment)',
    'Kaprodi::apiHapusMataKuliah/$1'
);

// Form tambah mata kuliah yang sudah ada
$routes->post(
    'Kaprodi/simpanMataKuliah',
    'Kaprodi::simpanMataKuliah'
);
// Edit mata kuliah
$routes->post(
    'Kaprodi/updateMataKuliah',
    'Kaprodi::updateMataKuliah'
);

// Hapus mata kuliah
$routes->post(
    'Kaprodi/hapusMataKuliah',
    'Kaprodi::hapusMataKuliah'
);

// ==========================================
// ROUTE KELOLA BIDANG KAPRODI
// ==========================================

// Halaman daftar bidang
$routes->get(
    'Kaprodi/bidang',
    'Kaprodi::bidang'
);

// API GET: Mengambil daftar bidang
$routes->get(
    'api/kaprodi/bidang',
    'Kaprodi::apiBidang'
);

// API POST: Menambahkan bidang
$routes->post(
    'api/kaprodi/bidang',
    'Kaprodi::apiSimpanBidang'
);

// API PUT: Mengedit bidang
$routes->put(
    'api/kaprodi/bidang/(:num)',
    'Kaprodi::apiUpdateBidang/$1'
);

// API DELETE: Menghapus bidang
$routes->delete(
    'api/kaprodi/bidang/(:num)',
    'Kaprodi::apiHapusBidang/$1'
);

// ==========================================
// ROUTE KELOLA JENIS TUGAS AKHIR KAPRODI
// ==========================================

// Halaman jenis tugas akhir
$routes->get(
    'Kaprodi/jenisTugasAkhir',
    'Kaprodi::jenisTugasAkhir'
);

// API GET: Mengambil daftar jenis tugas akhir berdasarkan prodi
$routes->get(
    'api/kaprodi/jenis-tugas-akhir',
    'Kaprodi::apiJenisTugasAkhir'
);

// API POST: Menambahkan jenis tugas akhir
$routes->post(
    'api/kaprodi/jenis-tugas-akhir',
    'Kaprodi::apiSimpanJenisTugasAkhir'
);

// API PUT: Mengubah jenis tugas akhir
$routes->put(
    'api/kaprodi/jenis-tugas-akhir/(:num)',
    'Kaprodi::apiUpdateJenisTugasAkhir/$1'
);


// API DELETE: Menghapus jenis tugas akhir dengan pemeriksaan relasi
$routes->delete(
    'api/kaprodi/jenis-tugas-akhir/(:num)',
    'Kaprodi::apiHapusJenisTugasAkhir/$1'
);


/*
|--------------------------------------------------------------------------
| API MATA KULIAH MAHASISWA
|--------------------------------------------------------------------------
*/

// GET: Membaca daftar mata kuliah dan pilihan mahasiswa
$routes->get(
    'api/mahasiswa/mata-kuliah',
    'Mahasiswa::apiMataKuliahMahasiswa'
);

// POST: Menyimpan pilihan mata kuliah mahasiswa
$routes->post(
    'api/mahasiswa/mata-kuliah',
    'Mahasiswa::apiSimpanMataKuliahMahasiswa'
);

// GET: Mengambil alasan penolakan pengajuan skripsi
$routes->get(
    'api/mahasiswa/alasan-penolakan',
    'Mahasiswa::apiAlasanPenolakan'
);

// API POST: Menyimpan keputusan pengajuan penulisan skripsi oleh Tendik
$routes->post(
    'api/tendik/pengajuan-skripsi/(:segment)/keputusan',
    'TenagaKependidikan::apiSimpanKeputusanPengajuanSkripsi/$1',
    ['filter' => 'role:tendik']
);

// POST: Download file KRS mahasiswa
    $routes->post(
        'Mahasiswa/downloadKrs/(:segment)',
        'Mahasiswa::downloadKrs/$1'
    );

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
    $routes->get(
    'detailPengajuanSkripsi/(:segment)',
    'TenagaKependidikan::detailPengajuanSkripsi/$1'
);

$routes->post(
    'balasKomentarPengajuanSkripsi/(:segment)',
    'TenagaKependidikan::balasKomentarPengajuanSkripsi/$1'
);
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

/* * ====================================================================
 * SOLUSI: TARUH ROUTE POST DI LUAR GROUP (Global Route)
 * Ini mem-bypass filter route yang mungkin bermasalah/error
 * ====================================================================
 */

// Hardcover Actions
$routes->post('TenagaKependidikan/setujuiHardcover/(:num)', 'TenagaKependidikan::setujuiHardcover/$1');
$routes->post('TenagaKependidikan/tolakHardcover/(:num)', 'TenagaKependidikan::tolakHardcover/$1');
$routes->post('TenagaKependidikan/simpanHardcoverManual', 'TenagaKependidikan::simpanHardcoverManual');

// Cetak Berita Acara (YANG TADI ERROR)
$routes->post('TenagaKependidikan/cetakBeritaAcaraSempro', 'TenagaKependidikan::cetakBeritaAcaraSempro');
$routes->post('TenagaKependidikan/cetakBeritaAcaraSidang', 'TenagaKependidikan::cetakBeritaAcaraSidang');
$routes->post('TenagaKependidikan/updateAndCetakBASidang', 'TenagaKependidikan::updateAndCetakBASidang');
