<?php
namespace Tests\Session;

use App\Controllers\Auth;
use CodeIgniter\Test\CIUnitTestCase;

final class SessionSecurityTest extends CIUnitTestCase
{
    // TEST 1: Regenerasi sesi setelah login berhasil
    public function testSessionRegeneratedDuringSuccessfulLogin()
    {
        $auth = new Auth();

        $profile = [
            'id' => 1,
            'username' => 'testuser',
            'roles' => ['mahasiswa'],
            'isLoggedIn' => true,
        ];

        $method = new \ReflectionMethod(Auth::class, 'finishLogin');
        $method->setAccessible(true);

        $method->invoke(
            $auth,
            $profile,
            'ldap',
            'testuser',
            1,
            '127.0.0.1'
        );

        $this->assertTrue(
            session()->didRegenerate,
            'Session harus diregenerasi setelah login berhasil.'
        );

        $this->assertTrue(
            session()->get('user_session')['isLoggedIn'],
            'Data sesi pengguna harus tersimpan setelah login.'
        );
    }

    // TEST 2: Logout mengarahkan pengguna ke halaman login
    public function testLogoutRedirectsToLogin()
    {
        $session = session();

        $session->set('user_session', [
            'username' => 'testuser',
            'roles' => ['mahasiswa'],
            'isLoggedIn' => true,
        ]);

        $auth = new Auth();
        $response = $auth->logout();

        $this->assertSame(
            base_url('auth/login'),
            $response->getHeaderLine('Location'),
            'Setelah logout, pengguna harus diarahkan ke halaman login.'
        );
    }
}
