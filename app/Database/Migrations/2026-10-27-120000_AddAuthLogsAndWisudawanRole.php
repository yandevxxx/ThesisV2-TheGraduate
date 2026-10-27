<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAuthLogsAndWisudawanRole extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('auth_logs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'username' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'akun_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'event' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => false,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                ],
                'auth_method' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'roles' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'reason' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'ip_address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 45,
                    'null'       => true,
                ],
                'user_agent' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('username');
            $this->forge->addKey('event');
            $this->forge->addKey('created_at');
            $this->forge->createTable('auth_logs', true);
        }

        $role = $this->db->table('role')->where('nama', 'wisudawan')->get()->getRowArray();

        if ($role === null) {
            $this->db->table('role')->insert(['nama' => 'wisudawan']);
            $role = $this->db->table('role')->where('nama', 'wisudawan')->get()->getRowArray();
        }

        if ($role === null || ! $this->db->tableExists('akses')) {
            return;
        }

        $roleId = (int) $role['id'];

        /*
         * Role wisudawan bersifat aditif: akun mahasiswa tetap mempertahankan
         * role mahasiswa, lalu mendapat role wisudawan bila skripsinya Lulus.
         */
        $sql = "INSERT INTO akses (id_akun, id_role)
                SELECT DISTINCT a.id, ?
                FROM akun a
                JOIN mahasiswa m ON m.email = a.email
                JOIN skripsi s ON s.npm = m.npm
                LEFT JOIN akses x ON x.id_akun = a.id AND x.id_role = ?
                WHERE s.status = 'Lulus' AND x.id IS NULL";

        $this->db->query($sql, [$roleId, $roleId]);
    }

    public function down()
    {
        $role = $this->db->table('role')->where('nama', 'wisudawan')->get()->getRowArray();

        if ($role !== null) {
            $this->db->table('akses')->where('id_role', $role['id'])->delete();
            $this->db->table('role')->where('id', $role['id'])->delete();
        }

        $this->forge->dropTable('auth_logs', true);
    }
}
