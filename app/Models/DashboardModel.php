<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    // Kita hapus parameter semester agar semua data tampil
    public function getStatusTaPerProdi($idProdi)
    {
        $db = \Config\Database::connect();

        $query = "
            -- 1. HITUNG SEMINAR PROPOSAL
            -- (Join ini SUDAH TERBUKTI BERHASIL di debugger kamu)
            SELECT 'Seminar Proposal' as status, COUNT(DISTINCT m.npm) as jumlah
            FROM mahasiswa m
            JOIN proposal p ON p.npm = m.npm 
            JOIN seminar_proposal sp ON sp.id_proposal = p.id
            WHERE m.id_prodi = ? 
            
            UNION ALL
            
            -- 2. HITUNG SEMINAR PRA SIDANG
            -- (Menggunakan id_skripsi sesuai screenshot tabel kamu)
            SELECT 'Seminar Pra Sidang' as status, COUNT(DISTINCT m.npm) as jumlah
            FROM mahasiswa m
            JOIN skripsi s ON s.npm = m.npm
            JOIN seminar_prasidang spr ON spr.id_skripsi = s.id 
            WHERE m.id_prodi = ? 
            
            UNION ALL
            
            -- 3. HITUNG SIDANG AKHIR
            -- (Join ini SUDAH TERBUKTI BERHASIL di debugger kamu)
            SELECT 'Sidang Akhir' as status, COUNT(DISTINCT m.npm) as jumlah
            FROM mahasiswa m
            JOIN skripsi s ON s.npm = m.npm
            JOIN sidang_skripsi ss ON ss.id_skripsi = s.id
            WHERE m.id_prodi = ? 
        ";

        // Parameter hanya ID PRODI
        $params = [$idProdi, $idProdi, $idProdi];

        return $db->query($query, $params)->getResultArray();
    }

    public function getBebanReviewerSempro($semester, $id_prodi)
{
    $sql = "SELECT dosen.nama, dosen.inisial,
            (SELECT COUNT(*) FROM seminar_proposal sp
             JOIN proposal p ON sp.id_proposal = p.id
             WHERE p.kode_semester = ?
             AND (sp.dosen_penguji1 = dosen.id OR sp.dosen_penguji2 = dosen.id)
            ) AS jumlah_uji
            FROM dosen
            WHERE dosen.id_prodi = ?
            ORDER BY jumlah_uji DESC";

    return $this->db->query($sql, [$semester, $id_prodi])->getResultArray();
}

   public function getBebanReviewerPrasidang($semester, $id_prodi)
{
    $sql = "SELECT dosen.nama, dosen.inisial,
            (SELECT COUNT(*) FROM seminar_prasidang spr
             JOIN skripsi s ON spr.id_skripsi = s.id
             WHERE s.kode_semester = ?
             AND spr.dosen_reviewer = dosen.id
            ) AS jumlah_uji
            FROM dosen
            WHERE dosen.id_prodi = ?
            ORDER BY jumlah_uji DESC";

    return $this->db->query($sql, [$semester, $id_prodi])->getResultArray();
}

   public function getBebanPengujiSidang($semester, $id_prodi)
{
    $sql = "SELECT dosen.nama, dosen.inisial,
            (SELECT COUNT(*) FROM sidang_skripsi ss
             JOIN skripsi s ON ss.id_skripsi = s.id
             WHERE s.kode_semester = ?
             AND (ss.ketua_sidang = dosen.id OR ss.dosen_penguji = dosen.id)
            ) AS jumlah_uji
            FROM dosen
            WHERE dosen.id_prodi = ?
            ORDER BY jumlah_uji DESC";

    return $this->db->query($sql, [$semester, $id_prodi])->getResultArray();
}

    public function getAllSemesters()
    {
        return $this->db->table('semester')->orderBy('kode', 'DESC')->get()->getResultArray();
    }
}
