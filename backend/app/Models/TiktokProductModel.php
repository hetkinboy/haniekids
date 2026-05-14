<?php

namespace App\Models;

use CodeIgniter\Model;

class TiktokProductModel extends Model
{
    protected $table = 'tiktok_products';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'product_id',
        'tiktok_product_id',
        'name',
        'shop_name',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}
