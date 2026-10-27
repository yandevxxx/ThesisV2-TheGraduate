<?php

namespace App\Commands;

use App\Libraries\LdapProfileSync;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Memproses antrean sinkronisasi profil LDAP ke akun/profil lokal.
 *
 * Contoh cron harian:
 *   php spark auth:sync-ldap-profiles --limit=100
 */
class SyncLdapProfiles extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:sync-ldap-profiles';
    protected $description = 'Memproses antrean sinkronisasi profil LDAP ke akun lokal.';
    protected $usage       = 'auth:sync-ldap-profiles [--limit=50] [--dry-run]';
    protected $options     = [
        '--limit'   => 'Jumlah maksimum antrean yang diproses (default 50, maksimum 500).',
        '--dry-run' => 'Tampilkan jumlah antrean tanpa memproses.',
    ];

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('ldap_profile_sync')) {
            CLI::error('Tabel ldap_profile_sync belum ada. Jalankan: php spark migrate');

            return;
        }

        $limit = (int) (CLI::getOption('limit') ?: 50);

        if (CLI::getOption('dry-run')) {
            $pending = $db->table('ldap_profile_sync')->where('status', 'pending')->countAllResults();
            $failed  = $db->table('ldap_profile_sync')->where('status', 'failed')->countAllResults();

            CLI::write($pending . ' antrean pending dan ' . $failed . ' gagal (dry-run, tidak ada perubahan).', 'yellow');

            return;
        }

        $stats = (new LdapProfileSync())->processPending($limit);

        CLI::write(
            'Diproses: ' . $stats['processed']
            . ', berhasil: ' . $stats['succeeded']
            . ', gagal: ' . $stats['failed']
            . ', dilewati: ' . $stats['skipped'] . '.',
            $stats['failed'] > 0 ? 'yellow' : 'green'
        );

        if ($stats['failed'] > 0) {
            CLI::write('Periksa kolom last_error pada tabel ldap_profile_sync.', 'yellow');
        }
    }
}
