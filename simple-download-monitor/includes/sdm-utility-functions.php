<?php

/**
 * Get (filtered) list of all download button colors.
 *
 * @return array Array of colors: color key => color name.
 */
function sdm_get_download_button_colors() {
	return apply_filters(
		'sdm_download_button_color_options',
		array(
			'green'    => __( 'Green', 'simple-download-monitor' ),
			'blue'     => __( 'Blue', 'simple-download-monitor' ),
			'purple'   => __( 'Purple', 'simple-download-monitor' ),
			'teal'     => __( 'Teal', 'simple-download-monitor' ),
			'darkblue' => __( 'Dark Blue', 'simple-download-monitor' ),
			'black'    => __( 'Black', 'simple-download-monitor' ),
			'grey'     => __( 'Grey', 'simple-download-monitor' ),
			'pink'     => __( 'Pink', 'simple-download-monitor' ),
			'orange'   => __( 'Orange', 'simple-download-monitor' ),
			'white'    => __( 'White', 'simple-download-monitor' ),
		)
	);
}

function sdm_get_download_count_for_post( $id ) {
	// Get number of downloads by counting db columns matching postID
	global $wpdb;
	$table = $wpdb->prefix . 'sdm_downloads';
	$wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $table . ' WHERE post_id=%s', $id ) );
	// Count database rows
	$db_count = $wpdb->num_rows;

	// Check post meta to see if we need to offset the count before displaying to viewers
	$get_offset = get_post_meta( $id, 'sdm_count_offset', true );

	if ( $get_offset && $get_offset != '' ) {

		$db_count = $db_count + $get_offset;
	}
	return $db_count;
}

/**
 * Counts all total downloads including offset count.
 *
 * @return number
 */
function sdm_get_download_count_for_all_posts() {
    global $wpdb;

	// For total count
    $table = $wpdb->prefix . 'sdm_downloads';
	$query1 = 'SELECT * FROM ' . $table;
    $wpdb->get_results($query1);
    $db_count = $wpdb->num_rows;

	// For offset count
    $table2 = $wpdb->prefix . 'posts';
	$query2 = ' SELECT * FROM ' . $table2 . ' WHERE post_type="sdm_downloads"';
    $result = $wpdb->get_results($query2);

    // Check post meta for offset count.
    for ($i = 0; $i < $wpdb->num_rows; $i++) {
        $get_offset = get_post_meta($result[$i]->ID, 'sdm_count_offset', true);
        if ($get_offset && $get_offset != '') {
            $db_count = $db_count + $get_offset;
        }
    }
    return $db_count;
}

function sdm_get_password_entry_form( $id, $args = array(), $class = '' ) {
	$action_url = WP_SIMPLE_DL_MONITOR_SITE_HOME_URL . '/?sdm_process_download=1&download_id=' . $id;

	//Get the download button text
	$button_text = isset( $args['button_text'] ) ? $args['button_text'] : '';
	if ( empty( $button_text ) ) {//Use the default text for the button
		$button_text_string = sdm_get_default_download_button_text( $id );
	} else { //Use the custom text
		$button_text_string = $button_text;
	}

	$uuid = uniqid( 'sdm-pass-' );

	$data = '';

	//Enter password label
	$enter_password_label = __( 'Enter Password to Download:', 'simple-download-monitor' );
	$enter_password_label = apply_filters( 'sdm_enter_password_to_download_label', $enter_password_label );
	$data                .= '<span class="sdm_enter_password_label_text">' . $enter_password_label . '</span>';

	//Check if new window is enabled
	$new_window    = get_post_meta( $id, 'sdm_item_new_window', true );
	$window_target = empty( $new_window ) ? '' : ' target="_blank"';

	//Form code
	$data .= '<form action="' . $action_url . '" method="post" id="' . $uuid . '" class="sdm-download-form"' . $window_target . '>';
	$data .= '<input type="password" name="pass_text" class="sdm_pass_text" value="" /> ';

	$data .= sdm_get_download_with_recaptcha();

	//Check if Terms & Condition enabled
	$data .= sdm_get_checkbox_for_termsncond();

	$data .= '<span class="sdm-download-button">';
	$data .= '<a href="#" name="sdm_dl_pass_submit" class="pass_sumbit sdm_pass_protected_download sdm_download_with_condition ' . esc_attr($class) . '">' . $button_text_string . '</a>';
	$data .= '</span>';
	$data .= '<input type="hidden" name="download_id" value="' . $id . '" />';
	$data .= '</form>';
	return $data;
}

