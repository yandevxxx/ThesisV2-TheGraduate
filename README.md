# ThesisV2 - The Graduate

Aplikasi gabungan **TheSIS** (manajemen skripsi) dan **The Graduate** (kelulusan & alumni)
berbasis CodeIgniter 4. Setelah login, semua pengguna melewati halaman pemilihan sistem
(`/graduate/choose`) sebelum masuk ke sisi Thesis atau The Graduate.

## Alur login

1. Pengguna membuka `/auth/login`.
2. Kredensial diverifikasi **LDAP-first**:
   - Semua akun di tabel `akun` diperiksa ke LDAP lebih dulu.
   - Hasil LDAP hanya dipakai bila role LDAP kompatibel dengan role lokal:
     `Mahasiswa` → `mahasiswa`/`wisudawan`; `Dosen` → `dosen`/`dospem eksternal`;
     `Staff` → `tendik`.
   - Role `admin`, `kaprodi`, `fakultas`, `klien`, dan `tamu` tetap lokal. Bila LDAP
     memberi role lain, hasil LDAP ditolak.
   - Bila LDAP gagal/tidak tersedia atau role tidak kompatibel, hash kata sandi lokal
     dipakai sebagai cadangan bila tersedia.
   - Akun dengan `password NULL` bersifat **LDAP-only** dan tidak punya cadangan lokal.
   - Pengguna dapat mencentang **Gunakan kata sandi lokal (cadangan)** untuk melewati
     LDAP saat server kampus bermasalah.
3. Semua kegagalan kredensial menampilkan pesan yang sama. Detail sumber hanya dicatat
   di `auth_logs.reason`.
4. Login berhasil selalu diarahkan ke `/graduate/choose`.
5. Pengguna memilih **THESIS** atau **THE GRADUATE**. Role tanpa akses Graduate hanya
   melihat kartu Thesis.
6. Setiap role mendarat di dashboard Thesis/Graduate sesuai matriks di bawah.

Akun LDAP yang belum memiliki akun lokal akan diprovision otomatis bila profil lokalnya
sudah tersedia (mahasiswa/dosen dari data akademik) atau dapat dibuat dari nama + email
(tendik). Bila data belum cukup, pengguna diarahkan ke formulir pendaftaran untuk
melengkapi profil. Perubahan profil setelah login disinkronkan lewat antrean background.

## Kebutuhan sistem

- PHP 7.3 atau lebih baru (kode baru dijaga kompatibel PHP 7.3; pengembangan diuji pada PHP 8.4)
- Ekstensi: `intl`, `mbstring`, `json`, `xml`, `mysqlnd`, dan `ldap` (untuk login kampus)
- MySQL/MariaDB
- Composer

## Instalasi

```bash
composer install
copy env .env        # Windows
# cp env .env        # Linux/macOS
```

Sesuaikan minimal konfigurasi berikut di `.env`:

```dotenv
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = thesis_v2
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
```

Jalankan migrasi lalu server pengembangan:

```bash
php spark migrate
php spark serve
```

## Konfigurasi LDAP

Semua nilai dapat di-override dari `.env` dengan prefix `authldap.`. Default ada di
`app/Config/AuthLdap.php`.

```dotenv
authldap.ldapDomain = pdc.yarsi.ac.id
authldap.baseDn = 'dc=yarsi,dc=ac,dc=id'
authldap.groupsDn = 'ou=Groups,dc=yarsi,dc=ac,dc=id'
authldap.useTls = false
authldap.useStartTls = false
authldap.bindDn = ''
authldap.bindPassword = ''
authldap.ldapUserAttribute = 'uid'
authldap.ldapGroupAttribute = 'cn'
authldap.roleAttribute = 'title'
authldap.networkTimeout = 5
authldap.searchSizeLimit = 2
```

Catatan:

- `useTls = true` memakai LDAPS (port 636). `useStartTls = true` memakai StartTLS pada
  koneksi biasa (port 389). Port diatur di `app/Config/AuthLdap.php` (`$tcpPort`).
- Isi `bindDn`/`bindPassword` bila server LDAP tidak mengizinkan bind anonim untuk
  pencarian. Jangan commit kredensial ke Git.
- Nilai username di tabel `akun` harus sama dengan atribut LDAP `uid`.
- Atribut `title` dipetakan ke role: `M*`/Mahasiswa/Student, `D*`/Dosen/Lecturer,
  `S*`/Staff/Tendik.

