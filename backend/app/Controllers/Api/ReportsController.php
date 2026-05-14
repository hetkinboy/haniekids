<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\OperatingCostModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\StockBySizeModel;
use CodeIgniter\HTTP\ResponseInterface;

class ReportsController extends BaseController
{
    private OrderModel $orders;
    private OrderItemModel $orderItems;
    private ProductModel $products;
    private ProductSkuModel $skus;
    private StockBySizeModel $stock;
    private OperatingCostModel $costs;

    public function __construct()
    {
        $this->orders = new OrderModel();
        $this->orderItems = new OrderItemModel();
        $this->products = new ProductModel();
        $this->skus = new ProductSkuModel();
        $this->stock = new StockBySizeModel();
        $this->costs = new OperatingCostModel();
    }

    public function overview(): ResponseInterface
    {
        [$dateFrom, $dateTo] = $this->dateRange();

        $orderSummary = $this->orders
            ->select('COUNT(*) AS order_count, COALESCE(SUM(gross_amount),0) AS gross_amount, COALESCE(SUM(net_revenue),0) AS net_revenue, COALESCE(SUM(total_cost),0) AS total_cost, COALESCE(SUM(total_profit),0) AS total_profit')
            ->where('order_date >=', $dateFrom . ' 00:00:00')
            ->where('order_date <=', $dateTo . ' 23:59:59')
            ->first();

        $operatingCost = $this->costs
            ->select('COALESCE(SUM(amount),0) AS amount')
            ->where('cost_date >=', $dateFrom)
            ->where('cost_date <=', $dateTo)
            ->first();

        $stockSummary = $this->stock
            ->select('COALESCE(SUM(quantity_on_hand),0) AS quantity_on_hand, COALESCE(SUM(quantity_available),0) AS quantity_available, COALESCE(SUM(quantity_on_hand * avg_cost),0) AS stock_value')
            ->first();

        return api_success('Success', [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'orders' => [
                'count' => (int) ($orderSummary['order_count'] ?? 0),
                'gross_amount' => (float) ($orderSummary['gross_amount'] ?? 0),
                'net_revenue' => (float) ($orderSummary['net_revenue'] ?? 0),
                'total_cost' => (float) ($orderSummary['total_cost'] ?? 0),
                'gross_profit' => (float) ($orderSummary['total_profit'] ?? 0),
            ],
            'operating_cost' => (float) ($operatingCost['amount'] ?? 0),
            'net_profit_after_operating_cost' => (float) ($orderSummary['total_profit'] ?? 0) - (float) ($operatingCost['amount'] ?? 0),
            'stock' => [
                'quantity_on_hand' => (int) ($stockSummary['quantity_on_hand'] ?? 0),
                'quantity_available' => (int) ($stockSummary['quantity_available'] ?? 0),
                'stock_value' => (float) ($stockSummary['stock_value'] ?? 0),
            ],
        ]);
    }

    public function byProduct(): ResponseInterface
    {
        [$dateFrom, $dateTo] = $this->dateRange();

        $rows = $this->orderItems
            ->select('order_items.product_id, products.product_code, products.name AS product_name, SUM(order_items.quantity) AS quantity_sold, SUM(order_items.total_sale) AS total_sale, SUM(order_items.total_cost) AS total_cost, SUM(order_items.profit) AS profit')
            ->join('orders', 'orders.id = order_items.order_id')
            ->join('products', 'products.id = order_items.product_id')
            ->where('orders.order_date >=', $dateFrom . ' 00:00:00')
            ->where('orders.order_date <=', $dateTo . ' 23:59:59')
            ->groupBy('order_items.product_id, products.product_code, products.name')
            ->orderBy('profit', 'DESC')
            ->findAll();

        return api_success('Success', [
            'items' => array_map(fn (array $row): array => [
                'product_id' => (int) $row['product_id'],
                'product_code' => $row['product_code'],
                'product_name' => $row['product_name'],
                'quantity_sold' => (int) $row['quantity_sold'],
                'total_sale' => (float) $row['total_sale'],
                'total_cost' => (float) $row['total_cost'],
                'profit' => (float) $row['profit'],
            ], $rows),
        ]);
    }

    public function bySku(): ResponseInterface
    {
        [$dateFrom, $dateTo] = $this->dateRange();

        $rows = $this->orderItems
            ->select('sku_id, sku_code, sku_display_name, size_name, combo_name, combo_quantity, SUM(quantity) AS quantity_sold, SUM(total_sale) AS total_sale, SUM(total_cost) AS total_cost, SUM(profit) AS profit')
            ->join('orders', 'orders.id = order_items.order_id')
            ->where('orders.order_date >=', $dateFrom . ' 00:00:00')
            ->where('orders.order_date <=', $dateTo . ' 23:59:59')
            ->groupBy('sku_id, sku_code, sku_display_name, size_name, combo_name, combo_quantity')
            ->orderBy('profit', 'DESC')
            ->findAll();

        return api_success('Success', [
            'items' => array_map(fn (array $row): array => [
                'sku_id' => (int) $row['sku_id'],
                'sku_code' => $row['sku_code'],
                'sku_display_name' => $row['sku_display_name'],
                'size_name' => $row['size_name'],
                'combo_name' => $row['combo_name'],
                'combo_quantity' => (int) $row['combo_quantity'],
                'quantity_sold' => (int) $row['quantity_sold'],
                'total_sale' => (float) $row['total_sale'],
                'total_cost' => (float) $row['total_cost'],
                'profit' => (float) $row['profit'],
            ], $rows),
        ]);
    }

    public function stock(): ResponseInterface
    {
        $rows = $this->stock
            ->select('stock_by_size.*, products.product_code, products.name AS product_name, variant_options.name AS size_name')
            ->join('products', 'products.id = stock_by_size.product_id')
            ->join('variant_options', 'variant_options.id = stock_by_size.size_option_id')
            ->orderBy('quantity_available', 'ASC')
            ->findAll();

        return api_success('Success', [
            'items' => array_map(fn (array $row): array => [
                'product_id' => (int) $row['product_id'],
                'product_code' => $row['product_code'],
                'product_name' => $row['product_name'],
                'size_option_id' => (int) $row['size_option_id'],
                'size_name' => $row['size_name'],
                'quantity_on_hand' => (int) $row['quantity_on_hand'],
                'quantity_reserved' => (int) $row['quantity_reserved'],
                'quantity_available' => (int) $row['quantity_available'],
                'avg_cost' => (float) $row['avg_cost'],
                'stock_value' => (int) $row['quantity_on_hand'] * (float) $row['avg_cost'],
            ], $rows),
        ]);
    }

    private function dateRange(): array
    {
        $dateFrom = $this->request->getGet('date_from') ?: date('Y-m-01');
        $dateTo = $this->request->getGet('date_to') ?: date('Y-m-d');

        return [$dateFrom, $dateTo];
    }
}
