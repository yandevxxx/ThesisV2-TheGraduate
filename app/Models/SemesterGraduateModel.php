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
}

