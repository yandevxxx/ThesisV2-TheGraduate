<?php

namespace Tests\Unit;

use App\Controllers\Auth;
use App\Controllers\TheGraduateWeb\Graduate;
use App\Filters\RbacFilter;
use App\Filters\SessionTimeoutFilter;
use App\Libraries\AuthLdap;
use App\Libraries\LoginThrottle;
use App\Models\AksesModel;
use App\Models\AkunModel;
use App\Models\MahasiswaModel;
use App\Models\ProgramStudiModel;
use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Session\Handlers\ArrayHandler;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockSession;
use ReflectionProperty;

/**
 * AuthLdap mock that returns a successful campus LDAP authentication result.
 */
class MockSuccessLdap extends AuthLdap
{
    public $callCount = 0;

    public function authenticate(string $userName, string $password): array
    {
        $this->callCount++;

        return [
            'authenticated'   => true,
            'username'        => $userName,
            'displayname'     => 'Mahasiswa Uji LDAP',
            'mail'            => 'mhs.uji@yarsi.ac.id',
            'role'            => 'Mahasiswa',
            'id_nik'          => '1402021999',
            'street'          => 'Jl. Letjen Suprapto',
            'telephonenumber' => '081234567890',
            'employee_number' => null,
        ];
    }
}

/**
 * Stateful in-memory session double simulating server-side session storage
 * keyed by session ID (cookie value) across multiple HTTP requests.
 */
class StatefulInMemorySession extends MockSession
{
    /** @var array<string, array<string, mixed>> */
    public $serverStore = [];

    /** @var string */
    private $activeId;

    public function __construct(string $initialSessionId = 'sess_before_login_001')
    {
        parent::__construct(new ArrayHandler(config('App'), '127.0.0.1'), config('App'));
        $this->activeId                    = $initialSessionId;
        $this->serverStore[$this->activeId] = [];
        $_SESSION                           = [];
    }

    public function getSessionId(): string
    {
        return $this->activeId;
    }

    public function loadForCookie(?string $cookieSessionId): void
    {
        if ($cookieSessionId !== null && $cookieSessionId !== '' && isset($this->serverStore[$cookieSessionId])) {
            $this->activeId = $cookieSessionId;
            $_SESSION       = $this->serverStore[$cookieSessionId];

            return;
        }

        // Unknown / invalidated cookie starts an empty anonymous session.
        $this->activeId                     = 'sess_anon_' . bin2hex(random_bytes(8));
        $this->serverStore[$this->activeId] = [];
        $_SESSION                           = [];
    }

    public function syncToStore(): void
    {
        if ($this->activeId !== '') {
            $this->serverStore[$this->activeId] = $_SESSION;
        }
    }

    public function regenerate(bool $destroy = false)
    {
        $oldId = $this->activeId;
        $newId = 'sess_regen_' . bin2hex(random_bytes(8));

        if ($destroy) {
            unset($this->serverStore[$oldId]);
            $this->serverStore[$newId] = $_SESSION;
        } else {
            $this->serverStore[$newId] = $_SESSION;
        }

        $this->activeId                    = $newId;
        $this->didRegenerate               = true;
        $_SESSION['__ci_last_regenerate']  = time();
        $this->serverStore[$this->activeId] = $_SESSION;
    }

    public function destroy()
    {
        unset($this->serverStore[$this->activeId]);
        $_SESSION       = [];
        $this->activeId = '';
    }
}

