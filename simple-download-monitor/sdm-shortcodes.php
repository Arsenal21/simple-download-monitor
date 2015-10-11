<?php

add_filter('widget_text', 'do_shortcode'); //Enable shortcode filtering in standard text widget

/*
 * * Register and handle Shortcode
 */

function sdm_register_shortcodes() {
    add_shortcode('sdm_download', 'sdm_create_download_shortcode');  // For download shortcode (underscores)
    add_shortcode('sdm-download', 'sdm_create_download_shortcode');  // For download shortcode (for backwards compatibility)
    add_shortcode('sdm_download_counter', 'sdm_create_counter_shortcode');  // For counter shortcode (underscores)
    add_shortcode('sdm-download-counter', 'sdm_create_counter_shortcode');  // For counter shortcode (for backwards compatibility)
    add_shortcode('sdm_latest_downloads', 'sdm_show_latest_downloads'); // For showing X number of latest downloads
    add_shortcode('sdm-latest-downloads', 'sdm_show_latest_downloads');  // For showing X number of latest downloads(for backwards compatibility)
    
    add_shortcode('sdm_download_link', 'sdm_create_simple_download_link');
    
    add_shortcode('sdm_show_dl_from_category', 'sdm_handle_category_shortcode'); //For category shortcode
    add_shortcode('sdm_download_categories', 'sdm_download_categories_shortcode'); // Ajax file tree browser
    
}

// Create Download Shortcode
function sdm_create_download_shortcode($atts) {

    extract(shortcode_atts(array(
        'id' => 'id',
        'fancy' => '0',
        'button_text' => '',
        'new_window' => '',
                    ), $atts));

    if (empty($id)) {
        return '<p style="color: red;">' . __('Error! Please enter an ID value with this shortcode.', 'simple-download-monitor') . '</p>';
    }

    // Check to see if the download link cpt is password protected
    $get_cpt_object = get_post($id);
    $cpt_is_password = !empty($get_cpt_object->post_password) ? 'yes' : 'no';  // yes = download is password protected;
    // Get CPT title
    $item_title = get_the_title($id);
    $isset_item_title = isset($item_title) && !empty($item_title) ? $item_title : '';

    // See if user color option is selected
    $main_opts = get_option('sdm_downloads_options');
    $color_opt = $main_opts['download_button_color'];
    $def_color = isset($color_opt) ? str_replace(' ', '', strtolower($color_opt)) : __('green', 'simple-download-monitor');

    //*** Generate the download now button code ***
    $window_target = '';
    if (!empty($new_window)) {
        $window_target = 'target="_blank"';
    }
    if (empty($button_text)) {//Use the default text for the button
        $button_text_string = __('Download Now!', 'simple-download-monitor');
    } else {//Use the custom text
        $button_text_string = $button_text;
    }
    $homepage = get_bloginfo('url');
    $download_url = $homepage . '/?smd_process_download=1&download_id=' . $id;
    $download_button_code = '<a href="' . $download_url . '" class="sdm_download ' . $def_color . '" title="' . $isset_item_title . '" ' . $window_target . '>' . $button_text_string . '</a>';

    if ($cpt_is_password !== 'no') {//This is a password protected download so replace the download now button with password requirement
        $download_button_code = sdm_get_password_entry_form($id);
    }
    //End of download now button code generation

    $output = '';
    if ($fancy == '0') {
        $output = '<div class="sdm_download_link">' . $download_button_code . '</div>';
    } else if ($fancy == '1') {
        include_once('includes/templates/fancy1/sdm-fancy-1.php');
        $output .= sdm_generate_fancy1_display_output($atts);
        $output .= '<div class="sdm_clear_float"></div>';
    } else if ($fancy == '2') {
        include_once('includes/templates/fancy2/sdm-fancy-2.php');
        $output .= '<link type="text/css" rel="stylesheet" href="' . WP_SIMPLE_DL_MONITOR_URL . '/includes/templates/fancy2/sdm-fancy-2-styles.css?ver=' . WP_SIMPLE_DL_MONITOR_VERSION . '" />';
        $output .= sdm_generate_fancy2_display_output($atts);
        $output .= '<div class="sdm_clear_float"></div>';
    } else {//Default output is the standard download now button (fancy 0)
        $output = '<div class="sdm_download_link">' . $download_button_code . '</div>';
    }

    return apply_filters('sdm_download_shortcode_output', $output, $atts);
}

