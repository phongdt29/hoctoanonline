@extends('layouts.student')

@section('title', 'Giải bài')
@section('greeting', 'Giải bài cùng A.I')
@php $active = 'solver'; @endphp

@section('content')
<div class="mx-auto" style="max-width:720px">
    <x-card title="Nhập đề toán" icon="bi-pencil-square" class="mb-4">
        <p class="text-secondary small">A.I sẽ <strong>gợi mở</strong> cách làm trước — không cho đáp án ngay để bạn tự tư duy.</p>

        <div class="mb-2">
            <textarea id="sv-problem" class="form-control" rows="3" placeholder="VD: Tính 1/2 + 1/3"></textarea>
        </div>

        {{-- Ban phim toan de soan cong thuc --}}
        <div class="mb-3">
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#svMath">
                <i class="bi bi-calculator"></i> Bàn phím toán
            </button>
            <div class="collapse mt-2" id="svMath">
                <div class="border rounded-3 p-2" style="border-color:var(--ht-line) !important">
                    <label class="form-label small text-secondary mb-1">Soạn công thức rồi bấm “Chèn vào đề”</label>
                    <math-field id="sv-mf" class="form-control" style="font-size:1.3rem; min-height:52px"></math-field>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <button type="button" id="sv-kb" class="btn btn-sm btn-outline-secondary"><i class="bi bi-keyboard"></i> Bàn phím ảo</button>
                        <button type="button" id="sv-ins" class="btn btn-sm btn-primary"><i class="bi bi-box-arrow-in-down"></i> Chèn vào đề</button>
                        <button type="button" id="sv-clr" class="btn btn-sm btn-outline-secondary">Xoá</button>
                        <span class="small text-secondary align-self-center">Gõ như viết tay: <code>1/2</code>→phân số, <code>sqrt</code>→căn, <code>^</code>→mũ.</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Xem truoc de dang soan (cong thuc render bang MathJax) --}}
        <div id="sv-preview-wrap" class="mb-3 d-none">
            <label class="form-label small text-secondary mb-1"><i class="bi bi-eye"></i> Xem trước đề</label>
            <div id="sv-preview" class="border rounded-3 p-3 lesson-theory bg-white" style="border-color:var(--ht-line) !important"></div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button id="sv-text" class="btn btn-primary ht-tap">Nhờ gợi ý</button>
            <label class="btn btn-outline-primary ht-tap mb-0">
                <i class="bi bi-camera"></i> Chụp ảnh đề
                <input id="sv-image" type="file" accept="image/*" hidden>
            </label>
        </div>
    </x-card>

    {{-- Ket qua --}}
    <div id="sv-result" class="d-none">
        <x-card title="Gợi ý" icon="bi-lightbulb" class="mb-3">
            <div id="sv-parsed" class="alert alert-light border small d-none"></div>
            <div id="sv-hint" class="mb-3"></div>
            <div class="d-flex gap-2 flex-wrap">
                <button id="sv-more" class="btn btn-sm btn-outline-primary ht-tap">Gợi ý thêm</button>
                <button id="sv-full" class="btn btn-sm btn-outline-primary ht-tap">Xem lời giải</button>
                <button id="sv-similar" class="btn btn-sm btn-outline-primary ht-tap">Bài tương tự</button>
            </div>
        </x-card>

        <x-card id="sv-solution-card" title="Lời giải" icon="bi-check2-circle" class="mb-3 d-none">
            <div id="sv-solution"></div>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mathlive@0.110.0/mathlive.min.js"></script>
<script>
$(function () {
    const mf   = document.getElementById('sv-mf');
    const prob = document.getElementById('sv-problem');
    if (!mf) return;

    mf.mathVirtualKeyboardPolicy = 'manual';   // ta tu bat ban phim ao MathLive

    // ----- Xem truoc de dang soan -----
    const preview = document.getElementById('sv-preview');
    const wrap    = document.getElementById('sv-preview-wrap');
    let ptimer;
    function renderPreview() {
        const v = (prob.value || '').trim();
        if (!v) { wrap.classList.add('d-none'); return; }
        wrap.classList.remove('d-none');
        preview.textContent = prob.value;          // textContent => an toan XSS; MathJax quet text node
        window.htTypeset && window.htTypeset(preview);
    }
    prob.addEventListener('input', () => { clearTimeout(ptimer); ptimer = setTimeout(renderPreview, 300); });
    renderPreview();

    const kbBtn = document.getElementById('sv-kb');
    kbBtn.addEventListener('mousedown', e => e.preventDefault());
    kbBtn.addEventListener('click', () => {
        const kb = window.mathVirtualKeyboard; if (kb) kb.visible = !kb.visible; mf.focus();
    });
    mf.addEventListener('focusin', () => { const kb = window.mathVirtualKeyboard; if (kb) kb.visible = true; });

    // Chen cong thuc vao o de tai vi tri con tro. Chan chen khi cong thuc chua hoan chinh.
    const insBtn = document.getElementById('sv-ins');
    insBtn.addEventListener('mousedown', e => e.preventDefault());
    insBtn.addEventListener('click', () => {
        const latex = (mf.value || '').trim();
        if (!latex || latex.includes('\\placeholder')) {
            alert('Công thức chưa hoàn chỉnh (còn ô trống). Điền đủ rồi hãy chèn.');
            mf.focus(); return;
        }
        const ins = '$' + latex + '$';
        const s = prob.selectionStart ?? prob.value.length;
        const e = prob.selectionEnd ?? prob.value.length;
        prob.value = prob.value.slice(0, s) + ins + prob.value.slice(e);
        const caret = s + ins.length;
        prob.focus(); prob.setSelectionRange(caret, caret);
        mf.value = '';
        renderPreview();      // cap nhat xem truoc ngay sau khi chen
    });

    document.getElementById('sv-clr').addEventListener('click', () => { mf.value = ''; mf.focus(); });
});
</script>
<script src="{{ asset('js/solver.js') }}"></script>
@endpush
