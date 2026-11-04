<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model Pengelolaan Pendaftaran Wisuda Sisi The Graduate
 */
class PendaftaranWisudaModel extends Model
{
    protected $table            = 'pendaftaran_wisuda';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'npm',
        'nama',
        'nik',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'no_hp',
        'no_telepon',
        'email',
        'prodi',
        'total_sks',
        'ipk',
        'kode_semester',
        'tanggal_lulus',
        'gelombang_wisuda',
        'pembimbing1',
        'pembimbing2',
        'pembimbing_agama',
        'dosen_penguji',
        'judul_skripsi',
        'pas_foto',
        'status_verifikasi',
        'catatan_verifikasi',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Cari pendaftaran berdasarkan NPM.
     */
    public function getByNpm(string $npm): ?array
    {
        return $this->where('npm', $npm)->first() ?: null;
    }

    /**
     * Dapatkan daftar NPM yang sudah mendaftar wisuda.
     */
    public function getRegisteredNpms(): array
    {
        $rows = $this->select('npm')->findAll();
        return array_map('strval', array_column($rows, 'npm'));
    }

    /**
     * Ambil data pendaftaran wisuda dengan filter semester, status verifikasi, dan pencarian.
     *
     * @param string|null $kodeSemester
     * @param string|null $statusVerifikasi 'MENUNGGU'|'DISETUJUI'|'DITOLAK'|null
     * @param string|null $q Kata kunci NPM atau Nama
     * @return array
     */
    public function getFiltered(?string $kodeSemester = null, ?string $statusVerifikasi = null, ?string $q = null): array
    {
        $builder = $this->builder();

        if ($kodeSemester !== null && $kodeSemester !== '' && strtolower($kodeSemester) !== 'semua') {
            $builder->where('kode_semester', $kodeSemester);
        }

        if ($statusVerifikasi !== null && $statusVerifikasi !== '' && strtolower($statusVerifikasi) !== 'semua') {
            $builder->where('status_verifikasi', strtoupper($statusVerifikasi));
        }

        if ($q !== null && trim($q) !== '') {
            $cleanQ = trim($q);
            $builder->groupStart()
                ->like('npm', $cleanQ)
                ->orLike('nama', $cleanQ)
                ->orLike('prodi', $cleanQ)
                ->groupEnd();
        }

        $builder->orderBy('id', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Ambil detail pendaftaran beserta berkas dokumen pendukungnya.
     */
    public function getPendaftaranWithBerkas(int $id): ?array
    {
        $pendaftaran = $this->find($id);
        if ($pendaftaran === null) {
            return null;
        }

        $berkasModel = new BerkasPendaftaranWisudaModel();
        $pendaftaran['berkas'] = $berkasModel->getByPendaftaranId($id);

        return $pendaftaran;
    }
}