/**
 * Get remote IP address.
 *
 * @link http://stackoverflow.com/questions/1634782/what-is-the-most-accurate-way-to-retrieve-a-users-correct-ip-address-in-php
 *
 * @param bool $ignore_private_and_reserved Ignore IPs that fall into private or reserved IP ranges.
 * @return mixed IP address as a string or null, if remote IP address cannot be determined (or is ignored).
 */
function sdm_get_ip_address( $ignore_private_and_reserved = false ) {
	$flags = $ignore_private_and_reserved ? ( FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) : 0;
	foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ) as $key ) {
		if ( array_key_exists( $key, $_SERVER ) === true ) {
			foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
				$ip = trim( $ip ); // just to be safe

				if ( filter_var( $ip, FILTER_VALIDATE_IP, $flags ) !== false ) {
					return $ip;
				}
			}
		}
	}
	return null;
}

/**
 * Get location information (country or other info) for given IP address.
 *
 * @param string $ip
 * @param string $purpose
 * @return mixed
 */
function sdm_ip_info( $ip, $purpose = 'location' ) {

	$continents = array(
		'AF' => 'Africa',
		'AN' => 'Antarctica',
		'AS' => 'Asia',
		'EU' => 'Europe',
		'OC' => 'Australia (Oceania)',
		'NA' => 'North America',
		'SA' => 'South America',
	);

	return sdm_get_ip_info_by_ipwhois($ip, $purpose, $continents );
//	return sdm_get_ip_info_by_geoplugin($ip, $purpose, $continents );

}

/**
 * @param String $ip The visitors IP address.
 * @param String $purpose Name of the data to receive.
 * @param Array  $continents Available continents.
 *
 * @return array|string|null*
 */
function sdm_get_ip_info_by_ipwhois( $ip, $purpose, $continents ) {
	$ipdat = @json_decode( wp_remote_retrieve_body( wp_remote_get( 'http://ipwhois.app/json/' . $ip ) ) );

	if ( @strlen( trim( $ipdat->country_code ) ) === 2 ) {
		switch ( $purpose ) {
			case 'location':
				return array(
					'city'           => @$ipdat->city,
					'state'          => @$ipdat->region,
					'country'        => @$ipdat->country,
					'country_code'   => @$ipdat->country_code,
					'continent'      => @$continents[ strtoupper( $ipdat->continent_code ) ],
					'continent_code' => @$ipdat->continent_code,
				);
			case 'address':
				$address = array( $ipdat->country );
				if ( @strlen( $ipdat->region ) >= 1 ) {
					$address[] = $ipdat->region;
				}
				if ( @strlen( $ipdat->city ) >= 1 ) {
					$address[] = $ipdat->city;
				}

				return implode( ', ', array_reverse( $address ) );
			case 'city':
				return @$ipdat->city;
			case 'state':
				return @$ipdat->region;
			case 'region':
				return @$ipdat->region;
			case 'country':
				return @$ipdat->country;
			case 'countrycode':
				return @$ipdat->country_code;
		}
	}

	// Either no info found or invalid $purpose.
	return null;
}

/**
 * @param String $ip The visitors IP address.
 * @param String $purpose Name of the data to receive.
 * @param Array  $continents Available continents.
 *
 * @return array|string|null*
 */
