<?php

function sdm_show_latest_downloads($args){

    extract(shortcode_atts(array(
        'number' => '5',
        'fancy' => '0',
        'button_text' => '',
        'new_window' => '',
        'orderby' => 'post_date',
	'order' => 'DESC',
        'category_slug' => '',
    ), $args));
    
    $query_args = array(
        'post_type' => 'sdm_downloads',
        'show_posts' => -1,
        'posts_per_page' => $number,
        'orderby' => $orderby,
	'order' => $order,
    );
    
    //Check if the query needs to be for a category
    if (!empty($category_slug)) {
        $field = 'slug';
        $terms = $category_slug;
        
        //Add the category slug parameters for the query args
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'sdm_categories',
                'field' => $field,
                'terms' => $terms
            )
        );
    }
    
    // Query cpt's based on arguments above
    $get_posts = get_posts($query_args);

    // If no cpt's are found
    if (!$get_posts) {
        return '<p style="color: red;">' . __('There are no download items matching this shortcode criteria.', 'simple-download-monitor') . '</p>';
    }
    // Else iterate cpt's
    else {

        $output = '';       
        if ($fancy == '0') {
            include_once(WP_SIMPLE_DL_MONITOR_PATH.'includes/templates/fancy0/sdm-fancy-0.php');
            $output .= sdm_generate_fancy0_latest_downloads_display_output($get_posts, $args);
        }
        if ($fancy == '1') {
            include_once(WP_SIMPLE_DL_MONITOR_PATH.'includes/templates/fancy1/sdm-fancy-1.php');
            $output .= sdm_generate_fancy1_latest_downloads_display_output($get_posts, $args);
        } else if ($fancy == '2') {
            include_once(WP_SIMPLE_DL_MONITOR_PATH.'includes/templates/fancy2/sdm-fancy-2.php');
            $output .= sdm_generate_fancy2_latest_downloads_display_output($get_posts, $args);
        }

        // Return results
        return apply_filters('sdm_latest_downloads_shortcode_output', $output, $args, $get_posts);
    }  // End else iterate cpt's
    
}
