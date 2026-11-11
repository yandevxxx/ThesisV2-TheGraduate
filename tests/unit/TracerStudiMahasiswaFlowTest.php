<?php

namespace Tests\Unit;

use App\Controllers\TheGraduateWeb\PendaftaranWisudaController;
use App\Controllers\TheGraduateWeb\TracerStudiController;
use App\Libraries\AccessControl;
use App\Models\JawabanTracerStudiAwalModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Test\CIUnitTestCase;

class TracerStudiMahasiswaFlowTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTablesExist(\Config\Database::connect());
    }

    public function testRbacAllowsOnlyMahasiswaForTracerFormAndSubmitWhilePreservingB10(): void
    {
        $this->assertTrue(AccessControl::canAccess('Graduate', 'tracer_studi', ['mahasiswa']));
        $this->assertTrue(AccessControl::canAccess('TracerStudiController', 'formMahasiswa', ['mahasiswa']));
        $this->assertTrue(AccessControl::canAccess('TracerStudiController', 'submitMahasiswa', ['mahasiswa']));

        foreach (['tendik', 'staf', 'prodi', 'kaprodi', 'dosen', 'wisudawan'] as $nonMahasiswaRole) {
            $this->assertFalse(
                AccessControl::canAccess('TracerStudiController', 'formMahasiswa', [$nonMahasiswaRole]),
                "Role {$nonMahasiswaRole} tidak boleh mengakses form pengisian Tracer Studi Mahasiswa."
            );
            $this->assertFalse(
                AccessControl::canAccess('TracerStudiController', 'submitMahasiswa', [$nonMahasiswaRole]),
                "Role {$nonMahasiswaRole} tidak boleh mengirim form Tracer Studi Mahasiswa."
            );
        }

        // B-10 tetap terjaga: Staf & Prodi membuka pertanyaan_tracer_studi, Mahasiswa & Dosen tetap ditolak
        foreach (['tendik', 'staf', 'prodi', 'kaprodi'] as $allowedListRole) {
            $this->assertTrue(AccessControl::canAccess('TracerStudiController', 'daftarPertanyaan', [$allowedListRole]));
        }
        $this->assertFalse(AccessControl::canAccess('TracerStudiController', 'daftarPertanyaan', ['mahasiswa']));
        $this->assertFalse(AccessControl::canAccess('TracerStudiController', 'daftarPertanyaan', ['dosen']));
    }

    public function testValidateJawabanSetRejectsMissingRequiredAndInvalidOptions(): void
    {
        $pertanyaan = PertanyaanTracerStudiAwalModel::defaultQuestions();

        // Kosong total -> pertanyaan wajib (urutan 1,3,4,5,6,7) error, urutan 2 & 8 opsional tidak error
        $emptyResult = JawabanTracerStudiAwalModel::validateJawabanSet($pertanyaan, []);
        $this->assertNotEmpty($emptyResult['errors']);
        $this->assertArrayHasKey(1, $emptyResult['errors']);
        $this->assertArrayNotHasKey(2, $emptyResult['errors']);

        // Pilihan ganda invalid -> ditolak
        $invalidOptionInput = [];
        foreach ($pertanyaan as $q) {
            $qid  = (int) $q['id'];
            $opsi = PertanyaanTracerStudiAwalModel::parseOpsiJawaban($q['opsi_jawaban']);
            $invalidOptionInput[$qid] = $opsi !== [] ? $opsi[0] : 'Jawaban valid';
        }
        $invalidOptionInput[1] = 'Pilihan Mengarang Bebas';

        $invalidResult = JawabanTracerStudiAwalModel::validateJawabanSet($pertanyaan, $invalidOptionInput);
        $this->assertArrayHasKey(1, $invalidResult['errors']);

        // Semua valid (opsional dikosongkan) -> bebas error
        $validInput = $invalidOptionInput;
        $validInput[1] = 'Sudah bekerja';
        $validInput[2] = '';
        $validInput[8] = '';

        $validResult = JawabanTracerStudiAwalModel::validateJawabanSet($pertanyaan, $validInput);
        $this->assertSame([], $validResult['errors']);
        $this->assertSame('Sudah bekerja', $validResult['answers'][1]);
    }

    public function testMahasiswaMustCompleteTracerStudiBeforeBiodata(): void
    {
        $db  = \Config\Database::connect();
        $npm = 'TESTTRACER' . random_int(1000, 9999);
        $db->table('mahasiswa_terplot_sk_yudisium')->insert([
            'npm'           => $npm,
            'nama'          => 'Mahasiswa Uji Tracer',
            'status'        => 'baru',
            'kode_semester' => '2026-2',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        try {
            session()->set('user_session', [
                'id'   => $npm,
                'npm'  => $npm,
                'nama' => 'Mahasiswa Uji Tracer',
                'role' => 'mahasiswa',
            ]);

            // 1. Sebelum isi tracer: GET biodata menampilkan layar kunci tracer_belum_diisi
            $pendaftaranController = new PendaftaranWisudaController();
            $pendaftaranController->initController(
                service('request'),
                service('response'),
                service('logger')
            );
            $biodataHtmlBefore = $pendaftaranController->biodata();
            $this->assertIsString($biodataHtmlBefore);
            $this->assertStringContainsString('Isi Tracer Study Awal Terlebih Dahulu', $biodataHtmlBefore);

            // 2. Mahasiswa melihat daftar & form pertanyaan tracer studi awal
            $tracerController = new TracerStudiController();
            $tracerController->initController(
                service('request'),
                service('response'),
                service('logger')
            );
            $tracerFormHtml = $tracerController->formMahasiswa();
            $this->assertIsString($tracerFormHtml);
            foreach (PertanyaanTracerStudiAwalModel::defaultQuestions() as $q) {
                $this->assertStringContainsString($q['teks_pertanyaan'], $tracerFormHtml);
            }

            // 3. Mahasiswa mengisi & menyimpan jawaban seluruh pertanyaan wajib
            $pertanyaanModel = new PertanyaanTracerStudiAwalModel();
            $pertanyaanAktif = $pertanyaanModel->getDaftarPertanyaan();
            $answers         = [];
            foreach ($pertanyaanAktif as $q) {
                $qid  = (int) $q['id'];
                $opsi = PertanyaanTracerStudiAwalModel::parseOpsiJawaban($q['opsi_jawaban'] ?? null);
                $answers[$qid] = $opsi !== [] ? $opsi[0] : 'Sudah bekerja sebagai Software Engineer';
            }

            $jawabanModel = new JawabanTracerStudiAwalModel();
            $this->assertFalse($jawabanModel->hasCompletedByNpm($npm));
            $jawabanModel->saveJawabanMahasiswa($npm, $pertanyaanAktif, $answers, '2026-2');
            $this->assertTrue($jawabanModel->hasCompletedByNpm($npm));

            // 4. Setelah isi tracer: gerbang tracer terbuka
            if (
                $db->tableExists('pendaftaran_wisuda')
                && $db->tableExists('dosen')
                && $db->tableExists('semester_graduate')
            ) {
                $biodataHtmlAfter = $pendaftaranController->biodata();
                $this->assertIsString($biodataHtmlAfter);
                $this->assertStringNotContainsString('Isi Tracer Study Awal Terlebih Dahulu', $biodataHtmlAfter);
                $this->assertStringContainsString('Biodata Mahasiswa', $biodataHtmlAfter);
            }
        } finally {
            $db->table('jawaban_tracer_studi_awal')->where('npm', $npm)->delete();
            $db->table('mahasiswa_terplot_sk_yudisium')->where('npm', $npm)->delete();
            session()->remove('user_session');
        }
    }

    private function ensureTablesExist(\CodeIgniter\Database\BaseConnection $db): void
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
    }
}
