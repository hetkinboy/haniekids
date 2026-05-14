<?php

namespace App\Models;

use CodeIgniter\Model;

class SettlementItemModel extends Model
{
    protected $table = 'settlement_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'settlement_id',
        'order_id',
        'order_code',
        'gross_amount',
        'platform_fee',
        'shipping_fee',
        'settled_amount',
        'expected_amount',
        'difference_amount',
        'reason',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
