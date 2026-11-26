<?php
/**
 * The debug log (SDM_Debug).
 *
 * Security report of November 2026 (fixed in 4.1.5): the debug log was saved as sdm-debug-log.txt in the plugin's main folder,
 * where anyone could download it. It is now saved in the plugin's "logs" folder (protected with .htaccess and an empty index.html),
 * and the file name has a random prefix that is unique to each site.
 */
class Test_Debug_Log extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		SDM_Debug::delete_log_file();
	}

	public function tear_down() {
		SDM_Debug::delete_log_file();
		// Put back the files of the logs folder, in case a test removed them.
		SDM_Debug::ensure_log_dir();
		parent::tear_down();
	}

	public function test_the_log_file_is_in_the_logs_folder_with_a_random_prefix() {
		$path = SDM_Debug::get_log_file_path();

		$this->assertSame( WP_SIMPLE_DL_MONITOR_PATH . 'logs/', SDM_Debug::get_log_dir() );
		$this->assertSame( SDM_Debug::get_log_dir(), dirname( $path ) . '/' );
		$this->assertMatchesRegularExpression( '/^[a-z0-9]{16,64}-sdm-debug-log\.txt$/', basename( $path ) );
	}

	public function test_the_prefix_is_saved_and_stays_the_same() {
		$prefix = SDM_Debug::get_log_file_prefix();

		$this->assertSame( $prefix, get_option( SDM_Debug::LOG_FILE_PREFIX_OPTION ) );
		$this->assertSame( $prefix, SDM_Debug::get_log_file_prefix() );
		$this->assertSame( SDM_Debug::get_log_file_path(), SDM_Debug::get_log_file_path() );
	}

	/**
	 * WP_SDM_LOG_FILE is kept for code that still uses it (it is defined when the plugin loads).
	 */
	public function test_the_old_constant_points_to_the_new_log_file() {
		$this->assertSame( SDM_Debug::get_log_file_path(), WP_SDM_LOG_FILE );
	}

	public function test_a_new_prefix_is_made_when_none_is_saved() {
		delete_option( SDM_Debug::LOG_FILE_PREFIX_OPTION );

		$prefix = SDM_Debug::get_log_file_prefix();

		$this->assertMatchesRegularExpression( '/^[a-z0-9]{16,64}$/', $prefix );
		$this->assertSame( $prefix, get_option( SDM_Debug::LOG_FILE_PREFIX_OPTION ) );
	}

	public function test_a_new_prefix_is_made_when_the_saved_one_is_not_valid() {
		update_option( SDM_Debug::LOG_FILE_PREFIX_OPTION, '../short' );

		$prefix = SDM_Debug::get_log_file_prefix();

		$this->assertNotSame( '../short', $prefix );
		$this->assertMatchesRegularExpression( '/^[a-z0-9]{16,64}$/', $prefix );
	}

	public function test_two_sites_get_different_prefixes() {
		delete_option( SDM_Debug::LOG_FILE_PREFIX_OPTION );
		$first = SDM_Debug::get_log_file_prefix();

		delete_option( SDM_Debug::LOG_FILE_PREFIX_OPTION );
		$second = SDM_Debug::get_log_file_prefix();

		$this->assertNotSame( $first, $second );
	}

	public function test_nothing_is_logged_when_debug_is_off() {
		update_option( 'sdm_downloads_options', array() );

		SDM_Debug::log( 'A test message' );
		SDM_Debug::log_array_data( array( 'key' => 'value' ) );

		$this->assertFileDoesNotExist( SDM_Debug::get_log_file_path() );
	}

	public function test_messages_are_logged_when_debug_is_on() {
		$this->enable_debug();

		SDM_Debug::log( 'A success message' );
		SDM_Debug::log( 'A failure message', false );
		SDM_Debug::log_array_data( array( 'key' => 'value' ), false, 'Test Addon' );

		$log = file_get_contents( SDM_Debug::get_log_file_path() );
		$this->assertStringContainsString( '[SUCCESS] A success message', $log );
		$this->assertStringContainsString( '[FAIL] A failure message', $log );
		$this->assertStringContainsString( '[Test Addon] FAILURE: Array', $log );
		$this->assertStringContainsString( '[key] => value', $log );
	}

	/**
	 * The plugin folder is replaced on every plugin update, so the folder and its protection files are created again when the log is written.
	 */
	public function test_writing_the_log_creates_the_protection_files() {
		$this->enable_debug();
		$dir = SDM_Debug::get_log_dir();
		@unlink( $dir . '.htaccess' );
		@unlink( $dir . 'index.html' );

		SDM_Debug::log( 'A test message' );

		$this->assertFileExists( $dir . '.htaccess' );
		$htaccess = file_get_contents( $dir . '.htaccess' );
		$this->assertStringContainsString( 'Require all denied', $htaccess, 'Apache 2.4 rule is missing.' );
		$this->assertStringContainsString( 'Deny from all', $htaccess, 'Apache 2.2 rule is missing.' );

		$this->assertFileExists( $dir . 'index.html' );
		$this->assertSame( '', file_get_contents( $dir . 'index.html' ) );
	}

	/**
	 * The logs folder and its protection files are part of the plugin's files too.
	 */
	public function test_the_plugin_ships_the_protection_files() {
		$this->assertFileExists( WP_SIMPLE_DL_MONITOR_PATH . 'logs/.htaccess' );
		$this->assertFileExists( WP_SIMPLE_DL_MONITOR_PATH . 'logs/index.html' );
	}

	public function test_reset_replaces_the_log_content() {
		$this->enable_debug();
		SDM_Debug::log( 'An old message' );

		SDM_Debug::reset_log();

		$log = file_get_contents( SDM_Debug::get_log_file_path() );
		$this->assertStringContainsString( 'Log has been reset.', $log );
		$this->assertStringNotContainsString( 'An old message', $log );
	}

	public function test_the_log_file_can_be_deleted() {
		$this->enable_debug();
		SDM_Debug::log( 'A test message' );
		$this->assertFileExists( SDM_Debug::get_log_file_path() );

		SDM_Debug::delete_log_file();

		$this->assertFileDoesNotExist( SDM_Debug::get_log_file_path() );
	}

	public function test_the_old_log_file_in_the_plugin_folder_is_deleted() {
		$legacy_file = WP_SIMPLE_DL_MONITOR_PATH . 'sdm-debug-log.txt';
		file_put_contents( $legacy_file, 'Old log content' );

		SDM_Debug::delete_legacy_log_file();

		$this->assertFileDoesNotExist( $legacy_file );
	}

	public function test_the_old_log_file_is_deleted_on_admin_init() {
		$legacy_file = WP_SIMPLE_DL_MONITOR_PATH . 'sdm-debug-log.txt';
		file_put_contents( $legacy_file, 'Old log content' );

		sdm_admin_init_time_tasks();

		$this->assertFileDoesNotExist( $legacy_file );
	}

	private function enable_debug() {
		update_option( 'sdm_downloads_options', array( 'enable_debug' => 'on' ) );
	}
}
