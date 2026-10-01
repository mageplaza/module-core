define(['jquery', 'mage/translate', 'mage/apply/main'], function ($, $t, mage) {
    'use strict';

    return function (config, element) {
        var root = $(element),
            button = root.find('[data-role="check-now"]'),
            error = root.find('[data-role="refresh-error"]'),
            notice = root.find('[data-role="refresh-notice"]'),
            banner = root.find('[data-role="promotion-banner"]'),
            bannerId = banner.attr('data-banner-id'),
            dismissed = null,
            cooldownUntil = 0,
            cooldownTimer = null,
            hasModules = !button.prop('disabled');

        function updateCooldown() {
            var seconds = Math.max(0, Math.ceil((cooldownUntil - Date.now()) / 1000));

            if (!document.documentElement.contains(element)) {
                window.clearInterval(cooldownTimer);
                return;
            }
            button.attr('aria-busy', 'false').prop('disabled', !hasModules || seconds > 0)
                .css('pointer-events', !hasModules || seconds > 0 ? 'none' : '')
                .find('span').text($t('Check now'));
            if (!seconds) {
                window.clearInterval(cooldownTimer);
                cooldownTimer = null;
            }
        }

        function startCooldown(seconds) {
            window.clearInterval(cooldownTimer);
            cooldownUntil = Date.now() + Math.max(0, Number(seconds) || 0) * 1000;
            updateCooldown();
            if (cooldownUntil > Date.now()) {
                cooldownTimer = window.setInterval(updateCooldown, 250);
            }
        }

        startCooldown(button.attr('data-retry-after'));
        function checkAfterConnect() {
            var pending = 0;
            try {
                pending = Number(window.sessionStorage.getItem('mp_core_connect_pending')) || 0;
            } catch (e) {
                return;
            }
            if (!pending || document.visibilityState === 'hidden') {
                return;
            }
            window.sessionStorage.removeItem('mp_core_connect_pending');
            if (Date.now() - pending > 1800000) {
                return;
            }
            notice.removeClass('message-success').addClass('message-notice')
                .text($t('Updating license information…')).prop('hidden', false);
            window.setTimeout(function () {
                $('[data-role="check-now"]').first().trigger('click');
            }, Math.max(0, cooldownUntil - Date.now()) + 500);
        }

        root.on('click', '[data-role="connect-licenses"]', function () {
            try {
                window.sessionStorage.setItem('mp_core_connect_pending', String(Date.now()));
            } catch (e) {
                return;
            }
        });
        $(window).off('focus.mpConnect').on('focus.mpConnect', checkAfterConnect);
        $(document).off('visibilitychange.mpConnect').on('visibilitychange.mpConnect', checkAfterConnect);
        checkAfterConnect();
        root.on('click', '[data-role="check-now-control"]', function (event) {
            if (hasModules && button.prop('disabled')) {
                event.preventDefault();
                notice.removeClass('message-success').addClass('message-notice')
                    .text($t('Please wait a moment before checking again.')).prop('hidden', false);
            }
        });

        try {
            dismissed = window.localStorage.getItem('mp_core_banner_dismissed');
        } catch (e) {
            // Storage can be unavailable in private or restricted browser contexts.
        }
        if (bannerId && dismissed !== bannerId) {
            banner.prop('hidden', false);
        }
        root.on('click', '[data-role="dismiss-banner"]', function () {
            banner.prop('hidden', true);
            try {
                window.localStorage.setItem('mp_core_banner_dismissed', bannerId);
            } catch (e) {
                // Dismiss for this page even when persistence is unavailable.
            }
        });
        // Click tracking is best effort; links keep their native navigation behavior.
        root.on('click', 'a[data-core-event]', function () {
            if (root.attr('data-track-enabled') !== '1' || !window.FORM_KEY || !window.crypto ||
                !window.crypto.getRandomValues) {
                return;
            }
            var bytes = new Uint8Array(16);
            window.crypto.getRandomValues(bytes);
            bytes[6] = (bytes[6] & 15) | 64;
            bytes[8] = (bytes[8] & 63) | 128;
            var hex = Array.prototype.map.call(bytes, function (byte) {
                return ('0' + byte.toString(16)).slice(-2);
            }).join('');
            var id = hex.slice(0, 8) + '-' + hex.slice(8, 12) + '-' + hex.slice(12, 16) + '-'
                + hex.slice(16, 20) + '-' + hex.slice(20);
            var data = new FormData();
            data.append('form_key', window.FORM_KEY);
            data.append('event_id', id);
            data.append('event_name', $(this).attr('data-core-event'));
            if ($(this).attr('data-package')) {
                data.append('package', $(this).attr('data-package'));
            }
            if ($(this).attr('data-banner-id')) {
                data.append('banner_id', $(this).attr('data-banner-id'));
            }
            var url = root.attr('data-track-url');
            if (!window.navigator.sendBeacon || !window.navigator.sendBeacon(url, data)) {
                $.ajax({url: url, type: 'POST', data: data, processData: false,
                    contentType: false, timeout: 2000});
            }
        });
        button.on('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (button.prop('disabled')) {
                return;
            }
            button.attr('aria-busy', 'true').prop('disabled', true).find('span').text($t('Checking…'));
            error.prop('hidden', true).text('');
            notice.prop('hidden', true).text('');
            button.css('pointer-events', 'none');
            $.ajax({
                url: button.attr('data-refresh-url'),
                type: 'POST',
                dataType: 'json',
                data: {form_key: window.FORM_KEY},
                timeout: 15000,
                showLoader: false,
                global: false,
                // Keep expired-session responses on this page instead of the backend redirect handler.
                complete: $.noop
            }).done(function (response) {
                if (response && response.success && typeof response.html === 'string' && response.html.trim()) {
                    var updatedRoot = $(response.html);
                    root.replaceWith(updatedRoot);
                    updatedRoot.find('[data-role="refresh-notice"]')
                        .removeClass('message-notice').addClass('message-success')
                        .text($t('Updated successfully.')).prop('hidden', false);
                    mage.apply();
                    return;
                }
                if (response && (response.reason === 'cooldown' || response.reason === 'in_progress')) {
                    startCooldown(response.retry_after);
                    notice.removeClass('message-success').addClass('message-notice').text(response.reason === 'in_progress'
                        ? $t('An update check is already in progress. Please try again shortly.')
                        : $t('Please wait a moment before checking again.')).prop('hidden', false);
                    return;
                }
                // A failed check keeps the existing data and only disables retry.
                startCooldown((response && response.retry_after) || 60);
            }).fail(function () {
                startCooldown(60);
            }).always(function () {
                updateCooldown();
            });
        });
    };
});
