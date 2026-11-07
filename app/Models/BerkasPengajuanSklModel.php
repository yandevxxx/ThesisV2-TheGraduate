<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model Berkas Dokumen Pengajuan SKL Sisi The Graduate
 */
class BerkasPengajuanSklModel extends Model
{
    protected $table            = 'berkas_pengajuan_skl';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'pengajuan_id',
        'npm',
        'persyaratan_id',
        'nama_persyaratan',
        'nama_file',
        'ukuran_kb',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Ambil seluruh berkas berdasarkan NPM mahasiswa.
     */
    public function getByNpm(string $npm): array
    {
        return $this->where('npm', $npm)->orderBy('persyaratan_id', 'ASC')->findAll();
    }

    /**
     * Ambil berkas-berkas dalam bentuk associative map [persyaratan_id => row] dan [nama_persyaratan => row].
     */
    public function getBerkasMapByNpm(string $npm): array
    {
        $rows = $this->getByNpm($npm);
        $map  = [];
        foreach ($rows as $row) {
            $map[(int) $row['persyaratan_id']] = $row;
            $map[$row['nama_persyaratan']]     = $row;
        }
        return $map;
    }

    /**
     * Ambil map berkas untuk banyak mahasiswa (digunakan di tabel monitoring tendik).
     * Output: [npm => [persyaratan_id => row]]
     */
    public function getBerkasMapGroupedByNpm(array $npms): array
    {
        if (empty($npms)) {
            return [];
        }

        $rows = $this->whereIn('npm', $npms)->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $npm = (string) $row['npm'];
            if (! isset($grouped[$npm])) {
                $grouped[$npm] = [];
            }
            $grouped[$npm][(int) $row['persyaratan_id']] = $row;
            $grouped[$npm][$row['nama_persyaratan']]     = $row;
        }
        return $grouped;
    }
}

