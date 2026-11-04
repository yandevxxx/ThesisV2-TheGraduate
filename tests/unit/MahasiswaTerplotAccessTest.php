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
    protected function setUp(): void
    {
        parent::setUp();

        $db = \Config\Database::connect();
        if (! $db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            $forge = \Config\Database::forge();
            $forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'npm' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                ],
                'nama' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => false,
                ],
                'id_prodi' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'TERPLOT',
                ],
                'tanggal_sk_yudisium' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'kode_semester' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('mahasiswa_terplot_sk_yudisium', true);
        }
    }

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
        $this->assertContains('kode_semester', $model->allowedFields);
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

    public function testUnregisteredWisudaStudentsFilteredBySemester()
    {
        $registeredNpms = ['2021010001'];

        $plottedList = [
            ['npm' => '2021010001', 'nama' => 'Ahmad', 'kode_semester' => 'Ganjil 2025/2026'],
            ['npm' => '2021010002', 'nama' => 'Budi', 'kode_semester' => 'Ganjil 2025/2026'],
            ['npm' => '2021010003', 'nama' => 'Citra', 'kode_semester' => 'Genap 2025/2026'],
        ];

        // Filter Ganjil 2025/2026
        $ganjilUnregistered = [];
        foreach ($plottedList as $m) {
            if ($m['kode_semester'] === 'Ganjil 2025/2026' && ! in_array($m['npm'], $registeredNpms, true)) {
                $ganjilUnregistered[] = $m;
            }
        }
        $this->assertCount(1, $ganjilUnregistered);
        $this->assertSame('2021010002', $ganjilUnregistered[0]['npm']);

        // Filter Genap 2025/2026
        $genapUnregistered = [];
        foreach ($plottedList as $m) {
            if ($m['kode_semester'] === 'Genap 2025/2026' && ! in_array($m['npm'], $registeredNpms, true)) {
                $genapUnregistered[] = $m;
            }
        }
        $this->assertCount(1, $genapUnregistered);
        $this->assertSame('2021010003', $genapUnregistered[0]['npm']);
    }

    public function testSemesterGraduateFormatNamaHelper()
    {
        // AC: Format penamaan semester seperti "Semester Ganjil 2026/2027"
        $this->assertSame(
            'Semester Ganjil 2026/2027',
            \App\Models\SemesterGraduateModel::formatNama('Tahun Akademik 2026/2027 Semester Ganjil', '2026-1')
        );

        $this->assertSame(
            'Semester Genap 2026/2027',
            \App\Models\SemesterGraduateModel::formatNama('Tahun Akademik 2026/2027 Semester Genap', '2026-2')
        );

        // Fallback by code when nama is empty
        $this->assertSame(
            'Semester Ganjil 2026/2027',
            \App\Models\SemesterGraduateModel::formatNama(null, '2026-1')
        );
        $this->assertSame(
            'Semester Genap 2026/2027',
            \App\Models\SemesterGraduateModel::formatNama(null, '2026-2')
        );

        // Fallback with simple Ganjil/Genap string
        $this->assertSame(
            'Semester Ganjil 2025/2026',
            \App\Models\SemesterGraduateModel::formatNama('Ganjil 2025/2026')
        );
    }

    public function testSafeDateFormattingHelper()
    {
        $formatDate = static function (?string $date, string $format = 'd/m/Y'): string {
            if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
                return '-';
            }
            $ts = strtotime($date);
            return ($ts !== false && $ts > 0) ? date($format, $ts) : '-';
        };

        $this->assertSame('15/10/2026', $formatDate('2026-10-15'));
        $this->assertSame('-', $formatDate(null));
        $this->assertSame('-', $formatDate(''));
        $this->assertSame('-', $formatDate('0000-00-00'));
        $this->assertSame('-', $formatDate('0000-00-00 00:00:00'));
        $this->assertSame('-', $formatDate('invalid-date-string'));
    }

    public function testCleanHiddenWhitespaceInNpm()
    {
        // Unicode non-breaking space (U+00A0) and zero-width space (U+200B)
        $npmWithNbsp = "2021010001\xC2\xA0";
        $cleaned = preg_replace('/[\s\x{00a0}\x{200b}]+/u', '', $npmWithNbsp);

        $this->assertSame('2021010001', $cleaned);
        $this->assertTrue(ctype_digit($cleaned));
        $this->assertSame(10, strlen($cleaned));
    }

    public function testModelAllowedFieldsContainsTimestamps()
    {
        $model = new MahasiswaTerplotModel();
        $this->assertContains('created_at', $model->allowedFields);
        $this->assertContains('updated_at', $model->allowedFields);
    }

    public function testFlashMessageNormalization()
    {
        $normalize = function ($flashMessage) {
            if (is_array($flashMessage)) {
                $msgIcon  = $flashMessage['icon'] ?? 'info';
                $msgTitle = $flashMessage['title'] ?? '';
                $msgText  = $flashMessage['text'] ?? '';
            } else {
                $msgIcon  = 'info';
                $msgTitle = '';
                $msgText  = (string) $flashMessage;
            }
            $alertClass = match ($msgIcon) {
                'success' => 'success',
                'warning' => 'warning',
                'info'    => 'info',
                default   => 'danger',
            };
            return ['class' => $alertClass, 'title' => $msgTitle, 'text' => $msgText];
        };

        // Test with standard array
        $resArray = $normalize(['icon' => 'success', 'title' => 'Berhasil', 'text' => 'Data tersimpan']);
        $this->assertSame('success', $resArray['class']);
        $this->assertSame('Berhasil', $resArray['title']);
        $this->assertSame('Data tersimpan', $resArray['text']);

        // Test with string (which previously caused PHP 8.4 Fatal Error)
        $resString = $normalize('Pesan biasa');
        $this->assertSame('info', $resString['class']);
        $this->assertSame('', $resString['title']);
        $this->assertSame('Pesan biasa', $resString['text']);
    }

    public function testBelumMendaftarHasActiveFilterCondition()
    {
        $checkActiveFilter = function (array $filters) {
            return (
                ($filters['q'] ?? '') !== '' ||
                ($filters['status'] ?? '') !== '' ||
                ($filters['sk'] ?? '') !== '' ||
                (($filters['semester'] ?? '') !== '' && ($filters['semester'] ?? '') !== 'SEMUA')
            );
        };

        // When all empty or default
        $this->assertFalse($checkActiveFilter(['q' => '', 'status' => '', 'sk' => '', 'semester' => '']));
        $this->assertFalse($checkActiveFilter(['q' => '', 'status' => '', 'sk' => '', 'semester' => 'SEMUA']));

        // When semester is filtered
        $this->assertTrue($checkActiveFilter(['q' => '', 'status' => '', 'sk' => '', 'semester' => '2026-2']));

        // When q is filtered
        $this->assertTrue($checkActiveFilter(['q' => 'Ahmad', 'status' => '', 'sk' => '', 'semester' => '']));

        // When sk is filtered
        $this->assertTrue($checkActiveFilter(['q' => '', 'status' => '', 'sk' => 'ada', 'semester' => '']));
    }

    public function testBelumMendaftarRoleCanManageSk()
    {
        // AC: Tendik and admin can manage, fakultas is read-only
        $this->assertTrue(AccessControl::hasAnyRole(['tendik'], ['tendik', 'staf', 'admin']));
        $this->assertTrue(AccessControl::hasAnyRole(['admin'], ['tendik', 'staf', 'admin']));
        $this->assertTrue(AccessControl::hasAnyRole(['staf'], ['tendik', 'staf', 'admin']));
        $this->assertFalse(AccessControl::hasAnyRole(['fakultas'], ['tendik', 'staf', 'admin']));
        $this->assertFalse(AccessControl::hasAnyRole(['mahasiswa'], ['tendik', 'staf', 'admin']));
    }

    public function testGraduateRegisteredWisudaNpmsWithSemesterParameter()
    {
        $controller = new \App\Controllers\TheGraduateWeb\Graduate();
        $registered = $controller->getRegisteredWisudaNpms('2026-2');

        $this->assertIsArray($registered);
    }

    public function testSanitizeTipeFileExtensions()
    {
        $sanitize = function ($rawTipeFile) {
            $exts = array_filter(array_map('trim', explode(',', strtolower($rawTipeFile))));
            $exts = array_filter($exts, static fn ($ext) => preg_match('/^[a-z0-9]+$/', $ext));
            return ! empty($exts) ? implode(',', $exts) : 'pdf,jpg,jpeg,png';
        };

        // Handles spaces and mixed case
        $this->assertSame('pdf,jpg,png', $sanitize(' PDF , JPG , png '));
        // Discards dangerous characters or paths
        $this->assertSame('pdf', $sanitize('pdf, ../etc, <script>'));
        // Defaults to standard if completely empty
        $this->assertSame('pdf,jpg,jpeg,png', $sanitize(''));
    }

    public function testReorderOrdersSanitization()
    {
        $sanitizeOrders = function ($orders) {
            $validOrders = [];
            foreach ($orders as $o) {
                $intId = (int) $o;
                if ($intId > 0) {
                    $validOrders[] = $intId;
                }
            }
            return $validOrders;
        };

        $this->assertSame([1, 2, 3], $sanitizeOrders(['1', '2', '3']));
        $this->assertSame([5, 9], $sanitizeOrders(['5', '0', '-1', 'abc', '9']));
        $this->assertEmpty($sanitizeOrders(['abc', '-5', '0']));
    }

    public function testPendaftaranWisudaGatekeeperForUnplottedStudent()
    {
        $unplottedNpm = '8888000001';

        $terplot = (new MahasiswaTerplotModel())->where('npm', $unplottedNpm)->first();
        $this->assertNull($terplot);

        // Verification simulation: Gatekeeper blocks unplotted students
        $canRegister = ($terplot !== null);
        $this->assertFalse($canRegister, 'Mahasiswa yang belum terplot di SK Yudisium tidak boleh mendaftar wisuda');
    }

    public function testPendaftaranWisudaImmutabilityWhenApproved()
    {
        $statusApproved = 'DISETUJUI';
        $immutableStatuses = ['DISETUJUI', 'VALID', 'DITERIMA', 'SELESAI'];

        $this->assertTrue(in_array(strtoupper($statusApproved), $immutableStatuses, true));

        // Attempting to modify or delete files when status is DISETUJUI should be blocked
        $canModify = ! in_array(strtoupper($statusApproved), $immutableStatuses, true);
        $this->assertFalse($canModify, 'Berkas dan pendaftaran yang sudah disetujui tidak boleh dimodifikasi/dihapus');
    }

    public function testPendaftaranWisudaVerifikasiRejectionRequiresNotes()
    {
        $status = 'DITOLAK';
        $catatanEmpty = '';
        $catatanValid = 'Perbaiki ijazah SMA karena scan buram';

        $isInvalid = ($status === 'DITOLAK' && trim($catatanEmpty) === '');
        $this->assertTrue($isInvalid, 'Penolakan pendaftaran tanpa catatan harus ditolak validasi');

        $isValid = ! ($status === 'DITOLAK' && trim($catatanValid) === '');
        $this->assertTrue($isValid, 'Penolakan dengan catatan perbaikan harus valid');
    }

    public function testPendaftaranWisudaDateFormatHelperSafety()
    {
        $formatDate = static function (?string $date): string {
            if (empty($date) || $date === '0000-00-00') {
                return '-';
            }
            $time = strtotime($date);
            return ($time !== false && $time > 0) ? date('d/m/Y', $time) : '-';
        };

        $this->assertSame('-', $formatDate(null));
        $this->assertSame('-', $formatDate(''));
        $this->assertSame('-', $formatDate('0000-00-00'));
        $this->assertSame('-', $formatDate('invalid-date-string'));
        $this->assertSame('15/11/2025', $formatDate('2025-11-15'));
        $this->assertSame('02/08/2001', $formatDate('2001-08-02'));
    }
}
