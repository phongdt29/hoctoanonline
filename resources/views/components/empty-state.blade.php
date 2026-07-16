{{--
    Empty state. UI spec §3: .text-center.py-5 + icon bi-* + DUNG 1 nut,
    kem 1 cau hanh dong ro rang (khong phai "Khong co du lieu").
--}}
@props([
    'icon'    => 'bi-inbox',
    'title'   => '',
    'message' => null,
    'action'  => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-5']) }}>
    <i class="bi {{ $icon }} text-secondary" style="font-size:2.5rem" aria-hidden="true"></i>

    <p class="fw-semibold mt-3 mb-1">{{ $title }}</p>

    @if ($message)
        <p class="text-secondary small mb-3">{{ $message }}</p>
    @endif

    @if ($action)
        <div>{{ $action }}</div>
    @endif
</div>
