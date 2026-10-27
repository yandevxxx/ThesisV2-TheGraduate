<?php

use App\Libraries\AuthLdap;
use App\Libraries\LoginResolver;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test double that never touches the network.
 */
final class LoginResolverFakeLdap extends AuthLdap
{
    public $calls = 0;

    public $result = [];

    public function authenticate(string $userName, string $password): array
    {
        $this->calls++;

        return $this->result;
    }
}

/**
 * @internal
 */
final class LoginResolverTest extends CIUnitTestCase
{
    private function resolverFor(array $ldapResult): array
    {
        $ldap         = new LoginResolverFakeLdap();
        $ldap->result = $ldapResult;

        return [new LoginResolver($ldap), $ldap];
    }

    public function testLdapIsAcceptedBeforeLocalPassword()
    {
        [$resolver] = $this->resolverFor([
            'authenticated' => true,
            'role'          => 'Mahasiswa',
        ]);

        $result = $resolver->resolve(
            'mhs.uji',
            'password-ldap',
            password_hash('password-lokal', PASSWORD_BCRYPT),
            ['mahasiswa']
        );

        $this->assertTrue($result['authenticated']);
        $this->assertSame('ldap', $result['method']);
        $this->assertNull($result['reason']);
        $this->assertSame('Mahasiswa', $result['ldap']['role']);
    }

    public function testRoleMismatchFallsBackToLocalHash()
    {
        [$resolver] = $this->resolverFor([
            'authenticated' => true,
            'role'          => 'Staff',
        ]);

        $result = $resolver->resolve(
            'admin.uji',
            'password-lokal',
            password_hash('password-lokal', PASSWORD_BCRYPT),
            ['admin']
        );

        $this->assertTrue($result['authenticated']);
        $this->assertSame('local', $result['method']);
        $this->assertSame('ldap_role_mismatch', $result['reason']);
        $this->assertNull($result['ldap']);
    }

    public function testLdapUnavailableFallsBackToLocalHash()
    {
        [$resolver] = $this->resolverFor([
            'authenticated' => false,
            'error'         => 'unavailable',
        ]);

        $result = $resolver->resolve(
            'ten.dik',
            'password-lokal',
            password_hash('password-lokal', PASSWORD_BCRYPT),
            ['tendik']
        );

        $this->assertTrue($result['authenticated']);
        $this->assertSame('local', $result['method']);
        $this->assertSame('ldap_unavailable', $result['reason']);
    }

    public function testLdapOnlyAccountFailsWhenLdapIsUnavailable()
    {
        [$resolver] = $this->resolverFor([
            'authenticated' => false,
            'error'         => 'unavailable',
        ]);

        $result = $resolver->resolve('mhs.ldap', 'password-apa-saja', null, ['mahasiswa']);

        $this->assertFalse($result['authenticated']);
        $this->assertSame('ldap', $result['method']);
        $this->assertSame('ldap_unavailable', $result['reason']);
    }

    public function testPlaintextLocalPasswordNeverBecomesAFallback()
    {
        [$resolver] = $this->resolverFor([
            'authenticated' => false,
            'error'         => 'invalid_credentials',
        ]);

        $result = $resolver->resolve('admin.uji', '12345678', '12345678', ['admin']);

        $this->assertFalse($result['authenticated']);
        $this->assertSame('ldap', $result['method']);
        $this->assertSame('invalid_credentials', $result['reason']);
    }

    public function testWrongLocalPasswordFailsWithoutRevealingSource()
    {
        [$resolver] = $this->resolverFor([
            'authenticated' => false,
            'error'         => 'invalid_credentials',
        ]);

        $result = $resolver->resolve(
            'ten.dik',
            'password-salah',
            password_hash('password-benar', PASSWORD_BCRYPT),
            ['tendik']
        );

        $this->assertFalse($result['authenticated']);
        $this->assertSame('local', $result['method']);
        $this->assertSame('invalid_credentials', $result['reason']);
    }

    public function testManualLocalSkipsLdapEntirely()
    {
        [$resolver, $ldap] = $this->resolverFor([
            'authenticated' => true,
            'role'          => 'Staff',
        ]);

        $result = $resolver->resolve(
            'ten.dik',
            'password-lokal',
            password_hash('password-lokal', PASSWORD_BCRYPT),
            ['tendik'],
            true
        );

        $this->assertTrue($result['authenticated']);
        $this->assertSame('manual_local', $result['method']);
        $this->assertNull($result['reason']);
        $this->assertSame(0, $ldap->calls);
    }

    public function testManualLocalNeverFallsBackToLdap()
    {
        [$resolver, $ldap] = $this->resolverFor([
            'authenticated' => true,
            'role'          => 'Staff',
        ]);

        $result = $resolver->resolve('ten.dik', 'password-salah', null, ['tendik'], true);

        $this->assertFalse($result['authenticated']);
        $this->assertSame('manual_local', $result['method']);
        $this->assertSame('invalid_credentials', $result['reason']);
        $this->assertSame(0, $ldap->calls);
    }

    public function testMissingLdapRoleAllowsManagedAccountOnly()
    {
        [$resolver] = $this->resolverFor([
            'authenticated' => true,
            'role'          => null,
        ]);

        $result = $resolver->resolve('mhs.uji', 'password-ldap', null, ['mahasiswa']);

        $this->assertTrue($result['authenticated']);
        $this->assertSame('ldap', $result['method']);
    }

    public function testMissingLdapRoleRejectsLocalOnlyRole()
    {
        [$resolver] = $this->resolverFor([
            'authenticated' => true,
            'role'          => null,
        ]);

        $result = $resolver->resolve(
            'admin.uji',
            'password-lokal',
            password_hash('password-lokal', PASSWORD_BCRYPT),
            ['admin']
        );

        $this->assertTrue($result['authenticated']);
        $this->assertSame('local', $result['method']);
        $this->assertSame('ldap_role_mismatch', $result['reason']);
    }

    public function testAccountWithoutRolesIsVerifiedForNoRoleHandling()
    {
        [$resolver] = $this->resolverFor([
            'authenticated' => true,
            'role'          => 'Mahasiswa',
        ]);

        $result = $resolver->resolve('tanpa.role', 'password-ldap', null, []);

        $this->assertTrue($result['authenticated']);
        $this->assertSame('ldap', $result['method']);
    }
}
