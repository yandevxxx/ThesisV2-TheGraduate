<?php

namespace Tests\Unit;

use App\Libraries\AccessControl;
use App\Models\JawabanTracerStudiAwalModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite B-15/16: Ubah dan hapus pertanyaan tracer studi awal (sisi The Graduate Web).
 *
 * @internal
 */
final class TracerStudiEditDeleteTest extends CIUnitTestCase
{
    public function testRbacUbahDanHapusPertanyaanHanyaUntukStafDanProdi(): void
    {
        foreach (['staf', 'tendik', 'admin', 'fakultas', 'prodi', 'kaprodi'] as $allowedRole) {
            $this->assertTrue(
                AccessControl::canAccess('TracerStudiController', 'updatePertanyaan', [$allowedRole]),
                "Role {$allowedRole} harus diizinkan mengubah pertanyaan tracer studi awal (B-15)."
            );
            $this->assertTrue(
                AccessControl::canAccess('TracerStudiController', 'deletePertanyaan', [$allowedRole]),
                "Role {$allowedRole} harus diizinkan menghapus pertanyaan tracer studi awal (B-16)."
            );
            $this->assertTrue(
                AccessControl::canAccess('Graduate', 'pertanyaanTracerStudiUpdate', [$allowedRole]),
                "Role {$allowedRole} harus diizinkan mengakses route ubah pertanyaan (B-15)."
            );
            $this->assertTrue(
                AccessControl::canAccess('Graduate', 'pertanyaanTracerStudiDelete', [$allowedRole]),
                "Role {$allowedRole} harus diizinkan mengakses route hapus pertanyaan (B-16)."
            );
        }

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'alumni'] as $deniedRole) {
            $this->assertFalse(
                AccessControl::canAccess('TracerStudiController', 'updatePertanyaan', [$deniedRole]),
                "Role {$deniedRole} tidak boleh mengubah pertanyaan tracer studi awal."
            );
            $this->assertFalse(
                AccessControl::canAccess('TracerStudiController', 'deletePertanyaan', [$deniedRole]),
                "Role {$deniedRole} tidak boleh menghapus pertanyaan tracer studi awal."
            );
        }
    }

    public function testUbahDanHapusPertanyaanMenjagaJawabanTersimpanDiB14(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $pertanyaanModel = new PertanyaanTracerStudiAwalModel();
        $jawabanModel    = new JawabanTracerStudiAwalModel();
        $npmTest         = 'B15TEST' . random_int(100, 999);
        $createdIds      = [];

        $db->table('mahasiswa_terplot_sk_yudisium')->insert([
            'npm'           => $npmTest,
            'nama'          => 'Mahasiswa Test B1516',
            'status'        => 'baru',
            'kode_semester' => '2026-2',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        try {
            // 1. Tambah 2 pertanyaan uji (B-11)
            $resEditTarget = $pertanyaanModel->createPertanyaan([
                'teks_pertanyaan' => '[B15-ORIG] Berapa bulan waktu tunggu sebelum bekerja?',
                'tipe_jawaban'    => 'ISIAN_SINGKAT',
                'is_wajib'        => '1',
                'status'          => 'AKTIF',
            ]);
            $resDeleteTarget = $pertanyaanModel->createPertanyaan([
                'teks_pertanyaan' => '[B16-ORIG] Pertanyaan lama yang akan dihapus?',
                'tipe_jawaban'    => 'TEKS',
                'is_wajib'        => '0',
                'status'          => 'AKTIF',
            ]);

            $this->assertTrue($resEditTarget['success']);
            $this->assertTrue($resDeleteTarget['success']);

            $idEdit      = (int) $resEditTarget['id'];
            $idDelete    = (int) $resDeleteTarget['id'];
            $createdIds  = [$idEdit, $idDelete];

            // 2. Mahasiswa mengisi dan menyimpan jawaban (B-13)
            $qEditRow   = $pertanyaanModel->find($idEdit);
            $qDeleteRow = $pertanyaanModel->find($idDelete);
            $jawabanModel->saveJawabanMahasiswa(
                $npmTest,
                [$qEditRow, $qDeleteRow],
                [
                    $idEdit   => '2 bulan setelah yudisium',
                    $idDelete => 'Jawaban historis yang harus tetap utuh',
                ],
                '2026-2'
            );

            // 3. Validasi ubah menolak teks kosong
            $invalidUpdate = $pertanyaanModel->updatePertanyaan($idEdit, [
                'teks_pertanyaan' => '   ',
                'tipe_jawaban'    => 'TEKS',
            ]);
            $this->assertFalse($invalidUpdate['success']);
            $this->assertNotEmpty($invalidUpdate['errors']);

            // 4. Staf mengubah pertanyaan pertama (B-15) -> perubahan tersimpan & tampil pada form
            $updatedRes = $pertanyaanModel->updatePertanyaan($idEdit, [
                'teks_pertanyaan' => '[B15-UPDATED] Seberapa cepat Anda memperoleh pekerjaan pertama?',
                'tipe_jawaban'    => 'PILIHAN_GANDA',
                'opsi_jawaban'    => "Kurang dari 3 bulan\n3 - 6 bulan\nLebih dari 6 bulan",
                'is_wajib'        => '1',
                'status'          => 'AKTIF',
            ]);
            $this->assertTrue($updatedRes['success']);

            // 5. Staf menghapus pertanyaan kedua (B-16) -> tidak lagi tampil pada form
            $deletedRes = $pertanyaanModel->deletePertanyaan($idDelete);
            $this->assertTrue($deletedRes['success']);
            $createdIds = [$idEdit];

            $formQuestions = $pertanyaanModel->getDaftarPertanyaan();
            $formById      = array_column($formQuestions, null, 'id');
            $this->assertArrayHasKey($idEdit, $formById);
            $this->assertSame('[B15-UPDATED] Seberapa cepat Anda memperoleh pekerjaan pertama?', $formById[$idEdit]['teks_pertanyaan']);
            $this->assertSame('PILIHAN_GANDA', $formById[$idEdit]['tipe_jawaban']);
            $this->assertArrayNotHasKey($idDelete, $formById);

            // 6. AC 3: Jawaban yang sudah tersimpan atas pertanyaan diubah & dihapus tetap utuh dan tampil di B-14
            $monitoring = $jawabanModel->getMonitoringTerplot(['q' => $npmTest]);
            $this->assertSame('SUDAH', $monitoring['rows'][0]['status_tracer'] ?? null);

            $detail     = $jawabanModel->getDetailJawabanByNpm($npmTest);
            $detailByQ  = array_column($detail, null, 'pertanyaan_id');
            $this->assertArrayHasKey($idEdit, $detailByQ);
            $this->assertArrayHasKey($idDelete, $detailByQ);
            $this->assertSame('[B15-ORIG] Berapa bulan waktu tunggu sebelum bekerja?', $detailByQ[$idEdit]['teks_pertanyaan']);
            $this->assertSame('2 bulan setelah yudisium', $detailByQ[$idEdit]['jawaban']);
            $this->assertSame('[B16-ORIG] Pertanyaan lama yang akan dihapus?', $detailByQ[$idDelete]['teks_pertanyaan']);
            $this->assertSame('Jawaban historis yang harus tetap utuh', $detailByQ[$idDelete]['jawaban']);
        } finally {
            $db->table('jawaban_tracer_studi_awal')->where('npm', $npmTest)->delete();
            $db->table('mahasiswa_terplot_sk_yudisium')->where('npm', $npmTest)->delete();
            foreach ($createdIds as $id) {
                $pertanyaanModel->delete($id);
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
