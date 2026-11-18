
<?php

use App\Libraries\LoginThrottle;
use CodeIgniter\Test\FeatureTestCase;

/**
 * @internal
 */
final class LoginThrottleFeatureTest extends FeatureTestCase
{
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

        try {
            // Simulasikan lima kali kegagalan login.
            for ($i = 0; $i < 5; $i++) {
                $throttle->hit($accountKey);
            }

            // Kirim percobaan login berikutnya.
            $result = $this->call(
                'post',
                'auth/signInAccount',
                [
                    'username' => $username,
                    'password' => 'password_pengujian',
                ]
            );

            // Pastikan login keenam ditolak.
            $result->assertStatus(429);

            $result->assertSee(
                'Terlalu banyak percobaan login'
            );
        } finally {
            // Bersihkan cache setelah pengujian.
            $throttle->clear($accountKey);
            $throttle->clear($ipKey);
        }
    }
}
