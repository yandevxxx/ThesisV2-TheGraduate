<?php

// Simpan sebagai: tests/unit/LoginRateLimitTest.php
// Jalankan:       vendor/bin/phpunit tests/unit/LoginRateLimitTest.php
// Syarat: MySQL (XAMPP) menyala karena Auth::signInAccount() membaca tabel akun.

namespace Tests\Unit;

use App\Libraries\AuthLdap;
use App\Models\AuthLogModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class LoginRateLimitTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    // Data uji palsu, bukan akun kampus sungguhan.
    private const TEST_USER     = 'uji_login_palsu';
    private const TEST_PASSWORD = 'PasswordUji!9x7Qz';

    protected function setUp(): void
{
    parent::setUp();

    // Pakai database lokal (MySQL) alih-alih grup 'tests' (SQLite3, kosong).
    config('Database')->defaultGroup = 'default';

    cache()->clean(); // reset penghitung LoginThrottle (bucket akun dan IP)
}

    protected function tearDown(): void
    {
        cache()->clean();
        Services::resetSingle('authLdap');
        (new AuthLogModel())->where('username', self::TEST_USER)->delete();
        parent::tearDown();
    }

    /** Mock LDAP yang selalu gagal login (tanpa menghubungi LDAP kampus). */
    private function mockLdapFailing($expectedCalls): void
    {
        $ldap = $this->createMock(AuthLdap::class);
        $ldap->expects($expectedCalls)
            ->method('authenticate')
            ->willReturn(['authenticated' => false, 'error' => 'invalid_credentials']);

        Services::injectMock('authLdap', $ldap);
    }

    private function postLogin()
    {
        return $this->post('auth/signInAccount', [
            'username' => self::TEST_USER,
            'password' => self::TEST_PASSWORD,
        ]);
    }

    /**
     * Test 1: 5 percobaan pertama diteruskan ke LDAP (mock) dan gagal;
     * percobaan ke-6 dibalas HTTP 429 + Retry-After TANPA memanggil LDAP.
     */
    public function testSixthAttemptIsBlockedWith429AndLdapIsNotCalled(): void
    {
        // LDAP boleh dipanggil tepat 5 kali. Panggilan ke-6 membuat test gagal.
        $this->mockLdapFailing($this->exactly(5));

        for ($i = 1; $i <= 5; $i++) {
            $result = $this->postLogin();
            $result->assertRedirectTo(base_url('auth/login')); // gagal biasa, bukan 429
        }

        $sixth = $this->postLogin();

        $sixth->assertStatus(429);
        $retryAfter = $sixth->response()->getHeaderLine('Retry-After');
        $this->assertNotSame('', $retryAfter);
        $this->assertGreaterThan(0, (int) $retryAfter);
    }

    /**
     * Test 2: setelah login uji, password uji tidak ada di log file,
     * tabel auth_logs, maupun session/flashdata.
     */
    public function testPasswordNeverAppearsInLogsOrSessionAfterTestLogin(): void
    {
        $this->mockLdapFailing($this->any());

        $logDir = WRITEPATH . 'logs/';
        $before = [];
        foreach (glob($logDir . 'log-*.log') ?: [] as $file) {
            $before[$file] = filesize($file);
        }

        $this->postLogin();

        // 1) log file baru
        $newLog = '';
        foreach (glob($logDir . 'log-*.log') ?: [] as $file) {
            $newLog .= substr((string) file_get_contents($file), $before[$file] ?? 0);
        }
        $this->assertStringNotContainsString(self::TEST_PASSWORD, $newLog);

        // 2) tabel auth_logs (writeAuthLog)
        $rows = (new AuthLogModel())->where('username', self::TEST_USER)->findAll();
        $this->assertNotEmpty($rows, 'writeAuthLog() seharusnya mencatat percobaan login.');
        $this->assertStringNotContainsString(self::TEST_PASSWORD, json_encode($rows));

        // 3) session dan flashdata
        $this->assertStringNotContainsString(
            self::TEST_PASSWORD,
            json_encode(session()->get())
        );
    }
}
