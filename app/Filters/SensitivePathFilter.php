<?php

namespace App\Filters;

use App\Libraries\SecurityAuditLogger;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Menolak akses HTTP ke berkas atau direktori sensitif (.env, .git, cadangan, log, dump SQL)
 * baik saat aplikasi dijalankan memakai php spark serve maupun di belakang web server.
 */
class SensitivePathFilter implements FilterInterface
{
    /**
     * Pola segmen direktori internal di root yang tidak boleh dibuka via URL.
     */
    private const BLOCKED_PREFIXES = [
        'writable',
        'app',
        'system',
        'tests',
        'vendor',
        'build',
        'backup',
    ];

    /**
     * Pola berkas di root/subpath yang hanya milik server atau repositori.
     */
    private const BLOCKED_FILES = [
        'composer.json',
        'composer.lock',
        'phpunit.xml',
        'phpunit.xml.dist',
        'spark',
        'builds',
        'env',
        'akun-dummy-testing',
        'web.config',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        if ($request instanceof CLIRequest) {
            return;
        }

        $candidates = [];
        if (method_exists($request, 'getPath')) {
            $candidates[] = (string) $request->getPath();
        }
        $candidates[] = (string) $request->getUri()->getPath();
        if (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) {
            $rawReqUri    = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
            $candidates[] = $rawReqUri;
        }

        $matchedPath = null;
        foreach ($candidates as $candidate) {
            if ($candidate !== '' && self::isSensitivePath($candidate)) {
                $matchedPath = $candidate;
                break;
            }
        }

        if ($matchedPath === null) {
            return;
        }

        $path = $matchedPath;

        try {
            SecurityAuditLogger::logAccessDenied($request, [], 'sensitive_path:' . trim($path, '/'));
        } catch (\Throwable $e) {
            log_message('warning', 'SensitivePathFilter blocked path={path}', ['path' => $path]);
        }

        $wantsJson = (method_exists($request, 'isAJAX') && $request->isAJAX())
            || strpos(strtolower($request->getHeaderLine('Accept')), 'application/json') !== false;

        $response = service('response')->setStatusCode(403);

        if ($wantsJson) {
            return $response->setJSON([
                'status'  => 403,
                'message' => 'Akses ke berkas sensitif ditolak.',
            ]);
        }

        return $response->setBody(
            '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>403 Akses Ditolak</title></head>'
            . '<body><h1>403 Forbidden</h1><p>Akses ke berkas atau direktori sensitif ditolak.</p></body></html>'
        );
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /**
     * Memeriksa apakah path URL mengarah ke dotfile, cadangan, log, dump SQL, atau folder internal.
     */
    public static function isSensitivePath(string $rawPath): bool
    {
        $decoded = rawurldecode($rawPath);
        $decoded = str_replace(['\\', "\0"], ['/', ''], $decoded);
        $trimmed = trim(preg_replace('#/+#', '/', $decoded) ?? '', '/');

        if ($trimmed === '') {
            return false;
        }

        $segments = explode('/', $trimmed);
        $first    = strtolower($segments[0] ?? '');

        if (in_array($first, self::BLOCKED_PREFIXES, true)) {
            return true;
        }

        foreach ($segments as $segment) {
            $lower = strtolower($segment);

            // Semua dotfile/direktori tersembunyi (.env, .git, .htaccess, .env.example, dll.)
            if ($lower !== '' && $lower[0] === '.') {
                return true;
            }
        }

        $last = strtolower((string) end($segments));
        if (in_array($last, self::BLOCKED_FILES, true)) {
            return true;
        }

        if (preg_match('/\.(env|bak|old|orig|save|swp|swo|sql|log|ini|yml|yaml)$/i', $last) === 1) {
            return true;
        }

        if (preg_match('/^(backup|dump|db|database|source|archive)\.(zip|tar|tgz|gz|rar|7z)$/i', $last) === 1) {
            return true;
        }

        // Tangkap berkas cadangan/source dengan akhiran ganda seperti Foo.php~
        if (substr($last, -1) === '~') {
            return true;
        }

        return false;
    }
}
