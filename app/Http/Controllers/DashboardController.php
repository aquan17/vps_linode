<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VpsInstance;
use App\Services\LinodeApiService;
use App\Services\LinodeBudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = VpsInstance::with('linodeAccount')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%")
                  ->orWhere('public_ip', 'like', "%{$search}%");
            });
        }

        $instances = $query->paginate(12)->appends($request->all());

        return view('dashboard.index', compact('instances'));
    }

    public function show(VpsInstance $vps)
    {
        $this->authorizeVps($vps);
        return view('dashboard.show', compact('vps'));
    }

    // ----------------------------------------------------------------
    // JSON status polling
    // ----------------------------------------------------------------
    public function statusJson(VpsInstance $vps, LinodeApiService $api): JsonResponse
    {
        $this->authorizeVps($vps);

        if (!$vps->linode_id || !$vps->linodeAccount) {
            return response()->json([
                'status'    => $vps->status,
                'public_ip' => $vps->public_ip,
                'ready'     => false,
            ]);
        }

        try {
            $api->setToken($vps->linodeAccount->api_token);
            $remote = $api->getInstance((int) $vps->linode_id);

            $remoteStatus = $remote['status'] ?? '';
            $ip           = $remote['ipv4'][0] ?? $vps->public_ip;
            $mapped       = $this->mapStatus($remoteStatus);
            $ready        = $remoteStatus === 'running';

            if ($vps->status !== $mapped || ($ip && $vps->public_ip !== $ip)) {
                $vps->update(['status' => $mapped, 'public_ip' => $ip]);
            }

            return response()->json([
                'status'     => $mapped,
                'raw_status' => $remoteStatus,
                'public_ip'  => $ip,
                'ready'      => $ready,
            ]);
        } catch (\Throwable $e) {
            Log::warning('VPS statusJson failed', ['id' => $vps->id, 'msg' => $e->getMessage()]);
            return response()->json([
                'status'    => $vps->status,
                'public_ip' => $vps->public_ip,
                'ready'     => false,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    // ----------------------------------------------------------------
    // Manual sync
    // ----------------------------------------------------------------
    public function sync(VpsInstance $vps, LinodeApiService $api)
    {
        $this->authorizeVps($vps);

        if (!$vps->linodeAccount || !$vps->linode_id) {
            return back()->with('error', 'VPS chưa liên kết Linode API.');
        }

        try {
            $api->setToken($vps->linodeAccount->api_token);
            $remote = $api->getInstance((int) $vps->linode_id);
            $vps->update([
                'status'    => $this->mapStatus($remote['status'] ?? ''),
                'public_ip' => $remote['ipv4'][0] ?? $vps->public_ip,
            ]);

            return back()->with('success', 'Đã đồng bộ trạng thái VPS.');
        } catch (\Throwable $e) {
            Log::warning('VPS sync failed', ['id' => $vps->id, 'msg' => $e->getMessage()]);
            return back()->with('error', 'Đồng bộ thất bại: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Reboot
    // ----------------------------------------------------------------
    public function reboot(VpsInstance $vps, LinodeApiService $api)
    {
        $this->authorizeVps($vps);
        $this->requireLinodeLink($vps);

        try {
            $api->setToken($vps->linodeAccount->api_token);
            $api->rebootInstance((int) $vps->linode_id);
            $vps->update(['status' => 'Đang khởi động lại']);

            return back()->with('success', 'Đã gửi lệnh reboot. VPS sẽ sẵn sàng sau 1–2 phút.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Reboot thất bại: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Shutdown (power off)
    // ----------------------------------------------------------------
    public function shutdown(VpsInstance $vps, LinodeApiService $api)
    {
        $this->authorizeVps($vps);
        $this->requireLinodeLink($vps);

        try {
            $api->setToken($vps->linodeAccount->api_token);
            $api->shutdownInstance((int) $vps->linode_id);
            $vps->update(['status' => 'Đang tắt']);

            return back()->with('success', 'Đã gửi lệnh tắt nguồn VPS.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Tắt nguồn thất bại: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Boot (power on)
    // ----------------------------------------------------------------
    public function boot(VpsInstance $vps, LinodeApiService $api)
    {
        $this->authorizeVps($vps);
        $this->requireLinodeLink($vps);

        try {
            $api->setToken($vps->linodeAccount->api_token);
            $api->bootInstance((int) $vps->linode_id);
            $vps->update(['status' => 'Đang khởi động']);

            return back()->with('success', 'Đã gửi lệnh bật nguồn VPS.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Bật nguồn thất bại: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Đổi root password
    // VPS phải đang OFF — Linode API yêu cầu
    // ----------------------------------------------------------------
    public function changePassword(Request $request, VpsInstance $vps, LinodeApiService $api)
    {
        $this->authorizeVps($vps);
        $this->requireLinodeLink($vps);

        $request->validate([
            'new_password' => 'required|string|min:11|max:128',
        ]);

        try {
            $api->setToken($vps->linodeAccount->api_token);

            // Tắt VPS trước nếu đang chạy
            $remote = $api->getInstance((int) $vps->linode_id);
            if (($remote['status'] ?? '') === 'running') {
                $api->shutdownInstance((int) $vps->linode_id);
                // Chờ tối đa 60s cho VPS tắt
                for ($i = 0; $i < 12; $i++) {
                    sleep(5);
                    $check = $api->getInstance((int) $vps->linode_id);
                    if (($check['status'] ?? '') === 'offline') break;
                }
            }

            $api->resetPassword((int) $vps->linode_id, $request->new_password);

            // Lưu password mới vào DB (đã encrypt)
            $vps->update(['root_password' => $request->new_password]);

            // Bật lại VPS
            $api->bootInstance((int) $vps->linode_id);
            $vps->update(['status' => 'Đang khởi động']);

            return back()->with('success', 'Đổi mật khẩu thành công. VPS đang được khởi động lại.');
        } catch (\Throwable $e) {
            Log::error('VPS changePassword failed', ['id' => $vps->id, 'msg' => $e->getMessage()]);
            return back()->with('error', 'Đổi mật khẩu thất bại: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Rebuild (cài lại OS — mất toàn bộ data)
    // ----------------------------------------------------------------
    public function rebuild(VpsInstance $vps, LinodeApiService $api)
    {
        $this->authorizeVps($vps);
        $this->requireLinodeLink($vps);

        try {
            $newPass = Str::random(16);
            $api->setToken($vps->linodeAccount->api_token);
            $api->rebuildInstance(
                (int) $vps->linode_id,
                config('linode.default_image'),
                $newPass
            );

            $vps->update([
                'root_password' => $newPass,
                'status'        => 'Đang rebuild...',
            ]);

            return back()->with('success', 'Đã gửi lệnh rebuild. VPS sẽ được cài lại từ đầu, mật khẩu mới đã lưu trong trang chi tiết.');
        } catch (\Throwable $e) {
            Log::error('VPS rebuild failed', ['id' => $vps->id, 'msg' => $e->getMessage()]);
            return back()->with('error', 'Rebuild thất bại: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Xóa VPS — xóa trên Linode, hoàn tiền theo ngày còn lại
    // ----------------------------------------------------------------
    public function destroy(
        VpsInstance $vps,
        LinodeApiService $api,
        LinodeBudgetService $budget
    ) {
        $this->authorizeVps($vps);

        try {
            // Xóa trên Linode nếu còn tồn tại
            if ($vps->linode_id && $vps->linodeAccount) {
                $api->setToken($vps->linodeAccount->api_token);
                try {
                    $api->deleteInstance((int) $vps->linode_id);
                } catch (\Throwable $apiErr) {
                    // Nếu instance không tồn tại trên Linode (đã xóa thủ công) → tiếp tục
                    Log::warning('Linode delete skipped', ['id' => $vps->id, 'msg' => $apiErr->getMessage()]);
                }

                // Cập nhật budget account
                try {
                    $budget->recalculateReserved($vps->linodeAccount->fresh());
                    $budget->updateFullFlag($vps->linodeAccount->fresh());
                } catch (\Throwable $ignored) {}
            }

            // Tính hoàn tiền theo ngày còn lại
            $refund = $this->calcRefund($vps);

            DB::transaction(function () use ($vps, $refund) {
                if ($refund > 0) {
                    User::where('id', $vps->user_id)->increment('balance', $refund);
                }
                $vps->delete();
            });

            $msg = 'Đã xóa VPS thành công.';
            if ($refund > 0) {
                $msg .= ' Hoàn tiền: ' . number_format($refund) . ' đ (theo ngày còn lại).';
            }

            return redirect()->route('dashboard')->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error('VPS destroy failed', ['id' => $vps->id, 'msg' => $e->getMessage()]);
            return back()->with('error', 'Xóa VPS thất bại: ' . $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------
    private function authorizeVps(VpsInstance $vps): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($vps->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }
    }

    private function requireLinodeLink(VpsInstance $vps): void
    {
        if (!$vps->linodeAccount || !$vps->linode_id) {
            abort(400, 'VPS chưa liên kết Linode API.');
        }
    }

    /**
     * Hoàn tiền tỷ lệ ngày còn lại trong tháng đã thanh toán.
     * Chỉ tính nếu còn > 3 ngày.
     */
    private function calcRefund(VpsInstance $vps): int
    {
        if (!$vps->expires_at || !$vps->paid_amount || !$vps->created_at) {
            return 0;
        }

        $now        = now();
        $totalDays  = max(1, $vps->created_at->diffInDays($vps->expires_at));
        $remaining  = max(0, $now->diffInDays($vps->expires_at, false)); // âm = đã hết hạn

        if ($remaining <= 3) return 0;

        return (int) round(($remaining / $totalDays) * $vps->paid_amount);
    }

    private function mapStatus(string $status): string
    {
        $map = [
            'running'       => 'Sẵn sàng',
            'offline'       => 'Đã tắt',
            'booting'       => 'Đang khởi động',
            'shutting_down' => 'Đang tắt',
            'rebooting'     => 'Đang khởi động lại',
            'provisioning'  => 'Đang khởi tạo...',
            'migrating'     => 'Đang migration...',
            'rebuilding'    => 'Đang rebuild...',
            'cloning'       => 'Đang clone...',
            'restoring'     => 'Đang restore...',
        ];

        return $map[$status] ?? $status;
    }
}
