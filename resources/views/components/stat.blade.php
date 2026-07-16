{{--
    O so lieu. UI spec §3: MOI so lieu (diem, %, timer) phai co class .num
    de dung font IBM Plex Mono + tabular-nums (so khong nhay khi dem nguoc).
--}}
@props([
    'label' => '',
    'value' => '',
    'unit'  => null,
    'icon'  => null,
    'hint'  => null,
])

<div {{ $attributes->merge(['class' => 'd-flex flex-column gap-1']) }}>
    <span class="text-secondary small d-flex align-items-center gap-1">
        @if ($icon)
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $label }}
    </span>

    <span class="d-flex align-items-baseline gap-1">
        <span class="num fs-4 fw-bold">{{ $value }}</span>
        @if ($unit)
            <span class="text-secondary small">{{ $unit }}</span>
        @endif
    </span>

    @if ($hint)
        <span class="text-secondary" style="font-size:12px">{{ $hint }}</span>
    @endif
</div>
