<?php

namespace App\Http\Controllers;

use App\Models\LinodeAccount;
use App\Services\LinodeBudgetService;
use App\Services\LinodePricingService;

class HomeController extends Controller
{
    public function index(LinodePricingService $pricing, LinodeBudgetService $budget)
    {
        $plans    = $pricing->getPlans();
        $accounts = LinodeAccount::where('is_active', true)
                        ->where('is_full', false)
                        ->get();

        $maxAvailableUsd = $accounts->map(fn($a) => $a->creditAvailableUsd())->max() ?? 0;

        $availablePlanIds = collect($plans)
            ->filter(fn($p) => (float) $p['cost_monthly_usd'] <= $maxAvailableUsd)
            ->keys()
            ->all();

        return view('home', [
            'plans'            => $plans,
            'regions'          => $pricing->getRegions(),
            'availablePlanIds' => $availablePlanIds,
        ]);
    }
}
