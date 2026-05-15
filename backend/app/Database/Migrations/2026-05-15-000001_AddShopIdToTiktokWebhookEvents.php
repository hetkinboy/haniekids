<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddShopIdToTiktokWebhookEvents extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('tiktok_webhook_events', [
            'shop_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'connection_id',
            ],
        ]);
        $this->db->query('CREATE INDEX shop_id ON tiktok_webhook_events (shop_id)');
    }

    public function down(): void
    {
        $this->forge->dropColumn('tiktok_webhook_events', 'shop_id');
    }
}
