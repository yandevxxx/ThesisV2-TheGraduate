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
    }
}
