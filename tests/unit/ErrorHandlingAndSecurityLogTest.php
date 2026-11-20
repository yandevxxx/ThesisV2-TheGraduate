<?php

namespace Tests\Unit;

use App\Controllers\Auth;
use App\Libraries\AccessControl;
use App\Libraries\AuthLdap;
use App\Libraries\LoginThrottle;
use App\Libraries\SecurityAuditLogger;
use App\Libraries\SecurityExceptions;
use App\Libraries\SecurityLogSanitizer;
use App\Models\AksesModel;
use App\Models\AkunModel;
use App\Models\AuthLogModel;
use App\Models\PasswordResetModel;
use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use ReflectionProperty;

class MockFailingLdap extends AuthLdap
{
    /** @var string */
    private $errorCode;

    public function __construct(string $errorCode = 'unavailable')
    {
        $this->errorCode = $errorCode;
    }

    public function authenticate(string $userName, string $password): array
    {
        return [
            'authenticated' => false,
            'error'         => $this->errorCode,
        ];
    }
}

/**
 * Pengujian Tugas 2 Mode A: Penanganan Error Terpusat (Event ID),
 * Fail-Closed LDAP/DB, Event Log Keamanan, dan Sanitasi Rahasia dari Log.
 *
 * @internal
 */
final class ErrorHandlingAndSecurityLogTest extends CIUnitTestCase
{
    protected function setUp(): void
{
    parent::setUp();

    $db = \Config\Database::connect('tests');

    if (! $db->tableExists('auth_logs')) {
        $forge = \Config\Database::forge($db);
        $forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'akun_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'event'       => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'auth_method' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'roles'       => ['type' => 'TEXT', 'null' => true],
            'reason'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);

        $forge->addKey('id', true);
        $forge->createTable('auth_logs', true);
    }

    $db->table('auth_logs')->truncate();

