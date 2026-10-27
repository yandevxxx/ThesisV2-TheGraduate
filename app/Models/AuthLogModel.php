<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthLogModel extends Model
{
    protected $table = 'auth_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'username',
        'akun_id',
        'event',
        'status',
        'auth_method',
        'roles',
        'reason',
        'ip_address',
        'user_agent',
        'created_at',
    ];
    protected $useTimestamps = false;
}
