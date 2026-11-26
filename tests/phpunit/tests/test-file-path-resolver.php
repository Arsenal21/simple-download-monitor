<?php
/**
 * The file path resolver used before a file is sent with PHP (SDM_Utils_File_System_Related::get_uploaded_file_path_from_url())
 * and the file checks that run on the resolved path.
 *
 * Security report of November 2026 (CVE-2026-135625, fixed in 4.1.6): a download URL such as
 * .../wp-content/uploads/sdm-uploads/../../../file.sql resolved to a file outside wp-content, and that file was sent to any visitor.
 */
class Test_File_Path_Resolver extends WP_UnitTestCase {

	/**
	 * Files created by the test (deleted in tear_down).
	 *
	 * @var string[]
	 */
	private $created_files = array();

	/**
	 * Folders created by the test (deleted in tear_down, if empty).
	 *
	 * @var string[]
	 */
	private $created_dirs = array();

	public function tear_down() {
		foreach ( $this->created_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
		foreach ( array_reverse( $this->created_dirs ) as $dir ) {
			if ( is_dir( $dir ) ) {
				@rmdir( $dir );
			}
		}
		$this->created_files = array();
		$this->created_dirs  = array();
		delete_option( 'sdm_downloads_options' );
		parent::tear_down();
	}

	// ---------------------------------------------------------------------------------------------
	// get_uploaded_file_path_from_url() for the protected folder (uploads/sdm-uploads)
	// ---------------------------------------------------------------------------------------------

	public function test_a_file_in_the_protected_folder_resolves_to_its_path() {
		$file = $this->create_file( $this->protected_dir() . '/test-file.zip' );

		$this->assertSame( realpath( $file ), $this->resolve_protected( $this->protected_url() . '/test-file.zip' ) );
	}

	public function test_a_file_in_a_subfolder_of_the_protected_folder_resolves_to_its_path() {
		$file = $this->create_file( $this->protected_dir() . '/sub/test-file.zip' );

		$this->assertSame( realpath( $file ), $this->resolve_protected( $this->protected_url() . '/sub/test-file.zip' ) );
	}

	/**
	 * Items saved before a site moved from http to https (or the other way) must keep working.
	 */
	public function test_the_url_scheme_is_ignored() {
		$file = $this->create_file( $this->protected_dir() . '/test-file.zip' );
		$url  = $this->protected_url() . '/test-file.zip';

		$https_url = preg_replace( '#^http:#i', 'https:', $url );
		$http_url  = preg_replace( '#^https:#i', 'http:', $url );

		$this->assertSame( realpath( $file ), $this->resolve_protected( $https_url ), 'The https URL was not resolved.' );
		$this->assertSame( realpath( $file ), $this->resolve_protected( $http_url ), 'The http URL was not resolved.' );
	}

	/**
	 * @dataProvider data_traversal_urls
	 */
	public function test_a_path_traversal_url_is_rejected( $url_suffix ) {
		// A file outside wp-content (in the WordPress root), like the database dump in the report.
		$this->create_file( ABSPATH . 'sdm-test-canary.sql' );
		// A file in the uploads folder, but outside the protected folder.
		$this->create_file( WP_CONTENT_DIR . '/uploads/sdm-test-outside-protected.zip' );

		$this->assertFalse( $this->resolve_protected( $this->protected_url() . $url_suffix ) );
	}

	public static function data_traversal_urls() {
		return array(
			'to the WordPress root (the report)'    => array( '/../../../sdm-test-canary.sql' ),
			'to the uploads folder'                 => array( '/../sdm-test-outside-protected.zip' ),
			'from a subfolder'                      => array( '/sub/../../../../sdm-test-canary.sql' ),
			'URL-encoded dots'                      => array( '/%2e%2e/%2e%2e/%2e%2e/sdm-test-canary.sql' ),
			'URL-encoded dots, upper case'          => array( '/%2E%2E/%2E%2E/%2E%2E/sdm-test-canary.sql' ),
			'double URL-encoded dots'               => array( '/%252e%252e/%252e%252e/%252e%252e/sdm-test-canary.sql' ),
			'URL-encoded slashes'                   => array( '/..%2f..%2f..%2fsdm-test-canary.sql' ),
			'backslashes'                           => array( '/..\\..\\..\\sdm-test-canary.sql' ),
			'a "." segment'                         => array( '/./../sdm-test-outside-protected.zip' ),
		);
	}

	public function test_a_url_that_does_not_start_with_the_protected_folder_url_is_rejected() {
		$this->create_file( $this->protected_dir() . '/test-file.zip' );

		// Contains "uploads/sdm-uploads" (which passed the old check), but on another site.
		$this->assertFalse( $this->resolve_protected( 'https://attacker.example/x/wp-content/uploads/' . $this->protected_dir_name() . '/test-file.zip' ) );
		// Another folder on this site that ends with the protected folder name.
		$this->assertFalse( $this->resolve_protected( WP_CONTENT_URL . '/plugins/uploads/' . $this->protected_dir_name() . '/test-file.zip' ) );
	}

	public function test_a_missing_file_is_rejected() {
		$this->assertFalse( $this->resolve_protected( $this->protected_url() . '/does-not-exist.zip' ) );
	}

	/**
	 * The protected folder itself is not a file inside it.
	 */
	public function test_the_protected_folder_itself_is_rejected() {
		wp_mkdir_p( $this->protected_dir() );

		$this->assertFalse( $this->resolve_protected( $this->protected_url() . '/' ) );
		$this->assertFalse( $this->resolve_protected( $this->protected_url() ) );
	}

	public function test_an_empty_or_invalid_url_is_rejected() {
		$this->assertFalse( $this->resolve_protected( '' ) );
		$this->assertFalse( $this->resolve_protected( null ) );
		$this->assertFalse( $this->resolve_protected( array( 'not a URL' ) ) );
	}

	// ---------------------------------------------------------------------------------------------
	// get_uploaded_file_path_from_url() for wp-content (used by PHP dispatch)
	// ---------------------------------------------------------------------------------------------

	public function test_a_file_in_the_uploads_folder_resolves_to_its_path() {
		$file = $this->create_file( WP_CONTENT_DIR . '/uploads/2026/11/test-file.pdf' );

		$this->assertSame( realpath( $file ), SDM_Utils_File_System_Related::get_uploaded_file_path_from_url( WP_CONTENT_URL . '/uploads/2026/11/test-file.pdf' ) );
	}

	/**
	 * The PHP dispatch path had the same problem (it only checked that the URL starts with the wp-content URL).
	 */
	public function test_a_path_traversal_url_outside_wp_content_is_rejected() {
		$this->create_file( ABSPATH . 'sdm-test-canary.sql' );

		$this->assertFalse( SDM_Utils_File_System_Related::get_uploaded_file_path_from_url( WP_CONTENT_URL . '/../sdm-test-canary.sql' ) );
		$this->assertFalse( SDM_Utils_File_System_Related::get_uploaded_file_path_from_url( WP_CONTENT_URL . '/uploads/../../sdm-test-canary.sql' ) );
		$this->assertFalse( SDM_Utils_File_System_Related::get_uploaded_file_path_from_url( WP_CONTENT_URL . '/uploads/%2e%2e/%2e%2e/sdm-test-canary.sql' ) );
	}

	public function test_an_external_url_is_rejected() {
		$this->assertFalse( SDM_Utils_File_System_Related::get_uploaded_file_path_from_url( 'https://cdn.example.com/wp-content/uploads/test-file.pdf' ) );
	}

	// ---------------------------------------------------------------------------------------------
	// path_has_traversal()
	// ---------------------------------------------------------------------------------------------

	/**
	 * @dataProvider data_path_has_traversal
	 */
	public function test_path_has_traversal( $path, $expected ) {
		$this->assertSame( $expected, SDM_Utils_File_System_Related::path_has_traversal( $path ) );
	}

	public static function data_path_has_traversal() {
		return array(
			'normal path'                       => array( '/wp-content/uploads/2026/11/file.zip', false ),
			'dots in a file name'               => array( '/wp-content/uploads/file..zip', false ),
			'dots at the start of a file name'  => array( '/wp-content/uploads/..file.zip', false ),
			'three dots'                        => array( '/wp-content/uploads/.../file.zip', false ),
			'hidden file'                       => array( '/wp-content/uploads/.htaccess', false ),
			'empty'                             => array( '', false ),
			'null'                              => array( null, false ),
			'parent segment'                    => array( '/wp-content/uploads/../file.zip', true ),
			'current segment'                   => array( '/wp-content/./uploads/file.zip', true ),
			'ends with a parent segment'        => array( '/wp-content/uploads/..', true ),
			'starts with a parent segment'      => array( '../file.zip', true ),
			'encoded'                           => array( '/wp-content/uploads/%2e%2e/file.zip', true ),
			'double encoded'                    => array( '/wp-content/uploads/%252e%252e/file.zip', true ),
			'backslashes'                       => array( '/wp-content/uploads\\..\\file.zip', true ),
			'in the query string only'          => array( '/wp-content/uploads/file.zip?x=../y', false ),
		);
	}

	// ---------------------------------------------------------------------------------------------
	// File checks on the resolved path
	// ---------------------------------------------------------------------------------------------

	/**
	 * @dataProvider data_always_blocked_extensions
	 */
	public function test_php_type_extensions_are_always_blocked( $file_name ) {
		// Even when the site saved its own list of blocked extensions (without the PHP ones).
		update_option( 'sdm_downloads_options', array( 'general_disallowed_file_ext_dispatch' => 'exe' ) );

		$this->assertFalse( SDM_Utils_File_System_Related::check_is_file_extension_allowed( '/var/www/file/' . $file_name ) );
	}

	public static function data_always_blocked_extensions() {
		$data = array();
		foreach ( array( 'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phtml', 'phar', 'pht', 'PHP', 'PhTmL' ) as $ext ) {
			$data[ $ext ] = array( 'file.' . $ext );
		}
		return $data;
	}

	public function test_the_default_blocked_extensions_are_used_when_none_are_saved() {
		foreach ( array( 'config', 'bak' ) as $ext ) {
			$this->assertFalse( SDM_Utils_File_System_Related::check_is_file_extension_allowed( '/var/www/file.' . $ext ), "The .$ext extension is not blocked by default." );
		}
		$this->assertTrue( SDM_Utils_File_System_Related::check_is_file_extension_allowed( '/var/www/file.pdf' ) );
		$this->assertTrue( SDM_Utils_File_System_Related::check_is_file_extension_allowed( '/var/www/file.zip' ) );
	}

	public function test_the_saved_blocked_extensions_are_used() {
		update_option( 'sdm_downloads_options', array( 'general_disallowed_file_ext_dispatch' => 'exe, ZIP' ) );

		$this->assertFalse( SDM_Utils_File_System_Related::check_is_file_extension_allowed( '/var/www/file.exe' ) );
		$this->assertFalse( SDM_Utils_File_System_Related::check_is_file_extension_allowed( '/var/www/file.zip' ), 'The saved list is not case-insensitive.' );
		$this->assertTrue( SDM_Utils_File_System_Related::check_is_file_extension_allowed( '/var/www/file.pdf' ) );
	}

	public function test_hidden_files_and_files_without_an_extension_are_detected() {
		$this->assertTrue( SDM_Utils_File_System_Related::check_is_hidden_or_no_extension_file( '/var/www/.htaccess' ) );
		$this->assertTrue( SDM_Utils_File_System_Related::check_is_hidden_or_no_extension_file( '/var/www/README' ) );
		$this->assertFalse( SDM_Utils_File_System_Related::check_is_hidden_or_no_extension_file( '/var/www/file.pdf' ) );
	}

	// ---------------------------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------------------------

	private function resolve_protected( $url ) {
		return SDM_Utils_File_System_Related::get_uploaded_file_path_from_url( $url, array( 'uploads', $this->protected_dir_name() ) );
	}

	private function protected_dir_name() {
		return SDM_File_Protection_Handler::get_protected_dir_name();
	}

	private function protected_dir() {
		return WP_CONTENT_DIR . '/uploads/' . $this->protected_dir_name();
	}

	private function protected_url() {
		return WP_CONTENT_URL . '/uploads/' . $this->protected_dir_name();
	}

	/**
	 * Creates a file (and its folders) that is deleted after the test.
	 */
	private function create_file( $path ) {
		$dir = dirname( $path );
		if ( ! is_dir( $dir ) ) {
			// Remember each new folder, so it can be removed after the test.
			$missing = array();
			for ( $d = $dir; ! is_dir( $d ); $d = dirname( $d ) ) {
				$missing[] = $d;
			}
			wp_mkdir_p( $dir );
			$this->created_dirs = array_merge( $this->created_dirs, array_reverse( $missing ) );
		}
		file_put_contents( $path, 'test content' );
		$this->created_files[] = $path;
		return $path;
	}
}
