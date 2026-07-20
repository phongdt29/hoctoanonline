{{--
    Layout phu huynh. Header dong bo voi student/teacher/admin: top navbar co logo MathAI.
    Khong sidebar; .container-lg, 6 khoi card.
--}}
@extends('layouts.base')

@section('body')
{{-- Top navbar — dong bo --}}
<nav class="navbar navbar-expand glass sticky-top border-bottom px-3">
    <x-brand size="sm" class="navbar-brand mb-0" />
    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
        @yield('topbar-chips')
        <x-logout class="btn btn-sm btn-outline-primary" />
    </div>
</nav>

<div class="container-lg py-3 py-lg-4">
    <header class="mb-4">
        <h1 class="h5 mb-0">@yield('greeting', 'Theo dõi con')</h1>
        <p class="text-secondary small mb-0">{{ now()->timezone('Asia/Ho_Chi_Minh')->translatedFormat('l, d/m/Y') }}</p>
    </header>

    @yield('content')
</div>
@endsection
