<?php

function sdm_generate_fancy0_latest_downloads_display_output($get_posts, $args) {

    $output = "";
    isset($args['button_text']) ? $button_text = $args['button_text'] : $button_text = '';
    isset($args['new_window']) ? $new_window = $args['new_window'] : $new_window = '';    
    foreach ($get_posts as $item) {
        $id = $item->ID;  //Get the post ID
        //Create a args array
        $args = array(
            'id' => $id,
            'fancy' => '0',
            'button_text' => $button_text,
            'new_window' => $new_window,
        );
        $output .= sdm_generate_fancy0_display_output($args);
    }
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

/*** TODO - Use this function in the category shortcode handler function ***/
//function sdm_generate_fancy0_category_display_output($get_posts, $args) {
//
//    $output = "";
//
//    //TODO - when the CSS file is moved to the fancy1 folder, change it here
//    //$output .= '<link type="text/css" rel="stylesheet" href="' . WP_SIMPLE_DL_MONITOR_URL . '/includes/templates/fancy1/sdm-fancy-1-styles.css?ver=' . WP_SIMPLE_DL_MONITOR_VERSION . '" />';
//    
//    foreach ($get_posts as $item) {
//        $id = $item->ID;  //Get the post ID
//        //Create a args array
//        $args = array(
//            'id' => $id,
//            'fancy' => '1',
//            'button_text' => $args['button_text'],
//            'new_window' => $args['new_window'],
//        );
//        $output .= sdm_generate_fancy0_display_output($args);
//    }
//    $output .= '<div class="sdm_clear_float"></div>';
//    return $output;
//}

/*
 * Generates the output of a single item using fancy2 sytle 
 * $args array can have the following parameters
 * id, fancy, button_text, new_window
 */

function sdm_generate_fancy0_display_output($args) {

    //Get the download ID
    $id = $args['id'];
    if (!is_numeric($id)) {
        return '<div class="sdm_error_msg">Error! The shortcode is missing the ID parameter. Please refer to the documentation to learn the shortcode usage.</div>';
    }

    // See if user color option is selected
    $main_opts = get_option('sdm_downloads_options');
    $color_opt = $main_opts['download_button_color'];
    $def_color = isset($color_opt) ? str_replace(' ', '', strtolower($color_opt)) : __('green', 'simple-download-monitor');

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

   // Get CPT title
    $item_title = get_the_title($id);
    $isset_item_title = isset($item_title) && !empty($item_title) ? $item_title : '';

    // Get download button
    $homepage = WP_SIMPLE_DL_MONITOR_SITE_HOME_URL;
    $download_url = $homepage . '/?smd_process_download=1&download_id=' . $id;
    $download_button_code = '<a href="' . $download_url . '" class="sdm_download ' . $def_color . '" title="' . $isset_item_title . '" ' . $window_target . '>' . $button_text_string . '</a>';

    // Check to see if the download link cpt is password protected
    $get_cpt_object = get_post($id);
    $cpt_is_password = !empty($get_cpt_object->post_password) ? 'yes' : 'no';  // yes = download is password protected;    
    if ($cpt_is_password !== 'no') {//This is a password protected download so replace the download now button with password requirement
        $download_button_code = sdm_get_password_entry_form($id);
    }

    $output = "";
    $output .= '<div class="sdm_download_button_box_default"><div class="sdm_download_link">' . $download_button_code . '</div></div>';

    return $output;
}