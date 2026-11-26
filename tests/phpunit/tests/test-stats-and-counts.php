<?php
/**
 * The Stats admin page functions (includes/sdm-utility-functions-admin-side.php), the download counts,
 * and the CSV export of the Logs page Export tab.
 */
class Test_Stats_And_Counts extends WP_UnitTestCase {

	// ---------------------------------------------------------------------------------------------
	// Stats page
	// ---------------------------------------------------------------------------------------------

	public function test_downloads_by_date_counts_each_day_in_the_date_range() {
		$this->add_log_entry( array( 'date_time' => '2026-03-01 09:00:00' ) );
		$this->add_log_entry( array( 'date_time' => '2026-03-01 18:30:00' ) );
		$this->add_log_entry( array( 'date_time' => '2026-03-03 12:00:00' ) );
		$this->add_log_entry( array( 'date_time' => '2026-02-28 23:59:59' ) ); // Before the range.
		$this->add_log_entry( array( 'date_time' => '2026-03-04 00:00:00' ) ); // After the range.

		$rows = sdm_get_downloads_by_date( '2026-03-01', '2026-03-03', array(), false );

		$this->assertSame(
			array(
				'2026-03-01' => 2,
				'2026-03-03' => 1,
			),
			$this->counts( $rows, 'day' )
		);
	}

	public function test_downloads_by_date_as_a_chart_string() {
		$this->add_log_entry( array( 'date_time' => '2026-03-01 09:00:00' ) );
		$this->add_log_entry( array( 'date_time' => '2026-03-02 09:00:00' ) );

		$this->assertSame( '["2026-03-01", 1],["2026-03-02", 1],', sdm_get_downloads_by_date( '2026-03-01', '2026-03-02' ) );
	}

	public function test_the_stats_can_be_limited_to_some_download_items() {
		$this->add_log_entry( array( 'post_id' => 11, 'visitor_country' => 'Canada' ) );
		$this->add_log_entry( array( 'post_id' => 12, 'visitor_country' => 'Germany' ) );
		$this->add_log_entry( array( 'post_id' => 13, 'visitor_country' => 'Canada' ) );

		$rows = sdm_get_downloads_by_country( '2026-03-01', '2026-03-31', array( 11, 12 ), false );

		$this->assertSame(
			array(
				'Canada'  => 1,
				'Germany' => 1,
			),
			$this->counts( $rows, 'country' )
		);
	}

	public function test_downloads_by_country() {
		$this->add_log_entry( array( 'visitor_country' => 'Canada' ) );
		$this->add_log_entry( array( 'visitor_country' => 'Canada' ) );
		$this->add_log_entry( array( 'visitor_country' => 'Germany' ) );

		$rows = sdm_get_downloads_by_country( '2026-03-01', '2026-03-31', array(), false );

		$this->assertSame(
			array(
				'Canada'  => 2,
				'Germany' => 1,
			),
			$this->counts( $rows, 'country' )
		);
	}

	public function test_downloads_by_browser() {
		$agents = array(
			'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
			'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36 Edg/120.0',
			'Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
			'SomeDownloader/1.0',
			'Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0',
		);
		foreach ( $agents as $agent ) {
			$this->add_log_entry( array( 'user_agent' => $agent ) );
		}

		$browsers = sdm_get_all_downloads_by_browser( '2026-03-01', '2026-03-31' );

		$this->assertSame( 1, $browsers['Chrome'] );
		$this->assertSame( 1, $browsers['Microsoft Edge'] );
		$this->assertSame( 2, $browsers['Firefox'] );
		$this->assertSame( 1, $browsers['Safari'] );
		$this->assertSame( 1, $browsers['Other'] );
		$this->assertSame( 'Other', array_key_last( $browsers ), '"Other" is not the last row.' );
	}

	public function test_downloads_by_operating_system() {
		$agents = array(
			'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
			'Mozilla/5.0 (Linux; Android 14; Pixel 8) Chrome/120.0 Mobile',
			'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) Safari/604.1',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) Safari/605.1.15',
			'Mozilla/5.0 (X11; Linux x86_64) Firefox/121.0',
			'SomeDownloader/1.0',
		);
		foreach ( $agents as $agent ) {
			$this->add_log_entry( array( 'user_agent' => $agent ) );
		}

		$systems = sdm_get_all_downloads_by_os( '2026-03-01', '2026-03-31' );

