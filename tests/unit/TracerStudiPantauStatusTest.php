<?php

namespace Tests\Unit;

use App\Controllers\TheGraduateWeb\TracerStudiController;
use App\Libraries\AccessControl;
use App\Models\JawabanTracerStudiAwalModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite B-14: Pantau status pengisian tracer studi awal (sisi The Graduate Web).
 *
 * @internal
 */
final class TracerStudiPantauStatusTest extends CIUnitTestCase
{
    public function testRbacPantauStatusHanyaUntukStafDanProdi(): void
    {
        foreach (['staf', 'tendik', 'admin', 'prodi', 'kaprodi'] as $allowedRole) {
            $this->assertTrue(
                AccessControl::canAccess('TracerStudiController', 'pantauPengisian', [$allowedRole]),
                "Role {$allowedRole} harus diizinkan memantau status pengisian tracer studi awal."
            );
            $this->assertTrue(
                AccessControl::canAccess('TracerStudiController', 'detailJawaban', [$allowedRole]),
                "Role {$allowedRole} harus diizinkan melihat detail jawaban mahasiswa."
            );
            $this->assertTrue(
                AccessControl::menuVisible('tracer_studi_pantau', [$allowedRole]),
                "Menu pemantauan tracer studi harus tampil untuk role {$allowedRole}."
            );
        }

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'wisudawan'] as $deniedRole) {
            $this->assertFalse(
                AccessControl::canAccess('TracerStudiController', 'pantauPengisian', [$deniedRole]),
                "Role {$deniedRole} tidak boleh mengakses pemantauan tracer studi awal."
            );
            $this->assertFalse(
                AccessControl::canAccess('TracerStudiController', 'detailJawaban', [$deniedRole]),
                "Role {$deniedRole} tidak boleh melihat detail jawaban mahasiswa lain."
            );
            $this->assertFalse(
                AccessControl::menuVisible('tracer_studi_pantau', [$deniedRole])
            );
        }
    }

    public function testStatusBerubahDariBelumMenjadiSudahDanJawabanDapatDilihat(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $npmSudah = 'B14SUDAH' . random_int(100, 999);
        $npmBelum = 'B14BELUM' . random_int(100, 999);

        $db->table('mahasiswa_terplot_sk_yudisium')->insertBatch([
            [
                'npm'           => $npmSudah,
                'nama'          => 'Mahasiswa B14 Sudah',
                'status'        => 'baru',
                'kode_semester' => '2026-2',
                'created_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'npm'           => $npmBelum,
                'nama'          => 'Mahasiswa B14 Belum',
                'status'        => 'transfer',
                'kode_semester' => '2026-2',
                'created_at'    => date('Y-m-d H:i:s'),
            ],
        ]);

        try {
            $jawabanModel    = new JawabanTracerStudiAwalModel();
            $pertanyaanModel = new PertanyaanTracerStudiAwalModel();

            // 1. Awalnya kedua mahasiswa berstatus BELUM
            $before = $jawabanModel->getMonitoringTerplot(['q' => 'Mahasiswa B14']);
            $mapBefore = array_column($before['rows'], null, 'npm');
            $this->assertSame('BELUM', $mapBefore[$npmSudah]['status_tracer'] ?? null);
            $this->assertSame('BELUM', $mapBefore[$npmBelum]['status_tracer'] ?? null);

            // 2. Mahasiswa 1 mengirim form tracer studi awal (B-13)
            $pertanyaan = $pertanyaanModel->getDaftarPertanyaan();
            $answers    = [];
            foreach ($pertanyaan as $q) {
                $qid  = (int) $q['id'];
                $opsi = PertanyaanTracerStudiAwalModel::parseOpsiJawaban($q['opsi_jawaban'] ?? null);
                $answers[$qid] = $opsi !== [] ? $opsi[0] : 'Jawaban B14 Mahasiswa';
            }
            $jawabanModel->saveJawabanMahasiswa($npmSudah, $pertanyaan, $answers, '2026-2');

            // 3. Status mahasiswa 1 berubah menjadi SUDAH, mahasiswa 2 tetap BELUM
            $after = $jawabanModel->getMonitoringTerplot(['q' => 'Mahasiswa B14']);
            $mapAfter = array_column($after['rows'], null, 'npm');
            $this->assertSame('SUDAH', $mapAfter[$npmSudah]['status_tracer'] ?? null);
            $this->assertSame('BELUM', $mapAfter[$npmBelum]['status_tracer'] ?? null);

            // Filter status sudah / belum
            $onlySudah = $jawabanModel->getMonitoringTerplot(['q' => 'Mahasiswa B14', 'status' => 'sudah']);
            $this->assertCount(1, $onlySudah['rows']);
            $this->assertSame($npmSudah, $onlySudah['rows'][0]['npm']);

            $onlyBelum = $jawabanModel->getMonitoringTerplot(['q' => 'Mahasiswa B14', 'status' => 'belum']);
            $this->assertCount(1, $onlyBelum['rows']);
            $this->assertSame($npmBelum, $onlyBelum['rows'][0]['npm']);

            // 4. Jawaban mahasiswa yang sudah mengisi dapat dilihat secara detail
            $detail = $jawabanModel->getDetailJawabanByNpm($npmSudah);
            $this->assertCount(count($pertanyaan), $detail);
            $this->assertSame($answers[(int) $pertanyaan[0]['id']], $detail[0]['jawaban']);
        } finally {
            $db->table('jawaban_tracer_studi_awal')->whereIn('npm', [$npmSudah, $npmBelum])->delete();
            $db->table('mahasiswa_terplot_sk_yudisium')->whereIn('npm', [$npmSudah, $npmBelum])->delete();
        }
    }

    public function testFilterSemesterDefaultAktifDanOpsiSemuaDisamakanDenganSkl(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $previousSemesters = $db->table('semester_graduate')->get()->getResultArray();
        $previousGet       = $_GET;
        $npmAktif          = 'B14SEMA' . random_int(100, 999);
        $npmLama           = 'B14SEML' . random_int(100, 999);
        $kodeAktif         = '2099-2';
        $kodeLama          = '2098-1';
        $now               = date('Y-m-d H:i:s');

        try {
            $db->table('semester_graduate')->emptyTable();
            $db->table('semester_graduate')->insertBatch([
                [
                    'kode'       => $kodeLama,
                    'nama'       => 'Semester Ganjil 2098/2099',
                    'status'     => 'SELESAI',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'kode'       => $kodeAktif,
                    'nama'       => 'Semester Genap 2099/2100',
                    'status'     => 'AKTIF',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            $db->table('mahasiswa_terplot_sk_yudisium')->insertBatch([
                [
                    'npm'           => $npmAktif,
                    'nama'          => 'Mahasiswa Sem Aktif B14',
                    'status'        => 'baru',
                    'kode_semester' => $kodeAktif,
                    'created_at'    => $now,
                ],
                [
                    'npm'           => $npmLama,
                    'nama'          => 'Mahasiswa Sem Lama B14',
                    'status'        => 'baru',
                    'kode_semester' => $kodeLama,
                    'created_at'    => $now,
                ],
            ]);

            session()->set('user_session', [
                'id'    => 9992,
                'nama'  => 'Staf Uji Pantau Semester',
                'name'  => 'Staf Uji Pantau Semester',
                'email' => 'staf.pantau@example.test',
                'roles' => ['staf'],
            ]);

            $makePantau = static function (array $get): string {
                $_SERVER['REQUEST_METHOD'] = 'GET';
                $_GET                      = $get;

                $uri = new URI('http://example.com/graduate/tracer_studi_pantau');
                if ($get !== []) {
                    $uri->setQueryArray($get);
                }
                $request = new IncomingRequest(config('App'), $uri, null, new UserAgent());
                $request->setGlobal('get', $get);

                $controller = new TracerStudiController();
                $controller->initController($request, Services::response(), Services::logger());

                return (string) $controller->pantauPengisian();
            };

            // 1. Tanpa parameter ?semester= -> otomatis filter semester AKTIF, opsi Semua bernilai "semua"
            $defaultHtml = $makePantau([]);
            $this->assertStringContainsString('<option value="semua" >Semua Semester</option>', $defaultHtml);
            $this->assertStringContainsString('<option value="' . $kodeAktif . '" selected>', $defaultHtml);
            $this->assertStringContainsString('(Aktif)', $defaultHtml);
            $this->assertStringContainsString('Mahasiswa Sem Aktif B14', $defaultHtml);
            $this->assertStringContainsString('<td class="text-center align-middle">Semester Genap 2099/2100 (Aktif)</td>', $defaultHtml);
            $this->assertStringNotContainsString('<td class="text-center align-middle">' . $kodeAktif . '</td>', $defaultHtml);
            $this->assertStringNotContainsString('Kelola Pertanyaan', $defaultHtml);
            $this->assertStringNotContainsString('</i> Dasbor', $defaultHtml);
            $this->assertStringNotContainsString('Total Mahasiswa Terplot', $defaultHtml);
            $this->assertStringNotContainsString('Sudah Mengisi Tracer Awal', $defaultHtml);
            $this->assertStringNotContainsString('Belum Mengisi Tracer Awal', $defaultHtml);
            $this->assertStringNotContainsString('Mahasiswa Sem Lama B14', $defaultHtml);
            $this->assertStringNotContainsString('>Reset</a>', $defaultHtml);

            // 2. Pilih Semua Semester (?semester=semua) -> menampilkan semua semester + tombol Reset
            $semuaHtml = $makePantau(['semester' => 'semua']);
            $this->assertStringContainsString('<option value="semua" selected>Semua Semester</option>', $semuaHtml);
            $this->assertStringContainsString('Mahasiswa Sem Aktif B14', $semuaHtml);
            $this->assertStringContainsString('Mahasiswa Sem Lama B14', $semuaHtml);
            $this->assertStringContainsString('>Reset</a>', $semuaHtml);

            // 3. Pilih semester spesifik (?semester=2098-1) -> hanya semester tersebut + tombol Reset
            $lamaHtml = $makePantau(['semester' => $kodeLama]);
            $this->assertStringContainsString('<option value="' . $kodeLama . '" selected>', $lamaHtml);
            $this->assertStringContainsString('Mahasiswa Sem Lama B14', $lamaHtml);
            $this->assertStringContainsString('<td class="text-center align-middle">Semester Ganjil 2098/2099</td>', $lamaHtml);
            $this->assertStringNotContainsString('<td class="text-center align-middle">' . $kodeLama . '</td>', $lamaHtml);
            $this->assertStringNotContainsString('Mahasiswa Sem Aktif B14', $lamaHtml);
            $this->assertStringContainsString('>Reset</a>', $lamaHtml);
        } finally {
            $_GET = $previousGet;
            session()->remove('user_session');
            $db->table('mahasiswa_terplot_sk_yudisium')->whereIn('npm', [$npmAktif, $npmLama])->delete();
            $db->table('semester_graduate')->emptyTable();
            if ($previousSemesters !== []) {
                $db->table('semester_graduate')->insertBatch($previousSemesters);
            }
        }
    }

    public function testKaprodiHanyaBisaPantauDanLihatDetailMahasiswaProdiSendiri(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $previousGet       = $_GET;
        $previousSemesters = $db->table('semester_graduate')->get()->getResultArray();

        $kodeAktif        = '2099-1';
        $npmProdiA        = '2099010001';
        $npmProdiBFallback = '2099020001';
        $npmProdiBDirect  = '2099020002';

        $insertedProdiIds = [];
        $insertedMhsNpms  = [];

        try {
            $prodiRows = $db->table('program_studi')->orderBy('id', 'ASC')->get()->getResultArray();
            if (count($prodiRows) < 2) {
                $prodiFields   = array_map('strtolower', $db->getFieldNames('program_studi'));
                $seedTemplates = [
                    ['nama' => 'Teknik Informatika Test B14', 'inisial' => 'TITST', 'email' => 'kaprodi.ti.tst@unit.test'],
                    ['nama' => 'Sains Data PDSI Test B14', 'inisial' => 'PDSITST', 'email' => 'kaprodi.pdsi.tst@unit.test'],
                ];
                foreach ($seedTemplates as $tpl) {
                    $row = ['nama' => $tpl['nama']];
                    if (in_array('inisial', $prodiFields, true)) {
                        $row['inisial'] = $tpl['inisial'];
                    }
                    if (in_array('email', $prodiFields, true)) {
                        $row['email'] = $tpl['email'];
                    }
                    if (in_array('id_fakultas', $prodiFields, true)) {
                        $row['id_fakultas'] = 1;
                    }
                    $db->table('program_studi')->insert($row);
                    $insertedProdiIds[] = (int) $db->insertID();
                }
                $prodiRows = $db->table('program_studi')->orderBy('id', 'ASC')->get()->getResultArray();
            }

            $prodiA   = $prodiRows[0];
            $prodiB   = $prodiRows[1];
            $prodiAId = (int) $prodiA['id'];
            $prodiBId = (int) $prodiB['id'];
            $this->assertNotSame($prodiAId, $prodiBId);

            // Seed mahasiswa fallback record untuk npmProdiBFallback (terplot.id_prodi sengaja NULL)
            $mhsTemplate = $db->table('mahasiswa')->limit(1)->get()->getRowArray();
            $mhsFields   = array_map('strtolower', $db->getFieldNames('mahasiswa'));
            $mhsInsert   = is_array($mhsTemplate) ? $mhsTemplate : [];
            $mhsInsert['npm']      = $npmProdiBFallback;
            $mhsInsert['nama']     = 'Mahasiswa PDSI Fallback B14';
            $mhsInsert['id_prodi'] = $prodiBId;
            if (in_array('email', $mhsFields, true)) {
                $mhsInsert['email'] = 'pdsi.fallback.b14@unit.test';
            }
            $db->table('mahasiswa')->where('npm', $npmProdiBFallback)->delete();
            $db->table('mahasiswa')->insert($mhsInsert);
            $insertedMhsNpms[] = $npmProdiBFallback;

            $db->table('semester_graduate')->emptyTable();
            $db->table('semester_graduate')->insert([
                'kode'       => $kodeAktif,
                'nama'       => 'Semester Uji Isolasi Prodi B14',
                'status'     => 'AKTIF',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $db->table('mahasiswa_terplot_sk_yudisium')
                ->whereIn('npm', [$npmProdiA, $npmProdiBFallback, $npmProdiBDirect])
                ->delete();

            $db->table('mahasiswa_terplot_sk_yudisium')->insertBatch([
                [
                    'npm'           => $npmProdiA,
                    'nama'          => 'Mahasiswa TI Test B14',
                    'status'        => 'baru',
                    'id_prodi'      => $prodiAId,
                    'kode_semester' => $kodeAktif,
                    'created_at'    => date('Y-m-d H:i:s'),
                ],
                [
                    'npm'           => $npmProdiBFallback,
                    'nama'          => 'Mahasiswa PDSI Fallback B14',
                    'status'        => 'baru',
                    'id_prodi'      => null,
                    'kode_semester' => $kodeAktif,
                    'created_at'    => date('Y-m-d H:i:s'),
                ],
                [
                    'npm'           => $npmProdiBDirect,
                    'nama'          => 'Mahasiswa PDSI Direct B14',
                    'status'        => 'transfer',
                    'id_prodi'      => $prodiBId,
                    'kode_semester' => $kodeAktif,
                    'created_at'    => date('Y-m-d H:i:s'),
                ],
            ]);

            $makeController = static function (string $path, array $get = []): \App\Controllers\TheGraduateWeb\TracerStudiController {
                $_SERVER['REQUEST_METHOD'] = 'GET';
                $_SERVER['REQUEST_URI']    = $path;
                $_GET                      = $get;

                $request = new \CodeIgniter\HTTP\IncomingRequest(
                    new \Config\App(),
                    new \CodeIgniter\HTTP\URI('http://example.com' . $path),
                    null,
                    new \CodeIgniter\HTTP\UserAgent()
                );
                if ($get !== []) {
                    $request->setGlobal('get', $get);
                }

                $controller = new \App\Controllers\TheGraduateWeb\TracerStudiController(
                    new \App\Models\PertanyaanTracerStudiAwalModel(),
                    new \App\Models\JawabanTracerStudiAwalModel(),
                    new \App\Models\MahasiswaTerplotModel()
                );
                $controller->initController(
                    $request,
                    \Config\Services::response(new \Config\App(), false),
                    \Config\Services::logger()
                );

                return $controller;
            };

            // 1. Kaprodi A (TI): hanya melihat Mahasiswa TI, diblok 403 saat akses detail mahasiswa prodi B
            session()->set('user_session', [
                'id'    => $prodiAId,
                'nama'  => 'Kaprodi A',
                'email' => (string) ($prodiA['email'] ?? ''),
                'prodi' => (string) ($prodiA['inisial'] ?? ''),
                'roles' => ['kaprodi'],
            ]);
            $pantauA = $makeController('/graduate/tracer_studi_pantau', ['semester' => $kodeAktif])->pantauPengisian();
            $this->assertIsString($pantauA);
            $this->assertStringContainsString('Mahasiswa TI Test B14', $pantauA);
            $this->assertStringContainsString('Program Studi: ' . (string) $prodiA['nama'], $pantauA);
            $this->assertStringNotContainsString('name="prodi"', $pantauA);
            $this->assertStringNotContainsString('Mahasiswa PDSI Direct B14', $pantauA);
            $this->assertStringNotContainsString('Mahasiswa PDSI Fallback B14', $pantauA);

            // Kaprodi TI tetap terisolasi walau mencoba memaksa query ?prodi=prodiBId
            $pantauATampered = $makeController('/graduate/tracer_studi_pantau', [
                'semester' => $kodeAktif,
                'prodi'    => (string) $prodiBId,
            ])->pantauPengisian();
            $this->assertIsString($pantauATampered);
            $this->assertStringContainsString('Mahasiswa TI Test B14', $pantauATampered);
            $this->assertStringNotContainsString('Mahasiswa PDSI Direct B14', $pantauATampered);
            $this->assertStringNotContainsString('Mahasiswa PDSI Fallback B14', $pantauATampered);

            $detailOwnA = $makeController('/graduate/tracer_studi_pantau/detail/' . $npmProdiA)->detailJawaban($npmProdiA);
            $this->assertIsString($detailOwnA);
            $this->assertStringContainsString('Mahasiswa TI Test B14', $detailOwnA);

            $detailCrossDirect = $makeController('/graduate/tracer_studi_pantau/detail/' . $npmProdiBDirect)->detailJawaban($npmProdiBDirect);
            $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $detailCrossDirect);
            $this->assertSame(403, $detailCrossDirect->getStatusCode());

            $detailCrossFallback = $makeController('/graduate/tracer_studi_pantau/detail/' . $npmProdiBFallback)->detailJawaban($npmProdiBFallback);
            $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $detailCrossFallback);
            $this->assertSame(403, $detailCrossFallback->getStatusCode());

            // 2. Kaprodi B (PDSI, resolve via id_prodi): hanya melihat mahasiswa PDSI (direct & fallback)
            session()->set('user_session', [
                'id'    => $prodiBId,
                'nama'  => 'Kaprodi B',
                'roles' => ['kaprodi'],
            ]);
            $pantauB = $makeController('/graduate/tracer_studi_pantau', ['semester' => $kodeAktif])->pantauPengisian();
            $this->assertIsString($pantauB);
            $this->assertStringContainsString('Mahasiswa PDSI Direct B14', $pantauB);
            $this->assertStringContainsString('Mahasiswa PDSI Fallback B14', $pantauB);
            $this->assertStringContainsString('Program Studi: ' . (string) $prodiB['nama'], $pantauB);
            $this->assertStringNotContainsString('Mahasiswa TI Test B14', $pantauB);

            $detailOwnB = $makeController('/graduate/tracer_studi_pantau/detail/' . $npmProdiBFallback)->detailJawaban($npmProdiBFallback);
            $this->assertIsString($detailOwnB);
            $this->assertStringContainsString('Mahasiswa PDSI Fallback B14', $detailOwnB);

            $detailCrossA = $makeController('/graduate/tracer_studi_pantau/detail/' . $npmProdiA)->detailJawaban($npmProdiA);
            $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $detailCrossA);
            $this->assertSame(403, $detailCrossA->getStatusCode());

            // 3. Akun Prodi/Kaprodi tanpa pemetaan prodi valid: fail-closed (0 data & 403 di detail)
            session()->set('user_session', [
                'id'    => 'UNMAPPED-PRODI',
                'nama'  => 'Prodi Unmapped',
                'roles' => ['prodi'],
            ]);
            $pantauUnmapped = $makeController('/graduate/tracer_studi_pantau', ['semester' => $kodeAktif])->pantauPengisian();
            $this->assertIsString($pantauUnmapped);
            $this->assertStringNotContainsString('Mahasiswa TI Test B14', $pantauUnmapped);
            $this->assertStringNotContainsString('Mahasiswa PDSI Direct B14', $pantauUnmapped);
            $this->assertStringContainsString('Akun Program Studi belum terhubung', $pantauUnmapped);

            $detailUnmapped = $makeController('/graduate/tracer_studi_pantau/detail/' . $npmProdiA)->detailJawaban($npmProdiA);
            $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $detailUnmapped);
            $this->assertSame(403, $detailUnmapped->getStatusCode());

            // 4. Staf / Tendik / Admin memantau semua prodi secara global dan bisa memfilter per prodi (?prodi=...)
            session()->set('user_session', [
                'id'    => 'STAF-B14',
                'nama'  => 'Staf Global',
                'roles' => ['staf'],
            ]);
            $pantauStaf = $makeController('/graduate/tracer_studi_pantau', ['semester' => $kodeAktif])->pantauPengisian();
            $this->assertIsString($pantauStaf);
            $this->assertStringContainsString('name="prodi"', $pantauStaf);
            $this->assertStringContainsString('<option value="">Semua Prodi</option>', $pantauStaf);
            $this->assertStringContainsString('Mahasiswa TI Test B14', $pantauStaf);
            $this->assertStringContainsString('Mahasiswa PDSI Fallback B14', $pantauStaf);
            $this->assertStringContainsString('Mahasiswa PDSI Direct B14', $pantauStaf);
            $this->assertStringNotContainsString('Akun Program Studi belum terhubung', $pantauStaf);

            // 5. Tendik memfilter prodi A (TI saja) dan prodi B (PDSI saja), serta Admin
            session()->set('user_session', [
                'id'    => 'TENDIK-B14',
                'nama'  => 'Tendik FTI',
                'roles' => ['tendik'],
            ]);
            $pantauTendikTi = $makeController('/graduate/tracer_studi_pantau', [
                'semester' => $kodeAktif,
                'prodi'    => (string) $prodiAId,
            ])->pantauPengisian();
            $this->assertIsString($pantauTendikTi);
            $this->assertStringContainsString('<option value="' . $prodiAId . '" selected>', $pantauTendikTi);
            $this->assertStringContainsString('Mahasiswa TI Test B14', $pantauTendikTi);
            $this->assertStringNotContainsString('Mahasiswa PDSI Direct B14', $pantauTendikTi);
            $this->assertStringNotContainsString('Mahasiswa PDSI Fallback B14', $pantauTendikTi);
            $this->assertStringContainsString('>Reset</a>', $pantauTendikTi);

            $pantauTendikPdsi = $makeController('/graduate/tracer_studi_pantau', [
                'semester' => $kodeAktif,
                'prodi'    => (string) $prodiBId,
            ])->pantauPengisian();
            $this->assertIsString($pantauTendikPdsi);
            $this->assertStringContainsString('<option value="' . $prodiBId . '" selected>', $pantauTendikPdsi);
            $this->assertStringContainsString('Mahasiswa PDSI Direct B14', $pantauTendikPdsi);
            $this->assertStringContainsString('Mahasiswa PDSI Fallback B14', $pantauTendikPdsi);
            $this->assertStringNotContainsString('Mahasiswa TI Test B14', $pantauTendikPdsi);

            session()->set('user_session', [
                'id'    => 1,
                'nama'  => 'Admin FTI',
                'roles' => ['admin'],
            ]);
            $pantauAdminTi = $makeController('/graduate/tracer_studi_pantau', [
                'semester' => $kodeAktif,
                'prodi'    => (string) $prodiAId,
            ])->pantauPengisian();
            $this->assertIsString($pantauAdminTi);
            $this->assertStringContainsString('Mahasiswa TI Test B14', $pantauAdminTi);
            $this->assertStringNotContainsString('Mahasiswa PDSI Direct B14', $pantauAdminTi);
        } finally {
            $_GET = $previousGet;
            session()->remove('user_session');
            $db->table('mahasiswa_terplot_sk_yudisium')
                ->whereIn('npm', [$npmProdiA, $npmProdiBFallback, $npmProdiBDirect])
                ->delete();
            if ($insertedMhsNpms !== []) {
                $db->table('mahasiswa')->whereIn('npm', $insertedMhsNpms)->delete();
            }
            if ($insertedProdiIds !== []) {
                $db->table('program_studi')->whereIn('id', $insertedProdiIds)->delete();
            }
            $db->table('semester_graduate')->emptyTable();
            if ($previousSemesters !== []) {
                $db->table('semester_graduate')->insertBatch($previousSemesters);
            }
        }
    }

    private function ensureTables(\CodeIgniter\Database\BaseConnection $db): void
    {
        $forge = \Config\Database::forge();

        if (! $db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            $forge->addField([
                'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'npm'                 => ['type' => 'VARCHAR', 'constraint' => 20],
                'nama'                => ['type' => 'VARCHAR', 'constraint' => 150],
                'id_prodi'            => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'baru'],
                'tanggal_sk_yudisium' => ['type' => 'DATE', 'null' => true],
                'kode_semester'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'created_at'          => ['type' => 'DATETIME', 'null' => true],
                'updated_at'          => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('mahasiswa_terplot_sk_yudisium', true);
        }

        if (! $db->tableExists('pertanyaan_tracer_studi_awal')) {
            $forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'urutan'          => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
                'teks_pertanyaan' => ['type' => 'TEXT'],
                'tipe_jawaban'    => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'TEKS'],
                'opsi_jawaban'    => ['type' => 'TEXT', 'null' => true],
                'is_wajib'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'AKTIF'],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('pertanyaan_tracer_studi_awal', true);
            $db->table('pertanyaan_tracer_studi_awal')->insertBatch(PertanyaanTracerStudiAwalModel::defaultQuestions());
        }

        if (! $db->tableExists('jawaban_tracer_studi_awal')) {
            $forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'npm'             => ['type' => 'VARCHAR', 'constraint' => 25],
                'pertanyaan_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'urutan'          => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
                'teks_pertanyaan' => ['type' => 'TEXT'],
                'jawaban'         => ['type' => 'TEXT', 'null' => true],
                'kode_semester'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'submitted_at'    => ['type' => 'DATETIME', 'null' => true],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->addKey('npm');
            $forge->addKey(['npm', 'pertanyaan_id'], false, true);
            $forge->createTable('jawaban_tracer_studi_awal', true);
        }

        if (! $db->tableExists('semester_graduate')) {
            $forge->addField([
                'kode'       => ['type' => 'VARCHAR', 'constraint' => 20],
                'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
                'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'NONAKTIF'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('kode', true);
            $forge->createTable('semester_graduate', true);
        }

        if (! $db->tableExists('program_studi')) {
            $forge->addField([
                'id'      => ['type' => 'INT', 'auto_increment' => true],
                'nama'    => ['type' => 'VARCHAR', 'constraint' => 150],
                'inisial' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'email'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('program_studi', true);
        }

        if (! $db->tableExists('mahasiswa')) {
            $forge->addField([
                'npm'      => ['type' => 'VARCHAR', 'constraint' => 20],
                'nama'     => ['type' => 'VARCHAR', 'constraint' => 150],
                'id_prodi' => ['type' => 'INT', 'null' => true],
            ]);
            $forge->addKey('npm', true);
            $forge->createTable('mahasiswa', true);
        }
    }
}
