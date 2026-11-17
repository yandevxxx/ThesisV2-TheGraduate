<?php







namespace App\Controllers;





use App\Models\PasswordResetModel;

use App\Models\AkunModel;

use CodeIgniter\Controller;



use App\Libraries\AuthLdap;

use App\Libraries\LdapProfileSync;

use App\Libraries\LocalCredential;

use App\Libraries\LoginResolver;

use App\Libraries\LoginThrottle;

use App\Libraries\PortalRouter;

use App\Models\AuthLogModel;



use CodeIgniter\I18n\Time;



use App\Controllers\BaseController;



class Auth extends BaseController

{



    protected $authLdap;

    protected $mahasiswaModel;

    protected $akunModel;

    protected $aksesModel;

    protected $roleModel;

    protected $fakultasModel;

    protected $prodiModel;

    protected $tendikModel;

    protected $dosenModel;

    protected $persetujuanTamuModel;

    protected $klienModel;





    public function __construct()

    {

        $this->mahasiswaModel = new \App\Models\MahasiswaModel();

        $this->akunModel = new \App\Models\AkunModel();

        $this->aksesModel = new \App\Models\AksesModel();

        $this->roleModel = new \App\Models\RoleModel();

        $this->fakultasModel = new \App\Models\FakultasModel();

        $this->prodiModel = new \App\Models\ProgramStudiModel();

        $this->tendikModel = new \App\Models\TenagaKependidikanModel();

        $this->dosenModel = new \App\Models\DosenModel();

        $this->persetujuanTamuModel = new \App\Models\PersetujuanTamuModel();

        $this->klienModel = new \App\Models\KlienModel();
    }







    public function index()

    {

        return redirect()->to(base_url("/auth/login"));
    }





    public function login()

    {

        if (session()->get("user_session") == null) {

            return view("auth/login");
        }

        return redirect()->to(base_url(PortalRouter::CHOOSER));
    }





    public function logout()

    {

        session()->destroy();

        return redirect()->to(base_url("auth/login"));
    }



    /* ===========================================================

     |  FORGOT PASSWORD — TAMPILAN FORM

     ============================================================ */

    public function forgotPassword()

    {

        return view("auth/forgot_password");
    }



    /* ===========================================================

 |  FORGOT PASSWORD — SUBMIT REQUEST RESET

 |  Mencari Akun di Tabel Tamu DULU, lalu di Tabel Utama

 ============================================================ */

    public function forgotSubmit()

