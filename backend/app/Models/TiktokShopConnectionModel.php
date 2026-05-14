<?php

namespace App\Models;

use CodeIgniter\Model;

class TiktokShopConnectionModel extends Model
{
    protected $table = 'tiktok_shop_connections';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'shop_name',
        'shop_id',
        'shop_cipher',
        'app_key',
        'app_secret',
        'base_url',
        'auth_base_url',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'refresh_token_expires_at',
        'status',
        'last_synced_at',
        'last_error',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function active(?int $id = null): ?array
    {
        $query = $this->where('status', 'active');

        if ($id !== null) {
            $query->where('id', $id);
        }

        return $query->orderBy('id', 'ASC')->first();
    }
}
