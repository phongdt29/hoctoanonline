@extends('layouts.admin')

@section('title', 'Nhà cung cấp AI')
@section('page-title', 'Nhà cung cấp AI')

@section('page-actions')
    <a href="{{ route('admin.home') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Về báo cáo</a>
@endsection

@section('content')
@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger py-2 small mb-3">
        @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif

<x-card title="Danh sách nhà cung cấp" icon="bi-hdd-network" class="mb-4">
    <p class="text-secondary small">
        Thứ tự failover: <strong>active</strong> trước, <strong>priority</strong> nhỏ chạy trước.
        Key được mã hoá — chỉ hiện 4 ký tự cuối. Để trống ô key khi sửa = giữ key cũ.
    </p>

    @forelse ($providers as $provider)
        <form method="POST" action="{{ route('admin.providers.update', $provider) }}"
              class="border rounded-3 p-3 mb-3" style="border-color:var(--ht-line) !important">
            @csrf
            @method('PUT')
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Tên</label>
                    <input name="name" value="{{ $provider->name }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Base URL</label>
                    <input name="base_url" value="{{ $provider->base_url }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Model</label>
                    <input name="model" value="{{ $provider->models['default'] ?? '' }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Key mới <span class="text-secondary">({{ $provider->maskedApiKey() }})</span></label>
                    <input name="api_key" class="form-control form-control-sm" placeholder="giữ nguyên" autocomplete="off">
                </div>
                <div class="col-4 col-md-1">
                    <label class="form-label small mb-1">Ưu tiên</label>
                    <input name="priority" type="number" min="0" max="100" value="{{ $provider->priority }}"
                           class="form-control form-control-sm num" required>
                </div>
                <div class="col-4 col-md-1">
                    <label class="form-label small mb-1">Trạng thái</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="active" @selected($provider->status === 'active')>Bật</option>
                        <option value="disabled" @selected($provider->status === 'disabled')>Tắt</option>
                    </select>
                </div>
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button class="btn btn-sm btn-primary flex-grow-1" title="Lưu"><i class="bi bi-check-lg"></i> Lưu</button>
                </div>
            </div>
        </form>

        {{-- Test + Xoa: form rieng (HTML khong cho long form) --}}
        <div class="d-flex gap-2" style="margin-top:-.5rem;margin-bottom:1.25rem">
            <form method="POST" action="{{ route('admin.providers.test', $provider) }}">
                @csrf
                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-lightning-charge"></i> Test key</button>
            </form>
            <form method="POST" action="{{ route('admin.providers.destroy', $provider) }}"
                  onsubmit="return confirm('Xoá nhà cung cấp &quot;{{ $provider->name }}&quot;?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Xoá</button>
            </form>
        </div>
    @empty
        <x-empty-state icon="bi-hdd-network" title="Chưa có nhà cung cấp nào">
            Thêm nhà cung cấp AI đầu tiên ở form bên dưới.
        </x-empty-state>
    @endforelse
</x-card>

<x-card title="Thêm nhà cung cấp mới" icon="bi-plus-circle">
    <form method="POST" action="{{ route('admin.providers.store') }}">
        @csrf
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Tên</label>
                <input name="name" value="{{ old('name', 'Gemini') }}" class="form-control form-control-sm" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Base URL</label>
                <input name="base_url" value="{{ old('base_url', 'https://generativelanguage.googleapis.com/v1beta') }}"
                       class="form-control form-control-sm" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Model</label>
                <input name="model" value="{{ old('model', 'gemini-flash-latest') }}" class="form-control form-control-sm" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">API key</label>
                <input name="api_key" class="form-control form-control-sm" required autocomplete="off">
            </div>
            <div class="col-4 col-md-1">
                <label class="form-label small mb-1">Ưu tiên</label>
                <input name="priority" type="number" min="0" max="100" value="{{ old('priority', 0) }}"
                       class="form-control form-control-sm num" required>
            </div>
            <div class="col-4 col-md-1">
                <label class="form-label small mb-1">Trạng thái</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="active">Bật</option>
                    <option value="disabled">Tắt</option>
                </select>
            </div>
            <div class="col-4 col-md-1">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg"></i></button>
            </div>
        </div>
    </form>
</x-card>
@endsection
