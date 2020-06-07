<?php

//Handles the output on the SDM individual download page (Custom Post Type)
add_filter( 'the_content', 'filter_sdm_post_type_content' );

function filter_sdm_post_type_content( $content ) {
    global $post;
    if ( isset( $post->post_type ) && $post->post_type == "sdm_downloads" ) {//Handle the content for sdm_downloads type post
	//$download_id = $post->ID;
	//$args = array('id' => $download_id, 'fancy' => '1');
	//$content = sdm_create_download_shortcode($args);
	$id = $post->ID;

        //Check if the single download page is disabled.
        $sdm_item_disable_single_download_page = get_post_meta( $id, 'sdm_item_disable_single_download_page', true );
        if ($sdm_item_disable_single_download_page){
            //Page is disabled. Show message and return.
            $content .= '<div class="sdm_post_single_download_page_disabled_msg">';
            $msg = __('The admin of this site has disabled this download item page.', 'simple-download-monitor');
            $content .= apply_filters('sdm_post_single_download_page_disabled_msg', $msg);
            $content .= '</div>';
            return $content;
        }

	//Check to see if the download link cpt is password protected
	$get_cpt_object			 = get_post( $id );
	$cpt_is_password		 = ! empty( $get_cpt_object->post_password ) ? 'yes' : 'no';  // yes = download is password protected;
	//Get item thumbnail
	$item_download_thumbnail	 = get_post_meta( $id, 'sdm_upload_thumbnail', true );
	$isset_download_thumbnail	 = isset( $item_download_thumbnail ) && ! empty( $item_download_thumbnail ) ? '<img class="sdm_post_thumbnail_image" src="' . $item_download_thumbnail . '" />' : '';

	//Get item title
	$item_title		 = get_the_title( $id );
	$isset_item_title	 = isset( $item_title ) && ! empty( $item_title ) ? $item_title : '';

	//Get item description
	$isset_item_description = sdm_get_item_description_output( $id );

	//$isset_item_description = apply_filters('the_content', $isset_item_description);
	//Get item file size
	$item_file_size		 = get_post_meta( $id, 'sdm_item_file_size', true );
	$isset_item_file_size	 = isset( $item_file_size ) ? $item_file_size : '';

	//Get item version
	$item_version		 = get_post_meta( $id, 'sdm_item_version', true );
	$isset_item_version	 = isset( $item_version ) ? $item_version : '';

	//Check if show published date is enabled
	$show_date_fd	 = get_post_meta( $id, 'sdm_item_show_date_fd', true );
	//Get published date
	$published_date	 = get_the_date( get_option( 'date_format' ), $id );

	// See if user color option is selected
	$main_opts	 = get_option( 'sdm_downloads_options' );
	$color_opt	 = $main_opts[ 'download_button_color' ];
	$def_color	 = isset( $color_opt ) ? str_replace( ' ', '', strtolower( $color_opt ) ) : __( 'green', 'simple-download-monitor' );

	//Download counter
	//$dl_counter = sdm_create_counter_shortcode(array('id'=>$id));
	//*** Generate the download now button code ***
	$button_text_string = sdm_get_default_download_button_text( $post->ID );

	// See if new window parameter is set
	$new_window	 = get_post_meta( $id, 'sdm_item_new_window', true );
	$window_target	 = empty( $new_window ) ? '_self' : '_blank';

	$homepage		 = get_bloginfo( 'url' );
	$download_url		 = $homepage . '/?smd_process_download=1&download_id=' . $id;
	$download_button_code	 = '<a href="' . $download_url . '" class="sdm_download ' . $def_color . '" title="' . $isset_item_title . '" target="' . $window_target . '">' . $button_text_string . '</a>';

	$main_advanced_opts = get_option( 'sdm_advanced_options' );

	//Check if Terms & Condition enabled
	$termscond_enable = isset( $main_advanced_opts[ 'termscond_enable' ] ) ? true : false;
	if ( $termscond_enable ) {
	    $download_button_code = sdm_get_download_form_with_termsncond( $id, array(), 'sdm_download ' . $def_color );
	}

	//Check if reCAPTCHA enabled
	$recaptcha_enable = isset( $main_advanced_opts[ 'recaptcha_enable' ] ) ? true : false;
	if ( $recaptcha_enable && $cpt_is_password == 'no' ) {
	    $download_button_code = sdm_get_download_form_with_recaptcha( $id, array(), 'sdm_download ' . $def_color );
	}

	if ( $cpt_is_password !== 'no' ) {//This is a password protected download so replace the download now button with password requirement
	    $download_button_code = sdm_get_password_entry_form( $id, array(), 'sdm_download ' . $def_color );
	}

	// Check if we only allow the download for logged-in users
//        if (isset($main_opts['only_logged_in_can_download'])) {
//            if ($main_opts['only_logged_in_can_download'] && sdm_get_logged_in_user()===false) {
//                // User not logged in, let's display the message
//                $download_button_code = __('You need to be logged in to download this file.','simple-download-monitor');
//            }
//        }

	$db_count		 = sdm_get_download_count_for_post( $id );
	$string			 = ($db_count == '1') ? __( 'Download', 'simple-download-monitor' ) : __( 'Downloads', 'simple-download-monitor' );
	$download_count_string	 = '<span class="sdm_post_count_number">' . $db_count . '</span><span class="sdm_post_count_string"> ' . $string . '</span>';

	//Output the download item details
	$content = '<div class="sdm_post_item">';
	$content .= '<div class="sdm_post_item_top">';

	$content .= '<div class="sdm_post_item_top_left">';
	$content .= '<div class="sdm_post_thumbnail">' . $isset_download_thumbnail . '</div>';
	$content .= '</div>'; //end .sdm_post_item_top_left

	$content .= '<div class="sdm_post_item_top_right">';
	$content .= '<div class="sdm_post_title">' . $isset_item_title . '</div>';

	if ( ! isset( $main_opts[ 'general_hide_donwload_count' ] ) ) {//The hide download count is enabled.
	    $content .= '<div class="sdm_post_download_count">' . $download_count_string . '</div>';
	}

	$content .= '<div class="sdm_post_description">' . $isset_item_description . '</div>';

	//This hook can be used to add content below the description
        $params = array( 'id' => $id );
	$content .= apply_filters( 'sdm_cpt_below_download_description', '', $params);

        //Check if the button of the single download page is disabled.
        $sdm_item_hide_dl_button_single_download_page = get_post_meta( $id, 'sdm_item_hide_dl_button_single_download_page', true );
        if ($sdm_item_hide_dl_button_single_download_page){
            //the download button is disabled.
            $content .= '<div class="sdm_post_single_download_page_disabled_dl_button_msg">';
            $msg = '<p>' . __('The admin of this site has disabled the download button for this page.', 'simple-download-monitor') . '</p>';
            $content .= apply_filters('sdm_post_single_download_page_disabled_dl_button_msg', $msg);
            $content .= '</div>';
        } else {
            $content .= '<div class="sdm_post_download_section"><div class="sdm_download_link">' . $download_button_code . '</div></div>';
        }

	if ( ! empty( $isset_item_file_size ) ) {//Show file size info
	    $content .= '<div class="sdm_post_download_file_size">';
	    $content .= '<span class="sdm_post_download_size_label">' . __( 'Size: ', 'simple-download-monitor' ) . '</span>';
	    $content .= '<span class="sdm_post_download_size_value">' . $isset_item_file_size . '</span>';
	    $content .= '</div>';
	}

	if ( ! empty( $isset_item_version ) ) {//Show version info
	    $content .= '<div class="sdm_post_download_version">';
	    $content .= '<span class="sdm_post_download_version_label">' . __( 'Version: ', 'simple-download-monitor' ) . '</span>';
	    $content .= '<span class="sdm_post_download_version_value">' . $isset_item_version . '</span>';
	    $content .= '</div>';
	}

	if ( $show_date_fd ) {//Show Published date
	    $content .= '<div class="sdm_post_download_published_date">';
	    $content .= '<span class="sdm_post_download_published_date_label">' . __( 'Published: ', 'simple-download-monitor' ) . '</span>';
	    $content .= '<span class="sdm_post_download_published_date_value">' . $published_date . '</span>';
	    $content .= '</div>';
	}

	//$content .= '<div class="sdm_post_meta_section"></div>';//TODO - Show post meta (category and tags)
	$content .= '</div>'; //end .sdm_post_item_top_right

	$content .= '</div>'; //end of .sdm_download_item_top

	$content .= '<div style="clear:both;"></div>';

	$content .= '</div>'; //end .sdm_post_item

	return $content;
    }

    return $content;
}

