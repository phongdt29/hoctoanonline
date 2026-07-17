/**
 * quiz.js — làm quiz cuối buổi (L2). Timer HIỂN THỊ từ expires_at của server;
 * server mới là nơi CHỐT hết giờ (client sửa giờ không ăn thua).
 */
(function ($) {
    'use strict';
    var api = window.ht.api;
    var state = { attemptId: null, submitUrl: null, questions: [], index: 0, answers: {}, expiresAt: null, skewMs: 0, timer: null, submitted: false };

    var $stage = $('#qz-stage');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function q() { return state.questions[state.index]; }

    function renderTimer() {
        // Gio "hien tai" theo server = client now + skew.
        var remain = Math.max(0, Math.round((state.expiresAt - (Date.now() + state.skewMs)) / 1000));
        var m = Math.floor(remain / 60), s = remain % 60;
        var $t = $('#qz-timer').text((m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s);
        // Con < 2 phut -> canh bao do.
        $t.parent().toggleClass('text-bg-danger', remain <= 120).toggleClass('text-bg-light', remain > 120);
        if (remain <= 0 && !state.submitted) { submit(true); }
    }

    function renderQuestion() {
        var cur = q();
        if (!cur) { return; }
        $('#qz-pos').text(state.index + 1);

        var html = '<div class="mb-2 text-secondary small text-uppercase">' + esc(cur.topic) + '</div>' +
            '<div class="fs-5 mb-4">' + esc(cur.content) + '</div><div class="list-group">';
        (cur.options || []).forEach(function (opt, i) {
            var letter = String.fromCharCode(65 + i);
            var on = state.answers[cur.index] === letter;
            html += '<label class="list-group-item list-group-item-action d-flex gap-3 ht-tap' + (on ? ' active' : '') + '">' +
                '<input class="form-check-input m-0" type="radio" name="opt" value="' + letter + '"' + (on ? ' checked' : '') + '>' +
                '<span class="num fw-semibold">' + letter + '.</span><span>' + esc(opt) + '</span></label>';
        });
        html += '</div>';
        $stage.html(html);
        if (window.MathJax && window.MathJax.typesetPromise) { window.MathJax.typesetPromise([$stage[0]]); }

        $('#qz-prev').prop('disabled', state.index === 0);
        var last = state.index === state.questions.length - 1;
        $('#qz-next').toggleClass('d-none', last);
        $('#qz-submit').toggleClass('d-none', !last);
    }

    function submit(auto) {
        if (state.submitted) { return; }
        state.submitted = true;
        if (state.timer) { clearInterval(state.timer); }
        $('#qz-submit, #qz-next, #qz-prev').prop('disabled', true);

        api.post(state.submitUrl, { attempt_id: state.attemptId, answers: state.answers })
            .then(function (r) { showResult(r.data, auto); })
            .catch(function () { state.submitted = false; $('#qz-submit').prop('disabled', false); });
    }

    function showResult(data, auto) {
        $('#qz-play').addClass('d-none');
        $('#qz-result').removeClass('d-none');
        var score = Number(data.score) || 0;
        $('#qz-score').text(score.toFixed(1).replace('.0', ''));

        var good = score >= 8;
        $('#qz-result-ico').html('<span class="ht-ico ' + (good ? 'ht-ico-grad' : '') + ' mx-auto" style="width:56px;height:56px;font-size:1.6rem">' +
            '<i class="bi ' + (good ? 'bi-trophy-fill' : 'bi-emoji-smile') + '"></i></span>');

        var msg = auto ? 'Đã hết giờ — bài được chấm theo phần bạn kịp làm. ' : '';
        msg += good ? 'Tuyệt vời! Bạn đã mở khóa buổi tiếp theo. 🎉'
                    : (data.suggestion === 'on_lai' ? 'Nên ôn lại bài này một chút trước khi đi tiếp nhé.' : 'Làm tốt lắm, tiếp tục cố gắng nhé!');
        $('#qz-msg').text(msg);
        if (good) { window.ht.showToast('+' + Math.round(score * 2) + ' điểm ⭐', 'star'); }
    }

    // Điều hướng
    $stage.on('change', 'input[name="opt"]', function () {
        state.answers[q().index] = $(this).val();
        $(this).closest('.list-group').find('.list-group-item').removeClass('active');
        $(this).closest('.list-group-item').addClass('active');
    });
    $('#qz-next').on('click', function () { if (state.index < state.questions.length - 1) { state.index++; renderQuestion(); } });
    $('#qz-prev').on('click', function () { if (state.index > 0) { state.index--; renderQuestion(); } });
    $('#qz-submit').on('click', function () { submit(false); });

    // Bắt đầu
    $('#qz-start').on('click', function () {
        var $btn = $(this).prop('disabled', true).text('Đang tạo đề...');
        api.post($(this).data('start')).then(function (r) {
            state.attemptId = r.data.attempt_id;
            state.submitUrl = $btn.data('submit');
            state.questions = r.data.questions || [];
            state.expiresAt = new Date(r.data.expires_at).getTime();
            state.skewMs = new Date(r.data.server_now).getTime() - Date.now();

            $('#qz-total').text(state.questions.length);
            $('#qz-intro').addClass('d-none');
            $('#qz-play').removeClass('d-none');
            renderQuestion();
            renderTimer();
            state.timer = setInterval(renderTimer, 1000);
        }).catch(function () {
            $btn.prop('disabled', false).text('Bắt đầu làm quiz');
            window.ht.showToast('Chưa tạo được quiz. Có thể hệ thống A.I chưa cấu hình. Thử lại sau nhé.', 'error');
        });
    });
}(window.jQuery));
