<?php

namespace App\Models;

use CodeIgniter\Model;

class TiktokSkuModel extends Model
{
    protected $table = 'tiktok_skus';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'tiktok_product_id',
        'product_sku_id',
        'tiktok_sku_id',
        'seller_sku',
        'name',
        'tiktok_price',
        'tiktok_inventory_quantity',
        'tiktok_warehouse_id',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}
