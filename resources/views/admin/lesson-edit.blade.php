@extends('layouts.admin')

@section('title', 'Soạn: ' . $lesson->title)
@section('page-title', 'Soạn bài')

@section('page-actions')
    <a href="{{ route('admin.lessons') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Danh sách</a>
@endsection

@section('content')
@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger py-2 small">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

<p class="text-secondary small">
    Học sinh: <strong>{{ $lesson->module?->curriculum?->student?->full_name ?? '—' }}</strong>.
    Chèn công thức bằng cú pháp LaTeX giữa <code>$…$</code> (vd <code>$x^2+1$</code>) hoặc
    <code>$$…$$</code> cho công thức riêng dòng. Bảng bên phải xem trước ngay.
</p>

<form method="POST" action="{{ route('admin.lessons.update', $lesson) }}" id="lessonForm">
    @csrf
    @method('PUT')

    <div class="row g-4">
        {{-- Cot soan --}}
        <div class="col-lg-7">
            <x-card class="mb-3">
                <label class="form-label small fw-semibold">Tiêu đề</label>
                <input name="title" value="{{ old('title', $lesson->title) }}"
                       class="form-control mb-3" maxlength="200" required>

                <label class="form-label small fw-semibold">Lý thuyết</label>

                {{-- Thanh cong cu cong thuc --}}
                <div class="d-flex flex-wrap gap-1 mb-2" id="mathToolbar" aria-label="Chèn công thức">
                    @php
                        $tools = [
                            ['Phân số', '$\frac{a}{b}$'],
                            ['Luỹ thừa', '$x^{2}$'],
                            ['Chỉ số', '$x_{1}$'],
                            ['Căn', '$\sqrt{x}$'],
                            ['Căn bậc n', '$\sqrt[n]{x}$'],
                            ['≤ ≥', '$\leq \geq$'],
                            ['≠', '$\neq$'],
                            ['± ', '$\pm$'],
                            ['×', '$\times$'],
                            ['Tổng', '$\sum_{i=1}^{n}$'],
                            ['Tích phân', '$\int_{a}^{b} f(x)\,dx$'],
                            ['Giới hạn', '$\lim_{x\to 0}$'],
                            ['π', '$\pi$'],
                            ['Δ', '$\Delta$'],
                            ['Riêng dòng', "$$\n\n$$"],
                        ];
                    @endphp
                    @foreach ($tools as [$label, $snippet])
                        <button type="button" class="btn btn-sm btn-outline-secondary math-btn"
                                data-snippet="{{ $snippet }}">{{ $label }}</button>
                    @endforeach
                </div>

                <textarea name="theory_content" id="theoryInput" rows="14"
                          class="form-control font-num math-src" maxlength="20000"
                          data-preview="#theoryPreview" required>{{ old('theory_content', $lesson->theory_content) }}</textarea>
                <div class="form-text">Enter để xuống dòng. Dùng ký hiệu LaTeX cho công thức.</div>
            </x-card>

            @if ($lesson->exercises->isNotEmpty())
                <x-card title="Bài tập" icon="bi-pencil-square">
                    @php $diffLabel = ['easy' => 'Dễ', 'medium' => 'Trung bình', 'hard' => 'Khó']; @endphp
                    @foreach ($lesson->exercises as $ex)
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">
                                Bài tập #{{ $ex->id }}
                                <span class="badge rounded-pill text-bg-light border">{{ $diffLabel[$ex->difficulty] ?? $ex->difficulty }}</span>
                            </label>
                            <textarea name="exercises[{{ $ex->id }}]" rows="3"
                                      class="form-control font-num math-src" maxlength="5000"
                                      data-preview="#exPreview{{ $ex->id }}">{{ $ex->content }}</textarea>
                        </div>
                    @endforeach
                </x-card>
            @endif
        </div>

        {{-- Cot xem truoc --}}
        <div class="col-lg-5">
            <div class="sticky-top" style="top:80px">
                <x-card title="Xem trước" icon="bi-eye" class="mb-3">
                    <h2 class="h5" id="titlePreview">{{ $lesson->title }}</h2>
                    <div id="theoryPreview" class="lesson-theory"></div>
                </x-card>

                @if ($lesson->exercises->isNotEmpty())
                    <x-card title="Bài tập (xem trước)" icon="bi-eye">
                        @foreach ($lesson->exercises as $ex)
                            <div id="exPreview{{ $ex->id }}" class="border rounded-3 p-2 mb-2 small"></div>
                        @endforeach
                    </x-card>
                @endif
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3 mb-5">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Lưu bài học</button>
        <a href="{{ route('admin.lessons') }}" class="btn btn-outline-secondary">Huỷ</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
    let lastFocused = document.getElementById('theoryInput');

    // Ghi nho o dang soan de nut cong thuc chen dung cho.
    $('.math-src').on('focus', function () { lastFocused = this; });

    // Chen snippet tai vi tri con tro cua textarea dang focus.
    $('.math-btn').on('click', function () {
        const snippet = $(this).data('snippet');
        const el = lastFocused || document.getElementById('theoryInput');
        const start = el.selectionStart, end = el.selectionEnd;
        el.value = el.value.slice(0, start) + snippet + el.value.slice(end);
        // Dat con tro vao giua snippet (sau ky tu '{' dau tien neu co, khong thi cuoi).
        const brace = snippet.indexOf('{');
        const caret = brace >= 0 ? start + brace + 1 : start + snippet.length;
        el.focus();
        el.setSelectionRange(caret, caret);
        renderPreview(el);
    });

    // Xem truoc: do text vao vung preview + typeset MathJax (debounce).
    function renderPreview(el) {
        const sel = el.getAttribute('data-preview');
        if (!sel) return;
        const target = document.querySelector(sel);
        if (!target) return;
        target.textContent = el.value;         // textContent => an toan XSS, MathJax quet text node
        window.htTypeset && window.htTypeset(target);
    }

    let timer;
    $('.math-src').on('input', function () {
        const el = this;
        clearTimeout(timer);
        timer = setTimeout(() => renderPreview(el), 300);
    });

    // Tieu de xem truoc song song.
    $('input[name=title]').on('input', function () {
        document.getElementById('titlePreview').textContent = this.value;
    });

    // Render lan dau khi MathJax san sang.
    function initialRender() {
        if (!(window.MathJax && window.MathJax.typesetPromise)) { return setTimeout(initialRender, 150); }
        $('.math-src').each(function () { renderPreview(this); });
    }
    initialRender();
});
</script>
@endpush
