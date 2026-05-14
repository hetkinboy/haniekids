<?php

namespace App\Models;

use CodeIgniter\Model;

class OperatingCostModel extends Model
{
    protected $table = 'operating_costs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'cost_date',
        'cost_type',
        'amount',
        'allocation_type',
        'product_id',
        'order_id',
        'note',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}

