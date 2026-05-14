<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\ProductSkuModel;
use App\Models\StockBySizeModel;
use App\Models\StockMovementModel;
use CodeIgniter\HTTP\ResponseInterface;

class OrdersController extends BaseController
{
    private OrderModel $orders;
    private OrderItemModel $items;
    private ProductSkuModel $skus;
    private StockBySizeModel $stock;
    private StockMovementModel $movements;

    public function __construct()
    {
        $this->orders = new OrderModel();
        $this->items = new OrderItemModel();
        $this->skus = new ProductSkuModel();
        $this->stock = new StockBySizeModel();
        $this->movements = new StockMovementModel();
    }

    public function index(): ResponseInterface
    {
        $keyword = trim((string) $this->request->getGet('keyword'));
        $status = trim((string) $this->request->getGet('status'));
        $platform = trim((string) $this->request->getGet('platform'));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = min(100, max(1, (int) ($this->request->getGet('pageSize') ?? 20)));

        $builder = $this->orders;

        if ($keyword !== '') {
            $builder = $builder->groupStart()
                ->like('order_code', $keyword)
                ->orLike('tiktok_order_id', $keyword)
                ->orLike('customer_name', $keyword)
                ->groupEnd();
        }

        if ($status !== '') {
            $builder = $builder->where('status', $status);
        }

        if ($platform !== '') {
            $builder = $builder->where('platform', $platform);
        }

        $orders = $builder->orderBy('id', 'DESC')->paginate($pageSize, 'default', $page);

        return api_success('Success', [
            'items' => array_map(fn (array $order): array => $this->formatOrder($order), $orders),
            'pager' => [
                'page' => $this->orders->pager->getCurrentPage(),
                'pageSize' => $pageSize,
                'total' => $this->orders->pager->getTotal(),
            ],
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $order = $this->orders->find($id);

        if (! $order) {
            return api_error('Order not found', [], 404);
        }

        $data = $this->formatOrder($order);
        $data['items'] = $this->items->where('order_id', $id)->orderBy('id', 'ASC')->findAll();

        return api_success('Success', $data);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $errors = $this->validatePayload($payload);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $orderCode = trim((string) ($payload['order_code'] ?? ''));
        if ($orderCode === '') {
            $orderCode = 'ORD-' . date('Ymd-His');
        }

        $platformFee = (float) ($payload['platform_fee'] ?? 0);
        $transactionFee = (float) ($payload['transaction_fee'] ?? 0);
        $shippingFee = (float) ($payload['shipping_fee'] ?? 0);
        $discountAmount = (float) ($payload['discount_amount'] ?? 0);
        $returnFee = (float) ($payload['return_fee'] ?? 0);
        $shouldDeductStock = ! in_array($payload['status'] ?? 'pending', ['cancelled', 'returned'], true);

        $grossAmount = 0.0;
        $totalCost = 0.0;

        $this->orders->db->transStart();

        $orderId = $this->orders->insert([
            'order_code' => $orderCode,
            'platform' => $payload['platform'] ?? 'tiktok',
            'tiktok_order_id' => $payload['tiktok_order_id'] ?? null,
            'customer_name' => $payload['customer_name'] ?? null,
            'order_date' => $payload['order_date'],
            'status' => $payload['status'] ?? 'pending',
            'gross_amount' => 0,
            'discount_amount' => $discountAmount,
            'platform_fee' => $platformFee,
            'transaction_fee' => $transactionFee,
            'shipping_fee' => $shippingFee,
            'cod_amount' => (float) ($payload['cod_amount'] ?? 0),
            'net_revenue' => 0,
            'total_cost' => 0,
            'total_profit' => 0,
            'stock_deducted' => $shouldDeductStock ? 1 : 0,
            'stock_returned' => 0,
            'return_fee' => $returnFee,
            'note' => $payload['note'] ?? null,
        ], true);

        foreach ($payload['items'] as $line) {
            $sku = $this->skus->find((int) $line['sku_id']);
            $quantity = (int) $line['quantity'];
            $salePrice = array_key_exists('sale_price', $line) ? (float) $line['sale_price'] : (float) $sku['sale_price'];
            $costPrice = array_key_exists('cost_price', $line) ? (float) $line['cost_price'] : (float) $sku['cost_price'];
            $stockQty = $quantity * (int) $sku['combo_quantity'];
            $totalSale = $quantity * $salePrice;
            $lineCost = $quantity * $costPrice;
            $grossAmount += $totalSale;
            $totalCost += $lineCost;

            $itemId = $this->items->insert([
                'order_id' => $orderId,
                'product_id' => (int) $sku['product_id'],
                'sku_id' => (int) $sku['id'],
                'sku_code' => $sku['sku_code'],
                'sku_display_name' => $sku['display_name'],
                'size_option_id' => (int) $sku['size_option_id'],
                'size_name' => $line['size_name'] ?? '',
                'combo_option_id' => (int) $sku['combo_option_id'],
                'combo_name' => $line['combo_name'] ?? '',
                'combo_quantity' => (int) $sku['combo_quantity'],
                'quantity' => $quantity,
                'stock_quantity_deducted' => $shouldDeductStock ? $stockQty : 0,
                'sale_price' => $salePrice,
                'cost_price' => $costPrice,
                'total_sale' => $totalSale,
                'total_cost' => $lineCost,
                'allocated_fee' => 0,
                'profit' => $totalSale - $lineCost,
            ], true);

            if ($shouldDeductStock) {
                $stock = $this->stock->findByProductAndSize((int) $sku['product_id'], (int) $sku['size_option_id']);
                $before = (int) $stock['quantity_on_hand'];
                $after = $before - $stockQty;

                $this->stock->update($stock['id'], [
                    'quantity_on_hand' => $after,
                    'quantity_available' => max(0, $after - (int) $stock['quantity_reserved']),
                ]);

                $this->movements->insert([
                    'product_id' => (int) $sku['product_id'],
                    'size_option_id' => (int) $sku['size_option_id'],
                    'movement_type' => 'sale',
                    'quantity' => -$stockQty,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'unit_cost' => $costPrice,
                    'reference_type' => 'order',
                    'reference_id' => $orderId,
                    'order_id' => $orderId,
                    'order_item_id' => $itemId,
                    'note' => 'Deduct stock by size from order ' . $orderCode,
                ]);
            }
        }

        $totalFee = $platformFee + $transactionFee + $shippingFee + $returnFee;
        $netRevenue = $grossAmount - $discountAmount - $totalFee;
        $totalProfit = $netRevenue - $totalCost;

        $this->orders->update($orderId, [
            'gross_amount' => $grossAmount,
            'net_revenue' => $netRevenue,
            'total_cost' => $totalCost,
            'total_profit' => $totalProfit,
        ]);

        $this->orders->db->transComplete();

        if ($this->orders->db->transStatus() === false) {
            return api_error('Could not create order', [], 500);
        }

        return $this->show($orderId);
    }

    public function updateStatus(int $id): ResponseInterface
    {
        $order = $this->orders->find($id);

        if (! $order) {
            return api_error('Order not found', [], 404);
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $status = $payload['status'] ?? null;

        if (! in_array($status, ['pending', 'confirmed', 'shipped', 'completed', 'cancelled', 'returned'], true)) {
            return api_error('Validation failed', ['status' => 'Invalid order status.'], 422);
        }

        $this->orders->db->transStart();

        if (in_array($status, ['cancelled', 'returned'], true) && (int) $order['stock_deducted'] === 1 && (int) $order['stock_returned'] === 0) {
            $this->returnStock($order);
            $this->orders->update($id, ['stock_returned' => 1]);
        }

        $this->orders->update($id, ['status' => $status]);
        $this->orders->db->transComplete();

        if ($this->orders->db->transStatus() === false) {
            return api_error('Could not update order status', [], 500);
        }

        return $this->show($id);
    }

    private function validatePayload(array $payload): array
    {
        $errors = [];

        if (empty($payload['order_date'])) {
            $errors['order_date'] = 'Order date is required.';
        }

        if (! isset($payload['items']) || ! is_array($payload['items']) || $payload['items'] === []) {
            $errors['items'] = 'Items must not be empty.';
            return $errors;
        }

        foreach ($payload['items'] as $index => $line) {
            $prefix = 'items.' . $index;
            $sku = $this->skus->find((int) ($line['sku_id'] ?? 0));

            if (! $sku) {
                $errors[$prefix . '.sku_id'] = 'SKU not found.';
                continue;
            }

            if ((int) ($line['quantity'] ?? 0) < 1) {
                $errors[$prefix . '.quantity'] = 'Quantity must be greater than 0.';
            }

            $stock = $this->stock->findByProductAndSize((int) $sku['product_id'], (int) $sku['size_option_id']);
            $requiredStock = (int) ($line['quantity'] ?? 0) * (int) $sku['combo_quantity'];

            if (! $stock || (int) $stock['quantity_available'] < $requiredStock) {
                $errors[$prefix . '.stock'] = 'Not enough stock for this size.';
            }
        }

        return $errors;
    }

    private function returnStock(array $order): void
    {
        $items = $this->items->where('order_id', (int) $order['id'])->findAll();

        foreach ($items as $item) {
            $stockQty = (int) $item['stock_quantity_deducted'];
            if ($stockQty <= 0) {
                continue;
            }

            $stock = $this->stock->findByProductAndSize((int) $item['product_id'], (int) $item['size_option_id']);
            $before = (int) $stock['quantity_on_hand'];
            $after = $before + $stockQty;

            $this->stock->update($stock['id'], [
                'quantity_on_hand' => $after,
                'quantity_available' => max(0, $after - (int) $stock['quantity_reserved']),
            ]);

            $this->movements->insert([
                'product_id' => (int) $item['product_id'],
                'size_option_id' => (int) $item['size_option_id'],
                'movement_type' => 'return',
                'quantity' => $stockQty,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => (float) $item['cost_price'],
                'reference_type' => 'order_return',
                'reference_id' => (int) $order['id'],
                'order_id' => (int) $order['id'],
                'order_item_id' => (int) $item['id'],
                'note' => 'Return stock from order ' . $order['order_code'],
            ]);
        }
    }

    private function formatOrder(array $order): array
    {
        foreach (['id', 'stock_deducted', 'stock_returned'] as $field) {
            $order[$field] = (int) $order[$field];
        }

        foreach (['gross_amount', 'discount_amount', 'platform_fee', 'transaction_fee', 'shipping_fee', 'cod_amount', 'net_revenue', 'total_cost', 'total_profit', 'return_fee'] as $field) {
            $order[$field] = (float) $order[$field];
        }

        return $order;
    }
}
