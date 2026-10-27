<?php

use App\Libraries\LdapProfileSync;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class LdapProfileSyncTest extends CIUnitTestCase
{
    public function testRoleNameMapping()
    {
        $this->assertSame('mahasiswa', LdapProfileSync::roleName('Mahasiswa'));
        $this->assertSame('dosen', LdapProfileSync::roleName('Dosen'));
        $this->assertSame('tendik', LdapProfileSync::roleName('Staff'));
        $this->assertNull(LdapProfileSync::roleName('Admin'));
        $this->assertNull(LdapProfileSync::roleName(''));
    }

    public function testPayloadKeepsOnlyWhitelistedFields()
    {
        $payload = LdapProfileSync::payloadFromLdap([
            'username'        => '  nama.tendik ',
            'displayname'     => 'Nama Tendik',
            'mail'            => 'tendik@example.test',
            'id_nik'          => '123',
            'role'            => 'Staff',
            'password'        => 'jangan-disimpan',
            'dn'              => 'uid=nama.tendik,dc=example',
            'unknown'         => 'abaikan',
        ]);

        $this->assertSame('nama.tendik', $payload['username']);
        $this->assertSame('Nama Tendik', $payload['displayname']);
        $this->assertSame('tendik@example.test', $payload['mail']);
        $this->assertSame('123', $payload['id_nik']);
        $this->assertSame('Staff', $payload['role']);
        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('dn', $payload);
        $this->assertArrayNotHasKey('unknown', $payload);
    }

    public function testPayloadHandlesMissingFields()
    {
        $this->assertSame([], LdapProfileSync::payloadFromLdap([]));
    }
}