function sdm_get_ip_info_by_geoplugin( $ip, $purpose, $continents ) {
	$ipdat = @json_decode( wp_remote_retrieve_body( wp_remote_get( 'http://www.geoplugin.net/json.gp?ip=' . $ip ) ) );

	if ( @strlen( trim( $ipdat->geoplugin_countryCode ) ) === 2 ) {
		switch ( $purpose ) {
			case 'location':
				return array(
					'city'           => @$ipdat->geoplugin_city,
					'state'          => @$ipdat->geoplugin_regionName,
					'country'        => @$ipdat->geoplugin_countryName,
					'country_code'   => @$ipdat->geoplugin_countryCode,
					'continent'      => @$continents[ strtoupper( $ipdat->geoplugin_continentCode ) ],
					'continent_code' => @$ipdat->geoplugin_continentCode,
				);
			case 'address':
				$address = array( $ipdat->geoplugin_countryName );
				if ( @strlen( $ipdat->geoplugin_regionName ) >= 1 ) {
					$address[] = $ipdat->geoplugin_regionName;
				}
				if ( @strlen( $ipdat->geoplugin_city ) >= 1 ) {
					$address[] = $ipdat->geoplugin_city;
				}
				return implode( ', ', array_reverse( $address ) );
			case 'city':
				return @$ipdat->geoplugin_city;
			case 'state':
				return @$ipdat->geoplugin_regionName;
			case 'region':
				return @$ipdat->geoplugin_regionName;
			case 'country':
				return @$ipdat->geoplugin_countryName;
			case 'countrycode':
				return @$ipdat->geoplugin_countryCode;
		}
	}

	// Either no info found or invalid $purpose.
	return null;
}

/*
 * Checks if the string exists in the array key value of the provided array. If it doesn't exist, it returns the first key element from the valid values.
 */

function sdm_sanitize_value_by_array( $to_check, $valid_values ) {
	$keys = array_keys( $valid_values );
	$keys = array_map( 'strtolower', $keys );
	if ( in_array( $to_check, $keys ) ) {
		return $to_check;
	}
	return reset( $keys ); //Return the first element from the valid values
}

function sdm_get_logged_in_user() {
	$visitor_name = false;

	if ( is_user_logged_in() ) {  // Get WP user name (if logged in)
		$current_user = wp_get_current_user();
		$visitor_name = $current_user->user_login;
	}

	//WP eMember plugin integration
	if ( class_exists( 'Emember_Auth' ) ) {
		//WP eMember plugin is installed.
		$emember_auth = Emember_Auth::getInstance();
		$username     = $emember_auth->getUserInfo( 'user_name' );
		if ( ! empty( $username ) ) {//Member is logged in.
			$visitor_name = $username; //Override the visitor name to emember username.
		}
	}

	$visitor_name = apply_filters( 'sdm_get_logged_in_user_name', $visitor_name );

	return $visitor_name;
}

// Checks if current visitor is a bot
function sdm_visitor_is_bot() {
	$bots = array( 'archiver', 'baiduspider', 'bingbot', 'binlar', 'casper', 'checkprivacy', 'clshttp', 'cmsworldmap', 'comodo', 'curl', 'diavol', 'dotbot', 'DuckDuckBot', 'Exabot', 'email', 'extract', 'facebookexternalhit', 'feedfinder', 'flicky', 'googlebot', 'grab', 'harvest', 'httrack', 'ia_archiver', 'jakarta', 'kmccrew', 'libwww', 'loader', 'MJ12bot', 'miner', 'msnbot', 'nikto', 'nutch', 'planetwork', 'purebot', 'pycurl', 'python', 'scan', 'skygrid', 'slurp', 'sucker', 'turnit', 'vikspider', 'wget', 'winhttp', 'yandex', 'yandexbot', 'yahoo', 'youda', 'zmeu', 'zune', 'Sidetrade', 'AhrefsBot' );

	$isBot = false;

	$user_agent = wp_kses_data( $_SERVER['HTTP_USER_AGENT'] );

	foreach ( $bots as $bot ) {
		if ( stripos( $user_agent, $bot ) !== false ) {
			$isBot = true;
		}
	}

	if ( empty( $user_agent ) || $user_agent == ' ' ) {
		$isBot = true;
	}

	//This filter can be used to override what you consider bot via your own custom function. You can read the user-agent value from the server var.
	$isBot = apply_filters( 'sdm_visitor_is_bot', $isBot );

	return $isBot;
}

