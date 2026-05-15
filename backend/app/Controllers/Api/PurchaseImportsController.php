<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\PurchaseImportItemModel;
use App\Models\PurchaseImportModel;
use App\Models\StockBySizeModel;
use App\Models\StockMovementModel;
use App\Models\VariantOptionModel;
use CodeIgniter\HTTP\ResponseInterface;

class PurchaseImportsController extends BaseController
{
    private const EDIT_WINDOW_SECONDS = 600;

    private PurchaseImportModel $imports;
    private PurchaseImportItemModel $items;
    private ProductModel $products;
    private VariantOptionModel $options;
    private StockBySizeModel $stock;
    private StockMovementModel $movements;

    public function __construct()
    {
        $this->imports = new PurchaseImportModel();
        $this->items = new PurchaseImportItemModel();
        $this->products = new ProductModel();
        $this->options = new VariantOptionModel();
        $this->stock = new StockBySizeModel();
        $this->movements = new StockMovementModel();
    }

    public function index(): ResponseInterface
    {
        $keyword = trim((string) $this->request->getGet('keyword'));
        $dateFrom = $this->request->getGet('date_from');
        $dateTo = $this->request->getGet('date_to');
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = min(100, max(1, (int) ($this->request->getGet('pageSize') ?? 20)));

        $builder = $this->imports;

        if ($keyword !== '') {
            $builder = $builder->groupStart()
                ->like('import_code', $keyword)
                ->orLike('supplier_name', $keyword)
                ->groupEnd();
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $builder = $builder->where('import_date >=', $dateFrom);
        }

        if ($dateTo !== null && $dateTo !== '') {
            $builder = $builder->where('import_date <=', $dateTo);
        }

        $summary = $this->importSummary($keyword, $dateFrom, $dateTo);
        $imports = $builder->orderBy('id', 'DESC')->paginate($pageSize, 'default', $page);

        return api_success('Success', [
            'items' => array_map(fn (array $item): array => $this->formatImport($item), $imports),
            'pager' => [
                'page'     => $this->imports->pager->getCurrentPage(),
                'pageSize' => $pageSize,
                'total'    => $this->imports->pager->getTotal(),
            ],
            'summary' => $summary,
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $import = $this->imports->find($id);

        if (! $import) {
            return api_error('Purchase import not found', [], 404);
        }

        $items = $this->items
            ->select('purchase_import_items.*, products.name AS product_name, variant_options.name AS size_name')
            ->join('products', 'products.id = purchase_import_items.product_id')
            ->join('variant_options', 'variant_options.id = purchase_import_items.size_option_id')
            ->where('products.deleted_at', null)
            ->where('purchase_import_id', $id)
            ->findAll();

        $data = $this->formatImport($import);
        $data['items'] = array_map(fn (array $item): array => $this->formatImportItem($item), $items);
        $data['can_edit'] = $this->canEditImport($import);

        return api_success('Success', $data);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $errors = $this->validatePayload($payload);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $importCode = trim((string) ($payload['import_code'] ?? ''));

        if ($importCode === '') {
            $importCode = 'IMP-' . date('Ymd-His');
        }

        $totalQuantity = 0;
        $totalAmount = 0.0;

        $this->imports->db->transStart();

        $importId = $this->imports->insert([
            'import_code'     => $importCode,
            'supplier_name'   => $payload['supplier_name'] ?? null,
            'import_date'     => $payload['import_date'],
            'total_quantity'  => 0,
            'total_amount'    => 0,
            'note'            => $payload['note'] ?? null,
        ], true);

        foreach ($payload['items'] as $line) {
            $productId = (int) $line['product_id'];
            $sizeOptionId = (int) $line['size_option_id'];
            $quantity = (int) $line['quantity'];
            $unitCost = (float) $line['unit_cost'];
            $lineTotal = $quantity * $unitCost;
            $totalQuantity += $quantity;
            $totalAmount += $lineTotal;

            $itemId = $this->items->insert([
                'purchase_import_id' => $importId,
                'product_id'         => $productId,
                'size_option_id'     => $sizeOptionId,
                'quantity'           => $quantity,
                'unit_cost'          => $unitCost,
                'total_cost'         => $lineTotal,
            ], true);

            $stock = $this->findOrCreateStock($productId, $sizeOptionId, $unitCost);
            $before = (int) $stock['quantity_on_hand'];
            $after = $before + $quantity;
            $avgCost = $this->weightedAverageCost($before, (float) $stock['avg_cost'], $quantity, $unitCost);

            $this->stock->update($stock['id'], [
                'quantity_on_hand'   => $after,
                'quantity_available' => max(0, $after - (int) $stock['quantity_reserved']),
                'avg_cost'           => $avgCost,
            ]);

            $this->movements->insert([
                'product_id'          => $productId,
                'size_option_id'      => $sizeOptionId,
                'movement_type'       => 'import',
                'quantity'            => $quantity,
                'quantity_before'     => $before,
                'quantity_after'      => $after,
                'unit_cost'           => $unitCost,
                'reference_type'      => 'purchase_import',
                'reference_id'        => $importId,
                'purchase_import_id'  => $importId,
                'note'                => 'Import item #' . $itemId,
            ]);
        }

        $this->imports->update($importId, [
            'total_quantity' => $totalQuantity,
            'total_amount'   => $totalAmount,
        ]);

        $this->imports->db->transComplete();

        if ($this->imports->db->transStatus() === false) {
            return api_error('Could not create purchase import', [], 500);
        }

        return api_success('Purchase import created', $this->showData($importId), 201);
    }

    public function update(int $id): ResponseInterface
    {
        $import = $this->imports->find($id);

        if (! $import) {
            return api_error('Purchase import not found', [], 404);
        }

        if (! $this->canEditImport($import)) {
            return api_error('Purchase import can only be edited within 10 minutes after creation', [], 422);
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $errors = $this->validatePayload($payload);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $this->imports->db->transStart();
        $this->reverseImportStock($id);
        $this->items->where('purchase_import_id', $id)->delete();
        $this->movements->where('purchase_import_id', $id)->delete();

        $totals = $this->applyImportItems($id, $payload['items']);
        $this->imports->update($id, [
            'import_code'     => trim((string) ($payload['import_code'] ?? '')) ?: $import['import_code'],
            'supplier_name'   => $payload['supplier_name'] ?? null,
            'import_date'     => $payload['import_date'],
            'total_quantity'  => $totals['quantity'],
            'total_amount'    => $totals['amount'],
            'note'            => $payload['note'] ?? null,
        ]);

        $this->imports->db->transComplete();

        if ($this->imports->db->transStatus() === false) {
            return api_error('Could not update purchase import', [], 500);
        }

        return api_success('Purchase import updated', $this->showData($id));
    }

    private function validatePayload(array $payload): array
    {
        $errors = [];

        if (empty($payload['import_date'])) {
            $errors['import_date'] = 'Import date is required.';
        }

        if (! isset($payload['items']) || ! is_array($payload['items']) || $payload['items'] === []) {
            $errors['items'] = 'Items must not be empty.';
            return $errors;
        }

        foreach ($payload['items'] as $index => $line) {
            $prefix = 'items.' . $index;
            $productId = (int) ($line['product_id'] ?? 0);
            $sizeOptionId = (int) ($line['size_option_id'] ?? 0);

            if (! $this->products->find($productId)) {
                $errors[$prefix . '.product_id'] = 'Product not found.';
            }

            if (! $this->isSizeOptionOfProduct($productId, $sizeOptionId)) {
                $errors[$prefix . '.size_option_id'] = 'Size option does not belong to product stock dimension.';
            }

            if ((int) ($line['quantity'] ?? 0) < 1) {
                $errors[$prefix . '.quantity'] = 'Quantity must be greater than 0.';
            }

            if ((float) ($line['unit_cost'] ?? -1) < 0) {
                $errors[$prefix . '.unit_cost'] = 'Unit cost must be greater than or equal to 0.';
            }
        }

        return $errors;
    }

    private function importSummary(string $keyword, mixed $dateFrom, mixed $dateTo): array
    {
        $builder = $this->imports->db->table('purchase_imports')
            ->select('COUNT(*) AS total_imports, COALESCE(SUM(total_quantity), 0) AS total_quantity, COALESCE(SUM(total_amount), 0) AS total_amount', false);

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('import_code', $keyword)
                ->orLike('supplier_name', $keyword)
                ->groupEnd();
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $builder->where('import_date >=', $dateFrom);
        }

        if ($dateTo !== null && $dateTo !== '') {
            $builder->where('import_date <=', $dateTo);
        }

        $row = $builder->get()->getRowArray() ?? [];

        return [
            'total_imports' => (int) ($row['total_imports'] ?? 0),
            'total_quantity' => (int) ($row['total_quantity'] ?? 0),
            'total_amount' => (float) ($row['total_amount'] ?? 0),
        ];
    }

    private function showData(int $id): array
    {
        $import = $this->imports->find($id);
        $items = $this->items
            ->select('purchase_import_items.*, products.name AS product_name, variant_options.name AS size_name')
            ->join('products', 'products.id = purchase_import_items.product_id')
            ->join('variant_options', 'variant_options.id = purchase_import_items.size_option_id')
            ->where('products.deleted_at', null)
            ->where('purchase_import_id', $id)
            ->findAll();

        $data = $this->formatImport($import);
        $data['items'] = array_map(fn (array $item): array => $this->formatImportItem($item), $items);
        $data['can_edit'] = $this->canEditImport($import);

        return $data;
    }

    private function applyImportItems(int $importId, array $items): array
    {
        $totalQuantity = 0;
        $totalAmount = 0.0;

        foreach ($items as $line) {
            $productId = (int) $line['product_id'];
            $sizeOptionId = (int) $line['size_option_id'];
            $quantity = (int) $line['quantity'];
            $unitCost = (float) $line['unit_cost'];
            $lineTotal = $quantity * $unitCost;
            $totalQuantity += $quantity;
            $totalAmount += $lineTotal;

            $itemId = $this->items->insert([
                'purchase_import_id' => $importId,
                'product_id'         => $productId,
                'size_option_id'     => $sizeOptionId,
                'quantity'           => $quantity,
                'unit_cost'          => $unitCost,
                'total_cost'         => $lineTotal,
            ], true);

            $stock = $this->findOrCreateStock($productId, $sizeOptionId, $unitCost);
            $before = (int) $stock['quantity_on_hand'];
            $after = $before + $quantity;
            $avgCost = $this->weightedAverageCost($before, (float) $stock['avg_cost'], $quantity, $unitCost);

            $this->stock->update($stock['id'], [
                'quantity_on_hand'   => $after,
                'quantity_available' => max(0, $after - (int) $stock['quantity_reserved']),
                'avg_cost'           => $avgCost,
            ]);

            $this->movements->insert([
                'product_id'          => $productId,
                'size_option_id'      => $sizeOptionId,
                'movement_type'       => 'import',
                'quantity'            => $quantity,
                'quantity_before'     => $before,
                'quantity_after'      => $after,
                'unit_cost'           => $unitCost,
                'reference_type'      => 'purchase_import',
                'reference_id'        => $importId,
                'purchase_import_id'  => $importId,
                'note'                => 'Import item #' . $itemId,
            ]);
        }

        return ['quantity' => $totalQuantity, 'amount' => $totalAmount];
    }

    private function reverseImportStock(int $importId): void
    {
        $items = $this->items->where('purchase_import_id', $importId)->findAll();

        foreach ($items as $item) {
            $stock = $this->findOrCreateStock((int) $item['product_id'], (int) $item['size_option_id'], (float) $item['unit_cost']);
            $before = (int) $stock['quantity_on_hand'];
            $after = $before - (int) $item['quantity'];

            if ($after < 0) {
                $after = 0;
            }

            $this->stock->update($stock['id'], [
                'quantity_on_hand'   => $after,
                'quantity_available' => max(0, $after - (int) $stock['quantity_reserved']),
                'avg_cost'           => $after === 0 ? 0 : (float) $stock['avg_cost'],
            ]);
        }
    }

    private function canEditImport(array $import): bool
    {
        $createdAt = strtotime((string) ($import['created_at'] ?? ''));

        return $createdAt !== false && time() - $createdAt <= self::EDIT_WINDOW_SECONDS;
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

    private function weightedAverageCost(int $oldQty, float $oldAvgCost, int $newQty, float $newUnitCost): float
    {
        $totalQty = $oldQty + $newQty;

        if ($totalQty <= 0) {
            return 0;
        }

        return (($oldQty * $oldAvgCost) + ($newQty * $newUnitCost)) / $totalQty;
    }

    private function formatImport(array $item): array
    {
        return [
            'id'             => (int) $item['id'],
            'import_code'    => $item['import_code'],
            'supplier_name'  => $item['supplier_name'],
            'import_date'    => $item['import_date'],
            'total_quantity' => (int) $item['total_quantity'],
            'total_amount'   => (float) $item['total_amount'],
            'note'           => $item['note'],
            'created_at'     => $item['created_at'],
            'updated_at'     => $item['updated_at'],
        ];
    }

    private function formatImportItem(array $item): array
    {
        return [
            'id'                 => (int) $item['id'],
            'purchase_import_id' => (int) $item['purchase_import_id'],
            'product_id'         => (int) $item['product_id'],
            'product_name'       => $item['product_name'] ?? null,
            'size_option_id'     => (int) $item['size_option_id'],
            'size_name'          => $item['size_name'] ?? null,
            'quantity'           => (int) $item['quantity'],
            'unit_cost'          => (float) $item['unit_cost'],
            'total_cost'         => (float) $item['total_cost'],
        ];
    }
}
