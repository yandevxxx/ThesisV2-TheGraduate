<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model Pengelolaan Semester Khusus Sisi The Graduate
 *
 * Mengakses tabel terpisah `semester_graduate` sehingga pengaturan semester
 * kelulusan/wisuda terisolasi dari semester akademik skripsi di Thesis.
 */
class SemesterGraduateModel extends Model
{
    protected $table         = 'semester_graduate';
    protected $primaryKey    = 'kode';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['kode', 'nama', 'status', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Memeriksa apakah semester dapat dihapus atau diubah kodenya.
     * Semester tidak boleh dihapus jika sedang AKTIF atau sudah memiliki
     * data pendaftaran wisuda atau data mahasiswa terplot SK Yudisium.
     */
    public function isSemesterDeleteable($kodeSemester): bool
    {
        $db = \Config\Database::connect();

        $semester = $this->find($kodeSemester);
        if (! $semester || $semester['status'] === 'AKTIF') {
            return false;
        }

        // Cek apakah kode semester digunakan pada pendaftaran_wisuda
        try {
            if ($db->tableExists('pendaftaran_wisuda')) {
                $countPendaftaran = $db->table('pendaftaran_wisuda')
                    ->where('kode_semester', $kodeSemester)
                    ->countAllResults();
                if ($countPendaftaran > 0) {
                    return false;
                }
            }
        } catch (\Throwable $e) {}

        // Cek apakah kode semester digunakan pada mahasiswa_terplot_sk_yudisium
        try {
            if ($db->tableExists('mahasiswa_terplot_sk_yudisium')) {
                $countTerplot = $db->table('mahasiswa_terplot_sk_yudisium')
                    ->where('kode_semester', $kodeSemester)
                    ->countAllResults();
                if ($countTerplot > 0) {
                    return false;
                }
            }
        } catch (\Throwable $e) {}

        return true;
    }

    /**
     * Mengambil semester yang sedang berstatus AKTIF di The Graduate.
     */
    public function getActiveSemester(): ?array
    {
        $semester = $this->where('status', 'AKTIF')->first();

        return $semester ?: null;
    }

    /**
     * Memformat nama semester menjadi format ringkas standar "Semester (Ganjil|Genap) YYYY/YYYY".
     */
    public static function formatNama(?string $nama, ?string $kode = null): string
    {
        if (! empty($nama)) {
            if (preg_match('/Tahun\s+Akademik\s+(\d{4}\/\d{4})\s+Semester\s+(\w+)/i', $nama, $m)) {
                return "Semester {$m[2]} {$m[1]}";
            }
            if (preg_match('/^(Ganjil|Genap)\s+(\d{4}\/\d{4})/i', $nama, $m)) {
                return "Semester {$m[1]} {$m[2]}";
            }
            if (preg_match('/^Semester\s+(Ganjil|Genap)\s+(\d{4}\/\d{4})/i', $nama, $m)) {
                return "Semester {$m[1]} {$m[2]}";
            }
            return $nama;
        }

        if (! empty($kode) && preg_match('/^(\d{4})-(\d+)$/', $kode, $m)) {
            $tahunMulai   = (int) $m[1];
            $tahunSelesai = $tahunMulai + 1;
            $semName      = ($m[2] === '1') ? 'Ganjil' : (($m[2] === '2') ? 'Genap' : 'Semester ' . $m[2]);
            return "Semester {$semName} {$tahunMulai}/{$tahunSelesai}";
        }

        return $kode ?? '-';
    }
}

