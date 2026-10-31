<?php

use App\Libraries\PortalRouter;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PortalRouterTest extends CIUnitTestCase
{
    public function testNormalizeRolesLowercasesDeduplicatesAndSorts()
    {
        $roles = PortalRouter::normalizeRoles([
            'roles' => ['Mahasiswa', 'ADMIN', 'mahasiswa', '  dosen  '],
        ]);

        $this->assertSame(['admin', 'dosen', 'mahasiswa'], $roles);
    }

    public function testNormalizeRolesHandlesMissingPayload()
    {
        $this->assertSame([], PortalRouter::normalizeRoles([]));
        $this->assertSame([], PortalRouter::normalizeRoles(['roles' => 'admin']));
    }

    public function testPrimaryRoleUsesPriority()
    {
        $this->assertSame('admin', PortalRouter::primaryRole(['mahasiswa', 'admin']));
        $this->assertSame('kaprodi', PortalRouter::primaryRole(['dosen', 'kaprodi']));
        $this->assertSame('prodi', PortalRouter::primaryRole(['dosen', 'prodi']));
        $this->assertSame('prodi', PortalRouter::primaryRole(['kaprodi', 'prodi']));
        $this->assertSame('wisudawan', PortalRouter::primaryRole(['mahasiswa', 'wisudawan']));
    }

    public function testRoleLabel()
    {
        $this->assertSame('Dosen Pembimbing Eksternal', PortalRouter::roleLabel(['dospem eksternal']));
        $this->assertSame('Prodi', PortalRouter::roleLabel(['prodi']));
        $this->assertSame('Pengguna', PortalRouter::roleLabel(['role-tidak-dikenal']));
    }

    public function testCanUseGraduate()
    {
        $this->assertTrue(PortalRouter::canUseGraduate(['mahasiswa']));
        $this->assertTrue(PortalRouter::canUseGraduate(['wisudawan', 'mahasiswa']));
        $this->assertTrue(PortalRouter::canUseGraduate(['kaprodi']));
        $this->assertTrue(PortalRouter::canUseGraduate(['prodi']));
        $this->assertTrue(PortalRouter::canUseGraduate(['dospem eksternal']));
        $this->assertTrue(PortalRouter::canUseGraduate(['staf']));
        $this->assertFalse(PortalRouter::canUseGraduate(['klien']));
        $this->assertFalse(PortalRouter::canUseGraduate(['tamu']));
    }

    public function testGraduateFolderMapping()
    {
        $this->assertSame('admin_tendik', PortalRouter::graduateFolder(['admin']));
        $this->assertSame('admin_tendik', PortalRouter::graduateFolder(['tendik']));
        $this->assertSame('admin_tendik', PortalRouter::graduateFolder(['staf']));
        $this->assertSame('admin_tendik', PortalRouter::graduateFolder(['fakultas']));
        $this->assertSame('prodi', PortalRouter::graduateFolder(['kaprodi']));
        $this->assertSame('prodi', PortalRouter::graduateFolder(['prodi']));
        $this->assertSame('wisudawan', PortalRouter::graduateFolder(['wisudawan', 'mahasiswa']));
        $this->assertSame('dosen', PortalRouter::graduateFolder(['dosen']));
        $this->assertSame('dosen', PortalRouter::graduateFolder(['dospem eksternal']));
        $this->assertSame('mahasiswa', PortalRouter::graduateFolder(['mahasiswa']));
        $this->assertNull(PortalRouter::graduateFolder(['klien']));
    }

    public function testThesisLanding()
    {
        $this->assertSame('admin/dashboard', PortalRouter::thesisLanding(['admin']));
        $this->assertSame('fakultas/dashboard', PortalRouter::thesisLanding(['tendik']));
        $this->assertSame('fakultas/dashboard', PortalRouter::thesisLanding(['staf']));
        $this->assertSame('kaprodi/dashboard', PortalRouter::thesisLanding(['kaprodi']));
        $this->assertSame('kaprodi/dashboard', PortalRouter::thesisLanding(['prodi']));
        $this->assertSame('fakultas/dashboard', PortalRouter::thesisLanding(['fakultas']));
        $this->assertSame('dosen/index', PortalRouter::thesisLanding(['dosen']));
        $this->assertSame('dosen/index', PortalRouter::thesisLanding(['dospem eksternal']));
        $this->assertSame('home', PortalRouter::thesisLanding(['mahasiswa']));
        $this->assertSame('home', PortalRouter::thesisLanding(['wisudawan']));
        $this->assertSame('home', PortalRouter::thesisLanding(['klien']));
        $this->assertSame('home', PortalRouter::thesisLanding(['tamu']));
    }

    public function testGraduateLanding()
    {
        $this->assertSame('graduate/admin_tendik', PortalRouter::graduateLanding(['admin']));
        $this->assertSame('graduate/admin_tendik', PortalRouter::graduateLanding(['staf']));
        $this->assertSame('graduate/prodi', PortalRouter::graduateLanding(['kaprodi']));
        $this->assertSame('graduate/prodi', PortalRouter::graduateLanding(['prodi']));
        $this->assertSame('graduate/wisudawan', PortalRouter::graduateLanding(['wisudawan', 'mahasiswa']));
        $this->assertSame('graduate/dosen', PortalRouter::graduateLanding(['dosen']));
        $this->assertSame('graduate/mahasiswa', PortalRouter::graduateLanding(['mahasiswa']));
        $this->assertNull(PortalRouter::graduateLanding(['klien']));
        $this->assertNull(PortalRouter::graduateLanding(['tamu']));
    }
}
