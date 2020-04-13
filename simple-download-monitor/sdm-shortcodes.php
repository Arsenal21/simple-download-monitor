<?php

add_filter( 'widget_text', 'do_shortcode' ); //Enable shortcode filtering in standard text widget

/*
 * * Register and handle Shortcode
 */

function sdm_register_shortcodes() {

	//Note: shortcode name should use underscores (not dashes). Some of the shortcodes have dashes for backwards compatibility.

	add_shortcode( 'sdm_download', 'sdm_create_download_shortcode' );  // For download shortcode (underscores)
	add_shortcode( 'sdm-download', 'sdm_create_download_shortcode' );  // For download shortcode (for backwards compatibility)
	add_shortcode( 'sdm_download_counter', 'sdm_create_counter_shortcode' );  // For counter shortcode (underscores)
	add_shortcode( 'sdm-download-counter', 'sdm_create_counter_shortcode' );  // For counter shortcode (for backwards compatibility)
	add_shortcode( 'sdm_latest_downloads', 'sdm_show_latest_downloads' ); // For showing X number of latest downloads
	add_shortcode( 'sdm-latest-downloads', 'sdm_show_latest_downloads' );  // For showing X number of latest downloads(for backwards compatibility)
	add_shortcode( 'sdm_popular_downloads', 'sdm_show_popular_downloads' ); // For showing X number of popular downloads

	add_shortcode( 'sdm_download_link', 'sdm_create_simple_download_link' );

	add_shortcode( 'sdm_show_all_dl', 'sdm_handle_show_all_dl_shortcode' ); // For show all downloads shortcode

	add_shortcode( 'sdm_show_dl_from_category', 'sdm_handle_category_shortcode' ); //For category shortcode
	add_shortcode( 'sdm_download_categories', 'sdm_download_categories_shortcode' ); // Ajax file tree browser

	add_shortcode( 'sdm_download_categories_list', 'sdm_download_categories_list_shortcode' );
	add_shortcode( 'sdm_search_form', 'sdm_search_form_shortcode' );

	add_shortcode( 'sdm_show_download_info', 'sdm_show_download_info_shortcode' );
}

/**
 * Process (sanitize) download button shortcode attributes:
 * - convert "id" to absolute integer
 * - set "color" to color from settings or default color, if empty
 * @param array $atts
 * @return array
 */
function sanitize_sdm_create_download_shortcode_atts( $atts ) {

	// Sanitize download item ID
	$atts['id'] = absint( $atts['id'] );

	// See if user color option is selected
	$main_opts = get_option( 'sdm_downloads_options' );

	if ( empty( $atts['color'] ) ) {
		// No color provided by shortcode, read color from plugin settings.
		$atts['color'] = isset( $main_opts['download_button_color'] ) ? strtolower( $main_opts['download_button_color'] ) // default values needs to be lowercased
		: 'green';
	}

	// Remove spaces from color key to make a proper CSS class name.
	$atts['color'] = str_replace( ' ', '', $atts['color'] );

	return $atts;
}

