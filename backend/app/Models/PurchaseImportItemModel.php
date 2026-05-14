<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseImportItemModel extends Model
{
    protected $table = 'purchase_import_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'purchase_import_id',
        'product_id',
        'size_option_id',
        'quantity',
        'unit_cost',
        'total_cost',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

