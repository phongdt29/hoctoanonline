{{--
    Trang kiem tra nhanh toan bo design system (UI-DESIGN-SPEC §6.5).
    CHI chay o moi truong local — route bi chan o production.
--}}
@extends('layouts.student')

@section('title', 'Style guide')
@section('greeting', 'Style guide')
@section('topbar-chips')
    <span class="badge rounded-pill text-bg-light border">
        <i class="bi bi-fire text-warning"></i> <span class="num">4</span> ngày
    </span>
    <span class="badge rounded-pill text-bg-light border">
        <i class="bi bi-star-fill" style="color:var(--ht-star)"></i> <span class="num">233</span>
    </span>
@endsection

@section('content')

{{-- Doi mau: chung minh DoD F5 — doi --ht-primary MOT CHO, moi thu doi theo --}}
<x-card title="Cá nhân hóa màu" icon="bi-palette" class="mb-4">
    <p class="text-secondary small">
        Bấm một màu — nút, link, badge, progress, mastery grid và nền ô ly phải đổi theo ngay.
        Đây là DoD của ticket F5.
    </p>

    <div class="d-flex flex-wrap gap-2">
        @foreach (config('hoctoan.personalization.colors') as $color)
            <button type="button"
                    class="btn btn-sm border ht-tap js-swatch"
                    data-hex="{{ $color['hex'] }}"
                    data-rgb="{{ $color['rgb'] }}"
                    style="background:{{ $color['hex'] }}; width:44px"
                    aria-label="Đổi sang màu {{ $color['name'] }}"></button>
        @endforeach
    </div>
</x-card>

