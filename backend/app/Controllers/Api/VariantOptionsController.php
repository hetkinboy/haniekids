<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductSkuModel;
use App\Models\StockBySizeModel;
use App\Models\StockMovementModel;
use App\Models\VariantGroupModel;
use App\Models\VariantOptionModel;
use CodeIgniter\HTTP\ResponseInterface;

class VariantOptionsController extends BaseController
{
    private VariantGroupModel $groups;
    private VariantOptionModel $options;
    private ProductSkuModel $skus;
    private StockBySizeModel $stock;
    private StockMovementModel $movements;

    public function __construct()
    {
        $this->groups = new VariantGroupModel();
        $this->options = new VariantOptionModel();
        $this->skus = new ProductSkuModel();
        $this->stock = new StockBySizeModel();
        $this->movements = new StockMovementModel();
    }

    public function create(int $groupId): ResponseInterface
    {
        $group = $this->groups->find($groupId);

        if (! $group) {
            return api_error('Variant group not found', [], 404);
        }

        $payload = $this->payload();
        $errors = $this->validateOptionPayload($payload, $group);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $this->options->db->transStart();

        $id = $this->options->insert($this->optionData($payload, $group), true);
        $option = $this->options->find($id);

        if ((int) $group['is_stock_group'] === 1) {
            $this->createStockBySize((int) $group['product_id'], $id, (float) $option['base_cost']);
        }

        $this->options->db->transComplete();

        if ($this->options->db->transStatus() === false) {
            return api_error('Could not create variant option', [], 500);
        }

        return api_success('Variant option created', $this->formatOption($option), 201);
    }

    public function update(int $id): ResponseInterface
    {
        $option = $this->options->find($id);

        if (! $option) {
            return api_error('Variant option not found', [], 404);
        }

        $group = $this->groups->find((int) $option['variant_group_id']);
        $payload = $this->payload();
        $errors = $this->validateOptionPayload($payload, $group, $id, false);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        if ($group['type'] === 'combo' && array_key_exists('combo_quantity', $payload)) {
            $hasSku = $this->skus->withDeleted()->where('combo_option_id', $id)->countAllResults() > 0;

            if ($hasSku && (int) $payload['combo_quantity'] !== (int) $option['combo_quantity']) {
                return api_error('Cannot change combo quantity after SKU exists', [], 422);
            }
        }

        $data = $this->optionData($payload, $group, false);

        $this->options->db->transStart();
        $this->options->update($id, $data);

        if ((int) $group['is_stock_group'] === 1 && array_key_exists('base_cost', $payload)) {
            $this->syncSizeAverageCostIfNoMovement((int) $group['product_id'], $id, (float) $payload['base_cost']);
        }

        $this->options->db->transComplete();

        if ($this->options->db->transStatus() === false) {
            return api_error('Could not update variant option', [], 500);
        }

        return api_success('Variant option updated', $this->formatOption($this->options->find($id)));
    }

    public function delete(int $id): ResponseInterface
    {
        $option = $this->options->find($id);

        if (! $option) {
            return api_error('Variant option not found', [], 404);
        }

        $group = $this->groups->find((int) $option['variant_group_id']);
        $usedBySku = $this->skus->withDeleted()
            ->groupStart()
            ->where('size_option_id', $id)
            ->orWhere('combo_option_id', $id)
            ->groupEnd()
            ->countAllResults() > 0;

        if ($usedBySku) {
            return api_error('Cannot delete option because it is used in SKU', [], 422);
        }

        if ((int) $group['is_stock_group'] === 1) {
            $stock = $this->stock->findByProductAndSize((int) $group['product_id'], $id);

            if ($stock && ((int) $stock['quantity_on_hand'] !== 0 || (int) $stock['quantity_reserved'] !== 0 || (int) $stock['quantity_available'] !== 0)) {
                return api_error('Cannot delete size option because it has stock quantity', [], 422);
            }

            if ($this->movements->where('product_id', $group['product_id'])->where('size_option_id', $id)->countAllResults() > 0) {
                return api_error('Cannot delete size option because it has stock movements', [], 422);
            }
        }

        $this->options->delete($id);

        return api_success('Variant option deleted');
    }

