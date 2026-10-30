<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * B-08a: Create mahasiswa_terplot_sk_yudisium table for SK Yudisium plotting.
 */
class CreateMahasiswaTerplotSkYudisium extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'npm' => [
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
                'constraint' => ['baru', 'transfer'],
                'null'       => false,
            ],
            'id_prodi' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'uploaded_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'uploaded_at' => [
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
        $this->forge->addUniqueKey('npm', 'uniq_mahasiswa_terplot_npm');
        $this->forge->createTable('mahasiswa_terplot_sk_yudisium', true);
    }

    public function down()
    {
        if ($this->db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            $this->forge->dropTable('mahasiswa_terplot_sk_yudisium');
        }
    }
}
