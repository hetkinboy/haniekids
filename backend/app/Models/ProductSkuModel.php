<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductSkuModel extends Model
{
    protected $table = 'product_skus';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'product_id',
        'sku_code',
        'display_name',
        'size_option_id',
        'combo_option_id',
        'combo_quantity',
        'suggested_cost',
        'cost_price',
        'sale_price',
        'tiktok_sku_id',
        'is_sellable',
        'is_active',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function forProduct(int $productId): array
    {
        return $this->where('product_id', $productId)
            ->orderBy('size_option_id', 'ASC')
            ->orderBy('combo_quantity', 'ASC')
            ->findAll();
    }
}

