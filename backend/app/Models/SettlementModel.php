<?php

namespace App\Models;

use CodeIgniter\Model;

class SettlementModel extends Model
{
    protected $table = 'settlements';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'settlement_code',
        'period_from',
        'period_to',
        'platform',
        'total_gross',
        'total_fee',
        'total_settled',
        'total_difference',
        'status',
        'note',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}
