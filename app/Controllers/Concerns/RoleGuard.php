<?php

namespace App\Controllers\Concerns;

use App\Libraries\AccessControl;
use CodeIgniter\Exceptions\PageNotFoundException;
use ReflectionMethod;

/**
 * Controller level guard used by Mahasiswa and TenagaKependidikan.
 *
 * The router calls _remap() for every request. This means every public method
 * in the controller, including future methods added by another developer, is
 * checked before it runs. That is the fail-closed part of B-05a.
 */
trait RoleGuard
{
    /**
     * @return string[]
     */
    abstract protected function allowedRoles(): array;

    /**
     * @param string $method
     * @param mixed  ...$params
     *
     * @return mixed
     */
    public function _remap($method, ...$params)
    {
        $roles = AccessControl::rolesFromSession(session('user_session'));

        if (! AccessControl::isSuperAdmin($roles)
            && ! AccessControl::hasAnyRole($roles, $this->allowedRoles())) {
            return $this->denyAccess();
        }

        if (! $this->isRoutableMethod((string) $method)) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! $this->authorizeOwnership((string) $method, $params)) {
            return $this->denyAccess();
        }

        return $this->{$method}(...$params);
    }

    /**
     * Controllers that need record ownership override this.
     *
     * @param array $params
     */
    protected function authorizeOwnership(string $method, array $params): bool
    {
        return true;
    }

    private function isRoutableMethod(string $method): bool
    {
        if ($method === '' || $method[0] === '_') {
            return false;
        }

        if (! method_exists($this, $method)) {
            return false;
        }

        $reflection = new ReflectionMethod($this, $method);

        return $reflection->isPublic()
            && ! $reflection->isStatic()
            && $reflection->getDeclaringClass()->getName() === static::class;
    }
}
