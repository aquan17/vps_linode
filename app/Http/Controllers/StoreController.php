<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VpsInstance;
use App\Services\LinodeAccountRouter;
use App\Services\LinodeApiService;
use App\Services\LinodeBudgetService;
use App\Services\LinodePricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\VpsCreated;

class StoreController extends Controller
{
    public function index(LinodePricingService $pricing, LinodeBudgetService $budget)
    {
        $plans    = $pricing->getPlans();
        $accounts = \App\Models\LinodeAccount::where('is_active', true)
                        ->where('is_full', false)
                        ->get();

        // Max USD account có thể chứa thêm
        $maxAvailableUsd = $accounts->map(fn($a) => $a->creditAvailableUsd())->max() ?? 0;

        // Gói nào cost <= maxAvailableUsd thì đang có slot
        $availablePlanIds = collect($plans)
            ->filter(fn($p) => (float)$p['cost_monthly_usd'] <= $maxAvailableUsd)
            ->keys()
            ->all();

        // Calculate max allowed duration based on promo expiration
        $maxDaysLeft = $accounts->map(function($a) {
            return $a->promo_expires_at ? now()->diffInDays($a->promo_expires_at, false) : 999;
        })->max() ?? 0;

        $durations = config('linode.durations');
        if ($maxDaysLeft < 60) {
            unset($durations[2]); // Remove 2 months option if no account has >= 60 days
        }

        return view('store.index', [
            'plans'            => $plans,
            'regions'          => $pricing->getRegions(),
            'durations'        => $durations,
            'availablePlanIds' => $availablePlanIds,
            'maxAvailableUsd'  => round($maxAvailableUsd, 2),
        ]);
    }

    public function create(string $plan, LinodePricingService $pricing)
    {
        $planData = $pricing->getPlan($plan);
        if (!$planData) {
            abort(404);
        }

        $accounts = \App\Models\LinodeAccount::where('is_active', true)
                        ->where('is_full', false)
                        ->get();
        $maxDaysLeft = $accounts->map(function($a) {
            return $a->promo_expires_at ? now()->diffInDays($a->promo_expires_at, false) : 999;
        })->max() ?? 0;

        $durations = config('linode.durations');
        if ($maxDaysLeft < 60) {
            unset($durations[2]);
        }

        return view('store.create', [
            'planId'    => $plan,
            'plan'      => $planData,
            'regions'   => $pricing->getRegions(),
            'durations' => $durations,
            'images'    => config('linode.images'),
        ]);
    }

