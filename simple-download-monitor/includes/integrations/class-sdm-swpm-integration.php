<?php

class SDM_SWPM_Integration
{

	public $error_msg = '';

	public function __construct()
	{
		if (!function_exists('is_plugin_active')) {
			include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		if (is_plugin_active('simple-membership/simple-wp-membership.php')) {
			add_action('plugin_loaded', array($this, 'setup_swpm_integration'));
		}
	}

	public function setup_swpm_integration()
	{
		add_action('sdm_file_protection_settings_updated', array($this, 'update_swpm_integration_settings'));
		add_action('sdm_after_file_protection_settings_fields', array($this, 'show_swpm_integration_settings'));

		$settings = get_option('sdm_global_options', array());

		// Check if SWPM access control feature enabled for SDM downloads.
		$is_swpm_access_control_enabled = isset($settings['enable_swpm_access_control']) && !empty($settings['enable_swpm_access_control']) ? true : false;
		if ($is_swpm_access_control_enabled) {
			// Block download process request for unauthorized visitors.
			add_action('sdm_process_download_request', array($this, 'check_sdm_download_process'), 10, 2);
			add_action('sdm_sf_process_download_request', array($this, 'check_sdm_download_process'), 10, 2);

			// Override download button html for unauthorized visitor.
			add_filter('sdm_download_button_code_html', array($this, 'disable_sdm_download_button'));
		}
	}

	public function update_swpm_integration_settings()
	{
		$settings = get_option('sdm_global_options', array());

		// Prepare settings array to save.
		$settings['enable_swpm_access_control'] = isset($_POST['enable_swpm_access_control']) ? 'checked="checked"' : '';

		// Save the settings.
		update_option('sdm_global_options', $settings);
	}

	public function show_swpm_integration_settings()
	{
		$settings = get_option('sdm_global_options', array());

		$enable_swpm_access_control = isset($settings['enable_swpm_access_control']) && !empty($settings['enable_swpm_access_control']) ? $settings['enable_swpm_access_control'] : '';

		$output = '<tr>';
		$output .= '<th scope="row">' . __('Enable SWPM Access Control', 'simple-download-monitor') . '</th>';
		$output .= '<td>';
		$output .= '<input name="enable_swpm_access_control" id="enable_swpm_access_control" type="checkbox" ' . esc_attr($enable_swpm_access_control) . '/>';
		$output .= '<p class="description">' . __('Check this to enable SWPM access control', 'simple-download-monitor') . '</p>';
		$output .= '</td>';
		$output .= '</tr>';
		echo $output;
	}

	public function check_sdm_download_process($dl_id, $dl_link)
	{
		if (! $this->is_download_permitted($dl_id)) {
			wp_die($this->error_msg);
		}
	}

	public function is_download_permitted($dl_id) {
		if (!class_exists('SwpmAccessControl') || !class_exists('SwpmProtection')) {
			return false;
		}

		$swpm_protection = SwpmProtection::get_instance();
		if($swpm_protection->post_in_parent_categories($dl_id) || $swpm_protection->post_in_categories($dl_id)){
			$this->error_msg = __('You are not allowed to access this download item!', 'simple-download-monitor');
			return false;
		}

		$swpm_access_control = \SwpmAccessControl::get_instance();

		// Adjust the error messages for a downloadable item.
		add_filter('swpm_not_logged_in_post_msg', array($this, "override_not_logged_in_post_msg"));
		add_filter('swpm_restricted_post_msg_older_post', array($this, "override_restricted_post_msg_older_post"));
		add_filter('swpm_restricted_post_msg', array($this, "override_post_msg"));

		$is_permitted = $swpm_access_control->can_i_read_post_by_post_id($dl_id);

		remove_filter('swpm_not_logged_in_post_msg',   array($this, "override_not_logged_in_post_msg"));
		remove_filter('swpm_restricted_post_msg_older_post', array($this, "override_restricted_post_msg_older_post"));
		remove_filter('swpm_restricted_post_msg',   array($this, "override_post_msg"));

		if (!$is_permitted){
			// Show authorized error message. If the 'get_lastError' isn't available (if user haven't updated swpm plugin yet) in swpm plugin, show a default message.
			if (method_exists($swpm_access_control, 'get_lastError')){
				$this->error_msg = $swpm_access_control->get_lastError();
			} else {
				$this->error_msg = __('You are not allowed to access this download item!', 'simple-download-monitor');
			}
		}

		return $is_permitted;
	}

	/**
	 * Disables the download button if it is restricted for current visitor.
	 */
	public function disable_sdm_download_button($btn_html)
	{
		$dom = new DOMDocument();
		// Suppress warnings due to malformed HTML
		libxml_use_internal_errors(true);
		$dom->loadHTML($btn_html);
		libxml_clear_errors();

		$links = $dom->getElementsByTagName('a');

		foreach ($links as $link) {
			$href = $link->getAttribute('href');
			// Parse the URL to get query parameters
			$query = parse_url($href, PHP_URL_QUERY);
			parse_str($query, $query_args);
			if (isset($query_args['download_id'])) {
				$download_id = $query_args['download_id'];
				if ( ! $this->is_download_permitted($download_id) ) {
					$link->removeAttribute('href');
					$link->setAttribute('class', 'sdm_download disabled');
					$link->setAttribute('title', __('This download item is for authorized members only.'));
					$disabled_btn_html = $dom->saveHTML();
					return $disabled_btn_html;
				}

				break;
			}
		}

		return $btn_html;
	}

	public function override_not_logged_in_post_msg($msg)
	{
		return str_replace('view this content', 'download this item', $msg);
	}

	public function override_restricted_post_msg_older_post($msg)
	{
		$msg = str_replace('content', 'item', $msg);
		return str_replace('viewed', 'downloaded', $msg);
	}

	public function override_post_msg($msg)
	{
		return str_replace('content', 'download item', $msg);
	}
}

new SDM_SWPM_Integration();
