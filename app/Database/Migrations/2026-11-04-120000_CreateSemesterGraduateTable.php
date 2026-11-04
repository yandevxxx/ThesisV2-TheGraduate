<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to create a separate semester table for The Graduate.
 * Keeps Thesis semester (`semester`) and The Graduate semester (`semester_graduate`) completely isolated.
 */
class CreateSemesterGraduateTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('semester_graduate')) {
            return;
        }

        $this->forge->addField([
            'kode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['AKTIF', 'NON-AKTIF'],
                'default'    => 'NON-AKTIF',
                'null'       => false,
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

        $this->forge->addKey('kode', true);
        $this->forge->createTable('semester_graduate', true);

        // Copy initial data from table `semester` so The Graduate has an initial baseline
        if ($this->db->tableExists('semester')) {
            $this->db->query("
                INSERT IGNORE INTO `semester_graduate` (`kode`, `nama`, `status`, `created_at`, `updated_at`)
                SELECT `kode`, `nama`, `status`, NOW(), NOW() FROM `semester`
            ");
        }
    }

    public function down()
    {
        if ($this->db->tableExists('semester_graduate')) {
            $this->forge->dropTable('semester_graduate', true);
        }
    }
}