// Create Download Shortcode
function sdm_create_download_shortcode( $atts ) {

	$shortcode_atts = sanitize_sdm_create_download_shortcode_atts(
		shortcode_atts(
			array(
				'id'           => '',
				'fancy'        => '0',
				'button_text'  => sdm_get_default_download_button_text( $atts['id'] ),
				'new_window'   => '',
				'color'        => '',
				'css_class'    => '',
				'show_size'    => '',
				'show_version' => '',
			),
			$atts
		)
	);

	// Make shortcode attributes available in function local scope.
	extract( $shortcode_atts );

	if ( empty( $id ) ) {
		return '<p style="color: red;">' . __( 'Error! Please enter an ID value with this shortcode.', 'simple-download-monitor' ) . '</p>';
	}

	// Check to see if the download link cpt is password protected
	$get_cpt_object  = get_post( $id );
	$cpt_is_password = ! empty( $get_cpt_object->post_password ) ? 'yes' : 'no';  // yes = download is password protected;
	// Get CPT title
	$item_title = get_the_title( $id );

	//*** Generate the download now button code ***
	if ( empty( $new_window ) ) {
		$new_window = get_post_meta( $id, 'sdm_item_new_window', true );
	}
	$window_target = empty( $new_window ) ? '_self' : '_blank';

	$homepage             = get_bloginfo( 'url' );
	$download_url         = $homepage . '/?smd_process_download=1&download_id=' . $id;
	$download_button_code = '<a href="' . $download_url . '" class="sdm_download ' . $color . '" title="' . $item_title . '" target="' . $window_target . '">' . $button_text . '</a>';

	$main_advanced_opts = get_option( 'sdm_advanced_options' );

	//Check if Terms & Condition enabled
	$termscond_enable = isset( $main_advanced_opts['termscond_enable'] ) ? true : false;
	if ( $termscond_enable ) {
		$download_button_code = sdm_get_download_form_with_termsncond( $id, $shortcode_atts, 'sdm_download ' . $color );
	}

	//Check if reCAPTCHA enabled
	$recaptcha_enable = isset( $main_advanced_opts['recaptcha_enable'] ) ? true : false;
	if ( $recaptcha_enable && $cpt_is_password == 'no' ) {
		$download_button_code = sdm_get_download_form_with_recaptcha( $id, $shortcode_atts, 'sdm_download ' . $color );
	}

	if ( $cpt_is_password !== 'no' ) {//This is a password protected download so replace the download now button with password requirement
		$download_button_code = sdm_get_password_entry_form( $id, $shortcode_atts, 'sdm_download ' . $color );
	}
	//End of download now button code generation

	$output = '';
	switch ( $fancy ) {
		case '1':
			include_once 'includes/templates/fancy1/sdm-fancy-1.php';
			$output .= sdm_generate_fancy1_display_output( $shortcode_atts );
			$output .= '<div class="sdm_clear_float"></div>';
			break;
		case '2':
			include_once 'includes/templates/fancy2/sdm-fancy-2.php';
			$output .= '<link type="text/css" rel="stylesheet" href="' . WP_SIMPLE_DL_MONITOR_URL . '/includes/templates/fancy2/sdm-fancy-2-styles.css?ver=' . WP_SIMPLE_DL_MONITOR_VERSION . '" />';
			$output .= sdm_generate_fancy2_display_output( $shortcode_atts );
			$output .= '<div class="sdm_clear_float"></div>';
			break;
		case '3':
			include_once 'includes/templates/fancy3/sdm-fancy-3.php';
			$output .= sdm_generate_fancy3_display_output( $shortcode_atts );
			$output .= '<div class="sdm_clear_float"></div>';
			break;
		default: // Default output is the standard download now button (fancy 0)
			include_once 'includes/templates/fancy0/sdm-fancy-0.php';
			$output .= sdm_generate_fancy0_display_output( $shortcode_atts );
	}

	return apply_filters( 'sdm_download_shortcode_output', $output, $atts );
}

function sdm_create_simple_download_link( $atts ) {
	extract(
		shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		)
	);

	if ( empty( $id ) ) {
		return '<p style="color: red;">' . __( 'Error! Please enter an ID value with this shortcode.', 'simple-download-monitor' ) . '</p>';
	}

	return WP_SIMPLE_DL_MONITOR_SITE_HOME_URL . '/?smd_process_download=1&download_id=' . $id;
}

// Create Counter Shortcode
function sdm_create_counter_shortcode( $atts ) {

	extract(
		shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		)
	);

	if ( empty( $id ) ) {
		return '<p style="color: red;">' . __( 'Error! Please enter an ID value with this shortcode.', 'simple-download-monitor' ) . '</p>';
	}

	$db_count = sdm_get_download_count_for_post( $id );

	// Set string for singular/plural results
	$string = ( $db_count == '1' ) ? __( 'Download', 'simple-download-monitor' ) : __( 'Downloads', 'simple-download-monitor' );

	$output = '<div class="sdm_download_count"><span class="sdm_count_number">' . $db_count . '</span><span class="sdm_count_string"> ' . $string . '</span></div>';
	// Return result
	return apply_filters( 'sdm_download_count_output', $output, $atts );
}

