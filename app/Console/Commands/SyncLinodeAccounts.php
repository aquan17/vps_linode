<?php

namespace App\Console\Commands;

use App\Models\LinodeAccount;
use App\Services\LinodeApiService;
use App\Services\LinodeBudgetService;
use Illuminate\Console\Command;

class SyncLinodeAccounts extends Command
{
    protected $signature = 'linode:sync-accounts';
    protected $description = 'Đồng bộ credit và reserved từ Linode API';

    public function handle(LinodeApiService $api, LinodeBudgetService $budget): int
    {
        $accounts = LinodeAccount::where('is_active', true)->get();

        foreach ($accounts as $account) {
            try {
                $budget->syncAccountFromApi($account, $api);
                $this->info("OK: {$account->label}");
            } catch (\Throwable $e) {
                $account->sync_error = $e->getMessage();
                $account->save();
                $this->error("FAIL: {$account->label} — {$e->getMessage()}");
            }
        }

        return 0;
    }
}
