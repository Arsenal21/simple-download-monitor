<?php
/**
 * @var array $args Shortcode attributes.
 */

extract( $args );

$id = intval( $id );

// Read plugin settings
//$main_opts = get_option( 'sdm_downloads_options' );

// See if new window parameter is set
if ( empty( $new_window ) ) {
	$new_window = get_post_meta( $id, 'sdm_item_new_window', true );
}
$window_target = empty( $new_window ) ? '_self' : '_blank';
$window_target = apply_filters( 'sdm_download_window_target', $window_target );

// Get CPT title
$item_title = get_the_title( $id );

// Get download details page URL
$dl_post_url                = get_permalink( $id );
$link_text                  = __( 'View Details', 'simple-download-monitor' );
$download_details_link_code = '<a href="' . esc_url($dl_post_url) . '" class="sdm_fancy3_view_details" title="' . esc_html( $item_title ) . '" target="' . esc_attr($window_target) . '">' . esc_attr( $link_text ) . '</a>';

$output = '';

$output .= '<div class="sdm_fancy3_download_item ' . esc_attr( $css_class ) . '">';
$output .= '<div class="sdm_fancy3_download_item_left">';
$output .= '<span class="sdm_fancy3_download_title">' . esc_html( $item_title ) . '</span>';
$output .= '</div>'; //End of .sdm_fancy3_download_title

$output .= '<div class="sdm_fancy3_download_right">';

//apply filter on view details button HTML code
$download_details_link_code = apply_filters( 'sdm_fancy3_view_details_link_code_html', $download_details_link_code );

$output .= '<span class="sdm_fancy3_view_details_link">' . $download_details_link_code . '</span>';

$output .= '</div>'; //end .sdm_fancy3_download_right
$output .= '<div class="sdm_clear_float"></div>';
$output .= '</div>'; //end .sdm_fancy3_download_item

return $output;
