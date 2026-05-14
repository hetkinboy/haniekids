<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'role_id',
        'name',
        'email',
        'password_hash',
        'status',
        'last_login_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function findActiveByEmail(string $email): ?array
    {
        return $this->select('users.*, roles.name AS role')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.email', $email)
            ->where('users.status', 'active')
            ->first();
    }

    public function findWithRole(int $id): ?array
    {
        return $this->select('users.id, users.role_id, users.name, users.email, users.status, users.last_login_at, roles.name AS role')
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $id)
            ->where('users.status', 'active')
            ->first();
    }
}

