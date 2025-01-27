<?php

class SDM_eMember_Integration {

	public $error_msg = '';

	public function __construct()
	{
		if (! function_exists('is_plugin_active')) {
			include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		if (is_plugin_active('wp-eMember/wp_eMember.php')) {
			add_action('plugin_loaded', array($this, 'setup_integration'));
		}
	}

	public function setup_integration()
	{
		add_action('sdm_file_protection_settings_updated', array($this, 'update_integration_settings'));
		add_action('sdm_after_file_protection_settings_fields', array($this, 'show_integration_settings'));

		$settings = get_option('sdm_global_options', array());

		// Check if SWPM access control feature enabled for SDM downloads.
		$is_swpm_access_control_enabled = isset($settings['enable_eMember_access_control']) && ! empty($settings['enable_eMember_access_control']) ? true : false;
		if ($is_swpm_access_control_enabled) {
			// Block download process request for unauthorized visitors.
			add_action( 'sdm_process_download_request', array( $this, 'check_download_process' ), 10, 2 );
			add_action( 'sdm_sf_process_download_request', array( $this, 'check_download_process' ), 10, 2 );

			// Override download button html for unauthorized visitor.
			add_filter('sdm_download_button_code_html', array($this, 'disable_sdm_download_button'));
		}
	}

	public function update_integration_settings()
	{
		$settings = get_option('sdm_global_options', array());

		// Prepare settings array to save.
		$settings['enable_eMember_access_control'] = isset($_POST['enable_eMember_access_control']) ? 'checked="checked"' : '';

		// Save the settings.
		update_option('sdm_global_options', $settings);
	}

	public function show_integration_settings()
	{
		$settings = get_option('sdm_global_options', array());

		$enable_eMember_access_control = isset($settings['enable_eMember_access_control']) && ! empty($settings['enable_eMember_access_control']) ? $settings['enable_eMember_access_control'] : '';

		$output = '<tr>';
		$output .= '<th scope="row">' . __('Enable eMember Access Control', 'simple-download-monitor') . '</th>';
		$output .= '<td>';
		$output .= '<input name="enable_eMember_access_control" id="enable_eMember_access_control" type="checkbox" ' . esc_attr($enable_eMember_access_control) . '/>';
		$output .= '<p class="description">' . __('Check this to enable WP eMember access control', 'simple-download-monitor') . '</p>';
		$output .= '</td>';
		$output .= '</tr>';
		echo $output;
	}

	public function check_download_process( $dl_id, $dl_link ) {
		if ( ! $this->is_download_permitted( $dl_id ) ) {
			wp_die( $this->error_msg );
		}
	}

	public function is_download_permitted( $id ) {
		if ( ! class_exists( 'Emember_Auth' ) ) {
			return false;
		}

		$emember_auth = Emember_Auth::getInstance();

		if ( $emember_auth->is_protected_category( $id ) || $emember_auth->is_protected_parent_category( $id ) || $emember_auth->is_protected_custom_post( $id ) ) {
			if ( $emember_auth->isLoggedIn() ) {
				if ( ! emember_check_all_subscriptions_expired() ) {
					if ( $emember_auth->is_permitted_category( $id ) ) {
						return true;
					} else {
						$this->error_msg = str_replace( 'view the rest of the content', 'download this item', EMEMBER_LEVEL_NOT_ALLOWED );
					}
				} else {
					$this->error_msg = get_renewal_link();
				}
			} else {
				$this->error_msg = str_replace( 'view this content', 'download this item', get_login_link() );
			}
			return false;
		}

		return true;
	}

	/**
	 * Disables the download button if it is restricted for current visitor.
	 */
	public function disable_sdm_download_button($btn_html) {
		$dom = new DOMDocument();
		// Suppress warnings due to malformed HTML
		libxml_use_internal_errors( true );
		$dom->loadHTML( $btn_html );
		libxml_clear_errors();

		$links = $dom->getElementsByTagName( 'a' );

		foreach ( $links as $link ) {
			$href = $link->getAttribute( 'href' );
			// Parse the URL to get query parameters
			$query = parse_url( $href, PHP_URL_QUERY );
			parse_str( $query, $query_args );
			if ( isset( $query_args['download_id'] ) ) {
				$download_id  = $query_args['download_id'];
				if ( ! $this->is_download_permitted( $download_id ) ) {
					$link->removeAttribute( 'href' );
					$link->setAttribute( 'class', 'sdm_download disabled' );
					$link->setAttribute( 'title', __( 'This download item is for authorized members only.' ) );
					$disabled_btn_html = $dom->saveHTML();

					return $disabled_btn_html;
				}

				break;
			}
		}

		return $btn_html;
	}
}

new SDM_eMember_Integration();
