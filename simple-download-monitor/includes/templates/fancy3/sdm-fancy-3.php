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
    //$output .= '<link type="text/css" rel="stylesheet" href="' . WP_SIMPLE_DL_MONITOR_URL . '/includes/templates/fancy3/sdm-fancy-3-styles.css?ver=' . WP_SIMPLE_DL_MONITOR_VERSION . '" />';

    foreach ( $get_posts as $item ) {
	$output .= sdm_generate_fancy3_display_output(
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

function sdm_generate_fancy3_display_output( $args ) {

    $shortcode_atts = sanitize_sdm_create_download_shortcode_atts(
    shortcode_atts( array(
	'id'		 => '',
	'button_text'	 => __( 'View Details', 'simple-download-monitor' ),
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
    //$main_opts = get_option( 'sdm_downloads_options' );

    // See if new window parameter is set
    if ( empty( $new_window ) ) {
	$new_window = get_post_meta( $id, 'sdm_item_new_window', true );
    }
    $window_target = empty( $new_window ) ? '_self' : '_blank';

    // Get CPT title
    $item_title = get_the_title( $id );

    // Get download details page URL
    $dl_post_url = get_permalink($id);
    $link_text = __( 'View Details', 'simple-download-monitor' );
    $download_details_link_code = '<a href="' . $dl_post_url . '" class="sdm_fancy3_view_details" title="' . $item_title . '" target="' . $window_target . '">' . $link_text . '</a>';

    $output = '';

    $output .= '<div class="sdm_fancy3_download_item ' . $css_class . '">';
    $output .= '<div class="sdm_fancy3_download_item_left">';
    $output .= '<span class="sdm_fancy3_download_title">' . $item_title . '</span>';
    $output .= '</div>'; //End of .sdm_fancy3_download_title

    $output .= '<div class="sdm_fancy3_download_right">';

    //apply filter on view details button HTML code
    $download_details_link_code = apply_filters( 'sdm_fancy3_view_details_link_code_html', $download_details_link_code );

    $output .= '<span class="sdm_fancy3_view_details_link">' . $download_details_link_code . '</span>';

    $output .= '</div>'; //end .sdm_fancy3_download_right
    $output .= '<div class="sdm_clear_float"></div>';
    $output .= '</div>'; //end .sdm_fancy3_download_item

    return $output;
}
