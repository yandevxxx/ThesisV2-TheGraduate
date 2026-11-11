<?php

namespace Tests\Unit;

use App\Libraries\AccessControl;
use App\Models\BankPertanyaanTracerStudiModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite Bank Pertanyaan Tracer Study (sisi The Graduate Web).
 *
 * @internal
 */
final class TracerStudiBankPertanyaanTest extends CIUnitTestCase
{
    public function testRbacBankPertanyaanUntukTendikStafDanProdi(): void
    {
        $methods = [
            'bankPertanyaan',
            'storeBankPertanyaan',
            'updateBankPertanyaan',
            'deleteBankPertanyaan',
            'ambilDariBank',
            'simpanKeBank',
            'nonaktifkanKeBank',
        ];

        $routes = [
            'bank_pertanyaan_tracer_studi',
            'bankPertanyaanTracerStudi',
            'bankPertanyaanTracerStudiStore',
            'bankPertanyaanTracerStudiUpdate',
            'bankPertanyaanTracerStudiDelete',
            'pertanyaanTracerStudiAmbilBank',
            'pertanyaanTracerStudiSimpanKeBank',
            'pertanyaanTracerStudiNonaktifkan',
        ];

        foreach (['staf', 'tendik', 'admin', 'prodi', 'kaprodi'] as $allowedRole) {
            $this->assertTrue(
                AccessControl::menuVisible('bank_pertanyaan_tracer_studi', [$allowedRole]),
                "Menu Bank Pertanyaan harus terlihat untuk role {$allowedRole}."
            );

            foreach ($methods as $method) {
                $this->assertTrue(
                    AccessControl::canAccess('TracerStudiController', $method, [$allowedRole]),
                    "Role {$allowedRole} harus diizinkan mengakses TracerStudiController::{$method}."
                );
            }

            foreach ($routes as $route) {
                $this->assertTrue(
                    AccessControl::canAccess('Graduate', $route, [$allowedRole]),
                    "Role {$allowedRole} harus diizinkan mengakses route Graduate/{$route}."
                );
            }
        }

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'wisudawan', 'fakultas'] as $deniedRole) {
            $this->assertFalse(
                AccessControl::menuVisible('bank_pertanyaan_tracer_studi', [$deniedRole]),
                "Menu Bank Pertanyaan tidak boleh tampil untuk role {$deniedRole}."
            );

            foreach ($methods as $method) {
                $this->assertFalse(
                    AccessControl::canAccess('TracerStudiController', $method, [$deniedRole]),
                    "Role {$deniedRole} harus ditolak pada TracerStudiController::{$method}."
                );
            }
        }
    }

    public function testCrudBankDanAmbilKeFormAktifTanpaDuplikasi(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $bankModel       = new BankPertanyaanTracerStudiModel();
        $pertanyaanModel = new PertanyaanTracerStudiAwalModel();

        $createdBankIds       = [];
        $createdPertanyaanIds = [];

        try {
            $suffix    = (string) random_int(1000, 9999);
            $createRes = $bankModel->createBankPertanyaan([
                'kategori'         => 'Relevansi & Kompetensi',
                'teks_pertanyaan'  => "[BANK-TEST-{$suffix}] Seberapa relevan mata kuliah proyek terhadap pekerjaan Anda?",
                'tipe_jawaban'     => 'SKALA',
                'opsi_jawaban'     => '',
                'is_wajib_default' => '1',
            ], 'Tester Prodi');

            $this->assertTrue($createRes['success']);
            $bankId = (int) $createRes['id'];
            $createdBankIds[] = $bankId;

            $updateRes = $bankModel->updateBankPertanyaan($bankId, [
                'kategori'         => 'Evaluasi Layanan & Kurikulum',
                'teks_pertanyaan'  => "[BANK-TEST-{$suffix}] Seberapa relevan praktikum & proyek terhadap pekerjaan pertama Anda?",
                'tipe_jawaban'     => 'PILIHAN_GANDA',
                'opsi_jawaban'     => "Sangat Relevan\nCukup Relevan\nKurang Relevan",
                'is_wajib_default' => '0',
            ]);
            $this->assertTrue($updateRes['success']);

            $fetched   = $bankModel->getByIds([$bankId]);
            $this->assertCount(1, $fetched);

            // Mengaktifkan dari Bank memindahkan pertanyaan ke form AKTIF dan menghapusnya dari Bank
            $activateFirst = $pertanyaanModel->activateFromBank($fetched, $bankModel);
            $this->assertTrue($activateFirst['success']);
            $this->assertSame(1, $activateFirst['importedCount']);
            $this->assertSame(0, $activateFirst['skippedCount']);
            $this->assertSame(1, $activateFirst['removedFromBankCount']);
            $createdPertanyaanIds = array_merge($createdPertanyaanIds, $activateFirst['importedIds']);

            $activatedRow = $pertanyaanModel->find((int) $activateFirst['importedIds'][0]);
            $this->assertIsArray($activatedRow);
            $this->assertSame(0, (int) ($activatedRow['is_wajib'] ?? 1));
            $this->assertSame('AKTIF', strtoupper((string) ($activatedRow['status'] ?? '')));
            $this->assertNull($bankModel->find($bankId), 'Item yang diaktifkan harus keluar dari tabel Bank Pertanyaan.');

            // Menonaktifkan pertanyaan form aktif langsung memindahkannya kembali ke Bank (dan menghapus dari form)
            $moveRes = $pertanyaanModel->moveQuestionToBank((int) $activateFirst['importedIds'][0], $bankModel, 'Tester Move');
            $this->assertTrue($moveRes['success']);
            $this->assertNull($pertanyaanModel->find((int) $activateFirst['importedIds'][0]));
            $movedBankId = (int) ($moveRes['bankId'] ?? 0);
            $this->assertGreaterThan(0, $movedBankId);
            $createdBankIds[] = $movedBankId;

            // syncExclusiveWithBank menjaga pemisahan eksklusif (aktif hanya di form, nonaktif hanya di bank)
            $customFormRes = $pertanyaanModel->createPertanyaan([
                'teks_pertanyaan' => "[FORM-SYNC-{$suffix}] Apa saran pengembangan sertifikasi kampus?",
                'tipe_jawaban'    => 'TEKS',
                'is_wajib'        => '0',
                'status'          => 'AKTIF',
            ]);
            $this->assertTrue($customFormRes['success']);
            $customFormId           = (int) $customFormRes['id'];
            $createdPertanyaanIds[] = $customFormId;

            $dupBankRes = $bankModel->createBankPertanyaan([
                'kategori'         => 'Saran & Masukan',
                'teks_pertanyaan'  => "[FORM-SYNC-{$suffix}] Apa saran pengembangan sertifikasi kampus?",
                'tipe_jawaban'     => 'TEKS',
                'is_wajib_default' => '0',
            ], 'Tester Sync');
            $dupBankId        = (int) ($dupBankRes['id'] ?? 0);
            $createdBankIds[] = $dupBankId;

            $syncStats = $pertanyaanModel->syncExclusiveWithBank($bankModel, 'Tester Sync');
            $this->assertGreaterThanOrEqual(1, $syncStats['removedFromBank']);
            $this->assertNull($bankModel->find($dupBankId), 'Pertanyaan aktif tidak boleh tersisa di Bank Pertanyaan.');
            $this->assertIsArray($pertanyaanModel->find($customFormId));

            // Menghapus item nonaktif dari Bank tidak memengaruhi form aktif
            $delBank = $bankModel->deleteBankPertanyaan($movedBankId);
            $this->assertTrue($delBank['success']);
            $this->assertIsArray($pertanyaanModel->find($customFormId));
        } finally {
            foreach ($createdPertanyaanIds as $qid) {
                $db->table('pertanyaan_tracer_studi_awal')->where('id', $qid)->delete();
            }
            foreach ($createdBankIds as $bid) {
                $db->table('bank_pertanyaan_tracer_studi')->where('id', $bid)->delete();
            }
        }
    }

    private function ensureTables(\CodeIgniter\Database\BaseConnection $db): void
    {
        $forge = \Config\Database::forge($db);

        if (! $db->tableExists('pertanyaan_tracer_studi_awal')) {
            require_once APPPATH . 'Database/Migrations/2026-11-11-010000_CreatePertanyaanTracerStudiAwalTable.php';
            (new \App\Database\Migrations\CreatePertanyaanTracerStudiAwalTable($forge))->up();
        }
        if (! $db->tableExists('bank_pertanyaan_tracer_studi')) {
            require_once APPPATH . 'Database/Migrations/2026-11-11-030000_CreateBankPertanyaanTracerStudiTable.php';
            (new \App\Database\Migrations\CreateBankPertanyaanTracerStudiTable($forge))->up();
        }
        if (! $db->fieldExists('bank_pertanyaan_id', 'pertanyaan_tracer_studi_awal')) {
            require_once APPPATH . 'Database/Migrations/2026-11-11-040000_AddBankPertanyaanIdToPertanyaanTracerStudiAwalTable.php';
            (new \App\Database\Migrations\AddBankPertanyaanIdToPertanyaanTracerStudiAwalTable($forge))->up();
        }
        require_once APPPATH . 'Database/Migrations/2026-11-11-050000_CleanActiveQuestionsFromBankTracerStudi.php';
        (new \App\Database\Migrations\CleanActiveQuestionsFromBankTracerStudi($forge))->up();
    }
}