// Create Category Shortcode
function sdm_handle_category_shortcode( $args ) {

	extract(
		shortcode_atts(
			array(
				'category_slug' => '',
				'category_id'   => '',
				'fancy'         => '0',
				'button_text'   => __( 'Download Now!', 'simple-download-monitor' ),
				'new_window'    => '',
				'orderby'       => 'post_date',
				'order'         => 'DESC',
				'pagination'    => '',
			),
			$args
		)
	);

	// Define vars
	$field = '';
	$terms = '';

	// If category slug and category id are empty.. return error
	if ( empty( $category_slug ) && empty( $category_id ) && empty( $args['show_all'] ) ) {
		return '<p style="color: red;">' . __( 'Error! You must enter a category slug OR a category id with this shortcode. Refer to the documentation for usage instructions.', 'simple-download-monitor' ) . '</p>';
	}

	// If both category slug AND category id are defined... return error
	if ( ! empty( $category_slug ) && ! empty( $category_id ) ) {
		return '<p style="color: red;">' . __( 'Error! Please enter a category slug OR id; not both.', 'simple-download-monitor' ) . '</p>';
	}

	// Else setup query arguments for category_slug
	if ( ! empty( $category_slug ) && empty( $category_id ) ) {

		$field = 'slug';

		$terms = array_filter(
			explode( ',', $category_slug ),
			function( $value ) {
				return ! empty( $value ) ? trim( $value ) : false;
			}
		);
	}
	// Else setup query arguments for category_id
	elseif ( ! empty( $category_id ) && empty( $category_slug ) ) {

		$field = 'term_id';
		//$terms = $category_id;
		$terms = array_filter(
			explode( ',', $category_id ),
			function( $value ) {
				return ! empty( $value ) ? trim( $value ) : false;
			}
		);
	}

	if ( isset( $args['show_all'] ) ) {
		$tax_query = array();
	} else {
		$tax_query = array(
			array(
				'taxonomy' => 'sdm_categories',
				'field'    => $field,
				'terms'    => $terms,
			),
		);
	}

	// For pagination
	$paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;
	if ( isset( $args['pagination'] ) ) {
		if ( ! is_numeric( $args['pagination'] ) ) {
			return '<p style="color: red;">' . __( 'Error! You must enter a numeric number for the "pagination" parameter of the shortcode. Refer to the usage documentation.', 'simple-download-monitor' ) . '</p>';
		}
		$posts_per_page = $args['pagination'];
	} else {
		$posts_per_page = 9999;
	}

	// Query cpt's based on arguments above
	$get_posts_args = array(
		'post_type'      => 'sdm_downloads',
		'show_posts'     => -1,
		'posts_per_page' => $posts_per_page,
		'tax_query'      => $tax_query,
		'orderby'        => $orderby,
		'order'          => $order,
		'paged'          => $paged,
	);

	$query = new WP_Query();

	$get_posts = $query->query( $get_posts_args );

	// If no cpt's are found
	if ( ! $get_posts ) {
		return '<p style="color: red;">' . __( 'There are no download items matching this category criteria.', 'simple-download-monitor' ) . '</p>';
	}
	// Else iterate cpt's
	else {

		$output = '';

		// See if user color option is selected
		$main_opts = get_option( 'sdm_downloads_options' );
		$color_opt = $main_opts['download_button_color'];
		$def_color = isset( $color_opt ) ? str_replace( ' ', '', strtolower( $color_opt ) ) : 'green';

		if ( $fancy == '0' ) {

			// Setup download location
			$homepage = get_bloginfo( 'url' );
			if ( empty( $new_window ) ) {
				$new_window = get_post_meta( $id, 'sdm_item_new_window', true );
			}

			$window_target = empty( $new_window ) ? '_self' : '_blank';

			// Iterate cpt's
			foreach ( $get_posts as $item ) {

				// Set download location
				$id           = $item->ID;  // get each cpt ID
				$download_url = $homepage . '/?smd_process_download=1&download_id=' . $id;

				// Get each cpt title
				$item_title = get_the_title( $id );

				// Setup download button code
				$download_button_code = '<a href="' . $download_url . '" class="sdm_download ' . $def_color . '" title="' . $item_title . '" target="' . $window_target . '">' . $button_text . '</a>';

				$main_advanced_opts = get_option( 'sdm_advanced_options' );

				//Check if Terms & Condition enabled
				$termscond_enable = isset( $main_advanced_opts['termscond_enable'] ) ? true : false;
				if ( $termscond_enable ) {
					$download_button_code = sdm_get_download_form_with_termsncond( $id, $args, 'sdm_download ' . $def_color );
				}

				//Check if reCAPTCHA enabled
				$recaptcha_enable = isset( $main_advanced_opts['recaptcha_enable'] ) ? true : false;
				if ( $recaptcha_enable ) {
					$download_button_code = sdm_get_download_form_with_recaptcha( $id, $args, 'sdm_download ' . $def_color );
				}

				// Generate download buttons
				$output .= '<div class="sdm_download_link">' . $download_button_code . '</div><br />';
			}  // End foreach
		}
		// Fancy 1 and onwards handles the loop inside the template function
		elseif ( $fancy == '1' ) {
			include_once 'includes/templates/fancy1/sdm-fancy-1.php';
			$output .= sdm_generate_fancy1_category_display_output( $get_posts, $args );
		} elseif ( $fancy == '2' ) {
			include_once 'includes/templates/fancy2/sdm-fancy-2.php';
			$output .= sdm_generate_fancy2_category_display_output( $get_posts, $args );
		} elseif ( $fancy == '3' ) {
			include_once 'includes/templates/fancy3/sdm-fancy-3.php';
			$output .= sdm_generate_fancy3_category_display_output( $get_posts, $args );
		}

		// Pagination related
		if ( isset( $args['pagination'] ) ) {
			$posts_per_page      = $args['pagination'];
			$count_sdm_posts     = $query->found_posts;
			$published_sdm_posts = $count_sdm_posts;
			$total_pages         = ceil( $published_sdm_posts / $posts_per_page );

			$big        = 999999999; // Need an unlikely integer
			$pagination = paginate_links(
				array(
					'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format'    => '',
					'add_args'  => '',
					'current'   => max( 1, get_query_var( 'paged' ) ),
					'total'     => $total_pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				)
			);
			$output    .= '<div class="sdm_pagination">' . $pagination . '</div>';
		}

		// Return results
		return apply_filters( 'sdm_category_download_items_shortcode_output', $output, $args, $get_posts );
	}  // End else iterate cpt's
}

