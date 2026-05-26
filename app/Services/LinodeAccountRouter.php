<?php

namespace App\Services;

use App\Models\LinodeAccount;
use Illuminate\Support\Facades\DB;

class LinodeAccountRouter
{
    private LinodeBudgetService $budget;

    public function __construct(LinodeBudgetService $budget)
    {
        $this->budget = $budget;
    }

    public function getAvailableAccounts()
    {
        return LinodeAccount::query()
            ->where('is_active', true)
            ->where('is_full', false)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();
    }

    /**
     * Chọn account có đủ credit; khóa row để tránh race khi tạo đồng thời.
     * Thêm check: không nhận đơn nếu promo còn < $minDaysRequired ngày.
     */
    public function pickForPlan(float $monthlyCostUsd, int $customerMonths): ?LinodeAccount
    {
        // Tối thiểu phải còn ít nhất (customerMonths * 30) ngày, nhưng không dưới 7
        $minDaysRequired = max(7, $customerMonths * 30);

        $accounts = $this->getAvailableAccounts();

        foreach ($accounts as $account) {
            $locked = DB::transaction(function () use ($account, $monthlyCostUsd, $customerMonths, $minDaysRequired) {
                $fresh = LinodeAccount::where('id', $account->id)->lockForUpdate()->first();
                if (!$fresh || !$fresh->is_active || $fresh->is_full) {
                    return null;
                }

                // Kiểm tra promo còn đủ ngày không
                if ($fresh->promo_expires_at) {
                    $daysLeft = (int) now()->diffInDays($fresh->promo_expires_at, false);
                    if ($daysLeft < $minDaysRequired) {
                        return null; // Promo quá gần hết, bỏ qua account này
                    }
                }

                $this->budget->recalculateReserved($fresh);
                $check = $this->budget->canAfford($fresh, $monthlyCostUsd, $customerMonths);

                return $check['ok'] ? $fresh : null;
            });

            if ($locked) {
                return $locked;
            }
        }

        return null;
    }

    public function markFull(int $accountId): void
    {
        $account = LinodeAccount::find($accountId);
        if ($account) {
            $account->is_full = true;
            $account->save();
        }
    }
}