function sdm_get_download_form_with_recaptcha( $id, $args = array(), $class = '' ) {
	$action_url = WP_SIMPLE_DL_MONITOR_SITE_HOME_URL . '/?sdm_process_download=1&download_id=' . $id;

	//Get the download button text
	$button_text = isset( $args['button_text'] ) ? $args['button_text'] : '';
	if ( empty( $button_text ) ) {//Use the default text for the button
		$button_text_string = sdm_get_default_download_button_text( $id );
	} else { //Use the custom text
		$button_text_string = $button_text;
	}

	$main_advanced_opts = get_option( 'sdm_advanced_options' );

	$new_window    = get_post_meta( $id, 'sdm_item_new_window', true );
	$window_target = empty( $new_window ) ? '' : ' target="_blank"';

	$data = '<form action="' . $action_url . '" method="post" class="sdm-g-recaptcha-form sdm-download-form"' . esc_attr($window_target) . '>';

	$data .= '<div class="sdm-recaptcha-button">';
	$data .= '<div class="g-recaptcha sdm-g-recaptcha"></div>';

	//Check if Terms & Condition enabled
	$data .= sdm_get_checkbox_for_termsncond();

	$data .= '<a href="#" class="' . esc_attr($class) . ' sdm_download_with_condition">' . $button_text_string . '</a>';
	$data .= '</div>';
	$data .= '<input type="hidden" name="download_id" value="' . $id . '" />';
	$data .= '</form>';
	return $data;
}

function sdm_get_download_with_recaptcha() {
	$main_advanced_opts = get_option( 'sdm_advanced_options' );
	$recaptcha_enable   = isset( $main_advanced_opts['recaptcha_enable'] ) ? true : false;
	if ( $recaptcha_enable ) {
		return '<div class="g-recaptcha sdm-g-recaptcha"></div>';
	}
	return '';
}

function sdm_get_checkbox_for_termsncond() {
	$main_advanced_opts = get_option( 'sdm_advanced_options' );
	$termscond_enable   = isset( $main_advanced_opts['termscond_enable'] ) ? true : false;
	if ( $termscond_enable ) {
		$data  = '<div class="sdm-termscond-checkbox">';
		$data .= '<input type="checkbox" class="agree_termscond" value="1"/> ' . __( 'I agree to the ', 'simple-download-monitor' ) . '<a href="' . $main_advanced_opts['termscond_url'] . '" target="_blank">' . __( 'terms and conditions', 'simple-download-monitor' ) . '</a>';
		$data .= '</div>';
		return $data;
	}
	return '';
}

function sdm_get_download_form_with_termsncond( $id, $args = array(), $class = '' ) {
	$action_url = WP_SIMPLE_DL_MONITOR_SITE_HOME_URL . '/?sdm_process_download=1&download_id=' . $id;

	//Get the download button text
	$button_text = isset( $args['button_text'] ) ? $args['button_text'] : '';
	if ( empty( $button_text ) ) {//Use the default text for the button
		$button_text_string = sdm_get_default_download_button_text( $id );
	} else { //Use the custom text
		$button_text_string = $button_text;
	}

	$main_advanced_opts = get_option( 'sdm_advanced_options' );
	$termscond_enable   = isset( $main_advanced_opts['termscond_enable'] ) ? true : false;

	$new_window    = get_post_meta( $id, 'sdm_item_new_window', true );
	$window_target = empty( $new_window ) ? '' : ' target="_blank"';

	$data  = '<form action="' . $action_url . '" method="post" class="sdm-download-form"' . $window_target . '>';
	$data .= sdm_get_checkbox_for_termsncond();
	$data .= '<div class="sdm-termscond-button">';
	$data .= '<a href="#" class="' . esc_attr($class) . ' sdm_download_with_condition">' . $button_text_string . '</a>';
	$data .= '</div>';
	$data .= '<input type="hidden" name="download_id" value="' . $id . '" />';
	$data .= '</form>';
	return $data;
}

function sdm_get_default_download_button_text( $download_id ) {
	$default_text = __( 'Download Now!', 'simple-download-monitor' );
	$meta_text    = get_post_meta( $download_id, 'sdm_download_button_text', true );

	$button_text = ! empty( $meta_text ) ? $meta_text : $default_text;
	return $button_text;
}

/*
 * Use this function to read the current page's URL
 */
function sdm_get_current_page_url() {
	$page_url = 'http';

	if ( isset( $_SERVER['SCRIPT_URI'] ) && ! empty( $_SERVER['SCRIPT_URI'] ) ) {
		$page_url = $_SERVER['SCRIPT_URI'];
		$page_url = apply_filters( 'sdm_get_current_page_url', $page_url );
		return $page_url;
	}

	if ( isset( $_SERVER['HTTPS'] ) && ( $_SERVER['HTTPS'] == 'on' ) ) {
		$page_url .= 's';
	}
	$page_url .= '://';
	if ( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] != '80' ) ) {
		$page_url .= ltrim( $_SERVER['SERVER_NAME'], '.*' ) . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
	} else {
		$page_url .= ltrim( $_SERVER['SERVER_NAME'], '.*' ) . $_SERVER['REQUEST_URI'];
	}

	$page_url = apply_filters( 'sdm_get_current_page_url', $page_url );
	return $page_url;
}

