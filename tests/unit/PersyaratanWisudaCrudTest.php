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
        $this->assertTrue(\App\Libraries\AccessControl::canAccess('Graduate', 'persyaratan', ['fakultas']));
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
}

