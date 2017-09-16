jQuery(document).ready(function ($) {
    var selectFileFrame;
    var selectThumbFramel
    // Run media uploader for file upload
    $('#upload_image_button').click(function (e) {
        e.preventDefault();
        selectFileFrame = wp.media({
            title: sdm_translations.select_file,
            button: {
                text: sdm_translations.insert,
            },
            multiple: false
        });
        selectFileFrame.open();
        selectFileFrame.on('select', function () {
            var attachment = selectFileFrame.state().get('selection').first().toJSON();

            $('#sdm_upload').val(attachment.url);
        });
        return false;
    });

    // Run media uploader for thumbnail upload
    $('#upload_thumbnail_button').click(function (e) {
        e.preventDefault();
        selectFileFrame = wp.media({
            title: sdm_translations.select_thumbnail,
            button: {
                text: sdm_translations.insert,
            },
            multiple: false,
            library: {type: 'image'},
        });
        selectFileFrame.open();
        selectFileFrame.on('select', function () {
            var attachment = selectFileFrame.state().get('selection').first().toJSON();

            $('#sdm_thumbnail_image').remove();
            $('#sdm_admin_thumb_preview').html('<img id="sdm_thumbnail_image" src="' + attachment.url + '" style="max-width:200px;" />');

            $('#sdm_upload_thumbnail').val(attachment.url);
        });
        return false;
    });

    // Remove thumbnail image from CPT
    $('#remove_thumbnail_button').click(function () {
        $.post(
                sdm_admin_ajax_url.sdm_admin_ajax_url,
                {
                    action: 'sdm_remove_thumbnail_image',
                    post_id_del: sdm_del_thumb_postid
                },
                function (response) {
                    if (response) {  // ** If response was successful
                        $('#sdm_thumbnail_image').remove();
                        $('#sdm_upload_thumbnail').val('');
                        alert(sdm_translations.image_removed);
                    } else {  // ** Else response was unsuccessful
                        alert(sdm_translations.ajax_error);
                    }
                }
        );
    });

});