<?php

namespace App\Database\Migrations;

use App\Libraries\LocalCredential;
use CodeIgniter\Database\Migration;
use RuntimeException;

/**
 * B-04 hardening for the local password column.
 *
 * Every non-NULL akun.password must already be a password hash. The migration
 * aborts before altering the table when plain text is found; only account IDs
 * are included in the error so no credential is written to logs.
 */
class HardenAkunPasswordColumn extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('akun')) {
            return;
        }

        $rows = $this->db->table('akun')
            ->select('id, password')
            ->where('password IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        $invalidIds = [];

        foreach ($rows as $row) {
            if (! LocalCredential::isHash($row['password'])) {
                $invalidIds[] = (int) $row['id'];
            }
        }

        if ($invalidIds !== []) {
            sort($invalidIds);

            throw new RuntimeException(
                'Migrasi dibatalkan: kolom akun.password berisi nilai non-hash pada akun ID: '
                . implode(', ', $invalidIds)
                . '. Perbaiki data tersebut sebelum menjalankan migrasi.'
            );
        }

        $this->forge->modifyColumn('akun', [
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        if (! $this->db->tableExists('akun')) {
            return;
        }

        $this->forge->modifyColumn('akun', [
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
        ]);
    }
}