final class SessionSecurityTest extends CIUnitTestCase
{
    /** @var StatefulInMemorySession */
    private $sessionStore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionStore = new StatefulInMemorySession('sess_pre_login_' . bin2hex(random_bytes(4)));
        Services::injectMock('session', $this->sessionStore);
        Services::resetSingle('router');
    }

    protected function tearDown(): void
    {
        $_POST    = [];
        $_COOKIE  = [];
        $_SESSION = [];
        parent::tearDown();
    }

    private function createAuthControllerWithMockedModels(AuthLdap $ldap): Auth
    {
        $akunModel = new class extends AkunModel {
            public function __construct() {}
            public function where($key = null, $value = null, ?bool $escape = null) { return $this; }
            public function first()
            {
                return [
                    'id'       => 901,
                    'username' => 'mhs.uji',
                    'email'    => 'mhs.uji@yarsi.ac.id',
                    'password' => null,
                    'status'   => 'ACTIVE',
                ];
            }
        };

        $aksesModel = new class extends AksesModel {
            public function __construct() {}
            public function getRoles($idAkun)
            {
                return [
                    ['id' => 1, 'nama' => 'mahasiswa'],
                ];
            }
        };

        $mahasiswaModel = new class extends MahasiswaModel {
            public function __construct() {}
            public function where($key = null, $value = null, ?bool $escape = null) { return $this; }
            public function first()
            {
                return [
                    'npm'      => '1402021999',
                    'nama'     => 'Mahasiswa Uji LDAP',
                    'email'    => 'mhs.uji@yarsi.ac.id',
                    'id_prodi' => 1,
                ];
            }
        };

        $prodiModel = new class extends ProgramStudiModel {
            public function __construct() {}
            public function find($id = null)
            {
                return [
                    'id'      => 1,
                    'nama'    => 'Teknik Informatika',
                    'inisial' => 'TI',
                ];
            }
        };

        $controller = new Auth();
        $controller->setAuthLdap($ldap);
        $controller->setLoginThrottle(new LoginThrottle());

        $inject = static function (object $target, string $prop, object $value): void {
            $ref = new ReflectionProperty($target, $prop);
            $ref->setAccessible(true);
            $ref->setValue($target, $value);
        };

        $inject($controller, 'akunModel', $akunModel);
        $inject($controller, 'aksesModel', $aksesModel);
        $inject($controller, 'mahasiswaModel', $mahasiswaModel);
        $inject($controller, 'prodiModel', $prodiModel);

        return $controller;
    }

    private function makeLoginPostRequest(string $username, string $password)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR']    = '127.0.0.1';
        $_POST['username']         = $username;
        $_POST['password']         = $password;

        $request = Services::request(config('App'), false);
        $request->setMethod('post');

        return $request;
    }

    private function makeHttpIncomingRequest(string $method = 'get'): IncomingRequest
    {
        $config = config('App');
        $req    = new class ($config, new URI('http://localhost/graduate/admin_tendik'), 'php://input', new UserAgent()) extends IncomingRequest {
            public function isCLI(): bool
            {
                return false;
            }
        };
        $req->setMethod($method);

        return $req;
    }

    private function performMockedLdapLogin(string $username = 'mhs.uji'): array
    {
        $mockLdap   = new MockSuccessLdap();
        $controller = $this->createAuthControllerWithMockedModels($mockLdap);

        $throttle = new LoginThrottle();
        $throttle->clear(LoginThrottle::accountKey($username, '127.0.0.1'));
        $throttle->clear(LoginThrottle::ipKey('127.0.0.1'));

        $sessionIdBefore = $this->sessionStore->getSessionId();
        $request         = $this->makeLoginPostRequest($username, 'ValidCampusPass!1');

        $controller->initController($request, Services::response(config('App'), false), Services::logger());
        $response = $controller->signInAccount();
        $this->sessionStore->syncToStore();

        return [
            'controller'      => $controller,
            'mockLdap'        => $mockLdap,
            'response'        => $response,
            'sessionIdBefore' => $sessionIdBefore,
            'sessionIdAfter'  => $this->sessionStore->getSessionId(),
        ];
    }

    public function testSessionIdChangesAfterLdapLogin(): void
    {
        // Simulasikan ada data pendaftaran sementara sebelum login selesai.
        $this->sessionStore->set('pendaftaran', ['username' => 'stale_temp', 'id_nik' => '000']);
        $this->sessionStore->syncToStore();

        $result = $this->performMockedLdapLogin();

        $this->assertSame(1, $result['mockLdap']->callCount, 'Mock LDAP harus dipanggil 1 kali saat login.');
        $this->assertInstanceOf(RedirectResponse::class, $result['response']);
        $this->assertStringContainsString('graduate/choose', $result['response']->getHeaderLine('Location'));

        $this->assertNotSame(
            $result['sessionIdBefore'],
            $result['sessionIdAfter'],
            'ID sesi sesudah login LDAP wajib berbeda dari ID sesi sebelum login (mencegah session fixation).'
        );
        $this->assertTrue($this->sessionStore->didRegenerate);
        $this->assertArrayNotHasKey(
            $result['sessionIdBefore'],
            $this->sessionStore->serverStore,
            'ID sesi lama harus dihapus dari store server setelah regenerasi.'
        );

        $userSession = $this->sessionStore->get('user_session');
        $this->assertIsArray($userSession);
        $this->assertSame('1402021999', $userSession['npm']);
        $this->assertSame(['mahasiswa'], $userSession['roles']);
        $this->assertSame('ldap', $userSession['auth_method']);
        $this->assertArrayHasKey('login_at', $userSession);
        $this->assertArrayHasKey('last_activity', $userSession);
        $this->assertArrayNotHasKey('password', $userSession);
        $this->assertNull($this->sessionStore->get('pendaftaran'), 'Sisa data pendaftaran LDAP harus dibersihkan.');
    }

    public function testRequestWithOldCookieIsRejectedAfterLogout(): void
    {
        $login        = $this->performMockedLdapLogin();
        $oldCookieId  = $login['sessionIdAfter'];
        $controller   = $login['controller'];

        // Pastikan sebelum logout sesi aktif valid untuk mengakses halaman terproteksi.
        $this->assertNotEmpty($this->sessionStore->get('user_session'));

        // Jalankan logout.
        $logoutRequest = Services::request(config('App'), false);
        $logoutRequest->setMethod('get');
        $controller->initController($logoutRequest, Services::response(config('App'), false), Services::logger());
        $logoutResponse = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $logoutResponse);
        $this->assertStringContainsString('auth/login', $logoutResponse->getHeaderLine('Location'));
        $this->assertEmpty($_SESSION, 'Seluruh data sesi di memori harus kosong setelah logout.');
        $this->assertArrayNotHasKey(
            $oldCookieId,
            $this->sessionStore->serverStore,
            'Sesi di server untuk ID cookie lama harus terhapus saat logout.'
        );

        // Pastikan browser menerima instruksi penghapusan cookie sesi.
        $cookieName   = config('App')->sessionCookieName;
        $deletedCookie = $logoutResponse->getCookie($cookieName);
        $this->assertNotNull($deletedCookie, 'Logout harus mengirim Set-Cookie penghapusan cookie sesi.');
        $this->assertSame('', $deletedCookie->getValue());
        $this->assertTrue($deletedCookie->isExpired());
        $this->assertTrue($deletedCookie->isHTTPOnly());
        $this->assertSame('Lax', $deletedCookie->getSameSite());

        // Simulasikan request baru dengan cookie lama yang dipakai kembali.
        $this->sessionStore->loadForCookie($oldCookieId);
        $this->assertNull($this->sessionStore->get('user_session'));

        // 1) Request browser ke rute Graduate yang dijaga RbacFilter -> diarahkan ke auth/login.
        $router = service('router');
        $refCtrl = new ReflectionProperty($router, 'controller');
        $refCtrl->setAccessible(true);
        $refCtrl->setValue($router, Graduate::class);

        $refMethod = new ReflectionProperty($router, 'method');
        $refMethod->setAccessible(true);
        $refMethod->setValue($router, 'admin_tendik');

        $browserRequest = $this->makeHttpIncomingRequest('get');

        $rbacFilter      = new RbacFilter();
        $browserDecision = $rbacFilter->before($browserRequest);

        $this->assertInstanceOf(RedirectResponse::class, $browserDecision);
        $this->assertStringContainsString(
            'auth/login',
            $browserDecision->getHeaderLine('Location'),
            'Request browser dengan cookie lama setelah logout harus diarahkan ke halaman login.'
        );

        // 2) Request AJAX/JSON dengan cookie lama -> dibalas HTTP 401.
        $apiRequest = $this->makeHttpIncomingRequest('get');
        $apiRequest->setMethod('get');
        $apiRequest->setHeader('Accept', 'application/json');
        $apiRequest->setHeader('X-Requested-With', 'XMLHttpRequest');

        $apiDecision = $rbacFilter->before($apiRequest);

        $this->assertInstanceOf(ResponseInterface::class, $apiDecision);
        $this->assertSame(
            401,
            $apiDecision->getStatusCode(),
            'Request JSON/AJAX dengan cookie lama setelah logout harus dibalas 401.'
        );
    }

    public function testCookieSecurityAttributesFollowPolicy(): void
    {
        $cookieConfig = config('Cookie');
        $appConfig    = config('App');

        $this->assertTrue($cookieConfig->httponly);
        $this->assertTrue($appConfig->cookieHTTPOnly);
        $this->assertContains($cookieConfig->samesite, ['Lax', 'Strict']);
        $this->assertIsBool($cookieConfig->secure);
    }

    public function testIdleAndAbsoluteTimeoutExpireSessions(): void
    {
        $security = config('AuthSecurity');
        $filter   = new SessionTimeoutFilter();

        // 1) Uji Idle Timeout: login baru saja (dalam batas absolut), namun idle melewati batas.
        $now = time();
        $this->sessionStore->set('user_session', [
            'id'            => '1402021999',
            'roles'         => ['mahasiswa'],
            'login_at'      => $now - ($security->sessionIdleTimeout + 120),
            'last_activity' => $now - ($security->sessionIdleTimeout + 60),
        ]);

        $idleReq = $this->makeHttpIncomingRequest('get');
        $idleRes = $filter->before($idleReq);

        $this->assertInstanceOf(RedirectResponse::class, $idleRes);
        $this->assertStringContainsString('auth/login', $idleRes->getHeaderLine('Location'));
        $this->assertNull($this->sessionStore->get('user_session'));

        // 2) Uji Absolute Timeout: baru saja aktif 5 detik lalu, tetapi login_at melewati batas absolut.
        $this->sessionStore->set('user_session', [
            'id'            => '1402021999',
            'roles'         => ['mahasiswa'],
            'login_at'      => $now - ($security->sessionAbsoluteTimeout + 60),
            'last_activity' => $now - 5,
        ]);

        $absReq = $this->makeHttpIncomingRequest('get');
        $absReq->setMethod('get');
        $absReq->setHeader('Accept', 'application/json');
        $absRes = $filter->before($absReq);

        $this->assertInstanceOf(ResponseInterface::class, $absRes);
        $this->assertSame(401, $absRes->getStatusCode());
        $this->assertNull($this->sessionStore->get('user_session'));
    }
}
