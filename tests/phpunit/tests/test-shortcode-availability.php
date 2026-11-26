<?php
/**
 * The shortcodes don't show download items that visitors can't access (private, draft, pending, scheduled).
 *
 * Fixed in 4.1.3 ([sdm_show_download_info] uses the same availability check as [sdm_download]) and
 * 4.1.4 ([sdm_popular_downloads] only lists published items). Both follow the "Allow downloads of unpublished items" option.
 */
class Test_Shortcode_Availability extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( 'sdm_downloads_options' );
		parent::tear_down();
	}

	// ---------------------------------------------------------------------------------------------
	// [sdm_download]
	// ---------------------------------------------------------------------------------------------

	public function test_download_shortcode_shows_a_published_item() {
		$download_id = $this->create_download_item( 'publish', 'Published item' );

		$output = do_shortcode( '[sdm_download id="' . $download_id . '"]' );

		$this->assertStringContainsString( 'sdm_process_download=1&download_id=' . $download_id, $output );
	}

	/**
	 * @dataProvider data_unavailable_statuses
	 */
	public function test_download_shortcode_hides_an_unavailable_item( $status ) {
		$download_id = $this->create_download_item( $status, 'Hidden item' );

		$output = do_shortcode( '[sdm_download id="' . $download_id . '"]' );

		$this->assertStringContainsString( 'This download item is not available', $output );
		$this->assertStringNotContainsString( 'sdm_process_download', $output );
	}

	public function test_download_shortcode_shows_an_unavailable_item_to_a_user_who_can_edit_it() {
		$download_id = $this->create_download_item( 'draft', 'Draft item' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$output = do_shortcode( '[sdm_download id="' . $download_id . '"]' );

		$this->assertStringContainsString( 'sdm_process_download=1&download_id=' . $download_id, $output );
	}

	public function test_download_shortcode_shows_an_unavailable_item_when_the_option_allows_it() {
		$this->allow_unpublished_items();
		$download_id = $this->create_download_item( 'private', 'Private item' );

		$output = do_shortcode( '[sdm_download id="' . $download_id . '"]' );

		$this->assertStringContainsString( 'sdm_process_download=1&download_id=' . $download_id, $output );
	}

	public function test_download_shortcode_rejects_an_id_that_is_not_a_download_item() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'A page' ) );

		$output = do_shortcode( '[sdm_download id="' . $page_id . '"]' );

		$this->assertStringContainsString( 'Incorrect download item id', $output );
		$this->assertStringNotContainsString( 'sdm_process_download', $output );
	}

	// ---------------------------------------------------------------------------------------------
	// [sdm_show_download_info] (4.1.3)
	// ---------------------------------------------------------------------------------------------

	public function test_download_info_shortcode_shows_a_published_item() {
		$download_id = $this->create_download_item( 'publish', 'Published item' );
		update_post_meta( $download_id, 'sdm_item_version', '2.5' );

		$this->assertSame( 'Published item', do_shortcode( '[sdm_show_download_info id="' . $download_id . '" download_info="title"]' ) );
		$this->assertSame( '2.5', do_shortcode( '[sdm_show_download_info id="' . $download_id . '" download_info="file_version"]' ) );
	}

	/**
	 * @dataProvider data_unavailable_statuses
	 */
	public function test_download_info_shortcode_hides_an_unavailable_item( $status ) {
		$download_id = $this->create_download_item( $status, 'Secret item title' );
		update_post_meta( $download_id, 'sdm_description', 'Secret description' );

		foreach ( array( 'title', 'description', 'download_url' ) as $info ) {
			$output = do_shortcode( '[sdm_show_download_info id="' . $download_id . '" download_info="' . $info . '"]' );

			$this->assertStringContainsString( 'This download item is not available', $output, "The $info of a $status item is not hidden." );
			$this->assertStringNotContainsString( 'Secret', $output );
			$this->assertStringNotContainsString( 'secret-file.zip', $output );
		}
	}

	/**
	 * The download_url info returns the standard download URL (not the file URL) when the download request handler has checks to run.
	 */
	public function test_download_info_url_of_a_password_protected_item_is_the_standard_download_url() {
		$download_id = $this->create_download_item( 'publish', 'Protected item' );
		wp_update_post( array( 'ID' => $download_id, 'post_password' => 'secret' ) );

		$output = do_shortcode( '[sdm_show_download_info id="' . $download_id . '" download_info="download_url"]' );

		$this->assertStringContainsString( 'sdm_process_download=1', $output );
		$this->assertStringNotContainsString( 'secret-file.zip', $output );
	}

	public function test_download_info_url_of_a_normal_item_is_the_file_url() {
		$download_id = $this->create_download_item( 'publish', 'Normal item' );

		$output = do_shortcode( '[sdm_show_download_info id="' . $download_id . '" download_info="download_url"]' );

		$this->assertSame( 'https://example.org/wp-content/uploads/secret-file.zip', $output );
	}

	// ---------------------------------------------------------------------------------------------
	// [sdm_popular_downloads] (4.1.4) and [sdm_latest_downloads]
	// ---------------------------------------------------------------------------------------------

	public function test_popular_downloads_lists_only_published_items() {
		$published_id = $this->create_download_item( 'publish', 'Published popular item' );
		$this->add_downloads( $published_id, 2 );
		foreach ( $this->unavailable_statuses() as $status ) {
			$hidden_id = $this->create_download_item( $status, "Hidden $status item" );
			$this->add_downloads( $hidden_id, 5 );
		}

		$output = do_shortcode( '[sdm_popular_downloads number="10"]' );

		$this->assertStringContainsString( 'Published popular item', $output );
		foreach ( $this->unavailable_statuses() as $status ) {
			$this->assertStringNotContainsString( "Hidden $status item", $output, "A $status item is listed." );
		}
	}

	public function test_popular_downloads_lists_unpublished_items_when_the_option_allows_it() {
		$this->allow_unpublished_items();
		$private_id = $this->create_download_item( 'private', 'Private popular item' );
		$this->add_downloads( $private_id, 3 );
		$trashed_id = $this->create_download_item( 'trash', 'Trashed popular item' );
		$this->add_downloads( $trashed_id, 3 );

		$output = do_shortcode( '[sdm_popular_downloads number="10"]' );

		$this->assertStringContainsString( 'Private popular item', $output );
		$this->assertStringNotContainsString( 'Trashed popular item', $output, 'A trashed item is listed.' );
	}

	public function test_latest_downloads_lists_only_published_items() {
		$this->create_download_item( 'publish', 'Published latest item' );
		foreach ( $this->unavailable_statuses() as $status ) {
			$this->create_download_item( $status, "Hidden $status item" );
		}

		$output = do_shortcode( '[sdm_latest_downloads number="10"]' );

		$this->assertStringContainsString( 'Published latest item', $output );
		foreach ( $this->unavailable_statuses() as $status ) {
			$this->assertStringNotContainsString( "Hidden $status item", $output, "A $status item is listed." );
		}
	}

	// ---------------------------------------------------------------------------------------------
	// [sdm_download_categories]
	// ---------------------------------------------------------------------------------------------

	/**
	 * It declared a function inside the shortcode handler, so a second use on the same page was a PHP fatal error (fixed with these tests).
	 */
	public function test_download_categories_shortcode_can_be_used_twice() {
		$parent = self::factory()->term->create( array( 'taxonomy' => 'sdm_categories', 'name' => 'Parent category' ) );
		self::factory()->term->create( array( 'taxonomy' => 'sdm_categories', 'name' => 'Child category', 'parent' => $parent ) );

		$first  = do_shortcode( '[sdm_download_categories]' );
		$second = do_shortcode( '[sdm_download_categories]' );

		$this->assertSame( $first, $second );
		$this->assertStringContainsString( 'Parent category', $first );
		$this->assertStringContainsString( 'Child category', $first );
		$this->assertStringContainsString( 'class="sdm_object_tree"', $first );
	}

	public function test_download_categories_shortcode_without_categories_shows_an_empty_tree() {
		$this->assertSame( '<div class="sdm_object_tree"></div>', do_shortcode( '[sdm_download_categories]' ) );
	}

	// ---------------------------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------------------------

	public static function data_unavailable_statuses() {
		$data = array();
		foreach ( self::unavailable_statuses() as $status ) {
			$data[ $status ] = array( $status );
		}
		return $data;
	}

	private static function unavailable_statuses() {
		return array( 'private', 'draft', 'pending', 'future' );
	}

	private function allow_unpublished_items() {
		update_option( 'sdm_downloads_options', array( 'general_allow_downloads_of_unpublished_items' => 'on' ) );
	}

	private function create_download_item( $status, $title ) {
		$post = array(
			'post_type'   => 'sdm_downloads',
			'post_status' => $status,
			'post_title'  => $title,
		);
		if ( 'future' === $status ) {
			$post['post_date'] = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );
		}
		$download_id = self::factory()->post->create( $post );
		update_post_meta( $download_id, 'sdm_upload', 'https://example.org/wp-content/uploads/secret-file.zip' );
		update_post_meta( $download_id, 'sdm_count_offset', 0 );
		return $download_id;
	}

	/**
	 * Adds download log entries for the item (the popular downloads are counted from the log).
	 */
	private function add_downloads( $download_id, $count ) {
		global $wpdb;
		for ( $i = 0; $i < $count; $i++ ) {
			$wpdb->insert(
				$wpdb->prefix . 'sdm_downloads',
				array(
					'post_id'         => $download_id,
					'post_title'      => get_the_title( $download_id ),
					'file_url'        => 'https://example.org/wp-content/uploads/secret-file.zip',
					'visitor_ip'      => '192.0.2.1',
					'date_time'       => current_time( 'mysql' ),
					'visitor_country' => 'US',
					'visitor_name'    => 'visitor',
					'user_agent'      => 'Test agent',
					'referrer_url'    => 'https://example.org/',
				)
			);
		}
	}
}
