(function ($) {
    'use strict';

    function postJSON(endpoint, data) {
        return wp.apiRequest({
            path: endpoint,
            method: 'POST',
            data: data,
        });
    }

    $(function () {
        var keywordForm = $('#tmw-sa100-keyword-form');
        var contentForm = $('#tmw-sa100-content-form');
        var testButton = $('.tmw-sa100-test-button');

        if (keywordForm.length) {
            keywordForm.on('submit', function (event) {
                event.preventDefault();

                var keywords = keywordForm.find('textarea[name="keywords"]').val().split('\n').filter(Boolean);
                var locale = keywordForm.find('select[name="locale"]').val();
                var output = keywordForm.find('.tmw-sa100-output');

                output.text(keywordForm.data('loading'));

                postJSON('tmw-sa100/v1/keyword-plan', {
                    keywords: keywords,
                    locale: locale,
                })
                    .done(function (response) {
                        output.text(JSON.stringify(response, null, 2));
                    })
                    .fail(function (error) {
                        output.text(error.responseJSON && error.responseJSON.message ? error.responseJSON.message : error.statusText);
                    });
            });
        }

        if (contentForm.length) {
            contentForm.on('submit', function (event) {
                event.preventDefault();

                var topic = contentForm.find('input[name="topic"]').val();
                var outline = contentForm.find('textarea[name="outline"]').val().split('\n').filter(Boolean);
                var output = contentForm.find('.tmw-sa100-output');

                output.text(contentForm.data('loading'));

                postJSON('tmw-sa100/v1/content-draft', {
                    topic: topic,
                    outline: outline,
                })
                    .done(function (response) {
                        output.text(response);
                    })
                    .fail(function (error) {
                        output.text(error.responseJSON && error.responseJSON.message ? error.responseJSON.message : error.statusText);
                    });
            });
        }

        if (testButton.length && typeof tmwSA100 !== 'undefined') {
            var testWrapper = $('.tmw-sa100-test-connections');
            var spinner = testWrapper.find('.tmw-sa100-test-spinner');
            var results = testWrapper.find('.tmw-sa100-test-results');
            var noticeArea = $('.tmw-sa100-test-notices');

            function renderResults(payload) {
                if (! payload) {
                    return;
                }

                var fragments = [];

                if (payload.serper_status === 'ok') {
                    fragments.push('<span class="tmw-sa100-connection-status tmw-sa100-connection-success">✅ ' + tmwSA100.i18nSerperOk + '</span>');
                } else {
                    fragments.push('<span class="tmw-sa100-connection-status tmw-sa100-connection-fail">❌ ' + tmwSA100.i18nSerperFail + '</span>');
                }

                if (payload.openai_status === 'ok') {
                    fragments.push('<span class="tmw-sa100-connection-status tmw-sa100-connection-success">✅ ' + tmwSA100.i18nOpenAIOk + '</span>');
                } else {
                    fragments.push('<span class="tmw-sa100-connection-status tmw-sa100-connection-fail">❌ ' + tmwSA100.i18nOpenAIFail + '</span>');
                }

                results.html(fragments.join(' '));

                var success = payload.serper_status === 'ok' && payload.openai_status === 'ok';
                showNotice(success);
            }

            function showNotice(success) {
                var message = success ? tmwSA100.i18nNoticeSuccess : tmwSA100.i18nNoticeFail;
                var noticeClass = success ? 'notice notice-success' : 'notice notice-error';

                noticeArea.html('<div class="' + noticeClass + '"><p>' + message + '</p></div>');
            }

            function handleError(message) {
                results.text(message);
                showNotice(false);
            }

            testButton.on('click', function (event) {
                event.preventDefault();

                spinner.addClass('is-active');
                testButton.prop('disabled', true);
                results.empty();
                noticeArea.empty();

                $.ajax({
                    url: tmwSA100.ajaxUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'tmw_sa100_test_connections',
                        nonce: tmwSA100.testNonce,
                    },
                })
                    .done(function (response) {
                        tmwSA100.cachedTest = response;
                        renderResults(response);
                    })
                    .fail(function (jqXHR) {
                        var message = tmwSA100.i18nGenericError;

                        if (jqXHR.responseJSON) {
                            if (jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                                message = jqXHR.responseJSON.data.message;
                            } else if (jqXHR.responseJSON.message) {
                                message = jqXHR.responseJSON.message;
                            }
                        }

                        handleError(message);
                    })
                    .always(function () {
                        spinner.removeClass('is-active');
                        testButton.prop('disabled', false);
                    });
            });

            if (tmwSA100.cachedTest) {
                renderResults(tmwSA100.cachedTest);
            }
        }

    });
})(jQuery);
