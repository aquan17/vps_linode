<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VpsInstance;
use App\Mail\VpsExpiring;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CheckExpiringVps extends Command
{
    protected $signature = 'linode:check-expiring';
    protected $description = 'Kiểm tra và gửi email cảnh báo các VPS sắp hết hạn (còn 3 ngày)';

    public function handle()
    {
        $this->info('Starting to check expiring VPS instances...');

        // Lấy các VPS đang active và có ngày hết hạn trong khoảng 3 ngày (từ 2 đến 3 ngày tới)
        // Để tránh gửi nhiều lần, ta có thể giới hạn đúng ngày thứ 3, hoặc check khoảng thời gian.
        // Cách tốt nhất là dùng whereDate để tìm chính xác ngày hết hạn = hôm nay + 3 ngày
        
        $targetDate = now()->addDays(3)->toDateString();

        $instances = VpsInstance::whereIn('status', ['Sẵn sàng', 'Đang chạy', 'Đã tắt'])
            ->whereDate('expires_at', $targetDate)
            ->with('user')
            ->get();

        $count = 0;
        foreach ($instances as $vps) {
            if ($vps->user && $vps->user->email) {
                try {
                    Mail::to($vps->user->email)->send(new VpsExpiring($vps, 3));
                    $this->info("Sent expiration warning to {$vps->user->email} for VPS ID: {$vps->id}");
                    $count++;
                } catch (\Exception $e) {
                    Log::error("Failed to send expiration email to {$vps->user->email} for VPS ID: {$vps->id}. Error: " . $e->getMessage());
                }
            }
        }

        $this->info("Completed. Sent {$count} warning emails.");
        return 0;
    }
}
