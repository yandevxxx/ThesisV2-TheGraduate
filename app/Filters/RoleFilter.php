<?php

namespace App\Filters;

use App\Libraries\AccessControl;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userData = session('user_session');

        if (! is_array($userData) || $userData === []) {
            return redirect()
                ->to(base_url('auth/login'))
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = AccessControl::rolesFromSession($userData);

        if (AccessControl::isSuperAdmin($roles)) {
            return;
        }

        if ($arguments === null || $arguments === []) {
            return;
        }

        $allowed = is_array($arguments) ? $arguments : [$arguments];

        if (! AccessControl::hasAnyRole($roles, $allowed)) {
            return AccessControl::forbiddenResponse($request, 'Akses ditolak', $roles);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
