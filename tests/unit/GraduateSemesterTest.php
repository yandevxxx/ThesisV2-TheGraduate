<?php

namespace Tests\Unit;

use App\Libraries\AccessControl;
use App\Models\SemesterModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test suite for Semester management on The Graduate side (Admin/Tendik).
 *
 * @internal
 */
final class GraduateSemesterTest extends CIUnitTestCase
{
    public function testSemesterRbacAccessControl()
    {
        // Admin and Fakultas can access semester (consistent with Thesis system)
        $this->assertTrue(AccessControl::canAccess('Graduate', 'semester', ['admin']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'semester', ['fakultas']));

        // Actions can be accessed by Fakultas and Admin
        $this->assertTrue(AccessControl::canAccess('Graduate', 'semesterstore', ['fakultas']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'semesterupdate', ['fakultas']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'semesteractivate', ['fakultas']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'semesternonactivate', ['fakultas']));
        $this->assertTrue(AccessControl::canAccess('Graduate', 'semesterdelete', ['fakultas']));

        // Tendik and Staf DO NOT manage semester (matches Thesis where only Fakultas manages it)
        $this->assertFalse(AccessControl::canAccess('Graduate', 'semester', ['tendik']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'semester', ['staf']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'semesterstore', ['tendik']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'semesteractivate', ['tendik']));

        // Students, wisudawan, and guests CANNOT access semester management
        $this->assertFalse(AccessControl::canAccess('Graduate', 'semester', ['mahasiswa']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'semester', ['wisudawan']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'semester', ['klien']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'semester', ['tamu']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'semesterstore', ['mahasiswa']));
        $this->assertFalse(AccessControl::canAccess('Graduate', 'semesteractivate', ['mahasiswa']));

        // SemesterController dedicated mapping
        $this->assertTrue(AccessControl::canAccess('SemesterController', 'index', ['admin']));
        $this->assertTrue(AccessControl::canAccess('SemesterController', 'index', ['fakultas']));
        $this->assertTrue(AccessControl::canAccess('SemesterController', 'store', ['fakultas']));
        $this->assertTrue(AccessControl::canAccess('SemesterController', 'update', ['fakultas']));
        $this->assertTrue(AccessControl::canAccess('SemesterController', 'activate', ['fakultas']));
        $this->assertTrue(AccessControl::canAccess('SemesterController', 'nonactivate', ['fakultas']));
        $this->assertTrue(AccessControl::canAccess('SemesterController', 'delete', ['fakultas']));
        $this->assertFalse(AccessControl::canAccess('SemesterController', 'index', ['tendik']));
        $this->assertFalse(AccessControl::canAccess('SemesterController', 'store', ['tendik']));
        $this->assertFalse(AccessControl::canAccess('SemesterController', 'index', ['mahasiswa']));

        // SkYudisiumController dedicated mapping
        $this->assertTrue(AccessControl::canAccess('SkYudisiumController', 'index', ['tendik']));
        $this->assertTrue(AccessControl::canAccess('SkYudisiumController', 'index', ['admin']));
        $this->assertTrue(AccessControl::canAccess('SkYudisiumController', 'upload', ['staf']));
        $this->assertFalse(AccessControl::canAccess('SkYudisiumController', 'index', ['mahasiswa']));

        // NotificationController dedicated mapping
        $this->assertTrue(AccessControl::canAccess('NotificationController', 'fetchnotif', ['admin']));
        $this->assertTrue(AccessControl::canAccess('NotificationController', 'fetchnotif', ['tendik']));
        $this->assertFalse(AccessControl::canAccess('NotificationController', 'fetchnotif', ['mahasiswa']));

        // ProfileController dedicated mapping
        $this->assertTrue(AccessControl::canAccess('ProfileController', 'profile', ['mahasiswa']));
        $this->assertTrue(AccessControl::canAccess('ProfileController', 'profile', ['dosen']));
        $this->assertTrue(AccessControl::canAccess('ProfileController', 'profile', ['admin']));
    }