/*
 * Use this function to redirect to a URL
 */
function sdm_redirect_to_url( $url, $delay = '0', $exit = '1' ) {
	$url = apply_filters( 'sdm_before_redirect_to_url', $url );
	if ( empty( $url ) ) {
		echo '<strong>';
		_e( 'Error! The URL value is empty. Please specify a correct URL value to redirect to!', 'simple-download-monitor' );
		echo '</strong>';
		exit;
	}
	if ( ! headers_sent() ) {
		header( 'Location: ' . $url );
	} else {
		echo '<meta http-equiv="refresh" content="' . esc_attr( $delay ) . ';url=' . esc_url( $url ) . '" />';
	}
	if ( $exit == '1' ) {//exit
		exit;
	}
}

/*
 * Utility function to insert a download record into the logs DB table. Used by addons sometimes.
 */
function sdm_insert_download_to_logs_table( $download_id ) {
	global $wpdb;

	if ( ! $download_id ) {
		SDM_Debug::log( 'Error! insert to logs function called with incorrect download item id.', false );
		return;
	}

	$main_option = get_option( 'sdm_downloads_options' );

	$download_title = get_the_title( $download_id );
	$download_link  = get_post_meta( $download_id, 'sdm_upload', true );

	$ipaddress = '';
	//Check if do not capture IP is enabled.
	if ( ! isset( $main_option['admin_do_not_capture_ip'] ) ) {
			$ipaddress = sdm_get_ip_address();
	}

	$user_agent = '';
	//Check if do not capture User Agent is enabled.
	if ( ! isset( $main_option['admin_do_not_capture_user_agent'] ) ) {
			//Get the user agent data. The get_browser() function doesn't work on many servers. So use the HTTP var.
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
				$user_agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] );
		}
	}

	$referrer_url = '';
	//Check if do not capture Referer URL is enabled.
	if ( ! isset( $main_option['admin_do_not_capture_referrer_url'] ) ) {
			//Get the user agent data. The get_browser() function doesn't work on many servers. So use the HTTP var.
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$referrer_url = sanitize_text_field( $_SERVER['HTTP_REFERER'] );
		}
	}

	$date_time       = current_time( 'mysql' );
	$visitor_country = ! empty( $ipaddress ) ? sdm_ip_info( $ipaddress, 'country' ) : '';

	$visitor_name = sdm_get_logged_in_user();
	$visitor_name = ( $visitor_name === false ) ? __( 'Not Logged In', 'simple-download-monitor' ) : $visitor_name;

	// Get option for global disabling of download logging
	$no_logs = isset( $main_option['admin_no_logs'] );

	// Get optoin for logging only unique IPs
	$unique_ips = isset( $main_option['admin_log_unique'] );

	// Get post meta for individual disabling of download logging
	$get_meta             = get_post_meta( $download_id, 'sdm_item_no_log', true );
	$item_logging_checked = isset( $get_meta ) && $get_meta === 'on' ? 'on' : 'off';

	$dl_logging_needed = true;

	// Check if download logs have been disabled (globally or per download item)
	if ( $no_logs === true || $item_logging_checked === 'on' ) {
			$dl_logging_needed = false;
	}

	// Check if we are only logging unique ips
	if ( $unique_ips === true ) {
			$check_ip = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'sdm_downloads WHERE post_id="' . $download_id . '" AND visitor_ip = "' . $ipaddress . '"' );

			//This IP is already logged for this download item. No need to log it again.
		if ( $check_ip ) {
				$dl_logging_needed = false;
		}
	}

	// Check if "Do Not Count Downloads from Bots" setting is enabled
	if ( isset( $main_option['admin_dont_log_bots'] ) ) {
			//it is. Now let's check if visitor is a bot
		if ( sdm_visitor_is_bot() ) {
				//visitor is a bot. We neither log nor count this download
				$dl_logging_needed = false;
		}
	}

	if ( $dl_logging_needed ) {
			// We need to log this download.
			$table = $wpdb->prefix . 'sdm_downloads';
			$data  = array(
				'post_id'         => $download_id,
				'post_title'      => $download_title,
				'file_url'        => $download_link,
				'visitor_ip'      => $ipaddress,
				'date_time'       => $date_time,
				'visitor_country' => $visitor_country,
				'visitor_name'    => $visitor_name,
				'user_agent'      => $user_agent,
				'referrer_url'    => $referrer_url,
			);

			$data         = array_filter( $data ); //Remove any null values.
			$insert_table = $wpdb->insert( $table, $data );

			if ( $insert_table ) {
					//Download request was logged successfully
					SDM_Debug::log( 'Download has been logged in the logs table for download ID: ' . $download_id );
			} else {
					//Failed to log the download request
					SDM_Debug::log( 'Error! Failed to log the download request in the database table.', false );
			}
	}
}

