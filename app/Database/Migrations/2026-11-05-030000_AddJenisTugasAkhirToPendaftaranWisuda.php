<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration penambahan kolom jenis_tugas_akhir pada tabel pendaftaran_wisuda
 * Pilihan: Skripsi, Publikasi Artikel Ilmiah, Proyek Akhir
 */
class AddJenisTugasAkhirToPendaftaranWisuda extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('pendaftaran_wisuda')) {
            $fields = $this->db->getFieldNames('pendaftaran_wisuda');
            if (! in_array('jenis_tugas_akhir', $fields, true)) {
                $this->forge->addColumn('pendaftaran_wisuda', [
                    'jenis_tugas_akhir' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 50,
                        'default'    => 'Skripsi',
                        'after'      => 'judul_skripsi',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('pendaftaran_wisuda')) {
            $fields = $this->db->getFieldNames('pendaftaran_wisuda');
            if (in_array('jenis_tugas_akhir', $fields, true)) {
                $this->forge->dropColumn('pendaftaran_wisuda', 'jenis_tugas_akhir');
            }
        }
    }
}

