{{--
    Layout admin/teacher (UI-DESIGN-SPEC §4): .container-fluid + bang, mat do cao.
    Khong to ve — day la man hinh lam viec.
--}}
@extends('layouts.base')

@section('body')
<nav class="navbar navbar-expand bg-white border-bottom px-3">
    <x-brand size="sm" class="navbar-brand mb-0" />

    <div class="navbar-nav me-auto">
        @yield('nav')
    </div>

    <div class="d-flex align-items-center gap-2">
        @yield('topbar-chips')
    </div>
</nav>

<div class="container-fluid py-3">
    <header class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h1 class="h5 mb-0">@yield('page-title', 'Quản trị')</h1>
        <div class="d-flex gap-2">@yield('page-actions')</div>
    </header>

    @yield('content')
</div>
@endsection
