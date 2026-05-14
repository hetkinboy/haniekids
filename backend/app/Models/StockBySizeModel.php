<?php

namespace App\Models;

use CodeIgniter\Model;

class StockBySizeModel extends Model
{
    protected $table = 'stock_by_size';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'product_id',
        'size_option_id',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'avg_cost',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findByProductAndSize(int $productId, int $sizeOptionId): ?array
    {
        return $this->where('product_id', $productId)
            ->where('size_option_id', $sizeOptionId)
            ->first();
    }
}

