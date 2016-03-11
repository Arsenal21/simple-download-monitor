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

function sdm_get_item_description_output($id){
    $item_description = get_post_meta($id, 'sdm_description', true);
    $isset_item_description = isset($item_description) && !empty($item_description) ? $item_description : '';
    //$isset_item_description = apply_filters('the_content', $isset_item_description);
    
    $isset_item_description = do_shortcode($isset_item_description);
    $isset_item_description = wptexturize($isset_item_description);
    $isset_item_description = convert_smilies($isset_item_description);
    $isset_item_description = convert_chars($isset_item_description);
    $isset_item_description = wpautop($isset_item_description);
    $isset_item_description = shortcode_unautop($isset_item_description);
    $isset_item_description = prepend_attachment($isset_item_description);
    return $isset_item_description;
}

function sdm_get_password_entry_form($id) {
    $action_url = WP_SIMPLE_DL_MONITOR_SITE_HOME_URL . '/?smd_process_download=1&download_id=' . $id;      
    $data = __('Enter Password to Download:', 'simple-download-monitor');
    $data .= '<form action="'.$action_url.'" method="post" >';
    $data .= '<input type="password" name="pass_text" class="sdm_pass_text" value="" /> ';
    $data .= '<input type="submit" name="sdm_dl_pass_submit" class="pass_sumbit" value="' . __('Submit', 'simple-download-monitor') . '" />';
    $data .= '<input type="hidden" name="download_id" value="' . $id . '" />';
    $data .= '</form>';
    return $data;
}

//Use this function to redirect to a URL
function sdm_redirect_to_url($url, $delay = '0', $exit = '1') {
    $url = apply_filters('sdm_before_redirect_to_url',$url);
    if (empty($url)) {
        echo '<strong>';
        _e('Error! The URL value is empty. Please specify a correct URL value to redirect to!', 'simple-download-monitor');
        echo '</strong>';
        exit;
    }
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . $url . '" />';
    }
    if ($exit == '1') {//exit
        exit;
    }
}

// Helper function to get visitor country (or other info)
function sdm_ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
    $output = NULL;
    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        $ip = $_SERVER["REMOTE_ADDR"];
        if ($deep_detect) {
            if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
    }
    $purpose = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
    $support = array("country", "countrycode", "state", "region", "city", "location", "address");
    $continents = array(
        "AF" => "Africa",
        "AN" => "Antarctica",
        "AS" => "Asia",
        "EU" => "Europe",
        "OC" => "Australia (Oceania)",
        "NA" => "North America",
        "SA" => "South America"
    );
    if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
        $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
        if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
            switch ($purpose) {
                case "location":
                    $output = array(
                        "city" => @$ipdat->geoplugin_city,
                        "state" => @$ipdat->geoplugin_regionName,
                        "country" => @$ipdat->geoplugin_countryName,
                        "country_code" => @$ipdat->geoplugin_countryCode,
                        "continent" => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                        "continent_code" => @$ipdat->geoplugin_continentCode
                    );
                    break;
                case "address":
                    $address = array($ipdat->geoplugin_countryName);
                    if (@strlen($ipdat->geoplugin_regionName) >= 1)
                        $address[] = $ipdat->geoplugin_regionName;
                    if (@strlen($ipdat->geoplugin_city) >= 1)
                        $address[] = $ipdat->geoplugin_city;
                    $output = implode(", ", array_reverse($address));
                    break;
                case "city":
                    $output = @$ipdat->geoplugin_city;
                    break;
                case "state":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "region":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "country":
                    $output = @$ipdat->geoplugin_countryName;
                    break;
                case "countrycode":
                    $output = @$ipdat->geoplugin_countryCode;
                    break;
            }
        }
    }
    return $output;
}

/*
 * Checks if the string exists in the array key value of the provided array. If it doesn't exist, it returns the first key element from the valid values.
 */
function sdm_sanitize_value_by_array($to_check, $valid_values)
{
    $keys = array_keys($valid_values);
    $keys = array_map('strtolower', $keys);
    if (in_array($to_check, $keys)) {
        return $to_check;
    }
    return reset($keys);//Return the first element from the valid values
}