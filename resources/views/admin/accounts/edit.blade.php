@extends('layouts.app')
@section('title', 'Sửa Tài khoản Linode — Admin')

@section('breadcrumbs')
    <span>Quản trị</span>
    <span class="mx-2 text-gray-300">/</span>
    <a href="{{ route('admin.accounts.index') }}" class="text-gray-500 hover:text-gray-900 transition-colors">Tài khoản Linode</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-900">Sửa</span>
@endsection

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Cập nhật Tài khoản</h1>
    <p class="text-sm text-gray-500 mt-1">Chỉnh sửa cấu hình và token của tài khoản: <span class="font-bold text-gray-900">{{ $account->label }}</span>.</p>
</div>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 max-w-2xl">
    <form method="POST" action="{{ route('admin.accounts.update', $account) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Tên hiển thị</label>
                <input type="text" name="label" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600" required value="{{ old('label', $account->label) }}">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Ưu tiên (0 là cao nhất)</label>
                <input type="number" name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600" value="{{ old('priority', $account->priority) }}">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Token API</label>
            <input type="password" name="api_token" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-mono focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600" required value="{{ old('api_token', $account->api_token) }}">
            <p class="text-[11px] text-gray-500 mt-1">Token sẽ được hiển thị dạng ẩn, bạn có thể nhập token mới nếu token cũ bị hết hạn.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Khuyến mãi (USD)</label>
                <input type="number" step="0.01" name="promo_credit_usd" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600" value="{{ old('promo_credit_usd', $account->promo_credit_usd) }}">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Ngày hết hạn khuyến mãi</label>
                <input type="date" name="promo_expires_at" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600" value="{{ old('promo_expires_at', $account->promo_expires_at ? $account->promo_expires_at->format('Y-m-d') : '') }}">
            </div>
        </div>

        <div class="flex items-center gap-6 pt-4 border-t border-gray-100">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" class="w-4 h-4 text-cloud-600 border-gray-300 rounded focus:ring-cloud-600" {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                <span class="text-sm font-semibold text-gray-700">Đang hoạt động (Active)</span>
            </label>
            
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_full" value="1" class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-600" {{ old('is_full', $account->is_full) ? 'checked' : '' }}>
                <span class="text-sm font-semibold text-gray-700">Đã đầy (Full / Limit)</span>
            </label>
        </div>

        <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
            <button type="button" onclick="showGlobalConfirm('Bạn có chắc chắn muốn xóa tài khoản này?<br><br>Lưu ý: Không thể xóa nếu tài khoản này vẫn còn VPS đang chạy.', () => document.getElementById('delete-account-form').submit())" class="px-4 py-2 border border-red-200 rounded-md bg-red-50 text-red-600 font-medium text-sm hover:bg-red-100 transition-colors shadow-sm flex items-center gap-2">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                Xóa tài khoản
            </button>

            <div class="flex gap-3">
                <a href="{{ route('admin.accounts.index') }}" class="px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-700 font-medium text-sm hover:bg-gray-50 transition-colors shadow-sm">Hủy bỏ</a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md bg-cloud-600 text-white font-medium text-sm hover:bg-cloud-700 transition-colors shadow-sm">
                    Lưu thay đổi
                </button>
            </div>
        </div>
    </form>

    {{-- Delete Form --}}
    <form id="delete-account-form" action="{{ route('admin.accounts.destroy', $account) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection
