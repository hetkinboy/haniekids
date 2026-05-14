<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\SettlementItemModel;
use App\Models\SettlementModel;
use CodeIgniter\HTTP\ResponseInterface;

class SettlementsController extends BaseController
{
    private SettlementModel $settlements;
    private SettlementItemModel $items;
    private OrderModel $orders;

    public function __construct()
    {
        $this->settlements = new SettlementModel();
        $this->items = new SettlementItemModel();
        $this->orders = new OrderModel();
    }

    public function index(): ResponseInterface
    {
        $status = trim((string) $this->request->getGet('status'));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pageSize = min(100, max(1, (int) ($this->request->getGet('pageSize') ?? 20)));

        $builder = $this->settlements;

        if ($status !== '') {
            $builder = $builder->where('status', $status);
        }

        $items = $builder->orderBy('id', 'DESC')->paginate($pageSize, 'default', $page);

        return api_success('Success', [
            'items' => array_map(fn (array $item): array => $this->formatSettlement($item), $items),
            'pager' => [
                'page' => $this->settlements->pager->getCurrentPage(),
                'pageSize' => $pageSize,
                'total' => $this->settlements->pager->getTotal(),
            ],
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $settlement = $this->settlements->find($id);

        if (! $settlement) {
            return api_error('Settlement not found', [], 404);
        }

        $data = $this->formatSettlement($settlement);
        $data['items'] = $this->items->where('settlement_id', $id)->orderBy('id', 'ASC')->findAll();

        return api_success('Success', $data);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $errors = $this->validatePayload($payload);

        if ($errors !== []) {
            return api_error('Validation failed', $errors, 422);
        }

        $code = trim((string) ($payload['settlement_code'] ?? ''));
        if ($code === '') {
            $code = 'SET-' . date('Ymd-His');
        }

        $this->settlements->db->transStart();

        $settlementId = $this->settlements->insert([
            'settlement_code' => $code,
            'period_from' => $payload['period_from'],
            'period_to' => $payload['period_to'],
            'platform' => $payload['platform'] ?? 'tiktok',
            'total_gross' => 0,
            'total_fee' => 0,
            'total_settled' => 0,
            'total_difference' => 0,
            'status' => $payload['status'] ?? 'draft',
            'note' => $payload['note'] ?? null,
        ], true);

        $totals = ['gross' => 0.0, 'fee' => 0.0, 'settled' => 0.0, 'difference' => 0.0];

        foreach ($payload['items'] ?? [] as $line) {
            $order = null;
            if (! empty($line['order_id'])) {
                $order = $this->orders->find((int) $line['order_id']);
            } elseif (! empty($line['order_code'])) {
                $order = $this->orders->where('order_code', $line['order_code'])->first();
            }

            $gross = (float) ($line['gross_amount'] ?? ($order['gross_amount'] ?? 0));
            $platformFee = (float) ($line['platform_fee'] ?? ($order['platform_fee'] ?? 0));
            $shippingFee = (float) ($line['shipping_fee'] ?? ($order['shipping_fee'] ?? 0));
            $settled = (float) ($line['settled_amount'] ?? 0);
            $expected = (float) ($line['expected_amount'] ?? ($order['net_revenue'] ?? ($gross - $platformFee - $shippingFee)));
            $difference = $settled - $expected;

            $totals['gross'] += $gross;
            $totals['fee'] += $platformFee + $shippingFee;
            $totals['settled'] += $settled;
            $totals['difference'] += $difference;

            $this->items->insert([
                'settlement_id' => $settlementId,
                'order_id' => $order['id'] ?? null,
                'order_code' => $line['order_code'] ?? ($order['order_code'] ?? ''),
                'gross_amount' => $gross,
                'platform_fee' => $platformFee,
                'shipping_fee' => $shippingFee,
                'settled_amount' => $settled,
                'expected_amount' => $expected,
                'difference_amount' => $difference,
                'reason' => $line['reason'] ?? null,
                'status' => abs($difference) > 0.01 ? 'mismatch' : 'matched',
            ]);
        }

        $this->settlements->update($settlementId, [
            'total_gross' => $totals['gross'],
            'total_fee' => $totals['fee'],
            'total_settled' => $totals['settled'],
            'total_difference' => $totals['difference'],
        ]);

        $this->settlements->db->transComplete();

        if ($this->settlements->db->transStatus() === false) {
            return api_error('Could not create settlement', [], 500);
        }

        return $this->show($settlementId);
    }

    private function validatePayload(array $payload): array
    {
        $errors = [];

        if (empty($payload['period_from'])) {
            $errors['period_from'] = 'Period from is required.';
        }

        if (empty($payload['period_to'])) {
            $errors['period_to'] = 'Period to is required.';
        }

        return $errors;
    }

    private function formatSettlement(array $settlement): array
    {
        $settlement['id'] = (int) $settlement['id'];
        foreach (['total_gross', 'total_fee', 'total_settled', 'total_difference'] as $field) {
            $settlement[$field] = (float) $settlement[$field];
        }

        return $settlement;
    }
}
