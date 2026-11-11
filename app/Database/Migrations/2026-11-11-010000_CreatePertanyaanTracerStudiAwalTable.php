<?php

namespace App\Database\Migrations;

use App\Models\PertanyaanTracerStudiAwalModel;
use CodeIgniter\Database\Migration;

/**
 * Migration pembuatan tabel pertanyaan_tracer_studi_awal untuk The Graduate Web (B-10).
 *
 * Tabel bersifat global tanpa kolom prodi agar daftar pertanyaan sama untuk semua prodi.
 */
class CreatePertanyaanTracerStudiAwalTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('pertanyaan_tracer_studi_awal')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'teks_pertanyaan' => [
                    'type' => 'TEXT',
                    'null' => false,
                ],
                'urutan' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'tipe_jawaban' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'TEKS',
                ],
                'opsi_jawaban' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'is_wajib' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['AKTIF', 'NON-AKTIF'],
                    'default'    => 'AKTIF',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('urutan');
            $this->forge->addKey('status');
            $this->forge->createTable('pertanyaan_tracer_studi_awal', true);

            $now  = date('Y-m-d H:i:s');
            $rows = [];
            foreach (PertanyaanTracerStudiAwalModel::defaultQuestions() as $item) {
                $rows[] = [
                    'teks_pertanyaan' => $item['teks_pertanyaan'],
                    'urutan'          => $item['urutan'],
                    'tipe_jawaban'    => $item['tipe_jawaban'],
                    'opsi_jawaban'    => $item['opsi_jawaban'],
                    'is_wajib'        => $item['is_wajib'],
                    'status'          => $item['status'],
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            if ($rows !== []) {
                $this->db->table('pertanyaan_tracer_studi_awal')->insertBatch($rows);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('pertanyaan_tracer_studi_awal')) {
            $this->forge->dropTable('pertanyaan_tracer_studi_awal', true);
        }
    }
}
