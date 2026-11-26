<?php
/**
 * Endpoint tests: sends download requests (/?sdm_process_download=1&download_id=N) to a running test site, then checks the response
 * (redirect, file content, error message) and the download logs table.
 * Run by the GitHub Actions "Plugin Tests" workflow (the "Endpoint tests" job sets up the site). Environment variables:
 * E2E_URL (example: http://127.0.0.1:8080), E2E_WP_PATH (the site's folder), E2E_DB_HOST, E2E_DB_NAME, E2E_DB_USER, E2E_DB_PASS.
 *
 * The download request is public (no login is needed), and it can send files from the server. The path traversal report of
 * November 2026 (CVE-2026-135625, fixed in 4.1.6) was about this request: a crafted file URL sent a file from outside wp-content.
 */
$url        = rtrim( getenv( 'E2E_URL' ), '/' );
$wp_path    = getenv( 'E2E_WP_PATH' );
$db         = new mysqli( getenv( 'E2E_DB_HOST' ), getenv( 'E2E_DB_USER' ), getenv( 'E2E_DB_PASS' ), getenv( 'E2E_DB_NAME' ) );
$prefix     = 'wp_';
$failures   = 0;
$known      = 0;
$cookie_jar = null;

//All the requests send this User-Agent (unless a test sends another one). It is saved in the download log.
const E2E_USER_AGENT = 'SDM-E2E-Tests/1.0';

//Known issues that aren't fixed (see "Known issues" in tests/TESTS.md). Their checks still run, but don't fail the tests:
//the output shows "KNOWN ISSUE (still happens)" or "KNOWN ISSUE NOW PASSES". The commit that fixes one removes it from this list,
//so its check becomes a normal check. There are none at the moment. Example: 'short-issue-id' => 'What is wrong and what it affects.',
//and in the test: check_known( 'short-issue-id', $label, $condition, $details );
const KNOWN_ISSUES = array();

// -------------------------------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------------------------------

function check( $label, $condition, $details = '' ) {
	global $failures;
	if ( $condition ) {
		echo "PASS: {$label}\n";
	} else {
		$failures++;
		echo "FAIL: {$label}\n";
		if ( '' !== $details ) {
			echo '    ' . $details . "\n";
		}
	}
	return $condition;
}

/**
 * A check for a known issue: a normal check when the issue isn't in KNOWN_ISSUES, otherwise it reports the result without failing.
 */
function check_known( $issue, $label, $condition, $details = '' ) {
	global $known;
	if ( ! isset( KNOWN_ISSUES[ $issue ] ) ) {
		return check( $label, $condition, $details );
	}
	$known++;
	if ( $condition ) {
		echo "KNOWN ISSUE NOW PASSES: {$label}\n    Remove '{$issue}' from KNOWN_ISSUES if it is fixed: " . KNOWN_ISSUES[ $issue ] . "\n";
	} else {
		echo "KNOWN ISSUE (still happens): {$label}\n    " . KNOWN_ISSUES[ $issue ] . "\n";
		if ( '' !== $details ) {
			echo '    ' . $details . "\n";
		}
	}
	return $condition;
}

function wp_cli( $command ) {
	global $wp_path;
	$output = array();
	exec( 'wp --path=' . escapeshellarg( $wp_path ) . ' ' . $command . ' 2>&1', $output, $exit_code );
	if ( 0 !== $exit_code ) {
		echo "WP-CLI command failed: wp {$command}\n" . implode( "\n", $output ) . "\n";
		exit( 1 );
	}
	return implode( "\n", $output );
}

/**
 * Starts a browser session: the following requests keep their cookies (the login cookies). Use use_session( null ) for no cookies.
 */
function new_session() {
	global $cookie_jar;
	$cookie_jar = tempnam( sys_get_temp_dir(), 'sdm-e2e-cookies-' );
	return $cookie_jar;
}

function use_session( $file ) {
	global $cookie_jar;
	$cookie_jar = $file;
}

/**
 * Sends a request (redirects are not followed).
 *
 * @param string $endpoint The path and query string, or a full URL.
 * @param array  $headers  Extra request headers ("Name: value").
 * @param string $user_agent The User-Agent ('' sends none).
 *
 * @return array The HTTP status code, the body, the Location header and all the response headers (lower case names).
 */
