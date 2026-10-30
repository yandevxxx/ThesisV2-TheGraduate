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

    public function testStudentsCannotOpenStaffOrAdminPages()
    {
        $roles = ['mahasiswa'];

        $this->assertTrue(AccessControl::canAccess('Mahasiswa', 'index', $roles));
        $this->assertTrue(AccessControl::canAccess('App\\Controllers\\Mahasiswa', 'statusProgres', $roles));
        $this->assertFalse(AccessControl::canAccess('TenagaKependidikan', 'pengajuanSkripsi', $roles));
        $this->assertFalse(AccessControl::canAccess('TenagaKependidikan', 'dosen', $roles));
        $this->assertFalse(AccessControl::canAccess('Admin', 'dashboard', $roles));
        $this->assertFalse(AccessControl::canAccess('Fakultas', 'dashboard', $roles));
    }

    public function testStaffCannotOpenStudentPages()
    {
        $roles = ['tendik'];

        $this->assertTrue(AccessControl::canAccess('TenagaKependidikan', 'pengajuanSkripsi', $roles));
        $this->assertTrue(AccessControl::canAccess('TenagaKependidikan', 'detailPengajuanSkripsi', $roles));
        $this->assertFalse(AccessControl::canAccess('Mahasiswa', 'index', $roles));
        $this->assertFalse(AccessControl::canAccess('Mahasiswa', 'detailProposal', $roles));
        $this->assertFalse(AccessControl::canAccess('Admin', 'manageUsers', $roles));
    }

    public function testAdminIsSuperAdmin()
    {
        foreach (['Mahasiswa', 'TenagaKependidikan', 'Fakultas', 'Admin', 'Kaprodi', 'Dosen'] as $controller) {
            $this->assertTrue(AccessControl::canAccess($controller, 'index', ['admin']));
        }
    }

    public function testDosenOnlyOpensDosenPages()
    {
        $roles = ['dosen'];

        $this->assertTrue(AccessControl::canAccess('Dosen', 'index', $roles));
        $this->assertFalse(AccessControl::canAccess('Mahasiswa', 'index', $roles));
        $this->assertFalse(AccessControl::canAccess('TenagaKependidikan', 'index', $roles));
        $this->assertFalse(AccessControl::canAccess('Kaprodi', 'dashboard', $roles));
    }

    public function testProdiSharesKaprodiPolicy()
    {
        $this->assertTrue(AccessControl::canAccess('Kaprodi', 'dashboard', ['prodi']));
        $this->assertTrue(AccessControl::canAccess('Kaprodi', 'dashboard', ['kaprodi']));
        $this->assertFalse(AccessControl::canAccess('Kaprodi', 'dashboard', ['dosen']));
    }

    public function testMethodSpecificPolicyForFakultasDashboard()
    {
        $this->assertTrue(AccessControl::canAccess('Fakultas', 'dashboard', ['tendik']));
        $this->assertFalse(AccessControl::canAccess('Fakultas', 'index', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Fakultas', 'index', ['fakultas']));
    }

    public function testAdminDashboardIsAdminOnly()
    {
        $this->assertFalse(AccessControl::canAccess('Admin', 'dashboard', ['tendik']));
        $this->assertFalse(AccessControl::canAccess('Admin', 'manageUsers', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Admin', 'dashboard', ['admin']));
        $this->assertTrue(AccessControl::canAccess('Admin', 'manageUsers', ['admin']));
    }

    public function testUnmappedControllerKeepsLegacyBehaviour()
    {
        $this->assertFalse(AccessControl::isControllerMapped('Auth'));
        $this->assertFalse(AccessControl::isControllerMapped('Home'));
        $this->assertTrue(AccessControl::isControllerMapped('Graduate'));
        $this->assertTrue(AccessControl::canAccess('SomeFutureController', 'index', ['tamu']));
    }

    public function testGraduateSkYudisiumIsStaffOnly()
    {
        $this->assertTrue(AccessControl::canAccess('Graduate', 'sk_yudisium', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'sk_yudisium', ['staf']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'sk_yudisium', ['admin']));

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'kaprodi', 'prodi', 'fakultas', 'wisudawan'] as $role) {
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

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'kaprodi', 'prodi', 'fakultas', 'wisudawan'] as $role) {
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

        foreach (['mahasiswa', 'dosen', 'dospem eksternal', 'kaprodi', 'prodi', 'fakultas', 'wisudawan'] as $role) {
            $this->assertFalse(
                AccessControl::canAccess('Graduate', 'skYudisiumStore', [$role]),
                'Role ' . $role . ' must not add SK Yudisium plots.'
            );
        }
    }

    public function testGraduateChooserRemainsAvailableForLocalOnlyRoles()
    {
        foreach (['admin', 'tendik', 'staf', 'dosen', 'mahasiswa', 'wisudawan', 'kaprodi', 'prodi', 'fakultas', 'klien', 'tamu'] as $role) {
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
        $this->assertFalse(AccessControl::canAccess('Graduate', 'mahasiswa', ['wisudawan']));

        $this->assertTrue(AccessControl::canAccess('Graduate', 'wisudawan', ['wisudawan']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'wisudawan', ['mahasiswa']));
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
        $this->assertTrue(AccessControl::canAccess('TenagaKependidikan', 'index', ['staf']));
        $this->assertFalse(AccessControl::canAccess('Mahasiswa', 'index', ['staf']));
    }
}
