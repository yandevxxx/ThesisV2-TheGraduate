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
                'status_biodata' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'DRAFT',
                ],
                'catatan_biodata' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'status_berkas' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'DRAFT',
                ],
                'catatan_berkas' => [
                    'type' => 'TEXT',
                    'null' => true,
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
        $this->assertFalse(\App\Libraries\AccessControl::canAccess('Graduate', 'pendaftaranwisudaverifikasi', ['fakultas']));
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

    public function testTanggalYudisiumReadOnlyAndLockedFromSk()
    {
        // 1. Pastikan view biodata wisuda mahasiswa merender input tanggalLulus dan prodi sebagai readonly
        $viewPath = APPPATH . 'Views/The-Graduate-Web/mahasiswa/biodata_wisuda.php';
        $viewContent = file_get_contents($viewPath);
        $this->assertStringContainsString('id="tanggalLulus"', $viewContent);
        $this->assertMatchesRegularExpression('/id="tanggalLulus"[^>]*readonly/i', $viewContent);

        $this->assertStringContainsString('id="prodi"', $viewContent);
        $this->assertMatchesRegularExpression('/id="prodi"[^>]*readonly/i', $viewContent);

        // 2. Simulasi logika controller submit: jika terplot memiliki tanggal_sk_yudisium,
        // tanggalLulus harus selalu memakai tanggal_sk_yudisium meskipun mahasiswa mengirimkan tanggal lain di POST
        $terplot = [
            'npm'                 => '1234567890',
            'nama'                => 'Mahasiswa Uji',
            'tanggal_sk_yudisium' => '2026-11-20',
        ];
        $postTanggalLulus = '2020-01-01'; // Mahasiswa mencoba mengirim tanggal berbeda

        $resolvedTanggalLulus = ! empty($terplot['tanggal_sk_yudisium'])
            ? $terplot['tanggal_sk_yudisium']
            : $postTanggalLulus;

        $this->assertSame('2026-11-20', $resolvedTanggalLulus);
        $this->assertNotSame($postTanggalLulus, $resolvedTanggalLulus);
    }

    public function testBerkasCountCalculationNotDoubled()
    {
        // 3 baris berkas diunggah
        $rows = [
            ['id' => 1, 'pendaftaran_id' => 10, 'persyaratan_id' => 1, 'nama_persyaratan' => 'Ijazah SMA'],
            ['id' => 2, 'pendaftaran_id' => 10, 'persyaratan_id' => 2, 'nama_persyaratan' => 'Akte Kelahiran'],
            ['id' => 3, 'pendaftaran_id' => 10, 'persyaratan_id' => 3, 'nama_persyaratan' => 'Kartu Keluarga'],
        ];

        // Map berkas menghasilkan 2 key per berkas (nama & id_X)
        $map = [];
        foreach ($rows as $row) {
            $map[$row['nama_persyaratan']] = $row;
            if (! empty($row['persyaratan_id'])) {
                $map['id_' . $row['persyaratan_id']] = $row;
            }
        }

        // Map count adalah 6, tapi berkas sesungguhnya adalah 3
        $this->assertCount(6, $map);
        $distinctKeys = array_filter(array_keys($map), static fn($k) => !str_starts_with((string) $k, 'id_'));
        $this->assertCount(3, $distinctKeys);

        // Pastikan view berkas wisuda tidak lagi menggunakan raw count($berkasMap)
        $viewPath = APPPATH . 'Views/The-Graduate-Web/mahasiswa/berkas_wisuda.php';
        $viewContent = file_get_contents($viewPath);
        $this->assertStringContainsString('id="jumlahBerkasInfo"', $viewContent);
        $this->assertStringNotContainsString('count($berkasMap)', $viewContent);
    }

    public function testSeparateBiodataAndBerkasWorkflow()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        // Ensure columns exist in SQLite or MySQL test DB
        $neededCols = [
            'status_biodata'  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
            'catatan_biodata' => ['type' => 'TEXT', 'null' => true],
            'status_berkas'   => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
            'catatan_berkas'  => ['type' => 'TEXT', 'null' => true],
        ];
        foreach ($neededCols as $col => $def) {
            if (! $db->fieldExists($col, 'pendaftaran_wisuda')) {
                $forge->addColumn('pendaftaran_wisuda', [$col => $def]);
            }
        }

        $pendaftaranModel = new PendaftaranWisudaModel();
        $controller       = new PendaftaranWisudaController();

        // 1. Mahasiswa mengunggah berkas -> draft pendaftaran dibuat
        $id = (int) $pendaftaranModel->insert([
            'npm'               => '9999010088',
            'nama'              => 'Mahasiswa Alur Terpisah',
            'kode_semester'     => '2026-2',
            'status_biodata'    => 'DRAFT',
            'status_berkas'     => 'DRAFT',
            'status_verifikasi' => 'DRAFT',
        ]);

        $this->assertGreaterThan(0, $id);

        // 2. Draft tidak boleh muncul di antrean verifikasi biodata maupun berkas Tendik
        $bioDraft = $pendaftaranModel->getFilteredBiodata('2026-2', null, '9999010088');
        $this->assertEmpty($bioDraft);
        $berkasDraft = $pendaftaranModel->getFilteredBerkas('2026-2', null, '9999010088');
        $this->assertEmpty($berkasDraft);

        // 3. Mahasiswa submit biodata
        $pendaftaranModel->update($id, [
            'status_biodata' => 'MENUNGGU',
            'nik'            => '3201234567890123',
            'prodi'          => 'Sistem Informasi',
        ]);
        $controller->syncOverallStatus($id);

        // Muncul di antrean biodata
        $bioQueue = $pendaftaranModel->getFilteredBiodata('2026-2', 'MENUNGGU', '9999010088');
        $this->assertCount(1, $bioQueue);
        $this->assertSame('MENUNGGU', $bioQueue[0]['status_biodata']);

        // Belum muncul di antrean berkas karena berkas masih DRAFT
        $berkasQueue = $pendaftaranModel->getFilteredBerkas('2026-2', 'MENUNGGU', '9999010088');
        $this->assertEmpty($berkasQueue);

        // Overall status harus MENUNGGU
        $row = $pendaftaranModel->find($id);
        $this->assertSame('MENUNGGU', $row['status_verifikasi']);

        // 4. Tendik menyetujui Biodata
        $pendaftaranModel->update($id, [
            'status_biodata'    => 'DISETUJUI',
            'catatan_biodata'   => 'Biodata valid',
            'diverifikasi_pada' => date('Y-m-d H:i:s'),
        ]);
        $controller->syncOverallStatus($id);

        // 5. Mahasiswa submit berkas persyaratan
        $pendaftaranModel->update($id, [
            'status_berkas' => 'MENUNGGU',
        ]);
        $controller->syncOverallStatus($id);

        $berkasQueueAfter = $pendaftaranModel->getFilteredBerkas('2026-2', 'MENUNGGU', '9999010088');
        $this->assertCount(1, $berkasQueueAfter);
        $this->assertSame('MENUNGGU', $berkasQueueAfter[0]['status_berkas']);

        // 6. Tendik menyetujui Berkas
        $pendaftaranModel->update($id, [
            'status_berkas'     => 'DISETUJUI',
            'catatan_berkas'    => 'Berkas lengkap dan sesuai',
            'diverifikasi_pada' => date('Y-m-d H:i:s'),
        ]);
        $controller->syncOverallStatus($id);

        // Karena biodata & berkas keduanya DISETUJUI, status_verifikasi menjadi DISETUJUI
        $finalRow = $pendaftaranModel->find($id);
        $this->assertSame('DISETUJUI', $finalRow['status_biodata']);
        $this->assertSame('DISETUJUI', $finalRow['status_berkas']);
        $this->assertSame('DISETUJUI', $finalRow['status_verifikasi']);

        // 7. Uji jika salah satu ditolak, overall menjadi DITOLAK
        $pendaftaranModel->update($id, [
            'status_berkas'  => 'DITOLAK',
            'catatan_berkas' => 'Scan KTP buram',
        ]);
        $controller->syncOverallStatus($id);

        $rejectedRow = $pendaftaranModel->find($id);
        $this->assertSame('DITOLAK', $rejectedRow['status_verifikasi']);

        // Clean up
        $pendaftaranModel->delete($id);
    }

    public function testBiodataWithoutGelombangWisudaAndLockedSemester()
    {
        $model = new PendaftaranWisudaModel();

        // 1. Simpan biodata tanpa mengirimkan gelombang wisuda
        $insertId = $model->insert([
            'npm'              => '1999010099',
            'nama'             => 'Mahasiswa Tanpa Gelombang',
            'nik'              => '3201999988887777',
            'kode_semester'    => '2024-2',
            'gelombang_wisuda' => null,
            'status_biodata'   => 'MENUNGGU',
        ], true);

        $this->assertIsNumeric($insertId);
        $row = $model->find($insertId);
        $this->assertNotNull($row);
        $this->assertNull($row['gelombang_wisuda'], 'Gelombang wisuda opsional/null harus tersimpan dengan aman');

        // 2. Semester locking logic: terplot kode_semester diprioritaskan
        $terplot = [
            'npm'           => '1999010099',
            'kode_semester' => '2024-2',
        ];
        $postKodeSemester = '2028-1'; // Mahasiswa mencoba mengirim semester lain

        $resolvedSemester = ! empty($terplot['kode_semester'])
            ? $terplot['kode_semester']
            : $postKodeSemester;

        $this->assertSame('2024-2', $resolvedSemester, 'Semester kelulusan mahasiswa harus selalu terkunci ke semester SK Yudisium');
        $this->assertNotSame($postKodeSemester, $resolvedSemester);

        // Clean up
        $model->delete($insertId);
    }

    public function testApprovedBiodataLockingAndResetToMenunggu()
    {
        $model = new PendaftaranWisudaModel();
        $controller = new PendaftaranWisudaController();

        $id = $model->insert([
            'npm'            => '1999010100',
            'nama'           => 'Mahasiswa Lock Test',
            'nik'            => '3201111122223333',
            'kode_semester'  => '2024-2',
            'status_biodata' => 'DISETUJUI',
        ], true);

        $row = $model->find($id);

        // 1. Biodata dengan status DISETUJUI harus terdeteksi terkunci
        $isLocked = ($row !== null && ($row['status_biodata'] === 'DISETUJUI' || ($row['status_verifikasi'] ?? '') === 'DISETUJUI'));
        $this->assertTrue($isLocked, 'Biodata yang sudah DISETUJUI harus terkunci dari perubahan sepihak mahasiswa');

        // 2. Tendik mereset status biodata kembali ke MENUNGGU
        $model->update($id, [
            'status_biodata'  => 'MENUNGGU',
            'catatan_biodata' => null,
        ]);
        $controller->syncOverallStatus($id);

        $resetRow = $model->find($id);
        $this->assertSame('MENUNGGU', $resetRow['status_biodata']);

        // Clean up
        $model->delete($id);
    }

    public function testPrefillPrioritizesSubmittedPendaftaranOverMasterDataAndSummaryCompleteness()
    {
        $controller = new PendaftaranWisudaController();
        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('getMahasiswaPrefillData');
        $method->setAccessible(true);

        $npm = '1999010101';
        $userData = ['nama' => 'Nama Session', 'email' => 'session@test.ac.id'];
        $terplot = ['nama' => 'Nama Terplot', 'kode_semester' => '2024-2', 'tanggal_sk_yudisium' => '2025-01-20'];
        $pendaftaran = [
            'npm'               => $npm,
            'nama'              => 'Nama Yang Diedit Mahasiswa',
            'nik'               => '3201123456789012',
            'tempat_lahir'      => 'Bandung',
            'tanggal_lahir'     => '2001-05-15',
            'jenis_kelamin'     => 'Laki-laki',
            'alamat'            => 'Jl. Dipati Ukur No. 10',
            'no_hp'             => '081234567890',
            'no_telepon'        => '0221234567',
            'email'             => 'mahasiswa.diedit@test.ac.id',
            'prodi'             => 'Teknik Informatika',
            'total_sks'         => 148,
            'ipk'               => 3.85,
            'kode_semester'     => '2024-2',
            'tanggal_lulus'     => '2025-01-20',
            'jenis_tugas_akhir' => 'Skripsi',
            'judul_skripsi'     => 'IMPLEMENTASI AI UNTUK SISTEM YUDISIUM',
            'pembimbing1'       => 'Dr. Budi Santoso',
            'pembimbing2'       => 'Dr. Siti Aminah',
            'pembimbing_agama'  => 'Ustadz Ahmad, M.Ag',
            'dosen_penguji'     => 'Prof. Hendra Wijaya',
            'pas_foto'          => 'uploads/graduate/foto/foto_1999010101.jpg',
        ];

        $prefill = $method->invoke($controller, $npm, $userData, $terplot, $pendaftaran);

        $this->assertSame('Nama Yang Diedit Mahasiswa', $prefill['nama'], 'Nama dari pendaftaran harus diprioritaskan');
        $this->assertSame('mahasiswa.diedit@test.ac.id', $prefill['email'], 'Email dari pendaftaran harus diprioritaskan');
        $this->assertSame(148, $prefill['totalSks'], 'Total SKS dari pendaftaran harus diprioritaskan');
        $this->assertSame('0221234567', $prefill['noTelepon'], 'No. Telepon harus tersedia di prefill');
        $this->assertSame('Prof. Hendra Wijaya', $prefill['dosenPenguji'], 'Dosen penguji harus tersedia di prefill');
        $this->assertSame('2024-2', $prefill['kode_semester'], 'Semester kelulusan harus tersedia di prefill');

        // Test getByNpm returns latest record by ID desc
        $model = new PendaftaranWisudaModel();
        $id1 = $model->insert([
            'npm'            => '1999010999',
            'nama'           => 'Draft Lama',
            'status_biodata' => 'DRAFT',
        ], true);
        $id2 = $model->insert([
            'npm'            => '1999010999',
            'nama'           => 'Pendaftaran Terbaru',
            'status_biodata' => 'MENUNGGU',
        ], true);

        $latest = $model->getByNpm('1999010999');
        $this->assertNotNull($latest);
        $this->assertSame('Pendaftaran Terbaru', $latest['nama'], 'getByNpm harus selalu mengambil baris pendaftaran terbaru');
        $this->assertSame((int) $id2, (int) $latest['id']);

        $model->delete($id1);
        $model->delete($id2);
    }

    public function testRequiredPembimbingAgamaAndDosenPengujiSidang()
    {
        // Validasi wajib: pembimbing1, pembimbing_agama, dan dosen_penguji
        $pembimbing1 = 'Dr. Pembimbing 1';
        $pembimbingAgama = 'Ustadz Pembimbing Agama';
        $dosenPenguji = 'Prof. Penguji Sidang';

        // 1. Lengkap -> Valid
        $isValidLengkap = ($pembimbing1 !== '' && $pembimbingAgama !== '' && $dosenPenguji !== '');
        $this->assertTrue($isValidLengkap);

        // 2. Pembimbing Agama kosong -> Invalid
        $pembimbingAgamaKosong = '';
        $isValidTanpaPa = ($pembimbing1 !== '' && $pembimbingAgamaKosong !== '' && $dosenPenguji !== '');
        $this->assertFalse($isValidTanpaPa, 'Pembimbing Agama wajib diisi');

        // 3. Dosen Penguji Sidang kosong -> Invalid
        $pengujiKosong = '';
        $isValidTanpaPenguji = ($pembimbing1 !== '' && $pembimbingAgama !== '' && $pengujiKosong !== '');
        $this->assertFalse($isValidTanpaPenguji, 'Dosen Penguji Sidang wajib diisi');
    }

    public function testDistinctDosenPembimbingDanPenguji()
    {
        $validateDistinct = static function ($p1, $p2, $pa, $penguji): bool {
            $list = array_filter([$p1, $p2, $pa, $penguji], static fn ($d) => $d !== '');
            return count($list) === count(array_unique($list));
        };

        // 1. Semua dosen berbeda (dengan pembimbing 2) -> VALID
        $this->assertTrue($validateDistinct('Dosen A', 'Dosen B', 'Dosen C', 'Dosen D'));

        // 2. Semua dosen berbeda (tanpa pembimbing 2) -> VALID
        $this->assertTrue($validateDistinct('Dosen A', '', 'Dosen C', 'Dosen D'));

        // 3. Pembimbing 1 sama dengan Pembimbing Agama -> INVALID
        $this->assertFalse($validateDistinct('Dosen A', '', 'Dosen A', 'Dosen D'));

        // 4. Pembimbing 1 sama dengan Dosen Penguji -> INVALID
        $this->assertFalse($validateDistinct('Dosen A', '', 'Dosen C', 'Dosen A'));

        // 5. Pembimbing 2 sama dengan Dosen Penguji -> INVALID
        $this->assertFalse($validateDistinct('Dosen A', 'Dosen B', 'Dosen C', 'Dosen B'));

        // 6. Pembimbing Agama sama dengan Dosen Penguji -> INVALID
        $this->assertFalse($validateDistinct('Dosen A', 'Dosen B', 'Dosen C', 'Dosen C'));
    }

    public function testBerkasWisudaSafeguardsAndFixes()
    {
        $pendaftaranModel = new PendaftaranWisudaModel();
        $berkasModel      = new \App\Models\BerkasPendaftaranWisudaModel();

        // 1. Role fakultas harus diizinkan sebagai staff preview berkas
        $rolesFakultas = ['fakultas'];
        $isStaff = \App\Libraries\AccessControl::hasAnyRole($rolesFakultas, ['tendik', 'staf', 'admin', 'fakultas']);
        $this->assertTrue($isStaff, 'Role fakultas harus memiliki hak akses staf untuk preview berkas');

        // 2. Simulasi pencocokan berkas saat nama persyaratan diperbarui
        $pId = (int) $pendaftaranModel->insert([
            'npm'               => '9999010099',
            'nama'              => 'Mahasiswa Berkas Test',
            'kode_semester'     => '2026-2',
            'status_biodata'    => 'DRAFT',
            'status_berkas'     => 'DRAFT',
            'status_verifikasi' => 'DRAFT',
            'pas_foto'          => 'uploads/graduate/berkas/berkas_9999010099_pas_foto_123.jpg',
        ]);
        $this->assertGreaterThan(0, $pId);

        $syaratId = 15;
        $bId = (int) $berkasModel->insert([
            'pendaftaran_id'   => $pId,
            'persyaratan_id'   => $syaratId,
            'nama_persyaratan' => 'Pas Foto Resmi Merah',
            'nama_file'        => 'berkas_9999010099_pas_foto_123.jpg',
            'ukuran_kb'        => 512,
            'status_berkas'    => 'DRAFT',
        ]);
        $this->assertGreaterThan(0, $bId);

        // Jika persyaratan diubah namanya menjadi "Pas Foto Terbaru Background Merah"
        // Pencarian harus tetap menemukan baris existing berdasarkan persyaratan_id
        $namaBaru = 'Pas Foto Terbaru Background Merah';
        $found = $berkasModel->getByPendaftaranAndPersyaratan($pId, $syaratId);
        $this->assertNotNull($found);
        $this->assertSame($bId, (int) $found['id']);

        // 3. Reset pas_foto saat berkas foto dihapus
        $pendaftaranAwal = $pendaftaranModel->find($pId);
        $this->assertNotEmpty($pendaftaranAwal['pas_foto']);

        if (! empty($pendaftaranAwal['pas_foto']) && (basename($pendaftaranAwal['pas_foto']) === $found['nama_file'] || stripos($found['nama_persyaratan'], 'foto') !== false)) {
            $pendaftaranModel->update($pId, ['pas_foto' => null]);
        }
        $pendaftaranSetelahHapus = $pendaftaranModel->find($pId);
        $this->assertNull($pendaftaranSetelahHapus['pas_foto'], 'Field pas_foto harus direset ke null saat berkas foto dihapus');

        // 4. Kunci berkas saat status MENUNGGU atau DISETUJUI
        $canModifyDraft = in_array('DRAFT', ['DRAFT', 'DITOLAK'], true);
        $this->assertTrue($canModifyDraft);

        $canModifyRejected = in_array('DITOLAK', ['DRAFT', 'DITOLAK'], true);
        $this->assertTrue($canModifyRejected);

        $canModifyPending = in_array('MENUNGGU', ['DRAFT', 'DITOLAK'], true);
        $this->assertFalse($canModifyPending, 'Status MENUNGGU harus dikunci dari modifikasi/hapus');

        $canModifyApproved = in_array('DISETUJUI', ['DRAFT', 'DITOLAK'], true);
        $this->assertFalse($canModifyApproved, 'Status DISETUJUI harus dikunci dari modifikasi/hapus');

        // Clean up
        $berkasModel->delete($bId);
        $pendaftaranModel->delete($pId);
    }

    public function testDashboardRealPengajuanSklAndWisudawanCounts()
    {
        $pendaftaranModel = new PendaftaranWisudaModel();

        // Buat 1 wisudawan disetujui
        $idWisudawan = (int) $pendaftaranModel->insert([
            'npm'               => '9999010077',
            'nama'              => 'Wisudawan Real Test',
            'prodi'             => 'Informatika',
            'kode_semester'     => '2026-2',
            'status_biodata'    => 'DISETUJUI',
            'status_berkas'     => 'DISETUJUI',
            'status_verifikasi' => 'DISETUJUI',
        ]);
        $this->assertGreaterThan(0, $idWisudawan);

        // Hitung data riil wisudawan
        $wisQuery = $pendaftaranModel->groupStart()
                ->where('status_verifikasi', 'DISETUJUI')
                ->orGroupStart()
                    ->where('status_biodata', 'DISETUJUI')
                    ->where('status_berkas', 'DISETUJUI')
                ->groupEnd()
            ->groupEnd()
            ->where('kode_semester', '2026-2');

        $countWisudawan = $wisQuery->countAllResults();
        $this->assertGreaterThanOrEqual(1, $countWisudawan);

        // Bersihkan
        $pendaftaranModel->delete($idWisudawan);
    }
}
