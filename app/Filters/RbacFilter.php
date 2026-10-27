<?php

namespace App\Filters;

use App\Libraries\AccessControl;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Global controller/method role filter.
 *
 * Route filters alone cannot protect auto-routed endpoints, so this filter is
 * registered in Config\Filters::$globals. It only acts on controllers listed
 * in Config\Rbac::$controllerRoles.
 */
class RbacFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (is_cli()) {
            return;
        }

        $router     = service('router');
        $controller = $this->controllerName($request, $router);

        if ($controller === '' || ! AccessControl::isControllerMapped($controller)) {
            return;
        }

        $userData = session('user_session');

        if (! is_array($userData) || $userData === []) {
            return redirect()
                ->to(base_url('auth/login'))
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles  = AccessControl::rolesFromSession($userData);
        $method = (string) $router->methodName();

        if ($method === '') {
            $method = $this->methodFromPath($request);
        }

        if (AccessControl::canAccess($controller, $method, $roles)) {
            return;
        }

        log_message(
            'warning',
            'RBAC deny controller={controller} method={method} roles={roles} uri={uri}',
            [
                'controller' => $controller,
                'method'     => $method,
                'roles'      => json_encode($roles),
                'uri'        => $request->getUri()->getPath(),
            ]
        );

        return AccessControl::forbiddenResponse($request, 'Akses ditolak', $roles);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /**
     * @param mixed $router
     */
    private function controllerName(RequestInterface $request, $router): string
    {
        $name = '';

        if (is_object($router) && method_exists($router, 'controllerName')) {
            $name = (string) $router->controllerName();
        }

        if ($name === '') {
            $segments = $request->getUri()->getSegments();

            if (isset($segments[0]) && $segments[0] !== 'index.php') {
                $name = $segments[0];
            } elseif (isset($segments[1])) {
                $name = $segments[1];
            }
        }

        $name  = trim($name, '\\');
        $parts = explode('\\', $name);

        return strtolower((string) end($parts));
    }

    private function methodFromPath(RequestInterface $request): string
    {
        $segments = $request->getUri()->getSegments();

        if (isset($segments[0]) && $segments[0] === 'index.php') {
            array_shift($segments);
        }

        return isset($segments[1]) && $segments[1] !== ''
            ? (string) $segments[1]
            : 'index';
    }
}
