<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTanggalSkYudisiumToMahasiswaTerplot extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            $fields = $this->db->getFieldData('mahasiswa_terplot_sk_yudisium');
            $hasColumn = false;
            foreach ($fields as $field) {
                if (strtolower($field->name) === 'tanggal_sk_yudisium') {
                    $hasColumn = true;
                    break;
                }
            }
            if (! $hasColumn) {
                $this->forge->addColumn('mahasiswa_terplot_sk_yudisium', [
                    'tanggal_sk_yudisium' => [
                        'type' => 'DATE',
                        'null' => true,
                        'after' => 'status',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            $this->forge->dropColumn('mahasiswa_terplot_sk_yudisium', 'tanggal_sk_yudisium');
        }
    }
}
