{{--
    O so lieu. UI spec §3: MOI so lieu (diem, %, timer) phai co class .num.
    icon hien trong vong tron mau mem (.ht-ico) cho hien dai.
--}}
@props([
    'label' => '',
    'value' => '',
    'unit'  => null,
    'icon'  => null,
    'hint'  => null,
])

<div {{ $attributes->merge(['class' => 'd-flex align-items-center gap-3']) }}>
    @if ($icon)
        <span class="ht-ico" style="width:44px;height:44px;font-size:1.25rem" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>
    @endif

    <div class="d-flex flex-column">
        <span class="text-secondary small">{{ $label }}</span>
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
</div>
