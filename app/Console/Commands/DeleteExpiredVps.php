<?php

namespace App\Console\Commands;

use App\Models\VpsInstance;
use App\Services\LinodeApiService;
use App\Services\LinodeBudgetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DeleteExpiredVps extends Command
{
    protected $signature = 'vps:delete-expired';
    protected $description = 'Tự động xóa các VPS đã quá hạn (10 phút) trên hệ thống Cloud.';

    public function handle(LinodeApiService $api, LinodeBudgetService $budget)
    {
        // Lấy danh sách VPS đã quá hạn 10 phút, và trạng thái chưa phải là "Đã xóa"
        $expiredVpsList = VpsInstance::where('expires_at', '<=', Carbon::now()->subMinutes(10))
            ->where('status', '!=', 'Đã xóa')
            ->with('linodeAccount')
            ->get();

        if ($expiredVpsList->isEmpty()) {
            return 0;
        }

        foreach ($expiredVpsList as $vps) {
            $this->info("Đang xử lý xóa VPS [{$vps->id}] - {$vps->label}...");

            try {
                if ($vps->linodeAccount && $vps->linode_id) {
                    $api->setToken($vps->linodeAccount->api_token);
                    
                    try {
                        $api->delete('/linode/instances/' . $vps->linode_id);
                        $this->info("Đã xóa trên Cloud API: {$vps->linode_id}");
                    } catch (\Exception $apiErr) {
                        Log::warning('Cloud delete skipped by cron', ['id' => $vps->id, 'msg' => $apiErr->getMessage()]);
                        $this->warn("Lỗi khi xóa trên Cloud (có thể đã bị xóa trước đó): " . $apiErr->getMessage());
                    }
                }
                
                // Cập nhật trạng thái
                $vps->update(['status' => 'Đã xóa']);
                
                // Cập nhật lại số slot full_flag cho account
                if ($vps->linodeAccount) {
                    $budget->updateFullFlag($vps->linodeAccount);
                }

            } catch (\Exception $e) {
                Log::error('Cron delete VPS failed', ['vps_id' => $vps->id, 'msg' => $e->getMessage()]);
                $this->error("Lỗi xóa VPS {$vps->id}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