    private function validateOptionPayload(array $payload, array $group, ?int $id = null, bool $isCreate = true): array
    {
        $errors = [];

        if ($isCreate || array_key_exists('name', $payload)) {
            $name = trim((string) ($payload['name'] ?? ''));

            if ($name === '') {
                $errors['name'] = 'The name field is required.';
            } else {
                $query = $this->options->where('variant_group_id', $group['id'])->where('name', $name);

                if ($id !== null) {
                    $query->where('id !=', $id);
                }

                if ($query->countAllResults() > 0) {
                    $errors['name'] = 'The name already exists in this group.';
                }
            }
        }

        if (array_key_exists('base_cost', $payload) && (float) $payload['base_cost'] < 0) {
            $errors['base_cost'] = 'The base cost must be greater than or equal to 0.';
        }

        if ($group['type'] === 'combo') {
            if ($isCreate || array_key_exists('combo_quantity', $payload)) {
                $comboQuantity = (int) ($payload['combo_quantity'] ?? 0);

                if ($comboQuantity < 1) {
                    $errors['combo_quantity'] = 'The combo quantity must be greater than or equal to 1.';
                } else {
                    $optionIds = $this->optionIdsForProductComboGroups((int) $group['product_id']);
                    $query = $this->options->where('combo_quantity', $comboQuantity);

                    if ($optionIds !== []) {
                        $query->whereIn('id', $optionIds);
                    } else {
                        $query->where('id', 0);
                    }

                    if ($id !== null) {
                        $query->where('id !=', $id);
                    }

                    if ($query->countAllResults() > 0) {
                        $errors['combo_quantity'] = 'The combo quantity already exists in this product.';
                    }
                }
            }
        }

        return $errors;
    }

    private function optionData(array $payload, array $group, bool $isCreate = true): array
    {
        $data = [];

        if ($isCreate || array_key_exists('name', $payload)) {
            $data['name'] = trim((string) $payload['name']);
        }

        if ($isCreate) {
            $data['variant_group_id'] = (int) $group['id'];
        }

        if ($group['type'] === 'text') {
            if ($isCreate || array_key_exists('value', $payload)) {
                $data['option_code'] = (string) ($payload['value'] ?? $payload['name']);
            }

            if ($isCreate || array_key_exists('base_cost', $payload)) {
                $data['base_cost'] = (float) ($payload['base_cost'] ?? 0);
            }
        }

        if ($group['type'] === 'combo' && ($isCreate || array_key_exists('combo_quantity', $payload))) {
            $data['combo_quantity'] = (int) $payload['combo_quantity'];
        }

        if ($isCreate || array_key_exists('sort_order', $payload)) {
            $data['sort_order'] = (int) ($payload['sort_order'] ?? 0);
        }

        if (array_key_exists('is_active', $payload)) {
            $data['status'] = $this->boolValue($payload['is_active']) ? 'active' : 'inactive';
        } elseif ($isCreate) {
            $data['status'] = 'active';
        }

        return $data;
    }

    private function createStockBySize(int $productId, int $sizeOptionId, float $baseCost): void
    {
        if ($this->stock->findByProductAndSize($productId, $sizeOptionId)) {
            return;
        }

        $this->stock->insert([
            'product_id'          => $productId,
            'size_option_id'      => $sizeOptionId,
            'quantity_on_hand'    => 0,
            'quantity_reserved'   => 0,
            'quantity_available'  => 0,
            'avg_cost'            => $baseCost,
        ]);
    }

    private function syncSizeAverageCostIfNoMovement(int $productId, int $sizeOptionId, float $baseCost): void
    {
        $stock = $this->stock->findByProductAndSize($productId, $sizeOptionId);

        if (! $stock) {
            $this->createStockBySize($productId, $sizeOptionId, $baseCost);
            return;
        }

        $hasMovement = $this->movements->where('product_id', $productId)->where('size_option_id', $sizeOptionId)->countAllResults() > 0;

        if (! $hasMovement) {
            $this->stock->update($stock['id'], ['avg_cost' => $baseCost]);
        }
    }

    private function optionIdsForProductComboGroups(int $productId): array
    {
        $comboGroupIds = array_column($this->groups->where('product_id', $productId)->where('type', 'combo')->findAll(), 'id');

        if ($comboGroupIds === []) {
            return [];
        }

        return array_column($this->options->whereIn('variant_group_id', $comboGroupIds)->findAll(), 'id');
    }

    private function payload(): array
    {
        return $this->request->getJSON(true) ?? $this->request->getRawInput() ?? $this->request->getPost();
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