## Keamanan autentikasi

Konfigurasi ada di `app/Config/AuthSecurity.php` dan dapat di-override dari `.env`:

```dotenv
authsecurity.loginMaxAttempts = 5
authsecurity.loginDecaySeconds = 120
authsecurity.ipMaxAttempts = 20
authsecurity.ipDecaySeconds = 120
authsecurity.sessionIdleTimeout = 1800
authsecurity.sessionAbsoluteTimeout = 28800
```

Yang sudah aktif:

- **CSRF** pada `POST auth/signInAccount`.
- **Throttle login** per akun+IP dan per IP. Percobaan berlebih ditolak sementara dan
  dicatat sebagai `blocked/throttled`.
- **Audit login & logout** ke tabel `auth_logs` (sukses, gagal, diblokir, alasan, IP, user agent).
- **Regenerasi session ID** setelah login berhasil.
- **Idle & absolute timeout** melalui filter global `SessionTimeoutFilter`. Sesi yang idle melebihi
  `authsecurity.sessionIdleTimeout` (30 menit) atau melewati masa berlaku absolut sejak login
  `authsecurity.sessionAbsoluteTimeout` (8 jam) diakhiri dan pengguna diarahkan ke login (atau 401 untuk request JSON/AJAX).
- **Logout bersih**: Menghapus data sesi di server & memori (`user_session`, `pendaftaran`, `active_side`)
  serta menghapus cookie sesi `ci_session` di browser.
- **Cookie** `HttpOnly` dan `SameSite=Lax`. Pada deployment HTTPS aktifkan:

  ```dotenv
  cookie.secure = true
  ```

- Pesan gagal login tidak membocorkan apakah username terdaftar maupun apakah kegagalan
  berasal dari LDAP atau kata sandi lokal. Detail hanya ditulis ke `auth_logs.reason`.
- Kata sandi tidak pernah ditulis ke log, audit, flashdata, atau session; hanya username,
  sumber autentikasi, dan alasan yang dicatat.
- Form pendaftaran akun LDAP hanya dapat dikirim bila sesi verifikasi LDAP masih ada;
  username (dan NPM untuk mahasiswa) harus sama dengan hasil verifikasi LDAP.

## Migrasi dan role wisudawan

Migrasi `2026-10-27-120000_AddAuthLogsAndWisudawanRole` membuat tabel `auth_logs`,
menambahkan role `wisudawan` (id 9), dan memberikannya kepada mahasiswa yang skripsinya
berstatus `Lulus`. Role ini bersifat **aditif**: akun tetap memiliki role `mahasiswa`.

Jalankan ulang sinkronisasi setiap kali data kelulusan bertambah:

```bash
php spark auth:assign-wisudawan --dry-run
php spark auth:assign-wisudawan
```

Contoh cron harian:

```cron
0 2 * * * cd /path/to/project && php spark auth:assign-wisudawan >> writable/logs/auth-wisudawan.log 2>&1
```

## Sinkronisasi profil LDAP (B-03)

Setelah LDAP memverifikasi kredensial, aplikasi mencari profil lokal:

- **Mahasiswa**: berdasarkan NPM dari atribut `description`, lalu email.
- **Dosen**: berdasarkan email.
- **Tendik**: berdasarkan email, lalu nama; profil tendik baru boleh dibuat dari
  nama + email LDAP.

Bila profil ditemukan dan belum punya akun, aplikasi membuat baris `akun` (password
`NULL`), menetapkan role, lalu login dilanjutkan ke chooser. Bila profil tidak ditemukan
atau email bentrok dengan akun lain, pengguna diarahkan ke formulir pendaftaran B-02.

Setiap login LDAP yang berhasil untuk mahasiswa, dosen, atau tendik menambahkan
pekerjaan ke tabel `ldap_profile_sync`.
Command berikut memperbarui `akun.email`, `mahasiswa.nama/email`, `dosen.nama/email`, atau
`tenaga_kependidikan.nama/email` dari data LDAP:

```bash
php spark auth:sync-ldap-profiles --dry-run
php spark auth:sync-ldap-profiles --limit=100
```

Contoh cron setiap 10 menit:

```cron
*/10 * * * * cd /path/to/project && php spark auth:sync-ldap-profiles --limit=100 >> writable/logs/auth-ldap-sync.log 2>&1
```

Perilaku kegagalan:

