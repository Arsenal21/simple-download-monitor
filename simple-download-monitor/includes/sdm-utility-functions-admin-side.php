<?php

function sdm_export_download_logs_to_csv( $start_date, $end_date ) {
	//appending time to start and end date
	$start_date_time = $start_date . ' 00:00:00';
	$end_date_time   = $end_date . ' 23:59:59';

	global $wpdb;
	$table_name      = $wpdb->prefix . 'sdm_downloads';
	$resultset_query = $wpdb->prepare( "SELECT * FROM $table_name WHERE date_time BETWEEN %s AND %s ORDER BY id DESC", $start_date_time, $end_date_time );
	$resultset       = $wpdb->get_results( $resultset_query, OBJECT );

	$csv_file_name = sprintf( 'sdm-download-logs-%s_%s.csv', $start_date, $end_date );
	header( 'Content-Type: text/csv' );
	header( 'Content-Disposition: attachment; filename="' . $csv_file_name . '"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	$fp = fopen( 'php://output', 'w' );

	$header_names = array( 'Log ID', 'Download ID', 'Download Title', 'File URL', 'Date', 'IP Address', 'Country', 'Name' );
	fputcsv( $fp, $header_names );

	foreach ( $resultset as $result ) {
		if ( empty( $result->purchase_qty ) ) {
			$result->purchase_qty = 1;
		}

		$fields = array( $result->id, $result->post_id, $result->post_title, $result->file_url, $result->date_time, $result->visitor_ip, $result->visitor_country, $result->visitor_name, $result->user_agent );
		fputcsv( $fp, $fields );
	}

	exit();
}

function sdm_get_downloads_by_date( $start_date = '', $end_date = '', $returnStr = true ) {
	global $wpdb;

	$q = $wpdb->prepare(
		"SELECT COUNT(id) as cnt, DATE_FORMAT(`date_time`,'%%Y-%%m-%%d') as day
            FROM " . $wpdb->prefix . "sdm_downloads
            WHERE DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')>=%s
            AND DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')<=%s
            GROUP BY day ORDER BY date_time",
		$start_date,
		$end_date
	);

	$res = $wpdb->get_results( $q, ARRAY_A );
	if ( $returnStr ) {
		$downloads_by_date_str = '';
		foreach ( $res as $item ) {
			$downloads_by_date_str .= '["' . $item['day'] . '", ' . $item['cnt'] . '],';
		}
		return $downloads_by_date_str;
	} else {
		return $res;
	}
}

function sdm_get_downloads_by_country( $start_date = '', $end_date = '', $returnStr = true ) {
	global $wpdb;

	$q   = $wpdb->prepare(
		'SELECT COUNT(id) as cnt, visitor_country as country
            FROM ' . $wpdb->prefix . "sdm_downloads
            WHERE DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')>=%s
            AND DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')<=%s
            GROUP BY visitor_country",
		$start_date,
		$end_date
	);
	$res = $wpdb->get_results( $q, ARRAY_A );

	if ( $returnStr ) {
		$downloads_by_country_str = "['Country', 'Downloads'],";
		foreach ( $res as $item ) {
			$downloads_by_country_str .= '["' . $item['country'] . '", ' . $item['cnt'] . '],';
		}
		return $downloads_by_country_str;
	} else {
		return $res;
	}
}

/**
 * Retrieves all user agent fields form downloads
 *
 * @param string $start_date
 * @param string $end_date
 *
 * @return array
 */
function sdm_get_all_download_user_agent( $start_date = '', $end_date = '' ) {
	global $wpdb;

	$q = $wpdb->prepare(
		'SELECT user_agent
            FROM ' . $wpdb->prefix . "sdm_downloads
            WHERE DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')>=%s
            AND DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')<=%s",
		$start_date,
		$end_date
	);

	return $wpdb->get_results( $q, ARRAY_A );
}

/**
 * Processes all user agent to browser
 *
 * @param string $start_date
 * @param string $end_date
 *
 * @return array
 */

function sdm_get_all_downloads_by_browser( $start_date = '', $end_date = '' ) {
	$user_agents = sdm_get_all_download_user_agent( $start_date, $end_date );

	$browsers = array();
	foreach ( $user_agents as $agent ) {
		$browserArray = array(
			'Microsoft Edge'    => 'Edg',
			'Opera'             => '(OPR)|(OPX)',
			'Vivaldi'           => 'Vivaldi',
			'Firefox'           => 'Firefox',
			'Samsung Browser'   => 'SamsungBrowser',
			'Chrome'            => 'Chrome',
			'Internet Explorer' => 'MSIE',
			'Safari'            => 'Safari',
		);
		$browser      = 'Other';
		foreach ( $browserArray as $k => $v ) {
			if ( preg_match( "/$v/", $agent['user_agent'] ) ) {
				$browser = $k;
				break;
			}
		}
		if ( isset( $browsers[ $browser ] ) ) {
			$browsers[ $browser ] += 1;
		} else {
			$browsers[ $browser ] = 1;
		}
	}
	return moveArrayElementToEnd( $browsers, 'Other' );
}

/**
 * Processes all user agent to operating system
 *
 * @param string $start_date
 * @param string $end_date
 *
 * @return array
 */

function sdm_get_all_downloads_by_os( $start_date = '', $end_date = '' ) {
	$user_agents = sdm_get_all_download_user_agent( $start_date, $end_date );

	$operating_systems = array();
	foreach ( $user_agents as $agent ) {
		$osArray = array(
			'Windows 10 Phone' => '(Windows Phone)|(Microsoft; Lumia)',
			'Android'          => '(Linux; Android)|Android',
			'ChromeOS'         => '(X11; CrOS)',
			'SymbianOS'        => 'SymbianOS',
			'Windows 98'       => '(Win98)|(Windows 98)',
			'Windows 2000'     => '(Windows 2000)|(Windows NT 5.0)',
			'Windows ME'       => 'Windows ME',
			'Windows XP'       => '(Windows XP)|(Windows NT 5.1)',
			'Windows Vista'    => 'Windows NT 6.0',
			'Windows 8'        => 'Windows NT 6.2',
			'Windows 8.1'      => 'Windows NT 6.3',
			'Windows 7'        => '(Windows NT 6.1)|(Windows NT 7.0)',
			'Windows 10'       => 'Windows NT 10.0',
			'Linux'            => '(X11)|(Linux)',
			'iOS'              => '(Apple-iPhone)|(iPhone)|(iPhone OS)',
			'macOS'            => '(Mac_PowerPC)|(Macintosh)|(Mac OS)',
		);
		$os      = 'Other';
		foreach ( $osArray as $k => $v ) {
			if ( preg_match( "/$v/", $agent['user_agent'] ) ) {
				$os = $k;
				break;
			}
		}
		if ( isset( $operating_systems[ $os ] ) ) {
			$operating_systems[ $os ] += 1;
		} else {
			$operating_systems[ $os ] = 1;
		}
	}
	return moveArrayElementToEnd( $operating_systems, 'Other' );
}

/**
 * Retrieves top user by total download count.
 *
 * @param string $start_date
 * @param string $end_date
 * @param int    $limit Total number of records to retrieve
 *
 * @return array
 */
function sdm_get_top_users_by_download_count( $start_date = '', $end_date = '', $limit = 25 ) {
	global $wpdb;

	$q   = $wpdb->prepare(
		'SELECT COUNT(id) as cnt, visitor_name
            FROM ' . $wpdb->prefix . "sdm_downloads
            WHERE DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')>=%s
            AND DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')<=%s
            GROUP BY visitor_name 
			ORDER BY cnt DESC 
			LIMIT $limit",
		$start_date,
		$end_date
	);
	$res = $wpdb->get_results( $q, ARRAY_A );

	return $res;
}

/**
 * Retrieves top downloads by download count
 *
 * @param string $start_date
 * @param string $end_date
 * @param int    $limit Total number of records to retrieve
 *
 * @return array
 */
function sdm_get_top_downloads_by_count( $start_date = '', $end_date = '', $limit = 25 ) {
	global $wpdb;

	$q   = $wpdb->prepare(
		'SELECT COUNT(id) as cnt, post_title
            FROM ' . $wpdb->prefix . "sdm_downloads
            WHERE DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')>=%s
            AND DATE_FORMAT(`date_time`,'%%Y-%%m-%%d')<=%s
            GROUP BY post_title 
            ORDER BY cnt DESC LIMIT $limit",
		$start_date,
		$end_date
	);
	$res = $wpdb->get_results( $q, ARRAY_A );

	return $res;
}

/**
 * Checks if valid date or not
 *
 * @param mixed $data
 *
 * @return boolean
 */
function sdm_validate_date_field( $data ) {
	if ( is_array( $data ) ) {
		foreach ( $data as $date ) {
			$date_elements = explode( '-', $date );

			$year  = isset( $date_elements[0] ) ? $date_elements[0] : null;
			$month = isset( $date_elements[1] ) ? $date_elements[1] : null;
			$day   = isset( $date_elements[2] ) ? $date_elements[2] : null;

			return checkdate( (int) $month, (int) $day, (int) $year );
		}
	}
	$date_elements = explode( '-', $data );

	$year  = isset( $date_elements[0] ) ? $date_elements[0] : null;
	$month = isset( $date_elements[1] ) ? $date_elements[1] : null;
	$day   = isset( $date_elements[2] ) ? $date_elements[2] : null;

	return checkdate( (int) $month, (int) $day, (int) $year );
}

/**
 * move an array element by its key to the end.
 *
 * @param array      $array The array being reordered.
 * @param string|int $key They key of the element you want to move.
 *
 * @return array
 */
function moveArrayElementToEnd( array &$array, $key ) {
	if ( ( $k = array_search( $key, array_keys( $array ) ) ) === false ) {
		return $array;
	}

	$p1    = array_splice( $array, $k, 1 );
	$p2    = array_splice( $array, 0, count( $array ) );
	$array = array_merge( $p2, $p1, $array );

	return $array;
}
