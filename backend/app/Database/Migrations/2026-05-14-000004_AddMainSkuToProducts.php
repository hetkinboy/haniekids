<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMainSkuToProducts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('products', [
            'main_sku' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'product_code',
            ],
        ]);
        $this->db->query('CREATE INDEX products_main_sku_index ON products (main_sku)');
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX products_main_sku_index ON products');
        $this->forge->dropColumn('products', 'main_sku');
    }
}
