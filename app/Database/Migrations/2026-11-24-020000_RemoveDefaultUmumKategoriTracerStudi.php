<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menghapus kategori bawaan "Umum" dari Master Kategori Tracer Study
 * serta membersihkan default "Umum" pada tabel terkait.
 */
class RemoveDefaultUmumKategoriTracerStudi extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('kategori_tracer_studi')) {
            $rows     = $this->db->table('kategori_tracer_studi')->select('id, nama_kategori, is_default')->get()->getResultArray();
            $deleteId = [];

            foreach ($rows as $row) {
                $nama = trim((string) ($row['nama_kategori'] ?? ''));
                if (! empty($row['is_default']) || strcasecmp($nama, 'Umum') === 0) {
                    $rid = (int) ($row['id'] ?? 0);
                    if ($rid > 0) {
                        $deleteId[] = $rid;
                    }
                }
            }

            if ($deleteId !== []) {
                $this->db->table('kategori_tracer_studi')->whereIn('id', $deleteId)->delete();
            }
        }

        if ($this->db->tableExists('pertanyaan_tracer_studi_awal') && $this->db->fieldExists('kategori', 'pertanyaan_tracer_studi_awal')) {
            $this->db->table('pertanyaan_tracer_studi_awal')
                ->where('kategori', 'Umum')
                ->update(['kategori' => '']);
        }

        if ($this->db->tableExists('bank_pertanyaan_tracer_studi') && $this->db->fieldExists('kategori', 'bank_pertanyaan_tracer_studi')) {
            $this->db->table('bank_pertanyaan_tracer_studi')
                ->where('kategori', 'Umum')
                ->update(['kategori' => '']);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('kategori_tracer_studi')) {
            $exists = $this->db->table('kategori_tracer_studi')
                ->where('nama_kategori', 'Umum')
                ->countAllResults();

            if ($exists === 0) {
                $now = date('Y-m-d H:i:s');
                $this->db->table('kategori_tracer_studi')->insert([
                    'nama_kategori' => 'Umum',
                    'deskripsi'     => 'Kategori bawaan sistem untuk pertanyaan umum atau tanpa pengelompokan khusus.',
                    'is_default'    => 1,
                    'created_by'    => 'Sistem',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }
}