- Login **tetap berhasil** walau antrean gagal dibuat atau diproses.
- Setiap kegagalan dicatat di `ldap_profile_sync.last_error` dan `auth_logs`
  (`event = profile_sync`, `status = failed`).
- Pekerjaan diulang maksimal 5 kali dengan jeda bertahap; setelah itu status menjadi
  `failed` dan perlu ditangani manual.
- Antrean yang tertinggal di status `processing` lebih dari 15 menit (mis. proses CLI
  terhenti) dijadwalkan ulang otomatis pada eksekusi berikutnya.
- Konflik email tidak menimpa data: pekerjaan gagal dan pemilik lama dipertahankan.

Periksa antrean bermasalah:

```sql
SELECT id, username, role, status, attempts, last_error, available_at
FROM ldap_profile_sync
WHERE status IN ('pending', 'failed')
ORDER BY id DESC LIMIT 20;
```

Catatan: tabel profil saat ini tidak memiliki kolom telepon/alamat, sehingga atribut
`street`, `telephoneNumber`, dan sejenisnya hanya disimpan di payload antrean, belum
ditulis ke profil. Role yang sudah dimiliki akun lokal tidak diubah otomatis oleh
sinkronisasi (mencegah perubahan hak akses tanpa persetujuan).

## Login LDAP-first dan cadangan lokal (B-04)

Urutan verifikasi untuk akun yang sudah ada di tabel `akun`:

1. **LDAP** diperiksa lebih dulu, termasuk akun yang memiliki kata sandi lokal.
2. Hasil LDAP diterima hanya bila role LDAP kompatibel dengan **seluruh** role lokal
   akun tersebut. Tabel kompatibilitas:

   | Role LDAP | Role lokal yang boleh |
   | --- | --- |
   | `Mahasiswa` | `mahasiswa`, `wisudawan` |
   | `Dosen` | `dosen`, `dospem eksternal` |
   | `Staff` | `tendik` |

3. Bila LDAP gagal, tidak tersedia, atau role tidak kompatibel, kata sandi lokal
   diverifikasi terhadap hash di `akun.password`.
4. Bila atribut role LDAP kosong, akun hanya boleh lewat LDAP jika seluruh role lokalnya
   termasuk role yang dikelola LDAP (`mahasiswa`, `wisudawan`, `dosen`, `dospem eksternal`,
   `tendik`). Akun dengan role lokal lain wajib memakai kata sandi lokal.
5. Centang **Gunakan kata sandi lokal (cadangan)** untuk melewati LDAP sepenuhnya. Audit
   mencatat `auth_method = manual_local`.

Akun tamu tetap memakai tabel `persetujuan_tamu` dan jalur kata sandi lokal; tamu tidak
melewati LDAP.

Aturan keamanan:

- Hanya nilai yang lolos `password_get_info()` yang dianggap hash. Plain text, string
  kosong, dan `NULL` tidak pernah dipakai sebagai cadangan.
- Semua kegagalan kredensial (LDAP salah, LDAP tidak tersedia, konfigurasi LDAP rusak,
  role tidak cocok, atau kata sandi lokal salah) menampilkan
  **"Username atau kata sandi salah."**.
- Pesan khusus hanya untuk `no_role`, `profile_missing`, throttle, dan input kosong.
- Backdoor kata sandi `Admin`/`12345678` dan route `dev/login` sudah dihapus.
- Debugbar development memakai `SafeToolbar`; field kata sandi tidak pernah ditulis ke
  `writable/debugbar`.
- Migrasi `2026-10-27-140000_HardenAkunPasswordColumn` memvalidasi seluruh nilai
  `akun.password` non-NULL adalah hash sebelum melebarkan kolom ke `varchar(255)`.
  Bila ada data non-hash, migrasi dibatalkan dan hanya menampilkan ID akun (tanpa nilai
  kata sandi). Rollback mengembalikan lebar kolom ke `150`.

Alasan audit yang mungkin muncul: `invalid_credentials`, `ldap_unavailable`,
`ldap_configuration`, `ldap_role_mismatch`, `inactive_account`, `no_role`,
`profile_missing`, `throttled`, dan `manual_local` (pada `auth_method`).

### Uji manual B-04

1. Cadangkan `.env`, lalu arahkan LDAP ke host yang tidak terjangkau:

   ```dotenv
   authldap.ldapDomain = 192.0.2.1
   authldap.networkTimeout = 2
   ```

