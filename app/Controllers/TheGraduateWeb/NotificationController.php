<?php

namespace App\Controllers\TheGraduateWeb;

use App\Controllers\BaseController;
use App\Libraries\AccessControl;
use App\Libraries\PortalRouter;

/**
 * Controller Notifikasi Sisi The Graduate
 *
 * Menangani polling AJAX notifikasi (fetchNotif), tandai sudah dibaca (markAsRead),
 * dan halaman riwayat notifikasi (semua_notifikasi).
 */
class NotificationController extends BaseController
{
    /**
     * Mengambil notifikasi kelulusan & yudisium untuk staf/tendik/admin secara asinkron (AJAX).
     */
    public function fetchNotif()
    {
        if (ob_get_length()) {
            ob_clean();
        }

        $userData = session()->get('user_session');
        if (! $userData) {
            return $this->response->setStatusCode(401)->setJSON(['unread' => 0, 'list' => []]);
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin'])) {
            return $this->response->setStatusCode(403)->setJSON(['unread' => 0, 'list' => []]);
        }

        $db          = \Config\Database::connect();
        $notifList   = [];
        $unreadCount = 0;
        $lastRead    = session()->get('graduate_notif_last_read') ?? '2000-01-01 00:00:00';
        $readAll     = (bool) session()->get('graduate_notif_read_all');

        // 1. Notifikasi Mahasiswa Terplot SK Yudisium (belum ada tanggal SK)
        if ($db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            $unsettled = $db->table('mahasiswa_terplot_sk_yudisium')
                ->select('npm, nama, uploaded_at, created_at')
                ->where('tanggal_sk_yudisium IS NULL')
                ->orderBy('id', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();

            foreach ($unsettled as $row) {
                $itemTime = $row['uploaded_at'] ?? $row['created_at'] ?? date('Y-m-d H:i:s');
                $isRead   = ($readAll || $itemTime <= $lastRead) ? 1 : 0;
                if ($isRead === 0) {
                    $unreadCount++;
                }

                $notifList[] = [
                    'pesan'         => 'SK Yudisium: ' . $row['nama'] . ' (Perlu tanggal SK)',
                    'is_read_admin' => $isRead,
                    'link'          => base_url('graduate/sk_yudisium'),
                ];
            }
        }

        // 2. Notifikasi Calon Wisudawan (Lulus Sidang Skripsi di TheSIS)
        if ($db->tableExists('skripsi')) {
            $lulusan = $db->table('skripsi')
                ->select('mahasiswa.nama, skripsi.npm, skripsi.tanggal_selesai_skripsi')
                ->join('mahasiswa', 'mahasiswa.npm = skripsi.npm')
                ->where('skripsi.status', 'Lulus')
                ->orderBy('skripsi.id', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();

            foreach ($lulusan as $row) {
                $itemTime = $row['tanggal_selesai_skripsi'] ?? date('Y-m-d H:i:s');
                $isRead   = ($readAll || $itemTime <= $lastRead) ? 1 : 0;
                if ($isRead === 0) {
                    $unreadCount++;
                }

                $notifList[] = [
                    'pesan'         => 'Calon Wisudawan: ' . $row['nama'] . ' (' . $row['npm'] . ')',
                    'is_read_admin' => $isRead,
                    'link'          => base_url('graduate/calon_wisudawan'),
                ];
            }
        }

        return $this->response->setJSON(['unread' => $unreadCount, 'list' => $notifList]);
    }

    /**
     * Menandai semua notifikasi kelulusan telah dibaca.
     */
    public function markAsRead()
    {
        session()->set('graduate_notif_last_read', date('Y-m-d H:i:s'));
        session()->set('graduate_notif_read_all', true);

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Halaman riwayat notifikasi khusus kelulusan dan wisuda.
     */
    public function semua_notifikasi()
    {
        $userData = session()->get('user_session');
        if (! $userData) {
            return redirect()->to(base_url('auth/login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $roles = PortalRouter::normalizeRoles($userData);
        if (! AccessControl::hasAnyRole($roles, ['tendik', 'staf', 'admin'])) {
            return AccessControl::forbiddenResponse($this->request, 'Akses ditolak', $roles);
        }

        $db       = \Config\Database::connect();
        $lastRead = session()->get('graduate_notif_last_read') ?? '2000-01-01 00:00:00';
        $readAll  = (bool) session()->get('graduate_notif_read_all');

        $skYudisium = [];
        if ($db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            $rows = $db->table('mahasiswa_terplot_sk_yudisium')
                ->select('npm, nama, status, tanggal_sk_yudisium, uploaded_at')
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $itemTime     = $r['uploaded_at'] ?? date('Y-m-d H:i:s');
                $r['is_read'] = ($readAll || $itemTime <= $lastRead) ? 1 : 0;
                $skYudisium[] = $r;
            }
        }

        $calonWisudawan = [];
        if ($db->tableExists('skripsi')) {
            $rows = $db->table('skripsi')
                ->select('mahasiswa.nama, skripsi.npm, skripsi.tanggal_selesai_skripsi')
                ->join('mahasiswa', 'mahasiswa.npm = skripsi.npm')
                ->where('skripsi.status', 'Lulus')
                ->orderBy('skripsi.id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                $itemTime         = $r['tanggal_selesai_skripsi'] ?? date('Y-m-d H:i:s');
                $r['is_read']     = ($readAll || $itemTime <= $lastRead) ? 1 : 0;
                $calonWisudawan[] = $r;
            }
        }

        session()->set('active_side', 'graduate');

        return view('The-Graduate-Web/admin_tendik/semua_notifikasi', [
            'title'          => 'Riwayat Notifikasi Kelulusan',
            'user'           => $userData,
            'role'           => $roles,
            'skYudisium'     => $skYudisium,
            'calonWisudawan' => $calonWisudawan,
        ]);
    }
}

