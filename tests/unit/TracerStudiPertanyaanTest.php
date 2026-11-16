<?php

namespace Tests\Unit;

use App\Controllers\TheGraduateWeb\TracerStudiController;
use App\Libraries\AccessControl;
use App\Models\JawabanTracerStudiAwalModel;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite for B-10: Lihat daftar pertanyaan tracer studi awal (sisi The Graduate Web).
 *
 * @internal
 */
final class TracerStudiPertanyaanTest extends CIUnitTestCase
{
    public function testStafAndProdiCanAccessPertanyaanTracerStudiAwal()
    {
        // AC 1: Staf, Tendik, Fakultas, dan Prodi dapat melihat daftar pertanyaan form tracer studi awal.
        foreach (['staf', 'tendik', 'admin', 'fakultas', 'prodi', 'kaprodi'] as $role) {
            $this->assertTrue(
                AccessControl::canAccess('Graduate', 'pertanyaan_tracer_studi', [$role]),
                "Role {$role} harus diizinkan mengakses Graduate::pertanyaan_tracer_studi."
            );
            $this->assertTrue(
                AccessControl::canAccess('TracerStudiController', 'daftarPertanyaan', [$role]),
                "Role {$role} harus diizinkan mengakses TracerStudiController::daftarPertanyaan."
            );
            $this->assertTrue(
                AccessControl::menuVisible('pertanyaan_tracer_studi', [$role]),
                "Menu pertanyaan tracer studi harus terlihat untuk role {$role}."
            );
            $this->assertTrue(
                TracerStudiController::canViewPertanyaan([$role]),
                "Controller guard harus mengizinkan role {$role}."
            );
        }
    }

