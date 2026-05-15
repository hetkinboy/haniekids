<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\OperatingFeeSettingModel;
use App\Models\OperatingCostModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use CodeIgniter\HTTP\ResponseInterface;

class OperatingCostsController extends BaseController
{
    private OperatingCostModel $costs;
    private OperatingFeeSettingModel $feeSettings;
    private ProductModel $products;
    private OrderModel $orders;

    public function __construct()
    {
        $this->costs = new OperatingCostModel();
        $this->feeSettings = new OperatingFeeSettingModel();
        $this->products = new ProductModel();
        $this->orders = new OrderModel();
    }

    public function feeSettings(): ResponseInterface
    {
        return api_success('Success', [
            'items' => array_map(
                fn (array $item): array => $this->formatFeeSetting($item),
                $this->feeSettings->orderBy('id', 'ASC')->findAll(),
            ),
        ]);
    }

    public function saveFeeSettings(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $items = $payload['items'] ?? [];

        if (! is_array($items)) {
            return api_error('Validation failed', ['items' => 'Items must be an array.'], 422);
        }

        $this->feeSettings->db->transStart();

        foreach ($items as $item) {
            $feeKey = trim((string) ($item['fee_key'] ?? ''));
            $existing = $feeKey === '' ? null : $this->feeSettings->where('fee_key', $feeKey)->first();

            if (! $existing) {
                continue;
            }

            $valueType = $item['value_type'] ?? $existing['value_type'];
            if (! in_array($valueType, ['percent', 'fixed'], true)) {
                $valueType = $existing['value_type'];
            }

            $this->feeSettings->update((int) $existing['id'], [
                'label'      => trim((string) ($item['label'] ?? $existing['label'])),
                'value_type' => $valueType,
                'rate'       => max(0, (float) ($item['rate'] ?? 0)),
                'status'     => in_array($item['status'] ?? $existing['status'], ['active', 'inactive'], true) ? $item['status'] ?? $existing['status'] : 'active',
            ]);
        }

        $this->feeSettings->db->transComplete();

        if ($this->feeSettings->db->transStatus() === false) {
            return api_error('Could not save fee settings', [], 500);
        }

        return $this->feeSettings();
    }

    public function index(): ResponseInterface
    {
        $costType = trim((string) $this->request->getGet('cost_type'));
        $dateFrom = $this->request->getGet('date_from');
        $dateTo = $this->request->getGet('date_to');
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = min(100, max(1, (int) ($this->request->getGet('pageSize') ?? 20)));

        $builder = $this->costs;

        if ($costType !== '') {
            $builder = $builder->where('operating_costs.cost_type', $costType);
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $builder = $builder->where('operating_costs.cost_date >=', $dateFrom);
        }

        if ($dateTo !== null && $dateTo !== '') {
            $builder = $builder->where('operating_costs.cost_date <=', $dateTo);
        }

        $summary = $this->costSummary($costType, $dateFrom, $dateTo);
        $items = $builder->orderBy('operating_costs.id', 'DESC')->paginate($pageSize, 'default', $page);

        return api_success('Success', [
            'items' => array_map(fn (array $item): array => $this->formatCost($item), $items),
            'pager' => [
                'page' => $this->costs->pager->getCurrentPage(),
                'pageSize' => $pageSize,
                'total' => $this->costs->pager->getTotal(),
            ],
            'summary' => $summary,
        ]);
    }

    private function costSummary(string $costType, mixed $dateFrom, mixed $dateTo): array
    {
        $builder = $this->costs->db->table('operating_costs')
            ->select('COUNT(*) AS total_costs, COALESCE(SUM(amount), 0) AS total_amount', false);

        if ($costType !== '') {
            $builder->where('cost_type', $costType);
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $builder->where('cost_date >=', $dateFrom);
        }

        if ($dateTo !== null && $dateTo !== '') {
            $builder->where('cost_date <=', $dateTo);
        }

        $row = $builder->get()->getRowArray() ?? [];

        return [
            'total_costs' => (int) ($row['total_costs'] ?? 0),
            'total_amount' => (float) ($row['total_amount'] ?? 0),
        ];
    }

    public function show(int $id): ResponseInterface
    {
        $cost = $this->costs->find($id);

        if (! $cost) {
            return api_error('Operating cost not found', [], 404);
        }

        return api_success('Success', $this->formatCost($cost));
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $errors = $this->validatePayload($payload);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $id = $this->costs->insert($this->costData($payload), true);

        return api_success('Operating cost created', $this->formatCost($this->costs->find($id)), 201);
    }

    public function update(int $id): ResponseInterface
    {
        if (! $this->costs->find($id)) {
            return api_error('Operating cost not found', [], 404);
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $errors = $this->validatePayload($payload);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $this->costs->update($id, $this->costData($payload));

        return api_success('Operating cost updated', $this->formatCost($this->costs->find($id)));
    }

    public function delete(int $id): ResponseInterface
    {
        if (! $this->costs->find($id)) {
            return api_error('Operating cost not found', [], 404);
        }

        $this->costs->delete($id);

        return api_success('Operating cost deleted');
    }

    private function validatePayload(array $payload): array
    {
        $errors = [];

        if (empty($payload['cost_date'])) {
            $errors['cost_date'] = 'Cost date is required.';
        }

        if (empty($payload['cost_type'])) {
            $errors['cost_type'] = 'Cost type is required.';
        }

        if ((float) ($payload['amount'] ?? -1) < 0) {
            $errors['amount'] = 'Amount must be greater than or equal to 0.';
        }

        if (! in_array($payload['allocation_type'] ?? 'day', ['day', 'month'], true)) {
            $errors['allocation_type'] = 'Allocation type must be day or month.';
        }

        return $errors;
    }

    private function costData(array $payload): array
    {
        return [
            'cost_date' => $payload['cost_date'],
            'cost_type' => $payload['cost_type'],
            'amount' => (float) $payload['amount'],
            'allocation_type' => $payload['allocation_type'] ?? 'day',
            'product_id' => null,
            'order_id' => null,
            'note' => $payload['note'] ?? null,
        ];
    }

    private function formatFeeSetting(array $setting): array
    {
        $setting['id'] = (int) $setting['id'];
        $setting['rate'] = (float) $setting['rate'];

        return $setting;
    }

    private function formatCost(array $cost): array
    {
        $cost['id'] = (int) $cost['id'];
        $cost['amount'] = (float) $cost['amount'];
        $cost['product_id'] = $cost['product_id'] === null ? null : (int) $cost['product_id'];
        $cost['order_id'] = $cost['order_id'] === null ? null : (int) $cost['order_id'];

        return $cost;
    }
}
