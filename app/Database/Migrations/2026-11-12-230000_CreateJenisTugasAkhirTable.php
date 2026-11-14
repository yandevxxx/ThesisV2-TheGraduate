<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJenisTugasAkhirTable extends Migration
{
    public function up()
    {
        // Pastikan tabel program studi sudah tersedia.
        if (! $this->db->tableExists('program_studi')) {
            return;
        }

        // Jika tabel jenis tugas akhir sudah ada,
        // jangan membuat ulang atau mengubah data yang tersedia.
        if ($this->db->tableExists('jenis_tugas_akhir')) {
            return;
        }

        $this->forge->addField([
            'id_jenis_tugas_akhir' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],
            'nama_jenis_tugas_akhir' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'id_prodi' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
        ]);

        // Primary key dan index.
        $this->forge->addKey('id_jenis_tugas_akhir', true);
        $this->forge->addKey('id_prodi');

        // Relasi dengan tabel program_studi.
        $this->forge->addForeignKey(
            'id_prodi',
            'program_studi',
            'id',
            'RESTRICT',
            'RESTRICT'
        );

        // Membuat tabel.
        $this->forge->createTable('jenis_tugas_akhir', true);
    }

    public function down()
    {
        // Tidak menghapus tabel agar data yang sudah digunakan
        // aplikasi tetap aman.
    }
}
