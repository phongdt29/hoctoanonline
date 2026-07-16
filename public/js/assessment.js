/**
 * assessment.js — lam bai kiem tra dau vao (ticket C3/C6).
 *
 * - 1 cau/man hinh.
 * - Dem time_spent_seconds TUNG CAU (input bat buoc cua phan loai tang 2).
 * - Autosave 30s (PUT .../save) -> refresh khong mat bai (DoD C3).
 * - MathJax render de.
 */
(function ($) {
    'use strict';

    var api = window.ht.api;
    var state = {
        assessmentId: null,
        questions: [],
        index: 0,
        timeSpent: {},      // questionId -> seconds
        answers: {},        // questionId -> value
        tickAt: null
    };

    var $stage = $('#asm-stage');

    function currentQuestion() {
        return state.questions[state.index];
    }

    // Cong don thoi gian cho cau dang xem.
    function accrueTime() {
        var q = currentQuestion();
        if (!q || !state.tickAt) { return; }

        var delta = Math.round((Date.now() - state.tickAt) / 1000);
        state.timeSpent[q.id] = (state.timeSpent[q.id] || 0) + delta;
        state.tickAt = Date.now();
    }

    function buildPayload() {
        var answers = {};
        state.questions.forEach(function (q) {
            answers[q.id] = {
                answer: state.answers[q.id] != null ? state.answers[q.id] : null,
                time_spent_seconds: state.timeSpent[q.id] || 0
            };
        });
        return { answers: answers };
    }

    function save() {
        accrueTime();
        return api.put('/api/v1/assessments/' + state.assessmentId + '/save', buildPayload());
    }

    function renderQuestion() {
        var q = currentQuestion();
        if (!q) { return; }

        state.tickAt = Date.now();

        var html = '<div class="mb-2 text-secondary small">Câu ' + (state.index + 1) +
            '/' + state.questions.length + ' · <span class="text-uppercase">' + q.topic + '</span></div>' +
            '<div class="fs-5 mb-4">' + $('<div>').text(q.content).html() + '</div>';

        if (q.type === 'multiple_choice' && q.options && q.options.length) {
            html += '<div class="list-group">';
            q.options.forEach(function (opt, i) {
                var letter = String.fromCharCode(65 + i);
                var checked = state.answers[q.id] === letter ? ' active' : '';
                html += '<label class="list-group-item list-group-item-action d-flex gap-3 ht-tap' + checked + '">' +
                    '<input class="form-check-input m-0" type="radio" name="opt" value="' + letter + '"' +
                    (state.answers[q.id] === letter ? ' checked' : '') + '>' +
                    '<span class="num fw-semibold">' + letter + '.</span>' +
                    '<span>' + $('<div>').text(opt).html() + '</span></label>';
            });
            html += '</div>';
        } else {
            var val = state.answers[q.id] || '';
            html += '<textarea id="asm-essay" class="form-control" rows="4" ' +
                'placeholder="Nhập lời giải của bạn...">' + $('<div>').text(val).html() + '</textarea>';
        }

        html += '</div>';
        $stage.html(html);

        if (window.MathJax && window.MathJax.typesetPromise) {
            window.MathJax.typesetPromise([$stage[0]]);
        }

        $('#asm-prev').prop('disabled', state.index === 0);
        var last = state.index === state.questions.length - 1;
        $('#asm-next').toggleClass('d-none', last);
        $('#asm-submit').toggleClass('d-none', !last);
    }

    function go(delta) {
        accrueTime();
        var next = state.index + delta;
        if (next < 0 || next >= state.questions.length) { return; }
        state.index = next;
        renderQuestion();
    }

    // --- Su kien ---
    $stage.on('change', 'input[name="opt"]', function () {
        state.answers[currentQuestion().id] = $(this).val();
        $(this).closest('.list-group').find('.list-group-item').removeClass('active');
        $(this).closest('.list-group-item').addClass('active');
    });

    $stage.on('input', '#asm-essay', function () {
        state.answers[currentQuestion().id] = $(this).val();
    });

    $('#asm-next').on('click', function () { go(1); });
    $('#asm-prev').on('click', function () { go(-1); });

    $('#asm-submit').on('click', function () {
        var $btn = $(this).prop('disabled', true).text('Đang nộp...');
        save().then(function () {
            return api.post('/api/v1/assessments/' + state.assessmentId + '/submit', buildPayload());
        }).then(function () {
            window.location.href = window.htAssessment.resultUrlBase + '/' + state.assessmentId + '/result';
        }).catch(function () {
            $btn.prop('disabled', false).text('Nộp bài');
        });
    });

    // Autosave 30s + luu khi roi trang.
    setInterval(function () {
        if (state.assessmentId) { save(); }
    }, 30000);

    $(window).on('beforeunload', function () {
        if (state.assessmentId && navigator.sendBeacon) {
            accrueTime();
            var blob = new Blob([JSON.stringify(buildPayload())], { type: 'application/json' });
            // sendBeacon khong gui duoc header CSRF/PUT -> chi best-effort; autosave 30s la chinh.
        }
    });

    // --- Khoi dong: goi start ---
    $('#asm-start').on('click', function () {
        var $btn = $(this).prop('disabled', true).text('Đang tạo đề...');

        api.post('/api/v1/assessments/start').then(function (res) {
            state.assessmentId = res.data.id;
            state.questions = res.data.questions;

            // Khoi phuc bai da lam do (refresh khong mat bai).
            state.questions.forEach(function (q) {
                if (q.student_answer != null) { state.answers[q.id] = q.student_answer; }
                if (q.time_spent_seconds) { state.timeSpent[q.id] = q.time_spent_seconds; }
            });

            $('#asm-intro').addClass('d-none');
            $('#asm-play').removeClass('d-none');
            renderQuestion();
        }).catch(function (xhr) {
            $btn.prop('disabled', false).text('Bắt đầu làm bài');
            window.ht.showToast(
                'Chưa tạo được đề. Có thể hệ thống A.I chưa được cấu hình. Thử lại sau nhé.',
                'error'
            );
        });
    });
}(window.jQuery));
