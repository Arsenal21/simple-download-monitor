<?php

//TODO write a function for doing grid display then the conditional function calls this grid display function

function sdm_generate_fancy2_popular_downloads_display_output( $get_posts, $args ) {
	wp_enqueue_style( 'sdm_generate_fancy2_popular_downloads_display_styles', WP_SIMPLE_DL_MONITOR_URL . '/includes/templates/fancy2/sdm-fancy-2-styles.css' , array(), WP_SIMPLE_DL_MONITOR_VERSION );
    $output	 = "";

    $count = 1;
    //$output .= '<ul class="sdm_fancy2_category_items">';
    foreach ( $get_posts as $item ) {
	$opts		 = $args;
	$opts[ 'id' ]	 = $item->ID;
	$output		 .= sdm_generate_fancy2_display_output( $opts );

	if ( $count % 3 == 0 ) {//Clear after every 3 items in the grid
	    $output .= '<div class="sdm_clear_float"></div>';
	}
	$count ++;
    }
    //$output .= '</ul>';
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

function sdm_generate_fancy2_latest_downloads_display_output( $get_posts, $args ) {
	wp_enqueue_style( 'sdm_generate_fancy2_latest_downloads_display_styles', WP_SIMPLE_DL_MONITOR_URL . '/includes/templates/fancy2/sdm-fancy-2-styles.css' , array(), WP_SIMPLE_DL_MONITOR_VERSION );
    $output	 = "";

    $count = 1;
    //$output .= '<ul class="sdm_fancy2_category_items">';
    foreach ( $get_posts as $item ) {
	$output .= sdm_generate_fancy2_display_output(
	array_merge( $args, array( 'id' => $item->ID ) )
	);

	if ( $count % 3 == 0 ) {//Clear after every 3 items in the grid
	    $output .= '<div class="sdm_clear_float"></div>';
	}
	$count ++;
    }
    //$output .= '</ul>';
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

function sdm_generate_fancy2_category_display_output( $get_posts, $args ) {
	wp_enqueue_style( 'sdm_generate_fancy2_category_display_styles', WP_SIMPLE_DL_MONITOR_URL . '/includes/templates/fancy2/sdm-fancy-2-styles.css' , array(), WP_SIMPLE_DL_MONITOR_VERSION );
    $output	 = "";

    $count = 1;
    //$output .= '<ul class="sdm_fancy2_category_items">';
    foreach ( $get_posts as $item ) {
        
        // Create a new array to prevent affecting the next item by the modified value of the current item in the loop. 
        $args_fresh = array_merge([], $args); 
        
        /**
         * Get the download button text.
         * Prioritize category shortcode param over custom button text from edit page.
         */
        if (empty($args_fresh['button_text'])) {
            $args_fresh['button_text'] = sdm_get_dl_button_text($item->ID);
        }
        
        $output .= sdm_generate_fancy2_display_output(
        array_merge( $args_fresh, array( 'id' => $item->ID ) )
        );

        if ( $count % 3 == 0 ) {//Clear after every 3 items in the grid
            $output .= '<div class="sdm_clear_float"></div>';
        }
        $count ++;
    }
    //$output .= '</ul>';
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

/*
 * Generates the output of a single item using fancy2 sytle
 * $args array can have the following parameters
 * id, fancy, button_text, new_window
 */

function sdm_generate_fancy2_display_output( $args ) {

    $shortcode_atts = sanitize_sdm_create_download_shortcode_atts(
    shortcode_atts( array(
	'id'		 => '',
	'button_text'	 => __( 'Download Now!', 'simple-download-monitor' ),
	'new_window'	 => '',
	'color'		 => '',
	'css_class'	 => 'sdm_fancy2_grid',
	'show_size'	 => '',
	'show_version'	 => '',
    'more_details_url' => '', 
    'more_details_anchor' => '',
    ), $args )
    );

    // Make shortcode attributes available in function local scope.
    extract( $shortcode_atts );

    // Check the download ID
    if ( empty( $id ) ) {
	return '<div class="sdm_error_msg">Error! The shortcode is missing the ID parameter. Please refer to the documentation to learn the shortcode usage.</div>';
    }

    $id = intval( $id );

    $more_details_url = esc_url_raw($more_details_url);
    $more_details_anchor = sdm_sanitize_text($more_details_anchor);

    
    // See if new window parameter is set
    if ( empty( $new_window ) ) {
	$new_window = get_post_meta( $id, 'sdm_item_new_window', true );
    }
    $window_target = empty( $new_window ) ? '_self' : '_blank';

    // Get the download URL and generate the download button code.
    $download_url = sdm_get_standard_download_url_from_id($id);
    $download_button_code = '<a href="' . $download_url . '" class="sdm_fancy2_download_dl_link" target="' . $window_target . '">' . $button_text . '</a>';

    // Check to see if the download link cpt is password protected
    $get_cpt_object	 = get_post( $id );
    $cpt_is_password = ! empty( $get_cpt_object->post_password ) ? 'yes' : 'no';  // yes = download is password protected;
    // Read plugin settings
    $main_opts	 = get_option( 'sdm_downloads_options' );

    $main_advanced_opts = get_option( 'sdm_advanced_options' );

    //Check if Terms & Condition enabled
    $termscond_enable = isset( $main_advanced_opts[ 'termscond_enable' ] ) ? true : false;
    if ( $termscond_enable ) {
	$download_button_code = sdm_get_download_form_with_termsncond( $id, $shortcode_atts, 'sdm_fancy2_download_dl_link' );
    }

	//Check if reCAPTCHA enabled
	$recaptcha_enable = sdm_is_any_recaptcha_enabled();
	if ( $recaptcha_enable && $cpt_is_password == 'no' ) {
		$download_button_code = sdm_get_download_form_with_recaptcha( $id, $shortcode_atts, 'sdm_download ' );
	}

    if ( $cpt_is_password !== 'no' ) {//This is a password protected download so replace the download now button with password requirement
	$download_button_code = sdm_get_password_entry_form( $id, $shortcode_atts, 'sdm_fancy2_download_dl_link' );
    }

    // Get item title
    $item_title = get_the_title( $id );
    
    // Get item thumbnail
    $thumbnail_alt = apply_filters ( 'sdm_download_fancy_2_thumbnail_alt', $item_title, $id );//Trigger a filter for the thumbnail alt
    $item_download_thumbnail	 = get_post_meta( $id, 'sdm_upload_thumbnail', true );
    $isset_download_thumbnail	 = isset( $item_download_thumbnail ) && ! empty( $item_download_thumbnail ) ? '<img class="sdm_fancy2_thumb_image" src="' . esc_url($item_download_thumbnail) . '" alt = "' . esc_html($thumbnail_alt) . '" />' : '';
    $isset_download_thumbnail	 = apply_filters( 'sdm_download_fancy_2_thumbnail', $isset_download_thumbnail, $args ); //Apply filter so it can be customized.

    // Get item description
    $isset_item_description = sdm_get_item_description_output( $id );

    //Get item file size
    $item_file_size = get_post_meta( $id, 'sdm_item_file_size', true );
    //Check if show file size is enabled
    if ( empty( $show_size ) ) {
	//Disabled in shortcode. Lets check if it is enabled in the download meta.
	$show_size = get_post_meta( $id, 'sdm_item_show_file_size_fd', true );
    }
    $isset_item_file_size	 = ($show_size && isset( $item_file_size )) ? $item_file_size : ''; //check if show_size is enabled and if there is a size value
    //Get item version
    $item_version		 = get_post_meta( $id, 'sdm_item_version', true );
    //Check if show version is enabled
    if ( empty( $show_version ) ) {
	//Disabled in shortcode. Lets check if it is enabled in the download meta.
	$show_version = get_post_meta( $id, 'sdm_item_show_item_version_fd', true );
    }
    $isset_item_version	 = ($show_version && isset( $item_version )) ? $item_version : ''; //check if show_version is enabled and if there is a version value
    // check show date in fancy display
    $show_date_fd = get_post_meta( $id, 'sdm_item_show_date_fd', true );
    // Get item date
    $download_date = get_the_date( get_option( 'date_format' ), $id );

    $output	 = '';
    $output	 .= '<div class="sdm_fancy_template_wrapper">';//For block themes
    $output	 .= '<div class="sdm_fancy2_item ' . esc_attr($css_class) . '">';
    $output	 .= '<div class="sdm_fancy2_wrapper">';
    
    $output	 .= '<div class="sdm_fancy2_download_item_top">';
    $output	 .= '<div class="sdm_fancy2_download_thumbnail">' . $isset_download_thumbnail . '</div>';
    $output	 .= '</div>'; //End of .sdm_download_item_top

    $output .= '<div class="sdm_fancy2_download_title">' . esc_html($item_title) . '</div>';

    //Show file size info if specified in the shortcode
    if ( ! empty( $isset_item_file_size ) ) {
	$output	 .= '<div class="sdm_fancy2_download_size">';
	$output	 .= '<span class="sdm_fancy2_download_size_label">' . __( 'Size: ', 'simple-download-monitor' ) . '</span>';
	$output	 .= '<span class="sdm_fancy2_download_size_value">' . $isset_item_file_size . '</span>';
	$output	 .= '</div>';
    }

    //Show version info if specified in the shortcode
    if ( ! empty( $isset_item_version ) ) {
        $output	 .= '<div class="sdm_fancy2_download_version">';
        $output	 .= '<span class="sdm_fancy2_download_version_label">' . __( 'Version: ', 'simple-download-monitor' ) . '</span>';
        $output	 .= '<span class="sdm_fancy2_download_version_value">' . $isset_item_version . '</span>';
        $output	 .= '</div>';
    }

    //Show date info if specified in the shortcode
    if ( $show_date_fd ) {
        $output	 .= '<div class="sdm_fancy2_download_date">';
        $output	 .= '<span class="sdm_fancy2_download_date_label">' . __( 'Published: ', 'simple-download-monitor' ) . '</span>';
        $output	 .= '<span class="sdm_fancy2_download_date_value">' . $download_date . '</span>';
        $output	 .= '</div>';
    }

    //Show more details link if specified in the shortcode
    if ( ! empty( $more_details_url ) ) {
        $output .= '<div class="sdm_fancy2_more_details_link_section">';
        $output .= '<a href="'. $more_details_url . '" class="sdm_fancy2_more_details_link">' . $more_details_anchor . '</a>';
        $output .= '</div>';
    }

    //Filter hook to allow other plugins to add their own HTML code before the download button.
    $extra_html_before_button = apply_filters( 'sdm_before_download_button', '', $id, $args );
    $output .= $extra_html_before_button;

    //The download button section.
    //Filter hook to allow other plugins to customize the download button code.
    $download_button_code = apply_filters( 'sdm_download_button_code_html', $download_button_code );    
    $output .= '<div class="sdm_fancy2_download_link">';
    $output .= $download_button_code;
    $output .= '</div>';

    $output .= '</div>'; //end .sdm_fancy2_wrapper
    $output .= '</div>'; //end .sdm_fancy2_item
    $output .= '</div>'; //end .sdm_fancy_template_wrapper

    //Filter to allow overriding the output
    $output = apply_filters( 'sdm_generate_fancy2_display_output_html', $output, $args );

    return $output;
}
