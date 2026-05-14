<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\StockBySizeModel;
use App\Models\StockMovementModel;
use App\Models\VariantGroupModel;
use App\Models\VariantOptionModel;
use CodeIgniter\HTTP\ResponseInterface;

class StockController extends BaseController
{
    private ProductModel $products;
    private StockBySizeModel $stock;
    private StockMovementModel $movements;
    private VariantGroupModel $groups;
    private VariantOptionModel $options;

    public function __construct()
    {
        $this->products = new ProductModel();
        $this->stock = new StockBySizeModel();
        $this->movements = new StockMovementModel();
        $this->groups = new VariantGroupModel();
        $this->options = new VariantOptionModel();
    }

    public function productStock(int $productId): ResponseInterface
    {
        if (! $this->products->find($productId)) {
            return api_error('Product not found', [], 404);
        }

        $items = $this->stock
            ->select('stock_by_size.*, products.name AS product_name, variant_options.name AS size_name')
            ->join('products', 'products.id = stock_by_size.product_id')
            ->join('variant_options', 'variant_options.id = stock_by_size.size_option_id')
            ->where('stock_by_size.product_id', $productId)
            ->orderBy('variant_options.sort_order', 'ASC')
            ->orderBy('variant_options.id', 'ASC')
            ->findAll();

        return api_success('Success', [
            'items' => array_map(fn (array $item): array => $this->formatStock($item), $items),
        ]);
    }

