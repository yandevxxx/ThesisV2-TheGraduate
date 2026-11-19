<?php

namespace App\Libraries;

use App\Models\AuthLogModel;
use CodeIgniter\HTTP\RequestInterface;
use Config\Services;

/**
 * Pencatat event keamanan terpusat (Mode A · The Graduate).
 *
 * Mencatat login berhasil/gagal, logout, akses ditolak (403), perubahan peran,
 * serta kegagalan LDAP/database dengan struktur standar:
 * waktu, npm/identitas, ip, aksi, hasil, dan event_id — bebas dari password,
 * cookie sesi, token, dan respons LDAP lengkap.
 */
final class SecurityAuditLogger
{
    /**
     * Catat event keamanan terstruktur ke log server & tabel auth_logs.
     */
    public static function log(string $event, string $hasil, ?string $npmOrUser, string $aksi, array $meta = []): string
    {
        $eventId = isset($meta['event_id']) && is_string($meta['event_id']) && $meta['event_id'] !== ''
            ? SecurityLogSanitizer::sanitizeText($meta['event_id'])
            : SecurityLogSanitizer::newEventId();

        $waktu = date('Y-m-d H:i:s');
        $npm   = substr(SecurityLogSanitizer::sanitizeText($npmOrUser ?: 'anonymous'), 0, 100);
        $ip    = isset($meta['ip']) && is_string($meta['ip']) && $meta['ip'] !== ''
            ? SecurityLogSanitizer::sanitizeText($meta['ip'])
            : self::resolveIpAddress($meta['request'] ?? null);

        $cleanEvent = substr(SecurityLogSanitizer::sanitizeText($event), 0, 40);
        $cleanAksi  = substr(SecurityLogSanitizer::sanitizeText($aksi), 0, 150);
        $cleanHasil = substr(SecurityLogSanitizer::sanitizeText($hasil), 0, 40);

        $extraMeta = $meta;
        unset($extraMeta['request'], $extraMeta['ip'], $extraMeta['event_id']);
        $safeMeta = SecurityLogSanitizer::sanitizeContext($extraMeta);

        $logPayload = [
            'event_id' => $eventId,
            'waktu'    => $waktu,
            'npm'      => $npm,
            'ip'       => $ip,
            'aksi'     => $cleanAksi,
            'hasil'    => $cleanHasil,
            'event'    => $cleanEvent,
        ];

        if ($safeMeta !== []) {
            $logPayload['detail'] = $safeMeta;
        }

        $encoded = json_encode($logPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        log_message('notice', '[SECURITY_EVENT] ' . ($encoded !== false ? $encoded : $eventId));

        self::persistToAuthLogs(
            $cleanEvent,
            $cleanHasil,
            $npm,
            isset($meta['akun_id']) ? (int) $meta['akun_id'] : null,
            (string) ($safeMeta['reason'] ?? $cleanAksi),
            $eventId,
            $ip,
            (string) ($safeMeta['auth_method'] ?? 'system'),
            isset($meta['roles']) && is_array($meta['roles']) ? $meta['roles'] : [],
            $meta['request'] ?? null,
            $waktu
        );

        return $eventId;
    }

    public static function logAccessDenied(?RequestInterface $request, array $roles = [], string $aksi = '', ?string $actorOverride = null): string
    {
        $userSession = null;
        try {
            $userSession = session()->get('user_session');
        } catch (\Throwable $e) {
            $userSession = null;
        }

        $npm = $actorOverride;
        if ($npm === null && is_array($userSession)) {
            $npm = (string) ($userSession['npm'] ?? $userSession['username'] ?? $userSession['email'] ?? 'guest');
        }
        $npm = $npm ?: 'guest';

        $resolvedAksi = $aksi !== '' ? $aksi : self::describeRequestAction($request, 'access_denied');
        $akunId       = is_array($userSession) && isset($userSession['akun_id']) ? (int) $userSession['akun_id'] : null;

        return self::log('access_denied', 'denied_403', $npm, $resolvedAksi, [
            'request' => $request,
            'akun_id' => $akunId,
            'roles'   => $roles,
            'reason'  => '403_forbidden',
        ]);
    }

    public static function logRoleChange(
        string $targetNpmOrUser,
        string $oldRole,
        string $newRole,
        ?string $actor = null,
        string $hasil = 'success',
        ?RequestInterface $request = null,
        array $extra = []
    ): string {
        $oldClean = SecurityLogSanitizer::sanitizeText($oldRole ?: '-');
        $newClean = SecurityLogSanitizer::sanitizeText($newRole ?: '-');
        $byClean  = SecurityLogSanitizer::sanitizeText($actor ?: 'system');

        return self::log(
            'role_change',
            $hasil,
            $targetNpmOrUser,
            'role_change:' . $oldClean . '->' . $newClean,
            array_merge($extra, [
                'request'  => $request,
                'actor'    => $byClean,
                'old_role' => $oldClean,
                'new_role' => $newClean,
                'reason'   => 'role:' . $oldClean . '->' . $newClean . ' by=' . $byClean,
            ])
        );
    }

    public static function describeRequestAction(?RequestInterface $request = null, string $fallback = 'http_request'): string
    {
        try {
            $req = $request ?? Services::request();
            $method = strtoupper((string) $req->getMethod());
            $path   = '/' . ltrim((string) $req->getUri()->getPath(), '/');

            return SecurityLogSanitizer::sanitizeText(trim($method . ' ' . $path)) ?: $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private static function resolveIpAddress($request = null): string
    {
        try {
            $req = $request instanceof RequestInterface ? $request : Services::request();

            return SecurityLogSanitizer::sanitizeText((string) $req->getIPAddress()) ?: '0.0.0.0';
        } catch (\Throwable $e) {
            return '0.0.0.0';
        }
    }

    private static function persistToAuthLogs(
        string $event,
        string $status,
        string $username,
        ?int $akunId,
        string $reason,
        string $eventId,
        string $ipAddress,
        string $authMethod,
        array $roles,
        $request,
        string $createdAt
    ): void {
        try {
            $userAgent = 'cli';
            if ($request instanceof RequestInterface && method_exists($request, 'getUserAgent')) {
                $userAgent = (string) $request->getUserAgent();
            } else {
                try {
                    $req = Services::request();
                    if (method_exists($req, 'getUserAgent')) {
                        $userAgent = (string) $req->getUserAgent();
                    }
                } catch (\Throwable $inner) {
                    $userAgent = 'unknown';
                }
            }

            $cleanReason = SecurityLogSanitizer::sanitizeText($reason);
            $storedReason = substr('[' . $eventId . '] ' . $cleanReason, 0, 100);

            $model = new AuthLogModel();
            $model->insert([
                'username'    => substr($username, 0, 100),
                'akun_id'     => $akunId,
                'event'       => substr($event, 0, 40),
                'status'      => substr($status, 0, 30),
                'auth_method' => substr(SecurityLogSanitizer::sanitizeText($authMethod), 0, 30),
                'roles'       => $roles === [] ? null : json_encode(array_values($roles)),
                'reason'      => $storedReason,
                'ip_address'  => substr($ipAddress, 0, 45),
                'user_agent'  => substr(SecurityLogSanitizer::sanitizeText($userAgent), 0, 255),
                'created_at'  => $createdAt,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menulis audit event [' . $eventId . ']: ' . SecurityLogSanitizer::sanitizeText($e->getMessage()));
        }
    }
}
