// Simple Download Monitor frontend scripts

jQuery(document).ready(function($) {
	
	// Populate all nested titles and links 
	$('li.sdm_cat').each(function() {
		
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
				$.each(response.final_array, function(key, value) {
					
					// Populate each matched post title and permalink
					$this.children('.sdm_placeholder').append('<a href="'+value['permalink']+'"><span class="sdm_post_title" style="cursor:pointer;">'+value['title']+'</span></a>');
				});
			}
		);
	});
	
	// Hide results on page load
	$('li.sdm_cat').children('.sdm_placeholder').hide();
	//$('li.sdm_cat').children('ul').hide();
	//$('.sdm_cat ul').children().hide();
	$('.sdm_cat ul').css('display', 'none');
	
	// Slide toggle for each list item
	$('body').on('click', '.sdm_cat_title', function(e) {
		
		// If there is any html.. then we have more elements
		if($(this).next().html() != '') {
			
			e.stopPropagation(); // prevetn climbing dom tree
			$(this).next().slideToggle(); // toggle div titles
			$(this).next().next().slideToggle(); // toggle next elements
			$(this).next().next().find('ul').slideToggle();  // toggle all child ul elements
		}
	});
		
	
	
	$('.pass_sumbit').click(function() {
		
		this_button_id = $(this).next().val();  // Get download cpt id from hidden input field
		password_attempt = $(this).prev().val();  // Get password text
		
		$.post(
			sdm_ajax_script.ajaxurl,
			{
			 action: 'sdm_check_pass',
			 pass_val: password_attempt,
			 button_id: this_button_id
			},
			function(response) {
				
				if(response) {  // ** If response was successful
				
					if(response.success === 'no') {  // If the password match failed
						
						alert(sdm_frontend_translations.incorrect_password);
						$('.pass_text').val('');  // Clear password field
					}
					
					if(response.url != '') {  // If the password match was a success
						
						window.location.href = response.url;  // Redirect to download url
						$('.pass_text').val('');  // Clear password field
					}
				} 
			}
		);
	});
	
});