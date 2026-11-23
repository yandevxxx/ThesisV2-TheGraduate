<?php

namespace Tests\Unit;

use App\Libraries\GraduateInputValidator;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Email;
use Config\Routes;

/**
 * Unit tests verifying High-severity security hardening fixes (GRAD-01..GRAD-04).
 *
 * @internal
 */
final class GraduateSecurityHardeningTest extends CIUnitTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = WRITEPATH . 'uploads/test_hardening_' . uniqid('', true);
        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*') ?: [];
            foreach ($files as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
            @rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    public function testGrad01TemplateLogoutUsesPostFormWithCsrfAndGetRouteIsBlocked(): void
    {
        $templatePath = APPPATH . 'Views/The-Graduate-Web/layout/template.php';
        $templateHtml = (string) file_get_contents($templatePath);

        $this->assertStringNotContainsString(
            "href=\"<?= base_url('Auth/logout') ?>\"",
            $templateHtml,
            'Graduate logout modal must not use a GET anchor link.'
        );
        $this->assertStringContainsString("base_url('auth/logout')", $templateHtml);
        $this->assertStringContainsString('method="post"', $templateHtml);
        $this->assertStringContainsString('csrf_field()', $templateHtml);

        $routesContent = (string) file_get_contents(APPPATH . 'Config/Routes.php');
        $this->assertStringContainsString("get('auth/logout', 'Auth::logoutMethodNotAllowed')", $routesContent);
        $this->assertStringContainsString("get('Auth/logout', 'Auth::logoutMethodNotAllowed')", $routesContent);
    }

    public function testGrad02CalonAndMahasiswaWisudawanUseSafeDataAttributesInsteadOfInlineJs(): void
    {
        $files = [
            APPPATH . 'Views/The-Graduate-Web/admin_tendik/calon_wisudawan.php',
            APPPATH . 'Views/The-Graduate-Web/admin_tendik/mahasiswa_wisudawan.php',
        ];

        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            $this->assertStringNotContainsString(
                'onsubmit="return confirm(',
                $content,
                basename($file) . ' must not interpolate PHP variables inside inline JS confirm().'
            );
            $this->assertStringContainsString("esc(\$m['nama'], 'attr')", $content);
            $this->assertStringContainsString("base_url('graduate/berkas/view/' . basename(\$m['pas_foto']))", $content);
        }
    }

    public function testGrad03EmailConfigHasNoHardcodedCredentials(): void
    {
        $emailConfig = new Email();

        $this->assertSame('', $emailConfig->SMTPUser, 'SMTPUser must not be hardcoded in Config\Email.');
        $this->assertSame('', $emailConfig->SMTPPass, 'SMTPPass must not be hardcoded in Config\Email.');
        $this->assertSame('', $emailConfig->fromEmail, 'fromEmail must not be hardcoded in Config\Email.');
    }

    public function testGrad04RejectsMimeSpoofedPdfAndOversizedFiles(): void
    {
        // Spoofed PDF: PHP/text payload renamed with .pdf extension
        $spoofedPath = $this->tempDir . '/fake.pdf';
        file_put_contents($spoofedPath, "<?php echo 'pwned'; ?>\n");

        $fakePdf = new class($spoofedPath, 'fake.pdf') extends UploadedFile {
            public function isValid(): bool
            {
                return true;
            }
        };

        $resFake = GraduateInputValidator::validateUploadedFile($fakePdf, ['pdf'], 2048, 'Berkas');
        $this->assertFalse($resFake['valid'], 'Spoofed PHP file renamed to .pdf must be rejected.');
        $this->assertStringContainsString('tidak sesuai dengan format pdf', (string) $resFake['error']);

        // Valid minimal PDF header
        $validPdfPath = $this->tempDir . '/valid.pdf';
        file_put_contents($validPdfPath, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");

        $validPdf = new class($validPdfPath, 'valid.pdf') extends UploadedFile {
            public function isValid(): bool
            {
                return true;
            }
        };

        $resValid = GraduateInputValidator::validateUploadedFile($validPdf, ['pdf'], 2048, 'Berkas');
        $this->assertTrue($resValid['valid'], 'Valid PDF file should pass validation: ' . ($resValid['error'] ?? ''));
        $this->assertSame('pdf', $resValid['ext']);
    }

    public function testGrad04UploadDirIsUnderWritepathAndResolvePrefersWritepathWithFcpathFallback(): void
    {
        $dir = GraduateInputValidator::graduateUploadDir('berkas');
        $this->assertStringStartsWith(rtrim(WRITEPATH, "\\/"), $dir);

        $fileName      = 'test_resolve_' . uniqid('', true) . '.pdf';
        $writeDir      = GraduateInputValidator::graduateUploadDir('berkas');
        $writeFilePath = $writeDir . $fileName;

        if (! is_dir($writeDir)) {
            mkdir($writeDir, 0755, true);
        }

        try {
            file_put_contents($writeFilePath, '%PDF-1.4');
            $resolved = GraduateInputValidator::resolveGraduateUploadPath('berkas', $fileName);
            $this->assertSame($writeFilePath, $resolved);

            // Traversal / invalid filenames must fail closed
            $this->assertNull(GraduateInputValidator::resolveGraduateUploadPath('berkas', '../secret.pdf'));
            $this->assertNull(GraduateInputValidator::resolveGraduateUploadPath('berkas', 'bad file!.pdf'));
        } finally {
            GraduateInputValidator::deleteGraduateUploadFiles('berkas', $fileName);
            $this->assertFalse(is_file($writeFilePath));
        }
    }

    public function testGrad10FakultasIsReadOnlyForCalonAndMahasiswaWisudawan(): void
    {
        $this->assertFalse(\App\Libraries\AccessControl::canAccess('Graduate', 'approvewisudawan', ['fakultas']));
        $this->assertFalse(\App\Libraries\AccessControl::canAccess('Graduate', 'batalwisudawan', ['fakultas']));
        $this->assertFalse(\App\Libraries\AccessControl::canAccess('PendaftaranWisudaController', 'approvewisudawan', ['fakultas']));
        $this->assertFalse(\App\Libraries\AccessControl::canAccess('PendaftaranWisudaController', 'batalwisudawan', ['fakultas']));

        $controllerSrc = (string) file_get_contents(APPPATH . 'Controllers/TheGraduateWeb/PendaftaranWisudaController.php');
        $this->assertStringContainsString(
            "if (! AccessControl::hasAnyRole(\$roles, ['tendik', 'staf', 'admin'])) {",
            $controllerSrc
        );

        foreach (['calon_wisudawan.php', 'mahasiswa_wisudawan.php'] as $viewName) {
            $viewSrc = (string) file_get_contents(APPPATH . 'Views/The-Graduate-Web/admin_tendik/' . $viewName);
            $this->assertStringContainsString('$canManageWisudawan', $viewSrc);
            $this->assertStringContainsString('Hanya Lihat', $viewSrc);
        }
    }

    public function testGrad05To08ServerGuardsArePresent(): void
    {
        $pendaftaranSrc = (string) file_get_contents(APPPATH . 'Controllers/TheGraduateWeb/PendaftaranWisudaController.php');
        $this->assertStringContainsString("Tempat Lahir Wajib Diisi", $pendaftaranSrc);
        $this->assertStringContainsString("Judul Tugas Akhir Wajib Diisi", $pendaftaranSrc);
        $this->assertStringContainsString("\$this->syncOverallStatus(\$id);", $pendaftaranSrc);

        $sklSrc = (string) file_get_contents(APPPATH . 'Controllers/TheGraduateWeb/SklController.php');
        $this->assertStringContainsString("if (! \$this->isBiodataApproved(\$npm))", $sklSrc);
        $this->assertStringContainsString("if (\$status === 'ditolak' && \$catatan === '')", $sklSrc);
        $this->assertStringContainsString("Tidak Dapat Dihapus", $sklSrc);

        $skySrc = (string) file_get_contents(APPPATH . 'Controllers/TheGraduateWeb/SkYudisiumController.php');
        $this->assertStringContainsString('Perubahan Massal Ditolak', $skySrc);
        $this->assertStringContainsString('GraduateInputValidator::validateNpm((string) $rawNpm)', $skySrc);
    }
}
