/**
 * tutor-chat.js — AI Tutor (I1). Polling 3s, dừng khi tab ẩn.
 */
(function ($) {
    'use strict';
    var api = window.ht.api;
    var convId = null, lastId = 0, polling = null;

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    function bubble(sender, content) {
        var side = sender === 'student' ? 'align-items-end' : 'align-items-start';
        var cls = sender === 'student' ? 'ht-bubble-student' : 'ht-bubble-ai';
        return $('<div class="d-flex flex-column gap-1 ' + side + '">' +
            '<div class="ht-bubble ' + cls + '">' + esc(content).replace(/\n/g, '<br>') + '</div></div>');
    }

    function append(sender, content) {
        var $m = $('#tt-messages');
        $m.append(bubble(sender, content));
        $m.scrollTop($m[0].scrollHeight);
        if (window.MathJax && window.MathJax.typesetPromise) window.MathJax.typesetPromise([$m[0]]);
    }

    function ensureConversation() {
        if (convId) return $.Deferred().resolve(convId).promise();
        return api.post('/api/v1/tutor/conversations').then(function (r) {
            convId = r.data.id; startPolling(); return convId;
        });
    }

    // Load lich su cuoc tro chuyen gan nhat khi mo trang.
    function loadHistory() {
        return api.get('/api/v1/tutor/current').then(function (r) {
            convId = r.data.conversation_id;
            var msgs = r.data.messages || [];

            if (msgs.length) {
                $('#tt-messages').empty();   // bo loi chao cung, hien lich su that
                msgs.forEach(function (m) {
                    append(m.sender, m.content);
                    if (m.id > lastId) lastId = m.id;
                });
            }
            startPolling();
        });
    }

    function poll() {
        if (!convId || document.hidden) return;
        api.get('/api/v1/tutor/conversations/' + convId + '/messages?after_id=' + lastId)
            .then(function (r) {
                (r.data || []).forEach(function (m) {
                    if (m.id > lastId) { lastId = m.id; if (m.sender === 'ai') append('ai', m.content); }
                });
            });
    }

    function startPolling() {
        if (polling) clearInterval(polling);
        polling = setInterval(poll, 3000);
    }

    // Dừng polling khi tab ẩn (I1 DoD).
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) { if (polling) clearInterval(polling); polling = null; }
        else if (convId) startPolling();
    });

    $('#tt-form').on('submit', function (e) {
        e.preventDefault();
        var text = $('#tt-input').val().trim();
        if (!text) return;
        $('#tt-input').val('');
        append('student', text);

        ensureConversation().then(function (id) {
            api.post('/api/v1/tutor/conversations/' + id + '/messages', { content: text })
                .then(function (r) {
                    if (r.data.id > lastId) { lastId = r.data.id; append('ai', r.data.content); }
                });
        });
    });

    // Mo trang -> load lich su chat gan nhat.
    $(loadHistory);
}(window.jQuery));