function http_request( $method, $endpoint, $fields = null, $headers = array(), $user_agent = E2E_USER_AGENT ) {
	global $url, $cookie_jar;
	$response_headers = array();
	$curl             = curl_init( 0 === strpos( $endpoint, 'http' ) ? $endpoint : $url . $endpoint );
	curl_setopt_array(
		$curl,
		array(
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_USERAGENT      => $user_agent,
			CURLOPT_HEADERFUNCTION => function ( $curl, $header ) use ( &$response_headers ) {
				$parts = explode( ':', $header, 2 );
				if ( 2 === count( $parts ) ) {
					$response_headers[ strtolower( trim( $parts[0] ) ) ] = trim( $parts[1] );
				}
				return strlen( $header );
			},
		)
	);
	if ( null !== $fields ) {
		curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query( $fields ) );
	}
	if ( null !== $cookie_jar ) {
		curl_setopt( $curl, CURLOPT_COOKIEFILE, $cookie_jar );
		curl_setopt( $curl, CURLOPT_COOKIEJAR, $cookie_jar );
	}
	$body   = (string) curl_exec( $curl );
	$status = (int) curl_getinfo( $curl, CURLINFO_HTTP_CODE );
	//The cookie file is written when the handle is freed.
	unset( $curl );
	return array(
		'status'   => $status,
		'body'     => $body,
		'location' => isset( $response_headers['location'] ) ? $response_headers['location'] : '',
		'headers'  => $response_headers,
	);
}

/**
 * Sends the download request for a download item.
 *
 * @param array $params Other query parameters (for example the password).
 */
function download( $download_id, $params = array(), $headers = array(), $user_agent = E2E_USER_AGENT ) {
	$query = http_build_query(
		array_merge(
			array(
				'sdm_process_download' => 1,
				'download_id'          => $download_id,
			),
			$params
		)
	);
	return http_request( 'GET', '/?' . $query, null, $headers, $user_agent );
}

/** The response text without the HTML, shortened (printed when a check fails). */
function response_text( $response ) {
	$text = trim( preg_replace( '/\s+/', ' ', strip_tags( preg_replace( '#<(style|script)\b.*?</\1>#s', '', $response['body'] ) ) ) );
	$text = 'HTTP ' . $response['status'] . ': ' . substr( $text, 0, 300 );
	if ( '' !== $response['location'] ) {
		$text .= ' (Location: ' . $response['location'] . ')';
	}
	return $text;
}

function contains( $haystack, $needle ) {
	return false !== strpos( (string) $haystack, $needle );
}

/** A redirect to this URL (the file URL), without the file content in the response. */
function is_redirect_to( $response, $target ) {
	return in_array( $response['status'], array( 301, 302, 303, 307 ), true ) && $target === $response['location'];
}

/** The file was sent: HTTP 200 and the body is exactly the file content. */
function is_file_sent( $response, $content ) {
	return 200 === $response['status'] && $content === $response['body'];
}

function log_in( $user_login, $password ) {
	$session = new_session();
	http_request( 'GET', '/wp-login.php' );
	$response = http_request(
		'POST',
		'/wp-login.php',
		array(
			'log'         => $user_login,
			'pwd'         => $password,
			'wp-submit'   => 'Log In',
			'redirect_to' => '/wp-admin/',
			'testcookie'  => '1',
		)
	);
	$logged_in = 302 === $response['status'] && contains( file_get_contents( $session ), 'wordpress_logged_in' );
	if ( ! $logged_in ) {
		echo "Could not log in as {$user_login}. " . response_text( $response ) . "\n";
		exit( 1 );
	}
	return $session;
}

function db_value( $sql, $types = '', $params = array() ) {
	global $db;
	$statement = $db->prepare( $sql );
	if ( $types ) {
		$statement->bind_param( $types, ...$params );
	}
	$statement->execute();
	$row = $statement->get_result()->fetch_row();
	return $row ? $row[0] : null;
}

function db_row( $sql, $types, $params ) {
	global $db;
	$statement = $db->prepare( $sql );
	$statement->bind_param( $types, ...$params );
	$statement->execute();
	return $statement->get_result()->fetch_assoc();
}

function db_write( $sql, $types, $params ) {
	global $db;
	$statement = $db->prepare( $sql );
	$statement->bind_param( $types, ...$params );
	$statement->execute();
}

//Options are written directly in the database. Each request to the site reads them again (there is no object cache).
function set_site_option_value( $name, $value ) {
	global $prefix;
	db_write( "INSERT INTO {$prefix}options (option_name, option_value, autoload) VALUES (?, ?, 'on') ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)", 'ss', array( $name, serialize( $value ) ) );
}

