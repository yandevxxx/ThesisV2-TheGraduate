<?php

namespace App\Models;

use CodeIgniter\Model;

class JenisTugasAkhirModel extends Model
{
    protected $table = "jenis_tugas_akhir";

    protected $primaryKey = "id_jenis_tugas_akhir";

    protected $useAutoIncrement = true;

    protected $returnType = "array";

    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'nama_jenis_tugas_akhir',
        'id_prodi'
    ];

    protected $useTimestamps = false;

    public function getJenisTugasAkhirByProdi($idProdi)
    {
        $db = \Config\Database::connect();

        $builder = $db->table("jenis_tugas_akhir");

        return $builder
            ->select()
            ->getWhere([
                "id_prodi" => $idProdi,
            ])
            ->getResultArray();
    }
}