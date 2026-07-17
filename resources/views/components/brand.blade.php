{{--
    Logo thuong hieu MathAI — icon gradient + wordmark "Math" + "AI" (AI gradient).
    Props: size (sm|md|lg), :link (co boc <a href> khong).
--}}
@props([
    'size' => 'md',
    'link' => true,
])

@php
    $dims = ['sm' => [30, '.95rem', '1rem'], 'md' => [38, '1.1rem', '1.25rem'], 'lg' => [46, '1.3rem', '1.5rem']];
    [$box, $ico, $text] = $dims[$size] ?? $dims['md'];
    $tag = $link ? 'a' : 'span';
@endphp

<{{ $tag }} @if ($link) href="{{ route('home') }}" @endif
    {{ $attributes->merge(['class' => 'd-inline-flex align-items-center gap-2 text-decoration-none']) }}>
    <span class="ht-ico ht-ico-grad" style="width:{{ $box }}px;height:{{ $box }}px;font-size:{{ $ico }}">
        <i class="bi bi-calculator-fill"></i>
    </span>
    <span class="fw-bold" style="font-size:{{ $text }}; letter-spacing:-.03em; color:var(--ht-ink)">Math<span class="ht-text-grad">AI</span></span>
</{{ $tag }}>
