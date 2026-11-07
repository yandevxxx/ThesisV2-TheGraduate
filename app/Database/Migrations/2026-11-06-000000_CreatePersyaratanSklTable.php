<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration pembuatan tabel persyaratan_skl, pengajuan_skl, dan berkas_pengajuan_skl untuk The Graduate
 */
class CreatePersyaratanSklTable extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        // 1. Tabel persyaratan_skl
        if (! $this->db->tableExists('persyaratan_skl')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'nama' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'keterangan' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'tipe_file' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'default'    => 'pdf',
                ],
                'max_size_kb' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 5120, // 5 MB
                ],
                'is_wajib' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'urutan' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
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
            $this->forge->addKey('status');
            $this->forge->addKey('urutan');
            $this->forge->createTable('persyaratan_skl', true);

            // Seed 3 persyaratan standar awal sesuai mockup & SOP fakultas
            $defaultItems = [
                [
                    'nama'        => 'Artikel',
                    'keterangan'  => 'Naskah publikasi artikel ilmiah skripsi dalam format PDF',
                    'tipe_file'   => 'pdf',
                    'max_size_kb' => 5120,
                    'is_wajib'    => 1,
                    'urutan'      => 1,
                    'status'      => 'AKTIF',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'nama'        => 'Lembar Pengesahan',
                    'keterangan'  => 'Lembar pengesahan skripsi bertanda tangan resmi pembimbing dan penguji',
                    'tipe_file'   => 'pdf',
                    'max_size_kb' => 5120,
                    'is_wajib'    => 1,
                    'urutan'      => 2,
                    'status'      => 'AKTIF',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'nama'        => 'Sertifikat Kompetensi',
                    'keterangan'  => 'Scan sertifikat uji kompetensi keahlian resmi',
                    'tipe_file'   => 'pdf',
                    'max_size_kb' => 5120,
                    'is_wajib'    => 1,
                    'urutan'      => 3,
                    'status'      => 'AKTIF',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
            ];

            $this->db->table('persyaratan_skl')->insertBatch($defaultItems);
        }

        // 2. Tabel pengajuan_skl
        if (! $this->db->tableExists('pengajuan_skl')) {
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
                'id_prodi' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'kode_semester' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['menunggu', 'disetujui', 'ditolak'],
                    'default'    => 'menunggu',
                ],
                'catatan' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'diajukan_pada' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'diverifikasi_oleh' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'diverifikasi_pada' => [
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
            $this->forge->addKey('status');
            $this->forge->createTable('pengajuan_skl', true);
        }

        // 3. Tabel berkas_pengajuan_skl
        if (! $this->db->tableExists('berkas_pengajuan_skl')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'pengajuan_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'npm' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                ],
                'persyaratan_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'nama_persyaratan' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'nama_file' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'ukuran_kb' => [
                    'type'       => 'INT',
                    'constraint' => 11,
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
            $this->forge->addKey('pengajuan_id');
            $this->forge->addKey('npm');
            $this->forge->addKey('persyaratan_id');
            $this->forge->createTable('berkas_pengajuan_skl', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('berkas_pengajuan_skl')) {
            $this->forge->dropTable('berkas_pengajuan_skl', true);
        }
        if ($this->db->tableExists('pengajuan_skl')) {
            $this->forge->dropTable('pengajuan_skl', true);
        }
        if ($this->db->tableExists('persyaratan_skl')) {
            $this->forge->dropTable('persyaratan_skl', true);
        }
    }
}