/** Sets the plugin settings (sdm_downloads_options) to the base settings (debug logging on) plus these. */
function set_plugin_settings( $settings = array() ) {
	set_site_option_value( 'sdm_downloads_options', array_merge( array( 'enable_debug' => 'on' ), $settings ) );
}

function log_count( $download_id ) {
	global $prefix;
	return (int) db_value( "SELECT COUNT(*) FROM {$prefix}sdm_downloads WHERE post_id = ?", 'i', array( $download_id ) );
}

/** The newest download log entry of the item. */
function last_log_entry( $download_id ) {
	global $prefix;
	return db_row( "SELECT * FROM {$prefix}sdm_downloads WHERE post_id = ? ORDER BY id DESC LIMIT 1", 'i', array( $download_id ) );
}

function delete_log_entries( $download_id ) {
	global $prefix;
	db_write( "DELETE FROM {$prefix}sdm_downloads WHERE post_id = ?", 'i', array( $download_id ) );
}

// -------------------------------------------------------------------------------------------------
// The tests
// -------------------------------------------------------------------------------------------------

function run_basic_download_tests( $ids, $contents, $urls ) {
	echo "--- Downloads ---\n";
	set_plugin_settings();

	$before   = log_count( $ids['redirect'] );
	$response = download( $ids['redirect'], array(), array( 'Referer: http://127.0.0.1:8080/downloads-page/' ) );
	check( 'A download redirects to the file URL', is_redirect_to( $response, $urls['public'] ), response_text( $response ) );
	check( 'The redirect response does not contain the file content', ! contains( $response['body'], $contents['public'] ), response_text( $response ) );
	check( 'The download is added to the download log', log_count( $ids['redirect'] ) === $before + 1 );
	$entry = last_log_entry( $ids['redirect'] );
	if ( $entry ) {
		check( 'The log entry has the download title and the file URL', 'E2E Redirect' === $entry['post_title'] && $urls['public'] === $entry['file_url'], json_encode( $entry ) );
		check( 'The log entry has the IP address and the country (from the country lookup)', '127.0.0.1' === $entry['visitor_ip'] && 'United States' === $entry['visitor_country'], json_encode( $entry ) );
		check( 'The log entry has "Not Logged In" for a visitor', 'Not Logged In' === $entry['visitor_name'], json_encode( $entry ) );
		check( 'The log entry has the User-Agent and the referrer', E2E_USER_AGENT === $entry['user_agent'] && 'http://127.0.0.1:8080/downloads-page/' === $entry['referrer_url'], json_encode( $entry ) );
	}

	$before   = log_count( $ids['dispatch'] );
	$response = download( $ids['dispatch'] );
	check( 'PHP dispatch sends the file', is_file_sent( $response, $contents['public'] ), response_text( $response ) );
	check(
		'PHP dispatch sends the file as an attachment with its file name',
		isset( $response['headers']['content-disposition'] ) && contains( $response['headers']['content-disposition'], 'attachment' ) && contains( $response['headers']['content-disposition'], 'public-file.txt' ),
		json_encode( $response['headers'] )
	);
	check( 'A PHP dispatch download is added to the download log', log_count( $ids['dispatch'] ) === $before + 1 );

	$response = download( $ids['external_dispatch'] );
	check( 'PHP dispatch redirects to a file on another site', is_redirect_to( $response, $urls['external'] ), response_text( $response ) );

	$before   = log_count( $ids['protected'] );
	$response = download( $ids['protected'] );
	check( 'A file in the protected folder is sent by the plugin', is_file_sent( $response, $contents['protected'] ), response_text( $response ) );
	check( 'The protected file URL is not shown (no redirect)', '' === $response['location'], response_text( $response ) );
	check( 'A protected download is added to the download log', log_count( $ids['protected'] ) === $before + 1 );
}

function run_error_tests( $ids ) {
	echo "--- Wrong download requests ---\n";
	set_plugin_settings();

	foreach ( array( 'no ID' => 0, 'an ID that does not exist' => 999999 ) as $label => $id ) {
		$response = download( $id );
		check( "A download request with {$label} shows an error", contains( $response['body'], 'Incorrect download item id' ) && '' === $response['location'], response_text( $response ) );
	}

	$response = download( $ids['no_file'] );
	check( 'A download item without a file URL shows an error', contains( $response['body'], 'does not have any download link' ) && '' === $response['location'], response_text( $response ) );
	//It used printf() inside wp_die() (fixed with these tests): the message was printed before the error page.
	check( 'The error message is inside the error page', 0 === stripos( ltrim( $response['body'] ), '<!DOCTYPE' ), substr( $response['body'], 0, 200 ) );
}

