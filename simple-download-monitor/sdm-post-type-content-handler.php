<?php

add_filter('the_content', 'filter_sdm_post_type_content');

function filter_sdm_post_type_content($content) {
    global $post;
    if ($post->post_type == "sdm_downloads") {//Handle the content for sdm_downloads type post
        //$download_id = $post->ID;
        //$args = array('id' => $download_id, 'fancy' => '1');
        //$content = sdm_create_download_shortcode($args);
        $id = $post->ID;

        //Check to see if the download link cpt is password protected
        $get_cpt_object = get_post($id);
        $cpt_is_password = !empty($get_cpt_object->post_password) ? 'yes' : 'no';  // yes = download is password protected;
        //Get item thumbnail
        $item_download_thumbnail = get_post_meta($id, 'sdm_upload_thumbnail', true);
        $isset_download_thumbnail = isset($item_download_thumbnail) && !empty($item_download_thumbnail) ? '<img class="sdm_post_thumbnail_image" src="' . $item_download_thumbnail . '" />' : '';

        //Get item title
        $item_title = get_the_title($id);
        $isset_item_title = isset($item_title) && !empty($item_title) ? $item_title : '';

        //Get item description
        $isset_item_description = sdm_get_item_description_output($id);

        //$isset_item_description = apply_filters('the_content', $isset_item_description);
        //Get item file size
        $item_file_size = get_post_meta($id, 'sdm_item_file_size', true);
        $isset_item_file_size = isset($item_file_size) ? $item_file_size : '';

        //Get item version
        $item_version = get_post_meta($id, 'sdm_item_version', true);
        $isset_item_version = isset($item_version) ? $item_version : '';

        // See if user color option is selected
        $main_opts = get_option('sdm_downloads_options');
        $color_opt = $main_opts['download_button_color'];
        $def_color = isset($color_opt) ? str_replace(' ', '', strtolower($color_opt)) : __('green', 'simple-download-monitor');

        //Download counter
        //$dl_counter = sdm_create_counter_shortcode(array('id'=>$id));
        //*** Generate the download now button code ***
        $button_text_string = __('Download Now!', 'simple-download-monitor');

        $homepage = get_bloginfo('url');
        $download_url = $homepage . '/?smd_process_download=1&download_id=' . $id;
        $download_button_code = '<a href="' . $download_url . '" class="sdm_download ' . $def_color . '" title="' . $isset_item_title . '">' . $button_text_string . '</a>';

        if ($cpt_is_password !== 'no') {//This is a password protected download so replace the download now button with password requirement
            $download_button_code = sdm_get_password_entry_form($id);
        }

        $db_count = sdm_get_download_count_for_post($id);
        $string = ($db_count == '1') ? __('Download', 'simple-download-monitor') : __('Downloads', 'simple-download-monitor');
        $download_count_string = '<span class="sdm_post_count_number">' . $db_count . '</span><span class="sdm_post_count_string"> ' . $string . '</span>';

        //Output the download item details
        $content = '<div class="sdm_post_item">';
        $content .= '<div class="sdm_post_item_top">';

        $content .= '<div class="sdm_post_item_top_left">';
        $content .= '<div class="sdm_post_thumbnail">' . $isset_download_thumbnail . '</div>';
        $content .= '</div>'; //end .sdm_post_item_top_left

        $content .= '<div class="sdm_post_item_top_right">';
        $content .= '<div class="sdm_post_title">' . $isset_item_title . '</div>';

        if (!isset($main_opts['general_hide_donwload_count'])) {//The hide download count is enabled.
            $content .= '<div class="sdm_post_download_count">' . $download_count_string . '</div>';
        }

        $content .= '<div class="sdm_post_description">' . $isset_item_description . '</div>';

        $content .= '<div class="sdm_post_download_section"><div class="sdm_download_link">' . $download_button_code . '</div></div>';

        if (!empty($isset_item_file_size)) {//Show file size info
            $content .= '<div class="sdm_post_download_file_size">';
            $content .= '<span class="sdm_post_download_size_label">' . __('Size: ', 'simple-download-monitor') . '</span>';
            $content .= '<span class="sdm_post_download_size_value">' . $isset_item_file_size . '</span>';
            $content .= '</div>';
        }

        if (!empty($isset_item_version)) {//Show version info
            $content .= '<div class="sdm_post_download_version">';
            $content .= '<span class="sdm_post_download_version_label">' . __('Version: ', 'simple-download-monitor') . '</span>';
            $content .= '<span class="sdm_post_download_version_value">' . $isset_item_version . '</span>';
            $content .= '</div>';
        }

        //$content .= '<div class="sdm_post_meta_section"></div>';//TODO - Show post meta (category and tags)
        $content .= '</div>'; //end .sdm_post_item_top_right

        $content .= '</div>'; //end of .sdm_download_item_top

        $content .= '<div style="clear:both;"></div>';

        $content .= '</div>'; //end .sdm_post_item

        return $content;
    }

    return $content;
}