    {

        // Mengambil input dari form

        $usernameInput = $this->request->getPost('username');

        $emailInput    = $this->request->getPost('email');



        if (empty($usernameInput) || empty($emailInput)) {

            return redirect()->back()->with('message', [

                "icon" => "error",

                "title" => "Gagal",

                "text" => "Username dan Email harus diisi lengkap."

            ]);
        }



        $akun = null;



        // 1. CARI DULU DI TABEL TAMU (perstujuan_tamu)

        $akun = $this->persetujuanTamuModel

            ->where('username', $usernameInput)

            ->where('email', $emailInput)

            ->first();



        // 2. JIKA TIDAK DITEMUKAN DI TAMU, CARI DI TABEL UTAMA (akun)

        if (!$akun) {

            $akun = $this->akunModel

                ->where('username', $usernameInput)

                ->where('email', $emailInput)

                ->first();
        }



        // 3. JIKA TETAP TIDAK DITEMUKAN

        if (!$akun) {

            return redirect()->back()->with('message', [

                "icon" => "error",

                "title" => "Gagal",

                "text" => "Kombinasi Username dan Email tidak cocok atau tidak terdaftar."

            ]);
        }



        // --- 4. LANJUTKAN DENGAN PROSES GENERATE TOKEN & SEND EMAIL ---



        // Email yang dipakai untuk token adalah email yang ditemukan di database (Tamu/Utama)

        $userEmail = $akun['email'];



        // Generate token

        $token = bin2hex(random_bytes(32));

        $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));



        // Simpan token (Tabel password_reset_tokens)

        $tokenModel = new \App\Models\PasswordResetModel();

        $tokenModel->insert([

            'email' => $userEmail,

            'token' => $token,

            'expires_at' => $expires

        ]);



        // Kirim email

        $resetLink = base_url("auth/resetPassword/$token");



        $emailService = \Config\Services::email();

        $emailService->setTo($userEmail);

        $emailService->setSubject('Reset Password TheSIS');

        $emailService->setMessage("

        Halo {$usernameInput},<br>

        Kami menerima permintaan untuk mengatur ulang kata sandi Anda.<br><br>

        Klik link berikut untuk reset password:<br>

        <a href='$resetLink'>$resetLink</a><br><br>

        Link berlaku 15 menit. Jika Anda tidak merasa melakukan ini, abaikan email ini.

    ");



        if (!$emailService->send()) {

            $data = $emailService->printDebugger(['headers']);

            log_message('error', 'Email reset password gagal dikirim: ' . print_r($data, true));



            return redirect()->back()->with('message', [

                "icon" => "error",

                "title" => "Gagal",

                "text" => "Gagal mengirim email reset password. Cek log server."

            ]);
        }



        return redirect()->back()->with('message', [

            "icon" => "success",

            "title" => "Berhasil",

            "text" => "Link reset password telah dikirim ke email: {$userEmail}"

        ]);
    }



    /* ===========================================================

 |  RESET PASSWORD — CEK TOKEN & TAMPILKAN FORM

 |  Fungsi ini dipanggil saat user klik link di email

 ============================================================ */

    public function resetPassword($token = null)

    {

        if (!$token) {

            return redirect()->to(base_url('auth/login'));
        }



        $tokenModel = new \App\Models\PasswordResetModel();

        $dataToken = $tokenModel->where('token', $token)->first();



        if (!$dataToken) {

            return redirect()->to(base_url('auth/login'))->with('message', [

                "icon" => "error",

                "title" => "Invalid Token",

                "text" => "Token tidak valid."

            ]);
        }



        // Cek Expired (15 menit)

        if (strtotime($dataToken['expires_at']) < time()) {

            return redirect()->to(base_url('auth/login'))->with('message', [

                "icon" => "error",

                "title" => "Expired",

                "text" => "Token sudah expired. Silakan kirim ulang permintaan reset password."

            ]);
        }



        // Jika token valid, tampilkan halaman form reset password

        return view("auth/reset_password", [

            'token' => $token

        ]);
    }



    /* ===========================================================

 |  RESET PASSWORD — SUBMIT PASSWORD BARU

 |  Ditingkatkan untuk update di Akun UTAMA dan Akun TAMU

 ============================================================ */

    public function resetSubmit()

    {

        $token   = $this->request->getPost('token');

        $pass    = $this->request->getPost('password');

        $confirm = $this->request->getPost('password_confirm');



        // 1. Validasi Konfirmasi Password (TETAP SAMA)

        if ($pass !== $confirm) {

            return redirect()->back()->with('message', [

                "icon" => "error",

                "title" => "Error",

                "text" => "Konfirmasi password tidak sama."

            ]);
        }



        // 2. Cek Validitas Token (TETAP SAMA)

        $tokenModel = new \App\Models\PasswordResetModel(); // Perlu namespace jika belum di-use di atas

        $dataToken  = $tokenModel->where('token', $token)->first();



        if (!$dataToken || strtotime($dataToken['expires_at']) < time()) {

            return redirect()->to(base_url('auth/login'))->with('message', [

                "icon" => "error",

                "title" => "Invalid",

                "text" => "Token tidak valid atau sudah expired. Silakan coba lagi."

            ]);
        }



        // --- 3. LOGIC UPDATE PASSWORD BARU (Perbaikan Multi-Tabel) ---

        $hashed = password_hash($pass, PASSWORD_DEFAULT);

        $emailToUpdate = $dataToken['email'];

        $isUpdated = false;



        // Coba update di Akun UTAMA (AkunModel)

        if ($this->akunModel->where('email', $emailToUpdate)->set(['password' => $hashed])->update()) {

            $isUpdated = true;
        }



        // Coba update di Akun TAMU (PersetujuanTamuModel)

        if ($this->persetujuanTamuModel->where('email', $emailToUpdate)->set(['password' => $hashed])->update()) {

            $isUpdated = true;
        }



        // 4. Hapus Token (TETAP SAMA)

        $tokenModel->where('token', $token)->delete();



        // 5. Response

        if ($isUpdated) {

            return redirect()->to(base_url('auth/login'))->with('message', [

                "icon" => "success",

                "title" => "Success",

                "text" => "Password berhasil direset! Silakan login dengan password baru."

            ]);
        } else {

            // Jika tidak ada satupun tabel yang terupdate (data tidak ditemukan)

            return redirect()->to(base_url('auth/login'))->with('message', [

                "icon" => "error",

                "title" => "Gagal",

                "text" => "Gagal memperbarui password. Akun tidak ditemukan di kedua tabel."

            ]);
        }
    }

    /* ===========================================================

 |  MENAMPILKAN FORM UPDATE PASSWORD

 ============================================================ */

    public function updatePassword()

    {

        $data = [

            'title' => 'Ubah Kata Sandi'

        ];



        return view('user_update_password_tamu', $data);
    }





    /* ===========================================================

 |  UPDATE PASSWORD - SOLUSI FINAL (MENGGUNAKAN EMAIL)

 ============================================================ */

    public function updatePasswordSubmit()

    {

        // Ambil data dari form

        $oldPass     = $this->request->getPost('old_password');

        $newPass     = $this->request->getPost('new_password');

        $confirmPass = $this->request->getPost('confirm_password');



        // Ambil session

        $userSession = session()->get('user_session');



        // 1. CEK LOGIN

        if (!$userSession) {

            return redirect()->to(base_url('auth/login'));
        }



        // 2. CEK MAHASISWA (Lewati karena pakai LDAP)

        if (in_array('mahasiswa', $userSession['roles'])) {

            return redirect()->to(base_url('home'));
        }



        // --- LOGIKA PENCARIAN USER (PERBAIKAN ANTI-ERROR) ---



        // Ambil Email dengan aman (pakai tanda ?? biar tidak error merah)

        $emailSession = $userSession['email'] ?? null;

        $idSession    = $userSession['id'] ?? null;



        // Jaga-jaga: Kalau session tidak punya email, kita cari manual pakai ID

        if (empty($emailSession) && $idSession) {

            // Cek di tabel tamu dulu buat ambil emailnya

            $tempTamu = $this->persetujuanTamuModel->find($idSession);

            if ($tempTamu) {

                $emailSession = $tempTamu['email'];
            }
        }



        // Kalau Email masih kosong juga, berarti session rusak -> Suruh Login Ulang

        if (empty($emailSession)) {

            return redirect()->to(base_url('auth/login'))->with('message', [

                "icon" => "error",
                "title" => "Sesi Kedaluwarsa",

                "text" => "Gagal membaca data sesi. Silakan login ulang."

            ]);
        }



        // --- MULAI PENCARIAN DI DATABASE ---

        $model  = $this->akunModel; // Prioritas 1: Cari di Tabel Akun (Klien/Admin/Dosen)

        $userDb = $model->where('email', $emailSession)->first();



        // Prioritas 2: Kalau tidak ketemu, cari di Tabel Tamu

        if (!$userDb) {

            $model  = $this->persetujuanTamuModel;

            $userDb = $model->where('email', $emailSession)->first();
        }



        // Kalau MASIH tidak ketemu juga di kedua tabel

        if (!$userDb) {

            return redirect()->back()->with('message', [

                "icon" => "error",
                "title" => "Error",

                "text" => "Akun dengan email '$emailSession' tidak ditemukan di database."

            ]);
        }



        // --- AMBIL ID ASLI DARI DATABASE ---

        // (Ini solusi biar ID session yang lama tidak bikin error)

        $pkName = $model->primaryKey; // Otomatis deteksi nama kolom ID (id/id_akun/dll)

        $realId = $userDb[$pkName] ?? $userDb['id'];



        // 4. VERIFIKASI PASSWORD LAMA

        if (! LocalCredential::verify((string) $oldPass, $userDb['password'])) {

            return redirect()->back()->with('message', [

                "icon" => "error",
                "title" => "Gagal",
                "text" => "Kata Sandi Lama SALAH."

            ]);
        }



        // 5. CEK KONFIRMASI PASSWORD BARU

        if ($newPass !== $confirmPass) {

            return redirect()->back()->with('message', [

                "icon" => "error",
                "title" => "Gagal",
                "text" => "Konfirmasi password baru tidak cocok."

            ]);
        }



        // 6. UPDATE PASSWORD

        $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);



        // Update ke tabel yang benar, pakai ID yang benar

        $updateStatus = $model->update($realId, ['password' => $hashedPassword]);



        if ($updateStatus) {

            return redirect()->back()->with('message', [

                "icon" => "success",
                "title" => "Berhasil",
                "text" => "Kata Sandi berhasil diperbarui! Silakan login ulang."

            ]);
        } else {

            return redirect()->back()->with('message', [

                "icon" => "error",
                "title" => "Error",
                "text" => "Gagal menyimpan ke database."

            ]);
        }
    }



    public function signInAccount()
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('auth/login'));
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        // Opsi cadangan eksplisit dari form login (default tidak dicentang).
        $useLocalPassword = $this->request->getPost('use_local_password') === '1';

        if ($username === '' || $password === '') {
            return $this->loginError('Username dan kata sandi wajib diisi.', $username);
        }

        $ipAddress  = $this->request->getIPAddress();
        $throttle   = new LoginThrottle();
        $accountKey = LoginThrottle::accountKey($username, $ipAddress);
        $ipKey      = LoginThrottle::ipKey($ipAddress);

        if ($throttle->tooManyAttempts($accountKey) || $throttle->tooManyAttempts($ipKey)) {
            $this->writeAuthLog('login', 'blocked', $username, null, 'throttled', $ipAddress, 'unknown');

            $retryAfter = max($throttle->availableIn($accountKey), $throttle->availableIn($ipKey));
            $minutes    = max(1, (int) ceil($retryAfter / 60));

            return $this->loginError(
                'Terlalu banyak percobaan login. Silakan coba lagi dalam ' . $minutes . ' menit.',
                $username
            );
        }

        $akunData = $this->akunModel->where('username', $username)->first();

        if ($akunData !== null && strtoupper((string) $akunData['status']) !== 'ACTIVE') {
            return $this->registerFailedAttempt(
                $throttle,
                $accountKey,
                $ipKey,
                $username,
                $akunData['id'],
                'inactive_account',
                $ipAddress,
                'local'
            );
        }

        if ($akunData === null) {
            $tamuData = $this->persetujuanTamuModel->where('username', $username)->first();

            if ($tamuData !== null) {
                if (! LocalCredential::verify($password, $tamuData['password'])) {
                    return $this->registerFailedAttempt(
                        $throttle,
                        $accountKey,
                        $ipKey,
                        $username,
                        null,
                        'invalid_credentials',
                        $ipAddress,
                        'local'
                    );
                }

                $this->clearLoginThrottle($throttle, $accountKey, $ipKey);

                $profile = [
                    'id'         => $tamuData['id'],
                    'nama'       => $tamuData['nama'],
                    'email'      => $tamuData['email'],
                    'username'   => $tamuData['username'],
                    'role'       => 'Tamu',
                    'roles'      => ['tamu'],
                    'instansi'   => $tamuData['instansi'],
                    'isLoggedIn' => true,
                ];

                return $this->finishLogin($profile, 'local', $username, null, $ipAddress);
            }

            // Mode cadangan tidak boleh diam-diam beralih ke LDAP.
            if ($useLocalPassword) {
                return $this->registerFailedAttempt(
                    $throttle,
                    $accountKey,
                    $ipKey,
                    $username,
                    null,
                    'invalid_credentials',
                    $ipAddress,
                    'manual_local'
                );
            }

            return $this->signInWithNewAccount($username, $password, $throttle, $accountKey, $ipKey, $ipAddress);
        }

        return $this->signInWithExistingAccount(
            $username,
            $password,
            $akunData,
            $throttle,
            $accountKey,
            $ipKey,
            $ipAddress,
            $useLocalPassword
        );
    }

    /**
     * Login untuk akun yang belum ada di database lokal.
     *
     * B-02: verifikasi kredensial LDAP lalu lanjutkan ke formulir pendaftaran.
     * B-03 akan mengganti langkah ini dengan provisioning profil otomatis.
     */
    private function signInWithNewAccount($username, $password, LoginThrottle $throttle, $accountKey, $ipKey, $ipAddress)
    {
        $ldap   = new AuthLdap();
        $result = $ldap->authenticate($username, $password);

        if (empty($result['authenticated'])) {
            $reason = isset($result['error']) ? $result['error'] : 'configuration';

            return $this->registerFailedAttempt(
                $throttle,
                $accountKey,
                $ipKey,
                $username,
                null,
                $reason,
                $ipAddress,
                'ldap'
            );
        }

        if (empty($result['role'])) {
            $this->writeAuthLog('ldap_role_unmapped', 'failed', $username, null, 'role_unmapped', $ipAddress, 'ldap');

            return $this->loginError(
                'Akun LDAP Anda tidak memiliki peran yang dikenali. Silakan hubungi administrator.',
                $username
            );
        }

        $this->clearLoginThrottle($throttle, $accountKey, $ipKey);

        /*
         * B-03: bila data lokal cukup, buat akun otomatis lalu login.
         * Bila tidak, lanjut ke formulir pendaftaran seperti alur B-02.
         */
        $sync        = new LdapProfileSync();
        $provisioned = $sync->provision($result, $result['role']);

        if ($provisioned !== null) {
            $userSession = $this->buildUserSession($provisioned['akun'], $provisioned['roles'], 'ldap');

            if ($userSession !== null) {
                $sync->enqueue($result, $provisioned['akun']['id'], $result['role']);
                $this->writeAuthLog(
                    'ldap_provisioned',
                    'success',
                    $username,
                    $provisioned['akun']['id'],
                    $provisioned['created'] ? 'auto_provision' : 'account_linked',
                    $ipAddress,
                    'ldap'
                );

                return $this->finishLogin($userSession, 'ldap', $username, $provisioned['akun']['id'], $ipAddress);
            }
        }

        $this->writeAuthLog('ldap_verified', 'success', $username, null, 'no_local_profile', $ipAddress, 'ldap');

        session()->set('pendaftaran', [
            'id_nik'          => $result['id_nik'],
            'displayname'     => $result['displayname'],
            'username'        => $result['username'],
            'role'            => $result['role'],
            'street'          => $result['street'],
            'phone'           => $result['telephonenumber'],
            'email'           => $result['mail'],
            'employee_number' => $result['employee_number'],
        ]);

        return redirect()->to(base_url('auth/pendaftaran'));
    }

    /**
     * Login untuk akun yang sudah terdaftar di tabel akun (B-04).
     *
     * LDAP selalu diperiksa lebih dulu. Hasil LDAP hanya dipakai bila role
     * LDAP kompatibel dengan seluruh role lokal. Bila LDAP gagal atau role
     * tidak kompatibel, hash password lokal dipakai sebagai cadangan. Akun
     * LDAP-only (password NULL) tetap gagal saat LDAP tidak dapat
     * memverifikasi kredensial.
     */
    private function signInWithExistingAccount($username, $password, $akunData, LoginThrottle $throttle, $accountKey, $ipKey, $ipAddress, $useLocalPassword = false)
    {
        $roles      = $this->resolveAccountRoles($akunData);
        $resolver   = new LoginResolver();
        $resolution = $resolver->resolve(
            $username,
            $password,
            $akunData['password'],
            $roles,
            $useLocalPassword
        );

        if (empty($resolution['authenticated'])) {
            return $this->registerFailedAttempt(
                $throttle,
                $accountKey,
                $ipKey,
                $username,
                $akunData['id'],
                $resolution['reason'],
                $ipAddress,
                $resolution['method']
            );
        }

        if (empty($roles)) {
            return $this->registerFailedAttempt(
                $throttle,
                $accountKey,
                $ipKey,
                $username,
                $akunData['id'],
                'no_role',
                $ipAddress,
                $resolution['method']
            );
        }

        $userSession = $this->buildUserSession($akunData, $roles, $resolution['method']);

        if ($userSession === null) {
            return $this->registerFailedAttempt(
                $throttle,
                $accountKey,
                $ipKey,
                $username,
                $akunData['id'],
                'profile_missing',
                $ipAddress,
                $resolution['method']
            );
        }

        if ($resolution['method'] === 'ldap' && ! empty($resolution['ldap'])) {
            $syncRole = $this->ldapSyncRole($roles);

            if ($syncRole !== '') {
                $sync = new LdapProfileSync();
                $sync->enqueue($resolution['ldap'], $akunData['id'], $syncRole);
            }
        }

        $this->clearLoginThrottle($throttle, $accountKey, $ipKey);

        return $this->finishLogin(
            $userSession,
            $resolution['method'],
            $username,
            $akunData['id'],
            $ipAddress,
            $resolution['reason']
        );
    }

    /**
     * Map local roles to the LDAP provisioning role used by B-03.
     *
     * @param string[] $roles
     */
    private function ldapSyncRole(array $roles): string
    {
        if (in_array('mahasiswa', $roles, true) || in_array('wisudawan', $roles, true)) {
            return 'Mahasiswa';
        }

        if (in_array('dosen', $roles, true) || in_array('dospem eksternal', $roles, true)) {
            return 'Dosen';
        }

        if (in_array('tendik', $roles, true)) {
            return 'Staff';
        }

        return '';
    }

    /**
     * Resolve every role owned by an account (akses is the source of truth).
     */
    private function resolveAccountRoles(array $akunData): array
    {
        $roles = [];

        foreach ($this->aksesModel->getRoles($akunData['id']) as $row) {
            if (! empty($row['nama'])) {
                $roles[] = strtolower($row['nama']);
            }
        }

        // Fallback for legacy rows that still carry akun.id_role.
        if (! empty($akunData['id_role'])) {
            $map = [
                1  => 'mahasiswa',
                2  => 'dosen',
                3  => 'kaprodi',
                4  => 'tendik',
                5  => 'fakultas',
                7  => 'klien',
                8  => 'admin',
                9  => 'wisudawan',
                99 => 'tamu',
            ];

            $idRole = (int) $akunData['id_role'];

            if (isset($map[$idRole])) {
                $roles[] = $map[$idRole];
            }
        }

        return PortalRouter::normalizeRoles(['roles' => $roles]);
    }

    /**
     * Build the user_session payload from the profile matching the primary role.
     * Returns null when the role owns no profile row.
     */
    private function buildUserSession(array $akunData, array $roles, $authMethod)
    {
        $primary = PortalRouter::primaryRole($roles);

        $session = [
            'id'         => $akunData['id'],
            'akun_id'    => (int) $akunData['id'],
            'nama'       => $akunData['username'],
            'email'      => $akunData['email'],
            'username'   => $akunData['username'],
            'prodi'      => '-',
            'role'       => $primary === null ? 'Pengguna' : ucfirst($primary),
            'roles'      => $roles,
            'isLoggedIn' => true,
            'auth_method' => $authMethod,
        ];

        if ($primary === 'admin') {
            $session['nama'] = 'Super Admin';

            return $session;
        }

        if (in_array($primary, ['mahasiswa', 'wisudawan'], true)) {
            $mahasiswa = $this->mahasiswaModel->where('email', $akunData['email'])->first();

            if ($mahasiswa === null) {
                return null;
            }

            $prodi = ! empty($mahasiswa['id_prodi']) ? $this->prodiModel->find($mahasiswa['id_prodi']) : null;

            $session['id']       = $mahasiswa['npm'];
            $session['nama']     = $mahasiswa['nama'];
            $session['npm']      = $mahasiswa['npm'];
            $session['id_prodi'] = isset($mahasiswa['id_prodi']) ? $mahasiswa['id_prodi'] : null;
            $session['prodi']    = isset($prodi['inisial']) ? $prodi['inisial'] : '-';

            return $this->attachStudentIdentity($session, $akunData, $roles);
        }

        if (in_array($primary, ['dosen', 'dospem eksternal'], true)) {
            $dosen = $this->dosenModel->where('email', $akunData['email'])->first();

            if ($dosen === null) {
                return null;
            }

            $prodi = ! empty($dosen['id_prodi']) ? $this->prodiModel->find($dosen['id_prodi']) : null;

            $session['id']    = $dosen['id'];
            $session['nama']  = $dosen['nama'];
            $session['prodi'] = isset($prodi['inisial']) ? $prodi['inisial'] : '-';

            return $this->attachStudentIdentity($session, $akunData, $roles);
        }

        if ($primary === 'tendik') {
            $tendik = $this->tendikModel->where('email', $akunData['email'])->first();

            if ($tendik === null) {
                return null;
            }

            $session['id']   = $tendik['id'];
            $session['nama'] = $tendik['nama'];

            return $this->attachStudentIdentity($session, $akunData, $roles);
        }

        if (in_array($primary, ['kaprodi', 'prodi'], true)) {
            $prodi = $this->prodiModel->where('email', $akunData['email'])->first();

            if ($prodi === null) {
                return null;
            }

            $session['id']    = $prodi['id'];
            $session['nama']  = $prodi['nama'];
            $session['prodi'] = $prodi['inisial'];

            return $this->attachStudentIdentity($session, $akunData, $roles);
        }

        if ($primary === 'fakultas') {
            $fakultas = $this->fakultasModel->where('email', $akunData['email'])->first();

            if ($fakultas === null) {
                return null;
            }

            $session['id']   = $fakultas['id'];
            $session['nama'] = $fakultas['nama'];

            return $this->attachStudentIdentity($session, $akunData, $roles);
        }

        if ($primary === 'klien') {
            $klien = $this->klienModel->where('email', $akunData['email'])->first();

            if ($klien === null) {
                return null;
            }

            $session['id']   = $klien['id'];
            $session['nama'] = $klien['nama'];

            return $this->attachStudentIdentity($session, $akunData, $roles);
        }

        // Tamu atau role lain yang tidak punya tabel profil khusus.
        return $this->attachStudentIdentity($session, $akunData, $roles);
    }

    /**
     * Attach NPM when a user has a student role, even when another role is
     * primary. This keeps ownership checks correct for multi-role accounts.
     */
    private function attachStudentIdentity(array $session, array $akunData, array $roles): array
    {
        if (! array_intersect($roles, ['mahasiswa', 'wisudawan'])) {
            return $session;
        }

        if (! empty($session['npm'])) {
            return $session;
        }

        $mahasiswa = $this->mahasiswaModel->where('email', $akunData['email'])->first();

        if ($mahasiswa === null) {
            return $session;
        }

        $session['npm'] = $mahasiswa['npm'];

        if (empty($session['id_prodi']) && ! empty($mahasiswa['id_prodi'])) {
            $session['id_prodi'] = $mahasiswa['id_prodi'];
        }

        if ((! isset($session['prodi']) || $session['prodi'] === '-') && ! empty($mahasiswa['id_prodi'])) {
            $prodi = $this->prodiModel->find($mahasiswa['id_prodi']);

            if (isset($prodi['inisial'])) {
                $session['prodi'] = $prodi['inisial'];
            }
        }

        return $session;
    }

    /**
     * Regenerate the session and send every role to the Thesis/Graduate chooser.
     */
    private function finishLogin(array $profile, $authMethod, $username, $akunId, $ipAddress, $reason = null)
    {
        $now = time();

        $profile['login_at']      = $now;
        $profile['last_activity'] = $now;
        $profile['auth_method']   = $authMethod;
        $profile['isLoggedIn']    = true;

        session()->regenerate(true);
        session()->set('user_session', $profile);
        session()->remove(['pendaftaran', 'active_side']);

        $this->writeAuthLog('login', 'success', $username, $akunId, $reason, $ipAddress, $authMethod);

                return redirect()->to(base_url(PortalRouter::CHOOSER));
    }

    private function registerFailedAttempt(LoginThrottle $throttle, $accountKey, $ipKey, $username, $akunId, $reason, $ipAddress, $authMethod)
    {
        $throttle->hit($accountKey);
        $throttle->hit($ipKey);

        $this->writeAuthLog('login', 'failed', $username, $akunId, $reason, $ipAddress, $authMethod);

        if ($reason === 'no_role') {
            $message = 'Akun Anda belum memiliki peran yang terdaftar. Silakan hubungi administrator.';
        } elseif ($reason === 'profile_missing') {
            $message = 'Data profil akun Anda tidak ditemukan. Silakan hubungi administrator.';
        } elseif ($reason === 'unavailable' || $reason === 'ldap_unavailable') {
            $message = 'Server LDAP kampus sedang tidak dapat dihubungi. Silakan coba lagi nanti atau gunakan kata sandi lokal cadangan jika tersedia.';
        } elseif ($reason === 'configuration' || $reason === 'ldap_configuration') {
            $message = 'Terjadi kendala pada konfigurasi server autentikasi LDAP. Silakan hubungi administrator.';
        } else {
            $message = 'Username atau kata sandi salah.';
        }

        return $this->loginError($message, $username);
    }

    private function clearLoginThrottle(LoginThrottle $throttle, $accountKey, $ipKey)
    {
        $throttle->clear($accountKey);
        $throttle->clear($ipKey);
    }

    private function loginError($message, $username = '')
    {
        session()->setFlashdata('message', [
            'icon'  => 'error',
            'title' => 'Login Gagal',
            'text'  => $message,
        ]);

        // Simpan hanya username; password tidak boleh masuk flashdata.
        session()->setFlashdata('login_username', $username);

        if ($this->request->getPost('use_local_password') === '1') {
            session()->setFlashdata('login_use_local', true);
        }

        return redirect()->to(base_url('auth/login'));
    }

    private function registrationError($message)
    {
        session()->setFlashdata('message', [
            'icon'  => 'error',
            'title' => 'Pendaftaran Gagal',
            'text'  => $message,
        ]);

        return redirect()->to(base_url('auth/pendaftaran'));
    }

    /**
     * Write an audit row. A missing table or database error must never break login.
     */
    private function writeAuthLog($event, $status, $username, $akunId, $reason, $ipAddress, $authMethod)
    {
        try {
            $userSession = session()->get('user_session');
            $roles       = [];

            if (is_array($userSession) && isset($userSession['roles']) && is_array($userSession['roles'])) {
                $roles = $userSession['roles'];
            }

            $model = new AuthLogModel();
            $model->insert([
                'username'    => substr((string) $username, 0, 100),
                'akun_id'     => $akunId,
                'event'       => $event,
                'status'      => $status,
                'auth_method' => $authMethod,
                'roles'       => $roles === [] ? null : json_encode($roles),
                'reason'      => $reason,
                'ip_address'  => $ipAddress,
                'user_agent'  => substr((string) $this->request->getUserAgent(), 0, 255),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menulis auth log: ' . $e->getMessage());
        }
    }

    /**
     * Ambil data pendaftaran yang sudah diverifikasi LDAP dari session.
     *
     * @return array|null
     */
    private function getVerifiedRegistration()
    {
        $pendaftaran = session()->get('pendaftaran');

        if (! is_array($pendaftaran) || empty($pendaftaran['username'])) {
            return null;
        }

        return $pendaftaran;
    }

    /**
     * Menampilkan formulir pendaftaran lanjutan setelah verifikasi LDAP.
     */
    public function pendaftaran()
    {
        $dataPendaftaran = session()->get('pendaftaran');

        if (! is_array($dataPendaftaran)) {
            return redirect()->to(base_url('auth/login'));
        }

        $userData = [
            'id_nik'      => isset($dataPendaftaran['id_nik']) ? $dataPendaftaran['id_nik'] : '',
            'displayname' => isset($dataPendaftaran['displayname']) ? $dataPendaftaran['displayname'] : '',
            'username'    => isset($dataPendaftaran['username']) ? $dataPendaftaran['username'] : '',
        ];

        $role = isset($dataPendaftaran['role']) ? $dataPendaftaran['role'] : '';

        if ($role === 'Mahasiswa') {
            return view('auth/pendaftaranMahasiswa', [
                'userData' => $userData,
                'prodi'    => $this->prodiModel->findAll(),
            ]);
        }

        if ($role === 'Dosen') {
            return view('auth/pendaftaranDosen', [
                'userData' => $userData,
                'prodi'    => $this->prodiModel->findAll(),
            ]);
        }

        if ($role === 'Staff') {
            return view('auth/pendaftaranTendik', [
                'userData' => $userData,
            ]);
        }

        return redirect()->to(base_url('auth/login'));
    }

    public function pendaftaranMahasiswa()
    {
        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $pendaftaran = $this->getVerifiedRegistration();

        if ($pendaftaran === null) {
            return $this->loginError('Sesi verifikasi LDAP tidak ditemukan. Silakan login ulang.');
        }

        $nama        = trim((string) $this->request->getPost('nama'));
        $npm         = trim((string) $this->request->getPost('npm'));
        $username    = trim((string) $this->request->getPost('username'));
        $prodi       = trim((string) $this->request->getPost('prodi'));
        $angkatan    = trim((string) $this->request->getPost('angkatan', FILTER_SANITIZE_SPECIAL_CHARS));
        $email       = trim((string) $this->request->getPost('email', FILTER_SANITIZE_EMAIL));
        $persetujuan = $this->request->getPost('persetujuan');

        if ($nama === '' || $npm === '' || $username === '' || $prodi === '' || $angkatan === '' || $email === '') {
            return $this->registrationError('Semua kolom wajib diisi.');
        }

        if ($persetujuan === null || $persetujuan === '') {
            return $this->registrationError('Pernyataan data wajib disetujui.');
        }

        if (! preg_match('/^\d{10}$/', $npm)) {
            return $this->registrationError('NPM tidak valid.');
        }

        if ($username !== (string) $pendaftaran['username'] || $npm !== (string) $pendaftaran['id_nik']) {
            return $this->loginError('Data pendaftaran tidak cocok dengan sesi verifikasi LDAP. Silakan login ulang.', $username);
        }

        if (! preg_match('/^\d{4}$/', $angkatan)) {
            return $this->registrationError('Tahun angkatan harus terdiri dari 4 digit.');
        }

        $angkatanDariNpm = substr($npm, 3, 4);
        $tahunSekarang    = (int) date('Y');
        $angkatanMin      = $tahunSekarang - 7;
        $angkatanMax      = $tahunSekarang - 2;

        if ($angkatan !== $angkatanDariNpm) {
            return $this->registrationError('Tahun angkatan tidak sesuai dengan NPM.');
        }

        if ((int) $angkatan < $angkatanMin || (int) $angkatan > $angkatanMax) {
            return $this->registrationError('Tahun angkatan harus antara ' . $angkatanMin . ' dan ' . $angkatanMax . '.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->registrationError('Format email tidak valid.');
        }

        if ($this->akunModel->where('email', $email)->first() !== null) {
            return $this->registrationError('Email sudah terdaftar dalam sistem.');
        }

        if ($this->akunModel->where('username', $username)->first() !== null) {
            return $this->registrationError('Username sudah terdaftar dalam sistem.');
        }

        if ($this->mahasiswaModel->find($npm) !== null) {
            return $this->registrationError('NPM sudah terdaftar dalam sistem.');
        }

        $roleMahasiswa = $this->roleModel->where('nama', 'mahasiswa')->first();
        if ($roleMahasiswa === null) {
            return $this->registrationError('Role mahasiswa belum tersedia.');
        }

        $dataProdi = $this->prodiModel->find($prodi);
        if ($dataProdi === null) {
            return $this->registrationError('Program studi tidak valid.');
        }

        $this->mahasiswaModel->insert([
            "npm" => $npm,
            'nama' => $nama,
            'email' => $email,
            'angkatan' => $angkatan,
            'id_prodi' => $prodi,
        ]);

        $dataMahasiswa = $this->mahasiswaModel->find($npm);

        $this->akunModel->insert([
            'username' => $username,
            'email' => $email,
            'status' => 'ACTIVE'
        ]);


        $idRoleMahasiswa = $roleMahasiswa['id'];
        $idAkun = $this->akunModel->where("email", $email)->first()['id'];

        $this->aksesModel->save([
            'id_akun' => $idAkun,
            'id_role' => $idRoleMahasiswa
        ]);

        session()->set("pendaftaran", null);
        session()->set("user_session", [
            'id' => $dataMahasiswa['npm'],
            'akun_id' => $idAkun,
            'npm' => $dataMahasiswa['npm'],
            'nama' => $dataMahasiswa['nama'],
            'email' => $dataMahasiswa['email'],
            'prodi' => $dataProdi['inisial'],
            'roles' => ['mahasiswa'],
            'isLoggedIn' => true,
            'login_at' => time(),
            'last_activity' => time(),
        ]);

        return redirect()->to(base_url(PortalRouter::CHOOSER));
    }



    public function pendaftaranDosen()
    {

        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();
        }



        $pendaftaran = $this->getVerifiedRegistration();

        if ($pendaftaran === null) {

            return $this->loginError('Sesi verifikasi LDAP tidak ditemukan. Silakan login ulang.');
        }



        $email = $this->request->getPost("email", FILTER_SANITIZE_SPECIAL_CHARS);

        $isEmailUsed = $this->akunModel->where("email", $email)->first();

        if ($isEmailUsed) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Login Gagal", "text" => "Email sudah terdaftar dalam sistem"]);

            return redirect()->back();
        }



        $inisial = $this->request->getPost("inisial", FILTER_SANITIZE_SPECIAL_CHARS);

        $isInisialUsed = $this->dosenModel->where("inisial", $inisial)->first();

        if ($isInisialUsed) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Login Gagal", "text" => "Inisial sudah terdaftar dalam sistem"]);

            return redirect()->back();
        }



        $RoleDosen = $this->roleModel->where("nama", "dosen")->first();

        if ($RoleDosen == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Login Gagal", "text" => "Database Error"]);

            return redirect()->back();
        }



        $nama = $this->request->getPost("nama");

        $username = $this->request->getPost("username");

        if (trim((string) $username) !== $pendaftaran['username']) {

            return $this->loginError('Data pendaftaran tidak cocok dengan sesi verifikasi LDAP. Silakan login ulang.', $username);
        }

        $prodi = $this->request->getPost("prodi");

        $password = $this->request->getPost("password");



        $this->dosenModel->insert([

            "nama" => $nama,

            'email' => $email,

            'inisial' => $inisial,

            'id_prodi' => $prodi,

        ]);

        $dataDosen = $this->dosenModel->where("inisial", $inisial)->first();



        $this->akunModel->insert([

            'username' => $username,

            'email' => $email,

            'status' => 'ACTIVE'

        ]);



        $idRoleDosen = $RoleDosen['id'];

        $idAkun = $this->akunModel->where("email", $email)->first()['id'];

        $dataProdi = $this->prodiModel->find($prodi);



        $this->aksesModel->save([

            'id_akun' => $idAkun,

            'id_role' => $idRoleDosen

        ]);

        session()->set("pendaftaran", null);

        session()->set("user_session", [

            'id' => $dataDosen['id'],
            'akun_id' => $idAkun,

            'nama' => $dataDosen['nama'],

            'email' => $dataDosen['email'],
            'prodi' => $dataProdi['inisial'],

            'roles' => ['dosen'],
            'isLoggedIn' => true,
            'login_at' => time(),
            'last_activity' => time(),

        ]);



        return redirect()->to(base_url(PortalRouter::CHOOSER));
    }



    public function pendaftaranTendik()
    {

        //buat mencegah access langsung dari link

        if ($this->request->getMethod() != 'post') {

            return redirect()->back();
        }



        $pendaftaran = $this->getVerifiedRegistration();

        if ($pendaftaran === null) {

            return $this->loginError('Sesi verifikasi LDAP tidak ditemukan. Silakan login ulang.');
        }



        $email = $this->request->getPost("email", FILTER_SANITIZE_SPECIAL_CHARS);

        $isEmailUsed = $this->akunModel->where("email", $email)->first();

        if ($isEmailUsed) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Login Gagal", "text" => "Email sudah terdaftar dalam sistem"]);

            return redirect()->back();
        }



        $RoleTendik = $this->roleModel->where("nama", "tendik")->first();

        if ($RoleTendik == null) {

            session()->setFlashdata("message", ["icon" => "error", "title" => "Login Gagal", "text" => "Database Error"]);

            return redirect()->back();
        }



        $nama = $this->request->getPost("nama");

        $username = $this->request->getPost("username");

        if (trim((string) $username) !== $pendaftaran['username']) {

            return $this->loginError('Data pendaftaran tidak cocok dengan sesi verifikasi LDAP. Silakan login ulang.', $username);
        }

        $password = $this->request->getPost("password");



        $this->tendikModel->insert([

            "nama" => $nama,

            'email' => $email,

        ]);

        $dataTendik = $this->tendikModel->where("email", $email)->first();



        $this->akunModel->insert([

            'username' => $username,

            'email' => $email,

            'status' => 'ACTIVE'

        ]);



        $idRoleTendik = $RoleTendik['id'];

        $idAkun = $this->akunModel->where("email", $email)->first()['id'];



        $this->aksesModel->save([

            'id_akun' => $idAkun,

            'id_role' => $idRoleTendik

        ]);

        session()->set("pendaftaran", null);

        session()->set("user_session", [

            'id' => $dataTendik['id'],
            'akun_id' => $idAkun,

            'nama' => $dataTendik['nama'],

            'email' => $dataTendik['email'],

            'roles' => ['tendik'],
            'isLoggedIn' => true,
            'login_at' => time(),
            'last_activity' => time(),

        ]);



        return redirect()->to(base_url(PortalRouter::CHOOSER));
    }


    public function pendaftaranTamu()
    {
        return view("auth/pendaftaranTamu");
    }

    public function insertTamu()
    {
        //buat mencegah access langsung dari link
        if ($this->request->getMethod() != 'post') {
            return redirect()->back();
        }

        $email = $this->request->getPost("email", FILTER_SANITIZE_SPECIAL_CHARS);
        $isEmailUsed = $this->akunModel->where("email", $email)->first();
        if ($isEmailUsed) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Registrasi Gagal", "text" => "Email telah digunakan"]);
            return redirect()->back();
        }

        $inisial = $this->request->getPost("inisial", FILTER_SANITIZE_SPECIAL_CHARS);
        $isInisialUsed = $this->dosenModel->where("inisial", $inisial)->first();
        if ($isInisialUsed) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Registrasi Gagal", "text" => "Inisial telah digunakan"]);
            return redirect()->back();
        }

        $username = $this->request->getPost("username", FILTER_SANITIZE_SPECIAL_CHARS);
        $isUsernameUsed = $this->akunModel->where("username", $username)->first();
        if ($isUsernameUsed) {
            session()->setFlashdata("message", ["icon" => "error", "title" => "Registrasi Gagal", "text" => "Username telah digunakan"]);
            return redirect()->back();
        }

        $nama = $this->request->getPost("nama", FILTER_SANITIZE_SPECIAL_CHARS);
        $password = $this->request->getPost("password");
        $instansi = $this->request->getPost("instansi", FILTER_SANITIZE_SPECIAL_CHARS);


        $this->persetujuanTamuModel->insert([
            "nama" => $nama,
            "inisial" => $inisial,
            "instansi" => $instansi,
            'email' => $email,
            "username" => $username,
            "password" => password_hash($password, PASSWORD_DEFAULT),
            "tanggal_pendaftaran" => date_format(Time::now('Asia/Jakarta', 'en_us'), 'Y-m-d H:i:s')
        ]);

        session()->setFlashdata("message", ["icon" => "success", "title" => "Registrasi Berhasil", "text" => "Registrasi Akun anda telah berhasil"]);
        return redirect()->to(base_url("auth"));
    }

    public function testEmail()
    {
        $email = \Config\Services::email();
        $email->clear(true);

        $emailConfig = config('Email');
        $from = ! empty($emailConfig->fromEmail) ? $emailConfig->fromEmail : 'zianbertanya2@gmail.com';
        $to = $this->request->getGet('to') ?: 'zildjian1121@gmail.com';

        $email->setFrom($from, 'Test Email TheSIS');
        $email->setTo($to);
        $email->setSubject('Uji Coba Pengiriman Email TheSIS');
        $email->setMessage('Halo! Jika email ini masuk ke inbox Anda, konfigurasi SMTP Gmail telah 100% BERHASIL dan SIAP digunakan.');

        if ($email->send()) {
            return "<div style='font-family: Arial; padding: 20px; background: #d4edda; color: #155724; border-radius: 8px; border: 1px solid #c3e6cb;'>"
                 . "<h2>✓ BERHASIL!</h2>"
                 . "<p>Email berhasil dikirim dari <strong>{$from}</strong> ke <strong>{$to}</strong>.</p>"
                 . "<p>Silakan periksa kotak masuk (Inbox atau Spam) pada akun {$to}.</p>"
                 . "</div>";
        } else {
            echo "<div style='font-family: Arial; padding: 20px; background: #f8d7da; color: #721c24; border-radius: 8px; border: 1px solid #f5c6cb;'>";
            echo "<h2>✗ GAGAL MENGIRIM EMAIL</h2>";
            echo "<p>Pesan error dari server Google:</p>";
            echo "<pre style='background: #fff; padding: 15px; border: 1px solid #ddd;'>" . print_r($email->printDebugger(['headers']), true) . "</pre>";
            echo "</div>";
        }
    }
}
