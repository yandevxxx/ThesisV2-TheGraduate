<?php

namespace App\Models;

use CodeIgniter\Model;

class RiwayatKomentarPengajuanSkripsiModel extends Model
{
    protected $table = 'riwayat_komentar_pengajuan_skripsi';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'npm',
        'id_tendik',
        'pesan',
        'tanggal_pesan'
    ];

    protected $useTimestamps = false;
}