    public function store(
        Request $request,
        LinodePricingService $pricing,
        LinodeAccountRouter $router,
        LinodeApiService $api,
        LinodeBudgetService $budget
    ) {
        $plans = array_keys($pricing->getPlans());
        $regions = array_keys($pricing->getRegions());

        $imageKeys = array_keys(config('linode.images', []));

        $validated = $request->validate([
            'plan'     => 'required|in:' . implode(',', $plans),
            'region'   => 'required|in:' . implode(',', $regions),
            'duration' => 'required|in:1,2',
            'label'    => 'required|string|min:3|max:32|regex:/^[a-zA-Z0-9\-]+$/',
            'image'    => 'required|in:' . implode(',', $imageKeys),
        ]);

        $plan = $pricing->getPlan($validated['plan']);
        $months = (int) $validated['duration'];
        $totalPrice = $pricing->calculatePrice($plan, $months);
        $costUsd = $pricing->costMonthlyUsd($plan);
        $user = Auth::user();

        if ($user->balance < $totalPrice) {
            return back()->withInput()->with('error', 'Số dư không đủ. Vui lòng nạp thêm tiền.');
        }

        $imageConfig = config("linode.images.{$validated['image']}");
        $isWindows = !empty($imageConfig['is_clone']);

        $account = $router->pickForPlan($costUsd, $months, $isWindows);
        if (!$account) {
            return back()->withInput()->with('error', 'Tạm hết slot trên hệ thống. Vui lòng thử lại sau hoặc chọn gói nhỏ hơn.');
        }

        $password = $isWindows ? 'Anhyeuem@' : Str::random(14);
        $expiresAt = Carbon::now()->addMonths($months);

        try {
            $vps = DB::transaction(function () use ($user, $account, $validated, $plan, $totalPrice, $costUsd, $password, $expiresAt) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                if ($lockedUser->balance < $totalPrice) {
                    throw new \RuntimeException('INSUFFICIENT_BALANCE');
                }

                $lockedUser->decrement('balance', $totalPrice);

                return VpsInstance::create([
                    'user_id' => $lockedUser->id,
                    'linode_account_id' => $account->id,
                    'label' => $validated['label'],
                    'root_password' => $password,
                    'region' => $validated['region'],
                    'linode_type' => $plan['linode_type'],
                    'plan_id' => $validated['plan'],
                    'status' => 'Đang khởi tạo...',
                    'cpu' => $plan['cores'],
                    'ram' => $plan['ram'],
                    'disk' => $plan['disk'],
                    'cost_monthly_usd' => $costUsd,
                    'paid_amount' => $totalPrice,
                    'expires_at' => $expiresAt,
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'INSUFFICIENT_BALANCE') {
                return back()->withInput()->with('error', 'Số dư không đủ.');
            }
            throw $e;
        }

        try {
            $api->setToken($account->api_token);
            
            if ($isWindows) {
                if (!$account->windows_template_id) {
                    throw new \RuntimeException('Lỗi hệ thống: Tài khoản không được cấu hình VPS mẫu Windows.');
                }
                $remote = $api->cloneInstance(
                    $account->windows_template_id,
                    $plan['linode_type'],
                    $validated['region'],
                    $validated['label']
                );
            } else {
                $remote = $api->createInstance(
                    $plan['linode_type'],
                    $validated['region'],
                    $validated['label'],
                    $password,
                    $validated['image']          // ← OS được user chọn
                );
            }

            $hourly = isset($remote['type']) ? null : null;
            try {
                $typeInfo = $api->getType($plan['linode_type']);
                $hourly = (float) ($typeInfo['price']['hourly'] ?? 0);
            } catch (\Throwable $ignored) {
            }

            $remoteStatus = $remote['status'] ?? ($isWindows ? 'cloning' : 'provisioning');
            $statusMap = [
                'running'      => 'Sẵn sàng',
                'offline'      => 'Đã tắt',
                'booting'      => 'Đang khởi động',
                'provisioning' => 'Đang khởi tạo...',
                'cloning'      => 'Đang nhân bản...',
                'rebuilding'   => 'Đang rebuild...',
            ];
            $mappedStatus = $statusMap[$remoteStatus] ?? 'Đang khởi tạo...';

            $vps->update([
                'linode_id'        => $remote['id'] ?? null,
                'public_ip'        => $remote['ipv4'][0] ?? null,
                'status'           => $mappedStatus,
                'hourly_price_usd' => $hourly,
            ]);

            $budget->recalculateReserved($account->fresh());
            $budget->updateFullFlag($account->fresh());
        } catch (\Throwable $e) {
            Log::error('Linode create failed', ['vps_id' => $vps->id, 'msg' => $e->getMessage()]);
            $vps->update(['status' => 'Lỗi: ' . substr($e->getMessage(), 0, 120)]);

            DB::transaction(function () use ($user, $totalPrice, $vps) {
                User::where('id', $user->id)->increment('balance', $totalPrice);
                $vps->update(['status' => 'Lỗi — đã hoàn tiền']);
            });

            return redirect()->route('dashboard')->with('error', 'Tạo VPS thất bại, đã hoàn tiền: ' . $e->getMessage());
        }

        try {
            Mail::to($user->email)->send(new VpsCreated($vps, $password));
        } catch (\Throwable $e) {
            Log::error('Failed to send VPS created email', ['vps_id' => $vps->id, 'msg' => $e->getMessage()]);
        }

        return redirect()->route('dashboard.show', $vps)->with('success', 'VPS đã được tạo thành công! (Thông tin đăng nhập đã được gửi vào Email của bạn)');
    }
}
