<?php

function sdm_get_download_count_for_post($id){
    // Get number of downloads by counting db columns matching postID
    global $wpdb;
    $table = $wpdb->prefix . 'sdm_downloads';
    $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $table . ' WHERE post_id=%s', $id));
    // Count database rows
    $db_count = $wpdb->num_rows;

    // Check post meta to see if we need to offset the count before displaying to viewers
    $get_offset = get_post_meta($id, 'sdm_count_offset', true);

    if ($get_offset && $get_offset != '') {

        $db_count = $db_count + $get_offset;
    }
    
    return $db_count;
}