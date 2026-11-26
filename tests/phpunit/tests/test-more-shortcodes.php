<?php
/**
 * The shortcodes that the other test files don't cover: the download counter, the category and "show all" lists,
 * the categories list, the search form, the other [sdm_show_download_info] values, and the reCAPTCHA and
 * Terms and Conditions forms of [sdm_download].
 */
class Test_More_Shortcodes extends WP_UnitTestCase {

	public function tear_down() {
		$_POST = array();
		delete_option( 'sdm_advanced_options' );
		parent::tear_down();
	}

	// ---------------------------------------------------------------------------------------------
	// [sdm_download_counter]
	// ---------------------------------------------------------------------------------------------

	public function test_the_counter_without_an_id_shows_an_error() {
		$this->assertStringContainsString( 'Please enter an ID value', do_shortcode( '[sdm_download_counter]' ) );
	}

	public function test_the_counter_shows_the_download_count_with_the_offset() {
		$id = $this->create_item( 'Counted', array( 'sdm_count_offset' => 3 ) );
		$this->add_log_entries( $id, 2 );

		$output = do_shortcode( '[sdm_download_counter id="' . $id . '"]' );

		$this->assertStringContainsString( '<span class="sdm_count_number">5</span>', $output );
		$this->assertStringContainsString( 'Downloads', $output );
	}

	public function test_the_counter_says_download_for_one_download() {
		$id = $this->create_item( 'Counted once' );
		$this->add_log_entries( $id, 1 );

		$output = do_shortcode( '[sdm_download_counter id="' . $id . '"]' );

		$this->assertStringContainsString( '<span class="sdm_count_number">1</span><span class="sdm_count_string"> Download</span>', $output );
	}

	public function test_the_counter_for_all_items() {
		$first  = $this->create_item( 'First', array( 'sdm_count_offset' => 10 ) );
		$second = $this->create_item( 'Second', array( 'sdm_count_offset' => 20 ) );
		$this->add_log_entries( $first, 1 );
		$this->add_log_entries( $second, 2 );

		$output = do_shortcode( '[sdm_download_counter id="all"]' );

		$this->assertStringContainsString( '<span class="sdm_count_number">33</span>', $output );
	}

	// ---------------------------------------------------------------------------------------------
	// [sdm_show_dl_from_category] and [sdm_show_all_dl]
	// ---------------------------------------------------------------------------------------------

	public function test_the_category_list_needs_a_category() {
		$this->assertStringContainsString( 'You must enter a category slug OR a category id', do_shortcode( '[sdm_show_dl_from_category]' ) );
		$this->assertStringContainsString( 'not both', do_shortcode( '[sdm_show_dl_from_category category_slug="a" category_id="1"]' ) );
	}

	public function test_the_category_list_shows_the_items_of_the_category() {
		$term     = self::factory()->term->create( array( 'taxonomy' => 'sdm_categories', 'slug' => 'manuals', 'name' => 'Manuals' ) );
		$in       = $this->create_item( 'In the category' );
		$out      = $this->create_item( 'Not in the category' );
		$draft    = $this->create_item( 'Draft in the category', array(), 'draft' );
		wp_set_object_terms( $in, array( $term ), 'sdm_categories' );
		wp_set_object_terms( $draft, array( $term ), 'sdm_categories' );

		foreach ( array( '[sdm_show_dl_from_category category_slug="manuals"]', '[sdm_show_dl_from_category category_id="' . $term . '"]' ) as $shortcode ) {
			$output = do_shortcode( $shortcode );
			$this->assertStringContainsString( 'download_id=' . $in, $output, $shortcode );
			$this->assertStringNotContainsString( 'download_id=' . $out, $output, $shortcode );
			$this->assertStringNotContainsString( 'download_id=' . $draft, $output, $shortcode . ': a draft item is listed.' );
		}
	}

	public function test_the_category_list_of_an_empty_category_shows_a_message() {
		self::factory()->term->create( array( 'taxonomy' => 'sdm_categories', 'slug' => 'empty-category' ) );

		$this->assertStringContainsString( 'There are no download items matching this category criteria', do_shortcode( '[sdm_show_dl_from_category category_slug="empty-category"]' ) );
	}

