<?php

namespace App\Libraries;

use App\Models\JawabanTracerStudiAwalModel;
use App\Models\MahasiswaTerplotModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Gerbang prasyarat Tracer Study Awal untuk layanan mahasiswa The Graduate.
 *
 * Memastikan mahasiswa terplot SK Yudisium telah mengisi Tracer Study Awal
 * sebelum mengakses Biodata Mahasiswa, Berkas Wisuda, SKL, dan Pengambilan Ijazah.
 */
final class TracerStudyGate
{
    /**
     * Mengambil NPM mahasiswa dari payload sesi.
     */
    public static function resolveNpm(?array $userData): string
    {
        if (! is_array($userData)) {
            return '';
        }

        return (string) ($userData['npm'] ?? $userData['id'] ?? '');
    }

    /**
     * Memeriksa apakah mahasiswa sudah mengirim jawaban Tracer Study Awal.
     */
    public static function hasCompletedTracerAwal(string $npm): bool
    {
        if ($npm === '') {
            return false;
        }

        try {
            $jawabanModel = function_exists('model')
                ? model(JawabanTracerStudiAwalModel::class)
                : new JawabanTracerStudiAwalModel();

            return $jawabanModel->hasCompletedByNpm($npm);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Memvalidasi prasyarat SK Yudisium dan Tracer Study Awal untuk halaman GET mahasiswa.
     * Mengembalikan HTML view pemblokiran jika belum memenuhi syarat, atau null jika lolos.
     */
    public static function guardMahasiswaView(
        array $userData,
        array $roles,
        string $fiturBelumTerplot,
        string $fiturTracer,
        ?array &$terplot = null
    ): ?string {
        $npm     = self::resolveNpm($userData);
        $terplot = $npm !== '' ? (new MahasiswaTerplotModel())->where('npm', $npm)->first() : null;

        if ($terplot === null) {
            return view('The-Graduate-Web/mahasiswa/belum_terplot', [
                'title' => 'Akses Ditolak',
                'user'  => $userData,
                'role'  => $roles,
                'fitur' => $fiturBelumTerplot,
            ]);
        }

        if (! self::hasCompletedTracerAwal($npm)) {
            return self::lockedView($userData, $roles, $fiturTracer);
        }

        return null;
    }

    /**
     * Merender tampilan halaman pemblokiran Tracer Study Awal belum diisi.
     */
    public static function lockedView(array $userData, array $roles, string $fitur = 'layanan ini'): string
    {
        return view('The-Graduate-Web/mahasiswa/tracer_belum_diisi', [
            'title' => 'Tracer Study Awal Diperlukan',
            'user'  => $userData,
            'role'  => $roles,
            'fitur' => $fitur,
        ]);
    }

    /**
     * Mengarahkan kembali ke halaman pengisian Tracer Study Awal beserta pesan peringatan.
     */
    public static function lockedRedirect(string $fitur, string $targetRoute = 'graduate/tracer_studi'): RedirectResponse
    {
        session()->setFlashdata('message', [
            'icon'  => 'warning',
            'title' => 'Tracer Study Awal Diperlukan',
            'text'  => "Silakan lihat dan isi daftar pertanyaan Tracer Study Awal terlebih dahulu sebelum mengakses {$fitur}.",
        ]);

        return redirect()->to(base_url($targetRoute));
    }

    /**
     * Membuat payload JSON error standar ketika endpoint aksi dipanggil sebelum Tracer Study Awal diisi.
     */
    public static function lockedJsonPayload(string $fitur): array
    {
        return [
            'success'    => false,
            'message'    => "Layanan {$fitur} belum dapat diakses: Silakan isi Tracer Study Awal terlebih dahulu.",
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ];
    }
}
