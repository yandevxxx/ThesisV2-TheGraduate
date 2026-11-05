<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration penambahan status DRAFT pada pendaftaran_wisuda
 *
 * Mengubah kolom status_verifikasi agar mendukung status DRAFT
 * sehingga mahasiswa yang baru mengunggah dokumen tidak langsung
 * masuk ke antrean verifikasi tendik sebelum formulir pendaftaran dikirimkan.
 */
class AddDraftStatusToPendaftaranWisuda extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('pendaftaran_wisuda')) {
            $this->forge->modifyColumn('pendaftaran_wisuda', [
                'status_verifikasi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'DRAFT',
                ],
            ]);

            // Ubah data yang belum diisi biodatanya (nik null) menjadi DRAFT
            try {
                $this->db->table('pendaftaran_wisuda')
                    ->where('nik IS NULL', null, false)
                    ->where('status_verifikasi', 'MENUNGGU')
                    ->update(['status_verifikasi' => 'DRAFT']);
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        if ($this->db->tableExists('pendaftaran_wisuda')) {
            $this->forge->modifyColumn('pendaftaran_wisuda', [
                'status_verifikasi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'MENUNGGU',
                ],
            ]);
        }
    }
}

