<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration penambahan kolom status_wisudawan, tgl_approval_wisudawan, dan catatan_wisudawan
 * pada tabel pendaftaran_wisuda untuk mendukung alur:
 * Calon Wisudawan (Biodata & Berkas Disetujui) -> Approve Tendik -> Mahasiswa Wisudawan.
 */
class AddStatusWisudawanToPendaftaranWisuda extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('pendaftaran_wisuda')) {
            $fieldsToAdd = [];

            if (! $this->db->fieldExists('status_wisudawan', 'pendaftaran_wisuda')) {
                $fieldsToAdd['status_wisudawan'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'MENUNGGU',
                    'null'       => false,
                    'after'      => 'status_berkas',
                ];
            }

            if (! $this->db->fieldExists('tgl_approval_wisudawan', 'pendaftaran_wisuda')) {
                $fieldsToAdd['tgl_approval_wisudawan'] = [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'status_wisudawan',
                ];
            }

            if (! $this->db->fieldExists('catatan_wisudawan', 'pendaftaran_wisuda')) {
                $fieldsToAdd['catatan_wisudawan'] = [
                    'type'  => 'TEXT',
                    'null'  => true,
                    'after' => 'tgl_approval_wisudawan',
                ];
            }

            if (! empty($fieldsToAdd)) {
                $this->forge->addColumn('pendaftaran_wisuda', $fieldsToAdd);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('pendaftaran_wisuda')) {
            $colsToDrop = ['status_wisudawan', 'tgl_approval_wisudawan', 'catatan_wisudawan'];
            foreach ($colsToDrop as $col) {
                if ($this->db->fieldExists($col, 'pendaftaran_wisuda')) {
                    $this->forge->dropColumn('pendaftaran_wisuda', $col);
                }
            }
        }
    }
}
