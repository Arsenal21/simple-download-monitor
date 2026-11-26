<?php
/**
 * Endpoint tests only (installed as a must-use plugin on the GitHub Actions test site, never on a real site).
 *
 * - Answers the visitor country lookup that the plugin makes for every logged download (sdm_ip_info() calls http://ipwhois.app/json/<ip>)
 *   with a fixed answer (United States), so the tests don't depend on that service and can check the logged country.
 * - Blocks every other request to an outside site, so the tests never depend on (or send data to) outside services.
 */
add_filter(
	'pre_http_request',
	function ( $pre, $args, $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

		if ( 'ipwhois.app' === $host ) {
			return array(
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => wp_json_encode(
					array(
						'country_code'   => 'US',
						'country'        => 'United States',
						'region'         => 'California',
						'city'           => 'San Francisco',
						'continent_code' => 'NA',
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		//Requests to the test site itself are allowed.
		if ( in_array( $host, array( '127.0.0.1', 'localhost' ), true ) ) {
			return $pre;
		}

		return new WP_Error( 'sdm_e2e_blocked', 'Requests to outside sites are blocked on the endpoint test site: ' . $url );
	},
	10,
	3
);
