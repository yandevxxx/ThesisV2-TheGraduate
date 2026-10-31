<?php

namespace Tests\Unit;

use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;
use App\Models\MahasiswaTerplotModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite for B-08b: Pembatasan akses bagi mahasiswa yang belum terplot SK Yudisium.
 *
 * @internal
 */
final class MahasiswaTerplotAccessTest extends CIUnitTestCase
{
    public function testRoleSeparationMahasiswaAndWisudawan()
    {
        // AC 3: Mahasiswa yang berhasil login tidak otomatis menjadi wisudawan
        $mahasiswaRoles = ['mahasiswa'];

        $this->assertSame('mahasiswa', PortalRouter::graduateFolder($mahasiswaRoles));
        $this->assertNotSame('wisudawan', PortalRouter::graduateFolder($mahasiswaRoles));

        // Mahasiswa cannot access wisudawan dashboard
        $this->assertFalse(
            AccessControl::canAccess('Graduate', 'wisudawan', $mahasiswaRoles),
            'Mahasiswa biasa tidak boleh dapat mengakses dashboard wisudawan'
        );

        // Wisudawan is a distinct role
        $this->assertTrue(
            AccessControl::canAccess('Graduate', 'wisudawan', ['wisudawan'])
        );
    }

    public function testStudentFormsRbacPermittedForMahasiswa()
    {
        // AC 4: Mahasiswa memiliki hak akses RBAC ke pendaftaran_wisuda dan tracer_studi
        $this->assertTrue(AccessControl::canAccess('Graduate', 'pendaftaran_wisuda', ['mahasiswa']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'tracer_studi', ['mahasiswa']));

        // Other roles are rejected
        $this->assertFalse(AccessControl::canAccess('Graduate', 'pendaftaran_wisuda', ['dosen']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'tracer_studi', ['tendik']));
    }

    public function testPlottedModelTableConfiguration()
    {
        // B-08b depends on B-08a table
        $model = new MahasiswaTerplotModel();
        $this->assertSame('mahasiswa_terplot_sk_yudisium', $model->getTable());
        $this->assertContains('npm', $model->allowedFields);
        $this->assertContains('nama', $model->allowedFields);
        $this->assertContains('status', $model->allowedFields);
        $this->assertContains('tanggal_sk_yudisium', $model->allowedFields);
    }

    public function testDateFormatValidationLogic()
    {
        // AC 2: Tanggal harus berformat valid (YYYY-MM-DD)
        $validDate = '2026-10-31';
        $d = \DateTime::createFromFormat('Y-m-d', $validDate);
        $this->assertNotFalse($d);
        $this->assertSame($validDate, $d->format('Y-m-d'));

        $invalidDates = ['31-10-2026', '2026/10/31', 'invalid-date', '2026-02-30'];
        foreach ($invalidDates as $invalid) {
            $parsed = \DateTime::createFromFormat('Y-m-d', $invalid);
            $isValid = ($parsed && $parsed->format('Y-m-d') === $invalid);
            $this->assertFalse($isValid, "{$invalid} should be detected as invalid date");
        }
    }

    public function testSkYudisiumSetTanggalRbacStaffOnly()
    {
        // AC 1: Staf berwenang mengisi/mengubah tanggal SK Yudisium
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumSetTanggal', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumSetTanggal', ['staf']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumSetTanggal', ['admin']));

        // Mahasiswa dan peran lain tidak boleh mengakses endpoint set tanggal
        $this->assertFalse(AccessControl::canAccess('Graduate', 'skYudisiumSetTanggal', ['mahasiswa']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'skYudisiumSetTanggal', ['dosen']));
    }

    public function testSkYudisiumBulkSetTanggalRbacStaffOnly()
    {
        // Bulk Set Tanggal harus berstatus staff-only
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumBulkSetTanggal', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumBulkSetTanggal', ['staf']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumBulkSetTanggal', ['admin']));

        $this->assertFalse(AccessControl::canAccess('Graduate', 'skYudisiumBulkSetTanggal', ['mahasiswa']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'skYudisiumBulkSetTanggal', ['dosen']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'skYudisiumBulkSetTanggal', ['kaprodi']));
    }

    public function testSkYudisiumUpdateStatusRbacStaffOnly()
    {
        // Edit status mahasiswa terplot hanya boleh diakses staf
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumUpdateStatus', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumUpdateStatus', ['staf']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumUpdateStatus', ['admin']));

        $this->assertFalse(AccessControl::canAccess('Graduate', 'skYudisiumUpdateStatus', ['mahasiswa']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'skYudisiumUpdateStatus', ['dosen']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'skYudisiumUpdateStatus', ['kaprodi']));
    }
}

