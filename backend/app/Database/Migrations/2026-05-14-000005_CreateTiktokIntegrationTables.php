<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTiktokIntegrationTables extends Migration
{
    public function up(): void
    {
        $this->createShopConnections();
        $this->createWebhookEvents();
    }

    public function down(): void
    {
        $this->forge->dropTable('tiktok_webhook_events', true);
        $this->forge->dropTable('tiktok_shop_connections', true);
    }

    private function createShopConnections(): void
    {
        $this->forge->addField([
            'id'                       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'shop_name'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'shop_id'                  => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'shop_cipher'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'app_key'                  => ['type' => 'VARCHAR', 'constraint' => 120],
            'app_secret'               => ['type' => 'VARCHAR', 'constraint' => 255],
            'base_url'                 => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'https://open-api.tiktokglobalshop.com'],
            'auth_base_url'            => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'https://auth.tiktok-shops.com'],
            'access_token'             => ['type' => 'TEXT', 'null' => true],
            'refresh_token'            => ['type' => 'TEXT', 'null' => true],
            'access_token_expires_at'  => ['type' => 'DATETIME', 'null' => true],
            'refresh_token_expires_at' => ['type' => 'DATETIME', 'null' => true],
            'status'                   => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'last_synced_at'           => ['type' => 'DATETIME', 'null' => true],
            'last_error'               => ['type' => 'TEXT', 'null' => true],
            'created_at'               => ['type' => 'DATETIME', 'null' => true],
            'updated_at'               => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'               => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('shop_id');
        $this->forge->addKey('shop_cipher');
        $this->forge->createTable('tiktok_shop_connections');
    }

    private function createWebhookEvents(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'connection_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'event_type'     => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'order_id'       => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'order_status'   => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'payload_json'   => ['type' => 'LONGTEXT'],
            'process_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'received'],
            'error_message'  => ['type' => 'TEXT', 'null' => true],
            'received_at'    => ['type' => 'DATETIME'],
            'processed_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('connection_id');
        $this->forge->addKey('order_id');
        $this->forge->addKey('process_status');
        $this->forge->addForeignKey('connection_id', 'tiktok_shop_connections', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('tiktok_webhook_events');
    }
}
