<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration pembuatan tabel persyaratan_wisuda untuk The Graduate
 */
class CreatePersyaratanWisudaTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('persyaratan_wisuda')) {
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
                    'default'    => 'pdf,jpg,jpeg,png',
                ],
                'max_size_kb' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 2048,
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
            $this->forge->createTable('persyaratan_wisuda', true);

            // Seed 9 persyaratan standar awal
            $now = date('Y-m-d H:i:s');
            $defaultItems = [
                ['nama' => 'Fotocopy Berwarna Ijazah SMA', 'keterangan' => 'Scan berwarna asli dari ijazah SMA/sederajat', 'tipe_file' => 'pdf,jpg,jpeg,png', 'max_size_kb' => 2048, 'is_wajib' => 1, 'urutan' => 1, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'Fotocopy Berwarna Akte Kelahiran', 'keterangan' => 'Scan berwarna akte kelahiran resmi', 'tipe_file' => 'pdf,jpg,jpeg,png', 'max_size_kb' => 2048, 'is_wajib' => 1, 'urutan' => 2, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'Fotocopy Berwarna Kartu Keluarga', 'keterangan' => 'Scan kartu keluarga terbaru', 'tipe_file' => 'pdf,jpg,jpeg,png', 'max_size_kb' => 2048, 'is_wajib' => 1, 'urutan' => 3, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'Fotocopy Berwarna KTP yang berlaku', 'keterangan' => 'Scan e-KTP asli yang masih berlaku', 'tipe_file' => 'pdf,jpg,jpeg,png', 'max_size_kb' => 2048, 'is_wajib' => 1, 'urutan' => 4, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'Fotocopy Berwarna KTM yang berlaku', 'keterangan' => 'Scan kartu tanda mahasiswa resmi', 'tipe_file' => 'pdf,jpg,jpeg,png', 'max_size_kb' => 2048, 'is_wajib' => 1, 'urutan' => 5, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'Pas Foto Berwarna', 'keterangan' => 'Pas foto resmi terbaru berlatar belakang merah/biru pakaian formal', 'tipe_file' => 'jpg,jpeg,png', 'max_size_kb' => 2048, 'is_wajib' => 1, 'urutan' => 6, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'Surat Keterangan Kelulusan (SKL)', 'keterangan' => 'Surat keterangan kelulusan resmi dari fakultas', 'tipe_file' => 'pdf', 'max_size_kb' => 2048, 'is_wajib' => 1, 'urutan' => 7, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'Sertifikat TOEIC', 'keterangan' => 'Sertifikat tes kemampuan bahasa Inggris resmi', 'tipe_file' => 'pdf', 'max_size_kb' => 2048, 'is_wajib' => 1, 'urutan' => 8, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
                ['nama' => 'File Skripsi', 'keterangan' => 'Naskah lengkap draft final skripsi yang telah disahkan', 'tipe_file' => 'pdf', 'max_size_kb' => 10240, 'is_wajib' => 1, 'urutan' => 9, 'status' => 'AKTIF', 'created_at' => $now, 'updated_at' => $now],
            ];

            $this->db->table('persyaratan_wisuda')->insertBatch($defaultItems);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('persyaratan_wisuda')) {
            $this->forge->dropTable('persyaratan_wisuda', true);
        }
    }
}

