
<?php

use App\Libraries\LoginThrottle;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class LoginThrottleTest extends CIUnitTestCase
{
    public function testAccountBucketBlocksAfterFiveFailures()
    {
        $throttle = new LoginThrottle();
        $key = LoginThrottle::accountKey('mahasiswa', '127.0.0.1');

        // Bersihkan cache sebelum pengujian.
        $throttle->clear($key);

        $this->assertSame(0, $throttle->attempts($key));
        $this->assertFalse($throttle->tooManyAttempts($key));

        // Simulasikan lima kali kegagalan login.
        for ($i = 1; $i <= 5; $i++) {
            $this->assertSame($i, $throttle->hit($key));
        }

        // Pastikan akun sudah diblokir setelah lima kegagalan.
        $this->assertTrue($throttle->tooManyAttempts($key));

        // Pastikan jumlah percobaan tetap lima.
        $this->assertSame(5, $throttle->attempts($key));

        // Pastikan waktu pemblokiran tersedia.
        $this->assertGreaterThan(0, $throttle->availableIn($key));

        // Bersihkan cache setelah pengujian.
        $throttle->clear($key);

        $this->assertFalse($throttle->tooManyAttempts($key));
        $this->assertSame(0, $throttle->availableIn($key));
    }

    public function testIpBucketHasSeparateLimit()
    {
        $throttle = new LoginThrottle();
        $key = LoginThrottle::ipKey('127.0.0.1');

        // Bersihkan cache sebelum pengujian.
        $throttle->clear($key);

        // IP masih diizinkan sampai 19 percobaan.
        for ($i = 1; $i <= 19; $i++) {
            $throttle->hit($key);
        }

        $this->assertFalse($throttle->tooManyAttempts($key));

        // Percobaan ke-20 mencapai batas IP.
        $throttle->hit($key);

        $this->assertTrue($throttle->tooManyAttempts($key));

        // Bersihkan cache setelah pengujian.
        $throttle->clear($key);
    }

    public function testAccountKeyNormalisesUsernameCase()
    {
        $this->assertSame(
            LoginThrottle::accountKey('Mahasiswa', '10.0.0.1'),
            LoginThrottle::accountKey('  mahasiswa ', '10.0.0.1')
        );

        $this->assertNotSame(
            LoginThrottle::accountKey('mahasiswa', '10.0.0.1'),
            LoginThrottle::accountKey('mahasiswa', '10.0.0.2')
        );
    }
}
