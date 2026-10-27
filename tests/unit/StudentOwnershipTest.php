<?php

use App\Libraries\StudentOwnership;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class StudentOwnershipTest extends CIUnitTestCase
{
    private const NPM = '2211501234';
    private const OTHER_NPM = '2211509999';

    private function ownership(?string $npm = self::NPM): StudentOwnership
    {
        $resolver = static function (string $resource, $id) {
            $map = [
                'proposal'             => [10 => self::NPM, 11 => self::OTHER_NPM],
                'skripsi'              => [20 => self::NPM, 21 => self::OTHER_NPM],
                'makalah'              => [30 => self::NPM, 31 => self::OTHER_NPM],
                'seminar_proposal'     => [40 => self::NPM, 41 => self::OTHER_NPM],
                'pengajuan_prasidang'  => [50 => self::NPM, 51 => self::OTHER_NPM],
                'pengajuan_sidang'     => [60 => self::NPM, 61 => self::OTHER_NPM],
                'sidang_skripsi'       => [70 => self::NPM, 71 => self::OTHER_NPM],
                'catatan_bimbingan'    => [80 => self::NPM, 81 => self::OTHER_NPM],
                'pembimbing'           => [90 => self::NPM, 91 => self::OTHER_NPM],
                'pengajuan_hardcover'  => [100 => self::NPM, 101 => self::OTHER_NPM],
            ];

            return $map[$resource][(int) $id] ?? null;
        };

        return new StudentOwnership($npm, $resolver);
    }

    public function testOwnsNpm()
    {
        $this->assertTrue($this->ownership()->ownsNpm(self::NPM));
        $this->assertFalse($this->ownership()->ownsNpm(self::OTHER_NPM));
        $this->assertFalse($this->ownership()->ownsNpm(null));
        $this->assertFalse($this->ownership(null)->ownsNpm(self::NPM));
    }

    public function testAuthorizeAllowsOwnRouteParameter()
    {
        $this->assertTrue($this->ownership()->authorize('downloadKhs', [self::NPM]));
        $this->assertTrue($this->ownership()->authorize('cetakFormBimbingan', [self::NPM]));
    }

    public function testAuthorizeRejectsAnotherStudentsRouteParameter()
    {
        $this->assertFalse($this->ownership()->authorize('downloadKhs', [self::OTHER_NPM]));
        $this->assertFalse($this->ownership()->authorize('detailProposal', [11]));
        $this->assertFalse($this->ownership()->authorize('downloadSkripsi', [21]));
    }

    public function testAuthorizeAllowsOwnedRecord()
    {
        $this->assertTrue($this->ownership()->authorize('detailProposal', [10]));
        $this->assertTrue($this->ownership()->authorize('updateSkripsi', [20]));
        $this->assertTrue($this->ownership()->authorize('deleteMakalah', [30]));
        $this->assertTrue($this->ownership()->authorize('downloadDraft', [50]));
        $this->assertTrue($this->ownership()->authorize('downloadDraftFinal', [60]));
        $this->assertTrue($this->ownership()->authorize('hasilSidangSkripsi', [70]));
        $this->assertTrue($this->ownership()->authorize('updateHasilBimbingan', [80]));
    }

    public function testAuthorizeRejectsForeignNpmInPost()
    {
        $this->assertFalse($this->ownership()->authorize('insertProposal', [], [], ['npm' => self::OTHER_NPM]));
        $this->assertTrue($this->ownership()->authorize('insertProposal', [], [], ['npm' => self::NPM]));
    }

    public function testAuthorizeRejectsForeignRecordInPost()
    {
        $this->assertFalse(
            $this->ownership()->authorize('insertPengajuanSidangSkripsi', [], [], ['id_skripsi' => 21])
        );
        $this->assertTrue(
            $this->ownership()->authorize('insertPengajuanSidangSkripsi', [], [], ['id_skripsi' => 20])
        );
    }

    public function testAuthorizeRejectsForeignRecordInGet()
    {
        $this->assertFalse(
            $this->ownership()->authorize('proposal', [], ['id_proposal' => 11])
        );
        $this->assertTrue(
            $this->ownership()->authorize('proposal', [], ['id_proposal' => 10])
        );
    }

    public function testAuthorizeRejectsForeignPembimbingPost()
    {
        $this->assertFalse(
            $this->ownership()->authorize('insertHasilBimbingan', [], [], ['pembimbing' => 91])
        );
        $this->assertTrue(
            $this->ownership()->authorize('insertHasilBimbingan', [], [], ['pembimbing' => 90])
        );
    }

    public function testUnknownParameterisedMethodFailsClosed()
    {
        $this->assertFalse($this->ownership()->authorize('futureMethod', [1]));
    }

    public function testNonScalarOwnerFieldFailsClosed()
    {
        $this->assertFalse(
            $this->ownership()->authorize('insertHasilBimbingan', [], [], ['pembimbing' => [90]])
        );
    }

    public function testParameterCountMismatchFailsClosed()
    {
        $this->assertFalse($this->ownership()->authorize('detailProposal', [10, 11]));
    }

    public function testUnparameterisedRequestWithoutOwnerFieldsIsAllowed()
    {
        $this->assertTrue($this->ownership()->authorize('index', []));
        $this->assertTrue($this->ownership()->authorize('insertProposal', [], [], ['judul' => 'Contoh']));
    }

    public function testMissingNpmFailsClosed()
    {
        $this->assertFalse($this->ownership(null)->authorize('index', []));
        $this->assertFalse($this->ownership(null)->authorize('downloadKhs', [self::NPM]));
    }

    public function testEveryParameterisedMahasiswaMethodHasAnOwnershipRule()
    {
        $reflection = new ReflectionClass(App\Controllers\Mahasiswa::class);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== App\Controllers\Mahasiswa::class) {
                continue;
            }

            if (strpos($method->getName(), '_') === 0 || $method->getNumberOfParameters() === 0) {
                continue;
            }

            $this->assertTrue(
                StudentOwnership::hasParamRule($method->getName()),
                'Missing ownership rule for Mahasiswa::' . $method->getName()
            );
        }
    }

    public function testKnownRoutesAreMapped()
    {
        foreach ([
            'insertPengajuanPenulisanSkripsi',
            'detailProposal',
            'updateProposal',
            'updateVideoSempro',
            'updateHasilBimbingan',
            'deleteHasilBimbingan',
            'detailPengajuanPraSidang',
            'detailPengajuanSidangSkripsi',
            'downloadKhs',
            'deleteKhs',
            'downloadSkripsi',
            'deleteSkripsi',
        ] as $method) {
            $this->assertTrue(StudentOwnership::hasParamRule($method), 'Missing rule: ' . $method);
        }
    }
}
