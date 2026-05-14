<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\OperatingCostModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use CodeIgniter\HTTP\ResponseInterface;

class OperatingCostsController extends BaseController
{
    private OperatingCostModel $costs;
    private ProductModel $products;
    private OrderModel $orders;

    public function __construct()
    {
        $this->costs = new OperatingCostModel();
        $this->products = new ProductModel();
        $this->orders = new OrderModel();
    }

    public function index(): ResponseInterface
    {
        $costType = trim((string) $this->request->getGet('cost_type'));
        $dateFrom = $this->request->getGet('date_from');
        $dateTo = $this->request->getGet('date_to');
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = min(100, max(1, (int) ($this->request->getGet('pageSize') ?? 20)));

        $builder = $this->costs
            ->select('operating_costs.*, products.name AS product_name, orders.order_code')
            ->join('products', 'products.id = operating_costs.product_id', 'left')
            ->join('orders', 'orders.id = operating_costs.order_id', 'left');

        if ($costType !== '') {
            $builder = $builder->where('operating_costs.cost_type', $costType);
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $builder = $builder->where('operating_costs.cost_date >=', $dateFrom);
        }

        if ($dateTo !== null && $dateTo !== '') {
            $builder = $builder->where('operating_costs.cost_date <=', $dateTo);
        }

        $items = $builder->orderBy('operating_costs.id', 'DESC')->paginate($pageSize, 'default', $page);

        return api_success('Success', [
            'items' => array_map(fn (array $item): array => $this->formatCost($item), $items),
            'pager' => [
                'page' => $this->costs->pager->getCurrentPage(),
                'pageSize' => $pageSize,
                'total' => $this->costs->pager->getTotal(),
            ],
        ]);
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

        if (! empty($payload['product_id']) && ! $this->products->find((int) $payload['product_id'])) {
            $errors['product_id'] = 'Product not found.';
        }

        if (! empty($payload['order_id']) && ! $this->orders->find((int) $payload['order_id'])) {
            $errors['order_id'] = 'Order not found.';
        }

        return $errors;
    }

    private function costData(array $payload): array
    {
        return [
            'cost_date' => $payload['cost_date'],
            'cost_type' => $payload['cost_type'],
            'amount' => (float) $payload['amount'],
            'allocation_type' => $payload['allocation_type'] ?? 'manual',
            'product_id' => $payload['product_id'] ?? null,
            'order_id' => $payload['order_id'] ?? null,
            'note' => $payload['note'] ?? null,
        ];
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
