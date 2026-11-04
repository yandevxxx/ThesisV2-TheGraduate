<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan kolom kode_semester ke tabel mahasiswa_terplot_sk_yudisium
 * untuk mengaitkan mahasiswa terplot dengan semester / periode wisuda di The Graduate.
 */
class AddKodeSemesterToMahasiswaTerplot extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            $fields = $this->db->getFieldData('mahasiswa_terplot_sk_yudisium');
            $existingColumns = array_map(function ($field) {
                return $field->name;
            }, $fields);

            if (! in_array('kode_semester', $existingColumns, true)) {
                $this->forge->addColumn('mahasiswa_terplot_sk_yudisium', [
                    'kode_semester' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 50,
                        'null'       => true,
                        'after'      => 'id_prodi',
                    ],
                ]);
            }

            // Jika ada data semester di semester_graduate, isi baris lama dengan semester aktif
            if ($this->db->tableExists('semester_graduate')) {
                $active = $this->db->table('semester_graduate')->where('status', 'AKTIF')->get()->getRowArray();
                $kodeAktif = $active['kode'] ?? null;
                if (! $kodeAktif) {
                    $first = $this->db->table('semester_graduate')->orderBy('kode', 'DESC')->get()->getRowArray();
                    $kodeAktif = $first['kode'] ?? null;
                }
                if ($kodeAktif) {
                    $this->db->table('mahasiswa_terplot_sk_yudisium')
                        ->where('kode_semester IS NULL')
                        ->update(['kode_semester' => $kodeAktif]);
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('mahasiswa_terplot_sk_yudisium')) {
            $fields = $this->db->getFieldData('mahasiswa_terplot_sk_yudisium');
            $existingColumns = array_map(function ($field) {
                return $field->name;
            }, $fields);

            if (in_array('kode_semester', $existingColumns, true)) {
                $this->forge->dropColumn('mahasiswa_terplot_sk_yudisium', 'kode_semester');
            }
        }
    }
}

