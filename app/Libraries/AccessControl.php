<?php

namespace App\Libraries;

use CodeIgniter\HTTP\RequestInterface;
use Config\Rbac;

/**
 * Central role and menu policy helper.
 *
 * The policy itself lives in Config\Rbac. This class only evaluates it, so
 * adding Dosen, Prodi, Wisudawan (or a future role) never requires a new
 * controller structure.
 */
final class AccessControl
{
    /**
     * Normalise a session payload or a plain list of roles.
     *
     * @param mixed $value
     *
     * @return string[]
     */
    public static function normalizeRoles($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        if (isset($value['roles']) || isset($value['role'])) {
            $value = isset($value['roles']) && is_array($value['roles'])
                ? $value['roles']
                : (isset($value['role']) ? [$value['role']] : []);
        }

        $normalised = [];

        foreach ($value as $role) {
            if (! is_scalar($role)) {
                continue;
            }

            $role = strtolower(trim((string) $role));

            if ($role !== '' && ! in_array($role, $normalised, true)) {
                $normalised[] = $role;
            }
        }

        return $normalised;
    }

    /**
     * @param mixed $session
     *
     * @return string[]
     */
    public static function rolesFromSession($session): array
    {
        return self::normalizeRoles($session);
    }

    public static function isSuperAdmin(array $roles): bool
    {
        foreach (self::config()->superAdminRoles as $superRole) {
            if (self::hasRole($roles, $superRole)) {
                return true;
            }
        }

        return false;
    }

    public static function hasRole(array $roles, string $required): bool
    {
        foreach ($roles as $role) {
            if (self::roleEquals($role, $required)) {
                return true;
            }
        }

        return false;
    }

    public static function hasAnyRole(array $roles, array $allowed): bool
    {
        foreach ($allowed as $required) {
            if (self::hasRole($roles, (string) $required)) {
                return true;
            }
        }

        return false;
    }

    public static function roleEquals(string $left, string $right): bool
    {
        $left  = strtolower(trim($left));
        $right = strtolower(trim($right));

        if ($left === $right) {
            return true;
        }

        $aliases = self::config()->roleAliases;

        return in_array($right, $aliases[$left] ?? [], true)
            || in_array($left, $aliases[$right] ?? [], true);
    }

    public static function isControllerMapped(string $controller): bool
    {
        $controller = self::controllerKey($controller);

        if (in_array($controller, self::config()->publicControllers, true)) {
            return false;
        }

        return isset(self::config()->controllerRoles[$controller]);
    }

    /**
     * @param string[] $roles
     */
    public static function canAccess(string $controller, string $method, array $roles): bool
    {
        if (self::isSuperAdmin($roles)) {
            return true;
        }

        $controller = self::controllerKey($controller);
        $rules      = self::config()->controllerRoles[$controller] ?? null;

        // Unmapped controllers keep their existing behaviour.
        if ($rules === null) {
            return true;
        }

        $method  = strtolower(trim($method));
        $allowed = $rules[$method] ?? $rules['*'] ?? null;

        if ($allowed === null) {
            return false;
        }

        return self::hasAnyRole($roles, $allowed);
    }

    /**
     * @param string[] $roles
     */
    public static function menuVisible(string $menu, array $roles): bool
    {
        if (self::isSuperAdmin($roles)) {
            return true;
        }

        $allowed = self::config()->menuRoles[$menu] ?? null;

        // Unlisted menus are shared menus.
        if ($allowed === null) {
            return true;
        }

        return self::hasAnyRole($roles, $allowed);
    }

    /**
     * Build the standard 403 response (HTML or JSON).
     *
     * @param string[] $roles
     */
    public static function forbiddenResponse(RequestInterface $request, string $message = 'Akses ditolak', array $roles = [])
    {
        SecurityAuditLogger::logAccessDenied($request, $roles, '', null);

        $response = service('response');
        $response->setStatusCode(403);

        $wantsJson = $request->isAJAX()
            || strpos(strtolower($request->getHeaderLine('Accept')), 'application/json') !== false;

        if ($wantsJson) {
            return $response->setJSON([
                'status'  => 403,
                'message' => $message,
            ]);
        }

        if ($roles !== []) {
            $activeSide = session()->get('active_side');
            if ($activeSide === 'graduate') {
                $landing = PortalRouter::graduateLanding($roles);
                $backUrl = $landing ? base_url($landing) : base_url(PortalRouter::CHOOSER);
            } else {
                $landing = PortalRouter::thesisLanding($roles);
                $backUrl = base_url($landing);
            }
        } else {
            $backUrl = base_url('auth/login');
        }

        return $response->setBody(view('errors/access_denied', [
            'message' => $message,
            'roles'   => $roles,
            'backUrl' => $backUrl,
        ]));
    }

    private static function controllerKey(string $controller): string
    {
        $controller = trim($controller, '\\');
        $parts      = explode('\\', $controller);

        return strtolower((string) end($parts));
    }

    private static function config(): Rbac
    {
        $config = config('Rbac');

        return $config instanceof Rbac ? $config : new Rbac();
    }
}
