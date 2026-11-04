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

    public function testExcelTanggalYudisiumParsingValidation()
    {
        $parseDate = function ($rawDateStr) {
            $parsedDate = null;
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rawDateStr, $matches)) {
                $year  = (int) $matches[1];
                $month = (int) $matches[2];
                $day   = (int) $matches[3];
                if (checkdate($month, $day, $year) && $year >= 2000 && $year <= 2100) {
                    $parsedDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                }
            }
            if ($parsedDate === null) {
                if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $rawDateStr, $matches)) {
                    $day   = (int) $matches[1];
                    $month = (int) $matches[2];
                    $year  = (int) $matches[3];
                    if (checkdate($month, $day, $year) && $year >= 2000 && $year <= 2100) {
                        $parsedDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    }
                }
            }
            return $parsedDate;
        };

        // Valid YYYY-MM-DD
        $this->assertSame('2026-08-25', $parseDate('2026-08-25'));
        $this->assertSame('2025-01-15', $parseDate('2025-01-15'));

        // Valid DD/MM/YYYY and DD-MM-YYYY
        $this->assertSame('2026-08-25', $parseDate('25/08/2026'));
        $this->assertSame('2026-08-25', $parseDate('25-08-2026'));

        // Invalid: Februari 30 (tanggal tidak ada di kalender)
        $this->assertNull($parseDate('2026-02-30'));

        // Invalid: April 31 (April hanya ada 30 hari)
        $this->assertNull($parseDate('2026-04-31'));

        // Invalid: Bulan 13
        $this->assertNull($parseDate('2026-13-01'));

        // Invalid: Teks acak
        $this->assertNull($parseDate('bukan-tanggal'));
        $this->assertNull($parseDate('12345'));
    }

    public function testNpmLengthValidationRules()
    {
        $validateNpm = function ($npm) {
            if ($npm === '') {
                return 'NPM wajib diisi';
            }
            if (! ctype_digit($npm)) {
                return 'NPM harus berupa angka';
            }
            $len = strlen($npm);
            if ($len < 10) {
                return 'NPM tidak valid: kurang dari 10 digit (harus 10 digit)';
            }
            if ($len > 10) {
                return 'NPM tidak valid: lebih dari 10 digit (harus 10 digit)';
            }
            return 'valid';
        };

        // Kurang dari 10 digit
        $this->assertSame('NPM tidak valid: kurang dari 10 digit (harus 10 digit)', $validateNpm('202101001'));
        $this->assertSame('NPM tidak valid: kurang dari 10 digit (harus 10 digit)', $validateNpm('123456789'));

        // Lebih dari 10 digit
        $this->assertSame('NPM tidak valid: lebih dari 10 digit (harus 10 digit)', $validateNpm('202101000001'));
        $this->assertSame('NPM tidak valid: lebih dari 10 digit (harus 10 digit)', $validateNpm('12345678901'));

        // Bukan angka
        $this->assertSame('NPM harus berupa angka', $validateNpm('202101000A'));

        // Tepat 10 digit
        $this->assertSame('valid', $validateNpm('2021010001'));
        $this->assertSame('valid', $validateNpm('1234567890'));
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

    public function testSkYudisiumBulkEditRbacStaffOnly()
    {
        // Bulk Edit harus berstatus staff-only
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumBulkEdit', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumBulkEdit', ['staf']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'skYudisiumBulkEdit', ['admin']));

        $this->assertFalse(AccessControl::canAccess('Graduate', 'skYudisiumBulkEdit', ['mahasiswa']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'skYudisiumBulkEdit', ['dosen']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'skYudisiumBulkEdit', ['kaprodi']));
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

    public function testGraduateRegisteredWisudaNpmsReturnsArray()
    {
        $controller = new \App\Controllers\TheGraduateWeb\Graduate();
        $registered = $controller->getRegisteredWisudaNpms();

        $this->assertIsArray($registered);
        $this->assertNotEmpty($registered);
        // Memastikan minimal terdaftar sample mahasiswa pendaftaran wisuda
        $this->assertContains('2021010001', $registered);
        $this->assertContains('2021010006', $registered);
    }

    public function testUnregisteredWisudaStudentsCalculation()
    {
        $registeredNpms = ['2021010001', '2021010002'];

        $plottedList = [
            ['npm' => '2021010001', 'nama' => 'Ahmad Fauzi', 'status' => 'baru', 'tanggal_sk_yudisium' => '2026-10-31'],
            ['npm' => '2021010002', 'nama' => 'Siti Rahmawati', 'status' => 'transfer', 'tanggal_sk_yudisium' => '2026-10-31'],
            ['npm' => '2021010007', 'nama' => 'Budi Sudarsono', 'status' => 'baru', 'tanggal_sk_yudisium' => null],
            ['npm' => '2021010008', 'nama' => 'Dewi Sartika', 'status' => 'baru', 'tanggal_sk_yudisium' => '2026-11-01'],
        ];

        $unregistered = [];
        foreach ($plottedList as $mhs) {
            $npmStr = (string) ($mhs['npm'] ?? '');
            if (! in_array($npmStr, $registeredNpms, true)) {
                $unregistered[] = $mhs;
            }
        }

        $this->assertCount(2, $unregistered);
        $this->assertSame('2021010007', $unregistered[0]['npm']);
        $this->assertSame('2021010008', $unregistered[1]['npm']);
    }
}
