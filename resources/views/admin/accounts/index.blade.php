@extends('layouts.app')
@section('title', 'Quản lý Tài khoản Linode — Admin')

@section('breadcrumbs')
    <span>Quản trị</span>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-900">Tài khoản Linode</span>
@endsection

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Kho Tài khoản Linode</h1>
    <p class="text-sm text-gray-500 mt-1">Quản lý Token API, tín dụng khuyến mãi $100 và trạng thái cấp phát tự động.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex items-center">
        <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-600 mr-4">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Tài khoản Hoạt động</p>
            <p class="text-2xl font-extrabold text-gray-900">{{ $stats['active'] }}</p>
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex items-center">
        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-600 mr-4">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
        </div>
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Tài khoản Đã đầy</p>
            <p class="text-2xl font-extrabold text-gray-900">{{ $stats['full'] }}</p>
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex items-center">
        <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 mr-4">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" /></svg>
        </div>
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Dự kiến Chi phí</p>
            <p class="text-2xl font-extrabold text-gray-900">${{ number_format($stats['total_reserved'], 0) }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
    {{-- ═══ TABLE ═══ --}}
    <div class="xl:col-span-3">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden flex flex-col h-full">
            <div class="px-6 py-5 border-b border-gray-200 flex flex-wrap items-center justify-between gap-4 bg-gray-50/50">
                <div class="flex items-center gap-3">
                    <h2 class="text-base font-bold text-gray-900">Danh sách Tài khoản</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-200 text-gray-700">{{ count($rows) }}</span>
                </div>
                <form method="POST" action="{{ route('admin.accounts.sync-all') }}">
                    @csrf
                    <button class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors shadow-sm">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        Đồng bộ Tất cả
                    </button>
                </form>
            </div>

            <div class="hidden lg:block overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Tài khoản</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Khuyến mãi / Đã dùng</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Hết hạn</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">VPS</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Trạng thái</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                            @php
                                $acc      = $row['model'];
                                $pct      = min(100, $row['used_pct']);
                                $daysLeft = $acc->promo_expires_at
                                    ? (int) now()->diffInDays($acc->promo_expires_at, false)
                                    : null;
                                $expired  = $daysLeft !== null && $daysLeft < 0;
                                $urgent   = !$expired && $daysLeft !== null && $daysLeft <= 14;
                                $warn     = !$expired && !$urgent && $daysLeft !== null && $daysLeft <= 30;
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                {{-- Label --}}
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 text-sm mb-0.5">{{ $acc->label }}</div>
                                    <div class="text-xs text-gray-500">{{ $acc->email ?? '—' }}</div>
                                    @if($acc->priority > 0)
                                        <div class="mt-1 text-[10px] font-semibold text-cloud-600 bg-cloud-50 inline-block px-1.5 py-0.5 rounded">Ưu tiên {{ $acc->priority }}</div>
                                    @endif
                                </td>

                                {{-- Credit bar --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-baseline gap-1.5 mb-1.5">
                                        <span class="font-mono font-bold text-sm text-cloud-600">${{ number_format($row['available_usd'], 1) }}</span>
                                        <span class="text-xs text-gray-400 font-medium">có sẵn</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-1.5 mb-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full {{ $pct > 85 ? 'bg-red-500' : ($pct > 65 ? 'bg-amber-500' : 'bg-cloud-500') }}" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <div class="text-[11px] text-gray-500 flex justify-between">
                                        <span>${{ number_format($acc->promo_credit_usd, 0) }} tổng</span>
                                        <span>${{ number_format($acc->reserved_monthly_usd, 0) }} dự kiến</span>
                                    </div>
                                </td>

                                {{-- Expiry --}}
                                <td class="px-6 py-4">
                                    @if($daysLeft === null)
                                        <span class="text-gray-400">—</span>
                                    @elseif($expired)
                                        <div class="text-xs font-bold text-red-600 mb-0.5">ĐÃ HẾT HẠN</div>
                                        <div class="text-xs font-mono text-red-500">{{ $acc->promo_expires_at->format('d/m/Y') }}</div>
                                    @else
                                        <div class="text-sm font-bold mb-0.5 {{ $urgent ? 'text-red-600' : ($warn ? 'text-amber-600' : 'text-green-600') }}">
                                            {{ $daysLeft }} ngày
                                        </div>
                                        <div class="text-xs font-mono text-gray-500">{{ $acc->promo_expires_at->format('d/m/Y') }}</div>
                                        @if($urgent)
                                            <div class="text-[10px] font-bold text-red-600 mt-1 flex items-center gap-1"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg> &lt;14 ngày!</div>
                                        @elseif($warn)
                                            <div class="text-[10px] font-bold text-amber-600 mt-1 flex items-center gap-1"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg> &lt;30 ngày</div>
                                        @endif
                                    @endif
                                </td>

                                {{-- Instances --}}
                                <td class="px-6 py-4 text-center">
                                    <div class="text-sm font-bold text-gray-900">{{ $acc->active_count ?? 0 }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">~{{ $row['slots_nano'] }} slot nano</div>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4">
                                    @if(!$acc->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">Tắt</span>
                                    @elseif($acc->is_full)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Đầy</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-50 text-green-700 border border-green-200">Hoạt động</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4">
                                    <div class="grid grid-cols-2 gap-1 w-[60px] ml-auto">
                                        <a href="{{ route('admin.accounts.edit', $acc) }}" class="flex items-center justify-center w-7 h-7 text-gray-400 hover:text-cloud-600 hover:bg-cloud-50 border border-gray-200 rounded bg-white transition-colors" title="Sửa Tài khoản">
                                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zM16.862 4.487L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                        </a>
                                        <form method="POST" action="{{ route('admin.accounts.sync', $acc) }}" class="m-0">
                                            @csrf
                                            <button class="flex items-center justify-center w-7 h-7 text-gray-400 hover:text-cloud-600 hover:bg-cloud-50 border border-gray-200 rounded bg-white transition-colors" title="Đồng bộ">
                                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.accounts.toggle', $acc) }}" class="m-0">
                                            @csrf
                                            <input type="hidden" name="field" value="is_full">
                                            <button class="flex items-center justify-center w-7 h-7 {{ $acc->is_full ? 'text-green-500 border-green-200 bg-green-50' : 'text-red-400 border-red-200 bg-red-50' }} rounded transition-colors border" title="{{ $acc->is_full ? 'Bỏ đánh dấu Đầy' : 'Đánh dấu Đầy' }}">
                                                @if($acc->is_full)
                                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" /></svg>
                                                @else
                                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                @endif
                                            </button>
                                        </form>
                                        <div class="flex items-center justify-center w-7 h-7 text-gray-200 border border-gray-100 rounded bg-gray-50 border-dashed" title="Trống">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                    <p>Không tìm thấy tài khoản Linode nào.</p>
                                    <p class="text-sm mt-1">Thêm token mới từ bảng bên phải.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- MOBILE BOX VIEW --}}
            <div class="block lg:hidden divide-y divide-gray-100">
                @foreach($rows as $row)
                    @php
                        $acc      = $row['model'];
                        $pct      = min(100, $row['used_pct']);
                        $daysLeft = $acc->promo_expires_at ? (int) now()->diffInDays($acc->promo_expires_at, false) : null;
                        $expired  = $daysLeft !== null && $daysLeft < 0;
                        $urgent   = !$expired && $daysLeft !== null && $daysLeft <= 14;
                        $warn     = !$expired && !$urgent && $daysLeft !== null && $daysLeft <= 30;
                    @endphp
                    <div class="p-4 hover:bg-gray-50/50 transition-colors">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <div class="font-bold text-gray-900 text-sm mb-0.5">{{ $acc->label }}</div>
                                <div class="text-xs text-gray-500">{{ $acc->email ?? '—' }}</div>
                            </div>
                            @if(!$acc->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">Tắt</span>
                            @elseif($acc->is_full)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200">Đầy</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-green-50 text-green-700 border border-green-200">Hoạt động</span>
                            @endif
                        </div>

                        <div class="mb-3">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-bold text-cloud-600">${{ number_format($row['available_usd'], 1) }} có sẵn</span>
                                <span class="text-gray-500">${{ number_format($acc->reserved_monthly_usd, 0) }} dự kiến</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full {{ $pct > 85 ? 'bg-red-500' : ($pct > 65 ? 'bg-amber-500' : 'bg-cloud-500') }}" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>

                        <div class="flex items-end justify-between border-t border-gray-100 pt-3 mt-3">
                            <div>
                                <div class="text-xs text-gray-500 mb-0.5">Thời hạn:</div>
                                @if($daysLeft === null)
                                    <span class="text-sm font-semibold text-gray-400">—</span>
                                @elseif($expired)
                                    <span class="text-sm font-bold text-red-600">Đã hết hạn</span>
                                @else
                                    <span class="text-sm font-bold {{ $urgent ? 'text-red-600' : ($warn ? 'text-amber-600' : 'text-green-600') }}">{{ $daysLeft }} ngày</span>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500 mb-0.5">VPS đang chạy:</div>
                                <span class="text-sm font-bold text-gray-900">{{ $acc->active_count ?? 0 }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-1 w-[60px]">
                                <a href="{{ route('admin.accounts.edit', $acc) }}" class="flex items-center justify-center w-7 h-7 text-gray-400 hover:text-cloud-600 hover:bg-cloud-50 border border-gray-200 rounded bg-white transition-colors" title="Sửa Tài khoản">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zM16.862 4.487L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.accounts.sync', $acc) }}" class="m-0">
                                    @csrf
                                    <button class="flex items-center justify-center w-7 h-7 text-gray-400 hover:text-cloud-600 hover:bg-cloud-50 border border-gray-200 rounded bg-white transition-colors" title="Đồng bộ">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.accounts.toggle', $acc) }}" class="m-0">
                                    @csrf
                                    <input type="hidden" name="field" value="is_full">
                                    <button class="flex items-center justify-center w-7 h-7 {{ $acc->is_full ? 'text-green-500 border-green-200 bg-green-50' : 'text-red-400 border-red-200 bg-red-50' }} rounded transition-colors border" title="{{ $acc->is_full ? 'Bỏ đánh dấu Đầy' : 'Đánh dấu Đầy' }}">
                                        @if($acc->is_full)
                                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" /></svg>
                                        @else
                                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        @endif
                                    </button>
                                </form>
                                <div class="flex items-center justify-center w-7 h-7 text-gray-200 border border-gray-100 rounded bg-gray-50 border-dashed" title="Trống">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══ FORM ═══ --}}
    <div class="xl:col-span-1">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sticky top-24">
            <h3 class="text-base font-bold text-gray-900 mb-5 flex items-center gap-2">
                <svg class="text-cloud-600" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Thêm Token Linode
            </h3>
            <form method="POST" action="{{ route('admin.accounts.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Tên hiển thị</label>
                    <input type="text" name="label" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600" placeholder="VD: Account #1" required value="{{ old('label') }}">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Token API</label>
                    <input type="password" name="api_token" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-mono focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600" placeholder="••••••••••••••••" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Khuyến mãi (USD)</label>
                        <input type="number" step="0.01" name="promo_credit_usd" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600" value="{{ old('promo_credit_usd', 100) }}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Số ngày</label>
                        <input type="number" name="promo_days" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600" value="{{ old('promo_days', 60) }}">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Ưu tiên 
                        <span class="text-gray-400 font-normal lowercase tracking-normal">(càng nhỏ càng ưu tiên)</span>
                    </label>
                    <input type="number" name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600" value="{{ old('priority', 0) }}">
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-cloud-600 hover:bg-cloud-700 transition-colors">
                        Thêm & Xác thực Token
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
