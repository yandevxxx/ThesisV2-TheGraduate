<?php

namespace Tests\Unit;

use App\Controllers\TheGraduateWeb\Graduate;
use App\Controllers\TheGraduateWeb\PendaftaranWisudaController;
use App\Controllers\TheGraduateWeb\SklController;
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

        foreach (['tendik', 'staf', 'prodi', 'kaprodi', 'dosen', 'alumni'] as $nonMahasiswaRole) {
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
                'id'    => $npm,
                'npm'   => $npm,
                'nama'  => 'Mahasiswa Uji Tracer',
                'roles' => ['mahasiswa'],
            ]);

            $pendaftaranController = $this->initWebController(new PendaftaranWisudaController());
            $sklController         = $this->initWebController(new SklController());
            $graduateController    = $this->initWebController(new Graduate());

            // 1. Sebelum isi tracer: biodata, berkas wisuda, SKL, dan pengambilan ijazah terkunci
            $biodataHtmlBefore = $pendaftaranController->biodata();
            $this->assertIsString($biodataHtmlBefore);
            $this->assertStringContainsString('Isi Tracer Study Awal Terlebih Dahulu', $biodataHtmlBefore);

            $berkasHtmlBefore = $pendaftaranController->berkasWisuda();
            $this->assertIsString($berkasHtmlBefore);
            $this->assertStringContainsString('Isi Tracer Study Awal Terlebih Dahulu', $berkasHtmlBefore);
            $this->assertStringContainsString('Berkas Wisuda', $berkasHtmlBefore);

            $uploadBerkasBefore = $pendaftaranController->uploadBerkas();
            $uploadPayload      = json_decode((string) $uploadBerkasBefore->getBody(), true);
            $this->assertIsArray($uploadPayload);
            $this->assertFalse($uploadPayload['success']);
            $this->assertStringContainsString('Tracer Study Awal', $uploadPayload['message']);

            $submitBerkasBefore = $pendaftaranController->submitBerkas();
            $this->assertStringContainsString('graduate/tracer_studi', $submitBerkasBefore->getHeaderLine('Location'));

            $sklHtmlBefore = $sklController->index();
            $this->assertIsString($sklHtmlBefore);
            $this->assertStringContainsString('Isi Tracer Study Awal Terlebih Dahulu', $sklHtmlBefore);
            $this->assertStringContainsString('layanan SKL', $sklHtmlBefore);

            $sklUploadBefore  = $sklController->uploadBerkas();
            $sklUploadPayload = json_decode((string) $sklUploadBefore->getBody(), true);
            $this->assertIsArray($sklUploadPayload);
            $this->assertFalse($sklUploadPayload['success']);
            $this->assertStringContainsString('Tracer Study Awal', $sklUploadPayload['message']);

            $sklSubmitBefore = $sklController->submit();
            $this->assertStringContainsString('graduate/tracer_studi', $sklSubmitBefore->getHeaderLine('Location'));

            $ijazahHtmlBefore = $graduateController->pengambilan_ijazah();
            $this->assertIsString($ijazahHtmlBefore);
            $this->assertStringContainsString('Isi Tracer Study Awal Terlebih Dahulu', $ijazahHtmlBefore);
            $this->assertStringContainsString('Formulir Pengambilan Ijazah', $ijazahHtmlBefore);

            $sidebarBefore = view('The-Graduate-Web/mahasiswa/sidebar');
            $this->assertStringContainsString('Isi Tracer Study Awal terlebih dahulu (Terkunci)', $sidebarBefore);

            // 2. Mahasiswa melihat daftar & form pertanyaan tracer studi awal
            $tracerController = $this->initWebController(new TracerStudiController());
            $tracerFormHtml   = $tracerController->formMahasiswa();
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

            // 4. Setelah isi tracer: gerbang tracer terbuka untuk semua menu terkait
            if (
                $db->tableExists('dosen')
                && $db->tableExists('semester_graduate')
            ) {
                $biodataHtmlAfter = $pendaftaranController->biodata();
                $this->assertIsString($biodataHtmlAfter);
                $this->assertStringNotContainsString('Isi Tracer Study Awal Terlebih Dahulu', $biodataHtmlAfter);
                $this->assertStringContainsString('Biodata Mahasiswa', $biodataHtmlAfter);
            }

            $berkasHtmlAfter = $pendaftaranController->berkasWisuda();
            $this->assertIsString($berkasHtmlAfter);
            $this->assertStringNotContainsString('Isi Tracer Study Awal Terlebih Dahulu', $berkasHtmlAfter);
            $this->assertStringContainsString('Berkas Persyaratan Wisuda', $berkasHtmlAfter);

            $sklHtmlAfter = $sklController->index();
            $this->assertIsString($sklHtmlAfter);
            $this->assertStringNotContainsString('Isi Tracer Study Awal Terlebih Dahulu', $sklHtmlAfter);
            $this->assertStringContainsString('Layanan SKL Belum Dapat Diakses', $sklHtmlAfter);

            $ijazahHtmlAfter = $graduateController->pengambilan_ijazah();
            $this->assertIsString($ijazahHtmlAfter);
            $this->assertStringNotContainsString('Isi Tracer Study Awal Terlebih Dahulu', $ijazahHtmlAfter);
            $this->assertStringContainsString('Pengambilan Ijazah Belum Dapat Diakses', $ijazahHtmlAfter);

            $sidebarAfter = view('The-Graduate-Web/mahasiswa/sidebar');
            $this->assertStringNotContainsString('Isi Tracer Study Awal terlebih dahulu (Terkunci)', $sidebarAfter);
            $this->assertStringContainsString('Biodata belum disetujui (Terkunci)', $sidebarAfter);
            $this->assertStringContainsString('Berkas wisuda belum dikumpulkan dan disetujui (Terkunci)', $sidebarAfter);

            // 5. Setelah berkas wisuda dikumpulkan dan disetujui (ACC), Pengambilan Ijazah terbuka
            $db->table('pendaftaran_wisuda')->insert([
                'npm'               => $npm,
                'nama'              => 'Mahasiswa Uji Tracer',
                'status_biodata'    => 'DRAFT',
                'status_berkas'     => 'DISETUJUI',
                'status_verifikasi' => 'DRAFT',
            ]);

            $ijazahHtmlApproved = $graduateController->pengambilan_ijazah();
            $this->assertIsString($ijazahHtmlApproved);
            $this->assertStringContainsString('Formulir Pengambilan Ijazah', $ijazahHtmlApproved);

            $sidebarApproved = view('The-Graduate-Web/mahasiswa/sidebar');
            $this->assertStringNotContainsString('Berkas wisuda belum dikumpulkan dan disetujui (Terkunci)', $sidebarApproved);
        } finally {
            $db->table('pendaftaran_wisuda')->where('npm', $npm)->delete();
            $db->table('jawaban_tracer_studi_awal')->where('npm', $npm)->delete();
            $db->table('mahasiswa_terplot_sk_yudisium')->where('npm', $npm)->delete();
            session()->remove('user_session');
        }
    }

    public function testSklRemainsBlockedBeforeTracerEvenWhenBiodataApproved(): void
    {
        $db = \Config\Database::connect();

        $npm = 'TESTSKLTRC' . random_int(1000, 9999);
        $db->table('mahasiswa_terplot_sk_yudisium')->insert([
            'npm'           => $npm,
            'nama'          => 'Mahasiswa SKL Uji Tracer',
            'status'        => 'baru',
            'kode_semester' => '2026-2',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $db->table('pendaftaran_wisuda')->insert([
            'npm'               => $npm,
            'nama'              => 'Mahasiswa SKL Uji Tracer',
            'kode_semester'     => '2026-2',
            'status_biodata'    => 'DISETUJUI',
            'status_berkas'     => 'DRAFT',
            'status_verifikasi' => 'DRAFT',
        ]);

        try {
            session()->set('user_session', [
                'id'    => $npm,
                'npm'   => $npm,
                'nama'  => 'Mahasiswa SKL Uji Tracer',
                'roles' => ['mahasiswa'],
            ]);

            $sklController = $this->initWebController(new SklController());

            $sklHtmlBefore = $sklController->index();
            $this->assertIsString($sklHtmlBefore);
            $this->assertStringContainsString('Isi Tracer Study Awal Terlebih Dahulu', $sklHtmlBefore);

            $hapusBefore  = $sklController->hapusBerkas(999999);
            $hapusPayload = json_decode((string) $hapusBefore->getBody(), true);
            $this->assertIsArray($hapusPayload);
            $this->assertFalse($hapusPayload['success']);
            $this->assertStringContainsString('Tracer Study Awal', $hapusPayload['message']);

            $downloadBefore = $sklController->downloadSklResmi();
            $this->assertStringContainsString('graduate/skl', $downloadBefore->getHeaderLine('Location'));

            $sidebarBefore = view('The-Graduate-Web/mahasiswa/sidebar');
            $this->assertStringContainsString('Isi Tracer Study Awal terlebih dahulu (Terkunci)', $sidebarBefore);

            // Isi Tracer Study Awal -> SKL langsung terbuka karena biodata sudah DISETUJUI
            $pertanyaanAktif = (new PertanyaanTracerStudiAwalModel())->getDaftarPertanyaan();
            $answers         = [];
            foreach ($pertanyaanAktif as $q) {
                $qid  = (int) $q['id'];
                $opsi = PertanyaanTracerStudiAwalModel::parseOpsiJawaban($q['opsi_jawaban'] ?? null);
                $answers[$qid] = $opsi !== [] ? $opsi[0] : 'Sudah bekerja';
            }
            (new JawabanTracerStudiAwalModel())->saveJawabanMahasiswa($npm, $pertanyaanAktif, $answers, '2026-2');

            $sklHtmlAfter = $sklController->index();
            $this->assertIsString($sklHtmlAfter);
            $this->assertStringNotContainsString('Isi Tracer Study Awal Terlebih Dahulu', $sklHtmlAfter);
            $this->assertStringNotContainsString('Layanan SKL Belum Dapat Diakses', $sklHtmlAfter);

            $sidebarAfter = view('The-Graduate-Web/mahasiswa/sidebar');
            $this->assertStringNotContainsString('Isi Tracer Study Awal terlebih dahulu (Terkunci)', $sidebarAfter);
            $this->assertStringNotContainsString('Biodata belum disetujui (Terkunci)', $sidebarAfter);
        } finally {
            $db->table('jawaban_tracer_studi_awal')->where('npm', $npm)->delete();
            $db->table('pendaftaran_wisuda')->where('npm', $npm)->delete();
            $db->table('mahasiswa_terplot_sk_yudisium')->where('npm', $npm)->delete();
            session()->remove('user_session');
        }
    }

    public function testTendikIsNotBlockedByMahasiswaTracerGate(): void
    {
        try {
            session()->set('user_session', [
                'id'    => 'TENDIKTEST01',
                'nama'  => 'Tendik Uji',
                'roles' => ['tendik'],
            ]);

            $pendaftaranController = $this->initWebController(new PendaftaranWisudaController());
            $berkasResponse        = $pendaftaranController->berkasWisuda();
            $this->assertStringContainsString('berkas_wisuda_verifikasi', $berkasResponse->getHeaderLine('Location'));

            $graduateController = $this->initWebController(new Graduate());
            $ijazahResponse     = $graduateController->pengambilan_ijazah();
            if (is_string($ijazahResponse)) {
                $this->assertStringNotContainsString('Isi Tracer Study Awal Terlebih Dahulu', $ijazahResponse);
            } else {
                $this->assertNotEmpty($ijazahResponse->getHeaderLine('Location'));
            }
        } finally {
            session()->remove('user_session');
        }
    }

    /**
     * @template T of \CodeIgniter\Controller
     * @param T $controller
     * @return T
     */
    private function initWebController($controller)
    {
        $controller->initController(
            service('request'),
            service('response'),
            service('logger')
        );

        return $controller;
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

        if (! $db->tableExists('pendaftaran_wisuda')) {
            $forge->addField([
                'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'npm'                    => ['type' => 'VARCHAR', 'constraint' => 20],
                'nama'                   => ['type' => 'VARCHAR', 'constraint' => 150],
                'nik'                    => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'tempat_lahir'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'tanggal_lahir'          => ['type' => 'DATE', 'null' => true],
                'jenis_kelamin'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'alamat'                 => ['type' => 'TEXT', 'null' => true],
                'no_hp'                  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'no_telepon'             => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'email'                  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'prodi'                  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'total_sks'              => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'ipk'                    => ['type' => 'DECIMAL', 'constraint' => '4,2', 'null' => true],
                'kode_semester'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'tanggal_lulus'          => ['type' => 'DATE', 'null' => true],
                'gelombang_wisuda'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'pembimbing1'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'pembimbing2'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'pembimbing_agama'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'dosen_penguji'          => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                'judul_skripsi'          => ['type' => 'TEXT', 'null' => true],
                'jenis_tugas_akhir'      => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Skripsi'],
                'pas_foto'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'status_biodata'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
                'catatan_biodata'        => ['type' => 'TEXT', 'null' => true],
                'status_berkas'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
                'catatan_berkas'         => ['type' => 'TEXT', 'null' => true],
                'status_wisudawan'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'MENUNGGU'],
                'tgl_approval_wisudawan' => ['type' => 'DATETIME', 'null' => true],
                'catatan_wisudawan'      => ['type' => 'TEXT', 'null' => true],
                'status_verifikasi'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
                'catatan_verifikasi'     => ['type' => 'TEXT', 'null' => true],
                'diverifikasi_oleh'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'diverifikasi_pada'      => ['type' => 'DATETIME', 'null' => true],
                'created_at'             => ['type' => 'DATETIME', 'null' => true],
                'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('pendaftaran_wisuda', true);
        }

        if (! $db->tableExists('persyaratan_wisuda')) {
            $forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nama'        => ['type' => 'VARCHAR', 'constraint' => 255],
                'keterangan'  => ['type' => 'TEXT', 'null' => true],
                'tipe_file'   => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'pdf,jpg,jpeg,png'],
                'max_size_kb' => ['type' => 'INT', 'constraint' => 11, 'default' => 2048],
                'is_wajib'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'urutan'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'status'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'AKTIF'],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('persyaratan_wisuda', true);
        }

        if (! $db->tableExists('berkas_pendaftaran_wisuda')) {
            $forge->addField([
                'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'pendaftaran_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'persyaratan_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'nama_persyaratan' => ['type' => 'VARCHAR', 'constraint' => 255],
                'nama_file'        => ['type' => 'VARCHAR', 'constraint' => 255],
                'ukuran_kb'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'status_berkas'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'MENUNGGU'],
                'catatan'          => ['type' => 'TEXT', 'null' => true],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
                'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('berkas_pendaftaran_wisuda', true);
        }

        if (! $db->tableExists('persyaratan_skl')) {
            $forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nama'        => ['type' => 'VARCHAR', 'constraint' => 255],
                'keterangan'  => ['type' => 'TEXT', 'null' => true],
                'tipe_file'   => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'pdf'],
                'max_size_kb' => ['type' => 'INT', 'constraint' => 11, 'default' => 5120],
                'is_wajib'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'urutan'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'status'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'AKTIF'],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('persyaratan_skl', true);
        }

        if (! $db->tableExists('pengajuan_skl')) {
            $forge->addField([
                'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'npm'               => ['type' => 'VARCHAR', 'constraint' => 20],
                'nama'              => ['type' => 'VARCHAR', 'constraint' => 150],
                'id_prodi'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'kode_semester'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'status'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'menunggu'],
                'catatan'           => ['type' => 'TEXT', 'null' => true],
                'no_surat'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'file_skl'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'tgl_terbit'        => ['type' => 'DATE', 'null' => true],
                'dikirim_oleh'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'dikirim_pada'      => ['type' => 'DATETIME', 'null' => true],
                'diajukan_pada'     => ['type' => 'DATETIME', 'null' => true],
                'diverifikasi_oleh' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'diverifikasi_pada' => ['type' => 'DATETIME', 'null' => true],
                'created_at'        => ['type' => 'DATETIME', 'null' => true],
                'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('pengajuan_skl', true);
        }

        if (! $db->tableExists('berkas_pengajuan_skl')) {
            $forge->addField([
                'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'pengajuan_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'npm'              => ['type' => 'VARCHAR', 'constraint' => 20],
                'persyaratan_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'nama_persyaratan' => ['type' => 'VARCHAR', 'constraint' => 255],
                'nama_file'        => ['type' => 'VARCHAR', 'constraint' => 255],
                'ukuran_kb'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
                'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('berkas_pengajuan_skl', true);
        }
    }
}
