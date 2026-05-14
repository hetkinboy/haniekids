<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'order_id',
        'product_id',
        'sku_id',
        'sku_code',
        'sku_display_name',
        'size_option_id',
        'size_name',
        'combo_option_id',
        'combo_name',
        'combo_quantity',
        'quantity',
        'stock_quantity_deducted',
        'sale_price',
        'cost_price',
        'total_sale',
        'total_cost',
        'allocated_fee',
        'profit',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

