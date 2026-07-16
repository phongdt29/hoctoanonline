{{--
    Layout hoc sinh (UI-DESIGN-SPEC §4).
    Desktop: sidebar co dinh 92px (nav-pills doc) + noi dung.
    Mobile <992px: an sidebar, hien bottom tab 5 muc.
--}}
@extends('layouts.base')

@section('body-class', 'has-bottom-nav')

@php
    $nav = [
        ['route' => 'dashboard',  'icon' => 'bi-house-door',   'label' => 'Trang chính'],
        ['route' => 'curriculum', 'icon' => 'bi-map',          'label' => 'Lộ trình'],
        ['route' => 'solver',     'icon' => 'bi-calculator',   'label' => 'Giải bài'],
        ['route' => 'tutor',      'icon' => 'bi-chat-dots',    'label' => 'Gia sư'],
        ['route' => 'profile',    'icon' => 'bi-person',       'label' => 'Cá nhân'],
    ];
@endphp

@section('body')
<div class="container-xxl py-3 py-lg-4">
    <div class="row g-4">

        {{-- Sidebar desktop --}}
        <aside class="col-auto d-none d-lg-flex">
            <nav class="nav nav-pills flex-column gap-1 text-center" style="width:92px" aria-label="Điều hướng chính">
                @foreach ($nav as $item)
                    <a class="nav-link ht-tap px-1 py-2 {{ ($active ?? '') === $item['route'] ? 'active' : '' }}"
                       href="#"
                       @if (($active ?? '') === $item['route']) aria-current="page" @endif>
                        <i class="bi {{ $item['icon'] }} d-block fs-5 mb-1" aria-hidden="true"></i>
                        <span style="font-size:11px">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="col">
            {{-- Topbar: chao + ngay | chips streak, diem, avatar gia su --}}
            <header class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div>
                    <h1 class="h5 mb-0">@yield('greeting', 'Chào bạn')</h1>
                    <p class="text-secondary small mb-0">{{ now()->timezone('Asia/Ho_Chi_Minh')->translatedFormat('l, d/m/Y') }}</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    @yield('topbar-chips')
                </div>
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
               href="#"
               @if (($active ?? '') === $item['route']) aria-current="page" @endif>
                <i class="bi {{ $item['icon'] }} d-block fs-5" aria-hidden="true"></i>
                <span style="font-size:10px">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
@endsection
