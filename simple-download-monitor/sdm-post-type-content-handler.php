<?php

add_filter('the_content', 'filter_sdm_post_type_content');

function filter_sdm_post_type_content($content) {
    global $post;
    if ($post->post_type == "sdm_downloads") {//Handle the content for sdm_downloads type post
        //$download_id = $post->ID;
        //$args = array('id' => $download_id, 'fancy' => '1');
        //$content = sdm_create_download_shortcode($args);
        
        $id = $post->ID;
        // Check to see if the download link cpt is password protected
        $get_cpt_object = get_post($id);
        $cpt_is_password = !empty($get_cpt_object->post_password) ? 'yes' : 'no';  // yes = download is password protected;
        // Get CPT thumbnail
        $item_download_thumbnail = get_post_meta($id, 'sdm_upload_thumbnail', true);
        $isset_download_thumbnail = isset($item_download_thumbnail) && !empty($item_download_thumbnail) ? '<img class="sdm_post_thumbnail_image" src="' . $item_download_thumbnail . '" />' : '';

        // Get CPT title
        $item_title = get_the_title($id);
        $isset_item_title = isset($item_title) && !empty($item_title) ? $item_title : '';

        // Get CPT description
        $item_description = get_post_meta($id, 'sdm_description', true);
        $isset_item_description = isset($item_description) && !empty($item_description) ? $item_description : '';
        $isset_item_description = do_shortcode($isset_item_description);

        // See if user color option is selected
        $main_opts = get_option('sdm_downloads_options');
        $color_opt = $main_opts['download_button_color'];
        $def_color = isset($color_opt) ? str_replace(' ', '', strtolower($color_opt)) : __('green', 'sdm_lang');

        //Download counter
        //$dl_counter = sdm_create_counter_shortcode(array('id'=>$id));
        //*** Generate the download now button code ***
        $button_text_string = __('Download Now!', 'sdm_lang');

        $homepage = get_bloginfo('url');
        $download_url = $homepage . '/?smd_process_download=1&download_id=' . $id;
        $download_button_code = '<a href="' . $download_url . '" class="sdm_download ' . $def_color . '" title="' . $isset_item_title . '">' . $button_text_string . '</a>';

        if ($cpt_is_password !== 'no') {//This is a password protected download so replace the download now button with password requirement
            $download_button_code = sdm_get_password_entry_form($id);
        }

        $db_count = sdm_get_download_count_for_post($id);
        $string = ($db_count == '1') ? __('Download', 'sdm_lang') : __('Downloads', 'sdm_lang');
        $download_count_string = '<span class="sdm_post_count_number">'.$db_count . '</span><span class="sdm_post_count_string"> ' . $string.'</span>';
                
        //TODO - make this display better with a new design
        $content = '<div class="sdm_post_item">';
        $content .= '<div class="sdm_post_item_top">';
        
        $content .= '<div class="sdm_post_item_top_left">';
        $content .= '<div class="sdm_post_thumbnail">' . $isset_download_thumbnail . '</div>';
        $content .= '</div>';//end .sdm_post_item_top_left
        
        $content .= '<div class="sdm_post_item_top_right">';
        $content .= '<div class="sdm_post_title">' . $isset_item_title . '</div>';
        $content .= '<div class="sdm_post_download_count">' . $download_count_string . '</div>';
        $content .= '<div class="sdm_post_description">' . $isset_item_description . '</div>';
        $content .= '<div class="sdm_post_download_section"><div class="sdm_download_link">' . $download_button_code . '</div></div>';
        //$content .= '<div class="sdm_post_meta_section"></div>';//TODO - Show post meta (category and tags)
        $content .= '</div>';//end .sdm_post_item_top_right
        
        $content .= '</div>'; //end of .sdm_download_item_top
        
        $content .= '<div style="clear:both;"></div>';             
        
        $content .= '</div>';//end .sdm_post_item

        return $content;
    }

    return $content;
}