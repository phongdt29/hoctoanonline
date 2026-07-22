@php
    // Cau da co (key so) hien checkbox "Xoa khi luu"; cau moi (key new_*) hien nut xoa khoi DOM.
    $isNew = ! is_numeric($key);
    $prev  = 'exPrev_' . $key;
@endphp
<div class="ex-row border rounded-3 p-3 mb-2" style="border-color:var(--ht-line) !important">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
        <div class="d-flex align-items-center gap-2">
            <span class="small fw-semibold">{{ $label }}</span>
            <select name="exercises[{{ $key }}][difficulty]" class="form-select form-select-sm" style="width:auto">
                <option value="easy" @selected($difficulty === 'easy')>Dễ</option>
                <option value="medium" @selected($difficulty === 'medium')>Trung bình</option>
                <option value="hard" @selected($difficulty === 'hard')>Khó</option>
            </select>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary js-clone-row" title="Nhân bản câu này">
                <i class="bi bi-files"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary js-similar" title="AI tạo câu tương tự">
                <i class="bi bi-stars"></i>
            </button>
            @if ($isNew)
                <button type="button" class="btn btn-sm btn-outline-danger js-remove-row"><i class="bi bi-x-lg"></i></button>
            @else
                <label class="small text-danger mb-0">
                    <input type="checkbox" name="exercises[{{ $key }}][_delete]" value="1" class="ex-delete"> Xoá
                </label>
            @endif
        </div>
    </div>

    <div class="math-field-group mb-2">
        <textarea name="exercises[{{ $key }}][content]" rows="2"
                  class="form-control form-control-sm font-num math-src" maxlength="5000"
                  data-preview="#{{ $prev }}" placeholder="Đề bài… (công thức đặt trong $…$)">{{ $content }}</textarea>
        <div class="text-end mt-1">
            <button type="button" class="btn btn-sm btn-outline-primary js-open-math">
                <i class="bi bi-calculator"></i> Chèn công thức
            </button>
        </div>
    </div>

    <div class="input-group input-group-sm mb-2">
        <span class="input-group-text">Đáp án</span>
        <input name="exercises[{{ $key }}][answer]" value="{{ $answer }}" class="form-control" maxlength="5000"
               placeholder="Đáp án / lời giải ngắn">
    </div>

    <div id="{{ $prev }}" class="border rounded-3 p-2 small bg-white lesson-theory"></div>
</div>
