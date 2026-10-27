<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Ends authenticated sessions that have been idle for too long.
 */
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

        $config = config('AuthSecurity');
        $now    = time();
        $last   = (int) ($userData['last_activity'] ?? $userData['login_at'] ?? $now);

        if ($last > 0 && ($now - $last) > (int) $config->sessionIdleTimeout) {
            $session->remove('user_session');
            $session->regenerate(true);
            $session->setFlashdata('message', [
                'icon'  => 'warning',
                'title' => 'Sesi Berakhir',
                'text'  => 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.',
            ]);

            return redirect()->to(base_url('auth/login'));
        }

        $userData['last_activity'] = $now;
        $session->set('user_session', $userData);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