function run_availability_tests( $ids, $urls ) {
	echo "--- Unpublished items ---\n";
	set_plugin_settings();

	foreach ( array( 'draft', 'private' ) as $status ) {
		$before   = log_count( $ids[ $status ] );
		$response = download( $ids[ $status ] );
		check( "A {$status} item can't be downloaded by a visitor", contains( $response['body'], 'This download item is not available' ) && '' === $response['location'], response_text( $response ) );
		check( "A {$status} item download by a visitor is not logged", log_count( $ids[ $status ] ) === $before );
	}

	use_session( log_in( 'e2e_subscriber', 'e2e-subscriber-pass' ) );
	$response = download( $ids['draft'] );
	check( "A draft item can't be downloaded by a subscriber", contains( $response['body'], 'This download item is not available' ), response_text( $response ) );

	use_session( log_in( 'admin', 'admin' ) );
	$response = download( $ids['draft'] );
	check( 'A draft item can be downloaded by a user who can edit it (an admin)', is_redirect_to( $response, $urls['public'] ), response_text( $response ) );
	use_session( null );

	set_plugin_settings( array( 'general_allow_downloads_of_unpublished_items' => 'on' ) );
	$response = download( $ids['private'] );
	check( 'A private item can be downloaded when "Allow downloads of unpublished items" is on', is_redirect_to( $response, $urls['public'] ), response_text( $response ) );
	set_plugin_settings();
}

function run_password_tests( $ids, $urls ) {
	echo "--- Password protected items ---\n";
	set_plugin_settings();
	$before = log_count( $ids['password'] );

	$response = download( $ids['password'] );
	check( 'A password protected item without the password shows an error', contains( $response['body'], 'This download requires a password' ) && '' === $response['location'], response_text( $response ) );

	$response = download( $ids['password'], array( 'pass_text' => 'wrong-pass' ) );
	check( 'A password protected item with a wrong password shows an error', contains( $response['body'], 'Incorrect password' ) && '' === $response['location'], response_text( $response ) );
	check( 'A download without the right password is not logged', log_count( $ids['password'] ) === $before );

	$response = download( $ids['password'], array( 'pass_text' => 'e2e-pass' ) );
	check( 'A password protected item with the right password is downloaded', is_redirect_to( $response, $urls['public'] ), response_text( $response ) );

	//The password form on the page sends a POST request.
	$response = http_request( 'POST', '/?sdm_process_download=1&download_id=' . $ids['password'], array( 'pass_text' => 'e2e-pass' ) );
	check( 'The password can be sent with a POST request', is_redirect_to( $response, $urls['public'] ), response_text( $response ) );
	check( 'The downloads with the right password are logged', log_count( $ids['password'] ) === $before + 2 );
}

function run_logged_in_only_tests( $ids, $urls ) {
	echo "--- Only logged-in users can download ---\n";
	set_plugin_settings( array( 'only_logged_in_can_download' => 'on' ) );

	$before   = log_count( $ids['redirect'] );
	$response = download( $ids['redirect'] );
	check( 'A visitor who is not logged in is asked to log in', contains( $response['body'], 'You need to be logged in to download this file' ) && '' === $response['location'], response_text( $response ) );
	check( 'The refused download is not logged', log_count( $ids['redirect'] ) === $before );

	$response = download( $ids['anonymous'] );
	check( 'An item that allows anonymous downloads can be downloaded by a visitor', is_redirect_to( $response, $urls['public'] ), response_text( $response ) );

	use_session( log_in( 'e2e_subscriber', 'e2e-subscriber-pass' ) );
	$response = download( $ids['redirect'] );
	check( 'A logged-in user can download', is_redirect_to( $response, $urls['public'] ), response_text( $response ) );
	$entry = last_log_entry( $ids['redirect'] );
	check( 'The log entry has the username', $entry && 'e2e_subscriber' === $entry['visitor_name'], json_encode( $entry ) );
	use_session( null );

	set_plugin_settings(
		array(
			'only_logged_in_can_download' => 'on',
			'general_login_page_url'      => 'http://127.0.0.1:8080/e2e-login-page/',
		)
	);
	$response = download( $ids['redirect'] );
	check( 'The message links to the login page set in the settings', contains( $response['body'], 'http://127.0.0.1:8080/e2e-login-page/' ), response_text( $response ) );
	set_plugin_settings();
}

