<?php
/**
 * The export logs AJAX handler (sdm_export_logs_handler(), the "Export Logs" button on the Logs page).
 *
 * GitHub issue #150 (fixed in 4.1.6): the handler sent the database error to the browser, and orderBy accepted any column name,
 * so a user could find the table's column names from the "Unknown column" errors. It also didn't check the user's permission.
 *
 * Note: the WordPress test library skips the "ajax" test group by default, so these tests call the handler directly
 * (with wp_doing_ajax() on, and wp_die() throwing an exception instead of ending the test run).
 */
class Test_Export_Logs extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_sdm_ajax_die_handler' ) );
	}

	public function tear_down() {
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_sdm_ajax_die_handler' ) );
		$_POST = array();
		SDM_Debug::delete_log_file();
		parent::tear_down();
	}

	public function test_a_request_without_a_valid_nonce_is_rejected() {
		$this->log_in_as( 'administrator' );
		$this->add_log_entry( array( 'visitor_name' => 'alice' ) );

		$response = $this->call_export( array( 'nonce' => 'wrong' ) );

		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Nonce verification failed', $response['data']['message'] );
		$this->assertArrayNotHasKey( 'logs', $response['data'] );
	}

	public function test_a_user_without_permission_is_rejected() {
		$this->log_in_as( 'subscriber' );
		$this->add_log_entry( array( 'visitor_name' => 'alice' ) );

		// A valid nonce for this user is not enough.
		$response = $this->call_export( array( 'nonce' => wp_create_nonce( 'sdm-export-logs-nonce-action' ) ) );

		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'permission', $response['data']['message'] );
		$this->assertArrayNotHasKey( 'logs', $response['data'] );
	}

	public function test_the_logs_are_exported() {
		$this->log_in_as( 'administrator' );
		$this->add_log_entry( array( 'post_title' => 'First download', 'visitor_name' => 'alice', 'visitor_ip' => '192.0.2.10' ) );
		$this->add_log_entry( array( 'post_title' => 'Second download', 'visitor_name' => 'bob', 'visitor_ip' => '192.0.2.20' ) );

		$response = $this->call_export();

		$this->assertTrue( $response['success'] );
		$this->assertCount( 2, $response['data']['logs'] );
		$first = $response['data']['logs'][0];
		foreach ( array( 'Log ID', 'Download ID', 'Download Title', 'File URL', 'Date', 'IP Address', 'Country', 'Name' ) as $column ) {
			$this->assertArrayHasKey( $column, $first, "The '$column' column is missing in the export." );
		}
	}

	public function test_the_newest_log_entry_comes_first_by_default() {
		$this->log_in_as( 'administrator' );
		$first_id  = $this->add_log_entry( array( 'visitor_name' => 'alice' ) );
		$second_id = $this->add_log_entry( array( 'visitor_name' => 'bob' ) );

		$response = $this->call_export();

		$this->assertSame( array( $second_id, $first_id ), $this->exported_ids( $response ) );
	}

	public function test_the_logs_are_sorted_by_an_allowed_column() {
		$this->log_in_as( 'administrator' );
		$this->add_log_entry( array( 'post_title' => 'Bravo' ) );
		$this->add_log_entry( array( 'post_title' => 'Charlie' ) );
		$this->add_log_entry( array( 'post_title' => 'Alpha' ) );

		$asc  = $this->call_export( array( 'orderBy' => 'post_title', 'order' => 'asc' ) );
		$desc = $this->call_export( array( 'orderBy' => 'post_title', 'order' => 'DESC' ) );

		$this->assertSame( array( 'Alpha', 'Bravo', 'Charlie' ), wp_list_pluck( $asc['data']['logs'], 'Download Title' ) );
		$this->assertSame( array( 'Charlie', 'Bravo', 'Alpha' ), wp_list_pluck( $desc['data']['logs'], 'Download Title' ) );
	}

	/**
	 * The export button sends the column the Logs table is sorted by, which can be any of its sortable columns.
	 *
	 * @dataProvider data_allowed_orderby_columns
	 */
	public function test_every_sortable_column_of_the_logs_table_is_accepted( $column ) {
		$this->log_in_as( 'administrator' );
		$this->add_log_entry();

		$response = $this->call_export( array( 'orderBy' => $column, 'order' => 'ASC' ) );

		$this->assertTrue( $response['success'], "Sorting by $column failed." );
		$this->assertCount( 1, $response['data']['logs'] );
	}

	public static function data_allowed_orderby_columns() {
		$data = array();
		foreach ( array( 'id', 'post_title', 'file_url', 'visitor_ip', 'date_time', 'visitor_country', 'visitor_name', 'user_agent', 'referrer_url' ) as $column ) {
			$data[ $column ] = array( $column );
		}
		return $data;
	}

	/**
	 * The report: an unknown column name made a database error that was sent to the browser.
	 *
	 * @dataProvider data_invalid_sort_values
	 */
	public function test_an_invalid_sort_value_falls_back_to_the_default_order( $order_by, $order ) {
		$this->log_in_as( 'administrator' );
		$first_id  = $this->add_log_entry();
		$second_id = $this->add_log_entry();

		$response = $this->call_export( array( 'orderBy' => $order_by, 'order' => $order ) );

		$this->assertTrue( $response['success'] );
		$this->assertSame( array( $second_id, $first_id ), $this->exported_ids( $response ), 'The default order (newest first) was not used.' );
		$this->assertStringNotContainsString( 'Unknown column', wp_json_encode( $response ) );
	}

	public static function data_invalid_sort_values() {
		return array(
			'unknown column (the report)'     => array( 'does_not_exist', 'ASC' ),
			'column with SQL'                 => array( 'id; DROP TABLE x', 'ASC' ),
			'expression'                      => array( '(SELECT 1)', 'ASC' ),
			'column with a direction'         => array( 'post_title DESC', '' ),
			'invalid direction'               => array( 'id', 'DESC; DROP TABLE x' ),
			'empty'                           => array( '', '' ),
		);
	}

	public function test_a_database_error_is_not_sent_to_the_browser() {
		global $wpdb;
		$this->log_in_as( 'administrator' );
		$this->add_log_entry();
		$this->enable_debug();

		// Break the export query, so the database returns an error.
		$break_query = function ( $query ) {
			if ( false !== strpos( $query, "'Log ID'" ) ) {
				$query = str_replace( 'ORDER BY', 'ORDER BY sdm_test_missing_column,', $query );
			}
			return $query;
		};
		add_filter( 'query', $break_query );
		$suppress = $wpdb->suppress_errors( true );

		$response = $this->call_export();

		$wpdb->suppress_errors( $suppress );
		remove_filter( 'query', $break_query );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Error! The download logs could not be retrieved.', $response['data']['message'] );
		$this->assertStringNotContainsString( 'sdm_test_missing_column', wp_json_encode( $response ) );

		// The database error is in the debug log instead.
		$this->assertStringContainsString( 'sdm_test_missing_column', file_get_contents( SDM_Debug::get_log_file_path() ) );
	}

	public function test_the_search_finds_matching_log_entries() {
		$this->log_in_as( 'administrator' );
		$this->add_log_entry( array( 'visitor_name' => 'alice', 'visitor_ip' => '192.0.2.10' ) );
		$this->add_log_entry( array( 'visitor_name' => 'bob', 'visitor_ip' => '198.51.100.20' ) );

		$by_name = $this->call_export( array( 'search' => 'ali' ) );
		$by_ip   = $this->call_export( array( 'search' => '198.51' ) );

		$this->assertSame( array( 'alice' ), wp_list_pluck( $by_name['data']['logs'], 'Name' ) );
		$this->assertSame( array( 'bob' ), wp_list_pluck( $by_ip['data']['logs'], 'Name' ) );
	}

	/**
	 * "%" and "_" in the search are searched for as characters (they were wildcards).
	 */
	public function test_search_wildcards_are_searched_as_characters() {
		$this->log_in_as( 'administrator' );
		$this->add_log_entry( array( 'visitor_name' => 'alice' ) );
		$this->add_log_entry( array( 'visitor_name' => 'user_one' ) );

		$percent    = $this->call_export( array( 'search' => '%' ) );
		$underscore = $this->call_export( array( 'search' => '_' ) );

		$this->assertTrue( $percent['success'] );
		$this->assertSame( array(), $percent['data']['logs'], '"%" matched every log entry.' );
		$this->assertSame( array( 'user_one' ), wp_list_pluck( $underscore['data']['logs'], 'Name' ), '"_" matched every log entry.' );
	}

	public function test_a_search_with_quotes_works() {
		$this->log_in_as( 'administrator' );
		$this->add_log_entry( array( 'post_title' => "Bob's file" ) );
		$this->add_log_entry( array( 'post_title' => 'Another file' ) );

		$response = $this->call_export( array( 'search' => "Bob's" ) );

		$this->assertTrue( $response['success'] );
		$this->assertSame( array( "Bob's file" ), wp_list_pluck( $response['data']['logs'], 'Download Title' ) );
	}

	/**
	 * The export reads the logs 100 at a time.
	 */
	public function test_more_than_100_log_entries_are_exported() {
		$this->log_in_as( 'administrator' );
		for ( $i = 1; $i <= 101; $i++ ) {
			$this->add_log_entry( array( 'visitor_name' => 'user' . $i ) );
		}

		$response = $this->call_export();

		$this->assertTrue( $response['success'] );
		$this->assertCount( 101, $response['data']['logs'] );
	}

	// ---------------------------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------------------------

	/**
	 * wp_die() handler for these tests (for AJAX requests): throws instead of ending the test run.
	 * Note: the names must not be the same as the WP_UnitTestCase methods get_wp_die_handler() and wp_die_handler().
	 */
	public function get_sdm_ajax_die_handler() {
		return array( $this, 'sdm_ajax_die_handler' );
	}

	public function sdm_ajax_die_handler( $message ) {
		throw new WPDieException( is_string( $message ) ? $message : '' );
	}

	/**
	 * Calls the handler and returns the JSON response as an array.
	 *
	 * @param array $post The POST data. A valid nonce is added when none is given.
	 */
	private function call_export( $post = array() ) {
		$_POST = array_merge( array( 'nonce' => wp_create_nonce( 'sdm-export-logs-nonce-action' ) ), $post );

		ob_start();
		try {
			sdm_export_logs_handler();
		} catch ( WPDieException $e ) {
			// wp_send_json() ends with wp_die().
			unset( $e );
		}
		$output = ob_get_clean();

		$response = json_decode( $output, true );
		$this->assertIsArray( $response, 'The response is not JSON: ' . $output );
		return $response;
	}

	private function exported_ids( $response ) {
		return array_map( 'intval', wp_list_pluck( $response['data']['logs'], 'Log ID' ) );
	}

	private function log_in_as( $role ) {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
	}

	private function enable_debug() {
		update_option( 'sdm_downloads_options', array( 'enable_debug' => 'on' ) );
	}

	/**
	 * Adds a row to the download logs table and returns its ID.
	 */
	private function add_log_entry( $data = array() ) {
		global $wpdb;
		$data = array_merge(
			array(
				'post_id'         => 1,
				'post_title'      => 'Test download',
				'file_url'        => 'https://example.org/wp-content/uploads/test-file.zip',
				'visitor_ip'      => '192.0.2.1',
				'date_time'       => current_time( 'mysql' ),
				'visitor_country' => 'US',
				'visitor_name'    => 'visitor',
				'user_agent'      => 'Test agent',
				'referrer_url'    => 'https://example.org/',
			),
			$data
		);
		$wpdb->insert( $wpdb->prefix . 'sdm_downloads', $data );
		return (int) $wpdb->insert_id;
	}
}
