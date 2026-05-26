<?php

namespace App\Services;

use App\Models\LinodeAccount;
use App\Models\VpsInstance;
use Carbon\Carbon;

class LinodeBudgetService
{
    public function recalculateReserved(LinodeAccount $account): void
    {
        $reserved = VpsInstance::query()
            ->where('linode_account_id', $account->id)
            ->whereNotIn('status', ['Lỗi', 'Đã xóa', 'Lỗi API'])
            ->sum('cost_monthly_usd');

        $account->reserved_monthly_usd = round((float) $reserved, 4);
        $account->save();
    }

    public function syncAccountFromApi(LinodeAccount $account, LinodeApiService $api): void
    {
        $api->setToken($account->api_token);
        $data = $api->getAccount();

        $account->email = $data['email'] ?? $account->email;
        $account->balance_usd = isset($data['balance']) ? (float) $data['balance'] : $account->balance_usd;

        if ($account->promo_remaining_usd === null && $account->promo_credit_usd > 0) {
            $account->promo_remaining_usd = (float) $account->promo_credit_usd;
        }

        if (!$account->promo_started_at) {
            $account->promo_started_at = now();
        }

        if (!$account->promo_expires_at) {
            $days = (int) config('linode.promo_days', 60);
            $account->promo_expires_at = $account->promo_started_at->copy()->addDays($days);
        }

        $this->recalculateReserved($account);
        $this->updateFullFlag($account);

        $account->last_synced_at = now();
        $account->sync_error = null;
        $account->save();
    }

    public function updateFullFlag(LinodeAccount $account): void
    {
        $available = $account->creditAvailableUsd();
        $minPlan = $this->minPlanMonthlyUsd();
        $account->is_full = $available < $minPlan;

        if ($account->promo_expires_at && $account->promo_expires_at->isPast()) {
            $account->is_full = true;
        }
    }

    public function canAfford(
        LinodeAccount $account,
        float $monthlyCostUsd,
        int $customerMonths
    ): array {
        $ratio     = (float) config('linode.budget_safety_ratio', 0.85);
        $remaining = (float) ($account->promo_remaining_usd ?? $account->promo_credit_usd);
        $reserved  = (float) $account->reserved_monthly_usd;

        // Kiểm tra promo còn hạn
        if ($account->promo_expires_at && $account->promo_expires_at->isPast()) {
            return [
                'ok'        => false,
                'reason'    => 'PROMO_EXPIRED',
                'message'   => 'Promo account đã hết hạn.',
                'available' => 0,
                'required'  => $monthlyCostUsd * $customerMonths,
            ];
        }

        // Số tiền thực sự cần: chi phí Linode * số tháng khách đặt
        $required  = $monthlyCostUsd * max(1, $customerMonths);

        // Credit khả dụng = (còn lại * safety ratio) - đã reserved
        $available = max(0, ($remaining * $ratio) - $reserved);

        if ($available < $required) {
            return [
                'ok'        => false,
                'reason'    => 'INSUFFICIENT_CREDIT',
                'message'   => 'Credit account không đủ cho gói này.',
                'available' => round($available, 2),
                'required'  => round($required, 2),
            ];
        }

        return [
            'ok'        => true,
            'available' => round($available, 2),
            'required'  => round($required, 2),
        ];
    }

    public function slotsEstimate(LinodeAccount $account, float $planMonthlyUsd): int
    {
        if ($planMonthlyUsd <= 0) {
            return 0;
        }

        return (int) floor($account->creditAvailableUsd() / $planMonthlyUsd);
    }

    private function minPlanMonthlyUsd(): float
    {
        $plans = config('linode.plans', []);
        $min = PHP_FLOAT_MAX;

        foreach ($plans as $plan) {
            $cost = (float) ($plan['cost_monthly_usd'] ?? 0);
            if ($cost > 0 && $cost < $min) {
                $min = $cost;
            }
        }

        return $min === PHP_FLOAT_MAX ? 5.0 : $min;
    }
}
