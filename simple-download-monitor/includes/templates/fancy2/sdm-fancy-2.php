<?php

//TODO write a function for doing grid display then the conditional function calls this grid display function

function sdm_generate_fancy2_latest_downloads_display_output($get_posts, $args) {

    $output = "";
    $output .= '<link type="text/css" rel="stylesheet" href="' . WP_SIMPLE_DL_MONITOR_URL . '/includes/templates/fancy2/sdm-fancy-2-styles.css?ver=' . WP_SIMPLE_DL_MONITOR_VERSION . '" />';

    $count = 1;
    //$output .= '<ul class="sdm_fancy2_category_items">';
    foreach ($get_posts as $item) {
        $id = $item->ID;  //Get the post ID
        $button_text = isset($args['button_text'])? $args['button_text'] : '';
        $new_window = isset($args['new_window'])? $args['new_window'] : '';
        
        //Create a args array
        $args = array(
            'id' => $id,
            'fancy' => '2',
            'button_text' => $button_text,
            'new_window' => $new_window,
            'css_class' => 'sdm_fancy2_grid',
        );
        $output .= sdm_generate_fancy2_display_output($args);

        if ($count % 3 == 0) {//Clear after every 3 items in the grid
            $output .= '<div class="sdm_clear_float"></div>';
        }
        $count++;
    }
    //$output .= '</ul>';
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

function sdm_generate_fancy2_category_display_output($get_posts, $args) {

    $output = "";
    $output .= '<link type="text/css" rel="stylesheet" href="' . WP_SIMPLE_DL_MONITOR_URL . '/includes/templates/fancy2/sdm-fancy-2-styles.css?ver=' . WP_SIMPLE_DL_MONITOR_VERSION . '" />';

    $count = 1;
    //$output .= '<ul class="sdm_fancy2_category_items">';
    foreach ($get_posts as $item) {
        $id = $item->ID;  //Get the post ID
        $button_text = isset($args['button_text'])? $args['button_text'] : '';
        $new_window = isset($args['new_window'])? $args['new_window'] : '';
        
        //Create a args array
        $args = array(
            'id' => $id,
            'fancy' => '2',
            'button_text' => $button_text,
            'new_window' => $new_window,
            'css_class' => 'sdm_fancy2_grid',
        );
        $output .= sdm_generate_fancy2_display_output($args);

        if ($count % 3 == 0) {//Clear after every 3 items in the grid
            $output .= '<div class="sdm_clear_float"></div>';
        }
        $count++;
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

function sdm_generate_fancy2_display_output($args) {

    //Get the download ID
    $id = $args['id'];
    if (!is_numeric($id)) {
        return '<div class="sdm_error_msg">Error! The shortcode is missing the ID parameter. Please refer to the documentation to learn the shortcode usage.</div>';
    }

    //See if new window parameter is seet
    $window_target = '';
    if (isset($args['new_window']) && $args['new_window'] == '1') {
        $window_target = 'target="_blank"';
    }

    //Get the download button text
    $button_text = isset($args['button_text']) ? $args['button_text'] : '';
    if (empty($button_text)) {//Use the default text for the button
        $button_text_string = __('Download Now!', 'simple-download-monitor');
    } else {//Use the custom text
        $button_text_string = $button_text;
    }
    $homepage = get_bloginfo('url');
    $download_url = $homepage . '/?smd_process_download=1&download_id=' . $id;
    $download_button_code = '<a href="' . $download_url . '" class="sdm_fancy2_download" ' . $window_target . '>' . $button_text_string . '</a>';

    // Check to see if the download link cpt is password protected
    $get_cpt_object = get_post($id);
    $cpt_is_password = !empty($get_cpt_object->post_password) ? 'yes' : 'no';  // yes = download is password protected;    
    if ($cpt_is_password !== 'no') {//This is a password protected download so replace the download now button with password requirement
        $download_button_code = sdm_get_password_entry_form($id);
    }

    // Get item permalink
    $permalink = get_permalink($id);

    // Get item thumbnail
    $item_download_thumbnail = get_post_meta($id, 'sdm_upload_thumbnail', true);
    $isset_download_thumbnail = isset($item_download_thumbnail) && !empty($item_download_thumbnail) ? '<img class="sdm_fancy2_thumb_image" src="' . $item_download_thumbnail . '" />' : '';

    // Get item title
    $item_title = get_the_title($id);
    $isset_item_title = isset($item_title) && !empty($item_title) ? $item_title : '';

    // Get item description
    $isset_item_description = sdm_get_item_description_output($id);

    $css_class = isset($args['css_class']) ? $args['css_class'] : '';
    $output = '';
    $output .= '<div class="sdm_fancy2_item ' . $css_class . '">';
    $output .= '<div class="sdm_fancy2_wrapper">';

    $output .= '<div class="sdm_fancy2_download_item_top">';
    $output .= '<div class="sdm_fancy2_download_thumbnail">' . $isset_download_thumbnail . '</div>';
    $output .= '</div>'; //End of .sdm_download_item_top

    $output .= '<div class="sdm_fancy2_download_title">' . $isset_item_title . '</div>';
    $output .= '<div class="sdm_fancy2_download_link">' . $download_button_code . '</div>';

    $output .= '</div>'; //end .sdm_fancy2_item
    $output .= '</div>'; //end .sdm_fancy2_wrapper

    return $output;
}