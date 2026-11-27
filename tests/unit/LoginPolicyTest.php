<?php

use App\Libraries\LoginPolicy;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class LoginPolicyTest extends CIUnitTestCase
{
    public function testMahasiswaLdapRoleOnlyAllowsStudentRoles()
    {
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('Mahasiswa', ['mahasiswa']));
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('Mahasiswa', ['alumni', 'mahasiswa']));

        $this->assertFalse(LoginPolicy::ldapRoleAllowed('Mahasiswa', ['tendik']));
        $this->assertFalse(LoginPolicy::ldapRoleAllowed('Mahasiswa', ['admin']));
        $this->assertFalse(LoginPolicy::ldapRoleAllowed('Mahasiswa', ['kaprodi']));
        $this->assertFalse(LoginPolicy::ldapRoleAllowed('Mahasiswa', ['mahasiswa', 'admin']));
    }

    public function testDosenLdapRoleOnlyAllowsLecturerRoles()
    {
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('Dosen', ['dosen']));
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('Dosen', ['dospem eksternal', 'dosen']));

        $this->assertFalse(LoginPolicy::ldapRoleAllowed('Dosen', ['tendik']));
        $this->assertFalse(LoginPolicy::ldapRoleAllowed('Dosen', ['dosen', 'fakultas']));
    }

    public function testStaffLdapRoleOnlyAllowsTendik()
    {
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('Staff', ['tendik']));

        $this->assertFalse(LoginPolicy::ldapRoleAllowed('Staff', ['admin']));
        $this->assertFalse(LoginPolicy::ldapRoleAllowed('Staff', ['tendik', 'kaprodi']));
    }

    public function testUnknownLdapRoleNeverEscalatesLocalRoles()
    {
        $this->assertFalse(LoginPolicy::ldapRoleAllowed('Admin', ['admin']));
        $this->assertFalse(LoginPolicy::ldapRoleAllowed('Lainnya', ['mahasiswa']));
    }

    public function testEmptyLdapRoleAllowsManagedRolesOnly()
    {
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('', ['mahasiswa']));
        $this->assertTrue(LoginPolicy::ldapRoleAllowed(null, ['alumni', 'mahasiswa']));
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('', ['dosen', 'dospem eksternal']));
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('', ['tendik']));

        $this->assertFalse(LoginPolicy::ldapRoleAllowed('', ['admin']));
        $this->assertFalse(LoginPolicy::ldapRoleAllowed('', ['kaprodi']));
        $this->assertFalse(LoginPolicy::ldapRoleAllowed('', ['mahasiswa', 'klien']));
        $this->assertFalse(LoginPolicy::ldapRoleAllowed('', ['tendik', 'tamu']));
    }

    public function testLocalRolesAreComparedCaseInsensitively()
    {
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('Mahasiswa', ['Mahasiswa']));
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('Staff', [' Tendik ']));
    }

    public function testAccountWithoutRolesCanStillBeVerified()
    {
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('Mahasiswa', []));
        $this->assertTrue(LoginPolicy::ldapRoleAllowed('', []));
    }
}