//The following filters are applied to the output of the SDM description field.
add_filter( 'sdm_downloads_description', 'do_shortcode' );
add_filter( 'sdm_downloads_description', 'wptexturize' );
add_filter( 'sdm_downloads_description', 'convert_smilies' );
add_filter( 'sdm_downloads_description', 'convert_chars' );
add_filter( 'sdm_downloads_description', 'wpautop' );
add_filter( 'sdm_downloads_description', 'shortcode_unautop' );
add_filter( 'sdm_downloads_description', 'prepend_attachment' );

function sdm_get_item_description_output( $id ) {
    $item_description		 = get_post_meta( $id, 'sdm_description', true );
    $isset_item_description		 = isset( $item_description ) && ! empty( $item_description ) ? $item_description : '';
    //$isset_item_description = apply_filters('the_content', $isset_item_description);
    //Lets apply all the filters like do_shortcode, wptexturize, convert_smilies, wpautop etc.
    $filtered_item_description	 = apply_filters( 'sdm_downloads_description', $isset_item_description );

    return $filtered_item_description;
}

//Add adsense or ad code below the description (if applicable)
add_filter( 'sdm_cpt_below_download_description', 'sdm_add_ad_code_below_description', 10, 2 );
add_filter( 'sdm_fancy1_below_download_description', 'sdm_add_ad_code_below_description', 10, 2 );

function sdm_add_ad_code_below_description( $output, $args ) {
    $main_advanced_opts	 = get_option( 'sdm_advanced_options' );
    $adsense_below_desc	 = isset( $main_advanced_opts[ 'adsense_below_description' ] ) ? $main_advanced_opts[ 'adsense_below_description' ] : '';
    if ( ! empty( $adsense_below_desc ) ) {
	//Ad code is configured in settings. Lets add it to the output.
	$output .= '<div class="sdm_below_description_ad_code">' . $adsense_below_desc . '</div>';
    }
    return $output;
}
