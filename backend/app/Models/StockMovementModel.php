<?php

namespace App\Models;

use CodeIgniter\Model;

class StockMovementModel extends Model
{
    protected $table = 'stock_movements';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'product_id',
        'size_option_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'reference_type',
        'reference_id',
        'order_id',
        'order_item_id',
        'purchase_import_id',
        'note',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

