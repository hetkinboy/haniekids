<?php

namespace App\Models;

use CodeIgniter\Model;

class PersonalAccessTokenModel extends Model
{
    protected $table = 'personal_access_tokens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'user_id',
        'token_hash',
        'name',
        'last_used_at',
        'expires_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}

