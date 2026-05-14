<?php

namespace App\Models;

use CodeIgniter\Model;

class PurchaseImportModel extends Model
{
    protected $table = 'purchase_imports';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'import_code',
        'supplier_name',
        'import_date',
        'total_quantity',
        'total_amount',
        'note',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

