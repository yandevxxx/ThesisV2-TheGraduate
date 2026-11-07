<?php

namespace Tests\Unit;

use App\Controllers\TheGraduateWeb\PersyaratanWisudaController;
use App\Models\PersyaratanWisudaModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite for Persyaratan Wisuda CRUD operations and access control.
 *
 * @internal
 */
final class PersyaratanWisudaCrudTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = \Config\Database::connect();
        if (! $db->tableExists('persyaratan_wisuda')) {
            $forge = \Config\Database::forge();
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
                    'default'    => 'pdf,jpg,jpeg,png',
                ],
                'max_size_kb' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 2048,
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
            $forge->createTable('persyaratan_wisuda', true);
        }

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
                    'null'       => true,
                ],
                'persyaratan_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'nama_persyaratan' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'nama_file' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('berkas_pendaftaran_wisuda', true);
        }
    }

    public function testModelTableAndAllowedFields()
    {
        $model = new PersyaratanWisudaModel();
        $this->assertSame('persyaratan_wisuda', $model->getTable());
        $this->assertContains('nama', $model->allowedFields);
        $this->assertContains('keterangan', $model->allowedFields);
        $this->assertContains('tipe_file', $model->allowedFields);
        $this->assertContains('max_size_kb', $model->allowedFields);
        $this->assertContains('is_wajib', $model->allowedFields);
        $this->assertContains('urutan', $model->allowedFields);
        $this->assertContains('status', $model->allowedFields);
    }

    public function testCrudOperationsOnModel()
    {
        $model = new PersyaratanWisudaModel();

        // 1. Create
        $insertId = $model->insert([
            'nama'        => 'Dokumen Uji Coba Unit Test',
            'keterangan'  => 'Berkas dummy untuk pengujian otomatis',
            'tipe_file'   => 'pdf',
            'max_size_kb' => 2048,
            'is_wajib'    => 1,
            'urutan'      => 99,
            'status'      => 'AKTIF',
        ], true);

        $this->assertIsNumeric($insertId);
        $this->assertGreaterThan(0, (int) $insertId);

        // 2. Read
        $item = $model->find($insertId);
        $this->assertNotNull($item);
        $this->assertSame('Dokumen Uji Coba Unit Test', $item['nama']);
        $this->assertSame('AKTIF', $item['status']);
        $this->assertSame('1', (string) $item['is_wajib']);

        // 3. Update / Toggle
        $model->update($insertId, [
            'status'   => 'NON-AKTIF',
            'is_wajib' => 0,
        ]);

        $updatedItem = $model->find($insertId);
        $this->assertSame('NON-AKTIF', $updatedItem['status']);
        $this->assertSame('0', (string) $updatedItem['is_wajib']);

        // 4. getPersyaratanAktif excludes NON-AKTIF
        $aktifList = $model->getPersyaratanAktif();
        $aktifIds  = array_column($aktifList, 'id');
        $this->assertNotContains($insertId, $aktifIds);

        // 5. Delete
        $deleted = $model->delete($insertId);
        $this->assertTrue((bool) $deleted);
        $this->assertNull($model->find($insertId));
    }

    public function testRbacPermissionsForPersyaratan()
    {
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratan', ['tendik']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratan', ['admin']));
        $this->assertFalse(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratan', ['fakultas']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratanstore', ['tendik']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratanupdate', ['tendik']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratandelete', ['tendik']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratantogglestatus', ['tendik']));
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratanreorder', ['tendik']));

        // Mahasiswa & Dosen cannot manage persyaratan
        $this->assertFalse(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratan', ['mahasiswa']));
        $this->assertFalse(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratan', ['dosen']));
        $this->assertFalse(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratanstore', ['mahasiswa']));
        $this->assertFalse(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratanreorder', ['mahasiswa']));
    }

    public function testReorderUpdatesUrutan()
    {
        $model = new PersyaratanWisudaModel();
        $idA = $model->insert(['nama' => 'Item A', 'urutan' => 1, 'status' => 'AKTIF'], true);
        $idB = $model->insert(['nama' => 'Item B', 'urutan' => 2, 'status' => 'AKTIF'], true);

        // Simulate reorder swapping A and B
        $newOrder = [$idB, $idA];
        foreach ($newOrder as $idx => $id) {
            $model->update((int) $id, ['urutan' => $idx + 1]);
        }

        $itemB = $model->find($idB);
        $itemA = $model->find($idA);

        $this->assertSame(1, (int) $itemB['urutan']);
        $this->assertSame(2, (int) $itemA['urutan']);

        // Clean up
        $model->delete($idA);
        $model->delete($idB);
    }

    public function testDuplicateRequirementNameValidationLogic()
    {
        $model = new PersyaratanWisudaModel();

        // 1. Insert initial requirement
        $id1 = $model->insert([
            'nama'        => 'Ijazah Asli SMA',
            'keterangan'  => 'Ijazah SMA asli scan',
            'tipe_file'   => 'pdf',
            'max_size_kb' => 2048,
            'is_wajib'    => 1,
            'urutan'      => 10,
            'status'      => 'AKTIF',
        ], true);

        $this->assertIsNumeric($id1);

        // 2. Duplicate check simulation for store (exact match or case-insensitive)
        $namaDuplikat = 'ijazah asli sma';
        $existing = $model->where('LOWER(nama)', strtolower($namaDuplikat))->first();
        $this->assertNotNull($existing, 'Nama persyaratan yang sama (case-insensitive) harus terdeteksi sebagai duplikat');

        // Distinct name should not be detected as duplicate
        $namaUnik = 'Akte Kelahiran Asli';
        $existingUnik = $model->where('LOWER(nama)', strtolower($namaUnik))->first();
        $this->assertNull($existingUnik);

        // 3. Duplicate check simulation for update
        $id2 = $model->insert([
            'nama'        => 'Akte Kelahiran Asli',
            'keterangan'  => 'Akte asli',
            'tipe_file'   => 'pdf',
            'max_size_kb' => 2048,
            'is_wajib'    => 1,
            'urutan'      => 11,
            'status'      => 'AKTIF',
        ], true);

        // Updating item 2 with own name is allowed
        $existingSelf = $model->where('LOWER(nama)', strtolower('Akte Kelahiran Asli'))
            ->where('id !=', $id2)
            ->first();
        $this->assertNull($existingSelf, 'Mempertahankan nama sendiri saat update tidak boleh terdeteksi sebagai duplikat');

        // Updating item 2 with item 1's name is rejected as duplicate
        $existingClash = $model->where('LOWER(nama)', strtolower('Ijazah Asli SMA'))
            ->where('id !=', $id2)
            ->first();
        $this->assertNotNull($existingClash, 'Mengubah nama ke nama dokumen lain yang sudah ada harus terdeteksi sebagai duplikat');

        // Clean up
        $model->delete($id1);
        $model->delete($id2);
    }

    public function testFileExtensionAndMaxSizeSanitizationLogic()
    {
        $safeAllowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'zip'];

        // Input with leading dots, spaces, uppercase, and unauthorized exts (e.g. .exe, .sh)
        $rawTipeFile = ' .PDF, .docx, exe , .sh, jpg ';
        $cleanExts = [];
        foreach (explode(',', strtolower($rawTipeFile)) as $ext) {
            $ext = trim($ext, " .\t\n\r\0\x0B");
            if (in_array($ext, $safeAllowedExts, true)) {
                $cleanExts[] = $ext;
            }
        }
        $sanitizedExt = ! empty($cleanExts) ? implode(',', array_unique($cleanExts)) : 'pdf,jpg,jpeg,png';

        $this->assertSame('pdf,docx,jpg', $sanitizedExt);

        // Fallback when completely invalid
        $invalidRaw = 'exe, bat, sh';
        $fallbackExts = [];
        foreach (explode(',', strtolower($invalidRaw)) as $ext) {
            $ext = trim($ext, " .\t\n\r\0\x0B");
            if (in_array($ext, $safeAllowedExts, true)) {
                $fallbackExts[] = $ext;
            }
        }
        $finalFallback = ! empty($fallbackExts) ? implode(',', array_unique($fallbackExts)) : 'pdf,jpg,jpeg,png';
        $this->assertSame('pdf,jpg,jpeg,png', $finalFallback);

        // Max size boundary clamping
        $sizeLow = 100;
        $clampedLow = ($sizeLow < 256) ? 2048 : $sizeLow;
        $this->assertSame(2048, $clampedLow);

        $sizeHigh = 999999;
        $clampedHigh = ($sizeHigh > 51200) ? 51200 : $sizeHigh;
        $this->assertSame(51200, $clampedHigh);
    }

    public function testDeleteIntegrityWithStudentUploads()
    {
        $model = new PersyaratanWisudaModel();
        $berkasModel = new \App\Models\BerkasPendaftaranWisudaModel();

        $syaratId = $model->insert([
            'nama'        => 'Dokumen Penting Wisuda',
            'tipe_file'   => 'pdf',
            'max_size_kb' => 2048,
            'is_wajib'    => 1,
            'urutan'      => 999,
            'status'      => 'AKTIF',
        ], true);

        // 1. Initially no berkas uploaded -> can be deleted
        $usedCount = $berkasModel->groupStart()
            ->where('persyaratan_id', $syaratId)
            ->orWhere('nama_persyaratan', 'Dokumen Penting Wisuda')
            ->groupEnd()
            ->countAllResults();
        $this->assertSame(0, $usedCount);

        // 2. Student uploads a file linked by persyaratan_id
        $berkasId = $berkasModel->insert([
            'pendaftaran_id'   => 101,
            'persyaratan_id'   => $syaratId,
            'nama_persyaratan' => 'Dokumen Penting Wisuda',
            'nama_file'        => 'dokumen_101.pdf',
        ], true);

        // Check again -> deletion must be blocked
        $usedCountAfterUpload = $berkasModel->groupStart()
            ->where('persyaratan_id', $syaratId)
            ->orWhere('nama_persyaratan', 'Dokumen Penting Wisuda')
            ->groupEnd()
            ->countAllResults();
        $this->assertGreaterThan(0, $usedCountAfterUpload);

        // Clean up berkas and requirement
        $berkasModel->delete($berkasId);
        $model->delete($syaratId);
    }

    public function testPartialReorderGuardLogic()
    {
        $model = new PersyaratanWisudaModel();
        $id1 = $model->insert(['nama' => 'Syarat Urutan 1', 'urutan' => 1, 'status' => 'AKTIF'], true);
        $id2 = $model->insert(['nama' => 'Syarat Urutan 2', 'urutan' => 2, 'status' => 'AKTIF'], true);
        $id3 = $model->insert(['nama' => 'Syarat Urutan 3', 'urutan' => 3, 'status' => 'AKTIF'], true);

        $totalAll = $model->countAllResults();
        $this->assertGreaterThanOrEqual(3, $totalAll);

        // Partial order attempt with only 2 items out of totalAll
        $partialOrders = [$id2, $id1];
        $isPartial = count($partialOrders) < $totalAll;
        $this->assertTrue($isPartial, 'Daftar urutan parsial harus terdeteksi untuk mencegah perusakan urutan data');

        // Full order attempt
        $allRequirements = $model->findAll();
        $fullOrders = array_column($allRequirements, 'id');
        $this->assertFalse(count($fullOrders) < $totalAll, 'Daftar urutan lengkap harus diizinkan untuk diproses');

        // Clean up
        $model->delete($id1);
        $model->delete($id2);
        $model->delete($id3);
    }
}

