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

        foreach (['staf', 'tendik', 'admin', 'fakultas', 'prodi', 'kaprodi'] as $allowedRole) {
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

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'wisudawan'] as $deniedRole) {
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

    public function testNonaktifkanUrutanPertamaAutoMerapatkanUrutanAktif(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $bankModel       = new BankPertanyaanTracerStudiModel();
        $pertanyaanModel = new PertanyaanTracerStudiAwalModel();

        $originalRows = $pertanyaanModel->getDaftarManajemenPertanyaan();
        $originalUris = [];
        foreach ($originalRows as $orig) {
            $originalUris[(int) $orig['id']] = (int) ($orig['urutan'] ?? 1);
        }

        $createdPertanyaanIds = [];
        $createdBankIds       = [];

        try {
            $suffix = (string) random_int(1000, 9999);
            $q1Res  = $pertanyaanModel->createPertanyaan([
                'teks_pertanyaan' => "[AUTO-ORDER-1-{$suffix}] Pertanyaan pertama untuk dinonaktifkan",
                'tipe_jawaban'    => 'TEKS',
                'is_wajib'        => '1',
                'status'          => 'AKTIF',
            ]);
            $q2Res = $pertanyaanModel->createPertanyaan([
                'teks_pertanyaan' => "[AUTO-ORDER-2-{$suffix}] Pertanyaan kedua yang harus naik jadi nomor satu",
                'tipe_jawaban'    => 'TEKS',
                'is_wajib'        => '0',
                'status'          => 'AKTIF',
            ]);

            $this->assertTrue($q1Res['success']);
            $this->assertTrue($q2Res['success']);
            $q1Id                   = (int) $q1Res['id'];
            $q2Id                   = (int) $q2Res['id'];
            $createdPertanyaanIds[] = $q1Id;
            $createdPertanyaanIds[] = $q2Id;

            $orderedIds = array_merge([$q1Id, $q2Id], array_keys($originalUris));
            $orderRes   = $pertanyaanModel->updateUrutanPertanyaan($orderedIds, true);
            $this->assertTrue($orderRes['success']);

            // Nonaktifkan pertanyaan urutan 1 -> pindah ke Bank, lalu mantan urutan 2 otomatis menjadi urutan 1
            $moveRes = $pertanyaanModel->moveQuestionToBank($q1Id, $bankModel, 'Tester Order');
            $this->assertTrue($moveRes['success']);
            if (! empty($moveRes['bankId'])) {
                $createdBankIds[] = (int) $moveRes['bankId'];
            }

            $q2AfterMove = $pertanyaanModel->find($q2Id);
            $this->assertIsArray($q2AfterMove);
            $this->assertSame(1, (int) $q2AfterMove['urutan']);

            $activeAfterMove = $pertanyaanModel->getDaftarPertanyaan();
            $this->assertSame($q2Id, (int) ($activeAfterMove[0]['id'] ?? 0));
            $expectedSeq = range(1, count($activeAfterMove));
            $actualSeq   = array_map(static fn (array $r): int => (int) ($r['urutan'] ?? 0), $activeAfterMove);
            $this->assertSame($expectedSeq, $actualSeq);
        } finally {
            foreach ($createdPertanyaanIds as $qid) {
                $db->table('pertanyaan_tracer_studi_awal')->where('id', $qid)->delete();
            }
            foreach ($createdBankIds as $bid) {
                $db->table('bank_pertanyaan_tracer_studi')->where('id', $bid)->delete();
            }
            foreach ($originalUris as $origId => $origUrutan) {
                $pertanyaanModel->update($origId, ['urutan' => $origUrutan]);
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
