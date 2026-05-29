<?php

namespace App\Console\Commands;

use App\Models\VpsInstance;
use App\Services\LinodeApiService;
use Illuminate\Console\Command;

class SyncVpsStatus extends Command
{
    protected $signature = 'linode:sync-status';
    protected $description = 'Kiểm tra trạng thái các VPS đang khởi tạo hoặc clone và boot nếu cần';

    public function handle(LinodeApiService $api): int
    {
        // Lấy các VPS đang ở trạng thái chưa sẵn sàng
        $instances = VpsInstance::whereIn('status', ['Đang khởi tạo...', 'Đang nhân bản...', 'Đã tắt', 'Đang khởi động'])
            ->whereNotNull('linode_id')
            ->get();

        foreach ($instances as $vps) {
            try {
                if (!$vps->linodeAccount || !$vps->linodeAccount->api_token) {
                    continue;
                }

                $api->setToken($vps->linodeAccount->api_token);
                $remote = $api->getInstance($vps->linode_id);
                $remoteStatus = $remote['status'] ?? null;

                if (!$remoteStatus) {
                    continue;
                }

                $statusMap = [
                    'running'      => 'Sẵn sàng',
                    'offline'      => 'Đã tắt',
                    'booting'      => 'Đang khởi động',
                    'provisioning' => 'Đang khởi tạo...',
                    'cloning'      => 'Đang nhân bản...',
                    'rebuilding'   => 'Đang rebuild...',
                ];

                $mappedStatus = $statusMap[$remoteStatus] ?? 'Không rõ';

                // Nếu Linode báo offline nhưng thực ra vừa mới clone xong, mình sẽ boot nó lên
                if ($remoteStatus === 'offline' && in_array($vps->status, ['Đang khởi tạo...', 'Đang nhân bản...', 'Đã tắt'])) {
                    $this->info("Booting offline clone VPS ID: {$vps->id}");
                    $api->bootInstance($vps->linode_id);
                    $mappedStatus = 'Đang khởi động';
                }

                if ($vps->status !== $mappedStatus) {
                    $vps->update(['status' => $mappedStatus]);
                    $this->info("Updated VPS ID: {$vps->id} to status: {$mappedStatus}");
                }

            } catch (\Throwable $e) {
                $this->error("Lỗi khi kiểm tra VPS ID {$vps->id}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