// Create category tree shortcode
function sdm_download_categories_shortcode() {

	function custom_taxonomy_walker( $taxonomy, $parent = 0 ) {

		// Get terms (check if has parent)
		$terms = get_terms(
			$taxonomy,
			array(
				'parent'     => $parent,
				'hide_empty' => false,
			)
		);

		// If there are terms, start displaying
		if ( count( $terms ) > 0 ) {
			// Displaying as a list
			$out = '<ul>';
			// Cycle though the terms
			foreach ( $terms as $term ) {
				// Secret sauce. Function calls itself to display child elements, if any
				$out .= '<li class="sdm_cat" id="' . $term->slug . '"><span id="' . $term->term_id . '" class="sdm_cat_title" style="cursor:pointer;">' . $term->name . '</span>';
				$out .= '<p class="sdm_placeholder" style="margin-bottom:0;"></p>' . custom_taxonomy_walker( $taxonomy, $term->term_id );
				$out .= '</li>';
			}
			$out .= '</ul>';
			return $out;
		}
		return;
	}

	return '<div class="sdm_object_tree">' . custom_taxonomy_walker( 'sdm_categories' ) . '</div>';
}

/**
 * Return HTML list with SDM categories rendered according to $atts.
 * @param array $atts
 * @param int $parent
 * @return string
 */
