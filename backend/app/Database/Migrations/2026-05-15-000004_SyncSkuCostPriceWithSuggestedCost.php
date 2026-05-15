<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SyncSkuCostPriceWithSuggestedCost extends Migration
{
    public function up(): void
    {
        $this->db->table('product_skus')->set('cost_price', 'suggested_cost', false)->update();
    }

    public function down(): void
    {
        // No safe rollback: previous manually entered cost_price values are intentionally replaced.
    }
}
