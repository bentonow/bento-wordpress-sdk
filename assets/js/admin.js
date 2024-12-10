(function($, settings) {
    'use strict';

    $(function() {
        const $button = $('#bento-test-email');

        if (!$button.length) return;

        $button.on('click', function(e) {
            e.preventDefault();

            const $result = $('#bento-test-email-result');

            // Disable button and show loading state
            $button.prop('disabled', true).text(settings.sending || 'Sending...');
            $result.hide();

            $.ajax({
                url: settings.ajax_url,
                type: 'POST',
                data: {
                    action: 'bento_send_test_email',
                    nonce: settings.nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data.message) {
                        $result
                            .removeClass('notice-error')
                            .addClass('notice-success')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                    } else {
                        const errorMsg = response.data?.message || 'Unknown error occurred';
                        $result
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + errorMsg + '</p>')
                            .show();
                    }
                },
                error: function(xhr, status, error) {
                    $result
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p>Network error: ' + error + '</p>')
                        .show();

                    console.error('Ajax error:', {xhr, status, error});
                },
                complete: function() {
                    // Re-enable button and restore text
                    $button.prop('disabled', false).text(settings.send || 'Send Test Email');
                }
            });
        });
    });
})(jQuery, window.bentoAdminSettings || {});