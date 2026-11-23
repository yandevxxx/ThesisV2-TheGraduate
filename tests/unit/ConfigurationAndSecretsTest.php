<?php

namespace Tests\Unit;

use App\Filters\SecurityHeadersFilter;
use App\Filters\SensitivePathFilter;
use CodeIgniter\Config\Factories;
use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Config\AuthLdap;
use Config\Database;
use Config\Email;
use Config\Encryption;
use Config\Filters;
use Config\Security;

/**
 * Pengujian Tugas 2 Mode A: Secrets Management, .env.example, .gitignore,
 * Penutupan File Sensitif (403/404), Security Headers, dan CORS.
 *
 * @internal
 */
final class ConfigurationAndSecretsTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Factories::reset();
        Services::resetSingle('response');
    }

    protected function tearDown(): void
    {
        Factories::reset();
        Services::resetSingle('response');
        parent::tearDown();
    }

    public function testConfigDefaultsDoNotHardcodeSecrets(): void
    {
        $authLdapDefaults   = (new \ReflectionClass(AuthLdap::class))->getDefaultProperties();
        $databaseDefaults   = (new \ReflectionClass(Database::class))->getDefaultProperties();
        $encryptionDefaults = (new \ReflectionClass(Encryption::class))->getDefaultProperties();
        $emailDefaults      = (new \ReflectionClass(Email::class))->getDefaultProperties();
        $appDefaults        = (new \ReflectionClass(App::class))->getDefaultProperties();
        $securityDefaults   = (new \ReflectionClass(Security::class))->getDefaultProperties();

        $this->assertSame('', $authLdapDefaults['bindDn']);
        $this->assertSame('', $authLdapDefaults['bindPassword']);
        $this->assertSame('', $databaseDefaults['default']['password']);
        $this->assertSame('', $encryptionDefaults['key']);
        $this->assertSame('', $emailDefaults['SMTPUser']);
        $this->assertSame('', $emailDefaults['SMTPPass']);
        $this->assertSame('', $emailDefaults['fromEmail']);
        $this->assertTrue($appDefaults['CSPEnabled']);
        $this->assertSame('', $securityDefaults['corsAllowedOrigins']);
    }

    public function testGitignoreAndEnvExampleProtectSecrets(): void
    {
        $gitignorePath  = ROOTPATH . '.gitignore';
        $envExamplePath = ROOTPATH . '.env.example';

        $this->assertFileExists($gitignorePath);
        $this->assertFileExists($envExamplePath);

        $gitignore = (string) file_get_contents($gitignorePath);
        foreach (['.env', '.env.*', '!.env.example', '*.bak', '*.sql', '*.log', 'Akun-Dummy-Testing'] as $pattern) {
            $this->assertStringContainsString($pattern, $gitignore);
        }

        $envExample = (string) file_get_contents($envExamplePath);
        $secretKeys = [
            'database.default.password',
            'authldap.bindPassword',
            'encryption.key',
            'email.SMTPUser',
            'email.SMTPPass',
        ];

        foreach ($secretKeys as $key) {
            $this->assertMatchesRegularExpression(
                '/^' . preg_quote($key, '/') . '\s*=\s*(\'\'|""|)$/m',
                $envExample,
                "{$key} di .env.example wajib dibiarkan kosong tanpa nilai rahasia."
            );
        }
    }

    public function testHtaccessFilesDisableIndexesAndBlockSensitivePaths(): void
    {
        $rootHtaccess   = ROOTPATH . '.htaccess';
        $publicHtaccess = PUBLICPATH . '.htaccess';

        $this->assertFileExists($rootHtaccess);
        $this->assertFileExists($publicHtaccess);

        foreach ([$rootHtaccess, $publicHtaccess] as $file) {
            $content = (string) file_get_contents($file);
            $this->assertStringContainsString('Options All -Indexes', $content);
            $this->assertStringContainsString('ServerSignature Off', $content);
            $this->assertStringContainsString('(^|/)\\.', $content);
            $this->assertStringContainsString('Require all denied', $content);
        }
    }

    public function testSensitivePathFilterBlocksSecretsBackupsLogsAndAllowsNormalRoutes(): void
    {
        $blockedPaths = [
            '/.env',
            '/.env.example',
            '/%2eenv',
            '/.git/config',
            '/.htaccess',
            '/backup.zip',
            '/backup/ProductTemplate.java.bak',
            '/thesis_v2.sql',
            '/writable/logs/log-2026-11-19.log',
            '/app/Config/Database.php',
            '/composer.json',
            '/Akun-Dummy-Testing',
            '/ProductTemplate.java~',
        ];

        $filter = new SensitivePathFilter();
        $config = new App();

        foreach ($blockedPaths as $path) {
            $this->assertTrue(SensitivePathFilter::isSensitivePath($path), "{$path} harus dianggap sensitif.");

            $uri     = new URI('http://localhost:8080' . $path);
            $request = new IncomingRequest($config, $uri, null, new UserAgent());
            $request->setPath($path);
            $request->setMethod('get');

            $result = $filter->before($request);
            $this->assertInstanceOf(Response::class, $result, "Request ke {$path} harus diblokir.");
            $this->assertSame(403, $result->getStatusCode(), "Request ke {$path} harus mengembalikan HTTP 403.");
        }

        $allowedPaths = [
            '/',
            '/auth/login',
            '/graduate/choose',
            '/graduate/biodata_wisuda',
            '/graduate/berkas/view/pasfoto.png',
            '/graduate/berkas_wisuda/download/lampiran.zip',
            '/graduate/skl',
        ];

        foreach ($allowedPaths as $path) {
            $this->assertFalse(SensitivePathFilter::isSensitivePath($path), "{$path} adalah rute sah dan tidak boleh diblokir.");

            $uri     = new URI('http://localhost:8080' . $path);
            $request = new IncomingRequest($config, $uri, null, new UserAgent());
            $request->setPath($path);
            $request->setMethod('get');

            $this->assertNull($filter->before($request), "Rute sah {$path} harus lolos SensitivePathFilter.");
        }
    }

    public function testSecurityHeadersFilterSetsRequiredHeadersAndStrictCors(): void
    {
        $filtersConfig = new Filters();
        $this->assertContains('sensitivepath', $filtersConfig->globals['before']);
        $this->assertContains('secureheaders', $filtersConfig->globals['before']);
        $this->assertContains('secureheaders', $filtersConfig->globals['after']);
        $this->assertSame(SecurityHeadersFilter::class, $filtersConfig->aliases['secureheaders']);

        $appConfig          = new App();
        $appConfig->baseURL = 'http://localhost:8080/';
        Factories::injectMock('config', 'App', $appConfig);

        $secConfig                     = new Security();
        $secConfig->corsAllowedOrigins = 'https://portal-fe.kampus.test';
        Factories::injectMock('config', 'Security', $secConfig);

        $filter = new SecurityHeadersFilter();

        // 1. Request biasa pada HTTP lokal
        $request  = new IncomingRequest($appConfig, new URI('http://localhost:8080/graduate/choose'), null, new UserAgent());
        $response = new Response($appConfig);
        $response->setHeader('X-Powered-By', 'PHP/8.3');

        $filter->after($request, $response);

        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        $this->assertSame('noopen', $response->getHeaderLine('X-Download-Options'));
        $this->assertSame('none', $response->getHeaderLine('X-Permitted-Cross-Domain-Policies'));
        $this->assertStringContainsString('camera=()', $response->getHeaderLine('Permissions-Policy'));
        $this->assertStringContainsString("frame-ancestors 'self'", $response->getHeaderLine('Content-Security-Policy'));
        $this->assertFalse($response->hasHeader('X-Powered-By'));
        $this->assertFalse($response->hasHeader('Strict-Transport-Security'));
        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));

        // 2. HTTPS request menambahkan Strict-Transport-Security
        $_SERVER['HTTPS'] = 'on';
        $httpsRequest     = new IncomingRequest($appConfig, new URI('https://localhost/graduate/choose'), null, new UserAgent());
        $httpsResponse    = new Response($appConfig);
        $filter->after($httpsRequest, $httpsResponse);
        unset($_SERVER['HTTPS']);
        $this->assertStringContainsString('max-age=31536000', $httpsResponse->getHeaderLine('Strict-Transport-Security'));

        // 3. Untrusted CORS Origin tidak dipantulkan & preflight OPTIONS ditolak 403
        $evilRequest = new IncomingRequest($appConfig, new URI('http://localhost:8080/api/kaprodi/bidang'), null, new UserAgent());
        $evilRequest->setMethod('options');
        $evilRequest->setHeader('Origin', 'https://attacker.example');

        $preflightBlocked = $filter->before($evilRequest);
        $this->assertInstanceOf(Response::class, $preflightBlocked);
        $this->assertSame(403, $preflightBlocked->getStatusCode());
        $this->assertSame('', $preflightBlocked->getHeaderLine('Access-Control-Allow-Origin'));

        // 4. Null origin juga ditolak
        $nullOriginReq = new IncomingRequest($appConfig, new URI('http://localhost:8080/api/kaprodi/bidang'), null, new UserAgent());
        $nullOriginReq->setMethod('get');
        $nullOriginReq->setHeader('Origin', 'null');
        $nullOriginResp = new Response($appConfig);
        $filter->after($nullOriginReq, $nullOriginResp);
        $this->assertSame('', $nullOriginResp->getHeaderLine('Access-Control-Allow-Origin'));

        // 5. Origin whitelist eksplisit diizinkan tanpa wildcard '*'
        $trustedReq = new IncomingRequest($appConfig, new URI('http://localhost:8080/api/kaprodi/bidang'), null, new UserAgent());
        $trustedReq->setMethod('get');
        $trustedReq->setHeader('Origin', 'https://portal-fe.kampus.test');
        $trustedResp = new Response($appConfig);
        $filter->after($trustedReq, $trustedResp);
        $this->assertSame('https://portal-fe.kampus.test', $trustedResp->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $trustedResp->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('Origin', $trustedResp->getHeaderLine('Vary'));
    }
}
