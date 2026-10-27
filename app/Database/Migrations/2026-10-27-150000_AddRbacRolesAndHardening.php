<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Throwable;

/**
 * B-05a: RBAC roles and schema hardening.
 *
 * The role/akses tables already exist in the legacy database. This migration
 * is deliberately non destructive: it creates missing tables, removes
 * duplicates, restores the unique indexes/FKs when needed, and seeds the
 * canonical roles used by the RBAC policy.
 */
class AddRbacRolesAndHardening extends Migration
{
    /**
     * Roles that must exist after this migration.
     *
     * `dosen` is seeded again because some legacy databases only store the
     * role implicitly through the dosen profile table. `prodi` is the new
     * canonical role for Kaprodi/Prodi users.
     *
     * @var string[]
     */
    private const ROLES = ['dosen', 'prodi'];

    public function up()
    {
        $this->createTablesIfMissing();

        if (! $this->db->tableExists('role')) {
            return;
        }

        $this->dedupeRoles();
        $this->dedupeAkses();
        $this->ensureRoleUniqueIndex();
        $this->ensureAksesUniqueIndex();
        $this->ensureAksesForeignKeys();

        foreach (self::ROLES as $nama) {
            $exists = $this->db->table('role')->where('nama', $nama)->countAllResults() > 0;

            if (! $exists) {
                $this->db->table('role')->insert(['nama' => $nama]);
            }
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('role')) {
            return;
        }

        $role = $this->db->table('role')->where('nama', 'prodi')->get()->getRowArray();

        if ($role === null) {
            return;
        }

        if ($this->db->tableExists('akses')) {
            $this->db->table('akses')->where('id_role', $role['id'])->delete();
        }

        $this->db->table('role')->where('id', $role['id'])->delete();

        // `dosen` pre-dates B-05a, so it is intentionally not removed here.
    }

    private function createTablesIfMissing(): void
    {
        if (! $this->db->tableExists('role')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'auto_increment' => true,
                ],
                'nama' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('nama', 'uniq_role_nama');
            $this->forge->createTable('role', true);
        }

        if (! $this->db->tableExists('akses')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'auto_increment' => true,
                ],
                'id_akun' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => false,
                ],
                'id_role' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => false,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('id_akun');
            $this->forge->addKey('id_role');
            $this->forge->addUniqueKey(['id_akun', 'id_role'], 'uniq_akses_akun_role');
            $this->forge->createTable('akses', true);
        }
    }

    /**
     * Merge duplicate role names into the lowest id, remapping akses rows
     * before removing the duplicate role. Keeps legacy data usable when the
     * unique index was missing.
     */
    private function dedupeRoles(): void
    {
        $duplicates = $this->db->query(
            'SELECT nama, MIN(id) AS keep_id FROM role GROUP BY nama HAVING COUNT(*) > 1'
        )->getResultArray();

        foreach ($duplicates as $duplicate) {
            $ids = $this->db->table('role')
                ->select('id')
                ->where('nama', $duplicate['nama'])
                ->where('id !=', $duplicate['keep_id'])
                ->get()
                ->getResultArray();

            foreach ($ids as $row) {
                if ($this->db->tableExists('akses')) {
                    $this->db->query(
                        'UPDATE IGNORE akses SET id_role = ? WHERE id_role = ?',
                        [(int) $duplicate['keep_id'], (int) $row['id']]
                    );
                    $this->db->table('akses')->where('id_role', $row['id'])->delete();
                }

                $this->db->table('role')->where('id', $row['id'])->delete();
            }
        }
    }

    private function dedupeAkses(): void
    {
        if (! $this->db->tableExists('akses')) {
            return;
        }

        $this->db->query(
            'DELETE a FROM akses a
             INNER JOIN akses b
                ON a.id_akun = b.id_akun
               AND a.id_role = b.id_role
               AND a.id > b.id'
        );
    }

    private function ensureRoleUniqueIndex(): void
    {
        if ($this->hasIndex('role', 'uniq_role_nama')) {
            return;
        }

        try {
            $this->db->query('ALTER TABLE `role` ADD UNIQUE KEY `uniq_role_nama` (`nama`)');
        } catch (Throwable $e) {
            log_message('warning', 'RBAC migration: unable to add uniq_role_nama: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function ensureAksesUniqueIndex(): void
    {
        if (! $this->db->tableExists('akses') || $this->hasIndex('akses', 'uniq_akses_akun_role')) {
            return;
        }

        try {
            $this->db->query(
                'ALTER TABLE `akses` ADD UNIQUE KEY `uniq_akses_akun_role` (`id_akun`, `id_role`)'
            );
        } catch (Throwable $e) {
            log_message('warning', 'RBAC migration: unable to add uniq_akses_akun_role: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function ensureAksesForeignKeys(): void
    {
        if (! $this->db->tableExists('akses') || ! $this->db->tableExists('akun')) {
            return;
        }

        $this->addForeignKeyIfMissing('akses', 'akses_ibfk_1', 'id_akun', 'akun', 'id');
        $this->addForeignKeyIfMissing('akses', 'akses_ibfk_2', 'id_role', 'role', 'id');
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $constraint,
        string $column,
        string $referencedTable,
        string $referencedColumn
    ): void {
        if ($this->hasForeignKey($table, $constraint)) {
            return;
        }

        try {
            $this->db->query(
                'ALTER TABLE `' . $table . '` ADD CONSTRAINT `' . $constraint . '` '
                . 'FOREIGN KEY (`' . $column . '`) REFERENCES `' . $referencedTable . '` (`' . $referencedColumn . '`)'
            );
        } catch (Throwable $e) {
            log_message('warning', 'RBAC migration: unable to add FK {constraint}: {message}', [
                'constraint' => $constraint,
                'message'    => $e->getMessage(),
            ]);
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        $rows = $this->db->query('SHOW INDEX FROM `' . $table . '`')->getResultArray();

        foreach ($rows as $row) {
            if (isset($row['Key_name']) && strcasecmp((string) $row['Key_name'], $index) === 0) {
                return true;
            }
        }

        return false;
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        $database = $this->db->database;

        $row = $this->db->query(
            'SELECT COUNT(*) AS total
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ?
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$database, $table, $constraint, 'FOREIGN KEY']
        )->getRowArray();

        return isset($row['total']) && (int) $row['total'] > 0;
    }
}
