<?php

define('PROJECT_DIR', dirname(dirname( __FILE__ )));

require_once PROJECT_DIR . '/vendor/autoload.php';

define('WP_TESTS_LIB_DIR', PROJECT_DIR . '/vendor/adilrabid/wp-tests-lib-custom');
define('WP_TESTS_CONFIG_FILE_PATH', PROJECT_DIR . '/tests/wp-tests-config.php');


if ( ! file_exists( WP_TESTS_LIB_DIR . '/includes/functions.php' ) ) {
	echo "Could not find $WP_TESTS_LIB_DIR/includes/functions.php" . PHP_EOL;
	exit( 1 );
}

require_once WP_TESTS_LIB_DIR . '/includes/bootstrap.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require PROJECT_DIR . '/simple-download-monitor/main.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// require_once __DIR__ . '/mocks/mock-shortcodes.php'; 
