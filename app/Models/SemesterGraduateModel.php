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
     * Memeriksa apakah semester dapat dihapus.
     */
    public function isSemesterDeleteable($kodeSemester): bool
    {
        $db = \Config\Database::connect();

        // Jika terhubung ke data spesifik The Graduate di masa depan, tambahkan foreign key check di sini.
        // Untuk saat ini, semester tidak boleh dihapus jika statusnya AKTIF.
        $semester = $this->find($kodeSemester);
        if (! $semester || $semester['status'] === 'AKTIF') {
            return false;
        }

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

