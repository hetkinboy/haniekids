<?php

namespace App\Models;

use CodeIgniter\Model;

class VariantOptionModel extends Model
{
    protected $table = 'variant_options';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'variant_group_id',
        'name',
        'option_code',
        'base_cost',
        'combo_quantity',
        'default_sellable',
        'sort_order',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}

