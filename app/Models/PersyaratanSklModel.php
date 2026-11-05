<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model Pengelolaan Persyaratan Pengambilan SKL Sisi The Graduate
 */
class PersyaratanSklModel extends Model
{
    protected $table            = 'persyaratan_skl';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'nama',
        'keterangan',
        'tipe_file',
        'max_size_kb',
        'is_wajib',
        'urutan',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Mengambil seluruh persyaratan SKL yang berstatus AKTIF terurut berdasarkan urutan.
     */
    public function getPersyaratanAktif(): array
    {
        return $this->where('status', 'AKTIF')
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}

