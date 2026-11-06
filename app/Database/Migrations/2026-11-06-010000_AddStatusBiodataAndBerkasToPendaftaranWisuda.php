<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration penambahan kolom status_biodata, catatan_biodata, status_berkas, dan catatan_berkas
 * pada tabel pendaftaran_wisuda untuk memisahkan verifikasi biodata dan berkas persyaratan secara mandiri.
 */
class AddStatusBiodataAndBerkasToPendaftaranWisuda extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('pendaftaran_wisuda')) {
            $fieldsToAdd = [];

            if (! $this->db->fieldExists('status_biodata', 'pendaftaran_wisuda')) {
                $fieldsToAdd['status_biodata'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'DRAFT',
                    'null'       => false,
                    'after'      => 'pas_foto',
                ];
            }

            if (! $this->db->fieldExists('catatan_biodata', 'pendaftaran_wisuda')) {
                $fieldsToAdd['catatan_biodata'] = [
                    'type'  => 'TEXT',
                    'null'  => true,
                    'after' => 'status_biodata',
                ];
            }

            if (! $this->db->fieldExists('status_berkas', 'pendaftaran_wisuda')) {
                $fieldsToAdd['status_berkas'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'DRAFT',
                    'null'       => false,
                    'after'      => 'catatan_biodata',
                ];
            }

            if (! $this->db->fieldExists('catatan_berkas', 'pendaftaran_wisuda')) {
                $fieldsToAdd['catatan_berkas'] = [
                    'type'  => 'TEXT',
                    'null'  => true,
                    'after' => 'status_berkas',
                ];
            }

            if (! empty($fieldsToAdd)) {
                $this->forge->addColumn('pendaftaran_wisuda', $fieldsToAdd);
            }

            // Sinkronisasi data existing agar selaras dengan status_verifikasi saat ini
            $this->db->query("
                UPDATE pendaftaran_wisuda 
                SET 
                    status_biodata = CASE 
                        WHEN status_verifikasi = 'MENUNGGU' THEN 'MENUNGGU'
                        WHEN status_verifikasi = 'DISETUJUI' THEN 'DISETUJUI'
                        WHEN status_verifikasi = 'DITOLAK' THEN 'DITOLAK'
                        ELSE 'DRAFT'
                    END,
                    status_berkas = CASE 
                        WHEN status_verifikasi = 'MENUNGGU' THEN 'MENUNGGU'
                        WHEN status_verifikasi = 'DISETUJUI' THEN 'DISETUJUI'
                        WHEN status_verifikasi = 'DITOLAK' THEN 'DITOLAK'
                        ELSE 'DRAFT'
                    END
                WHERE status_biodata = 'DRAFT' OR status_biodata IS NULL
            ");
        }
    }

    public function down()
    {
        if ($this->db->tableExists('pendaftaran_wisuda')) {
            $colsToDrop = ['status_biodata', 'catatan_biodata', 'status_berkas', 'catatan_berkas'];
            foreach ($colsToDrop as $col) {
                if ($this->db->fieldExists($col, 'pendaftaran_wisuda')) {
                    $this->forge->dropColumn('pendaftaran_wisuda', $col);
                }
            }
        }
    }
}

