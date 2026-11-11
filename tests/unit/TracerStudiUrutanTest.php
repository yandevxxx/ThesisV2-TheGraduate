<?php

namespace Tests\Unit;

use App\Libraries\AccessControl;
use App\Models\JawabanTracerStudiAwalModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite B-18: Atur urutan pertanyaan tracer studi awal (sisi The Graduate Web).
 *
 * @internal
 */
final class TracerStudiUrutanTest extends CIUnitTestCase
{
    public function testRbacAturUrutanPertanyaanHanyaUntukStafDanProdi(): void
    {
        foreach (['staf', 'tendik', 'admin', 'prodi', 'kaprodi'] as $allowedRole) {
            $this->assertTrue(
                AccessControl::canAccess('TracerStudiController', 'simpanUrutanPertanyaan', [$allowedRole]),
                "Role {$allowedRole} harus diizinkan mengatur urutan pertanyaan (B-18)."
            );
            $this->assertTrue(
                AccessControl::canAccess('Graduate', 'pertanyaanTracerStudiUrutan', [$allowedRole]),
                "Role {$allowedRole} harus diizinkan mengakses route urutan pertanyaan (B-18)."
            );
        }

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'wisudawan', 'fakultas'] as $deniedRole) {
            $this->assertFalse(
                AccessControl::canAccess('TracerStudiController', 'simpanUrutanPertanyaan', [$deniedRole]),
                "Role {$deniedRole} tidak boleh mengatur urutan pertanyaan tracer studi awal."
            );
        }
    }

    public function testUpdateUrutanMengubahUrutanFormDanMenjagaJawabanTersimpan(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $pertanyaanModel = new PertanyaanTracerStudiAwalModel();
        $jawabanModel    = new JawabanTracerStudiAwalModel();
        $npm             = 'B18TEST' . random_int(100, 999);
        $createdIds      = [];

        $originalRows = $pertanyaanModel->getDaftarManajemenPertanyaan();
        $originalUris = [];
        foreach ($originalRows as $orig) {
            $originalUris[(int) $orig['id']] = (int) ($orig['urutan'] ?? 1);
        }

        try {
            // 1. Tambah pertanyaan uji dan mahasiswa sudah menjawab sebelum perubahan urutan
            $qRes = $pertanyaanModel->createPertanyaan([
                'teks_pertanyaan' => '[B18-TEST] Pertanyaan paling akhir awal yang dipindah ke urutan 1',
                'tipe_jawaban'    => 'ISIAN_SINGKAT',
                'is_wajib'        => '1',
                'status'          => 'AKTIF',
            ]);
            $this->assertTrue($qRes['success']);
            $movedId      = (int) $qRes['id'];
            $createdIds[] = $movedId;

            $qBeforeRow = $pertanyaanModel->find($movedId);
            $oldOrder   = (int) $qBeforeRow['urutan'];
            $jawabanModel->saveJawabanMahasiswa(
                $npm,
                [$qBeforeRow],
                [$movedId => 'Jawaban sebelum urutan diubah'],
                '2026-2'
            );

            // 2. Tolak input urutan invalid (0 / non-positif)
            $invalidRes = $pertanyaanModel->updateUrutanPertanyaan([$movedId => '0']);
            $this->assertFalse($invalidRes['success']);

            // 3. Pindahkan pertanyaan uji ke urutan nomor 1 lewat input urutan_map
            $okRes = $pertanyaanModel->updateUrutanPertanyaan([$movedId => '1']);
            $this->assertTrue($okRes['success']);

            // 4. Form mahasiswa berikutnya langsung memuat pertanyaan tersebut di posisi pertama dengan nomor 1
            $activeList = $pertanyaanModel->getDaftarPertanyaan();
            $this->assertNotEmpty($activeList);
            $this->assertSame($movedId, (int) $activeList[0]['id']);
            $this->assertSame(1, (int) $activeList[0]['urutan']);

            // Uji pula reorder via daftar ID eksplisit (orders) dengan memindahkannya ke urutan 2
            if (isset($activeList[1])) {
                $secondId  = (int) $activeList[1]['id'];
                $swapOrder = array_values(array_map('intval', array_column($activeList, 'id')));
                $swapOrder[0] = $secondId;
                $swapOrder[1] = $movedId;

                $listRes = $pertanyaanModel->updateUrutanPertanyaan($swapOrder, true);
                $this->assertTrue($listRes['success']);
                $activeAfterSwap = $pertanyaanModel->getDaftarPertanyaan();
                $this->assertSame($movedId, (int) $activeAfterSwap[1]['id']);
                $this->assertSame(2, (int) $activeAfterSwap[1]['urutan']);
            }

            // 5. AC 4: Jawaban tersimpan sebelumnya tidak berubah (snapshot urutan & jawaban tetap utuh)
            $detail = $jawabanModel->getDetailJawabanByNpm($npm);
            $this->assertCount(1, $detail);
            $this->assertSame($oldOrder, (int) $detail[0]['urutan']);
            $this->assertSame('Jawaban sebelum urutan diubah', $detail[0]['jawaban']);
        } finally {
            $db->table('jawaban_tracer_studi_awal')->where('npm', $npm)->delete();
            foreach ($createdIds as $id) {
                $pertanyaanModel->delete($id);
            }
            foreach ($originalUris as $origId => $origUrutan) {
                $pertanyaanModel->update($origId, ['urutan' => $origUrutan]);
            }
        }
    }

    private function ensureTables(\CodeIgniter\Database\BaseConnection $db): void
    {
        $forge = \Config\Database::forge();

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