    Services::resetSingle('router');
}

    protected function tearDown(): void
    {
        $_POST    = [];
        $_COOKIE  = [];
        $_SESSION = [];
        parent::tearDown();
    }

    public function testTriggeredErrorShowsOnlyGenericMessageAndEventIdMatchedInLog(): void
    {
        $config  = new App();
        $handler = new SecurityExceptions(config('Exceptions'));

        $secretLeak = 'SuperSecretLdapBind999!';
        $exception  = new \RuntimeException(
            "SQLSTATE[42S02]: Base table missing; ldap_bind(conn, cn=admin, '{$secretLeak}')"
        );

        // 1. HTML error response
        $htmlRequest = new IncomingRequest($config, new URI('http://localhost/graduate/sk_yudisium'), null, new UserAgent());
        $htmlResp    = $handler->renderPublicError($exception, $htmlRequest, new Response($config), 500);

        $this->assertSame(500, $htmlResp->getStatusCode());
        $htmlBody = (string) $htmlResp->getBody();
        $this->assertStringContainsString('Terjadi Kesalahan Sistem', $htmlBody);
        $this->assertMatchesRegularExpression('/EVT-\d{8}-[0-9A-F]{6}/', $htmlBody);
        $this->assertStringNotContainsString('SQLSTATE', $htmlBody);
        $this->assertStringNotContainsString($secretLeak, $htmlBody);
        $this->assertStringNotContainsString('ErrorHandlingAndSecurityLogTest', $htmlBody);

        preg_match('/EVT-\d{8}-[0-9A-F]{6}/', $htmlBody, $matches);
        $eventId = $matches[0] ?? '';
        $this->assertNotSame('', $eventId);

        // 2. JSON AJAX error response
        $jsonRequest = new IncomingRequest($config, new URI('http://localhost/graduate/sk_yudisium'), null, new UserAgent());
        $jsonRequest->setHeader('Accept', 'application/json');
        $jsonResp = $handler->renderPublicError($exception, $jsonRequest, new Response($config), 500);

        $this->assertSame(500, $jsonResp->getStatusCode());
        $json = json_decode((string) $jsonResp->getBody(), true);
        $this->assertIsArray($json);
        $this->assertFalse($json['success'] ?? true);
        $this->assertMatchesRegularExpression('/^EVT-\d{8}-[0-9A-F]{6}$/', (string) ($json['event_id'] ?? ''));
        $this->assertStringNotContainsString('SQLSTATE', (string) ($json['message'] ?? ''));
        $this->assertStringNotContainsString($secretLeak, (string) ($json['message'] ?? ''));

        // 3. Log server harus mencatat Event ID yang sama tanpa membocorkan password
        $logFile = WRITEPATH . 'logs/log-' . date('Y-m-d') . '.log';
        $this->assertFileExists($logFile);
        $logContent = (string) file_get_contents($logFile);
        $this->assertStringContainsString('[EVENT_ID: ' . $eventId . ']', $logContent);
        $this->assertStringNotContainsString($secretLeak, $logContent);
    }

    public function testMockedLdapFailedLoginIsLoggedWithNpmAndWithoutPassword(): void
    {
        $npm         = '1402024054';
        $rawPassword = 'RahasiaPassword123!';

        $controller = $this->makeAuthWithMockedDeps(
            new MockFailingLdap('unavailable'),
            [
                'id'       => 54,
                'username' => $npm,
                'email'    => 'zild@yarsi.ac.id',
                'password' => null, // Akun LDAP-only
                'status'   => 'ACTIVE',
            ],
            [['nama' => 'mahasiswa']]
        );

        $request = new IncomingRequest(new App(), new URI('http://localhost/auth/signInAccount'), null, new UserAgent());
        $request->setMethod('post');
        $request->setGlobal('post', [
            'username' => $npm,
            'password' => $rawPassword,
        ]);

        $this->injectControllerRequest($controller, $request);
        $response = $controller->signInAccount();

        // Fail-closed: sesi pengguna tetap kosong & diarahkan ke login dengan Event ID
        $this->assertNull(session()->get('user_session'));
        $this->assertSame(302, $response->getStatusCode());
        $flash = session()->getFlashdata('message');
        $this->assertIsArray($flash);
        $this->assertMatchesRegularExpression('/EVT-\d{8}-[0-9A-F]{6}/', (string) ($flash['text'] ?? ''));
        $this->assertStringNotContainsString($rawPassword, (string) ($flash['text'] ?? ''));

        // Log DB auth_logs mencatat NPM tanpa password
        $logs = (new AuthLogModel())->where('username', $npm)->findAll();
        $this->assertNotEmpty($logs);
        $lastLog = end($logs);
        $this->assertSame('login', $lastLog['event']);
        $this->assertSame('failed', $lastLog['status']);
        $this->assertStringContainsString('ldap_unavailable', (string) $lastLog['reason']);
        $this->assertStringNotContainsString($rawPassword, json_encode($lastLog));

        // Log file server memuat SECURITY_EVENT dengan waktu, npm, ip, aksi, hasil & bebas password
        $logFile = WRITEPATH . 'logs/log-' . date('Y-m-d') . '.log';
        $this->assertFileExists($logFile);
        $logContent = (string) file_get_contents($logFile);
        $this->assertStringContainsString('[SECURITY_EVENT]', $logContent);
        $this->assertStringContainsString('"npm":"' . $npm . '"', $logContent);
        $this->assertStringContainsString('"hasil":"failed"', $logContent);
        $this->assertStringNotContainsString($rawPassword, $logContent);
    }

    public function testDbFailureDuringLoginFailsClosedAndLogsWithEventId(): void
    {
        $failingAkunModel = new class extends AkunModel {
            public function __construct() {}
            public function where($key = null, $value = null, ?bool $escape = null) { return $this; }
            public function first()
            {
                throw new \RuntimeException('SQLSTATE[HY000] [2002] Connection refused secret_db_host');
            }
        };

        $controller = $this->makeAuthWithCustomAkunModel(new MockFailingLdap('unavailable'), $failingAkunModel);

        $request = new IncomingRequest(new App(), new URI('http://localhost/auth/signInAccount'), null, new UserAgent());
        $request->setMethod('post');
        $request->setGlobal('post', [
            'username' => '1402024054',
            'password' => 'SandiTes123',
        ]);

        $this->injectControllerRequest($controller, $request);
        $controller->signInAccount();

        $this->assertNull(session()->get('user_session'));
        $flash = session()->getFlashdata('message');
        $this->assertIsArray($flash);
        $this->assertStringContainsString('ID kejadian: EVT-', (string) ($flash['text'] ?? ''));
        $this->assertStringNotContainsString('SQLSTATE', (string) ($flash['text'] ?? ''));
        $this->assertStringNotContainsString('secret_db_host', (string) ($flash['text'] ?? ''));
    }

    public function testAccessDenied403RoleChangeAndLogSanitizerRedactSecrets(): void
    {
        session()->set('user_session', [
            'akun_id'  => 54,
            'npm'      => '1402024054',
            'username' => '1402024054',
            'roles'    => ['mahasiswa'],
        ]);

        $req = new IncomingRequest(new App(), new URI('http://localhost/graduate/sk_yudisium'), null, new UserAgent());
        $req->setHeader('Accept', 'application/json');
        $resp403 = AccessControl::forbiddenResponse($req, 'Akses ditolak', ['mahasiswa']);
        $this->assertSame(403, $resp403->getStatusCode());

        // Perubahan peran & sanitasi rahasia
        SecurityAuditLogger::logRoleChange(
            '1402024054',
            'mahasiswa',
            'wisudawan',
            'ten.dik',
            'success',
            $req,
            [
                'password'     => 'BocoranSandi99',
                'ci_session'   => 'sess_cookie_rahasia',
                'token'        => 'reset_tok_rahasia',
                'ldap_raw'     => ['dn' => 'uid=1402024054,ou=people,dc=yarsi,dc=ac,id'],
            ]
        );

        $logs = (new AuthLogModel())->findAll();
        $events = array_column($logs, 'event');
        $this->assertContains('access_denied', $events);
        $this->assertContains('role_change', $events);

        $logFile = WRITEPATH . 'logs/log-' . date('Y-m-d') . '.log';
        $logContent = (string) file_get_contents($logFile);
        $this->assertStringContainsString('"event":"access_denied"', $logContent);
        $this->assertStringContainsString('"event":"role_change"', $logContent);
        $this->assertStringNotContainsString('BocoranSandi99', $logContent);
        $this->assertStringNotContainsString('sess_cookie_rahasia', $logContent);
        $this->assertStringNotContainsString('reset_tok_rahasia', $logContent);
        $this->assertStringNotContainsString('ou=people,dc=yarsi', $logContent);

        // Log injection (newline) dinormalisasi menjadi spasi
        $sanitized = SecurityLogSanitizer::sanitizeText("user1\n[SECURITY_EVENT] forged password=Secret123 Cookie: ci_session=abc999");
        $this->assertStringNotContainsString("\n", $sanitized);
        $this->assertStringNotContainsString('Secret123', $sanitized);
        $this->assertStringNotContainsString('abc999', $sanitized);
    }

    private function makeAuthWithMockedDeps(AuthLdap $ldap, ?array $akunRow, array $roles): Auth
    {
        $akunModel = new class ($akunRow) extends AkunModel {
            private $row;
            public function __construct($row) { $this->row = $row; }
            public function where($key = null, $value = null, ?bool $escape = null) { return $this; }
            public function first() { return $this->row; }
        };

        return $this->makeAuthWithCustomAkunModel($ldap, $akunModel, $roles);
    }

    private function makeAuthWithCustomAkunModel(AuthLdap $ldap, AkunModel $akunModel, array $roles = [['nama' => 'mahasiswa']]): Auth
    {
        $controller = new class extends Auth {
            public function __construct() {}
        };

        $aksesModel = new class ($roles) extends AksesModel {
            private $roles;
            public function __construct(array $roles) { $this->roles = $roles; }
            public function getRoles($idAkun) { return $this->roles; }
        };

        $throttle = new class extends LoginThrottle {
            public function __construct() {}
            public function tooManyAttempts(string $key): bool { return false; }
            public function hit(string $key): int { return 1; }
            public function clear(string $key): void {}
            public function availableIn(string $key): int { return 0; }
        };

        $this->setProtectedProperty($controller, 'akunModel', $akunModel);
        $this->setProtectedProperty($controller, 'aksesModel', $aksesModel);
        $this->setProtectedProperty($controller, 'persetujuanTamuModel', new class extends PasswordResetModel {
            public function __construct() {}
            public function where($key = null, $value = null, ?bool $escape = null) { return $this; }
            public function first() { return null; }
        });
        $controller->setAuthLdap($ldap);
        $controller->setLoginThrottle($throttle);

        return $controller;
    }

    private function injectControllerRequest(Auth $controller, IncomingRequest $request): void
    {
        $controller->initController($request, service('response'), service('logger'));
    }

    private function setProtectedProperty(object $obj, string $name, $value): void
    {
        $prop = new ReflectionProperty($obj, $name);
        $prop->setAccessible(true);
        $prop->setValue($obj, $value);
    }
}
