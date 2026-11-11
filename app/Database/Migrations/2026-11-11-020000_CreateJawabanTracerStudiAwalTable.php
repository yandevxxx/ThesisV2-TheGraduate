<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJawabanTracerStudiAwalTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('jawaban_tracer_studi_awal')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'npm' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 25,
                ],
                'pertanyaan_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'urutan' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                ],
                'teks_pertanyaan' => [
                    'type' => 'TEXT',
                ],
                'jawaban' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'kode_semester' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'submitted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
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
            $this->forge->addKey('npm');
            $this->forge->addKey(['npm', 'pertanyaan_id'], false, true);
            $this->forge->createTable('jawaban_tracer_studi_awal', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('jawaban_tracer_studi_awal', true);
    }
}
