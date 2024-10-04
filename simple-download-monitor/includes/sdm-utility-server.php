<?php

class SDM_Utils_Server {

	public static function get_server_software(){
		return $_SERVER['SERVER_SOFTWARE'];
	}

	public static function get_php_version() {
		return phpversion();
	}

	public static function get_server_os() {
		return php_uname();
	}

	public static function is_nginx_server() {
		$server_software = self::get_server_software();
		$pattern = "/nginx/i";
		if (preg_match($pattern, $server_software)) {
			return true;
		}

		return false;
	}

	public static function is_apache_server() {
		$server_software = self::get_server_software();
		$pattern = "/apache/i";
		if (preg_match($pattern, $server_software)) {
			return true;
		}

		return false;
	}

	public static function is_litespeed_server() {
		$server_software = self::get_server_software();
		$pattern = "/litespeed/i";
		if (preg_match($pattern, $server_software)) {
			return true;
		}

		return false;
	}
}