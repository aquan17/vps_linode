<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LinodeAccount;
use App\Services\LinodeApiService;
use App\Services\LinodeBudgetService;
use App\Services\LinodePricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LinodeAccountController extends Controller
{
    public function index(LinodePricingService $pricing, LinodeBudgetService $budget)
    {
        $accounts = LinodeAccount::withCount([
            'instances as active_count' => function ($q) {
                $q->whereNotIn('status', ['Lỗi', 'Đã xóa', 'Lỗi API']);
            },
        ])->orderBy('priority')->orderBy('id')->get();

        $minPlanUsd = 5.0;
        foreach ($pricing->getPlans() as $p) {
            $c = (float) $p['cost_monthly_usd'];
            if ($c < $minPlanUsd) {
                $minPlanUsd = $c;
            }
        }

        $rows = $accounts->map(function (LinodeAccount $acc) use ($budget, $minPlanUsd) {
            $budget->recalculateReserved($acc);
            $available = $acc->creditAvailableUsd();
            $slots = $budget->slotsEstimate($acc, $minPlanUsd);

            return [
                'model' => $acc,
                'available_usd' => $available,
                'slots_nano' => $slots,
                'used_pct' => $acc->promo_credit_usd > 0
                    ? min(100, round(($acc->reserved_monthly_usd / $acc->promo_credit_usd) * 100))
                    : 0,
            ];
        });

        return view('admin.accounts.index', [
            'rows' => $rows,
            'stats' => [
                'active' => $accounts->where('is_active', true)->where('is_full', false)->count(),
                'full' => $accounts->where('is_full', true)->count(),
                'total_reserved' => $accounts->sum('reserved_monthly_usd'),
            ],
        ]);
    }

    public function store(Request $request, LinodeApiService $api, LinodeBudgetService $budget)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'api_token' => 'required|string|min:20',
            'promo_credit_usd' => 'nullable|numeric|min:0|max:500',
            'promo_days' => 'nullable|integer|min:1|max:365',
            'priority' => 'nullable|integer|min:0|max:999',
        ]);

        try {
            $api->setToken($data['api_token']);
            $accountInfo = $api->getAccount();
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Token không hợp lệ: ' . $e->getMessage());
        }

        $days = (int) ($data['promo_days'] ?? config('linode.promo_days', 60));
        $credit = (float) ($data['promo_credit_usd'] ?? config('linode.promo_credit_usd', 100));

        $account = LinodeAccount::create([
            'label' => $data['label'],
            'email' => $accountInfo['email'] ?? null,
            'api_token' => $data['api_token'],
            'is_active' => true,
            'is_full' => false,
            'promo_credit_usd' => $credit,
            'promo_remaining_usd' => $credit,
            'promo_started_at' => now(),
            'promo_expires_at' => now()->addDays($days),
            'priority' => (int) ($data['priority'] ?? 0),
        ]);

        try {
            $budget->syncAccountFromApi($account, $api);
        } catch (\Throwable $e) {
            $account->sync_error = $e->getMessage();
            $account->save();
        }

        return back()->with('success', 'Đã thêm account [' . $account->label . '] thành công.');
    }

    public function sync(LinodeAccount $account, LinodeApiService $api, LinodeBudgetService $budget)
    {
        try {
            $budget->syncAccountFromApi($account, $api);
            return back()->with('success', 'Đồng bộ account thành công.');
        } catch (\Throwable $e) {
            $account->sync_error = $e->getMessage();
            $account->save();
            return back()->with('error', 'Đồng bộ lỗi: ' . $e->getMessage());
        }
    }

    public function syncAll(LinodeApiService $api, LinodeBudgetService $budget)
    {
        $ok = 0;
        $fail = 0;

        foreach (LinodeAccount::all() as $account) {
            try {
                $budget->syncAccountFromApi($account, $api);
                $ok++;
            } catch (\Throwable $e) {
                $account->sync_error = $e->getMessage();
                $account->save();
                $fail++;
            }
        }

        return back()->with('success', "Đồng bộ xong: {$ok} OK, {$fail} lỗi.");
    }

    public function toggle(Request $request, LinodeAccount $account)
    {
        $field = $request->input('field');
        if (!in_array($field, ['is_active', 'is_full'], true)) {
            return back()->with('error', 'Trường không hợp lệ.');
        }

        $account->$field = !$account->$field;
        $account->save();

        return back()->with('success', 'Đã cập nhật account.');
    }

    public function edit(LinodeAccount $account)
    {
        return view('admin.accounts.edit', compact('account'));
    }

    public function update(Request $request, LinodeAccount $account)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'api_token' => 'required|string|min:20',
            'promo_credit_usd' => 'nullable|numeric|min:0',
            'promo_days' => 'nullable|integer|min:0',
            'priority' => 'nullable|integer|min:0|max:999',
            'is_active' => 'boolean',
            'is_full' => 'boolean',
            'promo_expires_at' => 'nullable|date',
        ]);

        $account->update([
            'label' => $data['label'],
            'api_token' => $data['api_token'],
            'promo_credit_usd' => $data['promo_credit_usd'] ?? $account->promo_credit_usd,
            'priority' => (int) ($data['priority'] ?? 0),
            'is_active' => $request->has('is_active'),
            'is_full' => $request->has('is_full'),
            'promo_expires_at' => $data['promo_expires_at'] ?? $account->promo_expires_at,
        ]);

        return redirect()->route('admin.accounts.index')->with('success', 'Đã cập nhật thông tin tài khoản thành công.');
    }

    public function destroy(LinodeAccount $account)
    {
        if ($account->activeInstances()->exists()) {
            return back()->with('error', 'Không xóa account còn VPS đang chạy.');
        }

        $account->delete();
        return back()->with('success', 'Đã xóa account.');
    }
}
