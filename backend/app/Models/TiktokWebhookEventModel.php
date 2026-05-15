<?php

namespace App\Models;

use CodeIgniter\Model;

class TiktokWebhookEventModel extends Model
{
    protected $table = 'tiktok_webhook_events';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'connection_id',
        'shop_id',
        'event_type',
        'order_id',
        'order_status',
        'payload_json',
        'process_status',
        'error_message',
        'received_at',
        'processed_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
