{{-- UI spec §3: the noi dung = .card + .card-body (da nhuom o theme.css §2). --}}
@props([
    'title' => null,
    'icon'  => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'card h-100']) }}>
    @if ($title)
        <div class="card-body pb-0 d-flex justify-content-between align-items-start gap-2">
            <h2 class="h6 mb-0 d-flex align-items-center gap-2">
                @if ($icon)
                    <i class="bi {{ $icon }} text-primary" aria-hidden="true"></i>
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
