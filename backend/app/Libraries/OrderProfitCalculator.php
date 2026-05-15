<?php

namespace App\Libraries;

use App\Models\OperatingFeeSettingModel;

class OrderProfitCalculator
{
    private OperatingFeeSettingModel $feeSettings;

    public function __construct()
    {
        $this->feeSettings = new OperatingFeeSettingModel();
    }

    public function calculateFees(float $grossAmount, ?float $customerPaid = null): array
    {
        $customerPaid ??= $grossAmount;
        $fees = [
            'platform_fee'    => 0.0,
            'transaction_fee' => 0.0,
            'shipping_fee'    => 0.0,
            'return_fee'      => 0.0,
            'total_fee'       => 0.0,
            'breakdown'       => [],
        ];

        $settings = $this->feeSettings->where('status', 'active')->findAll();

        foreach ($settings as $setting) {
            $feeKey = (string) $setting['fee_key'];
            $baseAmount = $grossAmount;
            $amount = $this->feeAmount($setting, $baseAmount);

            if (in_array($feeKey, ['platform_commission'], true)) {
                $fees['platform_fee'] += $amount;
            } elseif (in_array($feeKey, ['transaction_fee'], true)) {
                $fees['transaction_fee'] += $amount;
            } elseif (in_array($feeKey, ['shipping_program_fee'], true)) {
                $fees['shipping_fee'] += $amount;
            } else {
                $fees['return_fee'] += $amount;
            }

            $fees['total_fee'] += $amount;
            $fees['breakdown'][$feeKey] = $amount;
        }

        return $fees;
    }

    public function profit(float $grossAmount, float $customerPaid, float $totalCost, ?float $settlementAmount = null, ?array $fees = null): array
    {
        $fees ??= $this->calculateFees($grossAmount, $customerPaid);
        $netRevenue = $settlementAmount !== null
            ? $settlementAmount
            : $grossAmount - (float) $fees['total_fee'];

        return [
            'platform_fee'    => (float) $fees['platform_fee'],
            'transaction_fee' => (float) $fees['transaction_fee'],
            'shipping_fee'    => (float) $fees['shipping_fee'],
            'return_fee'      => (float) $fees['return_fee'],
            'net_revenue'     => $netRevenue,
            'total_profit'    => $netRevenue - $totalCost,
            'breakdown'       => $fees['breakdown'],
        ];
    }

    private function feeAmount(array $setting, float $baseAmount): float
    {
        $rate = max(0, (float) ($setting['rate'] ?? 0));

        if (($setting['value_type'] ?? 'percent') === 'percent') {
            return $baseAmount * $rate / 100;
        }

        return $rate;
    }
}
