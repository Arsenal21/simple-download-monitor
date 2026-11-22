<?php

class SDM_Debug {

	const LOG_FILE_PREFIX_OPTION = 'sdm_debug_log_file_prefix';
	const LOG_FILE_BASE_NAME     = 'sdm-debug-log.txt';

	public function __construct() {

	}

	/**
	 * Returns the directory where the debug log file is stored (with trailing slash).
	 */
	static function get_log_dir() {
		return WP_SIMPLE_DL_MONITOR_PATH . 'logs/';
	}

	/**
	 * Returns the full path of the debug log file.
	 *
	 * The file name has a random prefix that is unique to each site, so the log file URL can't be guessed.
	 */
	static function get_log_file_path() {
		return self::get_log_dir() . self::get_log_file_prefix() . '-' . self::LOG_FILE_BASE_NAME;
	}

	/**
	 * Returns the per-site random prefix for the log file name. Generates and saves it on first use.
	 */
	static function get_log_file_prefix() {
		$prefix = get_option( self::LOG_FILE_PREFIX_OPTION );
		if ( is_string( $prefix ) && preg_match( '/^[a-z0-9]{16,64}$/', $prefix ) ) {
			return $prefix;
		}

		// Note: this can run before pluggable functions (like wp_generate_password) are loaded, so we don't use them here.
		try {
			$prefix = bin2hex( random_bytes( 12 ) );
		} catch ( Exception $e ) {
			$prefix = md5( uniqid( (string) mt_rand(), true ) );
		}

		update_option( self::LOG_FILE_PREFIX_OPTION, $prefix );
		return $prefix;
	}

	/**
	 * Makes sure the log directory exists and is protected from direct web access.
	 *
	 * The plugin folder gets replaced on every plugin update, so this is checked at write time (not only on activation).
	 */
	static function ensure_log_dir() {
		$log_dir = self::get_log_dir();

		if ( ! is_dir( $log_dir ) && ! wp_mkdir_p( $log_dir ) ) {
			return false;
		}

		$htaccess_file = $log_dir . '.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content  = "# Deny direct access to the debug log files.\n";
			$htaccess_content .= "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n";
			$htaccess_content .= "<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n";
			file_put_contents( $htaccess_file, $htaccess_content );
		}

		$index_file = $log_dir . 'index.html';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, '' );
		}

		return true;
	}

	/**
	 * Deletes the debug log file left in the plugin's root folder by older versions of the plugin (it was web-accessible).
	 */
	static function delete_legacy_log_file() {
		$legacy_log_file = WP_SIMPLE_DL_MONITOR_PATH . self::LOG_FILE_BASE_NAME;
		if ( file_exists( $legacy_log_file ) ) {
			@unlink( $legacy_log_file );
		}
	}

	/**
	 * Deletes the debug log file.
	 */
	static function delete_log_file() {
		$log_file = self::get_log_file_path();
		if ( file_exists( $log_file ) ) {
			@unlink( $log_file );
		}
	}

	/**
	 * Appends content to the log file.
	 */
	static function write_to_log_file( $content, $append = true ) {
		if ( ! self::ensure_log_dir() ) {
			return false;
		}
		return file_put_contents( self::get_log_file_path(), $content, $append ? FILE_APPEND : 0 );
	}

	static function log( $msg, $success = true ) {
		$opts = get_option( 'sdm_downloads_options' );
		if ( isset( $opts['enable_debug'] ) && $opts['enable_debug'] == 'on' ) {
			self::write_to_log_file( date( 'Y-m-d H:i:s', time() ) . ': [' . ( $success === true ? 'SUCCESS' : 'FAIL' ) . '] ' . $msg . "\r\n" );
		}
	}

	static function log_array_data( $array_to_write, $success = true, $addon_name = '') {
		$opts = get_option( 'sdm_downloads_options' );
                if ( ! isset( $opts['enable_debug'] ) || $opts['enable_debug'] != 'on' ) {
                    return true;
                }

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

		if ( ! self::write_to_log_file( $output . "\r\n" ) ) {
			return false;
		}

		return true;
	}

	static function reset_log() {
		$content  = date( 'Y-m-d H:i:s', time() ) . ': Log has been reset.' . "\r\n";
		$content .= '-------------------------------------------------------' . "\r\n";
		self::write_to_log_file( $content, false );
	}

}
