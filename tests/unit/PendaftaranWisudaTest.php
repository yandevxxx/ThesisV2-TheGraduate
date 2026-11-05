<?php

namespace Tests\Unit;

use App\Controllers\TheGraduateWeb\PendaftaranWisudaController;
use App\Models\BerkasPendaftaranWisudaModel;
use App\Models\PendaftaranWisudaModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite for Pendaftaran Wisuda Model, Berkas Model, and Controller operations.
 *
 * @internal
 */
final class PendaftaranWisudaTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = \Config\Database::connect();

        // 1. Setup tabel pendaftaran_wisuda jika belum ada
        if (! $db->tableExists('pendaftaran_wisuda')) {
            $forge = \Config\Database::forge();
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
                'nik' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'tempat_lahir' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'tanggal_lahir' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'jenis_kelamin' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'alamat' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'no_hp' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'no_telepon' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'prodi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'total_sks' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'ipk' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '4,2',
                    'null'       => true,
                ],
                'kode_semester' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'tanggal_lulus' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'gelombang_wisuda' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'pembimbing1' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'pembimbing2' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'pembimbing_agama' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'dosen_penguji' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'judul_skripsi' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'jenis_tugas_akhir' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'Skripsi',
                ],
                'pas_foto' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'status_verifikasi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'DRAFT',
                ],
                'catatan_verifikasi' => [
                    'type' => 'TEXT',
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
            $forge->createTable('pendaftaran_wisuda', true);
        }

        // 2. Setup tabel berkas_pendaftaran_wisuda jika belum ada
        if (! $db->tableExists('berkas_pendaftaran_wisuda')) {
            $forge = \Config\Database::forge();
            $forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'pendaftaran_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'persyaratan_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
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
                'status_berkas' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'MENUNGGU',
                ],
                'catatan' => [
                    'type' => 'TEXT',
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
            $forge->createTable('berkas_pendaftaran_wisuda', true);
        }
    }

    public function testModelTableAndAllowedFields()
    {
        $model = new PendaftaranWisudaModel();
        $this->assertSame('pendaftaran_wisuda', $model->getTable());
        $this->assertContains('npm', $model->allowedFields);
        $this->assertContains('nama', $model->allowedFields);
        $this->assertContains('nik', $model->allowedFields);
        $this->assertContains('prodi', $model->allowedFields);
        $this->assertContains('total_sks', $model->allowedFields);
        $this->assertContains('ipk', $model->allowedFields);
        $this->assertContains('kode_semester', $model->allowedFields);
        $this->assertContains('jenis_tugas_akhir', $model->allowedFields);
        $this->assertContains('status_verifikasi', $model->allowedFields);
        $this->assertContains('catatan_verifikasi', $model->allowedFields);
    }

    public function testBerkasModelTableAndAllowedFields()
    {
        $model = new BerkasPendaftaranWisudaModel();
        $this->assertSame('berkas_pendaftaran_wisuda', $model->getTable());
        $this->assertContains('pendaftaran_id', $model->allowedFields);
        $this->assertContains('persyaratan_id', $model->allowedFields);
        $this->assertContains('nama_persyaratan', $model->allowedFields);
        $this->assertContains('nama_file', $model->allowedFields);
        $this->assertContains('ukuran_kb', $model->allowedFields);
        $this->assertContains('status_berkas', $model->allowedFields);
    }

    public function testPendaftaranWisudaCrudAndFiltering()
    {
        $pendaftaranModel = new PendaftaranWisudaModel();
        $berkasModel      = new BerkasPendaftaranWisudaModel();

        // 1. Insert pendaftaran
        $pId = $pendaftaranModel->insert([
            'npm'               => '9999010001',
            'nama'              => 'Mahasiswa Uji Coba',
            'nik'               => '3201999999990001',
            'prodi'             => 'Teknik Informatika',
            'total_sks'         => 144,
            'ipk'               => 3.75,
            'kode_semester'     => '2026-2',
            'status_verifikasi' => 'MENUNGGU',
            'gelombang_wisuda'   => 'Gelombang 1 - 15 November 2025',
            'jenis_tugas_akhir'  => 'Publikasi Artikel Ilmiah',
            'judul_skripsi'      => 'SISTEM INFORMASI SKRIPSI DAN YUDISIUM TERINTEGRASI',
        ]);

        $this->assertIsInt($pId);
        $this->assertGreaterThan(0, $pId);

        // 2. getByNpm
        $found = $pendaftaranModel->getByNpm('9999010001');
        $this->assertNotNull($found);
        $this->assertSame('Mahasiswa Uji Coba', $found['nama']);
        $this->assertSame('Publikasi Artikel Ilmiah', $found['jenis_tugas_akhir']);
        $this->assertSame('SISTEM INFORMASI SKRIPSI DAN YUDISIUM TERINTEGRASI', $found['judul_skripsi']);

        // 3. getRegisteredNpms
        $registeredNpms = $pendaftaranModel->getRegisteredNpms();
        $this->assertContains('9999010001', $registeredNpms);

        // 4. getFiltered by semester and search query
        $filtered = $pendaftaranModel->getFiltered('2026-2', 'MENUNGGU', 'Uji Coba');
        $this->assertNotEmpty($filtered);
        $this->assertSame('9999010001', $filtered[0]['npm']);

        // 5. Berkas CRUD & map
        $bId = $berkasModel->insert([
            'pendaftaran_id'   => $pId,
            'persyaratan_id'   => 1,
            'nama_persyaratan' => 'FotoCopy Berwarna Ijazah SMA',
            'nama_file'        => 'berkas_9999010001_ijazah.pdf',
            'ukuran_kb'        => 512,
            'status_berkas'    => 'MENUNGGU',
        ]);
        $this->assertIsInt($bId);

        $berkasList = $berkasModel->getByPendaftaranId($pId);
        $this->assertCount(1, $berkasList);

        $berkasMap = $berkasModel->getBerkasMapByPendaftaranId($pId);
        $this->assertArrayHasKey('FotoCopy Berwarna Ijazah SMA', $berkasMap);
        $this->assertSame('berkas_9999010001_ijazah.pdf', $berkasMap['FotoCopy Berwarna Ijazah SMA']['nama_file']);

        // 6. getPendaftaranWithBerkas
        $withBerkas = $pendaftaranModel->getPendaftaranWithBerkas($pId);
        $this->assertNotNull($withBerkas);
        $this->assertArrayHasKey('berkas', $withBerkas);
        $this->assertCount(1, $withBerkas['berkas']);

        // 7. Verification update
        $pendaftaranModel->update($pId, [
            'status_verifikasi'  => 'DISETUJUI',
            'catatan_verifikasi' => 'Dokumen lengkap dan valid',
            'diverifikasi_pada'  => date('Y-m-d H:i:s'),
        ]);

        $updated = $pendaftaranModel->find($pId);
        $this->assertSame('DISETUJUI', $updated['status_verifikasi']);
        $this->assertSame('Dokumen lengkap dan valid', $updated['catatan_verifikasi']);

        // Clean up
        $berkasModel->delete($bId);
        $pendaftaranModel->delete($pId);
    }

    public function testControllerAccessControl()
    {
        // Unauthenticated access
        session()->remove('user_session');

        $controller = new PendaftaranWisudaController();
        $response = $controller->index();

        // Should redirect to auth/login
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $response);

        // RBAC rules check
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaran_wisuda', ['admin']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaran_wisuda', ['tendik']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaran_wisuda', ['fakultas']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaran_wisuda', ['mahasiswa']));

        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaranwisudasubmit', ['mahasiswa']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaranwisudauploadberkas', ['mahasiswa']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaranwisudahapusberkas', ['mahasiswa']));

        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaranwisudaverifikasi', ['tendik']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaranwisudaverifikasi', ['admin']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaranwisudaverifikasi', ['fakultas']));
        $this->assertFalse(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaranwisudaverifikasi', ['mahasiswa']));
    }

    public function testDraftPendaftaranStatusWorkflow()
    {
        $pendaftaranModel = new PendaftaranWisudaModel();

        // 1. Simulasikan pembuatan draft saat mahasiswa upload berkas tanpa submit form
        $draftId = $pendaftaranModel->insert([
            'npm'               => '9999010099',
            'nama'              => 'Mahasiswa Draft Only',
            'email'             => 'draft@test.ac.id',
            'kode_semester'     => '2026-2',
            'status_verifikasi' => 'DRAFT',
        ]);

        $this->assertIsInt($draftId);

        // 2. Draft tidak boleh muncul di getRegisteredNpms (sehingga tetap muncul di belum mendaftar)
        $registeredNpms = $pendaftaranModel->getRegisteredNpms();
        $this->assertNotContains('9999010099', $registeredNpms);

        // 3. Draft tidak boleh muncul di getFiltered (antrean verifikasi tendik) default
        $filteredAll = $pendaftaranModel->getFiltered('2026-2', null, '9999010099');
        $this->assertEmpty($filteredAll);

        // 4. Draft juga tidak boleh muncul jika filter status 'semua'
        $filteredSemua = $pendaftaranModel->getFiltered('2026-2', 'semua', '9999010099');
        $this->assertEmpty($filteredSemua);

        // 5. Ketika mahasiswa men-submit formulir, status berubah menjadi MENUNGGU
        $pendaftaranModel->update($draftId, [
            'nik'               => '3201999999990099',
            'tempat_lahir'      => 'Jakarta',
            'tanggal_lahir'     => '2002-05-10',
            'jenis_kelamin'     => 'Laki-laki',
            'alamat'            => 'Jl. Percobaan No. 99',
            'no_hp'             => '08123456789',
            'prodi'             => 'Teknik Informatika',
            'total_sks'         => 144,
            'ipk'               => 3.80,
            'status_verifikasi' => 'MENUNGGU',
        ]);

        // 6. Setelah submit, harus muncul di getRegisteredNpms
        $registeredAfterSubmit = $pendaftaranModel->getRegisteredNpms();
        $this->assertContains('9999010099', $registeredAfterSubmit);

        // 7. Setelah submit, harus muncul di antrean verifikasi Tendik
        $filteredAfterSubmit = $pendaftaranModel->getFiltered('2026-2', 'MENUNGGU', '9999010099');
        $this->assertNotEmpty($filteredAfterSubmit);
        $this->assertSame('9999010099', $filteredAfterSubmit[0]['npm']);

        // Clean up
        $pendaftaranModel->delete($draftId);
    }
}
