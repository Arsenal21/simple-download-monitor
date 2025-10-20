<?php

function sdm_generate_fancy3_popular_downloads_display_output( $get_posts, $args ) {

    $output = "";

    foreach ( $get_posts as $item ) {
	$opts = $args;
	$opts[ 'id' ] = $item->ID;
	$output .= sdm_generate_fancy3_display_output( $opts );
    }
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

function sdm_generate_fancy3_latest_downloads_display_output( $get_posts, $args ) {

    $output = "";

    foreach ( $get_posts as $item ) {
	$output .= sdm_generate_fancy3_display_output(
	array_merge( $args, array( 'id' => $item->ID ) )
	);
    }
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

function sdm_generate_fancy3_category_display_output( $get_posts, $args ) {

    $output = "";
    //TODO - when the CSS file is moved to the fancy3 folder, change it here

    foreach ( $get_posts as $item ) {
        $args = array_merge( $args, array( 'id' => $item->ID ) );
		$output .= sdm_generate_fancy3_display_output($args);
    }
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

/*
 * Generates the output of a single item using fancy2 sytle
 * $args array can have the following parameters
 * id, fancy, button_text, new_window
 */

function sdm_generate_fancy3_display_output( $args ) {
	$args = sanitize_sdm_create_download_shortcode_atts(
		shortcode_atts( array(
			'id'           => '',
			'button_text'  => __( 'View Details', 'simple-download-monitor' ),
			'new_window'   => '',
			'color'        => '',
			'css_class'    => '',
			'show_size'    => '',
			'show_version' => '',
		), $args )
	);

    // Check the download ID
    if ( empty($args['id']) ) {
		return '<div class="sdm_error_msg">Error! The shortcode is missing the ID parameter. Please refer to the documentation to learn the shortcode usage.</div>';
    }

	return sdm_load_template(3, $args);
}
