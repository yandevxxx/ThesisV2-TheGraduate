<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model Berkas Dokumen Pendukung Pendaftaran Wisuda Sisi The Graduate
 */
class BerkasPendaftaranWisudaModel extends Model
{
    protected $table            = 'berkas_pendaftaran_wisuda';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'pendaftaran_id',
        'persyaratan_id',
        'nama_persyaratan',
        'nama_file',
        'ukuran_kb',
        'status_berkas',
        'catatan',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Ambil berkas-berkas milik satu pendaftaran wisuda.
     */
    public function getByPendaftaranId(int $pendaftaranId): array
    {
        return $this->where('pendaftaran_id', $pendaftaranId)->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Ambil map berkas berdasar nama persyaratan untuk pendaftaran tertentu.
     * Return array key: nama_persyaratan => row berkas
     */
    public function getBerkasMapByPendaftaranId(int $pendaftaranId): array
    {
        $rows = $this->getByPendaftaranId($pendaftaranId);
        $map = [];
        foreach ($rows as $row) {
            $map[$row['nama_persyaratan']] = $row;
            if (! empty($row['persyaratan_id'])) {
                $map['id_' . $row['persyaratan_id']] = $row;
            }
        }
        return $map;
    }

    /**
     * Cari berkas berdasar pendaftaran_id dan persyaratan_id
     */
    public function getByPendaftaranAndPersyaratan(int $pendaftaranId, int $persyaratanId): ?array
    {
        return $this->where('pendaftaran_id', $pendaftaranId)
            ->where('persyaratan_id', $persyaratanId)
            ->first() ?: null;
    }

    /**
     * Cari berkas berdasar pendaftaran_id dan nama_persyaratan
     */
    public function getByPendaftaranAndNama(int $pendaftaranId, string $namaPersyaratan): ?array
    {
        return $this->where('pendaftaran_id', $pendaftaranId)
            ->where('nama_persyaratan', $namaPersyaratan)
            ->first() ?: null;
    }
}
