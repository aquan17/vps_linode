@extends('layouts.app')
@section('title', 'Tạo VPS ' . $plan['name'] . ' — NovaCloud')

@section('breadcrumbs')
    <a href="{{ route('pricing') }}" class="hover:text-gray-900 transition-colors">Bảng giá</a>
    <span class="mx-2 text-gray-300">/</span>
    <span class="text-gray-900">Khởi tạo VPS</span>
@endsection

@section('content')

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Khởi tạo VPS mới</h1>
    <p class="text-sm text-gray-500 mt-1">Cấu hình máy chủ ảo <span class="font-semibold text-cloud-600">{{ $plan['name'] }}</span> của bạn.</p>
</div>

{{-- ════ FULLSCREEN LOADING OVERLAY ════ --}}
<div id="fullScreenLoader" class="fixed inset-0 z-50 flex items-center justify-center bg-white/80 backdrop-blur-sm hidden transition-all duration-300">
    <div class="text-center bg-white p-8 rounded-2xl shadow-2xl border border-gray-100 max-w-sm w-full mx-4">
        <div class="relative w-20 h-20 mx-auto mb-6">
            <svg class="animate-spin w-full h-full text-cloud-100" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
            </svg>
            <svg class="animate-spin absolute inset-0 w-full h-full text-cloud-600" style="animation-direction: reverse; animation-duration: 1.5s;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Đang khởi tạo máy chủ...</h3>
        <p class="text-sm text-gray-500">Quá trình này có thể mất vài giây. Vui lòng không đóng trình duyệt hoặc tải lại trang.</p>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    {{-- ════ MAIN CONFIGURATION ════ --}}
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('store.store') }}" id="orderForm" class="space-y-6">
            @csrf
            <input type="hidden" name="plan" value="{{ $planId }}">
            <input type="hidden" name="image" id="selectedImage" value="{{ old('image', 'linode/ubuntu22.04') }}" required>

            {{-- Label --}}
            @php
                $emailPrefix = explode('@', auth()->user()->email)[0] ?? 'vps';
                // Chỉ giữ lại chữ, số và gạch ngang (theo đúng chuẩn Linode)
                $cleanPrefix = preg_replace('/[^a-zA-Z0-9\-]/', '', $emailPrefix);
                $defaultLabel = $cleanPrefix . '-' . substr(uniqid(), -4);
            @endphp
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-full bg-cloud-100 text-cloud-700 flex items-center justify-center font-bold text-sm">1</div>
                    <h2 class="text-lg font-bold text-gray-900">Tên gợi nhớ (Label)</h2>
                </div>
                <div>
                    <input type="text" name="label" 
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-md font-mono text-sm focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600 bg-white"
                           value="{{ old('label', $defaultLabel) }}"
                           pattern="[a-zA-Z0-9\-]+" maxlength="32" required
                           placeholder="vps-abc123">
                    <p class="mt-2 text-xs text-gray-500">Chỉ dùng chữ, số và dấu gạch ngang. Tối đa 32 ký tự.</p>
                </div>
            </div>

            {{-- OS Selection --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-full bg-cloud-100 text-cloud-700 flex items-center justify-center font-bold text-sm">2</div>
                    <h2 class="text-lg font-bold text-gray-900">Hệ điều hành (OS)</h2>
                </div>

                @php
                    $groups = [];
                    foreach ($images as $slug => $img) {
                        $groups[$img['group']][$slug] = $img;
                    }
                    $defaultImage = old('image', 'linode/ubuntu22.04');
                @endphp

                <div class="space-y-6">
                    @foreach($groups as $groupName => $groupImages)
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{{ $groupName }}</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($groupImages as $slug => $img)
                            <label class="os-card relative cursor-pointer group">
                                <input type="radio" name="_image_radio" value="{{ $slug }}"
                                       {{ $slug === $defaultImage ? 'checked' : '' }}
                                       class="peer sr-only"
                                       onchange="selectOs(this)">
                                <div class="flex items-center gap-3 p-3 border rounded-lg transition-all
                                            peer-checked:border-cloud-600 peer-checked:bg-cloud-50 peer-checked:ring-1 peer-checked:ring-cloud-600
                                            border-gray-200 bg-white group-hover:border-cloud-300">
                                    <span class="text-xl">{{ $img['icon'] }}</span>
                                    <span class="text-sm font-semibold text-gray-700">{{ $img['label'] }}</span>
                                </div>
                                @if(!empty($img['default']))
                                    <span class="absolute -top-2.5 right-2 bg-cloud-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm uppercase">Mặc định</span>
                                @endif
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                @error('image')
                    <p class="mt-3 text-sm text-red-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            {{-- Region --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-full bg-cloud-100 text-cloud-700 flex items-center justify-center font-bold text-sm">3</div>
                    <h2 class="text-lg font-bold text-gray-900">Khu vực (Region)</h2>
                </div>
                <div>
                    <select name="region" class="w-full px-4 py-2.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-cloud-600 focus:ring-1 focus:ring-cloud-600 bg-white" required>
                        @foreach($regions as $code => $name)
                            <option value="{{ $code }}" {{ old('region', config('linode.default_region')) === $code ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Duration --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-full bg-cloud-100 text-cloud-700 flex items-center justify-center font-bold text-sm">4</div>
                    <h2 class="text-lg font-bold text-gray-900">Thời gian thuê</h2>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($durations as $m => $dlabel)
                    <label class="duration-card cursor-pointer group relative">
                        <input type="radio" name="duration" value="{{ $m }}"
                               {{ $loop->first ? 'checked' : '' }}
                               class="peer sr-only"
                               onchange="selectDur(this)">
                        <div class="flex flex-col items-center justify-center p-4 border rounded-lg transition-all text-center
                                    peer-checked:border-cloud-600 peer-checked:bg-cloud-50 peer-checked:ring-1 peer-checked:ring-cloud-600
                                    border-gray-200 bg-white group-hover:border-cloud-300">
                            <span class="text-sm font-bold text-gray-900 mb-1">{{ $m }} Tháng</span>
                            <span class="text-xs text-cloud-600 font-mono" id="durPrice_{{ $m }}">{{ number_format($plan['price_per_month'] * $m) }} đ</span>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            @if($errors->any())
            <div class="p-4 rounded-md bg-red-50 border border-red-200 flex items-start gap-3">
                <svg class="text-red-600 flex-shrink-0 mt-0.5" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span class="text-sm text-red-800 font-medium">{{ $errors->first() }}</span>
            </div>
            @endif
        </form>
    </div>

    {{-- ════ SUMMARY SIDEBAR ════ --}}
    <div class="lg:col-span-1">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 sticky top-24">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-6">Tóm tắt đơn hàng</h3>

            {{-- Plan info --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                <div class="font-bold text-gray-900">{{ $plan['name'] }}</div>
                <div class="text-xs text-gray-500 mt-1 mb-3">{{ $plan['desc'] }}</div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-2 py-1 rounded bg-white border border-gray-200 text-[11px] font-medium text-gray-600">⚡ {{ $plan['cores'] }} vCPU</span>
                    <span class="inline-flex items-center px-2 py-1 rounded bg-white border border-gray-200 text-[11px] font-medium text-gray-600">💾 {{ $plan['ram'] }} GB RAM</span>
                    <span class="inline-flex items-center px-2 py-1 rounded bg-white border border-gray-200 text-[11px] font-medium text-gray-600">🗄️ {{ $plan['disk'] }} GB SSD</span>
                    <span class="inline-flex items-center px-2 py-1 rounded bg-white border border-gray-200 text-[11px] font-medium text-gray-600">🌐 {{ $plan['transfer_tb'] ?? 1 }} TB</span>
                    <span class="inline-flex items-center px-2 py-1 rounded bg-white border border-gray-200 text-[11px] font-medium text-gray-600">🚀 40 Gbps / {{ $plan['network_out_mbps'] ?? 1000 }} Mbps</span>
                </div>
            </div>

            {{-- Breakdown --}}
            <div class="space-y-3 mb-6">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500">Hệ điều hành</span>
                    <span id="summaryOs" class="font-medium text-gray-900 text-right">Ubuntu 22.04 LTS</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500">Thời gian</span>
                    <span id="summaryDuration" class="font-medium text-gray-900 text-right">1 Tháng</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500">Giá mỗi tháng</span>
                    <span class="font-mono text-gray-900 text-right">{{ number_format($plan['price_per_month']) }} đ</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500">Số dư hiện tại</span>
                    <span class="font-mono text-gray-900 text-right">{{ number_format(auth()->user()->balance) }} đ</span>
                </div>
            </div>

            <hr class="border-gray-200 mb-6">

            <div class="flex justify-between items-center mb-6">
                <span class="font-bold text-gray-900">Tổng thanh toán</span>
                <span id="totalPrice" class="text-xl font-extrabold text-cloud-600 font-mono">
                    {{ number_format($plan['price_per_month']) }} đ
                </span>
            </div>

            @php
                $enough = auth()->user()->balance >= $plan['price_per_month'];
            @endphp
            
            <button type="submit" form="orderForm" id="btnSubmitOrder" class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-cloud-600 hover:bg-cloud-700 transition-colors {{ !$enough ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$enough ? 'disabled' : '' }}>
                Khởi tạo VPS ngay
            </button>
            
            <div id="balanceErrorDiv" class="mt-4 p-3 rounded-md bg-red-50 border border-red-100" style="display: {{ !$enough ? 'block' : 'none' }}">
                <p class="text-xs text-red-700 text-center font-medium">Số dư không đủ. Vui lòng nạp thêm tiền.</p>
            </div>

            <p class="text-[11px] text-gray-400 mt-6 text-center leading-relaxed">
                Bằng việc bấm "Khởi tạo VPS ngay", bạn đồng ý với Điều khoản Dịch vụ của chúng tôi. VPS thường được khởi tạo thành công trong 2-5 phút.
            </p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const base = {{ $plan['price_per_month'] }};
const userBalance = {{ auth()->user()->balance }};
const images = @json(collect($images)->mapWithKeys(fn($v, $k) => [$k => $v['label']]));

// ── OS Selection ──
function selectOs(radio) {
    document.getElementById('selectedImage').value = radio.value;
    const name = images[radio.value] || radio.value;
    document.getElementById('summaryOs').textContent = name;
}

// ── Duration Selection ──
let selectedMonths = 1;
function selectDur(radio) {
    selectedMonths = parseInt(radio.value, 10);
    updateTotal();
    document.getElementById('summaryDuration').textContent = selectedMonths + ' Tháng';
}

function updateTotal() {
    const total = base * selectedMonths;
    document.getElementById('totalPrice').textContent = total.toLocaleString('vi-VN') + ' đ';

    const btn = document.getElementById('btnSubmitOrder');
    const errorDiv = document.getElementById('balanceErrorDiv');
    if (total > userBalance) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        if (errorDiv) errorDiv.style.display = 'block';
    } else {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
        if (errorDiv) errorDiv.style.display = 'none';
    }
}

// Init OS summary label
(function() {
    const sel = document.getElementById('selectedImage').value;
    if (images[sel]) document.getElementById('summaryOs').textContent = images[sel];
    updateTotal();
})();

// ── Form Submit Loading State ──
document.getElementById('orderForm').addEventListener('submit', function(e) {
    // Show fullscreen loader
    const loader = document.getElementById('fullScreenLoader');
    loader.classList.remove('hidden');
    
    const btn = document.getElementById('btnSubmitOrder');
    setTimeout(() => {
        btn.disabled = true;
    }, 10);
});
</script>
@endpush
