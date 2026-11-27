<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model Pengajuan SKL Mahasiswa Sisi The Graduate
 */
class PengajuanSklModel extends Model
{
    protected $table            = 'pengajuan_skl';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'npm',
        'nama',
        'id_prodi',
        'kode_semester',
        'status',
        'catatan',
        'no_surat',
        'file_skl',
        'tgl_terbit',
        'dikirim_oleh',
        'dikirim_pada',
        'diajukan_pada',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Cari pengajuan SKL berdasarkan NPM.
     */
    public function getByNpm(string $npm): ?array
    {
        return $this->where('npm', $npm)->orderBy('id', 'DESC')->first() ?: null;
    }

    /**
     * Ambil daftar pengajuan SKL terfilter dengan join program studi.
     */
    public function getFiltered(?string $status = null, ?string $kataCari = null, ?string $semester = null): array
    {
        $builder = $this->db->table($this->table . ' p');
        $builder->select('p.*, pr.nama as nama_prodi');
        $builder->join('program_studi pr', 'pr.id = p.id_prodi', 'left');

        if ($status !== null && $status !== '' && in_array($status, ['menunggu', 'proses', 'disetujui', 'ditolak'], true)) {
            $builder->where('p.status', $status);
        }

        if ($semester !== null && $semester !== '' && $semester !== 'semua' && $semester !== 'SEMUA') {
            $builder->where('p.kode_semester', $semester);
        }

        if ($kataCari !== null && $kataCari !== '') {
            $builder->groupStart()
                ->like('p.npm', $kataCari)
                ->orLike('p.nama', $kataCari)
                ->orLike('pr.nama', $kataCari)
                ->groupEnd();
        }

        $builder->orderBy('p.id', 'DESC');

        return $builder->get()->getResultArray();
    }
}

