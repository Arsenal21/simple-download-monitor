<?php

class SDM_Addons_Updater {
	protected $addons = array(
		array( 'sdm-user-upload', 'sdm-user-upload/main.php', 'sdm-user-upload' ),
		array( 'sdm-squeeze-form', 'sdm-squeeze-form/main.php', 'sdm-squeeze-form' ),
		array( 'sdm-hidden-downloads', 'sdm-hidden-downloads/sdm-hidden-downloads.php', 'sdm-hidden-downloads' ),
		array( 'sdm-dropbox', 'sdm-dropbox/sdm-dropbox.php', 'sdm-dropbox' ),
		array( 'sdm-allow-more-file-types', 'sdm-allow-more-file-types/sdm-allow-more-file-types.php', 'sdm-allow-more-file-types' ),
		array( 'sdm-amazon-s3', 'sdm-amazon-s3/sdm-amazon-s3.php', 'sdm-amazon-s3' ),
		array( 'sdm-download-email-notification', 'sdm-download-email-notification/sdm-download-email-notification.php', 'sdm-download-email-notification' ),
	);

	private $update_url = 'https://simple-download-monitor.com/updates/?action=get_metadata&slug=';

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public function plugins_loaded() {
		require_once WP_SIMPLE_DL_MONITOR_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';
		foreach ( $this->addons as $addon ) {
			$plugin_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $addon[1] );
			if ( file_exists( $plugin_file ) ) {
				$my_update_checker = Puc_v4_Factory::buildUpdateChecker(
					$this->update_url . $addon[0],
					$plugin_file,
					$addon[2]
				);
			}
		}

	}
}

new SDM_Addons_Updater();
