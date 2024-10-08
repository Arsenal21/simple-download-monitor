<?php

class SDM_File_Protection_Handler {

	public static $protected_dir = 'sdm-uploads';

	public static $protected_file_thumbnail = 'sdm-file-protected.png';

	public function __construct() {
		
	}

	public static function get_upload_dir(){
		return self::$protected_dir;
	}

	public static function get_protected_file_thumb_url(){
		return WP_SIMPLE_DL_MONITOR_URL . "/images/" . self::$protected_file_thumbnail;
	}

	public static function is_file_protection_enabled(){
		$main_option = get_option( 'sdm_advanced_options' );
		$file_protection_enable   = isset( $main_option['file_protection_enable'] ) && !empty($main_option['file_protection_enable']) ? true : false;

		return $file_protection_enable;
	}

	public static function prepare_file_protection_environment(){
		if( !self::is_file_protection_enabled() ){
			return;
		}
		
		// Add the filter that will be used to tell WP media uploader which folder to upload to.
		add_filter('upload_dir', 'SDM_File_Protection_Handler::override_wp_media_upload_directory_path');

		//Check if the protected folder and htaccess is available or not.
		if( is_sdm_admin_page() ){
			self::check_and_create_file_protection_folder();
			// self::check_and_create_file_thumbnail();
		}
	}
	
	public static function check_and_create_file_protection_folder(){
		// Define the directory path
		$uploads_dir = ABSPATH. '/wp-content/uploads/'. self::get_upload_dir();
		// Check if the sdm_uploads directory exists
		if ( !is_dir($uploads_dir) ) {
			// Try to create the directory with correct permissions (0755)
			try{
				mkdir($uploads_dir, 0755, true);
				SDM_Debug::log("The directory '".self::get_upload_dir()."' was successfully created.", true);
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
				SDM_Debug::log("The .htaccess file was successfully created inside '".self::get_upload_dir()."'.", true);
				file_put_contents($htaccess_file, $htaccess_content);
			} catch (\Exception $e) {
				wp_die(esc_html($e->getMessage()));
			}
		}
	}

	// public static function check_and_create_file_thumbnail(){
	// 	if(empty(get_option('sdm_protected_file_thumbnail_123', false))){
	// 		$thumbnail_id = self::create_protected_file_thumbnail_attachment();
	// 		update_option('sdm_protected_file_thumbnail_123', $thumbnail_id );
	// 	}
	// }

	// public static function create_protected_file_thumbnail_attachment($parent_attachment_id = 0) {
	// 	$thumbnail_url = self::get_protected_file_thumb_url();
	// 	// Download the custom thumbnail image and save it to the uploads folder
	// 	$upload_dir = wp_upload_dir();
	// 	$image_data = file_get_contents($thumbnail_url);
	// 	$filename = basename($thumbnail_url);
	
	// 	if ( wp_mkdir_p($upload_dir['path']) ) {
	// 		$file = $upload_dir['path'] . '/' . $filename;
	// 	} else {
	// 		$file = $upload_dir['basedir'] . '/' . $filename;
	// 	}
	
	// 	file_put_contents($file, $image_data);
	
	// 	// Check the file type
	// 	$wp_filetype = wp_check_filetype($filename, null);
	
	// 	// Create the attachment array
	// 	$attachment = array(
	// 		'post_mime_type' => $wp_filetype['type'],
	// 		'post_title'     => sanitize_file_name($filename),
	// 		'post_content'   => '',
	// 		// 'post_status'    => 'private', // Set the attachment as 'private' so it won't appear in the wp media library
	// 		'post_status'    => 'inherit',
	// 	);
	
	// 	// Insert the attachment into the media library
	// 	$thumbnail_id = wp_insert_attachment($attachment, $file, $parent_attachment_id);
	
	// 	// Generate attachment metadata
	// 	require_once(ABSPATH . 'wp-admin/includes/image.php');
	// 	$attach_data = wp_generate_attachment_metadata($thumbnail_id, $file);
	// 	wp_update_attachment_metadata($thumbnail_id, $attach_data);
	
	// 	return $thumbnail_id;
	// }

	public static function override_wp_media_upload_directory_path($upload) {
		// Check if the custom sdm directory is set in the request
		if (  isset($_POST['sdm_upload_to_protected_dir']) && !empty($_POST['sdm_upload_to_protected_dir']) ) {
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