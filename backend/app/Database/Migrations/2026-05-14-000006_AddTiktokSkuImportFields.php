<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTiktokSkuImportFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('tiktok_skus', [
            'tiktok_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => 0,
                'after'      => 'name',
            ],
            'tiktok_inventory_quantity' => [
                'type'       => 'INT',
                'default'    => 0,
                'after'      => 'tiktok_price',
            ],
            'tiktok_warehouse_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'tiktok_inventory_quantity',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('tiktok_skus', ['tiktok_price', 'tiktok_inventory_quantity', 'tiktok_warehouse_id']);
    }
}
