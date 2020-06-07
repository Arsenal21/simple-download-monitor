<?php

function sdm_generate_fancy1_popular_downloads_display_output( $get_posts, $args ) {

    $output = "";

    foreach ( $get_posts as $item ) {
	$opts		 = $args;
	$opts[ 'id' ]	 = $item->ID;
	$output		 .= sdm_generate_fancy1_display_output( $opts );
    }
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

function sdm_generate_fancy1_latest_downloads_display_output( $get_posts, $args ) {

    $output = "";

    foreach ( $get_posts as $item ) {
	$output .= sdm_generate_fancy1_display_output(
	array_merge( $args, array( 'id' => $item->ID ) )
	);
    }
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

function sdm_generate_fancy1_category_display_output( $get_posts, $args ) {

    $output = "";

    //TODO - when the CSS file is moved to the fancy1 folder, change it here
    //$output .= '<link type="text/css" rel="stylesheet" href="' . WP_SIMPLE_DL_MONITOR_URL . '/includes/templates/fancy1/sdm-fancy-1-styles.css?ver=' . WP_SIMPLE_DL_MONITOR_VERSION . '" />';

    foreach ( $get_posts as $item ) {
	$output .= sdm_generate_fancy1_display_output(
	array_merge( $args, array( 'id' => $item->ID ) )
	);
    }
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

/*
 * Generates the output of a single item using fancy2 sytle
 * $args array can have the following parameters
 * id, fancy, button_text, new_window
 */

function sdm_generate_fancy1_display_output( $args ) {

    $shortcode_atts = sanitize_sdm_create_download_shortcode_atts(
    shortcode_atts( array(
	'id'		 => '',
	'button_text'	 => __( 'Download Now!', 'simple-download-monitor' ),
	'new_window'	 => '',
	'color'		 => '',
	'css_class'	 => '',
	'show_size'	 => '',
	'show_version'	 => '',
    ), $args )
    );

    // Make shortcode attributes available in function local scope.
    extract( $shortcode_atts );

    // Check the download ID
    if ( empty( $id ) ) {
	return '<div class="sdm_error_msg">Error! The shortcode is missing the ID parameter. Please refer to the documentation to learn the shortcode usage.</div>';
    }

    // Read plugin settings
    $main_opts = get_option( 'sdm_downloads_options' );

    // See if new window parameter is set
    if ( empty( $new_window ) ) {
	$new_window = get_post_meta( $id, 'sdm_item_new_window', true );
    }
    $window_target = empty( $new_window ) ? '_self' : '_blank';

    // Get CPT thumbnail
    $item_download_thumbnail	 = get_post_meta( $id, 'sdm_upload_thumbnail', true );
    $isset_download_thumbnail	 = isset( $item_download_thumbnail ) && ! empty( $item_download_thumbnail ) ? '<img class="sdm_download_thumbnail_image" src="' . $item_download_thumbnail . '" />' : '';
    $isset_download_thumbnail	 = apply_filters( 'sdm_download_fancy_1_thumbnail', $isset_download_thumbnail, $args ); //Apply filter so it can be customized.
    // Get CPT title
    $item_title			 = get_the_title( $id );

    // Get CPT description
    $isset_item_description = sdm_get_item_description_output( $id );

    // Get download button
    $homepage		 = get_bloginfo( 'url' );
    $download_url		 = $homepage . '/?smd_process_download=1&download_id=' . $id;
    $download_button_code	 = '<a href="' . $download_url . '" class="sdm_download ' . $color . '" title="' . $item_title . '" target="' . $window_target . '">' . $button_text . '</a>';

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
    //Check to see if the download link cpt is password protected
    $get_cpt_object		 = get_post( $id );
    $cpt_is_password	 = ! empty( $get_cpt_object->post_password ) ? 'yes' : 'no';  // yes = download is password protected;
    //Check if show date is enabled
    $show_date_fd		 = get_post_meta( $id, 'sdm_item_show_date_fd', true );
    //Get item date
    $download_date		 = get_the_date( get_option( 'date_format' ), $id );

    $main_advanced_opts = get_option( 'sdm_advanced_options' );

    //Check if Terms & Condition enabled
    $termscond_enable = isset( $main_advanced_opts[ 'termscond_enable' ] ) ? true : false;
    if ( $termscond_enable ) {
	$download_button_code = sdm_get_download_form_with_termsncond( $id, $shortcode_atts, 'sdm_download ' . $color );
    }

    //Check if reCAPTCHA enabled
    $recaptcha_enable = isset( $main_advanced_opts[ 'recaptcha_enable' ] ) ? true : false;
    if ( $recaptcha_enable && $cpt_is_password == 'no' ) {
	$download_button_code = sdm_get_download_form_with_recaptcha( $id, $shortcode_atts, 'sdm_download ' . $color );
    }

    if ( $cpt_is_password !== 'no' ) {//This is a password protected download so replace the download now button with password requirement
	$download_button_code = sdm_get_password_entry_form( $id, $shortcode_atts, 'sdm_download ' . $color );
    }

    $db_count		 = sdm_get_download_count_for_post( $id );
    $string			 = ($db_count == '1') ? __( 'Download', 'simple-download-monitor' ) : __( 'Downloads', 'simple-download-monitor' );
    $download_count_string	 = '<span class="sdm_item_count_number">' . $db_count . '</span><span class="sdm_item_count_string"> ' . $string . '</span>';

    $output = '';

    $output	 .= '<div class="sdm_download_item ' . $css_class . '">';
    $output	 .= '<div class="sdm_download_item_top">';
    $output	 .= '<div class="sdm_download_thumbnail">' . $isset_download_thumbnail . '</div>';
    $output	 .= '<div class="sdm_download_title">' . $item_title . '</div>';
    $output	 .= '</div>'; //End of .sdm_download_item_top
    $output	 .= '<div style="clear:both;"></div>';

    $output .= '<div class="sdm_download_description">' . $isset_item_description . '</div>';

    //This hook can be used to add content below the description in fancy1 template
    $params = array( 'id' => $id );
    $output .= apply_filters( 'sdm_fancy1_below_download_description', '', $params);

    if ( ! empty( $isset_item_file_size ) ) {//Show file size info
	$output	 .= '<div class="sdm_download_size">';
	$output	 .= '<span class="sdm_download_size_label">' . __( 'Size: ', 'simple-download-monitor' ) . '</span>';
	$output	 .= '<span class="sdm_download_size_value">' . $isset_item_file_size . '</span>';
	$output	 .= '</div>';
    }

    if ( ! empty( $isset_item_version ) ) {//Show version info
	$output	 .= '<div class="sdm_download_version">';
	$output	 .= '<span class="sdm_download_version_label">' . __( 'Version: ', 'simple-download-monitor' ) . '</span>';
	$output	 .= '<span class="sdm_download_version_value">' . $isset_item_version . '</span>';
	$output	 .= '</div>';
    }

    if ( $show_date_fd ) {//Show date
	$output	 .= '<div class="sdm_download_date">';
	$output	 .= '<span class="sdm_download_date_label">' . __( 'Published: ', 'simple-download-monitor' ) . '</span>';
	$output	 .= '<span class="sdm_download_date_value">' . $download_date . '</span>';
	$output	 .= '</div>';
    }


    $output .= '<div class="sdm_download_link">';

    //apply filter on button HTML code
    $download_button_code = apply_filters( 'sdm_download_button_code_html', $download_button_code );

    $output .= '<span class="sdm_download_button">' . $download_button_code . '</span>';
    if ( ! isset( $main_opts[ 'general_hide_donwload_count' ] ) ) {//The hide download count is enabled.
	$output .= '<span class="sdm_download_item_count">' . $download_count_string . '</span>';
    }
    $output	 .= '</div>'; //end .sdm_download_link
    $output	 .= '</div>'; //end .sdm_download_item


    return $output;
}
