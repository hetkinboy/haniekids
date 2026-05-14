<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTiktokSettlementTables extends Migration
{
    public function up(): void
    {
        $this->createTiktokProducts();
        $this->createTiktokSkus();
        $this->createSettlements();
        $this->createSettlementItems();
    }

    public function down(): void
    {
        $this->forge->dropTable('settlement_items', true);
        $this->forge->dropTable('settlements', true);
        $this->forge->dropTable('tiktok_skus', true);
        $this->forge->dropTable('tiktok_products', true);
    }

    private function createTiktokProducts(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tiktok_product_id' => ['type' => 'VARCHAR', 'constraint' => 120],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'shop_name'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('tiktok_product_id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('tiktok_products');
    }

    private function createTiktokSkus(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tiktok_product_id' => ['type' => 'INT', 'unsigned' => true],
            'product_sku_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tiktok_sku_id'     => ['type' => 'VARCHAR', 'constraint' => 120],
            'seller_sku'        => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('tiktok_sku_id');
        $this->forge->addKey('tiktok_product_id');
        $this->forge->addKey('product_sku_id');
        $this->forge->addForeignKey('tiktok_product_id', 'tiktok_products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_sku_id', 'product_skus', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('tiktok_skus');
    }

    private function createSettlements(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'settlement_code'   => ['type' => 'VARCHAR', 'constraint' => 120],
            'period_from'       => ['type' => 'DATE'],
            'period_to'         => ['type' => 'DATE'],
            'platform'          => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'tiktok'],
            'total_gross'       => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total_fee'         => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total_settled'     => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total_difference'  => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'],
            'note'              => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('settlement_code');
        $this->forge->addKey(['period_from', 'period_to']);
        $this->forge->createTable('settlements');
    }

    private function createSettlementItems(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'settlement_id'     => ['type' => 'INT', 'unsigned' => true],
            'order_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'order_code'        => ['type' => 'VARCHAR', 'constraint' => 120],
            'gross_amount'      => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'platform_fee'      => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'shipping_fee'      => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'settled_amount'    => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'expected_amount'   => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'difference_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'reason'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('settlement_id');
        $this->forge->addKey('order_id');
        $this->forge->addKey('order_code');
        $this->forge->addForeignKey('settlement_id', 'settlements', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('settlement_items');
    }
}
