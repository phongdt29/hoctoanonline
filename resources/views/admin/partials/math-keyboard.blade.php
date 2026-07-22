{{--
    Ban phim Toan hoc — khung soan cong thuc truc quan.
    Engine: MathLive (<math-field>), nhan LaTeX; nhan chen vao textarea dang soan.
    Nhan phim: data-ins = LaTeX chen vao, data-cmd = lenh MathLive (xoa/di chuyen).
    #? la o trong cua MathLive — con tro nhay vao do sau khi chen.
--}}
@php
    $tabs = [
        'basic' => ['label' => 'Cơ bản', 'icon' => 'bi-grid-3x3-gap', 'keys' => [
            ['lbl' => '\(\frac{a}{b}\)', 'ins' => '\frac{#?}{#?}'],
            ['lbl' => '\(\sqrt{\ }\)',   'ins' => '\sqrt{#?}'],
            ['lbl' => '\(x^2\)',         'ins' => '#@^{2}'],    // #@ = ap vao ky tu truoc con tro
            ['lbl' => '\(x_y\)',         'ins' => '#@_{#?}'],
            ['lbl' => '\(( )\)',         'ins' => '\left(#?\right)'],
            ['lbl' => '\(\pi\)',         'ins' => '\pi'],
            ['lbl' => '\(+\)',           'ins' => '+'],
            ['lbl' => '\(-\)',           'ins' => '-'],
            ['lbl' => '\(\times\)',      'ins' => '\times'],
            ['lbl' => '\(\div\)',        'ins' => '\div'],
            ['lbl' => '\(=\)',           'ins' => '='],
            ['lbl' => '7', 'ins' => '7'], ['lbl' => '8', 'ins' => '8'], ['lbl' => '9', 'ins' => '9'],
            ['lbl' => '\(x\)', 'ins' => 'x'], ['lbl' => '\(y\)', 'ins' => 'y'],
            ['lbl' => '4', 'ins' => '4'], ['lbl' => '5', 'ins' => '5'], ['lbl' => '6', 'ins' => '6'],
            ['lbl' => '<i class="bi bi-backspace"></i>', 'cmd' => 'deleteBackward', 'wide' => true],
            ['lbl' => '1', 'ins' => '1'], ['lbl' => '2', 'ins' => '2'], ['lbl' => '3', 'ins' => '3'],
            ['lbl' => '0', 'ins' => '0'], ['lbl' => '.', 'ins' => '.'],
            ['lbl' => '<i class="bi bi-arrow-left"></i>',  'cmd' => 'moveToPreviousChar'],
            ['lbl' => '<i class="bi bi-arrow-down"></i>',  'cmd' => 'moveDown'],
            ['lbl' => '<i class="bi bi-arrow-up"></i>',    'cmd' => 'moveUp'],
            ['lbl' => '<i class="bi bi-arrow-right"></i>', 'cmd' => 'moveToNextChar'],
        ]],
        'algebra' => ['label' => 'Đại số', 'icon' => 'bi-superscript', 'keys' => [
            ['lbl' => '\(\sqrt[n]{\ }\)',      'ins' => '\sqrt[#?]{#?}'],
            ['lbl' => '\(x^n\)',               'ins' => '#@^{#?}'],
            ['lbl' => '\(|x|\)',               'ins' => '\left|#?\right|'],
            ['lbl' => '\(\pm\)',               'ins' => '\pm'],
            ['lbl' => '\(\leq\)',              'ins' => '\leq'],
            ['lbl' => '\(\geq\)',              'ins' => '\geq'],
            ['lbl' => '\(\neq\)',              'ins' => '\neq'],
            ['lbl' => '\(\approx\)',           'ins' => '\approx'],
            ['lbl' => '\(\infty\)',            'ins' => '\infty'],
            ['lbl' => '\(\Delta\)',            'ins' => '\Delta'],
            ['lbl' => '\(\sum\)',              'ins' => '\sum_{#?}^{#?}'],
            ['lbl' => '\(\int\)',              'ins' => '\int_{#?}^{#?}'],
            ['lbl' => '\(\lim\)',              'ins' => '\lim_{#?}'],
            ['lbl' => '\(\log\)',              'ins' => '\log_{#?}'],
            ['lbl' => '\(\ln\)',               'ins' => '\ln'],
            ['lbl' => '\(\alpha\)',            'ins' => '\alpha'],
            ['lbl' => '\(\beta\)',             'ins' => '\beta'],
            ['lbl' => '\(\in\)',               'ins' => '\in'],
            ['lbl' => '\(\subset\)',           'ins' => '\subset'],
            ['lbl' => '\(\cup\)',              'ins' => '\cup'],
            ['lbl' => '\(\cap\)',              'ins' => '\cap'],
            ['lbl' => '\(\Rightarrow\)',       'ins' => '\Rightarrow'],
            ['lbl' => '\(\{\ \}\)',            'ins' => '\left\{#?\right\}'],
            ['lbl' => '<i class="bi bi-backspace"></i>', 'cmd' => 'deleteBackward', 'wide' => true],
        ]],
        'geometry' => ['label' => 'Hình học', 'icon' => 'bi-triangle', 'keys' => [
            ['lbl' => '\(\angle\)',      'ins' => '\angle'],
            ['lbl' => '\(\triangle\)',   'ins' => '\triangle'],
            ['lbl' => '\(^\circ\)',      'ins' => '^{\circ}'],
            ['lbl' => '\(\parallel\)',   'ins' => '\parallel'],
            ['lbl' => '\(\perp\)',       'ins' => '\perp'],
            ['lbl' => '\(\sim\)',        'ins' => '\sim'],
            ['lbl' => '\(\cong\)',       'ins' => '\cong'],
            ['lbl' => '\(\overline{AB}\)', 'ins' => '\overline{#?}'],
            ['lbl' => '\(\vec{v}\)',     'ins' => '\vec{#?}'],
            ['lbl' => '\(\widehat{A}\)', 'ins' => '\widehat{#?}'],
            ['lbl' => '\(\sin\)',        'ins' => '\sin'],
            ['lbl' => '\(\cos\)',        'ins' => '\cos'],
            ['lbl' => '\(\tan\)',        'ins' => '\tan'],
            ['lbl' => '\(\cot\)',        'ins' => '\cot'],
            ['lbl' => '\(\pi\)',         'ins' => '\pi'],
            ['lbl' => '\(\frac{a}{b}\)', 'ins' => '\frac{#?}{#?}'],
            ['lbl' => '\(\sqrt{\ }\)',   'ins' => '\sqrt{#?}'],
            ['lbl' => '<i class="bi bi-backspace"></i>', 'cmd' => 'deleteBackward', 'wide' => true],
        ]],
    ];
