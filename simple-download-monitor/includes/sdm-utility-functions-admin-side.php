<?php

function sdm_export_download_logs_to_csv() {
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'sdm_downloads';
    $resultset = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC", OBJECT);
    
    $csv_file_path = WP_SIMPLE_DL_MONITOR_PATH . "sdm-download-logs.csv";
    $fp = fopen($csv_file_path, 'w');

    $header_names = array("Log ID", "Download ID", "Download Title", "File URL", "Date", "IP Address", "Country", "Name");
    fputcsv($fp, $header_names);
    
    foreach ($resultset as $result) {
        if (empty($result->purchase_qty)) {
            $result->purchase_qty = 1;
        }

        $fields = array($result->id, $result->post_id, $result->post_title, $result->file_url, $result->date_time, $result->visitor_ip, $result->visitor_country, $result->visitor_name);
        fputcsv($fp, $fields);
    }

    fclose($fp);    

    $file_url = WP_SIMPLE_DL_MONITOR_URL . '/sdm-download-logs.csv';
    return $file_url;
}
