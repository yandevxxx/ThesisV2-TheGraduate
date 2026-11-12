<?php

namespace Tests\Unit;

use App\Controllers\TheGraduateWeb\TracerStudiController;
use App\Libraries\AccessControl;
use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite B-19a: Pratinjau form tracer studi awal (sisi The Graduate Web).
 *
 * @internal
 */
final class TracerStudiPratinjauTest extends CIUnitTestCase
{
    public function testRbacPratinjauHanyaUntukStafDanProdi(): void
    {
        $methods = [
            'pratinjau',
            'submitPratinjau',
            'pratinjauLanjut',
            'resetPratinjau',
        ];

        foreach (['staf', 'tendik', 'admin', 'prodi', 'kaprodi'] as $allowedRole) {
            foreach ($methods as $method) {
                $this->assertTrue(
                    AccessControl::canAccess('TracerStudiController', $method, [$allowedRole]),
                    "Role {$allowedRole} harus diizinkan mengakses {$method} (B-19a)."
                );
            }
            $this->assertTrue(
                AccessControl::canAccess('Graduate', 'pertanyaanTracerStudiPratinjau', [$allowedRole]),
                "Role {$allowedRole} harus diizinkan mengakses route pratinjau tracer studi."
            );
        }

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'wisudawan', 'fakultas'] as $deniedRole) {
            foreach ($methods as $method) {
                $this->assertFalse(
                    AccessControl::canAccess('TracerStudiController', $method, [$deniedRole]),
                    "Role {$deniedRole} tidak boleh mengakses mode pratinjau ({$method})."
                );
            }
        }
    }

    public function testPratinjauDanSimulasiPerpindahanHalamanTanpaSimpanJawaban(): void
    {
        $db = \Config\Database::connect();
        $this->ensureTables($db);

        $pertanyaanModel = new PertanyaanTracerStudiAwalModel();
        $createdIds      = [];

        try {
            $res1 = $pertanyaanModel->createPertanyaan([
                'teks_pertanyaan' => '[B19a-TEST] Pertanyaan Pilihan Wajib',
                'tipe_jawaban'    => 'PILIHAN_GANDA',
                'opsi_jawaban'    => "Opsi A\nOpsi B",
                'is_wajib'        => '1',
                'status'          => 'AKTIF',
            ]);
            $this->assertTrue($res1['success']);
            $qWajibId     = (int) $res1['id'];
            $createdIds[] = $qWajibId;

            $res2 = $pertanyaanModel->createPertanyaan([
                'teks_pertanyaan' => '[B19a-TEST] Pertanyaan Isian Opsional',
                'tipe_jawaban'    => 'ISIAN_SINGKAT',
                'is_wajib'        => '0',
                'status'          => 'AKTIF',
            ]);
            $this->assertTrue($res2['success']);
            $qOpsionalId  = (int) $res2['id'];
            $createdIds[] = $qOpsionalId;

            session()->set('user_session', [
                'id'    => 9991,
                'nama'  => 'Staf Uji B19a',
                'name'  => 'Staf Uji B19a',
                'email' => 'staf.b19a@example.test',
                'roles' => ['staf'],
            ]);

            $beforeCount = (int) $db->table('jawaban_tracer_studi_awal')->countAllResults();

            $controller = $this->initController('GET', '/graduate/pertanyaan_tracer_studi');
            $editHtml   = $controller->daftarPertanyaan();
            $this->assertStringContainsString('Pratinjau', (string) $editHtml);
            $this->assertStringContainsString('graduate/pertanyaan_tracer_studi/pratinjau', (string) $editHtml);

            $controller  = $this->initController('GET', '/graduate/pertanyaan_tracer_studi/pratinjau');
            $previewHtml = (string) $controller->pratinjau();
            $this->assertStringContainsString('Mode Pratinjau', $previewHtml);
            $this->assertSame(1, substr_count($previewHtml, 'Kembali ke Mode Edit'));
            $this->assertStringNotContainsString('Biodata Mahasiswa Masih Terkunci', $previewHtml);
            $this->assertStringNotContainsString('Pratinjau Form Staf / Prodi:', $previewHtml);
            $posFirst  = strpos($previewHtml, '[B19a-TEST] Pertanyaan Pilihan Wajib');
            $posSecond = strpos($previewHtml, '[B19a-TEST] Pertanyaan Isian Opsional');
            $this->assertNotFalse($posFirst);
            $this->assertNotFalse($posSecond);
            $this->assertLessThan($posSecond, $posFirst);

            // Submit valid simulation without answering optional question -> redirects without DB write
            $activeQuestions = $pertanyaanModel->getDaftarPertanyaan();
            $postAnswers     = [];
            foreach ($activeQuestions as $q) {
                $qid = (int) $q['id'];
                if ($qid === $qOpsionalId) {
                    continue;
                }
                if (! empty($q['is_wajib'])) {
                    $opsi              = PertanyaanTracerStudiAwalModel::parseOpsiJawaban($q['opsi_jawaban'] ?? null);
                    $postAnswers[$qid] = $opsi !== [] ? (string) $opsi[0] : 'Jawaban simulasi test';
                }
            }

            $controller     = $this->initController('POST', '/graduate/pertanyaan_tracer_studi/pratinjau', [
                'jawaban' => $postAnswers,
            ]);
            $submitResponse = $controller->submitPratinjau();
            $this->assertTrue(method_exists($submitResponse, 'getHeaderLine'));
            $this->assertStringContainsString('pertanyaan_tracer_studi/pratinjau/lanjut', $submitResponse->getHeaderLine('Location'));

            $afterCount = (int) $db->table('jawaban_tracer_studi_awal')->countAllResults();
            $this->assertSame($beforeCount, $afterCount, 'Mode pratinjau tidak boleh menyimpan jawaban ke tabel hasil survei.');

            $controller = $this->initController('GET', '/graduate/pertanyaan_tracer_studi/pratinjau/lanjut');
            $nextHtml   = (string) $controller->pratinjauLanjut();
            $this->assertStringContainsString('Simulasi Pengiriman Berhasil', $nextHtml);
            $this->assertStringContainsString('Kembali ke Form Pratinjau', $nextHtml);
            $this->assertStringContainsString('Kembali ke Mode Edit', $nextHtml);
            $this->assertStringContainsString('Opsional — tidak diisi', $nextHtml);
        } finally {
            session()->remove(['user_session', 'tracer_preview_draft']);
            if ($createdIds !== []) {
                $db->table('pertanyaan_tracer_studi_awal')->whereIn('id', $createdIds)->delete();
            }
        }
    }

    private function initController(string $method, string $path, array $post = []): TracerStudiController
    {
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_POST                     = $post;

        $config  = config('App');
        $request = new IncomingRequest($config, new URI('http://example.com' . $path), null, new UserAgent());
        if (strtoupper($method) === 'POST') {
            $request->setGlobal('post', $post);
        }

        $controller = new TracerStudiController();
        $controller->initController($request, Services::response(), Services::logger());

        return $controller;
    }

    private function ensureTables($db): void
    {
        $forge = \Config\Database::forge();

        if (! $db->tableExists('pertanyaan_tracer_studi_awal')) {
            $forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'urutan'          => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
                'teks_pertanyaan' => ['type' => 'TEXT'],
                'tipe_jawaban'    => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'PILIHAN_GANDA'],
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
