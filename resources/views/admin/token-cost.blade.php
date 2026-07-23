@extends('layouts.admin')

@section('title', 'Ước tính chi phí token')
@section('page-title', 'Ước tính chi phí token AI')

@section('page-actions')
    <a href="{{ route('admin.home') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Về báo cáo</a>
@endsection

@section('content')
<p class="text-secondary small">
    Nhập số người dùng và số lượt gọi AI để ước tính token & chi phí.
    @if ($hasData)
        Token trung bình mỗi lượt lấy từ <strong>{{ number_format($sampleCalls) }}</strong> lượt gọi thật đã ghi log (sửa được).
    @else
        Chưa có dữ liệu thật — đang dùng con số mặc định, bạn nên chỉnh theo thực tế.
    @endif
    Đây là <strong>ước tính</strong>, không phải hoá đơn.
</p>

<div class="row g-4">
    {{-- Form nhap --}}
    <div class="col-lg-6">
        <x-card title="Thông số" icon="bi-sliders">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Số người dùng</label>
                <input type="number" min="0" id="users" value="1000" class="form-control num">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Số lượt gọi AI / người <span class="text-secondary">(trong kỳ, vd 1 tháng)</span></label>
                <input type="number" min="0" id="callsPerUser" value="20" class="form-control num">
            </div>

            <hr class="my-3">
            <div class="small fw-semibold mb-2 text-secondary">Token trung bình mỗi lượt gọi</div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small">Input (câu hỏi)</label>
                    <input type="number" min="0" id="avgIn" value="{{ $avgInput }}" class="form-control num">
                </div>
                <div class="col-6">
                    <label class="form-label small">Output (trả lời)</label>
                    <input type="number" min="0" id="avgOut" value="{{ $avgOutput }}" class="form-control num">
                </div>
            </div>

            <hr class="my-3">
            <div class="small fw-semibold mb-2 text-secondary">Đơn giá (USD / 1 triệu token) — chỉnh theo model</div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small">Giá input</label>
                    <input type="number" step="0.01" min="0" id="priceIn" value="{{ $pricing['input_usd_per_1m'] }}" class="form-control num">
                </div>
                <div class="col-6">
                    <label class="form-label small">Giá output</label>
                    <input type="number" step="0.01" min="0" id="priceOut" value="{{ $pricing['output_usd_per_1m'] }}" class="form-control num">
                </div>
            </div>
            <div class="mb-1">
                <label class="form-label small">Tỷ giá USD → VND</label>
                <input type="number" min="0" id="rate" value="{{ $pricing['usd_to_vnd'] }}" class="form-control num">
            </div>
            <div class="form-text">Gợi ý: Gemini Flash ≈ 0.30 / 2.50 · Claude Haiku ≈ 1 / 5 · Claude Sonnet ≈ 3 / 15.</div>
        </x-card>
    </div>

    {{-- Ket qua --}}
    <div class="col-lg-6">
        <x-card title="Ước tính" icon="bi-calculator" class="mb-3">
            <div class="row g-3 text-center mb-3">
                <div class="col-6">
                    <div class="ht-ico mx-auto mb-2"><i class="bi bi-lightning"></i></div>
                    <div class="h4 mb-0 num" id="rCalls">0</div>
                    <div class="small text-secondary">Tổng lượt gọi</div>
                </div>
                <div class="col-6">
                    <div class="ht-ico mx-auto mb-2"><i class="bi bi-coin"></i></div>
                    <div class="h4 mb-0 num" id="rTotalTok">0</div>
                    <div class="small text-secondary">Tổng token</div>
                </div>
            </div>

            <table class="table table-sm align-middle mb-3">
                <tbody class="num">
                    <tr><td class="text-secondary">Token input</td><td class="text-end" id="rInTok">0</td></tr>
                    <tr><td class="text-secondary">Token output</td><td class="text-end" id="rOutTok">0</td></tr>
                    <tr><td class="text-secondary">Chi phí input</td><td class="text-end" id="rInUsd">$0</td></tr>
                    <tr><td class="text-secondary">Chi phí output</td><td class="text-end" id="rOutUsd">$0</td></tr>
                </tbody>
            </table>

            <div class="p-3 rounded-3" style="background:var(--ht-bg)">
                <div class="d-flex justify-content-between align-items-baseline">
                    <span class="fw-semibold">Tổng chi phí</span>
                    <span class="h4 mb-0"><span class="ht-text-grad num" id="rUsd">$0</span> <span class="text-secondary small">USD</span></span>
                </div>
                <div class="d-flex justify-content-between align-items-baseline mt-1">
                    <span class="text-secondary small">Quy đổi</span>
                    <span class="h5 mb-0 num" id="rVnd">0 ₫</span>
                </div>
                <div class="text-secondary small mt-2">Bình quân <span class="num" id="rPerUser">0 ₫</span>/người.</div>
            </div>
        </x-card>

        @if ($byFeature->isNotEmpty())
            <x-card title="Token trung bình theo tính năng (dữ liệu thật)" icon="bi-table">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr class="small text-secondary"><th>Tính năng</th><th class="text-end">Lượt</th><th class="text-end">In</th><th class="text-end">Out</th></tr></thead>
                    <tbody class="num">
                        @foreach ($byFeature as $f)
                            <tr>
                                <td class="text-body">{{ $f['feature'] }}</td>
                                <td class="text-end">{{ number_format($f['calls']) }}</td>
                                <td class="text-end">{{ number_format($f['input']) }}</td>
                                <td class="text-end">{{ number_format($f['output']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="form-text">Dùng để ước lượng: mỗi lần sinh đề/giáo trình tốn nhiều token hơn 1 câu hỏi gia sư.</div>
            </x-card>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const $ = id => document.getElementById(id);
    const vnd = new Intl.NumberFormat('vi-VN');
    const num = new Intl.NumberFormat('vi-VN');

    function usd(v) {
        return '$' + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function calc() {
        const users = +$('users').value || 0;
        const cpu   = +$('callsPerUser').value || 0;
        const avgIn = +$('avgIn').value || 0;
        const avgOut = +$('avgOut').value || 0;
        const pIn = +$('priceIn').value || 0;
        const pOut = +$('priceOut').value || 0;
        const rate = +$('rate').value || 0;

        const calls   = users * cpu;
        const inTok   = calls * avgIn;
        const outTok  = calls * avgOut;
        const total   = inTok + outTok;
        const inUsd   = inTok / 1e6 * pIn;
        const outUsd  = outTok / 1e6 * pOut;
        const totUsd  = inUsd + outUsd;
        const totVnd  = totUsd * rate;

        $('rCalls').textContent = num.format(calls);
        $('rTotalTok').textContent = num.format(total);
        $('rInTok').textContent  = num.format(inTok);
        $('rOutTok').textContent = num.format(outTok);
        $('rInUsd').textContent  = usd(inUsd);
        $('rOutUsd').textContent = usd(outUsd);
        $('rUsd').textContent    = usd(totUsd);
        $('rVnd').textContent    = vnd.format(Math.round(totVnd)) + ' ₫';
        $('rPerUser').textContent = vnd.format(users ? Math.round(totVnd / users) : 0) + ' ₫';
    }

    document.querySelectorAll('#users,#callsPerUser,#avgIn,#avgOut,#priceIn,#priceOut,#rate')
        .forEach(el => el.addEventListener('input', calc));
    calc();
});
</script>
@endpush
