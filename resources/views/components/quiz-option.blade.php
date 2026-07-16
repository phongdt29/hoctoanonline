{{--
    Lua chon quiz. UI spec §3: .list-group-item-action
      dang chon  -> .active
      sau khi nop -> .list-group-item-success / -danger

    Dung <label> + <input type=radio> that (khong phai <div onclick>) de
    ban phim va screen reader dung duoc.
--}}
@props([
    'name'     => 'answer',
    'value'    => '',
    'letter'   => null,
    'selected' => false,
    'state'    => null,     // null | correct | wrong  (chi set SAU khi nop)
    'disabled' => false,
])

@php
    $stateClass = match ($state) {
        'correct' => 'list-group-item-success',
        'wrong'   => 'list-group-item-danger',
        default   => '',
    };
@endphp

<label {{ $attributes->merge([
    'class' => 'list-group-item list-group-item-action d-flex align-items-center gap-3 ht-tap '
        .($selected ? 'active ' : '').$stateClass,
]) }}>
    <input class="form-check-input flex-none m-0"
           type="radio"
           name="{{ $name }}"
           value="{{ $value }}"
           @checked($selected)
           @disabled($disabled)>

    @if ($letter)
        <span class="num fw-semibold">{{ $letter }}.</span>
    @endif

    <span class="flex-grow-1">{{ $slot }}</span>

    @if ($state === 'correct')
        <i class="bi bi-check-circle-fill" aria-label="Đáp án đúng"></i>
    @elseif ($state === 'wrong')
        <i class="bi bi-x-circle-fill" aria-label="Đáp án sai"></i>
    @endif
</label>
