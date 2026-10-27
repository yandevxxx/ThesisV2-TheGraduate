<?php

use App\Libraries\LocalCredential;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class LocalCredentialTest extends CIUnitTestCase
{
    public function testRecognisesPasswordHashes()
    {
        $this->assertTrue(LocalCredential::isHash(password_hash('rahasia', PASSWORD_BCRYPT)));

        if (defined('PASSWORD_ARGON2ID')) {
            $this->assertTrue(LocalCredential::isHash(password_hash('rahasia', PASSWORD_ARGON2ID)));
        }
    }

    public function testRejectsEmptyAndPlainTextValues()
    {
        $this->assertFalse(LocalCredential::isHash(null));
        $this->assertFalse(LocalCredential::isHash(''));
        $this->assertFalse(LocalCredential::isHash('12345678'));
        $this->assertFalse(LocalCredential::isHash('Admin'));
        $this->assertFalse(LocalCredential::isHash('$2y$10$bukan-hash-lengkap'));
    }

    public function testVerifiesOnlyMatchingPassword()
    {
        $hash = password_hash('rahasia', PASSWORD_BCRYPT);

        $this->assertTrue(LocalCredential::verify('rahasia', $hash));
        $this->assertFalse(LocalCredential::verify('salah', $hash));
    }

    public function testPlainTextNeverVerifies()
    {
        $this->assertFalse(LocalCredential::verify('12345678', '12345678'));
        $this->assertFalse(LocalCredential::verify('Admin', 'Admin'));
        $this->assertFalse(LocalCredential::verify('rahasia', null));
        $this->assertFalse(LocalCredential::verify('', password_hash('rahasia', PASSWORD_BCRYPT)));
    }
}
