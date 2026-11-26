<?php
/**
 * Saving the "Downloadable File" URL in the download item editor (SDM_Admin_Edit_Download::save_post_handler()).
 *
 * Security report of November 2026 (CVE-2026-135625, fixed in 4.1.6): a file URL with "../" was saved as entered
 * (esc_url_raw() keeps it). Such a URL is not saved now, and the editor shows an error notice.
 */
class Test_Edit_Download_Save extends WP_UnitTestCase {

	/**
	 * @var SDM_Admin_Edit_Download
	 */
	private $editor;

	public static function set_up_before_class() {
		parent::set_up_before_class();
		// The editor class is only loaded on admin pages. Loading it also adds its hooks.
		require_once WP_SIMPLE_DL_MONITOR_PATH . 'includes/admin-side/sdm-admin-edit-download.php';
	}

	public function set_up() {
		parent::set_up();
		// An instance without running the constructor, so its hooks are not added again.
		$this->editor = ( new ReflectionClass( 'SDM_Admin_Edit_Download' ) )->newInstanceWithoutConstructor();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function tear_down() {
		$_POST    = array();
		$_REQUEST = array();
		parent::tear_down();
	}

	public function test_a_normal_file_url_is_saved() {
		$download_id = $this->create_download_item( 'https://example.org/wp-content/uploads/old-file.zip' );

		$this->save( $download_id, 'https://example.org/wp-content/uploads/2026/11/new-file.zip' );

		$this->assertSame( 'https://example.org/wp-content/uploads/2026/11/new-file.zip', get_post_meta( $download_id, 'sdm_upload', true ) );
		$this->assertFalse( get_transient( $this->notice_transient() ), 'The error notice was set for a normal URL.' );
	}

	/**
	 * @dataProvider data_traversal_urls
	 */
	public function test_a_file_url_with_path_traversal_is_not_saved( $url ) {
		$download_id = $this->create_download_item( 'https://example.org/wp-content/uploads/old-file.zip' );

		$this->save( $download_id, $url );

		$this->assertSame( 'https://example.org/wp-content/uploads/old-file.zip', get_post_meta( $download_id, 'sdm_upload', true ), 'The previous file URL was not kept.' );
		$this->assertNotEmpty( get_transient( $this->notice_transient() ), 'The error notice was not set.' );
	}

	public static function data_traversal_urls() {
		return array(
			'the report'       => array( 'https://example.org/wp-content/uploads/sdm-uploads/../../../wp-config.php.bak' ),
			'URL-encoded dots' => array( 'https://example.org/wp-content/uploads/sdm-uploads/%2e%2e/%2e%2e/%2e%2e/dump.sql' ),
			'a "." segment'    => array( 'https://example.org/wp-content/./uploads/file.zip' ),
		);
	}

	/**
	 * "../" in the query string is not a path, so the URL is saved.
	 */
	public function test_dots_in_the_query_string_are_allowed() {
		$download_id = $this->create_download_item();

		$this->save( $download_id, 'https://example.com/download?file=../file.zip' );

		$this->assertSame( 'https://example.com/download?file=../file.zip', get_post_meta( $download_id, 'sdm_upload', true ) );
	}

	public function test_the_error_notice_is_shown_once() {
		$download_id = $this->create_download_item();
		$this->save( $download_id, 'https://example.org/wp-content/uploads/sdm-uploads/../../../dump.sql' );

		ob_start();
		$this->editor->show_rejected_upload_url_notice();
		$first = ob_get_clean();

		ob_start();
		$this->editor->show_rejected_upload_url_notice();
		$second = ob_get_clean();

		$this->assertStringContainsString( 'notice-error', $first );
		$this->assertStringContainsString( 'was not saved', $first );
		$this->assertSame( '', $second, 'The notice was shown again.' );
	}

	public function test_a_save_without_the_nonce_is_rejected() {
		$download_id = $this->create_download_item( 'https://example.org/wp-content/uploads/old-file.zip' );
		$_POST       = array(
			'action'     => 'editpost',
			'sdm_upload' => 'https://example.org/wp-content/uploads/new-file.zip',
		);
		$_REQUEST    = $_POST;

		$this->expectException( WPDieException::class );
		$this->editor->save_post_handler( $download_id, get_post( $download_id ), true );
	}

	// ---------------------------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------------------------

	/**
	 * Sends the editor form (with a valid nonce) for the download item.
	 */
	private function save( $download_id, $file_url ) {
		$_POST    = array(
			'action'                  => 'editpost',
			'sdm_admin_edit_download' => wp_create_nonce( 'sdm_admin_edit_download_' . $download_id ),
			'sdm_upload'              => $file_url,
		);
		$_REQUEST = $_POST;

		$this->editor->save_post_handler( $download_id, get_post( $download_id ), true );
	}

	private function notice_transient() {
		return 'sdm_upload_url_rejected_' . get_current_user_id();
	}

	private function create_download_item( $file_url = '' ) {
		$download_id = self::factory()->post->create(
			array(
				'post_type'   => 'sdm_downloads',
				'post_status' => 'publish',
				'post_title'  => 'Test download',
			)
		);
		if ( $file_url ) {
			update_post_meta( $download_id, 'sdm_upload', $file_url );
		}
		return $download_id;
	}
}
