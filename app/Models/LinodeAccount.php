<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinodeAccount extends Model
{
    protected $fillable = [
        'label',
        'email',
        'api_token',
        'is_active',
        'is_full',
        'promo_credit_usd',
        'promo_started_at',
        'promo_expires_at',
        'balance_usd',
        'promo_remaining_usd',
        'reserved_monthly_usd',
        'priority',
        'last_synced_at',
        'sync_error',
    ];

    protected $casts = [
        'api_token' => 'encrypted',
        'is_active' => 'boolean',
        'is_full' => 'boolean',
        'promo_credit_usd' => 'decimal:2',
        'promo_started_at' => 'datetime',
        'promo_expires_at' => 'datetime',
        'balance_usd' => 'decimal:4',
        'promo_remaining_usd' => 'decimal:4',
        'reserved_monthly_usd' => 'decimal:4',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'api_token',
    ];

    public function instances()
    {
        return $this->hasMany(VpsInstance::class);
    }

    public function activeInstances()
    {
        return $this->instances()->whereNotIn('status', ['Lỗi', 'Đã xóa', 'Lỗi API']);
    }

    public function creditAvailableUsd(): float
    {
        $remaining = (float) ($this->promo_remaining_usd ?? $this->promo_credit_usd);
        $reserved = (float) $this->reserved_monthly_usd;
        $ratio = (float) config('linode.budget_safety_ratio', 0.95);

        return max(0, ($remaining * $ratio) - $reserved);
    }
}
