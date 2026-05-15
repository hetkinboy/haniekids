<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOperatingFeeSettings extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'fee_key'    => ['type' => 'VARCHAR', 'constraint' => 80],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 160],
            'value_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'percent'],
            'rate'       => ['type' => 'DECIMAL', 'constraint' => '10,4', 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('fee_key');
        $this->forge->addKey('status');
        $this->forge->createTable('operating_fee_settings');

        $now = date('Y-m-d H:i:s');
        $this->db->table('operating_fee_settings')->insertBatch([
            ['fee_key' => 'platform_commission', 'label' => 'Phí hoa hồng nền tảng', 'value_type' => 'percent', 'rate' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['fee_key' => 'transaction_fee', 'label' => 'Phí giao dịch', 'value_type' => 'percent', 'rate' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['fee_key' => 'shipping_program_fee', 'label' => 'Phí tham gia ship', 'value_type' => 'percent', 'rate' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['fee_key' => 'personal_income_tax', 'label' => 'Thuế TNCN', 'value_type' => 'percent', 'rate' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['fee_key' => 'order_processing_fee', 'label' => 'Phí xử lý đơn hàng', 'value_type' => 'fixed', 'rate' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
            ['fee_key' => 'other_fee', 'label' => 'Phí khác', 'value_type' => 'fixed', 'rate' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('operating_fee_settings', true);
    }
}
