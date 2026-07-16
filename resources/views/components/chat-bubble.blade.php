{{--
    Bong bong chat AI Tutor (ticket I1).
    sender=student -> ben phai, nen primary.
    sender=ai      -> ben trai, the trang.
    typing=true    -> 3 cham nhun "dang soan..."
--}}
@props([
    'sender' => 'ai',   // student | ai
    'time'   => null,
    'typing' => false,
])

@php
    $isStudent = $sender === 'student';
@endphp

<div {{ $attributes->merge([
    'class' => 'd-flex flex-column gap-1 '.($isStudent ? 'align-items-end' : 'align-items-start'),
]) }}>
    <div class="ht-bubble {{ $isStudent ? 'ht-bubble-student' : 'ht-bubble-ai' }}">
        @if ($typing)
            <span class="ht-typing d-inline-flex align-items-center" aria-label="Gia sư đang soạn tin nhắn">
                <span></span><span></span><span></span>
            </span>
        @else
            {{ $slot }}
        @endif
    </div>

    @if ($time && ! $typing)
        <span class="text-secondary num" style="font-size:11px">{{ $time }}</span>
    @endif
</div>
