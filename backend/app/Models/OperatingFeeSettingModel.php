<?php

namespace App\Models;

use CodeIgniter\Model;

class OperatingFeeSettingModel extends Model
{
    protected $table = 'operating_fee_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'fee_key',
        'label',
        'value_type',
        'rate',
        'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
