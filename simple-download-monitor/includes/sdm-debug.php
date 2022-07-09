<?php

class SDM_Debug {

	public function __construct() {

	}

	static function log( $msg, $success = true ) {
		$opts = get_option( 'sdm_downloads_options' );
		if ( isset( $opts['enable_debug'] ) && $opts['enable_debug'] == 'on' ) {
			file_put_contents( WP_SDM_LOG_FILE, date( 'Y-m-d H:i:s', time() ) . ': [' . ( $success === true ? 'SUCCESS' : 'FAIL' ) . '] ' . $msg . "\r\n", FILE_APPEND );
		}
	}

	static function log_array_data( $array_to_write, $success = true, $addon_name = '') {
		$opts = get_option( 'sdm_downloads_options' );
                if ( ! isset( $opts['enable_debug'] ) || $opts['enable_debug'] != 'on' ) {
                    return true;
                }
                
		$log_file_path = WP_SDM_LOG_FILE;
                
		$output = '';
		//Timestamp it
		$output .= '[' . gmdate( 'm/d/Y g:i:s A' ) . '] - ';

		//Add the addon's name (if applicable)
		if ( ! empty( $addon_name ) ) {
			$output .= '[' . $addon_name . '] ';
		}

		//Flag failure (if applicable)
		if ( ! $success ) {
			$output .= 'FAILURE: ';
		}

		//Put the array content into a string
		ob_start();
		print_r( $array_to_write );
		$var = ob_get_contents();
		ob_end_clean();
		$output .= $var;

		if ( ! file_put_contents( $log_file_path, $output . "\r\n", FILE_APPEND ) ) {
			return false;
		}

		return true;
	}
        
	static function reset_log() {
		file_put_contents( WP_SDM_LOG_FILE, date( 'Y-m-d H:i:s', time() ) . ': Log has been reset.' . "\r\n" );
		file_put_contents( WP_SDM_LOG_FILE, '-------------------------------------------------------' . "\r\n", FILE_APPEND );
	}

}
