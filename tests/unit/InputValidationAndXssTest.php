<?php

namespace Tests\Unit;

use App\Controllers\TheGraduateWeb\SkYudisiumController;
use App\Libraries\GraduateInputValidator;
use App\Models\MahasiswaTerplotModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * Pengujian Validasi Input (Allowlist) dan Output Encoding (Anti-XSS)
 * untuk Tugas 2 Mode A pada modul The Graduate.
 *
 * @internal
 */
final class InputValidationAndXssTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = \Config\Database::connect();
        if (! $db->tableExists('mahasiswa_terplot_sk_yudisium')) {
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
                'id_prodi' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'TERPLOT',
                ],
                'tanggal_sk_yudisium' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'kode_semester' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
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
            $forge->createTable('mahasiswa_terplot_sk_yudisium', true);
        }

        $db->table('mahasiswa_terplot_sk_yudisium')->where('npm', '1402024054')->delete();
        $db->table('mahasiswa_terplot_sk_yudisium')->insert([
            'npm'                 => '1402024054',
            'nama'                => 'Zild Jian Test',
            'id_prodi'            => 1,
            'status'              => 'TERPLOT',
            'tanggal_sk_yudisium' => '2026-06-01',
            'kode_semester'       => '2026-2',
        ]);
    }

    public function testNpmContainingSqlPayloadIsRejectedWith422AndDoesNotTouchQuery(): void
    {
        $maliciousNpm = "1402024054' OR '1'='1";

        // 1. Validator allowlist menolak NPM berisi karakter SQL
        $npmCheck = GraduateInputValidator::validateNpm($maliciousNpm);
        $this->assertFalse($npmCheck['valid']);
        $this->assertNotEmpty($npmCheck['error']);

        // 2. Validasi model MahasiswaTerplotModel menolak payload SQL
        $model = new MahasiswaTerplotModel();
        $this->assertFalse($model->validate([
            'npm'    => $maliciousNpm,
            'nama'   => 'Zild Jian Test',
            'status' => 'TERPLOT',
        ]));

        // 3. Endpoint controller mengembalikan HTTP 422 JSON tanpa mengubah tabel DB
        session()->set('user_session', [
            'id'      => 99,
            'akun_id' => 99,
            'nama'    => 'Admin Tendik',
            'email'   => 'tendik@yarsi.ac.id',
            'roles'   => ['tendik'],
        ]);

        $config  = new App();
        $request = new IncomingRequest(
            $config,
            new URI('http://localhost/graduate/sk_yudisium/set_tanggal'),
            null,
            new UserAgent()
        );
        $request->setMethod('post');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setHeader('Accept', 'application/json');
        $request->setGlobal('post', [
            'npm'                 => $maliciousNpm,
            'tanggal_sk_yudisium' => '2026-12-31',
        ]);

        $controller = new SkYudisiumController();
        $controller->initController($request, service('response'), service('logger'));
        $response = $controller->setTanggal();

        $this->assertSame(422, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertSame(422, $payload['status'] ?? null);

        // Pesan validasi harus ramah pengguna dan bebas dari bocoran SQL / stack trace
        $msg = (string) ($payload['message'] ?? '');
        $this->assertStringNotContainsStringIgnoringCase('SQLSTATE', $msg);
        $this->assertStringNotContainsStringIgnoringCase('SELECT ', $msg);
        $this->assertStringNotContainsStringIgnoringCase('UPDATE ', $msg);
        $this->assertStringNotContainsStringIgnoringCase('Stack trace', $msg);

        // Data asli di database tidak berubah oleh payload injeksi
        $row = (new MahasiswaTerplotModel())->where('npm', '1402024054')->first();
        $this->assertIsArray($row);
        $this->assertSame('2026-06-01', $row['tanggal_sk_yudisium']);
    }

    public function testAllowlistRejectsInvalidNikEmailPhoneIpkSks(): void
    {
        // NIK harus 16 digit tepat; karakter non-angka tidak boleh diam-diam dibuang lalu lolos
        $this->assertFalse(GraduateInputValidator::validateNik("3201234567890123' OR 1=1--")['valid']);
        $this->assertFalse(GraduateInputValidator::validateNik('1234567890')['valid']);
        $this->assertTrue(GraduateInputValidator::validateNik('3201234567890123')['valid']);

        // Email wajib valid
        $this->assertFalse(GraduateInputValidator::validateEmail('bukan-email')['valid']);
        $this->assertFalse(GraduateInputValidator::validateEmail("user' OR 1=1--@yarsi.ac.id")['valid']);
        $this->assertTrue(GraduateInputValidator::validateEmail('mahasiswa@yarsi.ac.id')['valid']);

        // Nomor HP wajib format angka (+opsional di awal, 8-15 digit)
        $this->assertFalse(GraduateInputValidator::validatePhone('0812-abc-999', true)['valid']);
        $this->assertTrue(GraduateInputValidator::validatePhone('081234567890', true)['valid']);

        // IPK rentang 0.00 - 4.00
        $this->assertFalse(GraduateInputValidator::validateIpk('4.50', true)['valid']);
        $this->assertFalse(GraduateInputValidator::validateIpk('-1.00', true)['valid']);
        $this->assertTrue(GraduateInputValidator::validateIpk('3.85', true)['valid']);

        // SKS bilangan bulat 1 - 250
        $this->assertFalse(GraduateInputValidator::validateSks('300', true)['valid']);
        $this->assertFalse(GraduateInputValidator::validateSks('144 OR 1=1', true)['valid']);
        $this->assertTrue(GraduateInputValidator::validateSks('144', true)['valid']);
    }

    public function testNameContainingScriptTagRendersAsPlainText(): void
    {
        $xssNama = "<script>alert('xss')</script>";

        session()->set('user_session', [
            'id'      => 54,
            'akun_id' => 54,
            'npm'     => '1402024054',
            'nama'    => $xssNama,
            'email'   => 'mhs@yarsi.ac.id',
            'roles'   => ['tendik'],
        ]);

        // 1. Render halaman profil yang menampilkan nama pengguna/LDAP
        $profileHtml = view('The-Graduate-Web/profile', [
            'title'    => $xssNama,
            'dataDiri' => [
                'id'                  => '54',
                'id_akun'             => 54,
                'nama'                => $xssNama,
                'email'               => 'mhs@yarsi.ac.id',
                'roleLabel'           => 'Tendik',
                'prodi'               => 'Teknik Informatika',
                'extra_label'         => 'NPM',
                'extra_value'         => '1402024054',
                'extra_label2'        => '',
                'extra_value2'        => '',
                'tanggal_sk_yudisium' => null,
            ],
        ]);

        $this->assertStringNotContainsString(
            $xssNama,
            $profileHtml,
            'Tag <script> pada nama tidak boleh dirender mentah di HTML'
        );
        $this->assertStringContainsString(
            '&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;',
            $profileHtml,
            'Tag <script> pada nama harus tampil sebagai teks biasa yang ter-encode'
        );

        // 2. Pastikan dropdown notifikasi di layout/template.php memakai helper escapeHtml & safeNotifHref
        $templateSource = file_get_contents(APPPATH . 'Views/The-Graduate-Web/layout/template.php');
        $this->assertIsString($templateSource);
        $this->assertStringContainsString('escapeHtml(item.pesan)', $templateSource);
        $this->assertStringContainsString('safeNotifHref(item.link)', $templateSource);
        $this->assertStringNotContainsString('${item.pesan}', $templateSource);
    }
}
