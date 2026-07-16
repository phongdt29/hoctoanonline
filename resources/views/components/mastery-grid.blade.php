{{--
    Mastery Grid — SIGNATURE UI cua san pham (UI spec §2.3).
    Tien do kieu "to o vo": moi o = 1 lesson.

    done      = da hoan thanh
    now       = buoi dang mo
    just-done = vua dat quiz >= 8 -> to mau lan 400ms (ticket L5), chi 1 lan.

    A11y: grid la thong tin chu khong phai trang tri -> can text thay the.
--}}
@props([
    'lessons'   => [],    // collection|array cac lesson co ->status
    'justDone'  => null,  // id lesson vua hoan thanh (de chay animation)
    'label'     => 'Tiến độ lộ trình',
])

@php
    $items = collect($lessons);
    $done  = $items->where('status', 'completed')->count();
    $total = $items->count();
@endphp

<div {{ $attributes->merge(['class' => 'd-flex flex-column gap-2']) }}>
    <div class="d-flex justify-content-between align-items-baseline">
        <span class="text-secondary small">{{ $label }}</span>
        <span class="small"><span class="num fw-semibold">{{ $done }}</span><span class="text-secondary">/{{ $total }} buổi</span></span>
    </div>

    <div class="ht-mastery" role="img"
         aria-label="{{ $label }}: đã hoàn thành {{ $done }} trên {{ $total }} buổi">
        @foreach ($items as $lesson)
            @php
                $cell = match ($lesson->status) {
                    'completed'                 => 'done',
                    'unlocked', 'in_progress'   => 'now',
                    default                     => '',
                };

                if ($justDone && $lesson->id === $justDone) {
                    $cell .= ' just-done';
                }
            @endphp
            <div class="cell {{ $cell }}"
                 title="{{ $lesson->title ?? 'Buổi '.$loop->iteration }}"
                 data-bs-toggle="tooltip"></div>
        @endforeach
    </div>
</div>
