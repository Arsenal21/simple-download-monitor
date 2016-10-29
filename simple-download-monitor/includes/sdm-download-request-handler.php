<?php

//Handles the download request
function handle_sdm_download_via_direct_post() {
    if (isset($_REQUEST['smd_process_download']) && $_REQUEST['smd_process_download'] == '1') {
        global $wpdb;
        $download_id = absint($_REQUEST['download_id']);
        $download_title = get_the_title($download_id);
        $download_link = get_post_meta($download_id, 'sdm_upload', true);

        //Do some validation checks
        if ( !$download_id ) {
            wp_die(__('Error! Incorrect download item id.', 'simple-download-monitor'));
        }
        if (empty($download_link)) {
            wp_die(__('Error! This download item (' . $download_id . ') does not have any download link. Edit this item and specify a downloadable file URL for it.', 'simple-download-monitor'));
        }

        //Check download password (if applicable for this download)
        $post_object = get_post($download_id);// Get post object
        $post_pass = $post_object->post_password;// Get post password
        if(!empty($post_pass)){//This download item has a password. So validate the password.
            $pass_val = $_REQUEST['pass_text'];
            if(empty($pass_val)){//No password was submitted with the downoad request.
                $dl_post_url = get_permalink($download_id);
                $error_msg = __('Error! This download requires a password.', 'simple-download-monitor');
                $error_msg .= '<p>';
                $error_msg .= '<a href="'.$dl_post_url.'">'.__('Click here', 'simple-download-monitor').'</a>';
                $error_msg .= __(' and enter a valid password for this item', 'simple-download-monitor');
                $error_msg .= '</p>';
                wp_die($error_msg);
            }
            if ($post_pass != $pass_val) { 
                //Incorrect password submitted.
                wp_die(__('Error! Incorrect password. This download requires a valid password.', 'simple-download-monitor'));
            } else {
                //Password is valid. Go ahead with the download
            }  
        }
        //End of password check

        $ipaddress = sdm_get_ip_address();
        $date_time = current_time('mysql');
        $visitor_country = $ipaddress ? sdm_ip_info($ipaddress, 'country') : '';

        if (is_user_logged_in()) {  // Get user name (if logged in)
            $current_user = wp_get_current_user();
            $visitor_name = $current_user->user_login;
        } else {
            $visitor_name = __('Not Logged In', 'simple-download-monitor');
        }

        // Get option for global disabling of download logging
        $main_option = get_option('sdm_downloads_options');
        $no_logs = isset($main_option['admin_no_logs']);

        // Get optoin for logging only unique IPs
        $unique_ips = isset($main_option['admin_log_unique']);

        // Get post meta for individual disabling of download logging
        $get_meta = get_post_meta($download_id, 'sdm_item_no_log', true);
        $item_logging_checked = isset($get_meta) && $get_meta === 'on' ? 'on' : 'off';

        $dl_logging_needed = true;

        // Check if download logs have been disabled (globally or per download item)
        if ($no_logs === true || $item_logging_checked === 'on') {
            $dl_logging_needed = false;
        }

        // Check if we are only logging unique ips
        if ($unique_ips === true) {
            $check_ip = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'sdm_downloads WHERE post_id="' . $download_id . '" AND visitor_ip = "' . $ipaddress . '"');

            //This IP is already logged for this download item. No need to log it again.
            if ($check_ip) {
                $dl_logging_needed = false;
            }
        }

        if ($dl_logging_needed) {
            // We need to log this download.
            $table = $wpdb->prefix . 'sdm_downloads';
            $data = array(
                'post_id' => $download_id,
                'post_title' => $download_title,
                'file_url' => $download_link,
                'visitor_ip' => $ipaddress,
                'date_time' => $date_time,
                'visitor_country' => $visitor_country,
                'visitor_name' => $visitor_name
            );

            $data = array_filter($data); //Remove any null values.
            $insert_table = $wpdb->insert($table, $data);

            if ($insert_table) {
                //Download request was logged successfully
            } else {
                //Failed to log the download request
                wp_die(__('Error! Failed to log the download request in the database table', 'simple-download-monitor'));
            }
        }

        // Should the item be dispatched?
        $dispatch = apply_filters('sdm_dispatch_downloads', get_post_meta($download_id, 'sdm_item_dispatch', true));

        // Only local file can be dispatched.
        if ( $dispatch && (stripos($download_link, WP_CONTENT_URL) === 0) ) {
            // Get file path
            $file = path_join(WP_CONTENT_DIR, ltrim(substr($download_link, strlen(WP_CONTENT_URL)), '/'));
            // Try to dispatch file (terminates script execution on success)
            sdm_dispatch_file($file);
        }

        // As a fallback or when dispatching is disabled, redirect to the file
        // (and terminate script execution).
        sdm_redirect_to_url($download_link);
    }
}

/*
 * Use this function to redirect to a URL
 */
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

/**
 * Dispatch file with $filename and terminate script execution, if the file is
 * readable and headers have not been sent yet.
 * @param string $filename
 * @return void
 */
function sdm_dispatch_file($filename) {

    if ( headers_sent() ) {
        trigger_error(__FUNCTION__ . ": Cannot dispatch file $filename, headers already sent.");
        return;
    }

    if ( !is_readable($filename) ) {
        trigger_error(__FUNCTION__ . ": Cannot dispatch file $filename, file is not readable.");
        return;
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream'); // http://stackoverflow.com/a/20509354
    header('Content-Disposition: attachment; filename="'.basename($filename).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filename));

    ob_end_clean();
    readfile($filename);
    exit;
}
