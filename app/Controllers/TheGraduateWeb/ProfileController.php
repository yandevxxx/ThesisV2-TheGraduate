<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\PortalRouter;
use App\Models\MahasiswaTerplotModel;

/**
 * Controller Ubah Data Diri Sisi The Graduate
 *
 * Menangani tampilan halaman profil dan pembaruan data pengguna
 * lintas berbagai role di lingkungan The Graduate.
 */
class ProfileController extends BaseController
{
    /**
     * Halaman ubah data diri akun aktif.
     */
    public function profile()
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);

        if (! PortalRouter::canUseGraduate($roles)) {
            return redirect()->to(base_url(PortalRouter::CHOOSER))
                ->with('error', 'The Graduate belum tersedia untuk peran Anda.');
        }

        $primary   = PortalRouter::primaryRole($roles);
        $userEmail = $userData['email'] ?? '';
        $akunModel = new \App\Models\AkunModel();
        $akunData  = $akunModel->where('email', $userEmail)->first();

        $dataDiri = [
            'id'                  => $userData['id'] ?? '',
            'id_akun'             => $akunData['id'] ?? ($userData['akun_id'] ?? null),
            'nama'                => $userData['nama'] ?? '',
            'email'               => $userEmail,
            'roleLabel'           => PortalRouter::roleLabel($roles),
            'prodi'               => $userData['prodi'] ?? '-',
            'extra_label'         => '',
            'extra_value'         => '',
            'extra_label2'        => '',
            'extra_value2'        => '',
            'tanggal_sk_yudisium' => null,
        ];

        if (in_array($primary, ['mahasiswa', 'wisudawan'], true)) {
            $mhsModel = new \App\Models\MahasiswaModel();
            $npm      = ! empty($userData['npm']) ? $userData['npm'] : ($userData['id'] ?? null);
            $mhs      = $npm ? $mhsModel->getMahasiswaInfo($npm) : null;
            if ($mhs) {
                $dataDiri['nama']        = $mhs['nama'];
                $dataDiri['email']       = $mhs['email'];
                $dataDiri['prodi']       = $mhs['nama_prodi'] ?? ($userData['prodi'] ?? '-');
                $dataDiri['extra_label'] = 'NPM';
                $dataDiri['extra_value'] = $mhs['npm'];
            }

            if ($npm) {
                $terplotModel = new MahasiswaTerplotModel();
                $terplot      = $terplotModel->where('npm', $npm)->first();
                if ($terplot && ! empty($terplot['tanggal_sk_yudisium'])) {
                    $dataDiri['tanggal_sk_yudisium'] = $terplot['tanggal_sk_yudisium'];
                }
            }
        } elseif (in_array($primary, ['dosen', 'dospem eksternal'], true)) {
            $dosenModel = new \App\Models\DosenModel();
            $dosen      = $dosenModel->where('email', $userEmail)->first();
            if ($dosen) {
                $dataDiri['nama']        = $dosen['nama'];
                $dataDiri['email']       = $dosen['email'];
                $dataDiri['extra_label'] = 'NIDN';
                $dataDiri['extra_value'] = ! empty($dosen['nidn']) ? $dosen['nidn'] : '-';
                if (! empty($dosen['inisial'])) {
                    $dataDiri['extra_label2'] = 'Inisial';
                    $dataDiri['extra_value2'] = $dosen['inisial'];
                }
            }
        } elseif (in_array($primary, ['tendik', 'staf'], true)) {
            $tendikModel = new \App\Models\TenagaKependidikanModel();
            $tendik      = $tendikModel->where('email', $userEmail)->first();
            if ($tendik) {
                $dataDiri['nama']  = $tendik['nama'];
                $dataDiri['email'] = $tendik['email'];
            }
        } elseif (in_array($primary, ['kaprodi', 'prodi'], true)) {
            $prodiModel = new \App\Models\ProgramStudiModel();
            $prodi      = $prodiModel->where('email', $userEmail)->first();
            if ($prodi) {
                $dataDiri['nama']  = $prodi['nama'];
                $dataDiri['email'] = $prodi['email'];
                $dataDiri['prodi'] = $prodi['nama_prodi'] ?? ($prodi['nama'] ?? '-');
            }
        } elseif ($primary === 'fakultas') {
            $fakultasModel = new \App\Models\FakultasModel();
            $fak           = $fakultasModel->where('email', $userEmail)->first();
            if ($fak) {
                $dataDiri['nama']  = $fak['nama'];
                $dataDiri['email'] = $fak['email'];
            }
        }

        session()->set('active_side', 'graduate');

        return view('The-Graduate-Web/profile', [
            'title'    => 'Ubah Data Diri',
            'dataDiri' => $dataDiri,
            'roles'    => $roles,
            'user'     => $userData,
        ]);
    }

    public function index()
    {
        return $this->profile();
    }

    /**
     * Simpan pembaruan data diri ke database.
     */
    public function saveProfile()
    {
        $userData = session()->get('user_session');

        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(base_url('graduate/profile'));
        }

        $roles   = PortalRouter::normalizeRoles($userData);
        $primary = PortalRouter::primaryRole($roles);
        $nama    = trim((string) $this->request->getPost('nama', FILTER_SANITIZE_SPECIAL_CHARS));
        $email   = trim((string) $this->request->getPost('email', FILTER_SANITIZE_EMAIL));

        if ($nama === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Ubah Data Diri Gagal',
                'text'  => 'Nama dan email yang valid wajib diisi.',
            ]);
            return redirect()->to(base_url('graduate/profile'));
        }

        $akunModel = new \App\Models\AkunModel();
        $userEmail = $userData['email'] ?? '';
        $akunData  = $akunModel->where('email', $userEmail)->first();
        $akunId    = $akunData ? (int) $akunData['id'] : (int) ($userData['akun_id'] ?? 0);

        if ($akunData && $akunModel->isEmailAlreadyUsed($akunId, $email)) {
            session()->setFlashdata('message', [
                'icon'  => 'error',
                'title' => 'Ubah Data Diri Gagal',
                'text'  => 'Email telah digunakan oleh akun lain!',
            ]);
            return redirect()->to(base_url('graduate/profile'));
        }

        // 1. Update tabel akun
        if ($akunData) {
            $akunModel->update($akunId, ['email' => $email]);
        }

        // 2. Update tabel profil spesifik role
        if (in_array($primary, ['mahasiswa', 'wisudawan'], true)) {
            $mhsModel = new \App\Models\MahasiswaModel();
            $npm      = ! empty($userData['npm']) ? $userData['npm'] : ($userData['id'] ?? null);
            $mhs      = $npm ? $mhsModel->find($npm) : $mhsModel->where('email', $userEmail)->first();
            if ($mhs) {
                $mhsModel->update($mhs['npm'], ['nama' => $nama, 'email' => $email]);
            }
        } elseif (in_array($primary, ['dosen', 'dospem eksternal'], true)) {
            $dosenModel = new \App\Models\DosenModel();
            $dosen      = $dosenModel->where('email', $userEmail)->first();
            if ($dosen) {
                $updateData = ['nama' => $nama, 'email' => $email];
                $inisial    = trim((string) $this->request->getPost('inisial', FILTER_SANITIZE_SPECIAL_CHARS));
                if ($inisial !== '') {
                    if (! $dosenModel->isInitialAlreadyUsed($dosen['id'], $inisial)) {
                        $updateData['inisial'] = $inisial;
                    }
                }
                $dosenModel->update($dosen['id'], $updateData);
            }
        } elseif (in_array($primary, ['tendik', 'staf'], true)) {
            $tendikModel = new \App\Models\TenagaKependidikanModel();
            $tendik      = $tendikModel->where('email', $userEmail)->first();
            if ($tendik) {
                $tendikModel->update($tendik['id'], ['nama' => $nama, 'email' => $email]);
            }
        } elseif (in_array($primary, ['kaprodi', 'prodi'], true)) {
            $prodiModel = new \App\Models\ProgramStudiModel();
            $prodi      = $prodiModel->where('email', $userEmail)->first();
            if ($prodi) {
                $prodiModel->update($prodi['id'], ['nama' => $nama, 'email' => $email]);
            }
        } elseif ($primary === 'fakultas') {
            $fakultasModel = new \App\Models\FakultasModel();
            $fak           = $fakultasModel->where('email', $userEmail)->first();
            if ($fak) {
                $fakultasModel->update($fak['id'], ['nama' => $nama, 'email' => $email]);
            }
        }

        // 3. Update session yang aktif
        $userData['nama']  = $nama;
        $userData['email'] = $email;
        session()->set('user_session', $userData);

        session()->setFlashdata('message', [
            'icon'  => 'success',
            'title' => 'Ubah Data Diri Berhasil',
            'text'  => 'Data diri berhasil diperbarui!',
        ]);

        return redirect()->to(base_url('graduate/profile'));
    }
}

