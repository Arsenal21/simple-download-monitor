<?php

class SDM_Debug {

    public function __construct() {
        
    }

    static function log($msg, $success = true) {
        $opts = get_option('sdm_downloads_options');
        if (isset($opts['enable_debug']) && $opts['enable_debug'] == 'on') {
            file_put_contents(WP_SDM_LOG_FILE, date('Y-m-d H:i:s', time()) . ': [' . ($success === true ? 'SUCCESS' : 'FAIL') . '] ' . $msg . "\r\n", FILE_APPEND);
        }
    }

    static function reset_log() {
        file_put_contents(WP_SDM_LOG_FILE, date('Y-m-d H:i:s', time()) . ': Log has been reset.' . "\r\n");
        file_put_contents(WP_SDM_LOG_FILE, '-------------------------------------------------------'. "\r\n", FILE_APPEND);
    }

}
