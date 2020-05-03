<?php

class SDM_Addons_Updater {
	protected $addons = array(
		array( 'sdm-user-upload', 'sdm-user-upload/main.php', 'sdm-user-upload' ),
		array( 'sdm-squeeze-form', 'sdm-squeeze-form/main.php', 'sdm-squeeze-form', 'sdm-squeeze-form-addon-icon.png' ),
		array( 'sdm-hidden-downloads', 'sdm-hidden-downloads/sdm-hidden-downloads.php', 'sdm-hidden-downloads', 'sdm-hidden-downloads-addon-icon.png' ),
		array( 'sdm-dropbox', 'sdm-dropbox/sdm-dropbox.php', 'sdm-dropbox', 'sdm-dropbox-integration-addon.png' ),
		array( 'sdm-allow-more-file-types', 'sdm-allow-more-file-types/sdm-allow-more-file-types.php', 'sdm-allow-more-file-types', 'sdm-allow-uploads-addon-icon.png' ),
		array( 'sdm-amazon-s3', 'sdm-amazon-s3/sdm-amazon-s3.php', 'sdm-amazon-s3', 'sdm-amazon-s3-addon-icon.png', 'sdm-email-on-download-addon-icon.png' ),
		array( 'sdm-download-email-notification', 'sdm-download-email-notification/sdm-download-email-notification.php', 'sdm-download-email-notification', 'sdm-email-on-download-addon-icon.png' ),
	);

	private $update_url = 'https://simple-download-monitor.com/updates/?action=get_metadata&slug=';
	private $icons_path = WP_SIMPLE_DL_MONITOR_URL . '/images/addons/';
	private $icons      = array();

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public function set_icon( $data ) {
		if ( isset( $this->icons[ $data->slug ] ) ) {
			$data->icons = array( 'default' => $this->icons[ $data->slug ] );
		}
		return $data;
	}

	public function set_request_options( $options ) {
		$options['timeout'] = 5;
		return $options;
	}

	public function plugins_loaded() {
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			require_once WP_SIMPLE_DL_MONITOR_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';
		}
		foreach ( $this->addons as $addon ) {
			if ( ! empty( $addon[3] ) ) {
				$this->icons[ $addon[2] ] = $this->icons_path . $addon[3];
				add_filter( 'puc_request_info_result-' . $addon[2], array( $this, 'set_icon' ) );
			}
			add_filter( 'puc_request_info_options-' . $addon[2], array( $this, 'set_request_options' ) );
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