function run_logging_option_tests( $ids ) {
	echo "--- Download log options ---\n";
	$id = $ids['redirect'];

	set_plugin_settings();
	$before = log_count( $id );
	download( $id, array( 'sdm_ignore_logging' => 1 ) );
	check( 'A download with sdm_ignore_logging=1 is not logged', log_count( $id ) === $before );

	$before = log_count( $ids['no_log'] );
	download( $ids['no_log'] );
	check( 'A download of an item with logging turned off is not logged', log_count( $ids['no_log'] ) === $before );

	set_plugin_settings( array( 'admin_no_logs' => 'on' ) );
	$before = log_count( $id );
	download( $id );
	check( 'No download is logged when the download logs are turned off', log_count( $id ) === $before );

	set_plugin_settings( array( 'admin_log_unique' => 'on' ) );
	delete_log_entries( $id );
	download( $id );
	download( $id );
	check( 'Only the first download from an IP address is logged when "Log unique IP" is on', 1 === log_count( $id ), 'Log entries: ' . log_count( $id ) );

	set_plugin_settings(
		array(
			'admin_do_not_capture_ip'           => 'on',
			'admin_do_not_capture_user_agent'   => 'on',
			'admin_do_not_capture_referrer_url' => 'on',
		)
	);
	download( $id, array(), array( 'Referer: http://127.0.0.1:8080/downloads-page/' ) );
	$entry = last_log_entry( $id );
	check(
		'The IP address, country, User-Agent and referrer are not saved when the "Do not capture" options are on',
		$entry && '' === $entry['visitor_ip'] && '' === $entry['visitor_country'] && '' === $entry['user_agent'] && '' === $entry['referrer_url'],
		json_encode( $entry )
	);

	set_plugin_settings( array( 'admin_dont_log_bots' => 'on' ) );
	$before = log_count( $id );
	download( $id, array(), array(), 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)' );
	check( 'A download by a bot is not logged when "Do Not Count Downloads from Bots" is on', log_count( $id ) === $before );
	download( $id );
	check( 'A download by a browser is still logged when "Do Not Count Downloads from Bots" is on', log_count( $id ) === $before + 1 );

	set_plugin_settings();
}

function run_file_check_tests( $ids, $contents, $urls ) {
	echo "--- Files that PHP dispatch doesn't send ---\n";
	set_plugin_settings();

	$cases = array(
		'hidden file'                     => array( 'hidden_dispatch', 'hidden' ),
		'file without an extension'       => array( 'no_extension_dispatch', 'no_extension' ),
		'.bak file (default blocked list)' => array( 'bak_dispatch', 'bak' ),
		'.phtml file (always blocked)'    => array( 'phtml_dispatch', 'phtml' ),
	);
	foreach ( $cases as $label => $case ) {
		$response = download( $ids[ $case[0] ] );
		check( "PHP dispatch redirects instead of sending a {$label}", is_redirect_to( $response, $urls[ $case[1] ] ), response_text( $response ) );
		check( "The response does not contain the {$label}", ! contains( $response['body'], $contents[ $case[1] ] ), response_text( $response ) );
	}

	$response = download( $ids['protected_phtml'] );
	check( 'A .phtml file in the protected folder is not sent', contains( $response['body'], 'Disallowed extension' ) && ! contains( $response['body'], $contents['protected_phtml'] ), response_text( $response ) );

	$response = download( $ids['protected_hidden'] );
	check( 'A hidden file in the protected folder is not sent', contains( $response['body'], 'Hidden file or no extension' ) && ! contains( $response['body'], $contents['protected_hidden'] ), response_text( $response ) );

	set_plugin_settings( array( 'general_allow_hidden_noext_dispatch' => 'on' ) );
	$response = download( $ids['hidden_dispatch'] );
	check( 'A hidden file is sent when "Allow hidden and no-extension files" is on', is_file_sent( $response, $contents['hidden'] ), response_text( $response ) );

	set_plugin_settings( array( 'general_disallowed_file_ext_dispatch' => 'txt, zip' ) );
	$response = download( $ids['dispatch'] );
	check( 'PHP dispatch redirects for an extension in the saved blocked list', is_redirect_to( $response, $urls['public'] ), response_text( $response ) );
	set_plugin_settings();
}

