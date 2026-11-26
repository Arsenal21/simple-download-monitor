<?php

function sdm_search_form_shortcode( $args ) {
	$atts = shortcode_atts(
		array(
			'fancy'                  => '',
			'class'                  => '', // wrapper class
			'placeholder'            => 'Search...', // placeholder for search input
			'description_max_length' => 50, // short description symbols count
			'search_button_text'     => __('Search', 'simple-download-monitor'),
			'button_text'            => '',
		),
		$args
	);

	global $wpdb;

	// Check if we have a search value posted.
	// The search term is used as typed (it was HTML-encoded, so "Bob's" was searched as "Bob&#039;s"), in a prepared query.
	// It is escaped when it is printed.
	$s_term = isset( $_POST['sdm_search_term'] ) ? sanitize_text_field( wp_unslash( $_POST['sdm_search_term'] ) ) : '';

	if ( ! empty( $s_term ) ) {
		// we got search term posted
		$keywords_searched = array();
		$posts_collection  = array();
		$parts             = explode( ' ', $s_term );
		foreach ( $parts as $keyword ) {
			if ( strlen( $keyword ) < 3 ) {
				//Ignore keywords less than 3 characters long
				continue;
			}

			$keywords_searched[] = $keyword;

			//"%" and "_" in the keyword are searched for as characters, not as wildcards.
			$keyword_like = '%' . $wpdb->esc_like( $keyword ) . '%';
			$querystr     = $wpdb->prepare(
				"
	    SELECT $wpdb->posts.*, $wpdb->postmeta.meta_value as description
	    FROM $wpdb->posts, $wpdb->postmeta
	    WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
	    AND $wpdb->posts.post_status = 'publish'
	    AND $wpdb->posts.post_type = 'sdm_downloads'
	    AND ($wpdb->posts.post_title LIKE %s
	    OR ($wpdb->postmeta.meta_key='sdm_description' AND $wpdb->postmeta.meta_value LIKE %s) )
	    GROUP BY $wpdb->posts.ID
	    ",
				$keyword_like,
				$keyword_like
			);
			$query_result = $wpdb->get_results( $querystr, OBJECT );

			//Merge the results of this keyword with the collection array. Also remove any duplicate.
			$posts_collection = array_merge( $posts_collection, $query_result );
			//$posts_collection = array_unique(array_merge($posts_collection, $query_result), SORT_REGULAR);
		}

		//Lets get rid of any duplicates
		$unique_posts_collection_id = array();
		foreach ( $posts_collection as $key => $post ) {
			if ( in_array( $post->ID, $unique_posts_collection_id ) ) {
				//This is a duplicate.
				unset( $posts_collection[ $key ] );//Delete this entry from the collection.
			} else {
				//This post ID is not in the collection yet. Add it.
				$unique_posts_collection_id[] = $post->ID;
			}
		}

		//Create the result entries output using a template.
		$s_results = sdm_generate_search_result_using_template( $posts_collection, $atts );

		//Show the search result
		if ( ! empty( $s_results ) ) {
			$result_output  = '<h2 class="sdm_search_result_heading">' . __( 'Showing search results for ', 'simple-download-monitor' ) . '"' . esc_html( $s_term ) . '"</h2>';
			$result_output .= '<div class="sdm_search_result_number_of_items">' . __( 'Number of items found: ', 'simple-download-monitor' ) . count( $posts_collection ) . '</div>';
			$result_output .= '<div class="sdm_search_result_keywords">' . __( 'Keywords searched: ', 'simple-download-monitor' ) . esc_html( implode( ', ', $keywords_searched ) ) . '</div>';
			$result_output .= '<div class="sdm_search_result_listing">' . $s_results . '</div>';
		} else {
			$result_output = '<h2 class="sdm_search_result_heading">' . __( 'Nothing found for ', 'simple-download-monitor' ) . '"' . esc_html( $s_term ) . '".</h2>';
		}
	}
	$out  = '';
	$out .= '<form id="sdm_search_form" class="' . sanitize_html_class( $atts['class'], '' ) . '" method="POST">';
	$out .= '<input type="search" class="search-field" name="sdm_search_term" value="' . esc_attr( $s_term ) . '" placeholder="' . sdm_sanitize_text( $atts['placeholder'] ) . '">';

	$search_button_text = isset($atts['search_button_text']) ? sanitize_text_field($atts['search_button_text']) : '';

	$out .= '<input type="submit" class="sdm_search_submit" name="sdm_search_submit" value="'.esc_attr($search_button_text).'">';
	$out .= '</form>';
	$out .= isset( $result_output ) ? $result_output : '';

	return $out;
}

function sdm_generate_search_result_using_template( $posts_collection, $args = array() ) {
	$s_results = '';

	if ( isset( $args['fancy'] ) && ! empty( $args['fancy'] ) ) {
		if ( $args['fancy'] == '1' ) {
			include_once WP_SIMPLE_DL_MONITOR_PATH . 'includes/templates-related/fancy1/sdm-fancy-1.php';
			$s_results .= sdm_generate_fancy1_category_display_output( $posts_collection, $args );
		} elseif ( $args['fancy'] == '2' ) {
			include_once WP_SIMPLE_DL_MONITOR_PATH . 'includes/templates-related/fancy2/sdm-fancy-2.php';
			$s_results .= sdm_generate_fancy2_category_display_output( $posts_collection, $args );
		} elseif ( $args['fancy'] == '3' ) {
			include_once WP_SIMPLE_DL_MONITOR_PATH . 'includes/templates-related/fancy3/sdm-fancy-3.php';
			$s_results .= sdm_generate_fancy3_category_display_output( $posts_collection, $args );
		}
	} else {
		//No fancy template is used. Show the search result using the standard search display
		foreach ( $posts_collection as $post ) {
			$meta       = get_post_meta( $post->ID );
			$s_results .= '<div class="sdm_search_result_item">';
			$s_results .= '<h4><a href="' . esc_url( get_permalink( $post->ID ) ) . '">' . esc_html( $post->post_title ) . '</a></h4>';
			//An item without a description was a PHP warning (undefined index).
			$descr      = isset( $meta['sdm_description'][0] ) ? wp_strip_all_tags( strip_shortcodes( $meta['sdm_description'][0] ) ) : '';
			if ( strlen( $descr ) > $args['description_max_length'] ) {
				$descr = substr( $descr, 0, $args['description_max_length'] ) . '[...]';
			}
			$s_results .= '<span>' . esc_html( $descr ) . '</span>';
			$s_results .= '</div>';
		}
	}

	return $s_results;
}
