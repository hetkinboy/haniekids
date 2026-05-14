<?php

namespace App\Models;

use CodeIgniter\Model;

class VariantGroupModel extends Model
{
    protected $table = 'variant_groups';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'product_id',
        'name',
        'type',
        'is_stock_group',
        'sort_order',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}

