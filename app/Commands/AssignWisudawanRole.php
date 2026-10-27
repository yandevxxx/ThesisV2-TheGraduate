<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Menetapkan role wisudawan (aditif) kepada mahasiswa yang skripsinya Lulus.
 *
 * Jalankan setiap kali data kelulusan bertambah:
 *   php spark auth:assign-wisudawan
 *   php spark auth:assign-wisudawan --dry-run
 */
class AssignWisudawanRole extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:assign-wisudawan';
    protected $description = 'Menetapkan role wisudawan kepada mahasiswa dengan skripsi berstatus Lulus.';
    protected $usage       = 'auth:assign-wisudawan [--dry-run]';
    protected $options     = [
        '--dry-run' => 'Tampilkan jumlah akun yang akan diproses tanpa mengubah database.',
    ];

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('role') || ! $db->tableExists('akses') || ! $db->tableExists('skripsi')) {
            CLI::error('Tabel role/akses/skripsi tidak ditemukan. Jalankan migrasi terlebih dahulu.');

            return;
        }

        $role = $db->table('role')->where('nama', 'wisudawan')->get()->getRowArray();

        if ($role === null) {
            CLI::error('Role wisudawan belum ada. Jalankan: php spark migrate');

            return;
        }

        $roleId = (int) $role['id'];

        $countSql = "SELECT COUNT(DISTINCT a.id) AS total
                     FROM akun a
                     JOIN mahasiswa m ON m.email = a.email
                     JOIN skripsi s ON s.npm = m.npm
                     LEFT JOIN akses x ON x.id_akun = a.id AND x.id_role = ?
                     WHERE s.status = 'Lulus' AND x.id IS NULL";

        $countRow = $db->query($countSql, [$roleId])->getRowArray();
        $total    = $countRow === null ? 0 : (int) $countRow['total'];

        if ($total === 0) {
            CLI::write('Semua akun lulusan sudah memiliki role wisudawan.', 'green');

            return;
        }

        if (CLI::getOption('dry-run')) {
            CLI::write($total . ' akun akan mendapat role wisudawan (dry-run, tidak ada perubahan).', 'yellow');

            return;
        }

        $insertSql = "INSERT INTO akses (id_akun, id_role)
                      SELECT DISTINCT a.id, ?
                      FROM akun a
                      JOIN mahasiswa m ON m.email = a.email
                      JOIN skripsi s ON s.npm = m.npm
                      LEFT JOIN akses x ON x.id_akun = a.id AND x.id_role = ?
                      WHERE s.status = 'Lulus' AND x.id IS NULL";

        $db->query($insertSql, [$roleId, $roleId]);

        CLI::write($total . ' akun berhasil mendapat role wisudawan.', 'green');
    }
}
