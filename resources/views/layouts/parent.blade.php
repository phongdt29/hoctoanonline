{{--
    Layout phu huynh (UI-DESIGN-SPEC §4): khong sidebar, .container-lg,
    6 khoi = 6 card trong .row.g-4 (col-12 col-md-6), khoi risk dung dau col-12.
--}}
@extends('layouts.base')

@section('body')
<div class="container-lg py-3 py-lg-4">
    <header class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h5 mb-0">@yield('greeting', 'Theo dõi con')</h1>
            <p class="text-secondary small mb-0">{{ now()->timezone('Asia/Ho_Chi_Minh')->translatedFormat('l, d/m/Y') }}</p>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-2">
            @yield('topbar-chips')
            <x-logout class="btn btn-sm btn-outline-primary" />
        </div>
    </header>

    @yield('content')
</div>
@endsection