function sdm_download_categories_list_walker( $atts, $parent = 0 ) {

	$count        = (bool) $atts['count'];
	$hierarchical = (bool) $atts['hierarchical'];
	$show_empty   = (bool) $atts['empty'];
	$list_tag     = $atts['numbered'] ? 'ol' : 'ul';

	// Get terms (check if has parent)
	$terms = get_terms(
		array(
			'taxonomy'   => 'sdm_categories',
			'parent'     => $parent,
			'hide_empty' => ! $show_empty,
		)
	);

	// Return empty string, if no terms found.
	if ( empty( $terms ) ) {
		return '';
	}

	// Produce list of download categories under $parent.
	$out = '<' . $list_tag . '>';

	foreach ( $terms as $term ) {
		$out .= '<li>'
		. '<a href="' . get_term_link( $term ) . '">' . $term->name . '</a>' // link
		. ( $count ? ( ' <span>(' . $term->count . ')</span>' ) : '' ) // count
		. ( $hierarchical ? sdm_download_categories_list_walker( $atts, $term->term_id ) : '' ) // subcategories
		. '</li>';
	}

	$out .= '</' . $list_tag . '>';

	return $out;
}

/**
 * Return output of `sdm_download_categories_list` shortcode.
 * @param array $attributes
 * @return string
 */
function sdm_download_categories_list_shortcode( $attributes ) {

	$atts = shortcode_atts(
		array(
			'class'        => 'sdm-download-categories', // wrapper class
			'empty'        => '0', // show empty categories
			'numbered'     => '0', // use <ol> instead of <ul> to wrap the list
			'count'        => '0', // display count of items in every category
			'hierarchical' => '1', // display subcategories as well
		),
		$attributes
	);

	return '<div class="' . esc_attr( $atts['class'] ) . '">'
	. sdm_download_categories_list_walker( $atts )
	. '</div>';
}

function sdm_show_download_info_shortcode( $args ) {
	extract(
		shortcode_atts(
			array(
				'id'            => '',
				'download_info' => '',
			),
			$args
		)
	);

	if ( empty( $id ) || empty( $download_info ) ) {
		return '<div class="sdm_shortcode_error">Error! you must enter a value for "id" and "download_info" parameters.</div>';
	}

	//Available values: title, description, download_url, thumbnail, file_size, file_version, download_count

	$id             = absint( $id );
	$get_cpt_object = get_post( $id );

	if ( $download_info == 'title' ) {//download title
		$item_title = get_the_title( $id );
		return $item_title;
	}

	if ( $download_info == 'description' ) {//download description
		$item_description = sdm_get_item_description_output( $id );
		return $item_description;
	}

	if ( $download_info == 'download_url' ) {//download URL
		$download_link = get_post_meta( $id, 'sdm_upload', true );
		return $download_link;
	}

	if ( $download_info == 'thumbnail' ) {//download thumb
		$download_thumbnail = get_post_meta( $id, 'sdm_upload_thumbnail', true );
		$download_thumbnail = '<img class="sdm_download_thumbnail_image" src="' . $download_thumbnail . '" />';
		return $download_thumbnail;
	}

	if ( $download_info == 'thumbnail_url' ) {//download thumbnail raw URL
		$download_thumbnail = get_post_meta( $id, 'sdm_upload_thumbnail', true );
		return $download_thumbnail;
	}

	if ( $download_info == 'file_size' ) {//download file size
		$file_size = get_post_meta( $id, 'sdm_item_file_size', true );
		return $file_size;
	}

	if ( $download_info == 'file_version' ) {//download file version
		$file_version = get_post_meta( $id, 'sdm_item_version', true );
		return $file_version;
	}

	if ( $download_info == 'download_count' ) {//download count
		$dl_count = sdm_get_download_count_for_post( $id );
		return $dl_count;
	}

	return '<div class="sdm_shortcode_error">Error! The value of "download_info" field does not match any availalbe parameters.</div>';
}

function sdm_handle_show_all_dl_shortcode( $args ) {
	if ( isset( $args['category_id'] ) ) {
		unset( $args['category_id'] );
	}
	if ( isset( $args['category_slug'] ) ) {
		unset( $args['category_slug'] );
	}
	$args['show_all'] = 1;
	return sdm_handle_category_shortcode( $args );
}
