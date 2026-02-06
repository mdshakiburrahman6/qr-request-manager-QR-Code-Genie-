jQuery(document).ready(function ($) {

    function openMedia(button) {

        let targetInput = $('#' + button.data('target'));
        let previewBox  = $('#' + button.data('preview'));

        let frame = wp.media({
            title: 'Select QR Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        frame.on('select', function () {
            let attachment = frame.state().get('selection').first().toJSON();

            targetInput.val(attachment.id);

            previewBox.html(
                '<img src="' + attachment.url + '" style="max-width:200px;" />'
            );

            button.text('Replace Image');

            button
                .siblings('.qrm-remove')
                .show();
        });

        frame.open();
    }

    $('.qrm-upload').on('click', function (e) {
        e.preventDefault();
        openMedia($(this));
    });

    $('.qrm-remove').on('click', function (e) {
        e.preventDefault();

        let btn = $(this);
        let targetInput = $('#' + btn.data('target'));
        let previewBox  = $('#' + btn.data('preview'));

        targetInput.val('');
        previewBox.html('');

        btn.hide();
        btn.siblings('.qrm-upload').text('Upload Image');
    });

});
