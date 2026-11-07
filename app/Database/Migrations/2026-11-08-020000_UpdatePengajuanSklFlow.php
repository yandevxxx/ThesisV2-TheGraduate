<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration penambahan status 'proses', kolom file_skl, no_surat, dan tgl_terbit pada tabel pengajuan_skl
 */
class UpdatePengajuanSklFlow extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('pengajuan_skl')) {
            // Ubah tipe kolom status untuk mendukung nilai 'proses'
            $this->forge->modifyColumn('pengajuan_skl', [
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['menunggu', 'proses', 'disetujui', 'ditolak'],
                    'default'    => 'menunggu',
                ],
            ]);

            // Tambahkan kolom no_surat, file_skl, tgl_terbit, dikirim_pada, dikirim_oleh jika belum ada
            $fieldsToAdd = [];

            if (! $this->db->fieldExists('no_surat', 'pengajuan_skl')) {
                $fieldsToAdd['no_surat'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'status',
                ];
            }

            if (! $this->db->fieldExists('file_skl', 'pengajuan_skl')) {
                $fieldsToAdd['file_skl'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'no_surat',
                ];
            }

            if (! $this->db->fieldExists('tgl_terbit', 'pengajuan_skl')) {
                $fieldsToAdd['tgl_terbit'] = [
                    'type'  => 'DATE',
                    'null'  => true,
                    'after' => 'file_skl',
                ];
            }

            if (! $this->db->fieldExists('dikirim_pada', 'pengajuan_skl')) {
                $fieldsToAdd['dikirim_pada'] = [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'tgl_terbit',
                ];
            }

            if (! $this->db->fieldExists('dikirim_oleh', 'pengajuan_skl')) {
                $fieldsToAdd['dikirim_oleh'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'after'      => 'dikirim_pada',
                ];
            }

            if (! empty($fieldsToAdd)) {
                $this->forge->addColumn('pengajuan_skl', $fieldsToAdd);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('pengajuan_skl')) {
            $colsToDrop = [];
            foreach (['dikirim_oleh', 'dikirim_pada', 'tgl_terbit', 'file_skl', 'no_surat'] as $col) {
                if ($this->db->fieldExists($col, 'pengajuan_skl')) {
                    $colsToDrop[] = $col;
                }
            }
            if (! empty($colsToDrop)) {
                $this->forge->dropColumn('pengajuan_skl', $colsToDrop);
            }

            $this->forge->modifyColumn('pengajuan_skl', [
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['menunggu', 'disetujui', 'ditolak'],
                    'default'    => 'menunggu',
                ],
            ]);
        }
    }
}
