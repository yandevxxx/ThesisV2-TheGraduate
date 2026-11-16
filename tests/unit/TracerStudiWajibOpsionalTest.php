<?php

namespace Tests\Unit;

use App\Libraries\AccessControl;
use App\Models\JawabanTracerStudiAwalModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite B-17: Tetapkan pertanyaan wajib atau opsional (sisi The Graduate Web).
 *
 * @internal
 */
final class TracerStudiWajibOpsionalTest extends CIUnitTestCase
{
    public function testRbacToggleWajibHanyaUntukStafDanProdi(): void
    {
        foreach (['staf', 'tendik', 'admin', 'fakultas', 'prodi', 'kaprodi'] as $allowedRole) {
            $this->assertTrue(
                AccessControl::canAccess('TracerStudiController', 'toggleWajibPertanyaan', [$allowedRole]),
                "Role {$allowedRole} harus diizinkan mengatur status Wajib Diisi (B-17)."
            );
            $this->assertTrue(
                AccessControl::canAccess('Graduate', 'pertanyaanTracerStudiToggleWajib', [$allowedRole]),
                "Role {$allowedRole} harus diizinkan mengakses route toggle wajib (B-17)."
            );
        }

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'wisudawan'] as $deniedRole) {
            $this->assertFalse(
                AccessControl::canAccess('TracerStudiController', 'toggleWajibPertanyaan', [$deniedRole]),
                "Role {$deniedRole} tidak boleh mengatur status Wajib Diisi."
            );
        }
    }

    public function testToggleWajibMengubahValidasiDanMengizinkanPertanyaanOpsionalDilewati(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $pertanyaanModel = new PertanyaanTracerStudiAwalModel();
        $createdId       = null;

        try {
            // 1. Tambah pertanyaan baru dengan status awal Opsional (is_wajib = 0)
            $created = $pertanyaanModel->createPertanyaan([
                'teks_pertanyaan' => '[B17-TEST] Tuliskan sertifikasi keahlian tambahan Anda.',
                'tipe_jawaban'    => 'ISIAN_SINGKAT',
                'is_wajib'        => '0',
                'status'          => 'AKTIF',
            ]);
            $this->assertTrue($created['success']);
            $createdId = (int) $created['id'];
            $this->assertSame(0, (int) $created['data']['is_wajib']);

            // 2. Saat opsional, pertanyaan boleh dikosongkan tanpa error validasi
            $rowOpsional = $pertanyaanModel->find($createdId);
            $resSkip     = JawabanTracerStudiAwalModel::validateJawabanSet([$rowOpsional], [$createdId => '']);
            $this->assertSame([], $resSkip['errors']);

            // 3. Staf mengaktifkan pengaturan "Wajib Diisi" (is_wajib = 1)
            $enableRes = $pertanyaanModel->toggleWajibPertanyaan($createdId, true);
            $this->assertTrue($enableRes['success']);
            $this->assertSame(1, (int) $enableRes['is_wajib']);

            // 4. Sistem menolak pengiriman form jika pertanyaan wajib kosong & menampilkan peringatan pada ID terkait
            $rowWajib   = $pertanyaanModel->find($createdId);
            $resReject  = JawabanTracerStudiAwalModel::validateJawabanSet([$rowWajib], [$createdId => '   ']);
            $this->assertArrayHasKey($createdId, $resReject['errors']);
            $this->assertStringContainsString('wajib diisi', strtolower((string) $resReject['errors'][$createdId]));

            // 5. Staf mematikan kembali pengaturan "Wajib Diisi" menjadi Opsional (toggle)
            $disableRes = $pertanyaanModel->toggleWajibPertanyaan($createdId);
            $this->assertTrue($disableRes['success']);
            $this->assertSame(0, (int) $disableRes['is_wajib']);

            // 6. Setelah kembali opsional, pertanyaan dapat dilewati
            $rowOpsionalAgain = $pertanyaanModel->find($createdId);
            $resAllowSkip     = JawabanTracerStudiAwalModel::validateJawabanSet([$rowOpsionalAgain], []);
            $this->assertSame([], $resAllowSkip['errors']);
        } finally {
            if ($createdId !== null && $createdId > 0) {
                $pertanyaanModel->delete($createdId);
            }
        }
    }

    private function ensureTables(\CodeIgniter\Database\BaseConnection $db): void
    {
        if (! $db->tableExists('pertanyaan_tracer_studi_awal')) {
            $forge = \Config\Database::forge();
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
    }
}
