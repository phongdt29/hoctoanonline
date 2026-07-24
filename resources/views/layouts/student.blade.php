{{--
    Layout hoc sinh. Header dong bo voi teacher/admin: top navbar co logo MathAI.
    Desktop: top navbar + sidebar doc 92px + noi dung.
    Mobile <992px: top navbar + bottom tab.
--}}
@extends('layouts.base')

@section('body-class', 'has-bottom-nav')

@php
    $nav = [
        ['route' => 'dashboard',  'icon' => 'bi-house-door',   'label' => 'Trang chính'],
        ['route' => 'curriculum', 'icon' => 'bi-map',          'label' => 'Lộ trình'],
        ['route' => 'results',    'icon' => 'bi-graph-up-arrow', 'label' => 'Kết quả'],
        ['route' => 'solver',     'icon' => 'bi-calculator',   'label' => 'Giải bài'],
        ['route' => 'tutor',      'icon' => 'bi-chat-dots',    'label' => 'Gia sư'],
        ['route' => 'pricing',    'icon' => 'bi-gem',          'label' => 'Mua gói'],
        ['route' => 'profile',    'icon' => 'bi-person',       'label' => 'Cá nhân'],
    ];
@endphp

@section('body')
{{-- Top navbar — dong bo voi teacher/admin --}}
<nav class="navbar navbar-expand glass sticky-top border-bottom px-3">
    <x-brand size="sm" class="navbar-brand mb-0" />
    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
        @yield('topbar-chips')
        <x-logout class="btn btn-sm btn-outline-primary" />
    </div>
</nav>

<div class="container-xxl py-3 py-lg-4">
    <div class="row g-4">

        {{-- Sidebar desktop --}}
        <aside class="col-auto d-none d-lg-flex">
            <nav class="nav nav-pills flex-column gap-1 text-center" style="width:92px" aria-label="Điều hướng chính">
                @foreach ($nav as $item)
                    <a class="nav-link ht-tap px-1 py-2 {{ ($active ?? '') === $item['route'] ? 'active' : '' }}"
                       href="{{ route($item['route']) }}"
                       @if (($active ?? '') === $item['route']) aria-current="page" @endif>
                        <i class="bi {{ $item['icon'] }} d-block fs-5 mb-1" aria-hidden="true"></i>
                        <span style="font-size:11px">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="col">
            {{-- Loi chao + ngay --}}
            <header class="mb-4">
                <h1 class="h5 mb-0">@yield('greeting', 'Chào bạn')</h1>
                <p class="text-secondary small mb-0">{{ now()->timezone('Asia/Ho_Chi_Minh')->translatedFormat('l, d/m/Y') }}</p>
            </header>

            @yield('content')
        </div>
    </div>
</div>

{{-- Bottom tab mobile --}}
<nav class="navbar fixed-bottom bg-white border-top d-lg-none" aria-label="Điều hướng chính">
    <div class="container-fluid justify-content-around">
        @foreach ($nav as $item)
            <a class="nav-link ht-tap text-center px-2 {{ ($active ?? '') === $item['route'] ? 'text-primary fw-semibold' : 'text-secondary' }}"
               href="{{ route($item['route']) }}"
               @if (($active ?? '') === $item['route']) aria-current="page" @endif>
                <i class="bi {{ $item['icon'] }} d-block fs-5" aria-hidden="true"></i>
                <span style="font-size:10px">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
@endsection
