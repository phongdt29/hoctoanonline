{{-- Nut dang xuat — POST route('logout') kem CSRF. Prop `class` cho nut. --}}
@props(['label' => 'Đăng xuất', 'class' => 'btn btn-outline-primary'])

<form method="POST" action="{{ route('logout') }}" class="d-inline">
    @csrf
    <button type="submit" class="{{ $class }}">
        <i class="bi bi-box-arrow-right"></i> {{ $label }}
    </button>
</form>
