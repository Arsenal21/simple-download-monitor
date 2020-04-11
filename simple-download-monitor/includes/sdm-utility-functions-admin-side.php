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

        $fields = array($result->id, $result->post_id, $result->post_title, $result->file_url, $result->date_time, $result->visitor_ip, $result->visitor_country, $result->visitor_name, $result->user_agent);
        fputcsv($fp, $fields);
    }

    fclose($fp);

    $file_url = WP_SIMPLE_DL_MONITOR_URL . '/sdm-download-logs.csv';
    return $file_url;
}

function sdm_get_downloads_by_date($start_date = '', $end_date = '', $returnStr = true) {
    global $wpdb;

    $q = $wpdb->prepare("SELECT COUNT(id) as cnt, DATE_FORMAT(`date_time`,'%%Y-%%m-%%d') as day
            FROM " . $wpdb->prefix . "sdm_downloads
            WHERE DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')>=%s
            AND DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')<=%s
            GROUP BY DAY(date_time) ORDER BY date_time", $start_date, $end_date);

    $res = $wpdb->get_results($q, ARRAY_A);
    if ($returnStr) {
        $downloads_by_date_str = '';
        foreach ($res as $item) {
            $downloads_by_date_str .= '["' . $item['day'] . '", ' . $item['cnt'] . '],';
        }
        return $downloads_by_date_str;
    } else {
        return $res;
    }
}

function sdm_get_downloads_by_country($start_date = '', $end_date = '', $returnStr = true) {
    global $wpdb;

    $q = $wpdb->prepare("SELECT COUNT(id) as cnt, visitor_country as country
            FROM " . $wpdb->prefix . "sdm_downloads
            WHERE DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')>=%s
            AND DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')<=%s
            GROUP BY visitor_country", $start_date, $end_date);
    $res = $wpdb->get_results($q, ARRAY_A);

    if ($returnStr) {
        $downloads_by_country_str = "['Country', 'Downloads'],";
        foreach ($res as $item) {
            $downloads_by_country_str .= '["' . $item['country'] . '", ' . $item['cnt'] . '],';
        }
        return $downloads_by_country_str;
    } else {
        return $res;
    }
}
