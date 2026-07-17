{{-- UI spec §3: the noi dung = .card + .card-body (da nhuom o theme.css §2). --}}
@props([
    'title' => null,
    'icon'  => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'card h-100']) }}>
    @if ($title)
        <div class="card-body pb-0 d-flex justify-content-between align-items-center gap-2">
            <h2 class="h6 fw-bold mb-0 d-flex align-items-center gap-2">
                @if ($icon)
                    <span class="ht-ico" style="width:34px;height:34px;font-size:1rem" aria-hidden="true">
                        <i class="bi {{ $icon }}"></i>
                    </span>
                @endif
                {{ $title }}
            </h2>

            @if ($action)
                <div>{{ $action }}</div>
            @endif
        </div>
    @endif

    <div class="card-body">
        {{ $slot }}
    </div>
</div>
