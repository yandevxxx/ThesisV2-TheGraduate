<?php

use App\Libraries\AuthLdap;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AuthLdapTest extends CIUnitTestCase
{
    public function testFilterValueIsEscaped()
    {
        $this->assertSame('user\\2aadmin', AuthLdap::escapeFilterValue('user*admin'));
        $this->assertSame('\\28\\29', AuthLdap::escapeFilterValue('()'));
        $this->assertSame('a\\5cb', AuthLdap::escapeFilterValue('a\\b'));
        $this->assertSame('\\00', AuthLdap::escapeFilterValue("\0"));
    }

    public function testNormalValueIsUnchanged()
    {
        $this->assertSame('2021010001', AuthLdap::escapeFilterValue('2021010001'));
        $this->assertSame('nama.tendik', AuthLdap::escapeFilterValue('nama.tendik'));
    }

    public function testUnavailableErrorsAreClassified()
    {
        // OpenLDAP LDAP_SERVER_DOWN on Windows is -1 and the constant is not defined.
        $this->assertSame('unavailable', AuthLdap::classifyError(-1));
        $this->assertSame('unavailable', AuthLdap::classifyError(85)); // LDAP_TIMEOUT
        $this->assertSame('unavailable', AuthLdap::classifyError(91)); // LDAP_CONNECT_ERROR
        $this->assertSame('unavailable', AuthLdap::classifyError(51)); // LDAP_BUSY
        $this->assertSame('unavailable', AuthLdap::classifyError(52)); // LDAP_UNAVAILABLE
    }

    public function testInvalidCredentialsErrorIsClassified()
    {
        $this->assertSame('invalid_credentials', AuthLdap::classifyError(49));
    }

    public function testOtherErrorsAreConfiguration()
    {
        $this->assertSame('configuration', AuthLdap::classifyError(0));
        $this->assertSame('configuration', AuthLdap::classifyError(87)); // bad filter
    }

    public function testRoleMapping()
    {
        $this->assertSame('Mahasiswa', AuthLdap::mapRole('M'));
        $this->assertSame('Mahasiswa', AuthLdap::mapRole('Mahasiswa'));
        $this->assertSame('Dosen', AuthLdap::mapRole('Dosen Tetap'));
        $this->assertSame('Staff', AuthLdap::mapRole('S'));
        $this->assertSame('Staff', AuthLdap::mapRole('Tenaga Kependidikan'));
        $this->assertNull(AuthLdap::mapRole(''));
        $this->assertNull(AuthLdap::mapRole('Lainnya'));
    }
}