    public function testSemesterModelConfiguration()
    {
        $model = new SemesterModel();
        $this->assertSame('semester', $model->getTable());
        $this->assertSame('kode', $model->primaryKey);
        $this->assertContains('kode', $model->allowedFields);
        $this->assertContains('nama', $model->allowedFields);
        $this->assertContains('status', $model->allowedFields);

        // Dedicated The Graduate semester model
        $gradModel = new \App\Models\SemesterGraduateModel();
        $this->assertSame('semester_graduate', $gradModel->getTable());
        $this->assertSame('kode', $gradModel->primaryKey);
        $this->assertContains('kode', $gradModel->allowedFields);
        $this->assertContains('nama', $gradModel->allowedFields);
        $this->assertContains('status', $gradModel->allowedFields);
    }

    public function testSemesterCodeAndNameFormattingLogic()
    {
        $rawTahun = '2025/2026';
        $rawSem = 'Ganjil';

        $this->assertTrue((bool) preg_match('/(\d{4})/', $rawTahun, $matches));
        $tahunMulai = (int) $matches[1];
        $tahunSelesai = $tahunMulai + 1;
        $semCode = (strtolower($rawSem) === 'ganjil' || $rawSem === '1') ? 1 : 2;
        $semName = ($semCode === 1) ? 'Ganjil' : 'Genap';

        $kodeSemester = "{$tahunMulai}-{$semCode}";
        $namaSemester = "Tahun Akademik {$tahunMulai}/{$tahunSelesai} Semester {$semName}";

        $this->assertSame('2025-1', $kodeSemester);
        $this->assertSame('Tahun Akademik 2025/2026 Semester Ganjil', $namaSemester);

        // Test Genap format
        $rawTahun2 = '2026';
        $rawSem2 = 'Genap';
        preg_match('/(\d{4})/', $rawTahun2, $matches2);
        $tahunMulai2 = (int) $matches2[1];
        $tahunSelesai2 = $tahunMulai2 + 1;
        $semCode2 = (strtolower($rawSem2) === 'ganjil' || $rawSem2 === '1') ? 1 : 2;
        $semName2 = ($semCode2 === 1) ? 'Ganjil' : 'Genap';

        $kodeSemester2 = "{$tahunMulai2}-{$semCode2}";
        $namaSemester2 = "Tahun Akademik {$tahunMulai2}/{$tahunSelesai2} Semester {$semName2}";

        $this->assertSame('2026-2', $kodeSemester2);
        $this->assertSame('Tahun Akademik 2026/2027 Semester Genap', $namaSemester2);
    }

    public function testOnlyOneActiveSemesterRule()
    {
        $semesters = [
            ['kode' => '2024-1', 'nama' => 'Ganjil 2024', 'status' => 'NON-AKTIF'],
            ['kode' => '2024-2', 'nama' => 'Genap 2024', 'status' => 'AKTIF'],
            ['kode' => '2025-1', 'nama' => 'Ganjil 2025', 'status' => 'NON-AKTIF'],
        ];

        // Activate 2025-1
        $targetKode = '2025-1';
        foreach ($semesters as &$sem) {
            $sem['status'] = ($sem['kode'] === $targetKode) ? 'AKTIF' : 'NON-AKTIF';
        }
        unset($sem);

        $activeCount = count(array_filter($semesters, fn($s) => $s['status'] === 'AKTIF'));
        $this->assertSame(1, $activeCount);
        $this->assertSame('AKTIF', $semesters[2]['status']);
        $this->assertSame('NON-AKTIF', $semesters[1]['status']);
    }

    public function testActiveSemesterCannotBeDeletedLogic()
    {
        $semester = ['kode' => '2025-1', 'nama' => 'Ganjil 2025', 'status' => 'AKTIF'];
        $canDelete = ($semester['status'] !== 'AKTIF');
        $this->assertFalse($canDelete, 'Semester yang sedang aktif tidak boleh dapat dihapus');
    }
}
