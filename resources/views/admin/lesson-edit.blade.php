@extends('layouts.admin')

@section('title', 'Soạn: ' . $lesson->title)
@section('page-title', 'Soạn bài / soạn đề')

@php $student = $lesson->module?->curriculum?->student; @endphp

@section('page-actions')
    <a href="{{ route('admin.lessons') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left"></i> Danh sách</a>
    <button type="submit" form="lessonForm" class="btn btn-sm btn-primary"><i class="bi bi-check-lg"></i> Lưu bài học</button>
@endsection

@push('head')
<style>
    /* ===== Ban phim Toan hoc ===== */
    .mk-panel { border:1px solid var(--ht-line); border-radius:16px; padding:1rem; background:#fff; }
    .mk-badge { width:30px; height:30px; border-radius:9px; display:inline-grid; place-items:center;
                background:var(--ht-gradient, var(--ht-primary)); color:#fff; font-size:.95rem; }
    .mk-dot   { display:inline-block; width:7px; height:7px; border-radius:50%; background:#22c55e; margin-right:4px; }

    .mk-display { border:1.5px solid var(--ht-line); border-radius:12px; padding:.75rem 1rem; background:#fff;
                  min-height:92px; display:flex; align-items:center; justify-content:center; }
    .mk-display math-field { width:100%; font-size:1.7rem; border:none; outline:none; background:transparent; text-align:center; }

    .mk-tabs { display:grid; grid-template-columns:repeat(3,1fr); gap:.4rem;
               background:var(--ht-bg, #f5f6fa); padding:.3rem; border-radius:11px; }
    .mk-tab  { border:none; background:transparent; border-radius:8px; padding:.5rem .25rem; font-size:.85rem;
               font-weight:600; color:var(--ht-ink-soft, #6b7280); cursor:pointer; transition:background .15s,color .15s; }
    .mk-tab:hover  { background:rgba(var(--ht-primary-rgb), .08); }
    .mk-tab.active { background:var(--ht-primary); color:#fff; }

    .mk-keys { display:grid; grid-template-columns:repeat(auto-fit, minmax(54px,1fr)); gap:.45rem; }
    .mk-key  { min-height:46px; border:1px solid var(--ht-line); border-radius:10px; background:#fff;
               font-size:1rem; color:var(--ht-ink); cursor:pointer; transition:border-color .12s, box-shadow .12s, transform .06s;
               display:grid; place-items:center; }
    .mk-key:hover  { border-color:var(--ht-primary); box-shadow:0 2px 8px rgba(var(--ht-primary-rgb), .16); }
    .mk-key:active { transform:translateY(1px); }
    .mk-key-wide   { grid-column:span 2; }
    .mk-key .ML__latex, .mk-key mjx-container { font-size:1rem !important; }
</style>
@endpush

@section('content')
@if (session('status'))
    <div class="alert alert-success py-2 small">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger py-2 small">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

<p class="text-secondary small">
    Học sinh: <strong>{{ $student?->full_name ?? '—' }}</strong>.
    Dùng <strong>Bàn phím Toán học</strong> bên phải để soạn công thức rồi chèn vào ô đang gõ.
</p>

{{-- ============ CONG CU SOAN DE (form rieng, KHONG long trong form chinh) ============ --}}
<x-card class="mb-3">
    <div class="d-flex flex-wrap gap-2 mb-2">
        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#toolAi">
            <i class="bi bi-magic"></i> AI tự sinh đề
        </button>
        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#toolOcr">
            <i class="bi bi-camera"></i> Chụp ảnh → OCR
        </button>
        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#toolBulk">
            <i class="bi bi-list-ol"></i> Nhập nhanh nhiều câu
        </button>
        <span class="small text-secondary align-self-center">
            Câu tạo ra hiện ở khung “Bài tập” bên dưới để kiểm tra rồi bấm <strong>Lưu bài học</strong>.
        </span>
    </div>

    {{-- AI sinh de --}}
    <div class="collapse" id="toolAi">
        <form method="POST" action="{{ route('admin.lessons.ai-generate', $lesson) }}"
              class="border rounded-3 p-3 mt-2" style="border-color:var(--ht-line) !important">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Chủ đề</label>
                    <input name="topic" value="{{ old('topic', $lesson->title) }}" class="form-control form-control-sm" required
                           placeholder="vd: Phương trình bậc hai">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Lớp</label>
                    <select name="grade" class="form-select form-select-sm">
                        @for ($g = 6; $g <= 12; $g++)
                            <option value="{{ $g }}" @selected(old('grade', $student?->grade ?? 9) == $g)>Lớp {{ $g }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Độ khó</label>
                    <select name="difficulty" class="form-select form-select-sm">
                        <option value="easy" @selected(old('difficulty') === 'easy')>Dễ</option>
                        <option value="medium" @selected(old('difficulty', 'medium') === 'medium')>Trung bình</option>
                        <option value="hard" @selected(old('difficulty') === 'hard')>Khó</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Số câu</label>
                    <input name="count" type="number" min="1" max="10" value="{{ old('count', 3) }}"
                           class="form-control form-control-sm num" required>
                </div>
                <div class="col-6 col-md-2">
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-magic"></i> Sinh đề</button>
                </div>
            </div>
            <div class="form-text">AI sinh đề bài + đáp án, tự chèn công thức. Sinh xong xem lại rồi Lưu.</div>
        </form>
    </div>

    {{-- OCR anh --}}
    <div class="collapse" id="toolOcr">
        <form method="POST" action="{{ route('admin.lessons.ocr', $lesson) }}" enctype="multipart/form-data"
              class="border rounded-3 p-3 mt-2" style="border-color:var(--ht-line) !important">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small mb-1">Ảnh đề (jpg/png/webp, tối đa 5MB)</label>
                    <input name="image" type="file" accept="image/*" capture="environment"
                           class="form-control form-control-sm" required>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-camera"></i> Nhận diện đề</button>
                </div>
            </div>
            <div class="form-text">Chụp/tải ảnh đề trên giấy hoặc sách → AI chuyển thành đề số có công thức.</div>
        </form>
    </div>

    {{-- Nhap nhanh nhieu cau (khong dung AI -> tuc thi) --}}
    <div class="collapse" id="toolBulk">
        <form method="POST" action="{{ route('admin.lessons.bulk', $lesson) }}"
              class="border rounded-3 p-3 mt-2" style="border-color:var(--ht-line) !important">
            @csrf
            <label class="form-label small mb-1">Mỗi dòng một câu — dán cả danh sách vào đây</label>
            <textarea name="bulk" rows="6" class="form-control form-control-sm font-num" required
placeholder="Rút gọn $\frac{2}{4}$ | $\frac{1}{2}$
[khó] Giải $x^2-5x+6=0$ | x=2; x=3
Tính diện tích hình tròn bán kính 3"></textarea>
            <div class="form-text">
                Dấu <code>|</code> tách đáp án (không bắt buộc). Tiền tố <code>[dễ]</code> <code>[tb]</code>
                <code>[khó]</code> đặt độ khó (mặc định trung bình). Số thứ tự đầu dòng (“1.”, “Câu 2:”) tự được bỏ.
            </div>
            <button class="btn btn-sm btn-primary mt-1"><i class="bi bi-plus-lg"></i> Thêm tất cả</button>
        </form>
    </div>
</x-card>

{{-- ============ FORM CHINH ============ --}}
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
                <div class="math-field-group">
                    <textarea name="theory_content" id="theoryInput" rows="10"
                              class="form-control font-num math-src" maxlength="20000"
                              data-preview="#theoryPreview" required>{{ old('theory_content', $lesson->theory_content) }}</textarea>
                    <div class="d-flex justify-content-between align-items-center gap-2 mt-1">
                        <span class="form-text mb-0">Gõ chữ bình thường; cần công thức thì bấm nút bên phải.</span>
                        <button type="button" class="btn btn-sm btn-outline-primary js-open-math">
                            <i class="bi bi-calculator"></i> Chèn công thức
                        </button>
                    </div>
                </div>
            </x-card>

            {{-- ===== Bai tap: CRUD ===== --}}
            <x-card title="Bài tập" icon="bi-pencil-square">
                <div id="exList">
                    @foreach ($lesson->exercises as $ex)
                        @include('admin.partials.exercise-row', [
                            'key' => $ex->id,
                            'difficulty' => $ex->difficulty,
                            'content' => $ex->content,
                            'answer' => $ex->answer['value'] ?? '',
                            'label' => 'Câu #' . $ex->id,
                        ])
                    @endforeach
                </div>

                <div id="exEmpty" class="text-secondary small {{ $lesson->exercises->isNotEmpty() ? 'd-none' : '' }}">
                    Chưa có câu nào. Bấm “Thêm câu”, hoặc dùng “AI sinh đề” / “OCR ảnh” ở trên.
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addExercise">
                        <i class="bi bi-plus-lg"></i> Thêm câu
                    </button>
                    <span class="small text-secondary">
                        Phím tắt: <kbd>Alt</kbd>+<kbd>N</kbd> thêm câu ·
                        <kbd>Ctrl</kbd>+<kbd>M</kbd> công thức ·
                        <kbd>Ctrl</kbd>+<kbd>S</kbd> lưu ·
                        <i class="bi bi-files"></i> nhân bản · <i class="bi bi-stars"></i> câu tương tự
                    </span>
                </div>
            </x-card>
        </div>

        {{-- Cot phai: xem truoc --}}
        <div class="col-lg-5">
            <div class="sticky-top" style="top:80px">
                <x-card title="Xem trước lý thuyết" icon="bi-eye">
                    <h2 class="h5" id="titlePreview">{{ $lesson->title }}</h2>
                    <div id="theoryPreview" class="lesson-theory"></div>
                </x-card>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3 mb-5">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Lưu bài học</button>
        <a href="{{ route('admin.lessons') }}" class="btn btn-outline-secondary">Huỷ</a>
    </div>
</form>

{{-- Form an cho "tao cau tuong tu": nam NGOAI form chinh (HTML khong cho long form) --}}
<form method="POST" action="{{ route('admin.lessons.similar', $lesson) }}" id="similarForm" class="d-none">
    @csrf
    <input type="hidden" name="source">
    <input type="hidden" name="count" value="3">
    <input type="hidden" name="difficulty" value="medium">
</form>

{{-- Ban phim Toan hoc dung chung — JS di chuyen xuong ngay duoi o dang soan --}}
<div id="mathPanelWrap" class="d-none">
    @include('admin.partials.math-keyboard')
</div>

{{-- Template cho cau moi (JS clone, __ID__ -> new_N) --}}
<template id="exTemplate">
    @include('admin.partials.exercise-row', [
        'key' => '__ID__', 'difficulty' => 'medium', 'content' => '', 'answer' => '', 'label' => 'Câu mới',
    ])
</template>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mathlive@0.110.0/mathlive.min.js"></script>
<script>
$(function () {
    let lastFocused = document.getElementById('theoryInput');
    let newCounter = 0;
    const mf = document.getElementById('mf');

    // MathLive: tat ban phim ao mac dinh — ta da co ban phim rieng.
    if (mf) mf.mathVirtualKeyboardPolicy = 'manual';

    // Ghi nho o dang soan (delegation — dung cho ca cau them sau).
    $(document).on('focusin', '.math-src', function () { lastFocused = this; });

    function insertAtCursor(text) {
        const el = lastFocused || document.getElementById('theoryInput');
        const start = el.selectionStart, end = el.selectionEnd;
        el.value = el.value.slice(0, start) + text + el.value.slice(end);
        const caret = start + text.length;
        el.focus();
        el.setSelectionRange(caret, caret);
        renderPreview(el);
    }

    // ===== Ban phim Toan hoc =====
    // Nhan phim la LaTeX -> phai typeset. Chi typeset KHI pane da hien,
    // vi MathJax do sai kich thuoc trong element display:none.
    function typesetPane($pane) {
        if (!$pane.length || $pane.data('typeset')) return;
        if (!(window.MathJax && window.MathJax.typesetPromise)) return setTimeout(() => typesetPane($pane), 150);
        $pane.data('typeset', true);
        window.htTypeset($pane[0]);
    }

    // Doi tab.
    $('.mk-tab').on('click', function () {
        const tab = $(this).data('tab');
        $('.mk-tab').removeClass('active');
        $(this).addClass('active');
        $('.mk-keys').addClass('d-none');
        const $pane = $('.mk-keys[data-pane="' + tab + '"]').removeClass('d-none');
        typesetPane($pane);
    });

    // Bam phim: chen LaTeX hoac chay lenh MathLive. preventDefault de math-field khong mat focus.
    $('.mk-keys').on('mousedown', '.mk-key', e => e.preventDefault());
    $('.mk-keys').on('click', '.mk-key', function () {
        const ins = $(this).attr('data-ins'), cmd = $(this).attr('data-cmd');
        if (ins) mf.insert(ins, { focus: true });
        else if (cmd) mf.executeCommand(cmd);
        mf.focus();
    });

    // MathLive de lai \placeholder{} cho o trong chua dien. KHONG duoc cat bo —
    // cat ngoac se lam hong lenh (\frac{1}{...} -> \frac{1}, thieu tham so).
    // Cong thuc chua hoan chinh thi TU CHOI chen va bao nguoi soan dien not.
    function formulaIssue(s) {
        if (!s) return 'Chưa nhập công thức nào.';
        if (s.includes('\\placeholder')) return 'Còn ô trống chưa điền — điền đủ rồi hãy chèn.';
        if (/^[\^_]/.test(s)) return 'Thiếu cơ số trước mũ/chỉ số — gõ x trước rồi bấm x².';

        return null;
    }

    let warnTimer;
    function warn(msg) {
        clearTimeout(warnTimer);
        $('#mkTarget').text(msg).addClass('text-danger fw-semibold');
        warnTimer = setTimeout(() => $('#mkTarget').text('Sẵn sàng nhập công thức').removeClass('text-danger fw-semibold'), 3500);
    }

    // Mo ban phim ngay duoi o dang soan (panel dung chung, di chuyen trong DOM).
    $(document).on('click', '.js-open-math', function () {
        const $group = $(this).closest('.math-field-group');
        const el = $group.find('.math-src')[0];
        if (el) lastFocused = el;
        $('#mathPanelWrap').removeClass('d-none').insertAfter($group);
        $('#mkTarget').text(el && el.id === 'theoryInput' ? 'Đang soạn: Lý thuyết' : 'Đang soạn: Bài tập');
        typesetPane($('.mk-keys').not('.d-none').first());   // nhan phim cua tab dang mo
        mf.focus();
        $('#mathPanelWrap')[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    });
    $('#mkClose').on('click', () => $('#mathPanelWrap').addClass('d-none'));

    // Nut hanh dong: giu focus o textarea dang soan.
    $('#insInline, #insBlock, #mfClear').on('mousedown', e => e.preventDefault());
    function insertFormula(wrap) {
        const v = (mf.value || '').trim();
        const issue = formulaIssue(v);
        if (issue) { warn(issue); mf.focus(); return; }    // khong chen cong thuc hong
        insertAtCursor(wrap === 'block' ? '\n$$' + v + '$$\n' : '$' + v + '$');
        mf.value = '';                                     // don san cho cong thuc tiep theo
    }
    $('#insInline').on('click', () => insertFormula('inline'));
    $('#insBlock').on('click', () => insertFormula('block'));
    $('#mfClear').on('click', () => { mf.value = ''; mf.focus(); });

    // ===== Xem truoc (textContent => an toan XSS; MathJax quet text node) =====
    function renderPreview(el) {
        const sel = el.getAttribute('data-preview');
        if (!sel) return;
        const target = document.querySelector(sel);
        if (!target) return;
        target.textContent = el.value;
        window.htTypeset && window.htTypeset(target);
    }

    const timers = new WeakMap();
    $(document).on('input', '.math-src', function () {
        const el = this;
        clearTimeout(timers.get(el));
        timers.set(el, setTimeout(() => renderPreview(el), 300));
    });

    $('input[name=title]').on('input', function () {
        document.getElementById('titlePreview').textContent = this.value;
    });

    // ===== Bai tap =====
    $(document).on('change', '.ex-delete', function () {
        $(this).closest('.ex-row').toggleClass('opacity-50', this.checked);
    });
    $(document).on('click', '.js-remove-row', function () {
        $(this).closest('.ex-row').remove();
    });
    function newRow() {
        const html = document.getElementById('exTemplate').innerHTML.replaceAll('__ID__', 'new_' + (++newCounter));
        $('#exEmpty').addClass('d-none');
        const $row = $(html);
        $('#exList').append($row);

        return $row;
    }

    $('#addExercise').on('click', () => newRow().find('.math-src').trigger('focus'));

    // Nhan ban cau: chep noi dung + dap an + do kho sang mot cau moi.
    $(document).on('click', '.js-clone-row', function () {
        const $src = $(this).closest('.ex-row');
        const $row = newRow();
        $row.find('.math-src').val($src.find('.math-src').val());
        $row.find('input[name$="[answer]"]').val($src.find('input[name$="[answer]"]').val());
        $row.find('select').val($src.find('select').val());
        const el = $row.find('.math-src')[0];
        autoGrow(el); renderPreview(el);
        $row[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    });

    // AI tao cau tuong tu: nap noi dung cau nguon vao form an roi gui.
    $(document).on('click', '.js-similar', function () {
        const $row = $(this).closest('.ex-row');
        const src = ($row.find('.math-src').val() || '').trim();
        if (!src) { alert('Câu này chưa có nội dung để làm mẫu.'); return; }
        const n = prompt('Tạo mấy câu tương tự? (1-10)', '3');
        if (n === null) return;
        const count = parseInt(n, 10);
        if (!(count >= 1 && count <= 10)) { alert('Nhập số từ 1 đến 10.'); return; }
        $('#similarForm [name=source]').val(src);
        $('#similarForm [name=count]').val(count);
        $('#similarForm [name=difficulty]').val($row.find('select').val() || 'medium');
        $('#similarForm')[0].submit();
    });

    // Textarea tu gian theo noi dung — khoi phai keo thanh cuon khi de dai.
    function autoGrow(el) {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight + 2, 420) + 'px';
    }
    $(document).on('input', '.math-src', function () { autoGrow(this); });
    $('.math-src').each(function () { autoGrow(this); });

    // ===== Phim tat =====
    $(document).on('keydown', function (e) {
        const ctrl = e.ctrlKey || e.metaKey;
        if (ctrl && e.key.toLowerCase() === 's') {           // Ctrl+S: luu
            e.preventDefault();
            $('#lessonForm')[0].requestSubmit();
        } else if (ctrl && e.key.toLowerCase() === 'm') {    // Ctrl+M: mo ban phim cong thuc
            e.preventDefault();
            const $group = $(lastFocused).closest('.math-field-group');
            ($group.length ? $group : $('#theoryInput').closest('.math-field-group'))
                .find('.js-open-math').trigger('click');
        } else if (e.altKey && e.key.toLowerCase() === 'n') { // Alt+N: them cau
            e.preventDefault();
            newRow().find('.math-src').trigger('focus');
        }
    });

    // Render lan dau khi MathJax san sang (nhan ban phim + xem truoc).
    (function initialRender() {
        if (!(window.MathJax && window.MathJax.typesetPromise)) return setTimeout(initialRender, 150);
        // Nhan phim typeset khi mo panel (typesetPane) — o day chi lo xem truoc.
        $('.math-src').each(function () { renderPreview(this); });
    })();
});
</script>
@endpush