2. Login akun ber-hash tanpa checkbox → tetap masuk ke `/graduate/choose`. Audit:
   `status=success`, `auth_method=local`, `reason=ldap_unavailable`.
3. Logout, login lagi dengan checkbox → berhasil tanpa menunggu timeout LDAP. Audit:
   `auth_method=manual_local`.
4. Login dengan kata sandi salah (checkbox aktif maupun tidak) → pesan
   **"Username atau kata sandi salah."**, username tetap terisi dan status checkbox
   tidak berubah.
5. Login akun `password NULL` saat LDAP tidak terjangkau → pesan generik yang sama,
   audit `auth_method=ldap`, `reason=ldap_unavailable`.
6. Pulihkan `.env`, lalu pastikan tidak ada kata sandi di `writable/logs`,
   `writable/debugbar`, `writable/session`, maupun `auth_logs`.

## Matriks landing per role

Semua role melewati `/graduate/choose` terlebih dahulu.

| Role | Thesis | The Graduate |
| --- | --- | --- |
| mahasiswa | `home` | `graduate/mahasiswa` |
| wisudawan | `home` | `graduate/wisudawan` |
| tendik | `fakultas/dashboard` | `graduate/admin_tendik` |
| dosen | `dosen/index` | `graduate/dosen` |
| dospem eksternal | `dosen/index` | `graduate/dosen` |
| kaprodi | `kaprodi/dashboard` | `graduate/prodi` |
| fakultas | `fakultas/dashboard` | `graduate/admin_tendik` |
| admin | `admin/dashboard` | `graduate/admin_tendik` |
| klien / tamu | `home` | Tidak tersedia (kartu disembunyikan) |

Role yang membuka folder Graduate milik role lain akan diarahkan kembali ke dashboard
role-nya sendiri.

## Uji manual B-02

1. Login dengan akun valid → diarahkan ke `/graduate/choose`, label role benar.
2. Login dengan password salah → muncul "Username atau kata sandi salah.", username
   terisi ulang, password tidak.
3. Gunakan host LDAP yang tidak terjangkau → akun ber-hash lokal tetap bisa masuk lewat
   fallback, sedangkan akun LDAP-only gagal dengan **"Username atau kata sandi salah."**
   (skenario lengkap ada di Uji manual B-04).
4. Lakukan 5 kali login gagal → percobaan ke-6 menampilkan pesan throttle.
5. Pilih Thesis/Graduate → mendarat sesuai matriks role. Coba buka folder Graduate role
   lain → dialihkan ke dashboard sendiri.
6. Logout → kembali ke login. Buka `/graduate/choose` tanpa sesi → diarahkan ke login.
7. Biarkan sesi idle melebihi batas → muncul "Sesi Berakhir" dan diarahkan ke login.
8. Periksa audit:

   ```sql
   SELECT username, event, status, auth_method, reason, ip_address, created_at
   FROM auth_logs ORDER BY id DESC LIMIT 20;
   ```

9. Kirim `POST /auth/signInAccount` tanpa token CSRF → ditolak dan tidak ada baris audit
   baru.

## Uji manual B-03

1. Jalankan `php spark migrate`, lalu pastikan tabel `ldap_profile_sync` ada.
2. Login LDAP dengan akun yang profil lokalnya sudah ada tetapi belum punya baris `akun`
   → login langsung ke chooser. Periksa:

   ```sql
   SELECT * FROM akun WHERE username = 'USERNAME_LDAP';
   SELECT * FROM akses WHERE id_akun = ID_AKUN;
   SELECT * FROM ldap_profile_sync WHERE username = 'USERNAME_LDAP';
   SELECT * FROM auth_logs WHERE username = 'USERNAME_LDAP' ORDER BY id DESC;
   ```

   Harapan: akun baru berpassword `NULL`, role benar, antrean `pending`, audit
   `profile_sync` sukses dengan alasan `auto_provision`.
3. Login LDAP dengan akun yang belum punya profil lokal → diarahkan ke formulir
   pendaftaran, bukan dibuat otomatis.
4. Jalankan `php spark auth:sync-ldap-profiles`, lalu ubah nama/email di LDAP dan login
   lagi. Setelah command dijalankan, nama/email lokal ikut berubah dan status antrean
   menjadi `done`.
5. Uji konflik: buat antrean dengan email milik akun lain, jalankan command. Harapan:
   `last_error` terisi, audit `profile_sync/failed`, data lama tidak tertimpa, dan login
   pengguna tetap berhasil.
