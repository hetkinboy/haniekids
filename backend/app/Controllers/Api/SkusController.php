<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\VariantGroupModel;
use App\Models\VariantOptionModel;
use CodeIgniter\HTTP\ResponseInterface;

class SkusController extends BaseController
{
    private ProductModel $products;
    private ProductSkuModel $skus;
    private VariantGroupModel $groups;
    private VariantOptionModel $options;

    public function __construct()
    {
        $this->products = new ProductModel();
        $this->skus = new ProductSkuModel();
        $this->groups = new VariantGroupModel();
        $this->options = new VariantOptionModel();
    }

    public function generate(int $productId): ResponseInterface
    {
        $product = $this->products->find($productId);

        if (! $product) {
            return api_error('Product not found', [], 404);
        }

        $payload = $this->payload();
        $overwrite = $this->boolValue($payload['overwrite'] ?? false);
        $forceUpdateCost = $this->boolValue($payload['force_update_cost'] ?? false);
        $skuPrefix = trim((string) ($payload['sku_prefix'] ?? $product['product_code']));
        $sizePrefix = trim((string) ($payload['size_prefix'] ?? ''));
        $comboPrefix = trim((string) ($payload['combo_prefix'] ?? ''));
        $sizePrefixPosition = $payload['size_prefix_position'] ?? 'before';
        $comboPrefixPosition = $payload['combo_prefix_position'] ?? 'before';
        $variantOrder = $payload['variant_order'] ?? 'size_combo';

        foreach (['size_prefix_position' => $sizePrefixPosition, 'combo_prefix_position' => $comboPrefixPosition] as $field => $position) {
            if (! in_array($position, ['before', 'after'], true)) {
                return api_error('Validation failed', [$field => 'Position must be before or after.'], 422);
            }
        }

        if (! in_array($variantOrder, ['size_combo', 'combo_size'], true)) {
            return api_error('Validation failed', ['variant_order' => 'Variant order must be size_combo or combo_size.'], 422);
        }

        $sizeGroup = $this->groups->where('product_id', $productId)->where('is_stock_group', 1)->first();
        $comboGroup = $this->groups->where('product_id', $productId)->where('type', 'combo')->first();

        if (! $sizeGroup) {
            return api_error('Product must have a stock dimension group', [], 422);
        }

        if (! $comboGroup) {
            return api_error('Product must have a combo group', [], 422);
        }

        $sizes = $this->options->where('variant_group_id', $sizeGroup['id'])->where('status', 'active')->orderBy('sort_order', 'ASC')->findAll();
        $combos = $this->options->where('variant_group_id', $comboGroup['id'])->where('status', 'active')->orderBy('sort_order', 'ASC')->findAll();

        if ($sizes === []) {
            return api_error('Product must have at least one active size option', [], 422);
        }

        if ($combos === []) {
            return api_error('Product must have at least one active combo option', [], 422);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $items = [];

        foreach ($sizes as $size) {
            foreach ($combos as $combo) {
                $comboQuantity = (int) $combo['combo_quantity'];

                if ($comboQuantity < 1) {
                    return api_error('Combo quantity must be greater than or equal to 1', ['combo_option_id' => $combo['id']], 422);
                }

                $suggestedCost = (float) $size['base_cost'] * $comboQuantity;
                $sizePart = $this->buildVariantPart($size['name'], $sizePrefix, $sizePrefixPosition);
                $comboPart = $this->buildVariantPart((string) $comboQuantity, $comboPrefix, $comboPrefixPosition);
                $skuCode = $this->buildSkuCode(
                    $skuPrefix,
                    $variantOrder === 'combo_size' ? [$comboPart, $sizePart] : [$sizePart, $comboPart],
                );
                $displayName = 'Size ' . $size['name'] . ' - ' . $combo['name'];
                $existing = $this->skus->withDeleted()
                    ->where('product_id', $productId)
                    ->where('size_option_id', $size['id'])
                    ->where('combo_option_id', $combo['id'])
                    ->first();

                if ($existing) {
                    if (! $overwrite) {
                        $skipped++;
                        $items[] = $this->formatSku($existing, $size['name'], $combo['name']);
                        continue;
                    }

                    $data = [
                        'combo_quantity' => $comboQuantity,
                        'suggested_cost' => $suggestedCost,
                        'display_name'   => $displayName,
                    ];

                    if ($forceUpdateCost) {
                        $data['cost_price'] = $suggestedCost;
                    }

                    if ($this->canUseSkuCode($skuCode, (int) $existing['id'])) {
                        $data['sku_code'] = $skuCode;
                    }

                    if ($existing['deleted_at'] !== null) {
                        db_connect()->table('product_skus')->where('id', $existing['id'])->update(['deleted_at' => null]);
                    }

                    $this->skus->update($existing['id'], $data);
                    $updated++;
                    $items[] = $this->formatSku($this->skus->find($existing['id']), $size['name'], $combo['name']);
                    continue;
                }

                if (! $this->canUseSkuCode($skuCode)) {
                    return api_error('SKU code already exists', ['sku_code' => $skuCode], 422);
                }

                $id = $this->skus->insert([
                    'product_id'       => $productId,
                    'sku_code'         => $skuCode,
                    'display_name'     => $displayName,
                    'size_option_id'   => $size['id'],
                    'combo_option_id'  => $combo['id'],
                    'combo_quantity'   => $comboQuantity,
                    'suggested_cost'   => $suggestedCost,
                    'cost_price'       => $suggestedCost,
                    'sale_price'       => 0,
                    'is_sellable'      => (int) $combo['default_sellable'],
                    'is_active'        => 1,
                ], true);

                $created++;
                $items[] = $this->formatSku($this->skus->find($id), $size['name'], $combo['name']);
            }
        }

        return api_success('Sinh SKU thanh cong', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'items'   => $items,
        ]);
    }

