jQuery(document).ready(function ($) {

    $('.qrm-request-all').on('click', function () {

        let btn = $(this);
        btn.prop('disabled', true).text('Requesting...');

        $.post(qrm_ajax.ajax_url, {
            action: 'qrm_request_qr',
            nonce: qrm_ajax.nonce
        }, function (res) {

            if (res.success) {
                btn.text('Requested');
                location.reload();
            } else {
                btn.prop('disabled', false).text('Request QR Codes');
                alert(res.data.message);
            }
        });

    });

});
