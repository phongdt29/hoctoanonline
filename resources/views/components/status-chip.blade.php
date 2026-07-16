{{--
    Chip trang thai (diem danh / risk).

    UI spec §3 + CLAUDE.md #8: LUON la cham mau + CHU, khong bao gio mau tran.
    Ly do: nguoi mu mau khong phan biet duoc xanh/vang/do neu chi co mau.
--}}
@props([
    'status' => 'ok',   // ok | warn | danger | muted
])

@php
    $map = [
        'ok'     => 'dot-ok',
        'warn'   => 'dot-warn',
        'danger' => 'dot-danger',
        'muted'  => '',
    ];

    $dotClass = $map[$status] ?? $map['ok'];
@endphp

<span {{ $attributes->merge(['class' => 'badge rounded-pill text-bg-light border d-inline-flex align-items-center']) }}>
    @if ($dotClass)
        <span class="dot {{ $dotClass }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
