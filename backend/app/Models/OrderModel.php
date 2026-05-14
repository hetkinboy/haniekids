<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'order_code',
        'platform',
        'tiktok_order_id',
        'customer_name',
        'order_date',
        'status',
        'gross_amount',
        'discount_amount',
        'platform_fee',
        'transaction_fee',
        'shipping_fee',
        'cod_amount',
        'net_revenue',
        'total_cost',
        'total_profit',
        'stock_deducted',
        'stock_returned',
        'return_fee',
        'note',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