		foreach ( array( 'Windows 10', 'Android', 'iOS', 'macOS', 'Linux', 'Other' ) as $os ) {
			$this->assertSame( 1, isset( $systems[ $os ] ) ? $systems[ $os ] : 0, "Wrong count for $os: " . wp_json_encode( $systems ) );
		}
		$this->assertSame( 'Other', array_key_last( $systems ), '"Other" is not the last row.' );
	}

	public function test_top_users_are_sorted_by_download_count_and_limited() {
		foreach ( array( 'alice', 'bob', 'bob', 'carol', 'carol', 'carol' ) as $name ) {
			$this->add_log_entry( array( 'visitor_name' => $name ) );
		}

		$rows = sdm_get_top_users_by_download_count( '2026-03-01', '2026-03-31', array(), 2 );

		$this->assertSame( array( 'carol', 'bob' ), wp_list_pluck( $rows, 'visitor_name' ) );
		$this->assertSame( array( '3', '2' ), array_map( 'strval', wp_list_pluck( $rows, 'cnt' ) ) );
	}

	public function test_top_downloads_are_sorted_by_download_count_and_limited() {
		foreach ( array( 'Manual', 'Brochure', 'Brochure', 'Catalog', 'Catalog', 'Catalog' ) as $title ) {
			$this->add_log_entry( array( 'post_title' => $title ) );
		}

		$rows = sdm_get_top_downloads_by_count( '2026-03-01', '2026-03-31', array(), 2 );

		$this->assertSame( array( 'Catalog', 'Brochure' ), wp_list_pluck( $rows, 'post_title' ) );
	}

	public function test_the_stats_are_empty_without_downloads_in_the_range() {
		$this->add_log_entry( array( 'date_time' => '2025-01-01 10:00:00' ) );

		$this->assertSame( array(), sdm_get_downloads_by_date( '2026-03-01', '2026-03-31', array(), false ) );
		$this->assertSame( array(), sdm_get_downloads_by_country( '2026-03-01', '2026-03-31', array(), false ) );
		$this->assertSame( array(), sdm_get_all_downloads_by_browser( '2026-03-01', '2026-03-31' ) );
		$this->assertSame( array(), sdm_get_top_users_by_download_count( '2026-03-01', '2026-03-31' ) );
	}

	public function test_move_array_element_to_end() {
		$array = array(
			'Other'   => 1,
			'Chrome'  => 2,
			'Firefox' => 3,
		);
		$this->assertSame( array( 'Chrome', 'Firefox', 'Other' ), array_keys( moveArrayElementToEnd( $array, 'Other' ) ) );

		$without = array( 'Chrome' => 2 );
		$this->assertSame( array( 'Chrome' => 2 ), moveArrayElementToEnd( $without, 'Other' ) );
	}

	// ---------------------------------------------------------------------------------------------
	// Date check (the date range of the Export tab and the Stats page)
	// ---------------------------------------------------------------------------------------------

	/**
	 * @dataProvider data_dates
	 */
	public function test_a_date_is_checked( $date, $valid ) {
		$this->assertSame( $valid, sdm_validate_date_field( $date ) );
	}

	public static function data_dates() {
		return array(
			'valid date'       => array( '2026-03-15', true ),
			'leap day'         => array( '2024-02-29', true ),
			'no leap day'      => array( '2026-02-29', false ),
			'month 13'         => array( '2026-13-01', false ),
			'day 32'           => array( '2026-01-32', false ),
			'not a date'       => array( 'yesterday', false ),
			'empty'            => array( '', false ),
		);
	}

	/**
	 * The Export tab checks the start and the end date. Only the first date of the array was checked.
	 */
	public function test_every_date_of_a_date_range_is_checked() {
		$this->assertTrue( sdm_validate_date_field( array( '2026-03-01', '2026-03-31' ) ) );
		$this->assertFalse( sdm_validate_date_field( array( '2026-03-01', 'not-a-date' ) ), 'An invalid end date was accepted.' );
		$this->assertFalse( sdm_validate_date_field( array( 'not-a-date', '2026-03-31' ) ) );
		$this->assertFalse( sdm_validate_date_field( array() ) );
	}

	// ---------------------------------------------------------------------------------------------
	// Download counts ([sdm_download_counter], the download count shown with the items)
	// ---------------------------------------------------------------------------------------------

	public function test_the_download_count_of_an_item_adds_the_count_offset() {
		$id = $this->create_item( 5 );
		$this->add_log_entry( array( 'post_id' => $id ) );
		$this->add_log_entry( array( 'post_id' => $id ) );
		$this->add_log_entry( array( 'post_id' => $id + 1000 ) ); // Another item.

		$this->assertSame( 7, (int) sdm_get_download_count_for_post( $id ) );
	}

	public function test_the_download_count_of_an_item_without_an_offset() {
		$id = $this->create_item( 0 );
		$this->add_log_entry( array( 'post_id' => $id ) );

		$this->assertSame( 1, (int) sdm_get_download_count_for_post( $id ) );
	}

	/**
	 * The total count of all items ([sdm_download_counter id="all"]) adds the offsets of all the items.
	 * Its loop used $wpdb->num_rows as its end, which get_post_meta() changes, so it stopped after the first item.
	 */
	public function test_the_total_download_count_adds_the_offsets_of_all_items() {
		$ids = array( $this->create_item( 10 ), $this->create_item( 20 ), $this->create_item( 30 ), $this->create_item( 0 ) );
		foreach ( $ids as $id ) {
			$this->add_log_entry( array( 'post_id' => $id ) );
		}

		$this->assertSame( 4 + 10 + 20 + 30, (int) sdm_get_download_count_for_all_posts() );
	}

	// ---------------------------------------------------------------------------------------------
	// CSV export (the Export tab of the Logs page)
	// ---------------------------------------------------------------------------------------------

	public function test_the_csv_export_has_a_column_name_for_every_value() {
		$this->add_log_entry( array( 'visitor_name' => 'alice', 'user_agent' => 'Agent 1' ) );

		$rows = sdm_get_download_logs_csv_rows( '2026-03-01', '2026-03-31' );

		$this->assertCount( 2, $rows );
		$this->assertSame( array( 'Log ID', 'Download ID', 'Download Title', 'File URL', 'Date', 'IP Address', 'Country', 'Name', 'User Agent' ), $rows[0] );
		$this->assertCount( count( $rows[0] ), $rows[1], 'The rows have more values than column names.' );
		$this->assertSame( 'alice', $rows[1][7] );
		$this->assertSame( 'Agent 1', $rows[1][8] );
	}

	public function test_the_csv_export_has_the_entries_of_the_date_range_newest_first() {
		$this->add_log_entry( array( 'visitor_name' => 'before', 'date_time' => '2026-02-28 23:59:59' ) );
		$this->add_log_entry( array( 'visitor_name' => 'first day', 'date_time' => '2026-03-01 00:00:00' ) );
		$this->add_log_entry( array( 'visitor_name' => 'last day', 'date_time' => '2026-03-31 23:59:59' ) );
		$this->add_log_entry( array( 'visitor_name' => 'after', 'date_time' => '2026-04-01 00:00:00' ) );

		$rows = sdm_get_download_logs_csv_rows( '2026-03-01', '2026-03-31' );
		array_shift( $rows );

		$this->assertSame( array( 'last day', 'first day' ), array_column( $rows, 7 ) );
	}

	/**
	 * A value that starts with =, +, -, @ is read as a formula by Excel. The User-Agent comes from the visitor.
	 */
	public function test_the_csv_export_values_cant_be_spreadsheet_formulas() {
		$this->add_log_entry(
			array(
				'user_agent'   => '=HYPERLINK("http://example.com","click")',
				'visitor_name' => '@SUM(1+1)',
				'post_title'   => '+1 file',
			)
		);

		$row = sdm_get_download_logs_csv_rows( '2026-03-01', '2026-03-31' )[1];

		$this->assertSame( "'=HYPERLINK(\"http://example.com\",\"click\")", $row[8] );
		$this->assertSame( "'@SUM(1+1)", $row[7] );
		$this->assertSame( "'+1 file", $row[2] );
	}

	/**
	 * @dataProvider data_csv_cells
	 */
	public function test_csv_safe_cell( $value, $expected ) {
		$this->assertSame( $expected, sdm_csv_safe_cell( $value ) );
	}

	public static function data_csv_cells() {
		return array(
			'formula'          => array( '=1+1', "'=1+1" ),
			'plus'             => array( '+1', "'+1" ),
			'minus'            => array( '-1', "'-1" ),
			'at'               => array( '@cmd', "'@cmd" ),
			'tab'              => array( "\t=1", "'\t=1" ),
			'carriage return'  => array( "\r=1", "'\r=1" ),
			'normal text'      => array( 'Manual 1.0', 'Manual 1.0' ),
			'equals inside'    => array( 'a=b', 'a=b' ),
			'URL'              => array( 'https://example.org/?a=1', 'https://example.org/?a=1' ),
			'empty'            => array( '', '' ),
			'number'           => array( 15, 15 ),
		);
	}

	// ---------------------------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------------------------

	private function counts( $rows, $key ) {
		$counts = array();
		foreach ( $rows as $row ) {
			$counts[ $row[ $key ] ] = (int) $row['cnt'];
		}
		ksort( $counts );
		return $counts;
	}

	private function create_item( $count_offset ) {
		$id = self::factory()->post->create( array( 'post_type' => 'sdm_downloads' ) );
		update_post_meta( $id, 'sdm_count_offset', $count_offset );
		return $id;
	}

	/**
	 * Adds a row to the download logs table (in March 2026 unless the test gives another date) and returns its ID.
	 */
	private function add_log_entry( $data = array() ) {
		global $wpdb;
		$data = array_merge(
			array(
				'post_id'         => 1,
				'post_title'      => 'Test download',
				'file_url'        => 'https://example.org/wp-content/uploads/test-file.zip',
				'visitor_ip'      => '192.0.2.1',
				'date_time'       => '2026-03-15 10:00:00',
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
