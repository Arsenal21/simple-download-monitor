<?php

//TODO write a function for doing grid display then the conditional function calls this grid display function

function sdm_generate_fancy2_popular_downloads_display_output( $get_posts, $args ) {
	wp_enqueue_style( 'sdm_generate_fancy2_popular_downloads_display_styles', WP_SIMPLE_DL_MONITOR_URL . '/includes/templates-related/fancy2/sdm-fancy-2-styles.css' , array(), WP_SIMPLE_DL_MONITOR_VERSION );
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
	wp_enqueue_style( 'sdm_generate_fancy2_latest_downloads_display_styles', WP_SIMPLE_DL_MONITOR_URL . '/includes/templates-related/fancy2/sdm-fancy-2-styles.css' , array(), WP_SIMPLE_DL_MONITOR_VERSION );
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
	wp_enqueue_style( 'sdm_generate_fancy2_category_display_styles', WP_SIMPLE_DL_MONITOR_URL . '/includes/templates-related/fancy2/sdm-fancy-2-styles.css' , array(), WP_SIMPLE_DL_MONITOR_VERSION );
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

	    $args_fresh = array_merge( $args_fresh, array( 'id' => $item->ID ) );
		$output .= sdm_generate_fancy2_display_output($args_fresh);

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
	$args = sanitize_sdm_create_download_shortcode_atts(
		shortcode_atts( array(
			'id'                  => '',
			'button_text'         => __( 'Download Now!', 'simple-download-monitor' ),
			'new_window'          => '',
			'color'               => '',
			'css_class'           => 'sdm_fancy2_grid',
			'show_size'           => '',
			'show_version'        => '',
			'more_details_url'    => '',
			'more_details_anchor' => '',
		), $args )
	);


    // Check the download ID
    if ( empty( $args['id'] ) ) {
		return '<div class="sdm_error_msg">Error! The shortcode is missing the ID parameter. Please refer to the documentation to learn the shortcode usage.</div>';
    }

	return sdm_load_template(2, $args);
}