function sdm_sanitize_text( $text ) {
	$text = htmlspecialchars( $text );
	$text = strip_tags( $text );
	$text = sanitize_text_field( $text );
	$text = esc_attr( $text );
	return $text;
}

/*
* Useful for using with wp_kses() function.
*/
function sdm_sanitize_allowed_tags() {
	$my_allowed = wp_kses_allowed_html( 'post' );

	// form fields - input
	$my_allowed['input'] = array(
			'class' => array(),
			'id'    => array(),
			'name'  => array(),
			'value' => array(),
			'type'  => array(),
			'step' => array(),
			'min' => array(),
			'checked' => array(),
			'size' => array(),
			'readonly' => array(),
			'style' => array(),
			'placeholder' => array(),
			'required' => array(),
	);
	// select
	$my_allowed['select'] = array(
			'class'  => array(),
			'id'     => array(),
			'name'   => array(),
			'value'  => array(),
			'type'   => array(),
			'placeholder' => array(),
			'required' => array(),
	);
	// select options
	$my_allowed['option'] = array(
			'selected' => array(),
			'value' => array(),
	);
	// button
	$my_allowed['button'] = array(
			'type' => array(),
			'class' => array(),
			'id' => array(),
			'style' => array(),
	);
	// style
	$my_allowed['style'] = array(
			'types' => array(),
	);

	return $my_allowed;
}

/*
* Useful for using with wp_kses() function.
*/
function sdm_sanitize_allowed_tags_expanded() {
	$my_allowed = sdm_sanitize_allowed_tags();

	//Expanded allowed button tags
	if( isset( $my_allowed['input'] ) && is_array( $my_allowed['input'] ) ){
		$input_extra = array(
			'onclick' => array(),
		);
		$my_allowed['input'] = array_merge( $my_allowed['input'] , $input_extra);
	}

	// iframe
	$my_allowed['iframe'] = array(
			'src'             => array(),
			'height'          => array(),
			'width'           => array(),
			'frameborder'     => array(),
			'allowfullscreen' => array(),
	);

	// allow for some inline jquery
	$my_allowed['script'] = array();

	return $my_allowed;
}

/**
 * Retrieves the download button text for a download item.
 * If download is provided, get the custom download button text if available. Else return default text.
 * 
 * @param int|null $download_id The download id to fetch the custom button text if have any.
 * 
 * @return string Download button text.
 */
function get_dl_button_text($download_id = null){
	$default_button_text = __( 'Download Now!', 'simple-download-monitor' );
	if (empty($download_id)) {
		return $default_button_text;
	}

	$custom_button_text = sanitize_text_field(get_post_meta($download_id, 'sdm_download_button_text', true));
	
	return !empty($custom_button_text) ? $custom_button_text : $default_button_text;
}

/**
 * Get the capability settings for SDM admin sections.
 * Default 'manage_options', which is a admin capability. 
 * 
 * @return string User capability to get access to SDM admin. 
 */
function get_sdm_admin_access_permission(){
	$main_opts = get_option( 'sdm_downloads_options' );
	$admin_dashboard_access_permission = isset($main_opts['admin-dashboard-access-permission']) && !empty($main_opts['admin-dashboard-access-permission']) ? sanitize_text_field($main_opts['admin-dashboard-access-permission']) : 'manage_options';
    $admin_dashboard_access_permission = apply_filters("sdm_dashboard_access_role", $admin_dashboard_access_permission);
	return $admin_dashboard_access_permission;
}