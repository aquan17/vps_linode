<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VpsInstance extends Model
{
    protected $fillable = [
        'user_id',
        'linode_account_id',
        'linode_id',
        'label',
        'root_password',
        'region',
        'linode_type',
        'plan_id',
        'status',
        'public_ip',
        'cpu',
        'ram',
        'disk',
        'cost_monthly_usd',
        'hourly_price_usd',
        'paid_amount',
        'expires_at',
    ];

    protected $casts = [
        'root_password' => 'encrypted',
        'cost_monthly_usd' => 'decimal:4',
        'hourly_price_usd' => 'decimal:6',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function linodeAccount()
    {
        return $this->belongsTo(LinodeAccount::class);
    }

    public function isActive(): bool
    {
        return !in_array($this->status, ['Lỗi', 'Đã xóa', 'Lỗi API', 'Hết hạn'], true);
    }

    public function statusBadgeClass(): string
    {
        if (in_array($this->status, ['Sẵn sàng', 'Đang chạy', 'running'], true)) {
            return 'badge-success';
        }
        if (strpos($this->status, 'Lỗi') !== false) {
            return 'badge-danger';
        }
        return 'badge-warning';
    }
}
