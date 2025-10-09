<?php

function sdm_generate_fancy0_popular_downloads_display_output( $get_posts, $args ) {

    $output		 = "";
    isset( $args[ 'button_text' ] ) ? $button_text	 = $args[ 'button_text' ] : $button_text	 = '';
    isset( $args[ 'new_window' ] ) ? $new_window	 = $args[ 'new_window' ] : $new_window	 = '';
    foreach ( $get_posts as $item ) {
	$id	 = $item->ID;  //Get the post ID
	//Create a args array
	$args	 = array(
	    'id'		 => $id,
	    'fancy'		 => '0',
	    'button_text'	 => $button_text,
	    'new_window'	 => $new_window,
	);
	$output	 .= sdm_generate_fancy0_display_output( $args );
    }
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

function sdm_generate_fancy0_latest_downloads_display_output( $get_posts, $args ) {

    $output		 = "";
    isset( $args[ 'button_text' ] ) ? $button_text	 = $args[ 'button_text' ] : $button_text	 = '';
    isset( $args[ 'new_window' ] ) ? $new_window	 = $args[ 'new_window' ] : $new_window	 = '';
    foreach ( $get_posts as $item ) {
	$id	 = $item->ID;  //Get the post ID
	//Create a args array
	$args	 = array(
	    'id'		 => $id,
	    'fancy'		 => '0',
	    'button_text'	 => $button_text,
	    'new_window'	 => $new_window,
	);
	$output	 .= sdm_generate_fancy0_display_output( $args );
    }
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

function sdm_generate_fancy0_category_display_output($get_posts, $args) {

   $output = "";

   //TODO - when the CSS file is moved to the fancy1 folder, change it here

   foreach ($get_posts as $item) {
        //Create a args array
        $args = array(
            'id' =>  $item->ID,
            'fancy' => '1',
            'button_text' => isset($args['button_text']) ? $args['button_text'] : '',
            'new_window' => isset($args['new_window']) ? $args['new_window'] : '',
        );

        $tpl_data = $args;
        $output .= sdm_load_template(0, $tpl_data, false);

        // TODO: Old code, to be removed later.
        // $output .= sdm_generate_fancy0_display_output($args);
   }
   $output .= '<div class="sdm_clear_float"></div>';
   return $output;
}

/*
 * Generates the output of a single item using fancy2 sytle
 * $args array can have the following parameters
 * id, fancy, button_text, new_window
 */

function sdm_generate_fancy0_display_output( $args ) {

    //Get the download ID
    $id = $args[ 'id' ];
    if ( ! is_numeric( $id ) ) {
	return '<div class="sdm_error_msg">Error! The shortcode is missing the ID parameter. Please refer to the documentation to learn the shortcode usage.</div>';
    }

    // See if user color option is selected
    $main_opts	 = get_option( 'sdm_downloads_options' );
    $color_opt	 = isset( $main_opts[ 'download_button_color' ] ) ? $main_opts[ 'download_button_color' ] : null;
    $def_color	 = isset( $color_opt ) ? str_replace( ' ', '', strtolower( $color_opt ) ) : __( 'green', 'simple-download-monitor' );

    $def_color = empty( $args[ 'color' ] ) ? $def_color : $args[ 'color' ];

    //See if new window parameter is set in the shortcode args or download item meta.
    $window_target = '';
    if ( isset( $args[ 'new_window' ] ) && $args[ 'new_window' ] == '1' ) {
	    $window_target = 'target="_blank"';
    }
    if ( empty( $window_target ) ) {
        //Shortcode arg is not set so check the download item meta.
        $new_window = get_post_meta( $id, 'sdm_item_new_window', true );
        if( !empty( $new_window ) ){
            $window_target = 'target="_blank"';
        }
    }
    $window_target = apply_filters('sdm_download_window_target', $window_target);

    //Get the download button text
    $button_text = isset( $args[ 'button_text' ] ) ? $args[ 'button_text' ] : '';
    if ( empty( $button_text ) ) {//Use the default text for the button
	$button_text_string = __( 'Download Now!', 'simple-download-monitor' );
    } else {//Use the custom text
	$button_text_string = $button_text;
    }

    // Get CPT title
    $item_title = get_the_title( $id );
    $isset_item_title = isset( $item_title ) && ! empty( $item_title ) ? sanitize_text_field($item_title) : '';

    // Get download button
    $homepage = WP_SIMPLE_DL_MONITOR_SITE_HOME_URL;
    $download_url = $homepage . '/?sdm_process_download=1&download_id=' . $id;
    $download_button_code = '<a href="' . esc_url_raw($download_url) . '" class="sdm_download ' . esc_attr($def_color) . '" title="' . esc_html($isset_item_title) . '" ' . esc_attr($window_target) . '>' . esc_attr($button_text_string) . '</a>';

    // Check to see if the download link cpt is password protected
    $get_cpt_object	 = get_post( $id );
    $cpt_is_password = ! empty( $get_cpt_object->post_password ) ? 'yes' : 'no';  // yes = download is password protected;


    $main_advanced_opts = get_option( 'sdm_advanced_options' );

    //Check if Terms & Condition enabled
    $termscond_enable = isset( $main_advanced_opts[ 'termscond_enable' ] ) ? true : false;
    if ( $termscond_enable ) {
	$download_button_code = sdm_get_download_form_with_termsncond( $id, $args, 'sdm_download ' . $def_color );
    }

	//Check if reCAPTCHA enabled
	$recaptcha_enable = sdm_is_any_recaptcha_enabled();
	if ( $recaptcha_enable && $cpt_is_password == 'no' ) {
		$download_button_code = sdm_get_download_form_with_recaptcha( $id, $args, 'sdm_download ' . $def_color );
	}

	if ( $cpt_is_password !== 'no' ) {//This is a password protected download so replace the download now button with password requirement
	$download_button_code = sdm_get_password_entry_form( $id, $args, 'sdm_download ' . $def_color );
    }

    $output = "";

    //Filter hook to allow other plugins to add their own HTML code before the download button
    $extra_html_before_button = apply_filters( 'sdm_before_download_button', '', $id, $args );
    $output .= $extra_html_before_button;

    //Filter hook to allow other plugins to customize the download button code.
    $download_button_code = apply_filters( 'sdm_download_button_code_html', $download_button_code );

    $output .= '<div class="sdm_download_button_box_default"><div class="sdm_download_link">' . $download_button_code . '</div></div>';

    return $output;
}
