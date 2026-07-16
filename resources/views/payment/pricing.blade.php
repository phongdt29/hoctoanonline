@extends('layouts.student')

@section('title', 'Chọn gói học')
@section('greeting', 'Chọn gói học')
@php $active = 'profile'; @endphp

@section('content')
<div class="mx-auto" style="max-width:900px">
    <div class="text-center mb-4">
        <h2 class="h4 fw-bold">Nâng cấp để học không giới hạn</h2>
        <p class="text-secondary">Chọn gói phù hợp — thanh toán an toàn qua VNPAY hoặc MoMo.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2 small">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        @foreach ($plans as $plan)
            <div class="col-md-4">
                <div class="card h-100 {{ $plan->duration_days === 90 ? 'border-primary' : '' }}">
                    <div class="card-body p-4 d-flex flex-column">
                        @if ($plan->duration_days === 90)
                            <span class="badge text-bg-primary rounded-pill align-self-start mb-2">Phổ biến nhất</span>
                        @endif
                        <h3 class="h5 fw-bold">{{ $plan->name }}</h3>
                        <div class="my-2">
                            <span class="num fs-3 fw-bold text-primary">{{ number_format($plan->price, 0, ',', '.') }}</span>
                            <span class="text-secondary">đ</span>
                        </div>
                        <p class="text-secondary small mb-3">{{ $plan->duration_days }} ngày</p>

                        <ul class="list-unstyled small flex-grow-1">
                            @foreach ($plan->features ?? [] as $feature)
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> {{ $feature }}</li>
                            @endforeach
                        </ul>

                        {{-- Chon cong thanh toan --}}
                        <form method="POST" action="{{ route('payment.checkout', $plan) }}" class="mt-3">
                            @csrf
                            <div class="d-grid gap-2">
                                <button type="submit" name="gateway" value="vnpay"
                                        class="btn btn-primary ht-tap">
                                    <i class="bi bi-credit-card"></i> Thanh toán VNPAY
                                </button>
                                <button type="submit" name="gateway" value="momo"
                                        class="btn btn-outline-primary ht-tap">
                                    <i class="bi bi-wallet2"></i> Thanh toán MoMo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-center text-secondary small mt-4">
        <i class="bi bi-shield-lock"></i> Thanh toán được mã hóa và xác thực chữ ký. Bạn có thể hủy bất cứ lúc nào.
    </p>
</div>
@endsection
