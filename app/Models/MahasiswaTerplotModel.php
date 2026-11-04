<?php

namespace App\Models;

use CodeIgniter\Model;

class MahasiswaTerplotModel extends Model
{
    protected $table         = 'mahasiswa_terplot_sk_yudisium';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'npm',
        'nama',
        'status',
        'tanggal_sk_yudisium',
        'kode_semester',
        'id_prodi',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'npm'    => 'required|max_length[20]',
        'nama'   => 'required|max_length[150]',
        'status' => 'required|in_list[baru,transfer]',
    ];
}
