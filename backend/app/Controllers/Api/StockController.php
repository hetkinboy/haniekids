<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\StockBySizeModel;
use App\Models\StockMovementModel;
use App\Models\VariantGroupModel;
use App\Models\VariantOptionModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Throwable;

class StockController extends BaseController
{
    private ProductModel $products;
    private ProductSkuModel $skus;
    private StockBySizeModel $stock;
    private StockMovementModel $movements;
    private VariantGroupModel $groups;
    private VariantOptionModel $options;

    public function __construct()
    {
        $this->products = new ProductModel();
        $this->skus = new ProductSkuModel();
        $this->stock = new StockBySizeModel();
        $this->movements = new StockMovementModel();
        $this->groups = new VariantGroupModel();
        $this->options = new VariantOptionModel();
    }

    public function productStock(int $productId): ResponseInterface
    {
        $product = $this->products->find($productId);
        if (! $product) {
            return api_error('Product not found', [], 404);
        }

        $sizeGroup = $this->groups
            ->where('product_id', $productId)
            ->where('is_stock_group', 1)
            ->where('status', 'active')
            ->first();

        if (! $sizeGroup) {
            return api_success('Success', ['items' => []]);
        }

        $sizes = $this->options
            ->where('variant_group_id', (int) $sizeGroup['id'])
            ->where('status', 'active')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
        $existingRows = $this->stock->where('product_id', $productId)->findAll();
        $stockBySize = [];

        foreach ($existingRows as $row) {
            $stockBySize[(int) $row['size_option_id']] = $row;
        }

        $items = [];
        foreach ($sizes as $size) {
            $row = $stockBySize[(int) $size['id']] ?? [
                'id'                 => 0,
                'product_id'         => $productId,
                'product_name'       => $product['name'],
                'size_option_id'     => (int) $size['id'],
                'size_name'          => $size['name'],
                'quantity_on_hand'   => 0,
                'quantity_reserved'  => 0,
                'quantity_available' => 0,
                'avg_cost'           => 0,
            ];
            $row['product_name'] = $product['name'];
            $row['size_name'] = $size['name'];
            $items[] = $row;
        }

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
            ->join('variant_options', 'variant_options.id = stock_movements.size_option_id')
            ->where('products.deleted_at', null);

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

    public function importLegacyOneBoStock(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $url = trim((string) ($payload['url'] ?? 'https://shopapi.totdep.com/api/products/getProduct'));
        $dryRun = filter_var($payload['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return api_error('Validation failed', ['url' => 'URL không hợp lệ.'], 422);
        }

        try {
            $http = Services::curlrequest([
                'timeout' => 60,
                'http_errors' => false,
            ]);
            $response = $http->get($url, ['headers' => ['accept' => 'application/json']]);
            $json = json_decode($response->getBody(), true);
        } catch (Throwable $throwable) {
            return api_error($throwable->getMessage(), [], 500);
        }

        if (! is_array($json)) {
            return api_error('URL did not return valid JSON', [], 422);
        }

        $legacyProducts = is_array($json['data'] ?? null) ? $json['data'] : [];
        $summary = [
            'dry_run' => $dryRun,
            'products_seen' => count($legacyProducts),
            'products_matched' => 0,
            'rows_seen' => 0,
            'rows_updated' => 0,
            'rows_skipped' => 0,
            'items' => [],
        ];

        if (! $dryRun) {
            $this->stock->db->transStart();
        }

        foreach ($legacyProducts as $legacyProduct) {
            if (! is_array($legacyProduct)) {
                continue;
            }

            $productCode = trim((string) ($legacyProduct['sku'] ?? ''));
            $product = $productCode === '' ? null : $this->products->where('product_code', $productCode)->first();
            $oneBoOptions = is_array($legacyProduct['options']['1bo'] ?? null) ? $legacyProduct['options']['1bo'] : [];

            if (! $product) {
                if ($oneBoOptions !== []) {
                    $summary['rows_skipped'] += count($oneBoOptions);
                }
                $summary['items'][] = [
                    'product_code' => $productCode,
                    'status' => 'skipped',
                    'reason' => 'product_not_found',
                ];
                continue;
            }

            $summary['products_matched']++;

            foreach ($oneBoOptions as $legacyOption) {
                if (! is_array($legacyOption)) {
                    continue;
                }

                $summary['rows_seen']++;
                $legacySku = trim((string) ($legacyOption['sku'] ?? ''));
                $legacySizeName = trim((string) ($legacyOption['name'] ?? ''));
                $quantity = max(0, (int) ($legacyOption['quantity'] ?? 0));
                $unitCost = max(0, (float) ($legacyOption['cost'] ?? 0));
                $sizeOption = $this->findLegacySizeOption((int) $product['id'], $legacySku, $legacySizeName);

                if (! $sizeOption) {
                    $summary['rows_skipped']++;
                    $summary['items'][] = [
                        'product_code' => $productCode,
                        'legacy_sku' => $legacySku,
                        'size_name' => $legacySizeName,
                        'quantity' => $quantity,
                        'status' => 'skipped',
                        'reason' => 'size_not_found',
                    ];
                    continue;
                }

                $stock = $this->findOrCreateStock((int) $product['id'], (int) $sizeOption['id'], $unitCost);
                $before = (int) $stock['quantity_on_hand'];
                $after = $quantity;
                $delta = $after - $before;

                if (! $dryRun) {
                    $this->stock->update((int) $stock['id'], [
                        'quantity_on_hand' => $after,
                        'quantity_available' => max(0, $after - (int) $stock['quantity_reserved']),
                        'avg_cost' => $unitCost > 0 ? $unitCost : (float) $stock['avg_cost'],
                    ]);

                    $this->options->update((int) $sizeOption['id'], [
                        'base_cost' => $unitCost > 0 ? $unitCost : (float) $sizeOption['base_cost'],
                    ]);

                    if ($unitCost > 0) {
                        $this->syncSkuCostsForSize((int) $product['id'], (int) $sizeOption['id'], $unitCost);
                    }

                    $this->movements->insert([
                        'product_id' => (int) $product['id'],
                        'size_option_id' => (int) $sizeOption['id'],
                        'movement_type' => 'adjustment',
                        'quantity' => $delta,
                        'quantity_before' => $before,
                        'quantity_after' => $after,
                        'unit_cost' => $unitCost,
                        'reference_type' => 'legacy_1bo_stock_import',
                        'reference_id' => null,
                        'note' => 'Import tồn 1bo từ shopapi SKU ' . $legacySku,
                    ]);
                }

                $summary['rows_updated']++;
                $summary['items'][] = [
                    'product_id' => (int) $product['id'],
                    'product_code' => $productCode,
                    'legacy_sku' => $legacySku,
                    'size_option_id' => (int) $sizeOption['id'],
                    'size_name' => $sizeOption['name'],
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'unit_cost' => $unitCost,
                    'status' => $dryRun ? 'preview' : 'updated',
                ];
            }
        }

        if (! $dryRun) {
            $this->stock->db->transComplete();

            if ($this->stock->db->transStatus() === false) {
                return api_error('Could not import legacy stock', [], 500);
            }
        }

        return api_success('Legacy 1bo stock imported', $summary);
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

        $quantity = (int) ($payload['quantity'] ?? 0);
        $mode = $payload['mode'] ?? null;

        if ($mode === 'set') {
            if ($quantity < 0) {
                $errors['quantity'] = 'Quantity must be greater than or equal to 0.';
            }
        } elseif ($quantity < 1) {
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

    private function findLegacySizeOption(int $productId, string $legacySku, string $legacySizeName): ?array
    {
        $sku = $legacySku === '' ? null : $this->skus
            ->withDeleted()
            ->where('product_id', $productId)
            ->where('sku_code', $legacySku)
            ->first();

        if ($sku) {
            return $this->options->find((int) $sku['size_option_id']);
        }

        $sizeGroup = $this->groups
            ->where('product_id', $productId)
            ->where('is_stock_group', 1)
            ->first();

        if (! $sizeGroup) {
            return null;
        }

        $legacySizeCode = $this->legacySkuSizeCode($legacySku);
        $legacyNameKey = $this->normalizeLegacyText($legacySizeName);
        $legacyCodeKey = $this->normalizeLegacyText($legacySizeCode);
        $sizes = $this->options
            ->where('variant_group_id', (int) $sizeGroup['id'])
            ->findAll();

        foreach ($sizes as $size) {
            $nameKey = $this->normalizeLegacyText((string) $size['name']);
            $optionCodeKey = $this->normalizeLegacyText((string) ($size['option_code'] ?? ''));
            $firstTokenKey = $this->normalizeLegacyText(strtok((string) $size['name'], ' ') ?: (string) $size['name']);

            if ($legacyNameKey !== '' && ($nameKey === $legacyNameKey || $optionCodeKey === $legacyNameKey)) {
                return $size;
            }

            if ($legacyCodeKey !== '' && ($firstTokenKey === $legacyCodeKey || $optionCodeKey === $legacyCodeKey)) {
                return $size;
            }
        }

        return null;
    }

    private function syncSkuCostsForSize(int $productId, int $sizeOptionId, float $baseCost): void
    {
        $skus = $this->skus
            ->where('product_id', $productId)
            ->where('size_option_id', $sizeOptionId)
            ->findAll();

        foreach ($skus as $sku) {
            $suggestedCost = $baseCost * max(1, (int) $sku['combo_quantity']);
            $this->skus->update((int) $sku['id'], [
                'suggested_cost' => $suggestedCost,
                'cost_price' => $suggestedCost,
            ]);
        }
    }

    private function legacySkuSizeCode(string $legacySku): string
    {
        if (preg_match('/-1bo-(.+)$/i', $legacySku, $matches) === 1) {
            return $matches[1];
        }

        $parts = explode('-', $legacySku);

        return (string) end($parts);
    }

    private function normalizeLegacyText(string $value): string
    {
        $value = strtolower(trim($value));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
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
