<?php

namespace App\Database\Migrations;

use App\Models\BankPertanyaanTracerStudiModel;
use CodeIgniter\Database\Migration;

/**
 * Migration pembuatan tabel bank_pertanyaan_tracer_studi untuk The Graduate Web.
 */
class CreateBankPertanyaanTracerStudiTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('bank_pertanyaan_tracer_studi')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'kategori' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'default'    => 'Umum',
                ],
                'teks_pertanyaan' => [
                    'type' => 'TEXT',
                    'null' => false,
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
                'is_wajib_default' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'created_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
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
            $this->forge->addKey('kategori');
            $this->forge->addKey('tipe_jawaban');
            $this->forge->createTable('bank_pertanyaan_tracer_studi', true);
            $this->db->resetDataCache();

            $now  = date('Y-m-d H:i:s');
            $rows = [];
            foreach (BankPertanyaanTracerStudiModel::defaultBankQuestions() as $item) {
                $rows[] = [
                    'kategori'         => $item['kategori'],
                    'teks_pertanyaan'  => $item['teks_pertanyaan'],
                    'tipe_jawaban'     => $item['tipe_jawaban'],
                    'opsi_jawaban'     => $item['opsi_jawaban'],
                    'is_wajib_default' => $item['is_wajib_default'],
                    'created_by'       => 'Sistem',
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }

            if ($rows !== []) {
                $this->db->table('bank_pertanyaan_tracer_studi')->insertBatch($rows);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('bank_pertanyaan_tracer_studi')) {
            $this->forge->dropTable('bank_pertanyaan_tracer_studi', true);
            $this->db->resetDataCache();
        }
    }
}
