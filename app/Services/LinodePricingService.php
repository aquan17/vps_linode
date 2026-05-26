<?php

namespace App\Services;

class LinodePricingService
{
    public function getPlans(): array
    {
        return config('linode.plans', []);
    }

    public function getPlan(string $planId): ?array
    {
        return $this->getPlans()[$planId] ?? null;
    }

    public function getRegions(): array
    {
        return config('linode.regions', []);
    }

    public function calculatePrice(array $plan, int $months): int
    {
        return (int) $plan['price_per_month'] * max(1, $months);
    }

    public function costMonthlyUsd(array $plan): float
    {
        return (float) ($plan['cost_monthly_usd'] ?? 0);
    }

    public function formatVnd(int $amount): string
    {
        return number_format($amount, 0, ',', '.') . ' đ';
    }

    public function formatUsd(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }
}
