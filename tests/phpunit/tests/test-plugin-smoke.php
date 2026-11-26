<?php
/**
 * Basic checks that the plugin loads, activates and shows a download item in WordPress.
 */
class Test_Plugin_Smoke extends WP_UnitTestCase {

	public function test_plugin_is_loaded() {
		$this->assertTrue( class_exists( 'simpleDownloadManager' ), 'The simpleDownloadManager class is not loaded.' );
		$this->assertTrue( class_exists( 'SDM_Debug' ), 'The SDM_Debug class is not loaded.' );
		$this->assertTrue( class_exists( 'SDM_Utils_File_System_Related' ), 'The SDM_Utils_File_System_Related class is not loaded.' );
		$this->assertTrue( defined( 'WP_SIMPLE_DL_MONITOR_VERSION' ), 'The plugin version constant is not defined.' );
		$this->assertTrue( defined( 'WP_SDM_LOG_FILE' ), 'The WP_SDM_LOG_FILE constant (kept for backward compatibility) is not defined.' );
	}

	public function test_activation_creates_the_downloads_log_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sdm_downloads';

		$this->assertSame( $table_name, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ), 'The sdm_downloads table was not created.' );

		$columns = $wpdb->get_col( "DESCRIBE $table_name", 0 );
		$expected_columns = array( 'id', 'post_id', 'post_title', 'file_url', 'visitor_ip', 'date_time', 'visitor_country', 'visitor_name', 'user_agent', 'referrer_url' );
		foreach ( $expected_columns as $column ) {
			$this->assertContains( $column, $columns, "The sdm_downloads table has no '$column' column." );
		}
	}

	public function test_activation_saves_the_db_version_and_the_activation_time() {
		global $sdm_db_version;
		$this->assertSame( $sdm_db_version, get_option( 'sdm_db_version' ), 'The DB version was not saved on activation.' );
		$this->assertNotEmpty( get_option( 'sdm_plugin_activated_time' ), 'The plugin activation time was not saved.' );
	}

	/**
	 * A plugin update can run the activation again: it must not change the activation time.
	 */
	public function test_activating_again_keeps_the_activation_time() {
		update_option( 'sdm_plugin_activated_time', 1700000000 );

		sdm_install_db_table();

		$this->assertEquals( 1700000000, get_option( 'sdm_plugin_activated_time' ) );
	}

	public function test_post_type_and_taxonomies_are_registered() {
		$this->assertTrue( post_type_exists( 'sdm_downloads' ), 'The sdm_downloads post type is not registered.' );
		$this->assertTrue( taxonomy_exists( 'sdm_categories' ), 'The sdm_categories taxonomy is not registered.' );
		$this->assertTrue( taxonomy_exists( 'sdm_tags' ), 'The sdm_tags taxonomy is not registered.' );
		$this->assertTrue( is_object_in_taxonomy( 'sdm_downloads', 'sdm_categories' ) );
		$this->assertTrue( is_object_in_taxonomy( 'sdm_downloads', 'sdm_tags' ) );
	}

	/**
	 * @dataProvider data_shortcodes
	 */
	public function test_shortcode_is_registered( $shortcode ) {
		$this->assertTrue( shortcode_exists( $shortcode ), "The [$shortcode] shortcode is not registered." );
	}

	/**
	 * Every shortcode used without attributes must not cause a PHP error, warning or notice (PHPUnit turns them into failures).
	 *
	 * @dataProvider data_shortcodes
	 */
	public function test_shortcode_without_attributes_runs_without_php_errors( $shortcode ) {
		$output = do_shortcode( "[$shortcode]" );
		$this->assertIsString( $output );
	}

	public static function data_shortcodes() {
		$shortcodes = array(
			'sdm_download',
			'sdm-download',
			'sdm_download_counter',
			'sdm-download-counter',
			'sdm_latest_downloads',
			'sdm-latest-downloads',
			'sdm_popular_downloads',
			'sdm_download_link',
			'sdm_show_all_dl',
			'sdm_show_dl_from_category',
			'sdm_download_categories',
			'sdm_download_categories_list',
			'sdm_search_form',
			'sdm_show_download_info',
		);

		$data = array();
		foreach ( $shortcodes as $shortcode ) {
			$data[ $shortcode ] = array( $shortcode );
		}
		return $data;
	}

	public function test_download_shortcode_without_id_shows_an_error() {
		$output = do_shortcode( '[sdm_download]' );
		$this->assertStringContainsString( 'Please enter an ID value with this shortcode', $output );
		$this->assertStringNotContainsString( 'sdm_process_download', $output );
	}

	public function test_download_shortcode_shows_the_download_button() {
		$download_id = $this->create_download_item();

		$output = do_shortcode( '[sdm_download id="' . $download_id . '"]' );

		$this->assertStringContainsString( 'class="sdm_download', $output, 'The download button is missing.' );
		$this->assertStringContainsString( 'sdm_process_download=1&download_id=' . $download_id, $output, 'The download URL is missing.' );
		$this->assertStringContainsString( 'Download Now!', $output, 'The default button text is missing.' );
	}

	public function test_download_shortcode_uses_the_custom_button_text() {
		$download_id = $this->create_download_item();

		$output = do_shortcode( '[sdm_download id="' . $download_id . '" button_text="Get the file"]' );

		$this->assertStringContainsString( 'Get the file', $output );
	}

	public function test_download_link_shortcode_returns_the_download_url() {
		$download_id = $this->create_download_item();

		$output = do_shortcode( '[sdm_download_link id="' . $download_id . '"]' );

		$this->assertStringEndsWith( '/?sdm_process_download=1&download_id=' . $download_id, $output );
	}

	/**
	 * These checks are from the old tests (tests/PostTypeContentHandlerTest.php, removed when these tests were added).
	 */
	public function test_content_filters_are_registered() {
		$this->assertNotFalse( has_filter( 'the_title', 'filter_sdm_post_type_title' ) );
		$this->assertNotFalse( has_filter( 'the_content', 'filter_sdm_post_type_content' ) );

		foreach ( array( 'do_shortcode', 'wptexturize', 'convert_smilies', 'convert_chars', 'wpautop', 'shortcode_unautop', 'prepend_attachment' ) as $callback ) {
			$this->assertNotFalse( has_filter( 'sdm_downloads_description', $callback ), "The '$callback' filter is not added to the download description." );
		}

		$this->assertNotFalse( has_filter( 'sdm_cpt_below_download_description', 'sdm_add_ad_code_below_description' ) );
		$this->assertNotFalse( has_filter( 'sdm_fancy1_below_download_description', 'sdm_add_ad_code_below_description' ) );
	}

	public function test_title_filter_leaves_other_titles_unchanged() {
		$this->assertSame( 'Simple title', filter_sdm_post_type_title( 'Simple title', null ) );

		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'A page' ) );
		$this->assertSame( 'A page', filter_sdm_post_type_title( 'A page', $page_id ) );
	}

	public function test_single_download_page_shows_the_download_button() {
		global $post;
		$download_id = $this->create_download_item();
		$post        = get_post( $download_id );

		$output = filter_sdm_post_type_content( $post->post_content );

		$this->assertStringContainsString( '<div class="sdm_post_item">', $output );
		$this->assertStringContainsString( '<div class="sdm_download_link">', $output );
		$this->assertStringContainsString( 'sdm_process_download=1&download_id=' . $download_id, $output );
	}

	public function test_single_download_page_can_be_disabled() {
		global $post;
		$download_id = $this->create_download_item();
		update_post_meta( $download_id, 'sdm_item_disable_single_download_page', 'on' );
		$post = get_post( $download_id );

		$output = filter_sdm_post_type_content( $post->post_content );

		$this->assertStringContainsString( '<div class="sdm_post_single_download_page_disabled_msg">', $output );
		$this->assertStringNotContainsString( 'sdm_process_download', $output );
	}

	public function test_description_output_is_formatted() {
		$download_id = $this->create_download_item();
		update_post_meta( $download_id, 'sdm_description', 'A test SDM description' );

		$this->assertSame( wpautop( 'A test SDM description' ), sdm_get_item_description_output( $download_id ) );
	}

	/**
	 * Creates a published download item.
	 *
	 * @param array $meta Post meta to save (key => value).
	 *
	 * @return int The download item ID.
	 */
	private function create_download_item( $meta = array() ) {
		$download_id = self::factory()->post->create(
			array(
				'post_type'    => 'sdm_downloads',
				'post_status'  => 'publish',
				'post_title'   => 'Test download',
				'post_content' => '',
			)
		);

		$meta = array_merge( array( 'sdm_upload' => 'https://example.org/wp-content/uploads/test-file.zip' ), $meta );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $download_id, $key, $value );
		}

		return $download_id;
	}
}
