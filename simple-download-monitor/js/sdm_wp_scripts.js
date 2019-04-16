// Simple Download Monitor frontend scripts

function sdm_is_ie() {

    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE ");

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))
    {
	return true;
    }

    return false;
}

jQuery(document).ready(function ($) {

    // Populate all nested titles and links 
    $('li.sdm_cat').each(function () {

	var $this = $(this);
	this_slug = $this.attr('id');
	this_id = $this.children('.sdm_cat_title').attr('id');

	// Run ajax
	$.post(
		sdm_ajax_script.ajaxurl,
		{
		    action: 'sdm_pop_cats',
		    cat_slug: this_slug,
		    parent_id: this_id
		},
		function (response) {

		    // Loop array returned from ajax function
		    $.each(response.final_array, function (key, value) {

			// Populate each matched post title and permalink
			$this.children('.sdm_placeholder').append('<a href="' + value['permalink'] + '"><span class="sdm_post_title" style="cursor:pointer;">' + value['title'] + '</span></a>');
		    });

		    $this.children('span').append('<span style="margin-left:5px;" class="sdm_arrow">&#8616</span>');
		}
	);
    });

    // Hide results on page load
    $('li.sdm_cat').children('.sdm_placeholder').hide();

    // Slide toggle for each list item
    $('body').on('click', '.sdm_cat_title', function (e) {

	// If there is any html.. then we have more elements
	if ($(this).next().html() != '') {

	    $(this).next().slideToggle(); // toggle div titles
	}
    });

    // Download buttons with terms or captcha has this class applied to it
    $('.sdm_download_with_condition').on('click', function (e) {
	e.preventDefault();
	$(this).closest('form').trigger('submit');
    });

    // Check if terms checkbox is enabled.
    if ($('.sdm-termscond-checkbox').length) {

	$.each($('.sdm-termscond-checkbox'), function () {
	    if (!$(this).is(':checked')) {
		var cur = $(this).children(':checkbox');
		var btn = $(cur).closest('form').find('a.sdm_download,a.sdm_download_with_condition');
		$(btn).addClass('sdm_disabled_button');
	    }
	});

	$.each($('.sdm-download-form'), function () {
	    var form = $(this);
	    form.on('submit', function () {
		if ($('.agree_termscond', form).is(':checked')) {
		    $('.sdm-termscond-checkbox', form).removeClass('sdm_general_error_msg');
		    return true;
		} else {
		    $('.sdm-termscond-checkbox', form).addClass('sdm_general_error_msg');
		}
		return false;
	    });
	});

	$.each($('.agree_termscond'), function () {
	    var element = $(this);
	    var form = element.closest('form');
	    element.on('click', function () {
		if (element.is(':checked')) {
		    $('.sdm_download_with_condition', form).removeClass('sdm_disabled_button');
		    $('.sdm-termscond-checkbox', form).removeClass('sdm_general_error_msg');
		} else {
		    $('.sdm_download_with_condition', form).addClass('sdm_disabled_button');
		    $('.sdm-termscond-checkbox', form).addClass('sdm_general_error_msg');
		}
	    });
	});
    }
});