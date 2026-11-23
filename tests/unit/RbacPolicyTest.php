<?php

use App\Libraries\AccessControl;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class RbacPolicyTest extends CIUnitTestCase
{
    public function testNormalizeRolesAcceptsSessionPayload()
    {
        $this->assertSame(
            ['mahasiswa', 'dosen'],
            AccessControl::normalizeRoles(['roles' => ['Mahasiswa', 'DOSEN', ' mahasiswa ']])
        );

        $this->assertSame(['admin'], AccessControl::normalizeRoles(['role' => 'Admin']));
        $this->assertSame([], AccessControl::normalizeRoles(null));
        $this->assertSame([], AccessControl::normalizeRoles(['roles' => 'admin']));
    }

    public function testRoleAliasesKeepLegacyDataWorking()
    {
        $this->assertTrue(AccessControl::roleEquals('prodi', 'kaprodi'));
        $this->assertTrue(AccessControl::roleEquals('Kaprodi', 'PRODI'));
        $this->assertTrue(AccessControl::roleEquals('staf', 'tendik'));
        $this->assertFalse(AccessControl::roleEquals('dosen', 'prodi'));
    }

    public function testThesisControllersUnmappedToUseLegacyAuth()
    {
        foreach (['Mahasiswa', 'TenagaKependidikan', 'Fakultas', 'Admin', 'Kaprodi', 'Dosen', 'Auth'] as $controller) {
            $this->assertFalse(AccessControl::isControllerMapped($controller), "{$controller} should not be mapped in Rbac config (uses legacy auth)");
        }
    }

    public function testAdminIsSuperAdmin()
    {
        foreach (['Graduate', 'Mahasiswa', 'TenagaKependidikan'] as $controller) {
            $this->assertTrue(AccessControl::canAccess($controller, 'index', ['admin']));
        }
    }

    public function testUnmappedControllerKeepsLegacyBehaviour()
    {
        $this->assertFalse(AccessControl::isControllerMapped('Auth'));
        $this->assertTrue(AccessControl::isControllerMapped('Home'));
        $this->assertTrue(AccessControl::isControllerMapped('Graduate'));
        $this->assertTrue(AccessControl::canAccess('SomeFutureController', 'index', ['tamu']));
    }

    public function testGraduateSkYudisiumIsStaffOnly()
    {
        $this->assertTrue(AccessControl::canAccess('Graduate', 'sk_yudisium', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'sk_yudisium', ['staf']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'sk_yudisium', ['admin']));

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'kaprodi', 'prodi', 'fakultas', 'alumni'] as $role) {
            $this->assertFalse(
                AccessControl::canAccess('Graduate', 'sk_yudisium', [$role]),
                'Role ' . $role . ' must not open SK Yudisium.'
            );
        }
    }

    public function testGraduateSkYudisiumDeleteIsStaffOnly()
    {
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumDelete', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumDelete', ['staf']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumDelete', ['admin']));

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'kaprodi', 'prodi', 'fakultas', 'alumni'] as $role) {
            $this->assertFalse(
                AccessControl::canAccess('Graduate', 'skYudisiumDelete', [$role]),
                'Role ' . $role . ' must not delete SK Yudisium plots.'
            );
        }
    }

    public function testGraduateSkYudisiumStoreIsStaffOnly()
    {
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumStore', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumStore', ['staf']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumStore', ['admin']));

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'kaprodi', 'prodi', 'fakultas', 'alumni'] as $role) {
            $this->assertFalse(
                AccessControl::canAccess('Graduate', 'skYudisiumStore', [$role]),
                'Role ' . $role . ' must not add SK Yudisium plots.'
            );
        }
    }

    public function testGraduateChooserRemainsAvailableForLocalOnlyRoles()
    {
        foreach (['admin', 'tendik', 'staf', 'dosen', 'mahasiswa', 'alumni', 'kaprodi', 'prodi', 'fakultas', 'klien', 'tamu'] as $role) {
            $this->assertTrue(
                AccessControl::canAccess('Graduate', 'choose', [$role]),
                'Role ' . $role . ' must be able to open the chooser.'
            );
        }
    }

    public function testGraduateDashboardFoldersAreRoleSpecific()
    {
        $this->assertTrue(AccessControl::canAccess('Graduate', 'admin_tendik', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'admin_tendik', ['fakultas']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'admin_tendik', ['mahasiswa']));

        $this->assertTrue(AccessControl::canAccess('Graduate', 'prodi', ['prodi']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'prodi', ['dosen']));

        $this->assertTrue(AccessControl::canAccess('Graduate', 'dosen', ['dosen']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'dosen', ['prodi']));

        $this->assertTrue(AccessControl::canAccess('Graduate', 'mahasiswa', ['mahasiswa']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'mahasiswa', ['alumni']));

        $this->assertTrue(AccessControl::canAccess('Graduate', 'alumni', ['alumni']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'alumni', ['mahasiswa']));
    }

    public function testGraduateStudentFormsAreMahasiswaOnly()
    {
        $this->assertTrue(AccessControl::canAccess('Graduate', 'pendaftaran_wisuda', ['mahasiswa']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'pendaftaran_wisuda', ['dosen']));

        $this->assertTrue(AccessControl::canAccess('Graduate', 'tracer_studi', ['mahasiswa']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'tracer_studi', ['tendik']));
    }

    public function testMenuVisibilityFollowsRole()
    {
        $this->assertTrue(AccessControl::menuVisible('mahasiswa', ['mahasiswa']));
        $this->assertFalse(AccessControl::menuVisible('tendik', ['mahasiswa']));
        $this->assertFalse(AccessControl::menuVisible('admin', ['mahasiswa']));

        $this->assertTrue(AccessControl::menuVisible('tendik', ['tendik']));
        $this->assertTrue(AccessControl::menuVisible('fakultas_dashboard', ['tendik']));
        $this->assertFalse(AccessControl::menuVisible('mahasiswa', ['tendik']));

        $this->assertTrue(AccessControl::menuVisible('kaprodi_dashboard', ['prodi']));
        $this->assertTrue(AccessControl::menuVisible('kaprodi', ['prodi']));
        $this->assertFalse(AccessControl::menuVisible('kaprodi', ['dosen']));
    }

    public function testAdminAndUnknownMenus()
    {
        $this->assertTrue(AccessControl::menuVisible('admin', ['admin']));
        $this->assertTrue(AccessControl::menuVisible('mahasiswa', ['admin']));
        $this->assertTrue(AccessControl::menuVisible('menu-bersama', ['tamu']));
    }

    public function testStafAliasIsHonouredByPolicy()
    {
        $this->assertTrue(AccessControl::canAccess('Graduate', 'sk_yudisium', ['staf']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'tracer_studi', ['staf']));
    }

    public function testGraduateBelumMendaftarAccessControl()
    {
        $this->assertTrue(AccessControl::canAccess('Graduate', 'belum_mendaftar', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'belum_mendaftar', ['staf']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'belum_mendaftar', ['admin']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'belum_mendaftar', ['fakultas']));

        $this->assertFalse(AccessControl::canAccess('Graduate', 'belum_mendaftar', ['mahasiswa']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'belum_mendaftar', ['dosen']));
    }
}
