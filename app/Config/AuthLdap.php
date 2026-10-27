<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * LDAP connection settings.
 *
 * All public properties can be overridden from .env with the
 * authldap. prefix, for example:
 *
 *   authldap.ldapDomain = ldap.example.ac.id
 *   authldap.baseDn = dc=example,dc=ac,dc=id
 *   authldap.useTls = true
 */
class AuthLdap extends BaseConfig
{
    /** LDAP host, without a scheme or port. */
    public $ldapDomain = 'pdc.yarsi.ac.id';

    /** Use LDAPS (usually port 636). */
    public $useTls = false;

    /** Use StartTLS on a plain LDAP connection (usually port 389). */
    public $useStartTls = false;

    public $tcpPort = [
        'default' => 389,
        'tls'     => 636,
    ];

    /** Base DN used for user searches. */
    public $baseDn = 'dc=yarsi,dc=ac,dc=id';

    /** Groups DN (kept for the legacy group helpers). */
    public $groupsDn = 'ou=Groups,dc=yarsi,dc=ac,dc=id';

    /**
     * Optional service account used for the search bind.
     * Leave empty to try an anonymous bind.
     */
    public $bindDn = '';

    /** Password for $bindDn. */
    public $bindPassword = '';

    /** Attribute that stores the login name. */
    public $ldapUserAttribute = 'uid';

    /** Attribute used for groups. */
    public $ldapGroupAttribute = 'cn';

    /** Attributes requested from the LDAP server (dn is added by LDAP itself). */
    public $ldapSearchAttribute = [
        'cn',
        'sn',
        'givenName',
        'displayName',
        'mail',
        'telephoneNumber',
        'description',
        'title',
        'employeeNumber',
        'employeeType',
        'homePhone',
        'street',
        'homePostalAddress',
        'uid',
    ];

    /** Attribute containing the M/D/S role marker (or a descriptive title). */
    public $roleAttribute = 'title';

    public $ldapMemberOfGroupsIdentifier = 'cn';

    /** Seconds allowed for connect, search and bind operations. */
    public $networkTimeout = 5;

    /** Optional size limit for searches. */
    public $searchSizeLimit = 2;

    /** Runtime group data used by the legacy helper methods. */
    private $groups = [];
    private $groupByUsers = [];
    private $userNameAndGroup = [];

    public function isTlsEnabled(): bool
    {
        return $this->useTls || $this->useStartTls;
    }

    public function getLdapBaseDN(): string
    {
        return $this->baseDn;
    }

    public function getLdapGroupsDN(): string
    {
        return $this->groupsDn;
    }

    public function getLdapGroupAttribute(): string
    {
        return $this->ldapGroupAttribute;
    }

    public function getLdapUserAttribute(): string
    {
        return $this->ldapUserAttribute;
    }

    public function getLdapUrl(): string
    {
        $port   = $this->useTls ? $this->tcpPort['tls'] : $this->tcpPort['default'];
        $scheme = $this->useTls ? 'ldaps' : 'ldap';

        return sprintf('%s://%s:%d', $scheme, $this->ldapDomain, $port);
    }

    public function getLdapSearchAttribute(): array
    {
        $attributes = array_merge(
            $this->ldapSearchAttribute,
            [$this->ldapUserAttribute, 'cn', 'displayName', 'mail', $this->roleAttribute]
        );

        $filtered = [];

        foreach ($attributes as $attribute) {
            $attribute = trim((string) $attribute);

            if ($attribute === '' || strtolower($attribute) === 'dn') {
                continue;
            }

            if (! in_array($attribute, $filtered, true)) {
                $filtered[] = $attribute;
            }
        }

        return $filtered;
    }

    public function getLdapMemberOfGroupsIdentifier(): string
    {
        return $this->ldapMemberOfGroupsIdentifier;
    }

    public function getRoleAttribute(): string
    {
        return $this->roleAttribute;
    }

    public function getNetworkTimeout(): int
    {
        return max(1, (int) $this->networkTimeout);
    }

    public function getSearchSizeLimit(): int
    {
        return max(1, (int) $this->searchSizeLimit);
    }

    public function setGroup(string $groupName, array $users): void
    {
        if (! in_array($groupName, $this->groups, true)) {
            $this->groups[]                 = $groupName;
            $this->groupByUsers[$groupName] = $users;
        }
    }

    public function setUserAndGroup(string $userName, string $groupName): void
    {
        $this->userNameAndGroup[$userName][] = $groupName;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getGroupByUsers(): array
    {
        return $this->groupByUsers;
    }

    public function getRoleByUserName($userName): array
    {
        return isset($this->userNameAndGroup[$userName]) ? $this->userNameAndGroup[$userName] : [];
    }
}