<div class="row g-4">

    {{-- 1. card + 2. stat --}}
    <div class="col-12 col-lg-6">
        <x-card title="1 + 2. Card &amp; Stat" icon="bi-bar-chart">
            <div class="d-flex flex-wrap gap-4">
                <x-stat label="Hoàn thành" value="72" unit="%" icon="bi-check2-circle" />
                <x-stat label="Điểm TB" value="7.2" icon="bi-clipboard-check" hint="13 bài quiz" />
                <x-stat label="Học thực" value="31" unit="phút" icon="bi-clock" hint="online 45 phút" />
            </div>

            <div class="progress mt-4" role="progressbar" aria-label="Tiến độ" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width:72%"></div>
            </div>
        </x-card>
    </div>

    {{-- 3. status-chip --}}
    <div class="col-12 col-lg-6">
        <x-card title="3. Status chip" icon="bi-circle-half">
            <p class="text-secondary small">Luôn là chấm màu <em>kèm chữ</em> — không bao giờ màu trần.</p>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <x-status-chip status="ok">Có mặt</x-status-chip>
                <x-status-chip status="warn">Học chưa đủ</x-status-chip>
                <x-status-chip status="danger">Vắng</x-status-chip>
                <x-status-chip status="muted">Chưa tới giờ</x-status-chip>
            </div>

            <p class="text-secondary small mb-2">Đèn tín hiệu risk cho phụ huynh:</p>
            <div class="d-flex flex-wrap gap-2">
                <x-status-chip status="ok">Ổn định · <span class="num">21</span></x-status-chip>
                <x-status-chip status="warn">Cần theo dõi · <span class="num">35</span></x-status-chip>
                <x-status-chip status="danger">Nguy cơ cao · <span class="num">72</span></x-status-chip>
            </div>
        </x-card>
    </div>

    {{-- 4. mastery-grid --}}
    <div class="col-12">
        <x-card title="4. Mastery Grid (signature)" icon="bi-grid-3x3">
            <x-mastery-grid :lessons="$lessons" :just-done="$justDoneId" />
            <p class="text-secondary small mt-3 mb-0">
                Ô tô đậm = đã xong · ô nhạt = buổi đang mở · ô trắng = chưa mở.
                Ô vừa đạt quiz ≥ 8 chạy animation tô lan 400ms, một lần.
            </p>
        </x-card>
    </div>

    {{-- Thanh mix 20/60/20 --}}
    <div class="col-12 col-lg-6">
        <x-card title="Thanh mix 20/60/20" icon="bi-distribute-horizontal">
            <p class="text-secondary small">Cấu trúc buổi học theo SPEC §3.3 — đọc từ <code>config/hoctoan.php</code>.</p>

            @php $mix = config('hoctoan.session_mix'); @endphp

            <div class="ht-mix" role="img" aria-label="Cấu trúc buổi: ôn {{ $mix['review'] }}%, bài mới {{ $mix['new'] }}%, củng cố {{ $mix['reinforce'] }}%">
                <div class="m-review" style="width:{{ $mix['review'] }}%"></div>
                <div class="m-new" style="width:{{ $mix['new'] }}%"></div>
                <div class="m-fix" style="width:{{ $mix['reinforce'] }}%"></div>
            </div>

            <div class="d-flex justify-content-between small text-secondary mt-2">
                <span>Ôn <span class="num">{{ $mix['review'] }}%</span></span>
                <span>Bài mới <span class="num">{{ $mix['new'] }}%</span></span>
                <span>Củng cố <span class="num">{{ $mix['reinforce'] }}%</span></span>
            </div>
        </x-card>
    </div>

    {{-- 6. chat-bubble --}}
    <div class="col-12 col-lg-6">
        <x-card title="6. Chat bubble" icon="bi-chat-dots">
            <div class="d-flex flex-column gap-2">
                <x-chat-bubble sender="student" time="19:02">Cô ơi, bài phân số này con làm sao ạ?</x-chat-bubble>
                <x-chat-bubble sender="ai" time="19:02">Con thử quy đồng mẫu số trước xem sao nhé.</x-chat-bubble>
                <x-chat-bubble sender="ai" typing />
            </div>
        </x-card>
    </div>

    {{-- 5. quiz-option --}}
    <div class="col-12 col-lg-6">
        <x-card title="5. Quiz option" icon="bi-ui-checks">
            <p class="text-secondary small">Đang làm bài:</p>
            <div class="list-group mb-4">
                <x-quiz-option letter="A" value="a">Rút gọn phân số</x-quiz-option>
                <x-quiz-option letter="B" value="b" selected>Quy đồng mẫu số</x-quiz-option>
                <x-quiz-option letter="C" value="c">Nhân chéo</x-quiz-option>
            </div>

            <p class="text-secondary small">Sau khi nộp:</p>
            <div class="list-group">
                <x-quiz-option letter="A" value="a" state="wrong" disabled>Rút gọn phân số</x-quiz-option>
                <x-quiz-option letter="B" value="b" state="correct" disabled>Quy đồng mẫu số</x-quiz-option>
            </div>
        </x-card>
    </div>

    {{-- 7. empty-state --}}
    <div class="col-12 col-lg-6">
        <x-card title="7. Empty state" icon="bi-inbox">
            <x-empty-state icon="bi-journal-x"
                           title="Chưa có bài tập nào"
                           message="Giáo viên chưa giao bài cho lớp của bạn.">
                <x-slot:action>
                    <button type="button" class="btn btn-primary">Vào lộ trình học</button>
                </x-slot:action>
            </x-empty-state>
        </x-card>
    </div>

    {{-- Nut, form, toast --}}
    <div class="col-12 col-lg-6">
        <x-card title="Nút &amp; Form" icon="bi-input-cursor-text">
            <div class="d-flex flex-wrap gap-2 mb-4">
                <button type="button" class="btn btn-primary">Bắt đầu học</button>
                <button type="button" class="btn btn-outline-primary">Xem lời giải</button>
                <button type="button" class="btn btn-link">Bỏ qua</button>
                <button type="button" class="btn btn-primary" disabled>Đang nộp…</button>
            </div>

            <label for="sg-input" class="form-label small">Điểm trung bình toán</label>
            <input id="sg-input" class="form-control is-invalid num" value="12">
            <div class="invalid-feedback">Điểm phải từ 0 đến 10.</div>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="button" class="btn btn-sm btn-outline-primary js-toast" data-type="success">Toast thành công</button>
                <button type="button" class="btn btn-sm btn-outline-primary js-toast" data-type="star">Toast ⭐</button>
                <button type="button" class="btn btn-sm btn-outline-primary js-toast" data-type="error">Toast lỗi</button>
            </div>
        </x-card>
    </div>

    {{-- MathJax --}}
    <div class="col-12 col-lg-6">
        <x-card title="Công thức toán (MathJax)" icon="bi-calculator">
            <p>Phương trình bậc hai \(ax^2 + bx + c = 0\) có nghiệm:</p>
            <p>\[ x = \frac{-b \pm \sqrt{b^2 - 4ac}}{2a} \]</p>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    // DoD F5: doi --ht-primary + --ht-primary-rgb o MOT CHO -> toan bo doi theo.
    $('.js-swatch').on('click', function () {
        var root = document.documentElement;
        root.style.setProperty('--ht-primary', $(this).data('hex'));
        root.style.setProperty('--ht-primary-rgb', $(this).data('rgb'));
        window.ht.showToast('Đã đổi màu chủ đạo.', 'success');
    });

    $('.js-toast').on('click', function () {
        var type = $(this).data('type');
        var msg = {
            success: 'Đã lưu bài làm.',
            star: 'Tuyệt vời! +16 điểm.',
            error: 'Hết giờ làm bài rồi.'
        }[type];
        window.ht.showToast(msg, type);
    });
});
</script>
@endpush