6. Login tendik LDAP baru tanpa profil lokal → profil `tenaga_kependidikan` dibuat
   otomatis dari nama + email.

## Pengujian otomatis

```bash
vendor\bin\phpunit --no-coverage tests/unit   # Windows
vendor/bin/phpunit --no-coverage tests/unit   # Linux/macOS
```

Unit test mencakup `PortalRouter`, escaping/klasifikasi/pemetaan role LDAP,
`LoginThrottle`, pemetaan payload dan role `LdapProfileSync`, serta B-04:
`LocalCredential`, `LoginPolicy`, dan `LoginResolver` (LDAP-first, role gate,
fallback lokal, dan mode `manual_local`).

## Batasan dan tindak lanjut

- **Prodi/kaprodi tetap memakai password lokal.** Keputusan B-02: kriteria Prodi-LDAP
  ditunda; kaprodi tidak dipindahkan ke LDAP pada iterasi ini.
- **Fallback lokal B-04 hanya berlaku untuk akun ber-hash.** Akun LDAP-only
  (`password NULL`) tetap gagal saat LDAP tidak dapat memverifikasi kredensial.
- **B-03 memakai provisioning selektif.** Akun baru dibuat otomatis hanya bila profil
  lokal sudah ada (mahasiswa/dosen) atau dapat dibuat dari nama + email (tendik). Profil
  mahasiswa/dosen yang belum ada tetap harus melalui formulir karena NPM/prodi/angkatan/
  inisial belum tersedia di LDAP.
- **Sinkronisasi background membutuhkan cron.** Tanpa cron, antrean `ldap_profile_sync`
  akan menumpuk (login tetap berjalan).
- **Atribut telepon/alamat belum disimpan** karena tabel profil belum memiliki kolomnya.
- **Uji LDAP kampus belum dilakukan** karena memerlukan akun tes LDAP dan akses jaringan
  kampus.
- **Role wisudawan** perlu disinkronkan berkala (lihat command di atas).
- **Tabel `auth_logs` dan `ldap_profile_sync`** akan terus bertambah; siapkan kebijakan
  retensi/rotasi.
- **CSRF** saat ini hanya dipasang pada route login. Form pendaftaran/password reset
  mengikuti pola lama dan perlu ditambahkan pada iterasi berikutnya.

## Berkas penting

- `app/Libraries/PortalRouter.php` — sumber tunggal matriks role dan landing.
- `app/Libraries/AuthLdap.php` — klien LDAP dan klasifikasi error.
- `app/Libraries/LdapProfileSync.php` — provisioning dan antrean sinkronisasi profil.
- `app/Libraries/LocalCredential.php` — validasi hash dan verifikasi kata sandi lokal.
- `app/Libraries/LoginPolicy.php` — aturan kompatibilitas role LDAP dengan role lokal.
- `app/Libraries/LoginResolver.php` - orkestrasi LDAP-first dan fallback lokal.
- `app/Libraries/SafeToolbar.php` - toolbar debug yang tidak menyimpan field kata sandi.
- `app/Libraries/LoginThrottle.php` - throttle login berbasis cache.
- `app/Controllers/Auth.php` — alur login, provisioning, audit, dan session.
- `app/Controllers/Graduate.php` — chooser, guard, dan dashboard per role.
- `app/Config/AuthLdap.php`, `app/Config/AuthSecurity.php` — konfigurasi.
- `app/Config/Routes.php`, `app/Config/Filters.php` — route dan filter global.
- `app/Database/Migrations/2026-10-27-120000_AddAuthLogsAndWisudawanRole.php`.
- `app/Database/Migrations/2026-10-27-130000_CreateLdapProfileSyncQueue.php`.
- `app/Database/Migrations/2026-10-27-140000_HardenAkunPasswordColumn.php`.
- `app/Commands/AssignWisudawanRole.php`, `app/Commands/SyncLdapProfiles.php`.
- `tests/unit/PortalRouterTest.php`, `tests/unit/AuthLdapTest.php`,
  `tests/unit/LoginThrottleTest.php`, `tests/unit/LdapProfileSyncTest.php`,
  `tests/unit/LocalCredentialTest.php`, `tests/unit/LoginPolicyTest.php`,
  `tests/unit/LoginResolverTest.php`.

---

Dokumentasi framework: <https://codeigniter4.github.io/userguide/>.
