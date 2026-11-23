<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\ContentSecurityPolicy as ContentSecurityPolicyConfig;

/**
 * Memasang HTTP Security Response Headers (Mode A · Proyek LDAP UY)
 * serta membatasi CORS hanya untuk origin tepercaya milik aplikasi sendiri.
 */
class SecurityHeadersFilter implements FilterInterface
{
    /**
     * Header pelindung dasar yang selalu dipasang di setiap respons HTTP.
     */
    private const BASE_HEADERS = [
        'X-Content-Type-Options'           => 'nosniff',
        'X-Frame-Options'                  => 'SAMEORIGIN',
        'Referrer-Policy'                  => 'strict-origin-when-cross-origin',
        'X-Download-Options'               => 'noopen',
        'X-Permitted-Cross-Domain-Policies' => 'none',
        'Permissions-Policy'               => 'camera=(), microphone=(), geolocation=()',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        if ($request instanceof CLIRequest) {
            return;
        }

        if (strtoupper($request->getMethod()) !== 'OPTIONS') {
            return;
        }

        $origin = trim($request->getHeaderLine('Origin'));
        if ($origin === '') {
            return;
        }

        $allowedOrigin = self::resolveAllowedOrigin($origin);
        $response      = service('response')->setHeader('Vary', 'Origin');

        if ($allowedOrigin === null) {
            self::applySecurityHeaders($response, $request);

            return $response
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 403,
                    'message' => 'Origin lintas domain tidak diizinkan oleh kebijakan CORS.',
                ]);
        }

        $response
            ->setStatusCode(204)
            ->setHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->setHeader('Access-Control-Allow-Credentials', 'true')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-CSRF-TOKEN');

        self::applySecurityHeaders($response, $request);

        return $response;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if ($request instanceof CLIRequest) {
            return;
        }

        self::applySecurityHeaders($response, $request);

        $origin = trim($request->getHeaderLine('Origin'));
        if ($origin !== '') {
            $response->setHeader('Vary', 'Origin');
            $allowedOrigin = self::resolveAllowedOrigin($origin);

            if ($allowedOrigin !== null) {
                $response
                    ->setHeader('Access-Control-Allow-Origin', $allowedOrigin)
                    ->setHeader('Access-Control-Allow-Credentials', 'true')
                    ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                    ->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-CSRF-TOKEN');
            }
        }
    }

    public static function applySecurityHeaders(ResponseInterface $response, RequestInterface $request): void
    {
        if (method_exists($response, 'removeHeader')) {
            $response->removeHeader('X-Powered-By');
        }
        if (function_exists('header_remove') && ! headers_sent()) {
            header_remove('X-Powered-By');
        }

        foreach (self::BASE_HEADERS as $header => $value) {
            $response->setHeader($header, $value);
        }

        $isHttps = (method_exists($request, 'isSecure') && $request->isSecure())
            || (bool) (config('App')->forceGlobalSecureRequests ?? false);

        if ($isHttps) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $hasCsp = method_exists($response, 'hasHeader')
            ? ($response->hasHeader('Content-Security-Policy') || $response->hasHeader('Content-Security-Policy-Report-Only'))
            : false;

        if (! $hasCsp) {
            $cspValue = self::buildCspHeaderValue(config('ContentSecurityPolicy'));
            if ($cspValue !== '') {
                $headerName = ! empty(config('ContentSecurityPolicy')->reportOnly)
                    ? 'Content-Security-Policy-Report-Only'
                    : 'Content-Security-Policy';
                $response->setHeader($headerName, $cspValue);
            }
        }
    }

    /**
     * Mengembalikan origin ternormalisasi hanya jika termasuk origin aplikasi sendiri
     * atau whitelist eksplisit pada security.corsAllowedOrigins. Tidak pernah wildcard '*'.
     */
    public static function resolveAllowedOrigin(string $originHeader): ?string
    {
        $normalized = self::normalizeOrigin($originHeader);
        if ($normalized === null) {
            return null;
        }

        $allowed = self::allowedOrigins();
        foreach ($allowed as $candidate) {
            if (hash_equals($candidate, $normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public static function allowedOrigins(): array
    {
        $origins = [];

        $baseUrl = trim((string) (config('App')->baseURL ?? ''));
        if ($baseUrl !== '') {
            $normBase = self::normalizeOrigin($baseUrl);
            if ($normBase !== null) {
                $origins[] = $normBase;
            }
        }

        $configured = trim((string) (config('Security')->corsAllowedOrigins ?? ''));
        if ($configured !== '') {
            foreach (explode(',', $configured) as $entry) {
                $normEntry = self::normalizeOrigin($entry);
                if ($normEntry !== null && ! in_array($normEntry, $origins, true)) {
                    $origins[] = $normEntry;
                }
            }
        }

        return $origins;
    }

    public static function normalizeOrigin(string $rawOrigin): ?string
    {
        $raw = trim($rawOrigin);
        if ($raw === '' || strtolower($raw) === 'null' || $raw === '*') {
            return null;
        }

        $parts = parse_url($raw);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host   = strtolower($parts['host']);
        $origin = $scheme . '://' . $host;

        if (! empty($parts['port'])) {
            $port        = (int) $parts['port'];
            $isDefaultV4 = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
            if (! $isDefaultV4) {
                $origin .= ':' . $port;
            }
        }

        return $origin;
    }

    public static function buildCspHeaderValue(?ContentSecurityPolicyConfig $config): string
    {
        if ($config === null) {
            return "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'self' blob:";
        }

        $map = [
            'default-src'     => $config->defaultSrc ?? 'self',
            'base-uri'        => $config->baseURI ?? 'self',
            'form-action'     => $config->formAction ?? 'self',
            'frame-ancestors' => $config->frameAncestors ?? 'self',
            'script-src'      => $config->scriptSrc ?? 'self',
            'style-src'       => $config->styleSrc ?? 'self',
            'font-src'        => $config->fontSrc ?? 'self',
            'img-src'         => $config->imageSrc ?? 'self',
            'frame-src'       => $config->frameSrc ?? 'self',
            'object-src'      => $config->objectSrc ?? 'self',
            'connect-src'     => $config->connectSrc ?? 'self',
        ];

        $keywords = ['self', 'none', 'unsafe-inline', 'unsafe-eval'];
        $clauses  = [];

        foreach ($map as $directive => $values) {
            if (empty($values)) {
                continue;
            }

            $list   = is_array($values) ? $values : [$values];
            $tokens = [];

            foreach ($list as $item) {
                $item = trim((string) $item);
                if ($item === '') {
                    continue;
                }
                $tokens[] = in_array($item, $keywords, true) ? "'{$item}'" : $item;
            }

            if ($tokens !== []) {
                $clauses[] = $directive . ' ' . implode(' ', $tokens);
            }
        }

        return implode('; ', $clauses);
    }
}
