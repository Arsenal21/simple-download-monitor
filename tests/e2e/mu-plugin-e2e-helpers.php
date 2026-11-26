<?php
/**
 * Endpoint tests only (installed as a must-use plugin on the GitHub Actions test site, never on a real site).
 *
 * - Answers the visitor country lookup that the plugin makes for every logged download (sdm_ip_info() calls https://ipwhois.app/json/<ip>)
 *   with a fixed answer (United States), so the tests don't depend on that service and can check the logged country.
 *   Only an HTTPS lookup is answered (a plain HTTP one is blocked, so the logged country would be empty and the tests would fail).
 * - Answers the reCAPTCHA verification (https://www.google.com/recaptcha/api/siteverify): the token "e2e-valid-token" with the
 *   secret key "e2e-secret&key" (it has a "&", so it must be sent encoded) is valid, any other token isn't, and the token
 *   "e2e-network-error" is a failed request (as if Google can't be reached).
 * - Blocks every other request to an outside site, so the tests never depend on (or send data to) outside services.
 */
const SDM_E2E_RECAPTCHA_SECRET = 'e2e-secret&key';
const SDM_E2E_RECAPTCHA_TOKEN  = 'e2e-valid-token';
add_filter(
	'pre_http_request',
	function ( $pre, $args, $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

		if ( 'ipwhois.app' === $host && 'https' === strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
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

		if ( 'www.google.com' === $host && '/recaptcha/api/siteverify' === wp_parse_url( $url, PHP_URL_PATH ) ) {
			//The values can be in the POST body or in the URL.
			$fields = array();
			parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $fields );
			if ( isset( $args['body'] ) && is_array( $args['body'] ) ) {
				$fields = array_merge( $fields, $args['body'] );
			}
			$token  = isset( $fields['response'] ) ? $fields['response'] : '';
			$secret = isset( $fields['secret'] ) ? $fields['secret'] : '';
			if ( 'e2e-network-error' === $token ) {
				return new WP_Error( 'http_request_failed', 'cURL error 28: Connection timed out (endpoint tests)' );
			}
			return array(
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => wp_json_encode( array( 'success' => ( SDM_E2E_RECAPTCHA_TOKEN === $token && SDM_E2E_RECAPTCHA_SECRET === $secret ) ) ),
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