/**
 * The path traversal report (CVE-2026-135625). The canary file is outside the web root, so the only way to read it
 * is through the plugin. None of these requests may return its content.
 */
function run_path_traversal_tests( $ids, $contents, $urls ) {
	echo "--- Path traversal (CVE-2026-135625) ---\n";
	set_plugin_settings();

	$cases = array(
		'traversal_protected'          => 'the protected folder URL with "../" (the report)',
		'traversal_protected_encoded'  => 'the protected folder URL with URL-encoded "../"',
		'traversal_protected_dispatch' => 'the protected folder URL with "../" and PHP dispatch',
		'traversal_dispatch'           => 'the wp-content URL with "../" and PHP dispatch',
	);
	foreach ( $cases as $key => $label ) {
		$response = download( $ids[ $key ] );
		check( "A file outside wp-content is not sent for {$label}", ! contains( $response['body'], $contents['canary'] ), response_text( $response ) );
		check( "The response for {$label} is not a file", 200 !== $response['status'] || ! isset( $response['headers']['content-disposition'] ), response_text( $response ) );

		//A browser follows the redirect (and resolves the "../"): the file must not be there either.
		if ( '' !== $response['location'] ) {
			$followed = http_request( 'GET', $response['location'] );
			check( "Following the redirect for {$label} does not return the file", ! contains( $followed['body'], $contents['canary'] ), response_text( $followed ) );
		}
	}

	$response = download( $ids['traversal_dispatch'] );
	check( 'PHP dispatch shows "File not found" for a path traversal URL', 404 === $response['status'] && contains( $response['body'], 'File not found' ), response_text( $response ) );

	$response = download( $ids['traversal_to_uploads'] );
	check( 'A file outside the protected folder is not sent for a protected folder URL with "../"', ! contains( $response['body'], $contents['outside_protected'] ), response_text( $response ) );
}

// -------------------------------------------------------------------------------------------------
// Run
// -------------------------------------------------------------------------------------------------

//Create the test data.
$setup_output = wp_cli( 'eval-file ' . escapeshellarg( __DIR__ . '/setup-site.php' ) );
$lines        = array_filter( array_map( 'trim', explode( "\n", $setup_output ) ) );
$data         = json_decode( end( $lines ), true );
if ( empty( $data['ids']['redirect'] ) ) {
	echo "Could not create the test data. Output:\n{$setup_output}\n";
	exit( 1 );
}
$ids      = $data['ids'];
$contents = $data['contents'];
$urls     = $data['urls'];

run_basic_download_tests( $ids, $contents, $urls );
run_error_tests( $ids );
run_availability_tests( $ids, $urls );
run_password_tests( $ids, $urls );
run_logged_in_only_tests( $ids, $urls );
run_logging_option_tests( $ids );
run_file_check_tests( $ids, $contents, $urls );
run_path_traversal_tests( $ids, $contents, $urls );

//PHP errors on the test site (WP_DEBUG_LOG). A fatal error fails the tests; warnings and notices from the plugin are listed.
echo "--- PHP errors ---\n";
$debug_log = $wp_path . '/wp-content/debug.log';
$log_lines = file_exists( $debug_log ) ? file( $debug_log, FILE_IGNORE_NEW_LINES ) : array();
$fatal     = preg_grep( '/PHP Fatal error/', $log_lines );
check( 'No PHP fatal errors on the test site', 0 === count( $fatal ), implode( "\n    ", $fatal ) );
$plugin_messages = array_unique( preg_replace( '/^\[[^\]]+\] /', '', preg_grep( '#plugins/simple-download-monitor/#', $log_lines ) ) );
if ( $plugin_messages ) {
	echo 'PHP warnings and notices from the plugin (not a failure):' . "\n    " . implode( "\n    ", $plugin_messages ) . "\n";
}

echo "\n" . ( 0 === $failures ? 'All endpoint tests passed.' : "{$failures} endpoint test(s) failed." ) . "\n";
if ( $known ) {
	echo "{$known} check(s) for known issues ran without failing the tests (see the KNOWN ISSUE lines and KNOWN_ISSUES).\n";
}
exit( 0 === $failures ? 0 : 1 );
