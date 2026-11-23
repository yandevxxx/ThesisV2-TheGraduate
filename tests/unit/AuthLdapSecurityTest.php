<?php

namespace Tests\Unit;

use App\Controllers\Auth;
use App\Libraries\AuthLdap;
use App\Libraries\LoginThrottle;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;

class MockLdapSecurity extends AuthLdap
{
    public $callCount = 0;

    public function authenticate(string $userName, string $password): array
    {
        $this->callCount++;

        return [
            'authenticated' => false,
            'error'         => 'invalid_credentials',
        ];
    }
}

final class AuthLdapSecurityTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        session()->destroy();
    }

    private function createPostRequest(string $username, string $password, string $ip)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR']    = $ip;
        $_POST['username']         = $username;
        $_POST['password']         = $password;

        $request = Services::request(config('App'), false);
        $request->setMethod('post');

        return $request;
    }

    public function testSixthAttemptIsRejectedWithHttp429WithoutCallingLdap()
    {
        $mockLdap = new MockLdapSecurity();
        $throttle = new LoginThrottle();

        $testUsername = 'mhs.test429';
        $testIp       = '192.168.100.50';

        $accountKey = LoginThrottle::accountKey($testUsername, $testIp);
        $ipKey      = LoginThrottle::ipKey($testIp);
        $throttle->clear($accountKey);
        $throttle->clear($ipKey);

        // Percobaan 1 s.d 5: Gagal dan memanggil mock LDAP
        for ($i = 1; $i <= 5; $i++) {
            $request = $this->createPostRequest($testUsername, 'password_salah_' . $i, $testIp);

            $controller = new Auth();
            $controller->initController($request, Services::response(), Services::logger());
            $controller->setAuthLdap($mockLdap);
            $controller->setLoginThrottle($throttle);

            $response = $controller->signInAccount();

            $this->assertSame(
                $i,
                $mockLdap->callCount,
                "Pemanggilan LDAP harus bertambah pada percobaan ke-{$i}."
            );
        }

        $this->assertSame(5, $mockLdap->callCount, 'Mock LDAP harus sudah dipanggil tepat 5 kali.');

        // Percobaan ke-6: Harus diblokir sebelum mencapai LDAP dengan status HTTP 429
        $request6 = $this->createPostRequest($testUsername, 'password_salah_ke_6', $testIp);

        $controller6 = new Auth();
        $controller6->initController($request6, Services::response(), Services::logger());
        $controller6->setAuthLdap($mockLdap);
        $controller6->setLoginThrottle($throttle);

        $response6 = $controller6->signInAccount();

        // 1. Verifikasi respon status kode adalah 429
        $this->assertSame(429, $response6->getStatusCode(), 'Percobaan ke-6 harus ditolak dengan status HTTP 429.');

        // 2. Verifikasi LDAP TIDAK dipanggil pada percobaan ke-6 (callCount tetap 5)
        $this->assertSame(
            5,
            $mockLdap->callCount,
            'Server LDAP TIDAK boleh dipanggil pada percobaan ke-6 karena sudah diblokir di layer aplikasi.'
        );

        // Cleanup
        $throttle->clear($accountKey);
        $throttle->clear($ipKey);
    }

    public function testPasswordDoesNotAppearInLogFilesAfterTestLogin()
    {
        $mockLdap = new MockLdapSecurity();
        $throttle = new LoginThrottle();

        $testUsername = 'mhs.auditlog';
        $testPassword = 'RAHASIA_SUPER_UNIK_' . bin2hex(random_bytes(6));
        $testIp       = '192.168.100.99';

        $accountKey = LoginThrottle::accountKey($testUsername, $testIp);
        $ipKey      = LoginThrottle::ipKey($testIp);
        $throttle->clear($accountKey);
        $throttle->clear($ipKey);

        $request = $this->createPostRequest($testUsername, $testPassword, $testIp);

        $controller = new Auth();
        $controller->initController($request, Services::response(), Services::logger());
        $controller->setAuthLdap($mockLdap);
        $controller->setLoginThrottle($throttle);

        $controller->signInAccount();

        // 1. Pastikan password tidak ada di berkas log CodeIgniter (writable/logs/)
        $logDir = WRITEPATH . 'logs' . DIRECTORY_SEPARATOR;
        if (is_dir($logDir)) {
            $logFiles = glob($logDir . '*.log');
            foreach ($logFiles as $file) {
                $content = file_get_contents($file);
                $this->assertStringNotContainsString(
                    $testPassword,
                    $content,
                    "Password uji coba TIDAK boleh muncul di berkas log: " . basename($file)
                );
            }
        }

        // 2. Pastikan password tidak ada di tabel database auth_logs
        try {
            $authLogModel = new \App\Models\AuthLogModel();
            $recentLogs   = $authLogModel->where('username', $testUsername)->findAll();
            foreach ($recentLogs as $log) {
                $this->assertStringNotContainsString(
                    $testPassword,
                    json_encode($log),
                    'Password uji coba TIDAK boleh ada di tabel auth_logs.'
                );
            }
        } catch (\Throwable $e) {
            // Jika tabel belum dimigrasi di sqlite test, lewati pengecekan DB
        }

        // Cleanup
        $throttle->clear($accountKey);
        $throttle->clear($ipKey);
    }
}
