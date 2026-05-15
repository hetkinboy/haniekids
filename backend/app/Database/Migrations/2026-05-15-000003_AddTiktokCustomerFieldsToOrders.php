<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTiktokCustomerFieldsToOrders extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('orders', [
            'customer_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'after'      => 'customer_name',
            ],
            'customer_address' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'customer_phone',
            ],
            'buyer_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'customer_address',
            ],
            'shipping_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'buyer_email',
            ],
            'payment_method_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'shipping_provider',
            ],
            'delivery_option_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'payment_method_name',
            ],
            'tiktok_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'after'      => 'delivery_option_name',
            ],
            'tiktok_raw_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'after' => 'tiktok_status',
            ],
        ]);
    }

    public function down(): void
    {
        foreach (['customer_phone', 'customer_address', 'buyer_email', 'shipping_provider', 'payment_method_name', 'delivery_option_name', 'tiktok_status', 'tiktok_raw_json'] as $field) {
            $this->forge->dropColumn('orders', $field);
        }
    }
}