    public function testMahasiswaDosenAndOtherRolesAreRejected()
    {
        // AC 4: Peran lain (Mahasiswa dan Dosen) yang membuka daftar ini ditolak.
        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'wisudawan', 'klien', 'tamu'] as $role) {
            $this->assertFalse(
                AccessControl::canAccess('Graduate', 'pertanyaan_tracer_studi', [$role]),
                "Role {$role} harus ditolak mengakses Graduate::pertanyaan_tracer_studi."
            );
            $this->assertFalse(
                AccessControl::canAccess('TracerStudiController', 'daftarPertanyaan', [$role]),
                "Role {$role} harus ditolak mengakses TracerStudiController::daftarPertanyaan."
            );
            $this->assertFalse(
                AccessControl::menuVisible('pertanyaan_tracer_studi', [$role]),
                "Menu pertanyaan tracer studi tidak boleh terlihat untuk role {$role}."
            );
            $this->assertFalse(
                TracerStudiController::canViewPertanyaan([$role]),
                "Controller guard harus menolak role {$role}."
            );
        }

        // Pastikan B-08b (akses mahasiswa ke form tracer_studi) tidak berubah.
        $this->assertTrue(AccessControl::canAccess('Graduate', 'tracer_studi', ['mahasiswa']));
    }

    public function testDefaultQuestionsContainTextAndSequentialOrder()
    {
        // AC 2: Daftar memuat teks pertanyaan dan urutannya.
        $questions = PertanyaanTracerStudiAwalModel::defaultQuestions();
        $this->assertNotEmpty($questions);

        $expectedOrder = 1;
        foreach ($questions as $question) {
            $this->assertArrayHasKey('teks_pertanyaan', $question);
            $this->assertArrayHasKey('urutan', $question);
            $this->assertNotSame('', trim((string) $question['teks_pertanyaan']));
            $this->assertSame($expectedOrder, (int) $question['urutan']);
            $expectedOrder++;
        }
    }

    public function testQuestionSchemaIsUniformAcrossAllProdi()
    {
        // AC 3: Daftar pertanyaan sama untuk semua prodi (tidak ada pemisahan per id_prodi).
        $model = new PertanyaanTracerStudiAwalModel();
        $this->assertSame('pertanyaan_tracer_studi_awal', $model->getTable());
        $this->assertNotContains('id_prodi', $model->allowedFields);
        $this->assertNotContains('kode_prodi', $model->allowedFields);
    }

    public function testDatabaseQueryReturnsUniformOrderedActiveQuestions()
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('pertanyaan_tracer_studi_awal')) {
            $forge = \Config\Database::forge();
            $forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'teks_pertanyaan' => ['type' => 'TEXT', 'null' => false],
                'urutan'          => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
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

        $model       = new PertanyaanTracerStudiAwalModel();
        $insertedIds = [];

        try {
            $idKedua = $model->insert([
                'teks_pertanyaan' => '[TEST] Pertanyaan urutan 902?',
                'urutan'          => 902,
                'tipe_jawaban'    => 'TEKS',
                'opsi_jawaban'    => null,
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ], true);
            $insertedIds[] = $idKedua;

            $idPertama = $model->insert([
                'teks_pertanyaan' => '[TEST] Pertanyaan urutan 901?',
                'urutan'          => 901,
                'tipe_jawaban'    => 'PILIHAN_GANDA',
                'opsi_jawaban'    => json_encode(['Ya', 'Tidak']),
                'is_wajib'        => 1,
                'status'          => 'AKTIF',
            ], true);
            $insertedIds[] = $idPertama;

            $idNonaktif = $model->insert([
                'teks_pertanyaan' => '[TEST] Pertanyaan nonaktif?',
                'urutan'          => 903,
                'tipe_jawaban'    => 'TEKS',
                'opsi_jawaban'    => null,
                'is_wajib'        => 0,
                'status'          => 'NON-AKTIF',
            ], true);
            $insertedIds[] = $idNonaktif;

            $list = $model->getDaftarPertanyaan();
            $ids  = array_map('intval', array_column($list, 'id'));

            $posPertama = array_search((int) $idPertama, $ids, true);
            $posKedua   = array_search((int) $idKedua, $ids, true);

            $this->assertNotFalse($posPertama);
            $this->assertNotFalse($posKedua);
            $this->assertLessThan($posKedua, $posPertama, 'Pertanyaan urutan 901 harus tampil sebelum urutan 902.');
            $this->assertNotContains((int) $idNonaktif, $ids, 'Pertanyaan NON-AKTIF tidak boleh tampil pada daftar aktif.');
        } finally {
            foreach ($insertedIds as $id) {
                $model->delete($id);
            }
        }
    }

    public function testRbacTambahPertanyaanHanyaUntukStafDanProdi(): void
    {
        foreach (['staf', 'tendik', 'admin', 'fakultas', 'prodi', 'kaprodi'] as $role) {
            $this->assertTrue(
                AccessControl::canAccess('TracerStudiController', 'storePertanyaan', [$role]),
                "Role {$role} harus diizinkan menambah pertanyaan tracer studi awal (B-11)."
            );
            $this->assertTrue(
                AccessControl::canAccess('Graduate', 'pertanyaanTracerStudiStore', [$role]),
                "Role {$role} harus diizinkan mengakses endpoint store B-11."
            );
        }

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'wisudawan'] as $deniedRole) {
            $this->assertFalse(
                AccessControl::canAccess('TracerStudiController', 'storePertanyaan', [$deniedRole]),
                "Role {$deniedRole} harus ditolak menambah pertanyaan tracer studi awal (B-11)."
            );
        }
    }

    public function testValidasiTambahPertanyaanMenolakTeksKosongDanTipeTidakValid(): void
    {
        // Pertanyaan tanpa teks wajib ditolak dengan pesan error
        $emptyTextResult = PertanyaanTracerStudiAwalModel::validateNewPertanyaan([
            'teks_pertanyaan' => '   ',
            'tipe_jawaban'    => 'TEKS',
        ]);
        $this->assertNotEmpty($emptyTextResult['errors']);
        $this->assertStringContainsString('Teks pertanyaan wajib diisi', implode(' ', $emptyTextResult['errors']));

        // Pilihan ganda tanpa minimal 2 opsi ditolak
        $shortChoiceResult = PertanyaanTracerStudiAwalModel::validateNewPertanyaan([
            'teks_pertanyaan' => 'Status kerja saat ini?',
            'tipe_jawaban'    => 'PILIHAN_GANDA',
            'opsi_jawaban'    => 'Hanya satu opsi',
        ]);
        $this->assertNotEmpty($shortChoiceResult['errors']);

        // Validasi sukses untuk pilihan ganda dengan pemisah baris baru
        $validChoiceResult = PertanyaanTracerStudiAwalModel::validateNewPertanyaan([
            'teks_pertanyaan' => 'Apakah pekerjaan sesuai bidang ilmu?',
            'tipe_jawaban'    => 'PILIHAN_GANDA',
            'opsi_jawaban'    => "Ya, sesuai\nKurang sesuai",
            'is_wajib'        => '1',
            'status'          => 'AKTIF',
        ]);
        $this->assertSame([], $validChoiceResult['errors']);
        $this->assertSame('PILIHAN_GANDA', $validChoiceResult['data']['tipe_jawaban']);
        $this->assertSame(['Ya, sesuai', 'Kurang sesuai'], PertanyaanTracerStudiAwalModel::parseOpsiJawaban($validChoiceResult['data']['opsi_jawaban']));
    }

    public function testCreatePertanyaanSimpanTampilDiDaftarDanFormSesuaiStatus(): void
    {
        $model = new PertanyaanTracerStudiAwalModel();
        $db    = \Config\Database::connect();

        if (! $db->tableExists('pertanyaan_tracer_studi_awal')) {
            $forge = \Config\Database::forge();
            $forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'urutan'          => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
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

        $createdIds = [];
        try {
            $aktifRes = $model->createPertanyaan([
                'teks_pertanyaan' => '[B11-TEST] Berapa bulan waktu tunggu kerja pertama?',
                'tipe_jawaban'    => 'ISIAN_SINGKAT',
                'is_wajib'        => '1',
                'status'          => 'AKTIF',
            ]);
            $this->assertTrue($aktifRes['success']);
            $createdIds[] = (int) $aktifRes['id'];

            $nonAktifRes = $model->createPertanyaan([
                'teks_pertanyaan' => '[B11-TEST] Pertanyaan disembunyikan B-12',
                'tipe_jawaban'    => 'TEKS',
                'is_wajib'        => '0',
                'status'          => 'NON-AKTIF',
            ]);
            $this->assertTrue($nonAktifRes['success']);
            $createdIds[] = (int) $nonAktifRes['id'];

            // Daftar manajemen B-10 memuat keduanya
            $allIds = array_map('intval', array_column($model->getDaftarManajemenPertanyaan(), 'id'));
            $this->assertContains((int) $aktifRes['id'], $allIds);
            $this->assertContains((int) $nonAktifRes['id'], $allIds);

            // Daftar aktif / form mahasiswa hanya menampilkan yang AKTIF
            $activeIds = array_map('intval', array_column($model->getDaftarPertanyaan(), 'id'));
            $this->assertContains((int) $aktifRes['id'], $activeIds);
            $this->assertNotContains((int) $nonAktifRes['id'], $activeIds);
        } finally {
            foreach ($createdIds as $id) {
                $model->delete($id);
            }
        }
    }

    public function testRbacAturTampilPertanyaanB12(): void
    {
        foreach (['staf', 'tendik', 'admin', 'fakultas', 'prodi', 'kaprodi'] as $role) {
            $this->assertTrue(
                AccessControl::canAccess('TracerStudiController', 'simpanTampilPertanyaan', [$role]),
                "Role {$role} harus diizinkan mengatur tampilan pertanyaan (B-12)."
            );
            $this->assertTrue(
                AccessControl::canAccess('Graduate', 'pertanyaanTracerStudiTampil', [$role]),
                "Role {$role} harus diizinkan mengakses endpoint tampil B-12."
            );
        }

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'wisudawan'] as $deniedRole) {
            $this->assertFalse(
                AccessControl::canAccess('TracerStudiController', 'simpanTampilPertanyaan', [$deniedRole]),
                "Role {$deniedRole} harus ditolak mengatur tampilan pertanyaan (B-12)."
            );
        }
    }

    public function testUpdateTampilPertanyaanDanJawabanTersimpanTetapUtuh(): void
    {
        $model        = new PertanyaanTracerStudiAwalModel();
        $jawabanModel = new JawabanTracerStudiAwalModel();
        $db           = \Config\Database::connect();

        if (! $db->tableExists('pertanyaan_tracer_studi_awal')) {
            $forge = \Config\Database::forge();
            $forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'urutan'          => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
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
            $forge = \Config\Database::forge();
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

        $originalRows = $model->getDaftarManajemenPertanyaan();
        $originalStatusMap = [];
        foreach ($originalRows as $orig) {
            $originalStatusMap[(int) $orig['id']] = (string) ($orig['status'] ?? 'AKTIF');
        }

        $createdIds = [];
        $npm        = 'B12TEST' . random_int(1000, 9999);

        try {
            $qTampil = $model->createPertanyaan([
                'teks_pertanyaan' => '[B12-TEST] Pertanyaan tampil',
                'tipe_jawaban'    => 'TEKS',
                'is_wajib'        => '1',
                'status'          => 'AKTIF',
            ]);
            $qSembunyi = $model->createPertanyaan([
                'teks_pertanyaan' => '[B12-TEST] Pertanyaan disembunyikan',
                'tipe_jawaban'    => 'TEKS',
                'is_wajib'        => '1',
                'status'          => 'AKTIF',
            ]);
            $this->assertTrue($qTampil['success']);
            $this->assertTrue($qSembunyi['success']);
            $idTampil   = (int) $qTampil['id'];
            $idSembunyi = (int) $qSembunyi['id'];
            $createdIds = [$idTampil, $idSembunyi];

            // Mahasiswa mengisi sebelum pengaturan diubah
            $jawabanModel->saveJawabanMahasiswa(
                $npm,
                [
                    ['id' => $idTampil, 'urutan' => 991, 'teks_pertanyaan' => '[B12-TEST] Pertanyaan tampil'],
                    ['id' => $idSembunyi, 'urutan' => 992, 'teks_pertanyaan' => '[B12-TEST] Pertanyaan disembunyikan'],
                ],
                [
                    $idTampil   => 'Jawaban tampil awal',
                    $idSembunyi => 'Jawaban lama tetap utuh',
                ],
                '2026-2'
            );

            // Menolak simpan kosong (0 dipilih)
            $rejectEmpty = $model->updateTampilPertanyaan([]);
            $this->assertFalse($rejectEmpty['success']);

            // Staf memilih hanya $idTampil untuk tampil ($idSembunyi tidak dipilih)
            $activePool = array_keys(array_filter($originalStatusMap, static fn (string $st): bool => $st === 'AKTIF'));
            $selected   = array_merge($activePool, [$idTampil]);
            $updateRes  = $model->updateTampilPertanyaan($selected);
            $this->assertTrue($updateRes['success']);

            // Pertanyaan tidak terpilih hilang dari form mahasiswa, yang terpilih tampil
            $activeIds = array_map('intval', array_column($model->getDaftarPertanyaan(), 'id'));
            $this->assertContains($idTampil, $activeIds);
            $this->assertNotContains($idSembunyi, $activeIds);

            // Jawaban yang sudah tersimpan tetap utuh walaupun pertanyaan disembunyikan atau mahasiswa edit ulang
            $jawabanModel->saveJawabanMahasiswa(
                $npm,
                [['id' => $idTampil, 'urutan' => 991, 'teks_pertanyaan' => '[B12-TEST] Pertanyaan tampil']],
                [$idTampil => 'Jawaban tampil diperbarui'],
                '2026-2'
            );
            $savedMap = $jawabanModel->getMapByNpm($npm);
            $this->assertSame('Jawaban tampil diperbarui', $savedMap[$idTampil]['jawaban'] ?? null);
            $this->assertSame('Jawaban lama tetap utuh', $savedMap[$idSembunyi]['jawaban'] ?? null);
        } finally {
            $db->table('jawaban_tracer_studi_awal')->where('npm', $npm)->delete();
            foreach ($createdIds as $id) {
                $model->delete($id);
            }
            foreach ($originalStatusMap as $origId => $origStatus) {
                $model->update($origId, ['status' => $origStatus]);
            }
        }
    }
}
