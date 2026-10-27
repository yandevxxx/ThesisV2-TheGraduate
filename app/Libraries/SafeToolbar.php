<?php

namespace App\Libraries;

use CodeIgniter\Debug\Toolbar;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Debug toolbar wrapper that removes credential fields before the toolbar
 * data is persisted to writable/debugbar or rendered.
 *
 * CodeIgniter's default toolbar stores every POST field, including the login
 * password. The toolbar is only active in development, but credentials must
 * still never be written to disk.
 */
class SafeToolbar extends Toolbar
{
    /** POST fields that must never be persisted. */
    private const SENSITIVE_POST_FIELDS = [
        'password',
        'password_confirm',
        'current_password',
        'new_password',
        'passwd',
    ];

    public function run(float $startTime, float $totalTime, RequestInterface $request, ResponseInterface $response): string
    {
        $json = parent::run($startTime, $totalTime, $request, $response);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            return $json;
        }

        foreach (self::SENSITIVE_POST_FIELDS as $field) {
            unset($data['vars']['post'][$field]);
        }

        $safe = json_encode($data, JSON_UNESCAPED_SLASHES);

        return $safe === false ? $json : $safe;
    }
}
