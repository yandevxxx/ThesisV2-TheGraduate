<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use Config\Toolbar as ToolbarConfig;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Use a toolbar that does not persist credential fields.
     */
    public static function toolbar(?ToolbarConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('toolbar', $config);
        }

        $config = $config ?? config('Toolbar');

        return new \App\Libraries\SafeToolbar($config);
    }

    /**
     * Penanganan exception terpusat dengan pesan umum + Event ID.
     */
    public static function exceptions(
        ?\Config\Exceptions $config = null,
        ?\CodeIgniter\HTTP\IncomingRequest $request = null,
        ?\CodeIgniter\HTTP\Response $response = null,
        bool $getShared = true
    ) {
        if ($getShared) {
            return static::getSharedInstance('exceptions', $config, $request, $response);
        }

        $config   = $config ?? config('Exceptions');
        $request  = $request ?? static::request();
        $response = $response ?? static::response();

        return new \App\Libraries\SecurityExceptions($config, $request, $response);
    }

    /**
     * Layanan LDAP kampus. Dibuat sebagai service agar bisa di-mock saat unit test.
     */
    public static function authLdap(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('authLdap');
        }

        return new \App\Libraries\AuthLdap();
    }

    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */
}