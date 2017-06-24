<?php

function sdm_search_form_shortcode($attributes) {
    $atts = shortcode_atts(
            array(
        'class' => '', // wrapper class
        'placeholder' => 'Search...', // placeholder for search input
        'description_max_length' => 50, // short description symbols count
            ), $attributes
    );

    // Check if we have a search value posted

    $s_term = isset($_POST['sdm_search_term']) ? stripslashes(sanitize_text_field(esc_html($_POST['sdm_search_term']))) : '';

    if (!empty($s_term)) {
        // we got search term posted
        global $wpdb;
        $querystr = "
    SELECT $wpdb->posts.* 
    FROM $wpdb->posts
    WHERE $wpdb->posts.post_title LIKE '%$s_term%'
    AND $wpdb->posts.post_status = 'publish' 
    AND $wpdb->posts.post_type = 'sdm_downloads'
 ";
        $pageposts = $wpdb->get_results($querystr, OBJECT);
        $s_results = '';
        foreach ($pageposts as $post) {
            $meta = get_post_meta($post->ID);
            $s_results .= '<div class="sdm_search_result_item">';
            $s_results .= '<h4><a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a></h4>';
            $descr = strip_shortcodes($meta['sdm_description'][0]);
            if (strlen($descr) > $atts['description_max_length']) {
                $descr = substr($descr, 0, $atts['description_max_length']) . '[...]';
            }
            $s_results .= '<span>' . $descr . '</span>';
            $s_results .= '</div>';
        }
        if (!empty($s_results)) {
            $s_results = '<h2>Search results for "' . $s_term . '":</h2>'.$s_results;
        } else {
            $s_results ='<h2>Nothing found for "'.$s_term.'".</h2>';
        }
    }

    $out = '';
    $out .= '<form id="sdm_search_form" class="' . (empty($atts['class']) ? '' : ' ' . $atts['class']) . '" method="POST">';
    $out .= '<input type="search" class="search-field" name="sdm_search_term" value="' . $s_term . '" placeholder="' . $atts['placeholder'] . '">';
    $out .= '<input type="submit" class="sdm_search_submit" name="sdm_search_submit" value="Search">';
    $out .= '</form>';
    $out .= isset($s_results) ? $s_results : '';
    
    return $out;
}
