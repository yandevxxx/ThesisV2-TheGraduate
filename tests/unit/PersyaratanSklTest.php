<?php

namespace Tests\Unit;

use App\Models\BerkasPengajuanSklModel;
use App\Models\PengajuanSklModel;
use App\Models\PersyaratanSklModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite for Persyaratan SKL Dinamis & Pengajuan SKL
 *
 * @internal
 */
final class PersyaratanSklTest extends CIUnitTestCase
{
    protected PersyaratanSklModel $persyaratanModel;
    protected PengajuanSklModel $pengajuanModel;
    protected BerkasPengajuanSklModel $berkasModel;

    protected function setUp(): void
    {
        parent::setUp();

        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        // 1. Setup tabel persyaratan_skl jika belum ada di SQLite
        if (! $db->tableExists('pendaftaran_wisuda')) {
            $forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'npm' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                ],
                'nama' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => false,
                ],
                'status_biodata' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'DRAFT',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('pendaftaran_wisuda', true);
        }

        if (! $db->tableExists('persyaratan_skl')) {
            $forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'nama' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'keterangan' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'tipe_file' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'default'    => 'pdf',
                ],
                'max_size_kb' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 5120,
                ],
                'is_wajib' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'urutan' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'AKTIF',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('persyaratan_skl', true);

            // Seed default
            $now = date('Y-m-d H:i:s');
            $db->table('persyaratan_skl')->insertBatch([
                ['nama' => 'Artikel', 'keterangan' => 'Naskah artikel', 'tipe_file' => 'pdf', 'max_size_kb' => 5120, 'is_wajib' => 1, 'urutan' => 1, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'Lembar Pengesahan', 'keterangan' => 'Lembar pengesahan', 'tipe_file' => 'pdf', 'max_size_kb' => 5120, 'is_wajib' => 1, 'urutan' => 2, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'Sertifikat Kompetensi', 'keterangan' => 'Sertifikat', 'tipe_file' => 'pdf', 'max_size_kb' => 5120, 'is_wajib' => 1, 'urutan' => 3, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // 2. Setup tabel pengajuan_skl jika belum ada
        if (! $db->tableExists('pengajuan_skl')) {
            $forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'npm' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                ],
                'nama' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => false,
                ],
                'id_prodi' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'kode_semester' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'menunggu',
                ],
                'catatan' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'diajukan_pada' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'diverifikasi_oleh' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'diverifikasi_pada' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('pengajuan_skl', true);
        }

        // 3. Setup tabel berkas_pengajuan_skl jika belum ada
        if (! $db->tableExists('berkas_pengajuan_skl')) {
            $forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'pengajuan_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'npm' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                ],
                'persyaratan_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'nama_persyaratan' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'nama_file' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'ukuran_kb' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('berkas_pengajuan_skl', true);
        }

        $this->persyaratanModel = new PersyaratanSklModel();
        $this->pengajuanModel   = new PengajuanSklModel();
        $this->berkasModel      = new BerkasPengajuanSklModel();
    }

    public function testGetPersyaratanAktifReturnsOnlyActiveOrdered(): void
    {
        $aktifList = $this->persyaratanModel->getPersyaratanAktif();
        $this->assertIsArray($aktifList);
        $this->assertNotEmpty($aktifList);

        foreach ($aktifList as $item) {
            $this->assertSame('AKTIF', $item['status']);
        }
    }

    public function testCrudPersyaratanSkl(): void
    {
        // 1. Create
        $id = $this->persyaratanModel->insert([
            'nama'        => 'Surat Bebas Lab Unit Test',
            'keterangan'  => 'Keterangan uji unit',
            'tipe_file'   => 'pdf',
            'max_size_kb' => 2048,
            'is_wajib'    => 1,
            'urutan'      => 99,
            'status'      => 'AKTIF',
        ]);
        $this->assertIsNumeric($id);

        // 2. Read
        $syarat = $this->persyaratanModel->find($id);
        $this->assertNotNull($syarat);
        $this->assertSame('Surat Bebas Lab Unit Test', $syarat['nama']);
        $this->assertSame('AKTIF', $syarat['status']);

        // 3. Update & Toggle Status
        $this->persyaratanModel->update($id, [
            'status' => 'NON-AKTIF',
            'nama'   => 'Surat Bebas Lab Updated',
        ]);
        $updated = $this->persyaratanModel->find($id);
        $this->assertSame('NON-AKTIF', $updated['status']);
        $this->assertSame('Surat Bebas Lab Updated', $updated['nama']);

        // Pastikan tidak masuk di getPersyaratanAktif
        $aktifList = $this->persyaratanModel->getPersyaratanAktif();
        $ids = array_column($aktifList, 'id');
        $this->assertNotContains((int) $id, array_map('intval', $ids));

        // 4. Delete
        $this->persyaratanModel->delete($id);
        $deleted = $this->persyaratanModel->find($id);
        $this->assertNull($deleted);
    }

    public function testPengajuanAndBerkasSklRelationship(): void
    {
        $testNpm = 'TEST_NPM_SKL_' . time();

        // Buat pengajuan
        $pengajuanId = $this->pengajuanModel->insert([
            'npm'           => $testNpm,
            'nama'          => 'Mahasiswa Uji SKL',
            'id_prodi'      => 1,
            'kode_semester' => '2025-1',
            'status'        => 'menunggu',
        ]);
        $this->assertIsNumeric($pengajuanId);

        // Ambil salah satu persyaratan aktif
        $aktif = $this->persyaratanModel->getPersyaratanAktif();
        $this->assertNotEmpty($aktif);
        $firstSyarat = $aktif[0];

        // Buat berkas
        $berkasId = $this->berkasModel->insert([
            'pengajuan_id'     => $pengajuanId,
            'npm'              => $testNpm,
            'persyaratan_id'   => $firstSyarat['id'],
            'nama_persyaratan' => $firstSyarat['nama'],
            'nama_file'        => 'test-file.pdf',
            'ukuran_kb'        => 500,
        ]);
        $this->assertIsNumeric($berkasId);

        // Map berkas
        $map = $this->berkasModel->getBerkasMapByNpm($testNpm);
        $this->assertArrayHasKey((int) $firstSyarat['id'], $map);
        $this->assertSame('test-file.pdf', $map[(int) $firstSyarat['id']]['nama_file']);

        // Verifikasi pengajuan
        $this->pengajuanModel->update($pengajuanId, [
            'status'  => 'disetujui',
            'catatan' => 'Berkas lengkap dan sah',
        ]);
        $verified = $this->pengajuanModel->find($pengajuanId);
        $this->assertSame('disetujui', $verified['status']);

        // Bersihkan data uji
        $this->berkasModel->delete($berkasId);
        $this->pengajuanModel->delete($pengajuanId);
    }

    public function testSklBlockedWhenBiodataNotApproved(): void
    {
        $sklController = new \App\Controllers\TheGraduateWeb\SklController();
        $pendaftaranModel = new \App\Models\PendaftaranWisudaModel();

        $npmTest = 'TEST_BLOCKED_SKL_' . time();

        // 1. Mahasiswa belum memiliki biodata sama sekali -> akses SKL diblokir (false)
        $this->assertFalse($sklController->isBiodataApproved($npmTest));

        // 2. Biodata berstatus DRAFT -> akses SKL diblokir (false)
        $id1 = $pendaftaranModel->insert([
            'npm'            => $npmTest,
            'nama'           => 'Mahasiswa Draft',
            'status_biodata' => 'DRAFT',
        ], true);
        $this->assertFalse($sklController->isBiodataApproved($npmTest));

        // 3. Biodata berstatus MENUNGGU -> akses SKL diblokir (false)
        $pendaftaranModel->update($id1, ['status_biodata' => 'MENUNGGU']);
        $this->assertFalse($sklController->isBiodataApproved($npmTest));

        // 4. Biodata berstatus DITOLAK -> akses SKL diblokir (false)
        $pendaftaranModel->update($id1, ['status_biodata' => 'DITOLAK']);
        $this->assertFalse($sklController->isBiodataApproved($npmTest));

        // 5. Biodata berstatus DISETUJUI -> akses SKL dibuka (true)
        $pendaftaranModel->update($id1, ['status_biodata' => 'DISETUJUI']);
        $this->assertTrue($sklController->isBiodataApproved($npmTest));

        // Cleanup
        $pendaftaranModel->delete($id1);
    }

    public function testSyncAndCascadeCleanupOnPersyaratanSklUpdateAndDelete(): void
    {
        // 1. Tambah syarat
        $syaratId = $this->persyaratanModel->insert([
            'nama'        => 'Sertifikat TOEFL Uji',
            'tipe_file'   => 'pdf',
            'max_size_kb' => 2048,
            'is_wajib'    => 1,
            'urutan'      => 5,
            'status'      => 'AKTIF',
        ]);
        $this->assertIsNumeric($syaratId);

        // 2. Buat berkas terkait
        $berkasId = $this->berkasModel->insert([
            'npm'              => '9999888877',
            'persyaratan_id'   => $syaratId,
            'nama_persyaratan' => 'Sertifikat TOEFL Uji',
            'nama_file'        => 'dummy_toefl.pdf',
            'ukuran_kb'        => 512,
        ]);
        $this->assertIsNumeric($berkasId);

        // 3. Test update nama sinkron ke berkas_pengajuan_skl
        $this->persyaratanModel->update($syaratId, ['nama' => 'Sertifikat TOEFL Updated']);
        $this->berkasModel->where('persyaratan_id', $syaratId)->set(['nama_persyaratan' => 'Sertifikat TOEFL Updated'])->update();

        $bRow = $this->berkasModel->find($berkasId);
        $this->assertSame('Sertifikat TOEFL Updated', $bRow['nama_persyaratan']);

        // 4. Test hapus cascade
        $this->berkasModel->where('persyaratan_id', $syaratId)->delete();
        $this->persyaratanModel->delete($syaratId);

        $this->assertNull($this->persyaratanModel->find($syaratId));
        $this->assertNull($this->berkasModel->find($berkasId));
    }

    public function testDownloadTemplateSklFilenameFormatConformsToPattern(): void
    {
        $sklSrc = (string) file_get_contents(APPPATH . 'Controllers/TheGraduateWeb/SklController.php');

        $this->assertStringContainsString("\$baseFileName = \$sanitizedNama . '_' . \$npm . '_SKL';", $sklSrc);
        $this->assertStringContainsString("\$fileName = \$baseFileName . '.docx';", $sklSrc);
        $this->assertStringContainsString("\$fileName = \$baseFileName . '.pdf';", $sklSrc);
        $this->assertStringContainsString("if (\$requestedFormat === 'pdf')", $sklSrc);

        // Uji rute dan tombol view tendik menyediakan pilihan Word dan PDF
        $routesSrc = (string) file_get_contents(APPPATH . 'Config/Routes.php');
        $this->assertStringContainsString("skl_download_template/(:num)/(:segment)", $routesSrc);

        $viewSrc = (string) file_get_contents(APPPATH . 'Views/The-Graduate-Web/admin_tendik/skl.php');
        $this->assertStringContainsString("skl_download_template/' . \$pengajuanId . '/docx", $viewSrc);
        $this->assertStringContainsString("skl_download_template/' . \$pengajuanId . '/pdf", $viewSrc);

        // Uji logika format Nama Lengkap_NPM_SKL
        $namaLengkap = 'Agus Widayat';
        $npm = '1402002075';
        $sanitizedNama = trim(preg_replace('/\s+/', ' ', preg_replace('[/\\\\:*?"<>|]', '', $namaLengkap)));
        $baseFileName = $sanitizedNama . '_' . $npm . '_SKL';

        $this->assertSame('Agus Widayat_1402002075_SKL', $baseFileName);
        $this->assertSame('Agus Widayat_1402002075_SKL.docx', $baseFileName . '.docx');
        $this->assertSame('Agus Widayat_1402002075_SKL.pdf', $baseFileName . '.pdf');
    }

    public function testKirimSklEmailNotificationDoesNotAttachFile(): void
    {
        $sklSrc = (string) file_get_contents(APPPATH . 'Controllers/TheGraduateWeb/SklController.php');

        // Pastikan tidak ada $email->attach di method kirimNotifikasiEmailSkl
        $this->assertStringNotContainsString('$email->attach(', $sklSrc);

        // Pastikan view email adalah murni pemberitahuan tanpa lampiran berkas
        $emailViewSrc = (string) file_get_contents(APPPATH . 'Views/emails/notifikasi_skl_resmi.php');
        $this->assertStringContainsString('Berkas fisik Surat Keterangan Lulus (SKL) resmi tidak dilampirkan pada email ini', $emailViewSrc);
        $this->assertStringNotContainsString('telah dilampirkan pada email ini', $emailViewSrc);
    }
}