	public function test_the_category_list_pagination_must_be_a_number() {
		$this->assertStringContainsString( 'numeric number for the "pagination" parameter', do_shortcode( '[sdm_show_dl_from_category category_slug="a" pagination="ten"]' ) );
	}

	public function test_the_category_list_with_pagination_shows_one_page() {
		$term = self::factory()->term->create( array( 'taxonomy' => 'sdm_categories', 'slug' => 'paged' ) );
		$ids  = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			$ids[ $i ] = $this->create_item( 'Paged ' . $i, array(), 'publish', '2026-01-0' . $i . ' 10:00:00' );
			wp_set_object_terms( $ids[ $i ], array( $term ), 'sdm_categories' );
		}

		$output = do_shortcode( '[sdm_show_dl_from_category category_slug="paged" pagination="2"]' );

		// Newest first (post_date DESC).
		$this->assertStringContainsString( 'download_id=' . $ids[3], $output );
		$this->assertStringContainsString( 'download_id=' . $ids[2], $output );
		$this->assertStringNotContainsString( 'download_id=' . $ids[1], $output );
		$this->assertStringContainsString( 'sdm_pagination', $output );
	}

	public function test_show_all_downloads_lists_every_published_item() {
		$first  = $this->create_item( 'First item' );
		$second = $this->create_item( 'Second item' );
		$draft  = $this->create_item( 'Draft item', array(), 'draft' );

		// A category in the shortcode is ignored: all the items are listed.
		$output = do_shortcode( '[sdm_show_all_dl category_slug="does-not-exist"]' );

		$this->assertStringContainsString( 'download_id=' . $first, $output );
		$this->assertStringContainsString( 'download_id=' . $second, $output );
		$this->assertStringNotContainsString( 'download_id=' . $draft, $output );
	}

	/**
	 * WordPress 6.0 to 6.4 pass an empty string when the shortcode has no attributes (6.5+ pass an empty array).
	 * [sdm_show_all_dl] added "show_all" to that string, which is a PHP fatal error on PHP 8.
	 */
	public function test_show_all_downloads_works_with_the_empty_string_of_older_wordpress_versions() {
		$id = $this->create_item( 'Listed item' );

		$this->assertStringContainsString( 'download_id=' . $id, sdm_handle_show_all_dl_shortcode( '' ) );
		$this->assertStringContainsString( 'You must enter a category slug', sdm_handle_category_shortcode( '' ) );
	}

	// ---------------------------------------------------------------------------------------------
	// [sdm_download_categories_list]
	// ---------------------------------------------------------------------------------------------

	public function test_the_categories_list_shows_the_categories_with_links() {
		$parent = self::factory()->term->create( array( 'taxonomy' => 'sdm_categories', 'name' => 'Parent category' ) );
		$child  = self::factory()->term->create( array( 'taxonomy' => 'sdm_categories', 'name' => 'Child category', 'parent' => $parent ) );
		wp_set_object_terms( $this->create_item( 'Item' ), array( $parent, $child ), 'sdm_categories' );

		$output = do_shortcode( '[sdm_download_categories_list]' );

		$this->assertStringContainsString( '<div class="sdm-download-categories">', $output );
		$this->assertStringContainsString( '>Parent category</a>', $output );
		$this->assertStringContainsString( '>Child category</a>', $output, 'The subcategory is missing (hierarchical is on by default).' );
		$this->assertStringContainsString( esc_url( get_term_link( $parent ) ), $output );
	}

	public function test_the_categories_list_options() {
		$parent = self::factory()->term->create( array( 'taxonomy' => 'sdm_categories', 'name' => 'Parent category' ) );
		$child  = self::factory()->term->create( array( 'taxonomy' => 'sdm_categories', 'name' => 'Child category', 'parent' => $parent ) );
		self::factory()->term->create( array( 'taxonomy' => 'sdm_categories', 'name' => 'Empty category' ) );
		wp_set_object_terms( $this->create_item( 'Item' ), array( $parent, $child ), 'sdm_categories' );

		$this->assertStringNotContainsString( 'Empty category', do_shortcode( '[sdm_download_categories_list]' ) );
		$this->assertStringContainsString( 'Empty category', do_shortcode( '[sdm_download_categories_list empty="1"]' ) );
		$this->assertStringContainsString( '<ol>', do_shortcode( '[sdm_download_categories_list numbered="1"]' ) );
		$this->assertStringContainsString( 'Parent category</a> <span>(1)</span>', do_shortcode( '[sdm_download_categories_list count="1"]' ) );
		$this->assertStringNotContainsString( 'Child category', do_shortcode( '[sdm_download_categories_list hierarchical="0"]' ) );
		$this->assertStringContainsString( '<div class="my-class">', do_shortcode( '[sdm_download_categories_list class="my-class"]' ) );
	}

	public function test_the_categories_list_escapes_the_category_names() {
		$term = self::factory()->term->create( array( 'taxonomy' => 'sdm_categories', 'name' => 'Tom & Jerry' ) );
		wp_set_object_terms( $this->create_item( 'Item' ), array( $term ), 'sdm_categories' );
		// A name with HTML (saved by a user who can post unfiltered HTML, or by code).
		global $wpdb;
		$wpdb->update( $wpdb->terms, array( 'name' => '<img src=x onerror=alert(1)>' ), array( 'term_id' => $term ) );
		clean_term_cache( $term, 'sdm_categories' );

		$output = do_shortcode( '[sdm_download_categories_list]' );

		$this->assertStringNotContainsString( '<img src=x', $output );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $output );
	}

	public function test_the_categories_list_is_empty_without_categories() {
		$this->assertSame( '<div class="sdm-download-categories"></div>', do_shortcode( '[sdm_download_categories_list]' ) );
	}

	// ---------------------------------------------------------------------------------------------
	// [sdm_search_form]
	// ---------------------------------------------------------------------------------------------

	public function test_the_search_form_is_shown() {
		$output = do_shortcode( '[sdm_search_form placeholder="Find a file" search_button_text="Go"]' );

		$this->assertStringContainsString( '<form id="sdm_search_form"', $output );
		$this->assertStringContainsString( 'name="sdm_search_term"', $output );
		$this->assertStringContainsString( 'placeholder="Find a file"', $output );
		$this->assertStringContainsString( 'value="Go"', $output );
		$this->assertStringNotContainsString( 'sdm_search_result_heading', $output );
	}

	public function test_the_search_finds_published_items_by_title_and_description() {
		$by_title       = $this->create_item( 'Product manual', array( 'sdm_description' => 'The guide.' ) );
		$by_description = $this->create_item( 'Guide', array( 'sdm_description' => 'A manual for the product.' ) );
		$other          = $this->create_item( 'Brochure', array( 'sdm_description' => 'Pictures.' ) );
		$draft          = $this->create_item( 'Draft manual', array( 'sdm_description' => 'Draft.' ), 'draft' );

		$output = $this->search( 'manual' );

		$this->assertStringContainsString( 'Showing search results for "manual"', $output );
		$this->assertStringContainsString( 'Number of items found: 2', $output );
		$this->assertStringContainsString( get_permalink( $by_title ), $output );
		$this->assertStringContainsString( get_permalink( $by_description ), $output );
		$this->assertStringNotContainsString( 'Brochure', $output );
		$this->assertStringNotContainsString( 'Draft manual', $output );
		unset( $other, $draft );
	}

	public function test_the_search_without_results_says_nothing_found() {
		$this->create_item( 'Product manual', array( 'sdm_description' => 'The guide.' ) );

		$this->assertStringContainsString( 'Nothing found for "catalog"', $this->search( 'catalog' ) );
	}

	public function test_the_search_ignores_keywords_shorter_than_3_characters() {
		$this->create_item( 'An item', array( 'sdm_description' => 'Text.' ) );

		$output = $this->search( 'an' );

		$this->assertStringContainsString( 'Nothing found', $output );
	}

	/**
	 * The search term was HTML-encoded before the search, so "Bob's" was searched as "Bob&#039;s" and found nothing.
	 * It was also added to the SQL query without a prepared statement.
	 */
	public function test_the_search_with_a_quote_works() {
		global $wpdb;
		$this->create_item( "Bob's manual", array( 'sdm_description' => 'Text.' ) );

		$suppress = $wpdb->suppress_errors( true );
		$output   = $this->search( "Bob's" );
		$wpdb->suppress_errors( $suppress );

		$this->assertSame( '', $wpdb->last_error );
		$this->assertStringContainsString( 'Number of items found: 1', $output );
	}

	public function test_search_wildcards_are_searched_as_characters() {
		$this->create_item( 'Product manual', array( 'sdm_description' => 'The guide.' ) );

		$this->assertStringContainsString( 'Nothing found', $this->search( '%%%' ) );
		$this->assertStringContainsString( 'Nothing found', $this->search( '___' ) );
	}

	public function test_the_search_term_is_escaped_in_the_page() {
		$output = $this->search( '"><script>alert(1)</script>' );

		$this->assertStringNotContainsString( '<script>alert(1)</script>', $output );
		$this->assertStringNotContainsString( 'value=""><', $output );
	}

	/**
	 * An item without a description was a PHP warning (undefined index), which fails the test.
	 */
	public function test_the_search_finds_an_item_without_a_description() {
		$id = $this->create_item( 'Manual without description' );
		// The search joins the post meta, so the item needs a meta value (every saved item has some).
		update_post_meta( $id, 'sdm_item_version', '1.0' );

		$this->assertStringContainsString( 'Number of items found: 1', $this->search( 'without' ) );
	}

	// ---------------------------------------------------------------------------------------------
	// [sdm_show_download_info] (title, description and download_url are in test-shortcode-availability.php)
	// ---------------------------------------------------------------------------------------------

	public function test_download_info_values() {
		$id = $this->create_item(
			'Info item',
			array(
				'sdm_item_file_size'   => '2 MB',
				'sdm_item_version'     => '1.2.3',
				'sdm_upload_thumbnail' => 'https://example.org/thumb.png',
				'sdm_count_offset'     => 4,
			)
		);
		$this->add_log_entries( $id, 1 );

		$this->assertSame( '2 MB', do_shortcode( '[sdm_show_download_info id="' . $id . '" download_info="file_size"]' ) );
		$this->assertSame( '1.2.3', do_shortcode( '[sdm_show_download_info id="' . $id . '" download_info="file_version"]' ) );
		$this->assertSame( '5', do_shortcode( '[sdm_show_download_info id="' . $id . '" download_info="download_count"]' ) );
		$this->assertSame( 'https://example.org/thumb.png', do_shortcode( '[sdm_show_download_info id="' . $id . '" download_info="thumbnail_url"]' ) );
		$this->assertStringContainsString( '<img class="sdm_download_thumbnail_image" src="https://example.org/thumb.png"', do_shortcode( '[sdm_show_download_info id="' . $id . '" download_info="thumbnail"]' ) );
	}

	public function test_download_info_with_missing_or_wrong_parameters_shows_an_error() {
		$id = $this->create_item( 'Info item' );

		$this->assertStringContainsString( 'you must enter a value for "id" and "download_info"', do_shortcode( '[sdm_show_download_info id="' . $id . '"]' ) );
		$this->assertStringContainsString( 'does not match any availalbe parameters', do_shortcode( '[sdm_show_download_info id="' . $id . '" download_info="nothing"]' ) );
	}

	// ---------------------------------------------------------------------------------------------
	// [sdm_download] with Terms and Conditions or reCAPTCHA
	// ---------------------------------------------------------------------------------------------

	public function test_the_download_button_has_the_terms_checkbox_when_terms_are_on() {
		update_option( 'sdm_advanced_options', array( 'termscond_enable' => 'on', 'termscond_url' => 'https://example.org/terms/' ) );
		$id = $this->create_item( 'Terms item' );

		$output = do_shortcode( '[sdm_download id="' . $id . '"]' );

		$this->assertStringContainsString( 'class="agree_termscond"', $output );
		$this->assertStringContainsString( 'href="https://example.org/terms/"', $output );
		$this->assertStringContainsString( 'sdm_download_with_condition', $output );
	}

	/**
	 * The terms page URL was printed without escaping.
	 */
	public function test_the_terms_page_url_is_escaped() {
		update_option( 'sdm_advanced_options', array( 'termscond_enable' => 'on', 'termscond_url' => 'javascript:alert(1)' ) );
		$id = $this->create_item( 'Terms item' );

		$output = do_shortcode( '[sdm_download id="' . $id . '"]' );

		$this->assertStringContainsString( 'agree_termscond', $output );
		$this->assertStringNotContainsString( 'javascript:', $output );
	}

	public function test_the_download_button_is_a_recaptcha_form_when_recaptcha_is_on() {
		update_option( 'sdm_advanced_options', array( 'recaptcha_enable' => 'on', 'recaptcha_site_key' => 'site-key', 'recaptcha_secret_key' => 'secret' ) );
		$id = $this->create_item( 'Captcha item' );

		$output = do_shortcode( '[sdm_download id="' . $id . '"]' );

		$this->assertStringContainsString( 'sdm-g-recaptcha-form', $output );
		$this->assertStringContainsString( 'class="g-recaptcha sdm-g-recaptcha"', $output );
		$this->assertStringContainsString( 'name="download_id" value="' . $id . '"', $output );
	}

	/**
	 * With "Open in a new window" on, the reCAPTCHA form's target attribute was escaped as a whole:
	 * target=&quot;_blank&quot; (the browser opened a window named "_blank" with the quotes).
	 */
	public function test_the_recaptcha_form_opens_in_a_new_window_when_the_item_is_set_to() {
		update_option( 'sdm_advanced_options', array( 'recaptcha_enable' => 'on', 'recaptcha_site_key' => 'site-key', 'recaptcha_secret_key' => 'secret' ) );
		$id = $this->create_item( 'Captcha item', array( 'sdm_item_new_window' => 'on' ) );

		$output = do_shortcode( '[sdm_download id="' . $id . '"]' );

		$this->assertMatchesRegularExpression( '/<form [^>]*class="sdm-g-recaptcha-form sdm-download-form" target="_blank">/', $output );
		$this->assertStringNotContainsString( '&quot;_blank&quot;', $output );
	}

	public function test_the_password_form_is_used_instead_of_the_recaptcha_form_for_a_password_protected_item() {
		update_option( 'sdm_advanced_options', array( 'recaptcha_enable' => 'on', 'recaptcha_site_key' => 'site-key', 'recaptcha_secret_key' => 'secret' ) );
		$id = self::factory()->post->create( array( 'post_type' => 'sdm_downloads', 'post_password' => 'secret-pass' ) );

		$output = do_shortcode( '[sdm_download id="' . $id . '"]' );

		$this->assertStringContainsString( 'pass_text', $output );
	}

	// ---------------------------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------------------------

	private function search( $term ) {
		// WordPress adds slashes to the POST data (wp_magic_quotes()).
		$_POST = wp_slash( array( 'sdm_search_term' => $term ) );
		return do_shortcode( '[sdm_search_form]' );
	}

	private function create_item( $title, $meta = array(), $status = 'publish', $date = '' ) {
		$args = array(
			'post_type'   => 'sdm_downloads',
			'post_title'  => $title,
			'post_status' => $status,
		);
		if ( $date ) {
			$args['post_date'] = $date;
		}
		$id = self::factory()->post->create( $args );
		update_post_meta( $id, 'sdm_upload', 'https://example.org/wp-content/uploads/' . sanitize_title( $title ) . '.zip' );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return $id;
	}

	private function add_log_entries( $post_id, $count ) {
		global $wpdb;
		for ( $i = 0; $i < $count; $i++ ) {
			$wpdb->insert(
				$wpdb->prefix . 'sdm_downloads',
				array(
					'post_id'         => $post_id,
					'post_title'      => 'Test download',
					'file_url'        => 'https://example.org/file.zip',
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
