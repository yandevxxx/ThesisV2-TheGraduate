
<?php

use App\Libraries\LoginThrottle;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class LoginThrottleFeatureTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    public function testSixthLoginAttemptReturns429()
    {
        $throttle = new LoginThrottle();

        $username  = 'test_mahasiswa';
        $ipAddress = '127.0.0.1';

        $accountKey = LoginThrottle::accountKey(
            $username,
            $ipAddress
        );

        $ipKey = LoginThrottle::ipKey($ipAddress);

        // Bersihkan cache sebelum pengujian.
        $throttle->clear($accountKey);
        $throttle->clear($ipKey);

        $prevReporting = error_reporting(E_ALL & ~E_DEPRECATED);

        try {
            // Simulasikan lima kali kegagalan login.
            for ($i = 0; $i < 5; $i++) {
                $throttle->hit($accountKey);
            }

            // Kirim percobaan login berikutnya beserta token CSRF dan IP yang sesuai.
            $security   = config('Security');
            $csrfCookie = $security->cookieName ?? 'csrf_cookie_name';
            $csrfName   = csrf_token();
            $csrfHash   = csrf_hash();

            $_SERVER['REMOTE_ADDR'] = $ipAddress;
            $_COOKIE[$csrfCookie]   = $csrfHash;
            \Config\Services::resetSingle('security');

            $result = $this->withHeaders([
                $security->headerName ?? 'X-CSRF-TOKEN' => $csrfHash,
            ])->call(
                'post',
                'auth/signInAccount',
                [
                    'username' => $username,
                    'password' => 'password_pengujian',
                    $csrfName  => $csrfHash,
                ]
            );

            // Pastikan login keenam ditolak.
            $result->assertStatus(429);

            $result->assertSee(
                'Terlalu banyak percobaan login'
            );
        } finally {
            error_reporting($prevReporting);
            // Bersihkan cache setelah pengujian.
            $throttle->clear($accountKey);
            $throttle->clear($ipKey);
        }
    }
}