    public function movements(): ResponseInterface
    {
        $productId = $this->request->getGet('product_id');
        $sizeOptionId = $this->request->getGet('size_option_id');
        $movementType = trim((string) $this->request->getGet('movement_type'));
        $dateFrom = $this->request->getGet('date_from');
        $dateTo = $this->request->getGet('date_to');
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = min(100, max(1, (int) ($this->request->getGet('pageSize') ?? 20)));

        $builder = $this->movements
            ->select('stock_movements.*, products.name AS product_name, variant_options.name AS size_name')
            ->join('products', 'products.id = stock_movements.product_id')
            ->join('variant_options', 'variant_options.id = stock_movements.size_option_id');

        if ($productId !== null && $productId !== '') {
            $builder = $builder->where('stock_movements.product_id', (int) $productId);
        }

        if ($sizeOptionId !== null && $sizeOptionId !== '') {
            $builder = $builder->where('stock_movements.size_option_id', (int) $sizeOptionId);
        }

        if ($movementType !== '') {
            $builder = $builder->where('stock_movements.movement_type', $movementType);
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $builder = $builder->where('stock_movements.created_at >=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo !== null && $dateTo !== '') {
            $builder = $builder->where('stock_movements.created_at <=', $dateTo . ' 23:59:59');
        }

        $items = $builder->orderBy('stock_movements.id', 'DESC')->paginate($pageSize, 'default', $page);

        return api_success('Success', [
            'items' => array_map(fn (array $item): array => $this->formatMovement($item), $items),
            'pager' => [
                'page'     => $this->movements->pager->getCurrentPage(),
                'pageSize' => $pageSize,
                'total'    => $this->movements->pager->getTotal(),
            ],
        ]);
    }

    public function adjust(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $errors = $this->validateAdjustPayload($payload);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $productId = (int) $payload['product_id'];
        $sizeOptionId = (int) $payload['size_option_id'];
        $quantity = (int) $payload['quantity'];
        $mode = $payload['mode'];
        $note = $payload['note'] ?? null;
        $unitCost = array_key_exists('unit_cost', $payload) ? (float) $payload['unit_cost'] : 0;

        $this->stock->db->transStart();

        $stock = $this->findOrCreateStock($productId, $sizeOptionId, $unitCost);
        $before = (int) $stock['quantity_on_hand'];
        $after = match ($mode) {
            'increase' => $before + $quantity,
            'decrease' => $before - $quantity,
            'set' => $quantity,
        };

        if ($after < 0) {
            $this->stock->db->transRollback();
            return api_error('Stock cannot be negative', [], 422);
        }

        $delta = $after - $before;

        $this->stock->update($stock['id'], [
            'quantity_on_hand'   => $after,
            'quantity_available' => max(0, $after - (int) $stock['quantity_reserved']),
        ]);

        $movementId = $this->movements->insert([
            'product_id'      => $productId,
            'size_option_id'  => $sizeOptionId,
            'movement_type'   => 'adjustment',
            'quantity'        => $delta,
            'quantity_before' => $before,
            'quantity_after'  => $after,
            'unit_cost'       => $unitCost,
            'reference_type'  => 'stock_adjustment',
            'reference_id'    => null,
            'note'            => $note,
        ], true);

        $this->stock->db->transComplete();

        if ($this->stock->db->transStatus() === false) {
            return api_error('Could not adjust stock', [], 500);
        }

        return api_success('Stock adjusted', [
            'stock'    => $this->formatStock($this->stock->find($stock['id'])),
            'movement' => $this->movements->find($movementId),
        ]);
    }

    private function validateAdjustPayload(array $payload): array
    {
        $errors = [];

        if (! $this->products->find((int) ($payload['product_id'] ?? 0))) {
            $errors['product_id'] = 'Product not found.';
        }

        $sizeOptionId = (int) ($payload['size_option_id'] ?? 0);

        if (! $this->isSizeOptionOfProduct((int) ($payload['product_id'] ?? 0), $sizeOptionId)) {
            $errors['size_option_id'] = 'Size option does not belong to product stock dimension.';
        }

        if (! in_array($payload['mode'] ?? null, ['increase', 'decrease', 'set'], true)) {
            $errors['mode'] = 'Mode must be increase, decrease, or set.';
        }

        if ((int) ($payload['quantity'] ?? 0) < 1) {
            $errors['quantity'] = 'Quantity must be greater than 0.';
        }

        if (array_key_exists('unit_cost', $payload) && (float) $payload['unit_cost'] < 0) {
            $errors['unit_cost'] = 'Unit cost must be greater than or equal to 0.';
        }

        return $errors;
    }

    private function isSizeOptionOfProduct(int $productId, int $sizeOptionId): bool
    {
        return $this->options
            ->join('variant_groups', 'variant_groups.id = variant_options.variant_group_id')
            ->where('variant_options.id', $sizeOptionId)
            ->where('variant_groups.product_id', $productId)
            ->where('variant_groups.is_stock_group', 1)
            ->countAllResults() > 0;
    }

    private function findOrCreateStock(int $productId, int $sizeOptionId, float $avgCost = 0): array
    {
        $stock = $this->stock->findByProductAndSize($productId, $sizeOptionId);

        if ($stock) {
            return $stock;
        }

        $id = $this->stock->insert([
            'product_id'          => $productId,
            'size_option_id'      => $sizeOptionId,
            'quantity_on_hand'    => 0,
            'quantity_reserved'   => 0,
            'quantity_available'  => 0,
            'avg_cost'            => $avgCost,
        ], true);

        return $this->stock->find($id);
    }

    private function formatStock(array $item): array
    {
        return [
            'id'                 => (int) $item['id'],
            'product_id'         => (int) $item['product_id'],
            'product_name'       => $item['product_name'] ?? null,
            'size_option_id'     => (int) $item['size_option_id'],
            'size_name'          => $item['size_name'] ?? null,
            'quantity_on_hand'   => (int) $item['quantity_on_hand'],
            'quantity_reserved'  => (int) $item['quantity_reserved'],
            'quantity_available' => (int) $item['quantity_available'],
            'avg_cost'           => (float) $item['avg_cost'],
            'stock_value'        => (int) $item['quantity_on_hand'] * (float) $item['avg_cost'],
        ];
    }

    private function formatMovement(array $item): array
    {
        return [
            'id'                 => (int) $item['id'],
            'product_id'         => (int) $item['product_id'],
            'product_name'       => $item['product_name'] ?? null,
            'size_option_id'     => (int) $item['size_option_id'],
            'size_name'          => $item['size_name'] ?? null,
            'movement_type'      => $item['movement_type'],
            'quantity'           => (int) $item['quantity'],
            'quantity_before'    => (int) $item['quantity_before'],
            'quantity_after'     => (int) $item['quantity_after'],
            'unit_cost'          => (float) $item['unit_cost'],
            'reference_type'     => $item['reference_type'],
            'reference_id'       => $item['reference_id'] === null ? null : (int) $item['reference_id'],
            'order_id'           => $item['order_id'] === null ? null : (int) $item['order_id'],
            'order_item_id'      => $item['order_item_id'] === null ? null : (int) $item['order_item_id'],
            'purchase_import_id' => $item['purchase_import_id'] === null ? null : (int) $item['purchase_import_id'],
            'note'               => $item['note'],
            'created_at'         => $item['created_at'],
        ];
    }
}

