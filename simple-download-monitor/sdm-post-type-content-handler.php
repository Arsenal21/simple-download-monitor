<?php

add_filter('the_content', 'filter_sdm_post_type_content');

function filter_sdm_post_type_content($content)
{
    global $post;
    if($post->post_type == "sdm_downloads"){//Handle the content for sdm_downloads type post
        $download_id = $post->ID;
        $args = array('id'=>$download_id, 'fancy'=>'1');
        $content = sdm_create_download_shortcode($args);
        return $content;
    }

    return $content;
}