    public function index(int $productId): ResponseInterface
    {
        if (! $this->products->find($productId)) {
            return api_error('Product not found', [], 404);
        }

        $keyword = trim((string) $this->request->getGet('keyword'));
        $isSellable = $this->request->getGet('is_sellable');
        $isActive = $this->request->getGet('is_active');
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = min(100, max(1, (int) ($this->request->getGet('pageSize') ?? 20)));

        $builder = $this->skus
            ->select('product_skus.*, size_options.name AS size_name, combo_options.name AS combo_name')
            ->join('variant_options AS size_options', 'size_options.id = product_skus.size_option_id')
            ->join('variant_options AS combo_options', 'combo_options.id = product_skus.combo_option_id')
            ->where('product_skus.product_id', $productId);

        if ($keyword !== '') {
            $builder = $builder->groupStart()
                ->like('product_skus.sku_code', $keyword)
                ->orLike('product_skus.display_name', $keyword)
                ->orLike('size_options.name', $keyword)
                ->orLike('combo_options.name', $keyword)
                ->groupEnd();
        }

        if ($isSellable !== null && $isSellable !== '') {
            $builder = $builder->where('product_skus.is_sellable', $this->boolValue($isSellable) ? 1 : 0);
        }

        if ($isActive !== null && $isActive !== '') {
            $builder = $builder->where('product_skus.is_active', $this->boolValue($isActive) ? 1 : 0);
        }

        $items = $builder
            ->orderBy('size_options.sort_order', 'ASC')
            ->orderBy('product_skus.combo_quantity', 'ASC')
            ->paginate($pageSize, 'default', $page);

        return api_success('Success', [
            'items' => array_map(fn (array $sku): array => $this->formatSku($sku, $sku['size_name'], $sku['combo_name']), $items),
            'pager' => [
                'page'     => $this->skus->pager->getCurrentPage(),
                'pageSize' => $pageSize,
                'total'    => $this->skus->pager->getTotal(),
            ],
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $sku = $this->skus->find($id);

        if (! $sku) {
            return api_error('SKU not found', [], 404);
        }

        $payload = $this->payload();
        $errors = [];

        foreach (['cost_price', 'sale_price'] as $field) {
            if (array_key_exists($field, $payload) && (float) $payload[$field] < 0) {
                $errors[$field] = 'The ' . $field . ' must be greater than or equal to 0.';
            }
        }

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $data = [];

        foreach (['cost_price', 'sale_price'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }

        foreach (['is_sellable', 'is_active'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $this->boolValue($payload[$field]) ? 1 : 0;
            }
        }

        if ($data !== []) {
            $this->skus->update($id, $data);
        }

        $updated = $this->skus
            ->select('product_skus.*, size_options.name AS size_name, combo_options.name AS combo_name')
            ->join('variant_options AS size_options', 'size_options.id = product_skus.size_option_id')
            ->join('variant_options AS combo_options', 'combo_options.id = product_skus.combo_option_id')
            ->where('product_skus.id', $id)
            ->first();

        return api_success('SKU updated', $this->formatSku($updated, $updated['size_name'], $updated['combo_name']));
    }

    private function canUseSkuCode(string $skuCode, ?int $ignoreId = null): bool
    {
        $query = $this->skus->withDeleted()->where('sku_code', $skuCode);

        if ($ignoreId !== null) {
            $query->where('id !=', $ignoreId);
        }

        return $query->countAllResults() === 0;
    }

    private function buildSkuCode(string $prefix, array $parts): string
    {
        $segments = [];
        $prefix = $this->sanitizeSkuSegment($prefix);

        if ($prefix !== '') {
            $segments[] = $prefix;
        }

        foreach ($parts as $part) {
            $part = $this->sanitizeSkuPart($part);

            if ($part !== '') {
                $segments[] = $part;
            }
        }

        return implode('-', $segments);
    }

    private function buildVariantPart(string $value, string $prefix, string $position): string
    {
        $value = $this->sanitizeSkuPart($value);
        $prefix = $this->sanitizeSkuPart($prefix);

        if ($prefix === '') {
            return $value;
        }

        return $position === 'after'
            ? $value . $prefix
            : $prefix . $value;
    }

    private function sanitizeSkuPart(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9]+/', '', $value) ?? '';
    }

    private function sanitizeSkuSegment(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[^A-Za-z0-9-]+/', '', $value) ?? '';
        $value = preg_replace('/-+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    private function payload(): array
    {
        return $this->request->getJSON(true) ?? $this->request->getRawInput() ?? $this->request->getPost();
    }

    private function formatSku(array $sku, ?string $sizeName = null, ?string $comboName = null): array
    {
        return [
            'id'                => (int) $sku['id'],
            'sku_code'          => $sku['sku_code'],
            'display_name'      => $sku['display_name'],
            'size_option_id'    => (int) $sku['size_option_id'],
            'combo_option_id'   => (int) $sku['combo_option_id'],
            'size_name'         => $sizeName,
            'combo_name'        => $comboName,
            'combo_quantity'    => (int) $sku['combo_quantity'],
            'suggested_cost'    => (float) $sku['suggested_cost'],
            'cost_price'        => (float) $sku['cost_price'],
            'sale_price'        => (float) $sku['sale_price'],
            'is_sellable'       => (bool) $sku['is_sellable'],
            'is_active'         => (bool) $sku['is_active'],
        ];
    }

    private function boolValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
