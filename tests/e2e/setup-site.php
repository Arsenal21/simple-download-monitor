<?php
/**
 * Endpoint tests: creates the test data on the test site. Run with: wp eval-file tests/e2e/setup-site.php
 * Prints a JSON line with what the tests need (the last line of the output): the download item IDs, the file contents and the file URLs.
 */

//Plugin settings: debug logging (the log helps when a test fails). The runner changes the other settings when a test needs them.
update_option( 'sdm_downloads_options', array( 'enable_debug' => 'on' ) );

//A subscriber, to download as a logged-in user who can't edit the download items.
if ( ! username_exists( 'e2e_subscriber' ) ) {
	wp_insert_user(
		array(
			'user_login' => 'e2e_subscriber',
			'user_pass'  => 'e2e-subscriber-pass',
			'user_email' => 'subscriber@example.com',
			'role'       => 'subscriber',
		)
	);
}

$uploads_dir   = WP_CONTENT_DIR . '/uploads/sdm-e2e';
$uploads_url   = content_url( 'uploads/sdm-e2e' );
$protected_dir = SDM_File_Protection_Handler::get_protected_dir();
$protected_url = content_url( 'uploads/' . SDM_File_Protection_Handler::get_protected_dir_name() );
wp_mkdir_p( $uploads_dir );
wp_mkdir_p( $protected_dir );

/**
 * Creates a file with this content.
 */
function sdm_e2e_file( $path, $content ) {
	file_put_contents( $path, $content );
	return $content;
}

$contents = array(
	'public'           => sdm_e2e_file( $uploads_dir . '/public-file.txt', 'SDM-E2E-PUBLIC-FILE' ),
	'protected'        => sdm_e2e_file( $protected_dir . '/protected-file.txt', 'SDM-E2E-PROTECTED-FILE' ),
	'hidden'           => sdm_e2e_file( $uploads_dir . '/.hidden-file', 'SDM-E2E-HIDDEN-FILE' ),
	'no_extension'     => sdm_e2e_file( $uploads_dir . '/README', 'SDM-E2E-NO-EXTENSION-FILE' ),
	'bak'              => sdm_e2e_file( $uploads_dir . '/backup.bak', 'SDM-E2E-BAK-FILE' ),
	'phtml'            => sdm_e2e_file( $uploads_dir . '/script.phtml', 'SDM-E2E-PHTML-FILE' ),
	'protected_phtml'  => sdm_e2e_file( $protected_dir . '/script.phtml', 'SDM-E2E-PROTECTED-PHTML-FILE' ),
	'protected_hidden' => sdm_e2e_file( $protected_dir . '/.hidden-file', 'SDM-E2E-PROTECTED-HIDDEN-FILE' ),
	//Outside the site's folder (the web root), so only a path traversal through the plugin can read it.
	'canary'           => sdm_e2e_file( dirname( rtrim( ABSPATH, '/' ) ) . '/sdm-e2e-canary.sql', 'SDM-E2E-CANARY-SECRET' ),
	//In the uploads folder, but outside the protected folder.
	'outside_protected' => sdm_e2e_file( WP_CONTENT_DIR . '/uploads/sdm-e2e-outside-protected.txt', 'SDM-E2E-OUTSIDE-PROTECTED-FILE' ),
);

$urls = array(
	'public'           => $uploads_url . '/public-file.txt',
	'protected'        => $protected_url . '/protected-file.txt',
	'hidden'           => $uploads_url . '/.hidden-file',
	'no_extension'     => $uploads_url . '/README',
	'bak'              => $uploads_url . '/backup.bak',
	'phtml'            => $uploads_url . '/script.phtml',
	'protected_phtml'  => $protected_url . '/script.phtml',
	'protected_hidden' => $protected_url . '/.hidden-file',
	'external'         => 'https://downloads.example.com/file.zip',
	//The report (CVE-2026-135625): a URL that contains "uploads/sdm-uploads" and then goes up out of wp-content.
	//The site is in /tmp/e2e-site, so 4 levels up from uploads/sdm-uploads is /tmp, where the canary file is.
	'traversal_protected'         => $protected_url . '/../../../../sdm-e2e-canary.sql',
	'traversal_protected_encoded' => $protected_url . '/%2e%2e/%2e%2e/%2e%2e/%2e%2e/sdm-e2e-canary.sql',
	'traversal_to_uploads'        => $protected_url . '/../sdm-e2e-outside-protected.txt',
	//The same for PHP dispatch (it only checked that the URL starts with the wp-content URL).
	'traversal_dispatch'          => content_url() . '/../../sdm-e2e-canary.sql',
);