@endphp

<div class="mk-panel mb-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="mk-badge"><i class="bi bi-calculator"></i></span>
            <span class="fw-semibold">Bàn phím Toán học</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="small text-secondary d-none d-sm-inline">
                <span class="mk-dot"></span> <span id="mkTarget">Sẵn sàng nhập công thức</span>
            </span>
            <button type="button" class="btn btn-sm btn-link text-secondary p-0" id="mkClose" title="Đóng">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    {{-- Khung nhap --}}
    <label class="form-label small text-secondary mb-1">Nhập công thức</label>
    <div class="mk-display mb-3">
        <math-field id="mf"></math-field>
    </div>

    {{-- Tabs --}}
    <div class="mk-tabs mb-2" role="tablist">
        @foreach ($tabs as $id => $tab)
            <button type="button" class="mk-tab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $id }}" role="tab">
                <i class="bi {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Ban phim --}}
    @foreach ($tabs as $id => $tab)
        <div class="mk-keys {{ $loop->first ? '' : 'd-none' }}" data-pane="{{ $id }}">
            @foreach ($tab['keys'] as $k)
                <button type="button"
                        class="mk-key {{ ! empty($k['wide']) ? 'mk-key-wide' : '' }}"
                        @isset($k['ins']) data-ins="{{ $k['ins'] }}" @endisset
                        @isset($k['cmd']) data-cmd="{{ $k['cmd'] }}" @endisset
                >{!! $k['lbl'] !!}</button>
            @endforeach
        </div>
    @endforeach

    {{-- Hanh dong --}}
    <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
        <button type="button" class="btn btn-primary px-4" id="insInline">
            <i class="bi bi-box-arrow-in-down"></i> Chèn công thức
        </button>
        <button type="button" class="btn btn-outline-primary" id="insBlock">Chèn riêng dòng</button>
        <button type="button" class="btn btn-outline-danger" id="mfClear"><i class="bi bi-trash"></i> Xoá</button>
    </div>
    <p class="text-center small text-secondary mt-2 mb-0">
        Bấm phím ở trên hoặc gõ trực tiếp (<code>1/2</code>→phân số, <code>sqrt</code>→căn, <code>^</code>→mũ),
        rồi bấm <strong>Chèn công thức</strong> vào ô đang soạn.
    </p>
</div>
