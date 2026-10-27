<?php

namespace App\Controllers;

use App\Libraries\AccessControl;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
        \Config\Services::session();

    }

    /**
     * @return array|null
     */
    protected function currentUser()
    {
        $user = session('user_session');

        return is_array($user) ? $user : null;
    }

    /**
     * @return string[]
     */
    protected function currentRoles(): array
    {
        return AccessControl::rolesFromSession($this->currentUser());
    }

    protected function currentNpm(): ?string
    {
        $user = $this->currentUser();

        if ($user === null) {
            return null;
        }

        if (! empty($user['npm'])) {
            return (string) $user['npm'];
        }

        $roles = AccessControl::normalizeRoles($user);

        if (in_array($roles[0] ?? '', ['mahasiswa', 'wisudawan'], true)
            && ! empty($user['id'])) {
            return (string) $user['id'];
        }

        return null;
    }

    /**
     * Return a standard 403 response instead of redirecting.
     *
     * @return mixed
     */
    protected function denyAccess(string $message = 'Akses ditolak')
    {
        return AccessControl::forbiddenResponse(
            $this->request,
            $message,
            $this->currentRoles()
        );
    }
}
