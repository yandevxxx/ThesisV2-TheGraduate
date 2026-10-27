<?php

namespace App\Libraries;

use Config\AuthLdap as AuthLdapConfig;

/**
 * LDAP authentication client.
 *
 * authenticate() always returns an array. On success it contains
 * authenticated=true and the mapped user profile. On failure it contains
 * authenticated=false and an error code:
 *   - invalid_credentials : user unknown or wrong password
 *   - unavailable         : server/network is down or timed out
 *   - configuration       : missing extension, bad DN/filter settings, TLS failure
 */
class AuthLdap
{
    /** @var AuthLdapConfig */
    private $config;

    /** @var resource|\LDAP\Connection|null */
    private $connection;

    public function __construct()
    {
        $this->config     = config('AuthLdap');
        $this->connection = null;
    }

    public function authenticate(string $userName, string $password): array
    {
        $userName = trim($userName);

        if ($userName === '' || $password === '') {
            return $this->failure('invalid_credentials');
        }

        if (! extension_loaded('ldap')) {
            log_message('critical', 'LDAP extension is not loaded.');

            return $this->failure('configuration');
        }

        if ($this->config->ldapDomain === '' || $this->config->baseDn === '') {
            log_message('critical', 'LDAP host or base DN is empty.');

            return $this->failure('configuration');
        }

        $connection = $this->connect();

        if ($connection === null) {
            return $this->failure('unavailable');
        }

        $this->setOptions($connection);

        if ($this->config->useStartTls) {
            if (! @ldap_start_tls($connection)) {
                log_message('error', 'LDAP StartTLS failed: ' . ldap_error($connection));

                return $this->failure('unavailable');
            }
        }

        if (! $this->bindForSearch($connection)) {
            return $this->failure('configuration');
        }

        $filter = '(' . $this->config->getLdapUserAttribute() . '=' . self::escapeFilterValue($userName) . ')';

        $search = @ldap_search(
            $connection,
            $this->config->getLdapBaseDN(),
            $filter,
            $this->config->getLdapSearchAttribute(),
            0,
            $this->config->getSearchSizeLimit(),
            $this->config->getNetworkTimeout()
        );

        if ($search === false) {
            $errno = ldap_errno($connection);
            log_message('error', 'LDAP search failed (' . $errno . '): ' . ldap_error($connection));

            return $this->failure($this->classifyError($errno));
        }

        $entries = @ldap_get_entries($connection, $search);

        if (! is_array($entries) || (int) ($entries['count'] ?? 0) === 0) {
            return $this->failure('invalid_credentials');
        }

        if ((int) $entries['count'] > 1) {
            log_message('warning', 'LDAP returned more than one entry for username ' . $userName);
        }

        $entry = $entries[0];
        $dn    = isset($entry['dn']) ? $entry['dn'] : '';

        if ($dn === '') {
            return $this->failure('invalid_credentials');
        }

        if (! @ldap_bind($connection, $dn, $password)) {
            $errno = ldap_errno($connection);
            log_message('info', 'LDAP credential bind failed for ' . $userName . ' (' . $errno . ').');

            return $this->failure($this->classifyError($errno));
        }

        $title = $this->firstValue($entry, $this->config->getRoleAttribute());
        $role  = self::mapRole($title);

        return [
            'authenticated'     => true,
            'dn'                => $dn,
            'username'          => $userName,
            'cn'                => $this->firstValue($entry, 'cn'),
            'displayname'       => $this->firstValue($entry, 'displayName') ?: $this->firstValue($entry, 'cn'),
            'mail'              => $this->firstValue($entry, 'mail'),
            'role'              => $role,
            'role_code'         => $role === null ? null : $this->roleCode($role),
            'id_nik'            => $this->firstValue($entry, 'description') ?: '-',
            'employee_number'   => $this->firstValue($entry, 'employeeNumber'),
            'street'            => $this->firstValue($entry, 'street') ?: '-',
            'homephone'         => $this->firstValue($entry, 'homePhone') ?: '-',
            'homepostaladdress' => $this->firstValue($entry, 'homePostalAddress') ?: '-',
            'telephonenumber'   => $this->firstValue($entry, 'telephoneNumber') ?: '-',
            'logged_in'         => true,
        ];
    }