function sdm_create_simple_download_link($atts){
    extract(shortcode_atts(array(
        'id' => 'id',
    ), $atts));

    if (empty($id)) {
        return '<p style="color: red;">' . __('Error! Please enter an ID value with this shortcode.', 'simple-download-monitor') . '</p>';
    }
    
    $download_url = WP_SIMPLE_DL_MONITOR_SITE_HOME_URL . '/?smd_process_download=1&download_id=' . $id;
    return $download_url;
}

// Create Counter Shortcode
function sdm_create_counter_shortcode($atts) {

    extract(shortcode_atts(array(
        'id' => ''
                    ), $atts));

    if (empty($id)) {
        return '<p style="color: red;">' . __('Error! Please enter an ID value with this shortcode.', 'simple-download-monitor') . '</p>';
    }

    $db_count = sdm_get_download_count_for_post($id);

    // Set string for singular/plural results
    $string = ($db_count == '1') ? __('Download', 'simple-download-monitor') : __('Downloads', 'simple-download-monitor');

    $output = '<div class="sdm_download_count"><span class="sdm_count_number">' . $db_count . '</span><span class="sdm_count_string"> ' . $string . '</span></div>';
    // Return result
    return apply_filters('sdm_download_count_output', $output, $atts);
}

// Create Category Shortcode
function sdm_handle_category_shortcode($args) {

    extract(shortcode_atts(array(
        'category_slug' => '',
        'category_id' => '',
        'fancy' => '0',
        'button_text' => '',
        'new_window' => '',
        'orderby' => 'post_date',
	'order' => 'DESC',
        'pagination' => '',
    ), $args));

    // Define vars
    $field = '';
    $terms = '';

    // If category slug and category id are empty.. return error
    if (empty($category_slug) && empty($category_id)) {
        return '<p style="color: red;">' . __('Error! You must enter a category slug OR a category id with this shortcode. Refer to the documentation for usage instructions.', 'simple-download-monitor') . '</p>';
    }
    // Else if both category slug AND category id are defined... return error
    else if (!empty($category_slug) && !empty($category_id)) {
        return '<p style="color: red;">' . __('Error! Please enter a category slug OR id; not both.', 'simple-download-monitor') . '</p>';
    }
    // Else setup query arguments for category_slug
    else if (!empty($category_slug) && empty($category_id)) {

        $field = 'slug';
        $terms = $category_slug;
    }
    // Else setup query arguments for category_id
    else if (!empty($category_id) && empty($category_slug)) {

        $field = 'term_id';
        $terms = $category_id;
    }

    // For pagination
    $paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;
    if(isset($args['pagination'])){
        if(!is_numeric($args['pagination'])){
            return '<p style="color: red;">' . __('Error! You must enter a numeric number for the "pagination" parameter of the shortcode. Refer to the usage documentation.', 'simple-download-monitor') . '</p>';
        }
        $posts_per_page = $args['pagination'];
    } else {
        $posts_per_page = 9999;
    }
    
        
    // Query cpt's based on arguments above
    $get_posts = get_posts(array(
        'post_type' => 'sdm_downloads',
        'show_posts' => -1,
        'posts_per_page' => $posts_per_page,
        'tax_query' => array(
            array(
                'taxonomy' => 'sdm_categories',
                'field' => $field,
                'terms' => $terms
            )
        ),
        'orderby' => $orderby,
	'order' => $order,
        'paged' => $paged,
    ));

    // If no cpt's are found
    if (!$get_posts) {
        return '<p style="color: red;">' . __('There are no download items matching this category criteria.', 'simple-download-monitor') . '</p>';
    }
    // Else iterate cpt's
    else {

        $output = '';

        // Setup download location
        $homepage = get_bloginfo('url');

        // See if user color option is selected
        $main_opts = get_option('sdm_downloads_options');
        $color_opt = $main_opts['download_button_color'];
        $def_color = isset($color_opt) ? str_replace(' ', '', strtolower($color_opt)) : __('green', 'simple-download-monitor');

        $window_target = '';
        if (!empty($new_window)) {
            $window_target = 'target="_blank"';
        }

        if (empty($button_text)) {//Use the default text for the button
            $button_text_string = __('Download Now!', 'simple-download-monitor');
        } else {//Use the custom text
            $button_text_string = $button_text;
        }

        // Iterate cpt's
        foreach ($get_posts as $item) {

            // Set download location
            $id = $item->ID;  // get each cpt ID
            $download_url = $homepage . '/?smd_process_download=1&download_id=' . $id;

            // Get each cpt title
            $item_title = get_the_title($id);
            $isset_item_title = isset($item_title) && !empty($item_title) ? $item_title : '';

            // Get CPT thumbnail (for fancy option)
            $item_download_thumbnail = get_post_meta($id, 'sdm_upload_thumbnail', true);
            $isset_download_thumbnail = isset($item_download_thumbnail) && !empty($item_download_thumbnail) ? '<img class="sdm_download_thumbnail_image" src="' . $item_download_thumbnail . '" />' : '';

            // Get item description (for fancy option)
            $isset_item_description = sdm_get_item_description_output($id);

            // Setup download button code
            $download_button_code = '<a href="' . $download_url . '" class="sdm_download ' . $def_color . '" title="' . $isset_item_title . '" ' . $window_target . '>' . $button_text_string . '</a>';

            // Generate download buttons            
            if ($fancy == '0') {
                $output .= '<div class="sdm_download_link">' . $download_button_code . '</div><br />';
            }
        }  // End foreach
        //Fancy 1 and onwards handles the loop inside the template function
        if ($fancy == '1') {
            include_once('includes/templates/fancy1/sdm-fancy-1.php');
            $output .= sdm_generate_fancy1_category_display_output($get_posts, $args);
        } else if ($fancy == '2') {
            include_once('includes/templates/fancy2/sdm-fancy-2.php');
            $output .= sdm_generate_fancy2_category_display_output($get_posts, $args);
        }

        // Pagination related
        if(isset($args['pagination'])){
            $posts_per_page = $args['pagination'];
            $count_sdm_posts = wp_count_posts('sdm_downloads');
            $published_sdm_posts = $count_sdm_posts->publish;            
            $total_pages = ceil($published_sdm_posts / $posts_per_page);
            
            $big = 999999999; // Need an unlikely integer
            $pagination = paginate_links( array(
			'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format'       => '',
			'add_args'     => '',
			'current'      => max( 1, get_query_var( 'paged' ) ),
			'total'        => $total_pages,
			'prev_text'    => '&larr;',
			'next_text'    => '&rarr;',
		) );
            $output .= '<div class="sdm_pagination">'.$pagination.'</div>';
        }
    
        // Return results
        return apply_filters('sdm_category_download_items_shortcode_output', $output, $args, $get_posts);
    }  // End else iterate cpt's
}

// Create category tree shortcode
function sdm_download_categories_shortcode() {

    function custom_taxonomy_walker($taxonomy, $parent = 0) {

        // Get terms (check if has parent)
        $terms = get_terms($taxonomy, array('parent' => $parent, 'hide_empty' => false));

        // If there are terms, start displaying
        if (count($terms) > 0) {
            // Displaying as a list
            $out = '<ul>';
            // Cycle though the terms
            foreach ($terms as $term) {
                // Secret sauce. Function calls itself to display child elements, if any
                $out .= '<li class="sdm_cat" id="' . $term->slug . '"><span id="' . $term->term_id . '" class="sdm_cat_title" style="cursor:pointer;">' . $term->name . '</span>';
                $out .= '<p class="sdm_placeholder" style="margin-bottom:0;"></p>' . custom_taxonomy_walker($taxonomy, $term->term_id);
                $out .= '</li>';
            }
            $out .= '</ul>';
            return $out;
        }
        return;
    }

    return '<div class="sdm_object_tree">' . custom_taxonomy_walker('sdm_categories') . '</div>';
}