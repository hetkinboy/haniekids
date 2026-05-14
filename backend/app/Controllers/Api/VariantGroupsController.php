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

class VariantGroupsController extends BaseController
{
    private ProductModel $products;
    private VariantGroupModel $groups;
    private VariantOptionModel $options;
    private ProductSkuModel $skus;

    public function __construct()
    {
        $this->products = new ProductModel();
        $this->groups = new VariantGroupModel();
        $this->options = new VariantOptionModel();
        $this->skus = new ProductSkuModel();
    }

    public function index(int $productId): ResponseInterface
    {
        if (! $this->products->find($productId)) {
            return api_error('Product not found', [], 404);
        }

        $groups = $this->groups
            ->where('product_id', $productId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $groupIds = array_column($groups, 'id');
        $optionsByGroup = [];

        if ($groupIds !== []) {
            $options = $this->options
                ->whereIn('variant_group_id', $groupIds)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($options as $option) {
                $optionsByGroup[$option['variant_group_id']][] = $this->formatOption($option);
            }
        }

        $items = array_map(function (array $group) use ($optionsByGroup): array {
            $formatted = $this->formatGroup($group);
            $formatted['options'] = $optionsByGroup[$group['id']] ?? [];

            return $formatted;
        }, $groups);

        return api_success('Success', ['items' => $items]);
    }

    public function create(int $productId): ResponseInterface
    {
        if (! $this->products->find($productId)) {
            return api_error('Product not found', [], 404);
        }

        $payload = $this->payload();
        $errors = $this->validateGroupPayload($payload, $productId);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $id = $this->groups->insert([
            'product_id'      => $productId,
            'name'            => trim((string) $payload['name']),
            'type'            => $payload['type'],
            'is_stock_group'  => $this->boolValue($payload['is_stock_dimension'] ?? false) ? 1 : 0,
            'sort_order'      => (int) ($payload['sort_order'] ?? 0),
            'status'          => 'active',
        ], true);

        return api_success('Variant group created', $this->formatGroup($this->groups->find($id)), 201);
    }

    public function update(int $id): ResponseInterface
    {
        $group = $this->groups->find($id);

        if (! $group) {
            return api_error('Variant group not found', [], 404);
        }

        $payload = $this->payload();
        $errors = $this->validateGroupPayload($payload, (int) $group['product_id'], $id, false);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $data = [];

        if (array_key_exists('name', $payload)) {
            $data['name'] = trim((string) $payload['name']);
        }

        if (array_key_exists('sort_order', $payload)) {
            $data['sort_order'] = (int) $payload['sort_order'];
        }

        if (array_key_exists('is_stock_dimension', $payload)) {
            $data['is_stock_group'] = $this->boolValue($payload['is_stock_dimension']) ? 1 : 0;
        }

        if (array_key_exists('type', $payload) && $payload['type'] !== $group['type']) {
            $hasOptions = $this->options->where('variant_group_id', $id)->countAllResults() > 0;
            $hasSkus = $this->hasSkuForGroup($id);

            if ($hasOptions || $hasSkus) {
                return api_error('Cannot change type after options or SKUs exist', [], 422);
            }

            $data['type'] = $payload['type'];
        }

        if ($data !== []) {
            $this->groups->update($id, $data);
        }

        return api_success('Variant group updated', $this->formatGroup($this->groups->find($id)));
    }

    public function delete(int $id): ResponseInterface
    {
        $group = $this->groups->find($id);

        if (! $group) {
            return api_error('Variant group not found', [], 404);
        }

        if ($this->hasSkuForGroup($id) || $this->hasOrderItemForGroup($id)) {
            return api_error('Cannot delete group because it is used by SKU or order items', [], 422);
        }

        $optionIds = array_column($this->options->where('variant_group_id', $id)->findAll(), 'id');

        if ($optionIds !== []) {
            $this->options->whereIn('id', $optionIds)->delete();
        }

        $this->groups->delete($id);

        return api_success('Variant group deleted');
    }

    private function validateGroupPayload(array $payload, int $productId, ?int $id = null, bool $isCreate = true): array
    {
        $errors = [];

        if ($isCreate || array_key_exists('name', $payload)) {
            if (trim((string) ($payload['name'] ?? '')) === '') {
                $errors['name'] = 'The name field is required.';
            }
        }

        if ($isCreate || array_key_exists('type', $payload)) {
            if (! in_array($payload['type'] ?? null, ['text', 'combo'], true)) {
                $errors['type'] = 'The type must be text or combo.';
            }
        }

        $currentGroup = $id === null ? null : $this->groups->find($id);
        $type = $payload['type'] ?? ($currentGroup['type'] ?? null);
        $isStockDimension = array_key_exists('is_stock_dimension', $payload)
            ? $this->boolValue($payload['is_stock_dimension'])
            : false;

        if ($isStockDimension && (($type ?? null) === 'combo')) {
            $errors['is_stock_dimension'] = 'A combo group cannot be the stock dimension.';
        }

        if ($type === 'combo') {
            $query = $this->groups->where('product_id', $productId)->where('type', 'combo');

            if ($id !== null) {
                $query->where('id !=', $id);
            }

            if ($query->countAllResults() > 0) {
                $errors['type'] = 'Each product can only have one combo group.';
            }
        }

        if (array_key_exists('is_stock_dimension', $payload) && $this->boolValue($payload['is_stock_dimension'])) {
            $query = $this->groups->where('product_id', $productId)->where('is_stock_group', 1);

            if ($id !== null) {
                $query->where('id !=', $id);
            }

            if ($query->countAllResults() > 0) {
                $errors['is_stock_dimension'] = 'Each product can only have one stock dimension group.';
            }
        }

        return $errors;
    }

    private function hasSkuForGroup(int $groupId): bool
    {
        $optionIds = array_column($this->options->withDeleted()->where('variant_group_id', $groupId)->findAll(), 'id');

        if ($optionIds === []) {
            return false;
        }

        return $this->skus->withDeleted()
            ->groupStart()
            ->whereIn('size_option_id', $optionIds)
            ->orWhereIn('combo_option_id', $optionIds)
            ->groupEnd()
            ->countAllResults() > 0;
    }

    private function hasOrderItemForGroup(int $groupId): bool
    {
        $optionIds = array_column($this->options->withDeleted()->where('variant_group_id', $groupId)->findAll(), 'id');

        if ($optionIds === []) {
            return false;
        }

        return db_connect()->table('order_items')
            ->groupStart()
            ->whereIn('size_option_id', $optionIds)
            ->orWhereIn('combo_option_id', $optionIds)
            ->groupEnd()
            ->countAllResults() > 0;
    }

    private function payload(): array
    {
        return $this->request->getJSON(true) ?? $this->request->getRawInput() ?? $this->request->getPost();
    }

    private function formatGroup(array $group): array
    {
        return [
            'id'                 => (int) $group['id'],
            'product_id'         => (int) $group['product_id'],
            'name'               => $group['name'],
            'type'               => $group['type'],
            'is_stock_dimension' => (bool) $group['is_stock_group'],
            'sort_order'         => (int) $group['sort_order'],
            'status'             => $group['status'],
            'created_at'         => $group['created_at'],
            'updated_at'         => $group['updated_at'],
        ];
    }

    private function formatOption(array $option): array
    {
        return [
            'id'                 => (int) $option['id'],
            'variant_group_id'   => (int) $option['variant_group_id'],
            'name'               => $option['name'],
            'value'              => $option['option_code'],
            'base_cost'          => (float) $option['base_cost'],
            'combo_quantity'     => $option['combo_quantity'] === null ? null : (int) $option['combo_quantity'],
            'default_sellable'   => (bool) $option['default_sellable'],
            'sort_order'         => (int) $option['sort_order'],
            'is_active'          => $option['status'] === 'active',
            'status'             => $option['status'],
        ];
    }

    private function boolValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