    /**
     * Escape a value before putting it into an LDAP filter (RFC 4515).
     */
    public static function escapeFilterValue(string $value): string
    {
        if (function_exists('ldap_escape')) {
            return ldap_escape($value, '', LDAP_ESCAPE_FILTER);
        }

        return strtr($value, [
            '\\' => '\\5c',
            '*'  => '\\2a',
            '('  => '\\28',
            ')'  => '\\29',
            "\0" => '\\00',
        ]);
    }

    /**
     * @return resource|\LDAP\Connection|null
     */
    private function connect()
    {
        $connection = @ldap_connect($this->config->getLdapUrl());

        if ($connection === false) {
            log_message('error', 'Unable to create LDAP connection to ' . $this->config->getLdapUrl());

            return null;
        }

        $this->connection = $connection;

        return $connection;
    }

    /**
     * @param resource|\LDAP\Connection $connection
     */
    private function setOptions($connection): void
    {
        @ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
        @ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, $this->config->getNetworkTimeout());
        @ldap_set_option($connection, LDAP_OPT_TIMELIMIT, $this->config->getNetworkTimeout());
    }

    /**
     * Bind with the configured service account, or anonymously when none is set.
     *
     * @param resource|\LDAP\Connection $connection
     */
    private function bindForSearch($connection): bool
    {
        $bindDn       = trim((string) $this->config->bindDn);
        $bindPassword = (string) $this->config->bindPassword;

        if ($bindDn !== '') {
            if (@ldap_bind($connection, $bindDn, $bindPassword)) {
                return true;
            }

            log_message('error', 'LDAP service-account bind failed: ' . ldap_error($connection));

            return false;
        }

        if (! @ldap_bind($connection)) {
            log_message('warning', 'LDAP anonymous bind failed: ' . ldap_error($connection));
        }

        return true;
    }

    private function firstValue(array $entry, string $attribute): string
    {
        $key = strtolower($attribute);

        if (isset($entry[$key][0]) && is_scalar($entry[$key][0])) {
            return trim((string) $entry[$key][0]);
        }

        return '';
    }

    public static function mapRole(string $title): ?string
    {
        $normalised = strtoupper(trim($title));

        if ($normalised === '') {
            return null;
        }

        $code = substr($normalised, 0, 1);

        if ($code === 'M' || strpos($normalised, 'MAHASISWA') !== false || strpos($normalised, 'STUDENT') !== false) {
            return 'Mahasiswa';
        }

        if ($code === 'D' || strpos($normalised, 'DOSEN') !== false || strpos($normalised, 'LECTURER') !== false) {
            return 'Dosen';
        }

        if ($code === 'S' || strpos($normalised, 'STAFF') !== false || strpos($normalised, 'TENDIK') !== false || strpos($normalised, 'KEPENDIDIKAN') !== false) {
            return 'Staff';
        }

        return null;
    }

    private function roleCode(string $role): string
    {
        $codes = ['Mahasiswa' => 'M', 'Dosen' => 'D', 'Staff' => 'S'];

        return isset($codes[$role]) ? $codes[$role] : '';
    }

    public static function classifyError(int $errno): string
    {
        $unavailable = [
            -1, // OpenLDAP LDAP_SERVER_DOWN; not always defined by PHP on Windows.
            defined('LDAP_SERVER_DOWN') ? LDAP_SERVER_DOWN : -1,
            defined('LDAP_TIMEOUT') ? LDAP_TIMEOUT : 85,
            defined('LDAP_CONNECT_ERROR') ? LDAP_CONNECT_ERROR : 91,
            defined('LDAP_BUSY') ? LDAP_BUSY : 51,
            defined('LDAP_UNAVAILABLE') ? LDAP_UNAVAILABLE : 52,
        ];

        if (in_array($errno, $unavailable, true)) {
            return 'unavailable';
        }

        $invalidCredentials = defined('LDAP_INVALID_CREDENTIALS') ? LDAP_INVALID_CREDENTIALS : 49;

        if ($errno === $invalidCredentials) {
            return 'invalid_credentials';
        }

        return 'configuration';
    }

    private function failure(string $error): array
    {
        return [
            'authenticated' => false,
            'error'         => $error,
        ];
    }
}
