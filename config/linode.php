<?php

return [
    'api_base' => env('LINODE_API_BASE', 'https://api.linode.com/v4'),
    'default_region' => env('LINODE_DEFAULT_REGION', 'sg-sin-2'),
    'default_image' => env('LINODE_DEFAULT_IMAGE', 'linode/ubuntu22.04'),
    'usd_vnd_rate' => (int) env('LINODE_USD_VND_RATE', 25500),
    'promo_credit_usd' => (float) env('LINODE_PROMO_CREDIT_USD', 100),
    'promo_days' => (int) env('LINODE_PROMO_DAYS', 60),
    'budget_safety_ratio' => (float) env('LINODE_BUDGET_SAFETY', 0.90),

    'regions' => [
        'sg-sin-2'     => 'Singapore',
        'ap-south'     => 'Singapore (Legacy)',
        'ap-northeast' => 'Tokyo',
        'jp-osa'       => 'Osaka',
        'id-cgk'       => 'Jakarta',
        'in-bom-2'     => 'Mumbai',
        'us-east'      => 'US East (Newark)',
        'us-west'      => 'US West (Fremont)',
        'us-central'   => 'US Central (Dallas)',
        'us-lax'       => 'US West (Los Angeles)',
        'us-mia'       => 'US East (Miami)',
        'eu-west'      => 'London',
    ],

    /**
     * Gói bán lẻ — chỉ type có giá Linode <= $100/tháng (giá gốc US).
     * cost_monthly_usd: chi phí infrastructure; price_per_month: VND bán cho khách.
     */
    'plans' => [
        'nano' => [
            'name' => 'Nano S1',
            'desc' => 'Web nhẹ, học tập, bot đơn giản',
            'linode_type' => 'g6-nanode-1',
            'cores' => 1,
            'ram' => 1,
            'disk' => 25,
            'transfer_tb' => 1,
            'network_out_mbps' => 1000,
            'cost_monthly_usd' => 5.0,
            'price_per_month' => 45000,
            'badge' => 'Tiết kiệm',
        ],
        'starter' => [
            'name' => 'Starter L2',
            'desc' => 'WordPress, panel, tool nhỏ',
            'linode_type' => 'g6-standard-1',
            'cores' => 1,
            'ram' => 2,
            'disk' => 50,
            'transfer_tb' => 2,
            'network_out_mbps' => 2000,
            'cost_monthly_usd' => 12.0,
            'price_per_month' => 69000,
            'badge' => null,
        ],
        'pro' => [
            'name' => 'Pro L4',
            'desc' => 'Sản xuất, API, game proxy nhẹ',
            'linode_type' => 'g6-standard-2',
            'cores' => 2,
            'ram' => 4,
            'disk' => 80,
            'transfer_tb' => 4,
            'network_out_mbps' => 4000,
            'cost_monthly_usd' => 24.0,
            'price_per_month' => 129000,
            'badge' => 'Phổ biến',
        ],
        'business' => [
            'name' => 'Business L8',
            'desc' => 'Dự án lớn, database, nhiều traffic',
            'linode_type' => 'g6-standard-4',
            'cores' => 4,
            'ram' => 8,
            'disk' => 160,
            'transfer_tb' => 5,
            'network_out_mbps' => 5000,
            'cost_monthly_usd' => 48.0,
            'price_per_month' => 219000,
            'badge' => 'Hiệu năng cao',
        ],
    ],

    'durations' => [
        1 => '1 tháng',
        2 => '2 tháng',
    ],

    /**
     * Danh sách OS images được phép chọn.
     * Key = Linode image slug, Value = tên hiển thị
     */
    'images' => [
        'linode/ubuntu24.04'  => ['label' => 'Ubuntu 24.04 LTS',    'icon' => '🐧', 'group' => 'Ubuntu'],
        'linode/ubuntu22.04'  => ['label' => 'Ubuntu 22.04 LTS',    'icon' => '🐧', 'group' => 'Ubuntu', 'default' => true],
        'linode/ubuntu20.04'  => ['label' => 'Ubuntu 20.04 LTS',    'icon' => '🐧', 'group' => 'Ubuntu'],
        'linode/debian12'     => ['label' => 'Debian 12 (Bookworm)', 'icon' => '🌀', 'group' => 'Debian'],
        'linode/debian11'     => ['label' => 'Debian 11 (Bullseye)', 'icon' => '🌀', 'group' => 'Debian'],
        'linode/almalinux8'   => ['label' => 'AlmaLinux 8',          'icon' => '🔴', 'group' => 'RHEL'],
        'linode/almalinux9'   => ['label' => 'AlmaLinux 9',          'icon' => '🔴', 'group' => 'RHEL'],
        'linode/rocky9'       => ['label' => 'Rocky Linux 9',        'icon' => '🪨', 'group' => 'RHEL'],
        'linode/centos-stream9' => ['label' => 'CentOS Stream 9',    'icon' => '🎩', 'group' => 'RHEL'],
        'linode/arch'         => ['label' => 'Arch Linux',            'icon' => '🏹', 'group' => 'Other'],
        'linode/fedora40'     => ['label' => 'Fedora 40',             'icon' => '🎩', 'group' => 'Other'],
        'windows-2012'        => ['label' => 'Windows Server 2012',   'icon' => '🪟', 'group' => 'Windows', 'is_clone' => true],
    ],
];
