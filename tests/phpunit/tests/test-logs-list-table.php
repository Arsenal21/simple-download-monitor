<?php
/**
 * The Logs admin page table (sdm_List_Table, Downloads > Logs > Main Logs, and the Specific Item Logs tab):
 * sorting, search, one item's logs, pages, deleting entries, and the escaping of the values.
 */
class Test_Logs_List_Table extends WP_UnitTestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		// The admin functions the list table uses (screen, template functions). They are loaded on admin pages.
		require_once ABSPATH . 'wp-admin/includes/admin.php';
	}

	public function set_up() {
		parent::set_up();
		set_current_screen( 'sdm_downloads_page_sdm-logs' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function tear_down() {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		remove_all_filters( 'sdm_download_logs_menu_items_per_page' );
		set_current_screen( 'front' );
		parent::tear_down();
	}

	public function test_the_newest_log_entry_comes_first_by_default() {
		$this->add_log_entry( array( 'visitor_name' => 'old', 'date_time' => '2026-01-01 10:00:00' ) );
		$this->add_log_entry( array( 'visitor_name' => 'new', 'date_time' => '2026-03-01 10:00:00' ) );
		$this->add_log_entry( array( 'visitor_name' => 'middle', 'date_time' => '2026-02-01 10:00:00' ) );

		$table = $this->prepare_table();

		$this->assertSame( array( 'new', 'middle', 'old' ), wp_list_pluck( $table->items, 'visitor_name' ) );
	}

	public function test_every_item_has_the_values_the_table_shows() {
		$this->add_log_entry(
			array(
				'post_id'         => 7,
				'post_title'      => 'My file',
				'file_url'        => 'https://example.org/file.zip',
				'visitor_ip'      => '192.0.2.5',
				'visitor_country' => 'Canada',
				'visitor_name'    => 'alice',
				'user_agent'      => 'Agent 1',
				'referrer_url'    => 'https://example.org/page/',
			)
		);

		$table = $this->prepare_table();

		$this->assertCount( 1, $table->items );
		$item = $table->items[0];
		$this->assertSame( '7', (string) $item['ID'] );
		$this->assertSame( 'My file', $item['title'] );
		$this->assertSame( 'https://example.org/file.zip', $item['URL'] );
		$this->assertSame( '192.0.2.5', $item['visitor_ip'] );
		$this->assertSame( 'Canada', $item['visitor_country'] );
		$this->assertSame( 'alice', $item['visitor_name'] );
		$this->assertSame( 'Agent 1', $item['user_agent'] );
		$this->assertSame( 'https://example.org/page/', $item['referrer_url'] );
	}

	/**
	 * The column headers link to orderby=<column>&order=asc|desc.
	 */
	public function test_the_logs_are_sorted_by_a_column() {
		$this->add_log_entry( array( 'post_title' => 'Bravo' ) );
		$this->add_log_entry( array( 'post_title' => 'Charlie' ) );
		$this->add_log_entry( array( 'post_title' => 'Alpha' ) );

		$asc  = $this->prepare_table( array( 'orderby' => 'post_title', 'order' => 'asc' ) );
		$desc = $this->prepare_table( array( 'orderby' => 'post_title', 'order' => 'desc' ) );

		$this->assertSame( array( 'Alpha', 'Bravo', 'Charlie' ), wp_list_pluck( $asc->items, 'title' ) );
		$this->assertSame( array( 'Charlie', 'Bravo', 'Alpha' ), wp_list_pluck( $desc->items, 'title' ) );
	}

	/**
	 * @dataProvider data_sortable_columns
	 */
	public function test_every_sortable_column_works( $column ) {
		$this->add_log_entry();
		$this->add_log_entry();

		$table = $this->prepare_table( array( 'orderby' => $column, 'order' => 'asc' ) );

		$this->assertCount( 2, $table->items, "Sorting by $column failed." );
	}

	public static function data_sortable_columns() {
		$data = array();
		foreach ( array( 'post_title', 'file_url', 'visitor_ip', 'date_time', 'visitor_country', 'visitor_name', 'user_agent', 'referrer_url' ) as $column ) {
			$data[ $column ] = array( $column );
		}
		return $data;
	}

	/**
	 * A column or direction that isn't allowed is replaced (the column by post_title, the direction by desc): no SQL error.
	 *
	 * @dataProvider data_invalid_sort_values
	 */
	public function test_an_invalid_sort_value_is_not_used_in_the_query( $orderby, $order ) {
		global $wpdb;
		$this->add_log_entry( array( 'post_title' => 'Alpha' ) );
		$this->add_log_entry( array( 'post_title' => 'Bravo' ) );

		$suppress = $wpdb->suppress_errors( true );
		$table    = $this->prepare_table( array( 'orderby' => $orderby, 'order' => $order ) );
		$wpdb->suppress_errors( $suppress );

		$this->assertSame( '', $wpdb->last_error );
		$this->assertSame( array( 'Bravo', 'Alpha' ), wp_list_pluck( $table->items, 'title' ), 'The logs are not sorted by "post_title desc".' );
	}

	public static function data_invalid_sort_values() {
		return array(
			'unknown column'       => array( 'does_not_exist', 'desc' ),
			'column with SQL'      => array( 'id; DROP TABLE x', 'desc' ),
			'direction with SQL'   => array( 'post_title', 'desc; DROP TABLE x' ),
			'invalid direction'    => array( 'post_title', 'sideways' ),
		);
	}

	public function test_the_search_finds_by_name_ip_title_and_country() {
		$this->add_log_entry( array( 'visitor_name' => 'alice', 'visitor_ip' => '192.0.2.10', 'post_title' => 'Manual', 'visitor_country' => 'Canada' ) );
		$this->add_log_entry( array( 'visitor_name' => 'bob', 'visitor_ip' => '198.51.100.20', 'post_title' => 'Brochure', 'visitor_country' => 'Germany' ) );

		$this->assertSame( array( 'alice' ), $this->search_names( 'ali' ) );
		$this->assertSame( array( 'bob' ), $this->search_names( '198.51' ) );
		$this->assertSame( array( 'bob' ), $this->search_names( 'Brochure' ) );
		$this->assertSame( array( 'alice' ), $this->search_names( 'Canada' ) );
		$this->assertSame( array(), $this->search_names( 'nobody' ) );
	}

	public function test_the_search_result_count_is_used_for_the_pages() {
		$this->add_log_entry( array( 'visitor_name' => 'alice' ) );
		$this->add_log_entry( array( 'visitor_name' => 'alina' ) );
		$this->add_log_entry( array( 'visitor_name' => 'bob' ) );

		$table = $this->prepare_table( array( 's' => 'ali' ) );

		$this->assertSame( 2, (int) $table->get_pagination_arg( 'total_items' ) );
	}

	/**
	 * "%" and "_" in the search are searched for as characters (they were wildcards, so "%" found every entry).
	 */
	public function test_search_wildcards_are_searched_as_characters() {
		$this->add_log_entry( array( 'visitor_name' => 'alice' ) );
		$this->add_log_entry( array( 'visitor_name' => 'user_one' ) );

		$this->assertSame( array(), $this->search_names( '%' ), '"%" matched every log entry.' );
		$this->assertSame( array( 'user_one' ), $this->search_names( '_' ), '"_" matched every log entry.' );
	}

	public function test_a_search_with_quotes_works() {
		global $wpdb;
		$this->add_log_entry( array( 'post_title' => "Bob's file", 'visitor_name' => 'bob' ) );
		$this->add_log_entry( array( 'post_title' => 'Another file', 'visitor_name' => 'alice' ) );

		$suppress = $wpdb->suppress_errors( true );
		$names    = $this->search_names( "Bob's" );
		$wpdb->suppress_errors( $suppress );

		$this->assertSame( '', $wpdb->last_error );
		$this->assertSame( array( 'bob' ), $names );
	}

	/**
	 * The Specific Item Logs tab: sdm_logs_dl_id.
	 */
	public function test_the_logs_of_one_download_item_are_shown() {
		$this->add_log_entry( array( 'post_id' => 11, 'visitor_name' => 'alice' ) );
		$this->add_log_entry( array( 'post_id' => 12, 'visitor_name' => 'bob' ) );
		$this->add_log_entry( array( 'post_id' => 11, 'visitor_name' => 'carol' ) );

		$table = $this->prepare_table( array( 'sdm_logs_dl_id' => '11' ) );

		$this->assertEqualsCanonicalizing( array( 'alice', 'carol' ), wp_list_pluck( $table->items, 'visitor_name' ) );
		$this->assertSame( 2, (int) $table->get_pagination_arg( 'total_items' ) );
	}

	public function test_the_logs_are_shown_in_pages() {
		add_filter(
			'sdm_download_logs_menu_items_per_page',
			function () {
				return 2;
			}
		);
		for ( $i = 1; $i <= 3; $i++ ) {
			$this->add_log_entry( array( 'visitor_name' => 'user' . $i, 'date_time' => '2026-01-0' . $i . ' 10:00:00' ) );
		}

		$page_1 = $this->prepare_table();
		$page_2 = $this->prepare_table( array( 'paged' => '2' ) );

		$this->assertSame( array( 'user3', 'user2' ), wp_list_pluck( $page_1->items, 'visitor_name' ) );
		$this->assertSame( array( 'user1' ), wp_list_pluck( $page_2->items, 'visitor_name' ) );
		$this->assertSame( 3, (int) $page_1->get_pagination_arg( 'total_items' ) );
		$this->assertSame( 2, (int) $page_1->get_pagination_arg( 'total_pages' ) );
	}

	/**
	 * The values come from the download request (User-Agent, referrer) and from the country lookup service.
	 * They were printed without escaping.
	 */
	public function test_the_values_are_escaped_in_the_table() {
		$this->add_log_entry(
			array(
				'post_title'      => '<b>Title</b>',
				'visitor_country' => '<img src=x onerror=alert(1)>',
				'user_agent'      => '<script>alert("agent")</script>',
				'referrer_url'    => '"><script>alert("referrer")</script>',
				'visitor_name'    => '<i>name</i>',
			)
		);

		$html = $this->display_table();

		$this->assertStringNotContainsString( '<img src=x', $html );
		$this->assertStringNotContainsString( '<script>alert', $html );
		$this->assertStringNotContainsString( '<i>name</i>', $html );
		$this->assertStringNotContainsString( '<b>Title</b>', $html );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(&quot;agent&quot;)&lt;/script&gt;', $html );
	}

	public function test_the_table_shows_the_entries_with_the_row_actions() {
		$row_id = $this->add_log_entry( array( 'post_id' => 21, 'post_title' => 'Shown item', 'visitor_name' => 'alice' ) );

		$html = $this->display_table();

		$this->assertStringContainsString( 'Shown item', $html );
		$this->assertStringContainsString( 'post.php?post=21&action=edit', $html );
		$this->assertStringContainsString( 'row_id=' . $row_id, $html );
		$this->assertStringContainsString( 'name="download[]" value="' . $row_id . '"', $html );
		$this->assertStringContainsString( 'sdm-export-logs-nonce', $html, 'The Export Logs button is missing.' );
	}

	public function test_bulk_delete_deletes_the_selected_entries() {
		$first  = $this->add_log_entry();
		$second = $this->add_log_entry();
		$kept   = $this->add_log_entry();

		$output = $this->prepare_table_output(
			array( 'action' => 'delete2' ),
			array(
				'_wpnonce' => wp_create_nonce( 'bulk-downloads' ),
				'download' => array( (string) $first, (string) $second ),
			)
		);

		$this->assertStringContainsString( 'Entries Deleted!', $output );
		$this->assertSame( array( $kept ), $this->log_ids() );
	}

	public function test_bulk_delete_without_a_selection_shows_a_message() {
		$this->add_log_entry();

		$output = $this->prepare_table_output( array( 'action' => 'delete2' ), array( '_wpnonce' => wp_create_nonce( 'bulk-downloads' ) ) );

		$this->assertStringContainsString( 'No entries were selected.', $output );
		$this->assertCount( 1, $this->log_ids() );
	}

	public function test_bulk_delete_with_a_wrong_nonce_is_refused() {
		$id = $this->add_log_entry();

		$this->expect_die( 'Security check failed' );
		try {
			$this->prepare_table_output( array( 'action' => 'delete2' ), array( '_wpnonce' => 'wrong', 'download' => array( (string) $id ) ) );
		} finally {
			$this->assertSame( array( $id ), $this->log_ids(), 'The entry was deleted.' );
		}
	}

	public function test_bulk_delete_refuses_a_row_id_that_is_not_a_number() {
		$id = $this->add_log_entry();

		$this->expect_die( 'must be numeric' );
		try {
			$this->prepare_table_output(
				array( 'action' => 'delete2' ),
				array(
					'_wpnonce' => wp_create_nonce( 'bulk-downloads' ),
					'download' => array( $id . ' OR 1=1' ),
				)
			);
		} finally {
			$this->assertSame( array( $id ), $this->log_ids(), 'The entry was deleted.' );
		}
	}

	public function test_the_delete_link_deletes_one_entry() {
		$deleted = $this->add_log_entry();
		$kept    = $this->add_log_entry();

		$output = $this->prepare_table_output(
			array(
				'action'   => 'delete',
				'row_id'   => (string) $deleted,
				'_wpnonce' => wp_create_nonce( 'sdm_delete_log_entry' ),
			)
		);

		$this->assertStringContainsString( 'Entry Deleted!', $output );
		$this->assertSame( array( $kept ), $this->log_ids() );
	}

	public function test_the_delete_link_with_a_wrong_nonce_is_refused() {
		$id = $this->add_log_entry();

		$this->expect_die( 'Security check failed' );
		try {
			$this->prepare_table_output(
				array(
					'action'   => 'delete',
					'row_id'   => (string) $id,
					'_wpnonce' => 'wrong',
				)
			);
		} finally {
			$this->assertSame( array( $id ), $this->log_ids(), 'The entry was deleted.' );
		}
	}

	// ---------------------------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------------------------

	/**
	 * Creates the table and runs prepare_items() with these query parameters (GET) and POST data.
	 */
	private function prepare_table( $get = array(), $post = array() ) {
		// WordPress adds slashes to the request data (wp_magic_quotes()).
		$_GET     = wp_slash( $get );
		$_POST    = wp_slash( $post );
		$_REQUEST = array_merge( $_GET, $_POST );

		$table = new sdm_List_Table();
		$table->prepare_items();
		return $table;
	}

	/**
	 * Same as prepare_table(), and returns what was printed (the messages of the delete actions).
	 */
	private function prepare_table_output( $get = array(), $post = array() ) {
		ob_start();
		try {
			$this->prepare_table( $get, $post );
		} finally {
			$output = ob_get_clean();
		}
		return $output;
	}

	private function display_table( $get = array() ) {
		$table = $this->prepare_table( $get );
		ob_start();
		$table->display();
		return ob_get_clean();
	}

	private function search_names( $search ) {
		return wp_list_pluck( $this->prepare_table( array( 's' => $search ) )->items, 'visitor_name' );
	}

	private function expect_die( $message ) {
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( $message );
	}

	private function log_ids() {
		global $wpdb;
		return array_map( 'intval', $wpdb->get_col( 'SELECT id FROM ' . $wpdb->prefix . 'sdm_downloads ORDER BY id' ) );
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
