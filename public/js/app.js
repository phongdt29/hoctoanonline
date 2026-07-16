/**
 * hoctoanonline — app.js
 *
 * jQuery thuan, KHONG build (CLAUDE.md: cam Vite/webpack/npm).
 * Nap sau jQuery + bootstrap.bundle.
 *
 * Cung cap: CSRF setup, helper api.get/post, showToast(), khoi tao Tooltip/Toast.
 */
(function (window, $) {
    'use strict';

    /* ---------------------------------------------------------
     * CSRF cho MOI request AJAX (SPEC §6)
     * ------------------------------------------------------- */
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    /* ---------------------------------------------------------
     * Toast — goc phai tren
     * ------------------------------------------------------- */
    var TOAST_ICON = {
        success: 'bi-check-circle-fill text-success',
        error: 'bi-exclamation-triangle-fill text-danger',
        info: 'bi-info-circle-fill text-primary',
        star: 'bi-star-fill'
    };

    function ensureToastContainer() {
        var $c = $('#ht-toasts');

        if (!$c.length) {
            $c = $('<div id="ht-toasts" class="toast-container position-fixed top-0 end-0 p-3"></div>')
                .appendTo('body');
        }

        return $c;
    }

    /**
     * @param {string} message Text tieng Viet hien cho nguoi dung
     * @param {'success'|'error'|'info'|'star'} [type=info]
     */
    function showToast(message, type) {
        type = type || 'info';

        var iconClass = TOAST_ICON[type] || TOAST_ICON.info;
        var style = type === 'star' ? ' style="color:var(--ht-star)"' : '';

        var $toast = $(
            '<div class="toast align-items-center border-0" role="alert" aria-live="polite" aria-atomic="true">' +
                '<div class="d-flex">' +
                    '<div class="toast-body d-flex align-items-center gap-2">' +
                        '<i class="bi ' + iconClass + '"' + style + '></i>' +
                        '<span class="ht-toast-msg"></span>' +
                    '</div>' +
                    '<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Đóng"></button>' +
                '</div>' +
            '</div>'
        );

        // .text() chu khong phai .html() — chan XSS tu noi dung server tra ve.
        $toast.find('.ht-toast-msg').text(message);
        $toast.appendTo(ensureToastContainer());

        var toast = new window.bootstrap.Toast($toast[0], { delay: 4000 });

        $toast.on('hidden.bs.toast', function () {
            $toast.remove();
        });

        toast.show();

        return toast;
    }

    /* ---------------------------------------------------------
     * Helper API — moi endpoint tra { data, message } (SPEC §0)
     * ------------------------------------------------------- */

    /** Map loi 422 cua Laravel vao .is-invalid + .invalid-feedback (UI spec §3). */
    function applyValidationErrors($form, errors) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback.ht-dynamic').remove();

        $.each(errors, function (field, messages) {
            var $input = $form.find('[name="' + field + '"]');

            if (!$input.length) {
                return;
            }

            $input.addClass('is-invalid');

            if (!$input.next('.invalid-feedback').length) {
                $('<div class="invalid-feedback ht-dynamic"></div>')
                    .text(messages[0])
                    .insertAfter($input);
            } else {
                $input.next('.invalid-feedback').text(messages[0]);
            }
        });
    }

    function request(method, url, data) {
        return $.ajax({
            url: url,
            method: method,
            data: data ? JSON.stringify(data) : undefined,
            contentType: 'application/json',
            dataType: 'json'
        }).fail(function (xhr) {
            if (xhr.status === 422) {
                return; // caller tu xu ly qua applyValidationErrors
            }

            if (xhr.status === 429) {
                showToast(xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Bạn thao tác hơi nhanh. Chờ một chút rồi thử lại nhé.', 'error');
                return;
            }

            showToast(xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'Có lỗi xảy ra. Thử lại sau nhé.', 'error');
        });
    }

    var api = {
        get: function (url) {
            return request('GET', url);
        },
        post: function (url, data) {
            return request('POST', url, data);
        },
        put: function (url, data) {
            return request('PUT', url, data);
        },
        applyValidationErrors: applyValidationErrors
    };

    /* ---------------------------------------------------------
     * Khoi tao
     * ------------------------------------------------------- */
    $(function () {
        $('[data-bs-toggle="tooltip"]').each(function () {
            new window.bootstrap.Tooltip(this);
        });

        $('.toast').each(function () {
            new window.bootstrap.Toast(this).show();
        });
    });

    window.ht = { api: api, showToast: showToast };
}(window, window.jQuery));
