<?php

namespace Tests\Unit;

use App\Libraries\AccessControl;
use App\Models\BankPertanyaanTracerStudiModel;
use App\Models\KategoriTracerStudiModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite Master Kategori Tracer Study (The Graduate Web).
 *
 * @internal
 */
final class TracerStudiKategoriTest extends CIUnitTestCase
{
    public function testRbacMasterKategoriUntukStafDanProdi(): void
    {
        $methods = [
            'kategoriPertanyaan',
            'storeKategori',
            'updateKategori',
            'deleteKategori',
        ];

        $graduateRoutes = [
            'kategori_tracer_studi',
            'kategoriTracerStudi',
            'kategoriTracerStudiStore',
            'kategoriTracerStudiUpdate',
            'kategoriTracerStudiDelete',
        ];

        foreach (['admin', 'tendik', 'staf', 'fakultas', 'prodi', 'kaprodi'] as $allowedRole) {
            $this->assertTrue(
                AccessControl::menuVisible('kategori_tracer_studi', [$allowedRole]),
                "Menu Master Kategori harus tampil untuk role {$allowedRole}."
            );

            foreach ($methods as $method) {
                $this->assertTrue(
                    AccessControl::canAccess('TracerStudiController', $method, [$allowedRole]),
                    "Role {$allowedRole} harus diizinkan mengakses TracerStudiController::{$method}."
                );
            }

            foreach ($graduateRoutes as $route) {
                $this->assertTrue(
                    AccessControl::canAccess('Graduate', $route, [$allowedRole]),
                    "Role {$allowedRole} harus diizinkan mengakses Graduate/{$route}."
                );
            }
        }

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'alumni', 'klien', 'tamu'] as $deniedRole) {
            $this->assertFalse(
                AccessControl::menuVisible('kategori_tracer_studi', [$deniedRole]),
                "Menu Master Kategori tidak boleh tampil untuk role {$deniedRole}."
            );

            foreach ($methods as $method) {
                $this->assertFalse(
                    AccessControl::canAccess('TracerStudiController', $method, [$deniedRole]),
                    "Role {$deniedRole} harus ditolak mengakses TracerStudiController::{$method}."
                );
            }
        }
    }

    public function testMigrasiSeedDanCrudMasterKategoriDenganProteksiDanCascade(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $kategoriModel   = new KategoriTracerStudiModel();
        $bankModel       = new BankPertanyaanTracerStudiModel();
        $pertanyaanModel = new PertanyaanTracerStudiAwalModel();

        // 1. Kategori bawaan "Umum" tidak disemai/disertakan lagi
        $this->assertNull($kategoriModel->findByNama('Umum'));

        $usageRows = $kategoriModel->getDaftarKategoriWithUsageCounts();
        $this->assertNotEmpty($usageRows);
        foreach ($usageRows as $row) {
            $this->assertFalse($row['is_protected']);
        }

        $createdKategoriIds   = [];
        $createdBankIds       = [];
        $createdPertanyaanIds = [];

        try {
            $suffix = (string) random_int(1000, 9999);

            // 2. Validasi input kosong & batas panjang
            $emptyRes = $kategoriModel->createKategori(['nama_kategori' => '   ']);
            $this->assertFalse($emptyRes['success']);

            $longRes = $kategoriModel->createKategori(['nama_kategori' => str_repeat('A', 85)]);
            $this->assertFalse($longRes['success']);

            // 3. Tambah kategori baru
            $namaAwal = "Karier Alumni {$suffix}";
            $createRes = $kategoriModel->createKategori([
                'nama_kategori' => $namaAwal,
                'deskripsi'     => 'Kategori uji Tracer Study',
            ], 'Admin Uji');
            $this->assertTrue($createRes['success']);
            $katId = (int) $createRes['id'];
            $createdKategoriIds[] = $katId;

            // Duplikasi case-insensitive ditolak
            $dupRes = $kategoriModel->createKategori([
                'nama_kategori' => strtolower($namaAwal),
            ], 'Admin Uji');
            $this->assertFalse($dupRes['success']);

            // Kategori baru muncul di BankPertanyaanTracerStudiModel::getKategoriList()
            $this->assertContains($namaAwal, $bankModel->getKategoriList());

            // 4. Tidak boleh hapus kategori jika masih dipakai oleh pertanyaan Bank atau Form Aktif
            $bankRes = $bankModel->createBankPertanyaan([
                'kategori'         => $namaAwal,
                'teks_pertanyaan'  => "[KAT-BANK-{$suffix}] Pertanyaan bank untuk uji relasi kategori",
                'tipe_jawaban'     => 'TEKS',
                'is_wajib_default' => '1',
            ], 'Tester');
            $this->assertTrue($bankRes['success']);
            $bankId = (int) $bankRes['id'];
            $createdBankIds[] = $bankId;

            $qRes = $pertanyaanModel->createPertanyaan([
                'kategori'        => $namaAwal,
                'teks_pertanyaan' => "[KAT-AKTIF-{$suffix}] Pertanyaan aktif untuk uji relasi kategori",
                'tipe_jawaban'    => 'TEKS',
                'is_wajib'        => '1',
                'status'          => 'AKTIF',
            ]);
            $this->assertTrue($qRes['success']);
            $qId = (int) $qRes['id'];
            $createdPertanyaanIds[] = $qId;

            $blockedDelete = $kategoriModel->deleteKategori($katId);
            $this->assertFalse($blockedDelete['success']);
            $this->assertTrue($blockedDelete['blockedByUsage']);

            // 5. Ubah nama kategori -> cascade ke Bank Pertanyaan dan Pertanyaan Aktif
            $namaBaru  = "Jejaring Alumni {$suffix}";
            $renameRes = $kategoriModel->updateKategori($katId, [
                'nama_kategori' => $namaBaru,
                'deskripsi'     => 'Deskripsi baru setelah rename',
            ]);
            $this->assertTrue($renameRes['success']);
            $this->assertSame(1, $renameRes['cascadeBankCount']);
            $this->assertSame(1, $renameRes['cascadeAktifCount']);

            $bankAfterRename  = $bankModel->find($bankId);
            $aktifAfterRename = $pertanyaanModel->find($qId);
            $this->assertSame($namaBaru, $bankAfterRename['kategori'] ?? null);
            $this->assertSame($namaBaru, $aktifAfterRename['kategori'] ?? null);

            // 6. Setelah pertanyaan dipindah ke kategori lain ("Status & Pekerjaan"), kategori kosong bisa dihapus
            $bankModel->updateBankPertanyaan($bankId, [
                'kategori'         => 'Status & Pekerjaan',
                'teks_pertanyaan'  => $bankAfterRename['teks_pertanyaan'],
                'tipe_jawaban'     => 'TEKS',
                'is_wajib_default' => '1',
            ]);
            $pertanyaanModel->updatePertanyaan($qId, [
                'kategori'        => 'Status & Pekerjaan',
                'teks_pertanyaan' => $aktifAfterRename['teks_pertanyaan'],
                'tipe_jawaban'    => 'TEKS',
                'is_wajib'        => '1',
                'status'          => 'AKTIF',
            ]);

            $deleteUnused = $kategoriModel->deleteKategori($katId);
            $this->assertTrue($deleteUnused['success']);
            $this->assertNull($kategoriModel->find($katId));
        } finally {
            foreach ($createdPertanyaanIds as $qid) {
                $db->table('pertanyaan_tracer_studi_awal')->where('id', $qid)->delete();
            }
            foreach ($createdBankIds as $bid) {
                $db->table('bank_pertanyaan_tracer_studi')->where('id', $bid)->delete();
            }
            foreach ($createdKategoriIds as $kid) {
                $db->table('kategori_tracer_studi')->where('id', $kid)->delete();
            }
        }
    }

    public function testKategoriTerjagaSaatPindahAntaraBankDanFormAktif(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $kategoriModel   = new KategoriTracerStudiModel();
        $bankModel       = new BankPertanyaanTracerStudiModel();
        $pertanyaanModel = new PertanyaanTracerStudiAwalModel();

        $createdKategoriIds   = [];
        $createdBankIds       = [];
        $createdPertanyaanIds = [];

        try {
            $suffix      = (string) random_int(1000, 9999);
            $katKhusus   = "Kompetensi Khusus {$suffix}";
            $katCreate   = $kategoriModel->createKategori(['nama_kategori' => $katKhusus], 'Tester');
            $this->assertTrue($katCreate['success']);
            $createdKategoriIds[] = (int) $katCreate['id'];

            // Buat item di Bank dengan kategori khusus -> aktifkan ke Form Aktif -> kategori harus ikut
            $bankCreate = $bankModel->createBankPertanyaan([
                'kategori'         => $katKhusus,
                'teks_pertanyaan'  => "[SYNC-KAT-{$suffix}] Pertanyaan uji sinkronisasi kategori aktif dan bank",
                'tipe_jawaban'     => 'TEKS',
                'is_wajib_default' => '1',
            ], 'Tester');
            $this->assertTrue($bankCreate['success']);
            $bankId = (int) $bankCreate['id'];
            $createdBankIds[] = $bankId;

            $fetched     = $bankModel->getByIds([$bankId]);
            $activateRes = $pertanyaanModel->activateFromBank($fetched, $bankModel);
            $this->assertTrue($activateRes['success']);
            $this->assertSame(1, $activateRes['importedCount']);
            $aktifId = (int) ($activateRes['importedIds'][0] ?? 0);
            $createdPertanyaanIds[] = $aktifId;

            $aktifRow = $pertanyaanModel->find($aktifId);
            $this->assertIsArray($aktifRow);
            $this->assertSame($katKhusus, $aktifRow['kategori'] ?? null);

            // Nonaktifkan kembali ke Bank -> kategori khusus harus tetap terjaga
            $moveRes = $pertanyaanModel->moveQuestionToBank($aktifId, $bankModel, 'Tester');
            $this->assertTrue($moveRes['success']);
            $movedBankId = (int) ($moveRes['bankId'] ?? 0);
            $this->assertGreaterThan(0, $movedBankId);
            $createdBankIds[] = $movedBankId;

            $bankBackRow = $bankModel->find($movedBankId);
            $this->assertIsArray($bankBackRow);
            $this->assertSame($katKhusus, $bankBackRow['kategori'] ?? null);
        } finally {
            foreach ($createdPertanyaanIds as $qid) {
                $db->table('pertanyaan_tracer_studi_awal')->where('id', $qid)->delete();
            }
            foreach ($createdBankIds as $bid) {
                $db->table('bank_pertanyaan_tracer_studi')->where('id', $bid)->delete();
            }
            foreach ($createdKategoriIds as $kid) {
                $db->table('kategori_tracer_studi')->where('id', $kid)->delete();
            }
        }
    }

    public function testSeedKategoriPertanyaanMenambahSembilanPertanyaanTanpaDuplikasi(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $beforeAktifIds = array_map(
            static fn (array $r): int => (int) ($r['id'] ?? 0),
            $db->table('pertanyaan_tracer_studi_awal')->select('id')->get()->getResultArray()
        );
        $beforeBankIds = array_map(
            static fn (array $r): int => (int) ($r['id'] ?? 0),
            $db->table('bank_pertanyaan_tracer_studi')->select('id')->get()->getResultArray()
        );

        require_once APPPATH . 'Database/Migrations/2026-11-24-030000_SeedKategoriPertanyaanTracerStudi.php';
        $forge     = \Config\Database::forge($db);
        $migration = new \App\Database\Migrations\SeedKategoriPertanyaanTracerStudi($forge);

        try {
            // Jalankan dua kali untuk memverifikasi sifat idempoten dan tanpa duplikasi
            $migration->up();
            $aktifCountFirst = $db->table('pertanyaan_tracer_studi_awal')->countAllResults();
            $bankCountFirst  = $db->table('bank_pertanyaan_tracer_studi')->countAllResults();

            $migration->up();
            $this->assertSame($aktifCountFirst, $db->table('pertanyaan_tracer_studi_awal')->countAllResults());
            $this->assertSame($bankCountFirst, $db->table('bank_pertanyaan_tracer_studi')->countAllResults());

            $kategoriModel = new KategoriTracerStudiModel($db);
            $usageRows     = $kategoriModel->getDaftarKategoriWithUsageCounts();
            $usageByName   = [];
            foreach ($usageRows as $row) {
                $usageByName[(string) ($row['nama_kategori'] ?? '')] = $row;
            }

            foreach (['Masa Tunggu & Gaji', 'Relevansi & Kompetensi', 'Status & Pekerjaan'] as $targetKategori) {
                $this->assertArrayHasKey($targetKategori, $usageByName);
                $this->assertGreaterThanOrEqual(2, (int) ($usageByName[$targetKategori]['jumlah_aktif'] ?? 0));
                $this->assertGreaterThanOrEqual(1, (int) ($usageByName[$targetKategori]['jumlah_bank'] ?? 0));
                $this->assertFalse($usageByName[$targetKategori]['can_delete']);
            }
        } finally {
            $afterAktifIds = array_map(
                static fn (array $r): int => (int) ($r['id'] ?? 0),
                $db->table('pertanyaan_tracer_studi_awal')->select('id')->get()->getResultArray()
            );
            $newAktifIds = array_values(array_diff($afterAktifIds, $beforeAktifIds));
            if ($newAktifIds !== []) {
                $db->table('pertanyaan_tracer_studi_awal')->whereIn('id', $newAktifIds)->delete();
                (new PertanyaanTracerStudiAwalModel($db))->normalizeUrutan();
            }

            $afterBankIds = array_map(
                static fn (array $r): int => (int) ($r['id'] ?? 0),
                $db->table('bank_pertanyaan_tracer_studi')->select('id')->get()->getResultArray()
            );
            $newBankIds = array_values(array_diff($afterBankIds, $beforeBankIds));
            if ($newBankIds !== []) {
                $db->table('bank_pertanyaan_tracer_studi')->whereIn('id', $newBankIds)->delete();
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

        require_once APPPATH . 'Database/Migrations/2026-11-24-010000_CreateKategoriTracerStudiTable.php';
        (new \App\Database\Migrations\CreateKategoriTracerStudiTable($forge))->up();

        require_once APPPATH . 'Database/Migrations/2026-11-24-020000_RemoveDefaultUmumKategoriTracerStudi.php';
        (new \App\Database\Migrations\RemoveDefaultUmumKategoriTracerStudi($forge))->up();
    }
}
