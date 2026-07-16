/**
 * solver.js — giai bai (I2/I3). Chống lệ thuộc đáp án:
 * gợi ý mở → gợi ý thêm (max 2) → xem lời giải (chủ động).
 */
(function ($) {
    'use strict';
    var api = window.ht.api;
    var reqId = null;

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    function showResult(data) {
        reqId = data.request_id;
        $('#sv-result').removeClass('d-none');
        $('#sv-solution-card').addClass('d-none');
        $('#sv-solution').empty();

        if (data.parsed_text) {
            $('#sv-parsed').removeClass('d-none').html('<strong>Đề đọc được:</strong> ' + esc(data.parsed_text) +
                (data.needs_confirmation ? ' <em>(hãy kiểm tra lại, sửa nếu sai rồi bấm Gợi ý thêm)</em>' : ''));
        } else {
            $('#sv-parsed').addClass('d-none');
        }

        $('#sv-hint').html(data.hint ? esc(data.hint) : '<span class="text-secondary">Chưa có gợi ý — xác nhận đề trước nhé.</span>');
        $('#sv-more').prop('disabled', data.can_more_hint === false);
        renderMath('#sv-hint');
    }

    function renderMath(sel) {
        if (window.MathJax && window.MathJax.typesetPromise) window.MathJax.typesetPromise([$(sel)[0]]);
    }

    $('#sv-text').on('click', function () {
        var problem = $('#sv-problem').val().trim();
        if (!problem) { window.ht.showToast('Nhập đề trước nhé.', 'info'); return; }
        var $b = $(this).prop('disabled', true).text('Đang nghĩ...');
        api.post('/api/v1/solver/text', { problem: problem })
            .then(function (r) { showResult(r.data); })
            .always(function () { $b.prop('disabled', false).text('Nhờ gợi ý'); });
    });

    $('#sv-image').on('change', function () {
        if (!this.files || !this.files[0]) return;
        var fd = new FormData();
        fd.append('image', this.files[0]);
        window.ht.showToast('Đang đọc ảnh...', 'info');
        $.ajax({
            url: '/api/v1/solver/image', method: 'POST', data: fd,
            processData: false, contentType: false, dataType: 'json'
        }).done(function (r) { showResult(r.data); })
          .fail(function () { window.ht.showToast('Không đọc được ảnh. Thử ảnh rõ hơn hoặc gõ đề.', 'error'); });
    });

    $('#sv-more').on('click', function () {
        if (!reqId) return;
        api.post('/api/v1/solver/' + reqId + '/more-hint')
            .then(function (r) {
                $('#sv-hint').html(esc(r.data.hint)); renderMath('#sv-hint');
                $('#sv-more').prop('disabled', r.data.can_more_hint === false);
            });
    });

    $('#sv-full').on('click', function () {
        if (!reqId) return;
        api.post('/api/v1/solver/' + reqId + '/full-solution')
            .then(function (r) {
                $('#sv-solution-card').removeClass('d-none');
                $('#sv-solution').html(esc(r.data.solution).replace(/\n/g, '<br>'));
                renderMath('#sv-solution');
            });
    });

    $('#sv-similar').on('click', function () {
        if (!reqId) return;
        api.get('/api/v1/solver/' + reqId + '/similar')
            .then(function (r) {
                $('#sv-problem').val(r.data.problem);
                window.ht.showToast('Đã tạo bài tương tự vào ô đề.', 'success');
            });
    });
}(window.jQuery));
