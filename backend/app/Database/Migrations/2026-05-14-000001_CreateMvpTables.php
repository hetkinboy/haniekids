<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMvpTables extends Migration
{
    public function up(): void
    {
        $this->createProducts();
        $this->createVariantGroups();
        $this->createVariantOptions();
        $this->createProductSkus();
        $this->createStockBySize();
        $this->createPurchaseImports();
        $this->createPurchaseImportItems();
        $this->createOrders();
        $this->createOrderItems();
        $this->createStockMovements();
        $this->createOperatingCosts();
    }

    public function down(): void
    {
        $this->forge->dropTable('operating_costs', true);
        $this->forge->dropTable('stock_movements', true);
        $this->forge->dropTable('order_items', true);
        $this->forge->dropTable('orders', true);
        $this->forge->dropTable('purchase_import_items', true);
        $this->forge->dropTable('purchase_imports', true);
        $this->forge->dropTable('stock_by_size', true);
        $this->forge->dropTable('product_skus', true);
        $this->forge->dropTable('variant_options', true);
        $this->forge->dropTable('variant_groups', true);
        $this->forge->dropTable('products', true);
    }

    private function createProducts(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_code' => ['type' => 'VARCHAR', 'constraint' => 80],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'category'     => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'description'  => ['type' => 'TEXT', 'null' => true],
            'image_url'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('product_code');
        $this->forge->addKey('status');
        $this->forge->createTable('products');
    }

    private function createVariantGroups(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'     => ['type' => 'INT', 'unsigned' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 120],
            'type'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'is_stock_group' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'     => ['type' => 'INT', 'default' => 0],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addKey(['product_id', 'type']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('variant_groups');
    }

    private function createVariantOptions(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'variant_group_id' => ['type' => 'INT', 'unsigned' => true],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 120],
            'option_code'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'base_cost'        => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'combo_quantity'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'default_sellable' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order'       => ['type' => 'INT', 'default' => 0],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('variant_group_id');
        $this->forge->addForeignKey('variant_group_id', 'variant_groups', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('variant_options');
    }

    private function createProductSkus(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'      => ['type' => 'INT', 'unsigned' => true],
            'sku_code'        => ['type' => 'VARCHAR', 'constraint' => 120],
            'display_name'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'size_option_id'  => ['type' => 'INT', 'unsigned' => true],
            'combo_option_id' => ['type' => 'INT', 'unsigned' => true],
            'combo_quantity'  => ['type' => 'INT', 'unsigned' => true],
            'suggested_cost'  => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'cost_price'      => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'sale_price'      => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'tiktok_sku_id'   => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'is_sellable'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addKey('size_option_id');
        $this->forge->addKey('combo_option_id');
        $this->forge->addUniqueKey('sku_code');
        $this->forge->addUniqueKey(['product_id', 'size_option_id', 'combo_option_id'], 'product_sku_variant_unique');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('size_option_id', 'variant_options', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('combo_option_id', 'variant_options', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('product_skus');
    }

    private function createStockBySize(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'         => ['type' => 'INT', 'unsigned' => true],
            'size_option_id'     => ['type' => 'INT', 'unsigned' => true],
            'quantity_on_hand'   => ['type' => 'INT', 'default' => 0],
            'quantity_reserved'  => ['type' => 'INT', 'default' => 0],
            'quantity_available' => ['type' => 'INT', 'default' => 0],
            'avg_cost'           => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addKey('size_option_id');
        $this->forge->addUniqueKey(['product_id', 'size_option_id'], 'stock_by_size_unique');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('size_option_id', 'variant_options', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('stock_by_size');
    }

    private function createPurchaseImports(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'import_code'    => ['type' => 'VARCHAR', 'constraint' => 80],
            'supplier_name'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'import_date'    => ['type' => 'DATE'],
            'total_quantity' => ['type' => 'INT', 'default' => 0],
            'total_amount'   => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'note'           => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('import_code');
        $this->forge->addKey('import_date');
        $this->forge->createTable('purchase_imports');
    }

    private function createPurchaseImportItems(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'purchase_import_id' => ['type' => 'INT', 'unsigned' => true],
            'product_id'         => ['type' => 'INT', 'unsigned' => true],
            'size_option_id'     => ['type' => 'INT', 'unsigned' => true],
            'quantity'           => ['type' => 'INT', 'unsigned' => true],
            'unit_cost'          => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total_cost'         => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('purchase_import_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey('size_option_id');
        $this->forge->addForeignKey('purchase_import_id', 'purchase_imports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('size_option_id', 'variant_options', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('purchase_import_items');
    }

    private function createOrders(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'order_code'         => ['type' => 'VARCHAR', 'constraint' => 80],
            'platform'           => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'tiktok'],
            'tiktok_order_id'    => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'customer_name'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'order_date'         => ['type' => 'DATETIME'],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'gross_amount'       => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'discount_amount'    => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'platform_fee'       => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'transaction_fee'    => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'shipping_fee'       => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'cod_amount'         => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'net_revenue'        => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total_cost'         => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total_profit'       => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'stock_deducted'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'stock_returned'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'return_fee'         => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'note'               => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('order_code');
        $this->forge->addKey('tiktok_order_id');
        $this->forge->addKey('order_date');
        $this->forge->addKey('status');
        $this->forge->createTable('orders');
    }

    private function createOrderItems(): void
    {
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'                => ['type' => 'INT', 'unsigned' => true],
            'product_id'              => ['type' => 'INT', 'unsigned' => true],
            'sku_id'                  => ['type' => 'INT', 'unsigned' => true],
            'sku_code'                => ['type' => 'VARCHAR', 'constraint' => 120],
            'sku_display_name'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'size_option_id'          => ['type' => 'INT', 'unsigned' => true],
            'size_name'               => ['type' => 'VARCHAR', 'constraint' => 120],
            'combo_option_id'         => ['type' => 'INT', 'unsigned' => true],
            'combo_name'              => ['type' => 'VARCHAR', 'constraint' => 120],
            'combo_quantity'          => ['type' => 'INT', 'unsigned' => true],
            'quantity'                => ['type' => 'INT', 'unsigned' => true],
            'stock_quantity_deducted' => ['type' => 'INT', 'unsigned' => true],
            'sale_price'              => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'cost_price'              => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total_sale'              => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'total_cost'              => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'allocated_fee'           => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'profit'                  => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey('sku_id');
        $this->forge->addKey('size_option_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('sku_id', 'product_skus', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('size_option_id', 'variant_options', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('combo_option_id', 'variant_options', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('order_items');
    }

    private function createStockMovements(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'         => ['type' => 'INT', 'unsigned' => true],
            'size_option_id'     => ['type' => 'INT', 'unsigned' => true],
            'movement_type'      => ['type' => 'VARCHAR', 'constraint' => 30],
            'quantity'           => ['type' => 'INT'],
            'quantity_before'    => ['type' => 'INT', 'default' => 0],
            'quantity_after'     => ['type' => 'INT', 'default' => 0],
            'unit_cost'          => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'reference_type'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'reference_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'order_id'           => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'order_item_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'purchase_import_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'note'               => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addKey('size_option_id');
        $this->forge->addKey('movement_type');
        $this->forge->addKey(['reference_type', 'reference_id']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('size_option_id', 'variant_options', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('order_item_id', 'order_items', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('purchase_import_id', 'purchase_imports', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('stock_movements');
    }

    private function createOperatingCosts(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'cost_date'       => ['type' => 'DATE'],
            'cost_type'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'amount'          => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'allocation_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'manual'],
            'product_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'order_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'note'            => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('cost_date');
        $this->forge->addKey('cost_type');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('operating_costs');
    }
}

