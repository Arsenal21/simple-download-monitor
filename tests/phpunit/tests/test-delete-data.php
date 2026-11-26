<?php
/**
 * The settings page AJAX buttons: "Delete all data and deactivate plugin" (sdm_delete_data_handler())
 * and "Reset Debug Log" (sdm_reset_log_handler()).
 *
 * The WordPress test library skips the "ajax" test group, so the handlers are called directly
 * (with wp_doing_ajax() on, and wp_die() throwing an exception instead of ending the test run).
 */
class Test_Delete_Data extends WP_UnitTestCase {

	/** The queries the test stopped (the DROP TABLE of the download logs table). */
	private $stopped_queries = array();

	public static function set_up_before_class() {
		parent::set_up_before_class();
		// deactivate_plugins() is loaded on admin requests (AJAX requests too).
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	public function set_up() {
		parent::set_up();
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_sdm_ajax_die_handler' ) );
		// The test doesn't drop the real table (the other tests use it): the query is recorded and not run.
		add_filter( 'query', array( $this, 'stop_drop_table_query' ), 999 );
	}

	public function tear_down() {
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_sdm_ajax_die_handler' ) );
		remove_filter( 'query', array( $this, 'stop_drop_table_query' ), 999 );
		$_POST    = array();
		$_REQUEST = array();
		SDM_Debug::delete_log_file();

		// The handler unregisters the post type and the taxonomies: register them again for the next tests.
		if ( ! post_type_exists( 'sdm_downloads' ) ) {
			sdm_register_post_type();
		}
		if ( ! taxonomy_exists( 'sdm_categories' ) || ! taxonomy_exists( 'sdm_tags' ) ) {
			sdm_create_taxonomies();
		}
		parent::tear_down();
	}

	public function test_delete_all_data_deletes_the_items_settings_and_logs_and_deactivates_the_plugin() {
		global $wpdb;
		$this->log_in_as( 'administrator' );
		$item     = self::factory()->post->create( array( 'post_type' => 'sdm_downloads' ) );
		$other    = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$basename = plugin_basename( WP_SIMPLE_DL_MONITOR_PATH . 'main.php' );
		update_option( 'active_plugins', array( $basename ) );
		update_option( 'sdm_downloads_options', array( 'enable_debug' => 'on' ) );
		SDM_Debug::log( 'A debug log line.' );
		// The path is read now: after the prefix option is deleted, get_log_file_path() makes a new prefix.
		$log_file = SDM_Debug::get_log_file_path();
		$this->assertFileExists( $log_file );

		$output = $this->call_handler( 'sdm_delete_data_handler', wp_create_nonce( 'sdm_delete_data' ) );

		$this->assertSame( '1', $output );
		$this->assertNull( get_post( $item ), 'The download item was not deleted.' );
		$this->assertNotNull( get_post( $other ), 'A normal post was deleted.' );
		$this->assertFalse( get_option( 'sdm_downloads_options' ) );
		$this->assertFalse( get_option( 'sdm_db_version' ) );
		$this->assertFalse( get_option( SDM_Debug::LOG_FILE_PREFIX_OPTION ) );
		$this->assertFileDoesNotExist( $log_file );
		$this->assertFalse( post_type_exists( 'sdm_downloads' ) );
		$this->assertFalse( taxonomy_exists( 'sdm_categories' ) );
		$this->assertNotContains( $basename, (array) get_option( 'active_plugins' ), 'The plugin was not deactivated.' );
		$this->assertCount( 1, $this->stopped_queries, 'The download logs table was not dropped.' );
		$this->assertStringContainsString( $wpdb->prefix . 'sdm_downloads', $this->stopped_queries[0] );
	}

	public function test_delete_all_data_without_a_valid_nonce_does_nothing() {
		$this->log_in_as( 'administrator' );
		$item = self::factory()->post->create( array( 'post_type' => 'sdm_downloads' ) );
		update_option( 'sdm_downloads_options', array( 'enable_debug' => 'on' ) );

		$this->call_handler( 'sdm_delete_data_handler', 'wrong' );

		$this->assert_nothing_was_deleted( $item );
	}

	public function test_delete_all_data_by_a_user_without_permission_does_nothing() {
		$this->log_in_as( 'subscriber' );
		$item = self::factory()->post->create( array( 'post_type' => 'sdm_downloads' ) );
		update_option( 'sdm_downloads_options', array( 'enable_debug' => 'on' ) );

		// A valid nonce for this user is not enough.
		$this->call_handler( 'sdm_delete_data_handler', wp_create_nonce( 'sdm_delete_data' ) );

		$this->assertSame( 'You do not have permission to access this settings page.', $this->die_message );
		$this->assert_nothing_was_deleted( $item );
	}

	public function test_reset_debug_log_replaces_the_log_content() {
		$this->log_in_as( 'administrator' );
		update_option( 'sdm_downloads_options', array( 'enable_debug' => 'on' ) );
		SDM_Debug::log( 'An old debug log line.' );

		$output = $this->call_handler( 'sdm_reset_log_handler', wp_create_nonce( 'sdm_delete_data' ) );

		$this->assertSame( '1', $output );
		$content = file_get_contents( SDM_Debug::get_log_file_path() );
		$this->assertStringNotContainsString( 'An old debug log line.', $content );
		$this->assertStringContainsString( 'Log has been reset.', $content );
	}

	/**
	 * @dataProvider data_refused_reset_requests
	 */
	public function test_reset_debug_log_is_refused( $role, $valid_nonce ) {
		$this->log_in_as( $role );
		update_option( 'sdm_downloads_options', array( 'enable_debug' => 'on' ) );
		SDM_Debug::log( 'An old debug log line.' );

		$this->call_handler( 'sdm_reset_log_handler', $valid_nonce ? wp_create_nonce( 'sdm_delete_data' ) : 'wrong' );

		$this->assertStringContainsString( 'An old debug log line.', file_get_contents( SDM_Debug::get_log_file_path() ) );
	}

	public static function data_refused_reset_requests() {
		return array(
			'wrong nonce'                => array( 'administrator', false ),
			'user without permission'    => array( 'subscriber', true ),
		);
	}

	// ---------------------------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------------------------

	/** The message of the last wp_die() call. */
	private $die_message = null;

	/**
	 * wp_die() handler for these tests (for AJAX requests): throws instead of ending the test run.
	 * Note: the names must not be the same as the WP_UnitTestCase methods get_wp_die_handler() and wp_die_handler().
	 */
	public function get_sdm_ajax_die_handler() {
		return array( $this, 'sdm_ajax_die_handler' );
	}

	public function sdm_ajax_die_handler( $message ) {
		$this->die_message = is_scalar( $message ) ? (string) $message : '';
		throw new WPDieException( $this->die_message );
	}

	public function stop_drop_table_query( $query ) {
		global $wpdb;
		if ( preg_match( '/^\s*DROP\s+(TEMPORARY\s+)?TABLE/i', $query ) && false !== strpos( $query, $wpdb->prefix . 'sdm_downloads' ) ) {
			$this->stopped_queries[] = $query;
			return '';
		}
		return $query;
	}

	/**
	 * Calls the AJAX handler with this nonce and returns what it printed.
	 */
	private function call_handler( $handler, $nonce ) {
		$_POST    = array( 'nonce' => $nonce );
		$_REQUEST = $_POST;

		ob_start();
		try {
			call_user_func( $handler );
		} catch ( WPDieException $e ) {
			// The handlers end with wp_die().
			unset( $e );
		}
		return ob_get_clean();
	}

	private function assert_nothing_was_deleted( $item ) {
		$this->assertNotNull( get_post( $item ), 'The download item was deleted.' );
		$this->assertSame( array( 'enable_debug' => 'on' ), get_option( 'sdm_downloads_options' ) );
		$this->assertTrue( post_type_exists( 'sdm_downloads' ) );
		$this->assertSame( array(), $this->stopped_queries, 'The download logs table was dropped.' );
	}

	private function log_in_as( $role ) {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
	}
}
