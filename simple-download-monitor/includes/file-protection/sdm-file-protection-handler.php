<?php

class SDM_File_Protection_Handler {

	public static $protected_dir = 'sdm_uploads';

	public function __construct()
	{
		
	}

	public static function get_upload_dir(){
		return self::$protected_dir;
	}

	public static function is_file_protection_enable(){
		$main_option = get_option( 'sdm_advanced_options' );
		$file_protection_enable   = isset( $main_option['file_protection_enable'] ) && !empty($main_option['file_protection_enable']) ? true : false;

		return $file_protection_enable;
	}

	public static function sdm_check_security_settings(){
		if(!self::is_file_protection_enable()){
			return;
		}
		
		//Check if 'sdm_uploads' special folder and htaccess is available or not
	
		// Define the directory path
		$uploads_dir = ABSPATH. '/wp-content/uploads/sdm_uploads';
		// Check if the sdm_uploads directory exists
		if ( !is_dir($uploads_dir) ) {
			// Try to create the directory with correct permissions (0755)
			try{
				mkdir($uploads_dir, 0755, true);
				SDM_Debug::log("The directory 'sdm_uploads' was successfully created.", true);
			}catch(\Exception $e){
				wp_die(esc_html($e->getMessage()));
			}
		}
	
		// Define the directory path
		$htaccess_file = $uploads_dir . '/.htaccess';
		if ( !file_exists($htaccess_file) ) {
			try{
				// Create the .htaccess file
				$htaccess_content = "deny from all\n";
				SDM_Debug::log("The .htaccess file was successfully created inside 'sdm_uploads'.", true);
				file_put_contents($htaccess_file, $htaccess_content);
			} catch (\Exception $e) {
				wp_die(esc_html($e->getMessage()));
			}
		}
	
		add_filter('upload_dir', 'SDM_File_Protection_Handler::sdm_protected_upload_directory');
	}
	
	public static function sdm_protected_upload_directory($upload) {
		// Check if the custom sdm directory is set in the request
		if (isset($_POST['sdm_upload_to_protected_dir'])) {
			// Use the custom sdm directory provided in the POST request
			$dir = self::get_upload_dir();
			
			// Set the custom sdm upload directory path
			$upload['path'] = $upload['basedir'] . '/' . $dir;
			$upload['url'] = $upload['baseurl'] . '/' . $dir;
			$upload['subdir'] = '/' . $dir;
		}
	
		return $upload;
	}

}

// new SDM_File_Protection_Handler();