/**
 * Creates a download item. The file URL is saved directly in the post meta (like a value saved by an older version,
 * or by code that doesn't use the editor), so the download request handler's own checks are tested.
 */
function sdm_e2e_item( $title, $file_url, $meta = array(), $post = array() ) {
	$id = wp_insert_post(
		array_merge(
			array(
				'post_type'   => 'sdm_downloads',
				'post_status' => 'publish',
				'post_title'  => $title,
			),
			$post
		)
	);
	if ( '' !== $file_url ) {
		update_post_meta( $id, 'sdm_upload', $file_url );
	}
	update_post_meta( $id, 'sdm_count_offset', 0 );
	foreach ( $meta as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}
	return $id;
}

$dispatch = array( 'sdm_item_dispatch' => '1' );

$ids = array(
	'redirect'                    => sdm_e2e_item( 'E2E Redirect', $urls['public'] ),
	'dispatch'                    => sdm_e2e_item( 'E2E PHP Dispatch', $urls['public'], $dispatch ),
	'external_dispatch'           => sdm_e2e_item( 'E2E External File', $urls['external'], $dispatch ),
	'protected'                   => sdm_e2e_item( 'E2E Protected', $urls['protected'] ),
	'password'                    => sdm_e2e_item( 'E2E Password', $urls['public'], array(), array( 'post_password' => 'e2e-pass' ) ),
	'draft'                       => sdm_e2e_item( 'E2E Draft', $urls['public'], array(), array( 'post_status' => 'draft' ) ),
	'private'                     => sdm_e2e_item( 'E2E Private', $urls['public'], array(), array( 'post_status' => 'private' ) ),
	'no_file'                     => sdm_e2e_item( 'E2E No File', '' ),
	'anonymous'                   => sdm_e2e_item( 'E2E Anonymous Can Download', $urls['public'], array( 'sdm_item_anonymous_can_download' => '1' ) ),
	'no_log'                      => sdm_e2e_item( 'E2E No Log', $urls['public'], array( 'sdm_item_no_log' => 'on' ) ),
	'hidden_dispatch'             => sdm_e2e_item( 'E2E Hidden File', $urls['hidden'], $dispatch ),
	'no_extension_dispatch'       => sdm_e2e_item( 'E2E No Extension', $urls['no_extension'], $dispatch ),
	'bak_dispatch'                => sdm_e2e_item( 'E2E Bak File', $urls['bak'], $dispatch ),
	'phtml_dispatch'              => sdm_e2e_item( 'E2E Phtml File', $urls['phtml'], $dispatch ),
	'protected_phtml'             => sdm_e2e_item( 'E2E Protected Phtml', $urls['protected_phtml'] ),
	'protected_hidden'            => sdm_e2e_item( 'E2E Protected Hidden', $urls['protected_hidden'] ),
	'traversal_protected'         => sdm_e2e_item( 'E2E Traversal Protected', $urls['traversal_protected'] ),
	'traversal_protected_dispatch' => sdm_e2e_item( 'E2E Traversal Protected Dispatch', $urls['traversal_protected'], $dispatch ),
	'traversal_protected_encoded' => sdm_e2e_item( 'E2E Traversal Protected Encoded', $urls['traversal_protected_encoded'] ),
	'traversal_to_uploads'        => sdm_e2e_item( 'E2E Traversal To Uploads', $urls['traversal_to_uploads'] ),
	'traversal_dispatch'          => sdm_e2e_item( 'E2E Traversal Dispatch', $urls['traversal_dispatch'], $dispatch ),
);

echo wp_json_encode(
	array(
		'ids'      => $ids,
		'contents' => $contents,
		'urls'     => $urls,
	)
) . "\n";
