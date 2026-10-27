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
        $key      = LoginThrottle::accountKey('mahasiswa', '127.0.0.1');

        $this->assertSame(0, $throttle->attempts($key));
        $this->assertFalse($throttle->tooManyAttempts($key));

        for ($i = 1; $i <= 5; $i++) {
            $this->assertSame($i, $throttle->hit($key));
        }

        $this->assertTrue($throttle->tooManyAttempts($key));
        $this->assertGreaterThan(0, $throttle->availableIn($key));

        $throttle->clear($key);

        $this->assertFalse($throttle->tooManyAttempts($key));
        $this->assertSame(0, $throttle->availableIn($key));
    }

    public function testIpBucketHasSeparateLimit()
    {
        $throttle = new LoginThrottle();
        $key      = LoginThrottle::ipKey('127.0.0.1');

        for ($i = 1; $i <= 19; $i++) {
            $throttle->hit($key);
        }

        $this->assertFalse($throttle->tooManyAttempts($key));

        $throttle->hit($key);

        $this->assertTrue($throttle->tooManyAttempts($key));
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
