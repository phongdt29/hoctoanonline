@extends('layouts.base')

@section('title', 'Hoàn thiện hồ sơ')

@section('body')
<main class="container py-4 py-lg-5">
    <div class="mx-auto" style="max-width:640px">
        <div class="text-center mb-4">
            <h1 class="h4 mb-1">Chào {{ $student->full_name }}!</h1>
            <p class="text-secondary small mb-0">Điền vài thông tin để A.I cá nhân hóa lộ trình cho bạn.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success py-2 small">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('onboarding.store') }}" novalidate>
            @csrf

            {{-- Buoc 1: thong tin ca nhan --}}
            <div class="card mb-3">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3"><span class="badge text-bg-primary rounded-pill me-2 num">1</span>Thông tin cá nhân</h2>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="full_name">Họ và tên</label>
                        <input id="full_name" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name', $student->full_name) }}" required>
                        @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="date_of_birth">Ngày sinh</label>
                            <input id="date_of_birth" type="date" name="date_of_birth"
                                   class="form-control @error('date_of_birth') is-invalid @enderror"
                                   value="{{ old('date_of_birth', $student->date_of_birth?->toDateString()) }}" required>
                            @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold" for="phone">Số điện thoại</label>
                            <input id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $student->phone) }}" required>
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small fw-semibold" for="address">Địa chỉ nơi ở</label>
                        <input id="address" name="address" class="form-control @error('address') is-invalid @enderror"
                               value="{{ old('address', $student->address) }}" required>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- Buoc 2: hoc tap --}}
            <div class="card mb-3">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3"><span class="badge text-bg-primary rounded-pill me-2 num">2</span>Học tập</h2>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold" for="school_name">Trường học</label>
                            <input id="school_name" name="school_name" class="form-control @error('school_name') is-invalid @enderror"
                                   value="{{ old('school_name', $student->school_name) }}" required>
                            @error('school_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6 col-sm-3">
                            <label class="form-label small fw-semibold" for="grade">Khối lớp</label>
                            <select id="grade" name="grade" class="form-select @error('grade') is-invalid @enderror" required>
                                <option value="">—</option>
                                @foreach (range(1, 12) as $g)
                                    <option value="{{ $g }}" @selected(old('grade', $student->grade) == $g)>Lớp {{ $g }}</option>
                                @endforeach
                            </select>
                            @error('grade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6 col-sm-3">
                            <label class="form-label small fw-semibold" for="math_gpa">Điểm TB toán</label>
                            <input id="math_gpa" type="number" step="0.1" min="0" max="10" name="math_gpa"
                                   class="form-control num @error('math_gpa') is-invalid @enderror"
                                   value="{{ old('math_gpa', $student->math_gpa) }}" required>
                            @error('math_gpa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small fw-semibold d-block">Học lực tự đánh giá</label>
                        @php $lvl = old('self_assessed_level', $student->self_assessed_level); @endphp
                        <div class="btn-group w-100" role="group">
                            @foreach (['trung_binh' => 'Trung bình', 'kha' => 'Khá', 'gioi' => 'Giỏi'] as $val => $label)
                                <input type="radio" class="btn-check" name="self_assessed_level" id="lvl-{{ $val }}" value="{{ $val }}" @checked($lvl === $val) required>
                                <label class="btn btn-outline-primary" for="lvl-{{ $val }}">{{ $label }}</label>
                            @endforeach
                        </div>
                        @error('self_assessed_level') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- Buoc 3: ca nhan hoa --}}
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3"><span class="badge text-bg-primary rounded-pill me-2 num">3</span>Cá nhân hóa</h2>

                    <label class="form-label small fw-semibold d-block">Bạn muốn học với</label>
                    @php $tg = old('tutor_gender', $student->tutor_gender ?? 'thay'); @endphp
                    <div class="d-flex gap-2 mb-3">
                        @foreach (['thay' => ['Thầy', 'bi-person'], 'co' => ['Cô', 'bi-person-heart']] as $val => [$label, $icon])
                            <input type="radio" class="btn-check" name="tutor_gender" id="tg-{{ $val }}" value="{{ $val }}" @checked($tg === $val) required>
                            <label class="btn btn-outline-primary flex-fill ht-tap d-flex flex-column py-2" for="tg-{{ $val }}">
                                <i class="bi {{ $icon }} fs-5"></i><span class="small">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <label class="form-label small fw-semibold d-block">Màu yêu thích</label>
                    @php $fc = old('favorite_color', $student->favorite_color); @endphp
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach (config('hoctoan.personalization.colors') as $color)
                            <input type="radio" class="btn-check" name="favorite_color" id="fc-{{ $loop->index }}" value="{{ $color['hex'] }}" @checked($fc === $color['hex']) required>
                            <label class="btn border ht-tap p-0" for="fc-{{ $loop->index }}" title="{{ $color['name'] }}"
                                   style="width:40px;height:40px;background:{{ $color['hex'] }};outline-offset:2px"></label>
                        @endforeach
                    </div>
                    @error('favorite_color') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                    <label class="form-label small fw-semibold d-block">Sở thích (không bắt buộc)</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['Bóng đá', 'Game', 'Âm nhạc', 'Vẽ', 'Đọc sách', 'Khoa học'] as $hobby)
                            <input type="checkbox" class="btn-check" name="interests[]" id="hb-{{ $loop->index }}" value="{{ $hobby }}"
                                   @checked(in_array($hobby, old('interests', $student->interests ?? [])))>
                            <label class="btn btn-sm btn-outline-primary rounded-pill" for="hb-{{ $loop->index }}">{{ $hobby }}</label>
                        @endforeach
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 ht-tap">Lưu và làm bài kiểm tra</button>
        </form>
    </div>
</main>
@endsection
