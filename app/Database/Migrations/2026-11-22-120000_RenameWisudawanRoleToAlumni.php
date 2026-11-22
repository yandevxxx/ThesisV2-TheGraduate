<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameWisudawanRoleToAlumni extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('role')) {
            $this->db->table('role')->where('nama', 'wisudawan')->update(['nama' => 'alumni']);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('role')) {
            $this->db->table('role')->where('nama', 'alumni')->update(['nama' => 'wisudawan']);
        }
    }
}
