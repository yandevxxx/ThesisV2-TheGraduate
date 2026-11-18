<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SessionTimeoutFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($request->isCLI()) {
            return;
        }

        $session  = session();
        $userData = $session->get('user_session');

        if (! is_array($userData) || $userData === []) {
            return;
        }

        $config          = config('AuthSecurity');
        $now             = time();
        $loginAt         = (int) ($userData['login_at'] ?? 0);
        $lastActivity    = (int) ($userData['last_activity'] ?? $loginAt ?: $now);
        $idleTimeout     = (int) ($config->sessionIdleTimeout ?? 1800);
        $absoluteTimeout = (int) ($config->sessionAbsoluteTimeout ?? 28800);

        if ($loginAt > 0 && $absoluteTimeout > 0 && ($now - $loginAt) > $absoluteTimeout) {
            return $this->expireSession(
                $request,
                $session,
                'Batas waktu sesi maksimum telah berakhir. Silakan login kembali.'
            );
        }

        if ($lastActivity > 0 && $idleTimeout > 0 && ($now - $lastActivity) > $idleTimeout) {
            return $this->expireSession(
                $request,
                $session,
                'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.'
            );
        }

        $userData['last_activity'] = $now;
        $session->set('user_session', $userData);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }


    private function expireSession(RequestInterface $request, $session, string $message)
    {
        $session->remove(['user_session', 'pendaftaran', 'active_side']);
        $session->regenerate(true);

        $wantsJson = (method_exists($request, 'isAJAX') && $request->isAJAX())
            || strpos(strtolower($request->getHeaderLine('Accept')), 'application/json') !== false;

        if ($wantsJson) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 401,
                    'message' => $message,
                ]);
        }

        $session->setFlashdata('message', [
            'icon'  => 'warning',
            'title' => 'Sesi Berakhir',
            'text'  => $message,
        ]);

        return redirect()->to(base_url('auth/login'));
    }
}
