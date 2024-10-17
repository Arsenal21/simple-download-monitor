<?php

class SDM_File_Protection_Handler {

	public static $protected_dir_name = 'sdm-uploads';

	public static $protected_file_thumbnail = 'sdm-file-protected-thumbnail.png';

	public function __construct() {
		if(is_admin()) {
			// Listen to file protection settings update event.
			add_action('sdm_file_protection_settings_updated', array($this, 'prepare_file_protection_environment'));

			// Upload protected files to custom directory
			add_filter('upload_dir', array($this, 'override_wp_media_upload_directory_path'));

			// Add special post meta to protected files.
			add_action('add_attachment', array($this, 'add_custom_meta_to_specific_directory_media') );

			// Override protected file thumbnail in media library grid view.
			add_filter('wp_prepare_attachment_for_js', array($this, 'override_media_library_protected_file_thumbnail'), 10, 3 );

			// Exclude the custom thumbnail attachment in media library.
			add_action('pre_get_posts', array($this, 'exclude_our_hidden_attachments_in_media_library' ) );
		}
	}

	/**
	 * Add necessary post meta to the protected files at upload time.
	 *
	 * @param $post_ID int uploaded attachment post ID.
	 *
	 * @return void
	 */
	public function add_custom_meta_to_specific_directory_media( $post_ID ) {
		// Get the file path of the uploaded attachment
		$file_path = get_attached_file( $post_ID );

		$is_image_attachment = wp_attachment_is_image($post_ID);

		// Check if its a image type attachment and the file path contains the sdm protected directory
		if ( $is_image_attachment && self::contains_protected_dirname($file_path) ) {

			$settings = get_option( 'sdm_global_options' );

			$thumbnail_attachment_id   = isset( $settings['protected_file_thumbnail_id'] ) && !empty($settings['protected_file_thumbnail_id']) ? sanitize_text_field($settings['protected_file_thumbnail_id']) : '';

			if (!empty($thumbnail_attachment_id)){
				$meta_key = '_thumbnail_id'; // This meta is used to display thumbnail in the media library list view page by wordpress.
				$meta_value = intval($thumbnail_attachment_id); // This attachment will be used to display the thumbnail of protected files.

				// Add the post meta
				add_post_meta( $post_ID, $meta_key, $meta_value, true );
			}
		}
	}

	public static function get_protected_dir_name(){
		return self::$protected_dir_name;
	}

	public static function get_protected_dir(){
		return WP_CONTENT_DIR. '/uploads/'. self::get_protected_dir_name();
	}

	public static function get_protected_file_thumb_url(){
		return WP_SIMPLE_DL_MONITOR_URL . "/images/" . self::$protected_file_thumbnail;
	}

	/**
	 * Check whether file protection is enabled or not.
	 *
	 * @return bool
	 */
	public static function is_file_protection_enabled(){
		$settings = get_option( 'sdm_global_options' );
		$file_protection_enable = isset( $settings['file_protection_enable'] ) && !empty($settings['file_protection_enable']) ? true : false;

		return $file_protection_enable;
	}

	/**
	 * Check whether the attachment exists or not.
	 *
	 * @param $attachment_id
	 *
	 * @return bool
	 */
	public static function does_attachment_exist( $attachment_id ) {
		$attachment = get_post( $attachment_id );

		// Check if the post exists and if it's of type 'attachment'
		if ( !is_null($attachment) && $attachment->post_type === 'attachment' ) {
			return true;
		}

		return false;
	}


	/**
	 *
	 * @param string $uri Download URI
	 *
	 * @return bool
	 */
	public static function contains_protected_dirname($uri){
		$pattern = "/uploads\/". self::get_protected_dir_name() . "/i";  // Using concatenation
		if (preg_match($pattern, $uri)) {
			return true;
		}
		return false;
	}

	public function prepare_file_protection_environment(){
		if( !self::is_file_protection_enabled() ){
			return;
		}

		SDM_Debug::log("File protection setting updated and protection is enabled. Preparing environment for protected files if needed.", true);

		//Check if the protected folder and htaccess is available or not.
		$this->check_and_create_file_protection_folder();
		
		//Check if the protected file thumbnail available or not.
		$this->check_and_create_protected_file_thumbnail();
	}
	
	public function check_and_create_file_protection_folder(){
		// Define the directory path
		$uploads_dir = self::get_protected_dir();
		// Check if the sdm_uploads directory exists
		if ( !is_dir($uploads_dir) ) {
			// Try to create the directory with correct permissions (0755)
			try{
				mkdir($uploads_dir, 0755, true);
				SDM_Debug::log("The directory '".self::get_protected_dir_name() . "' was successfully created.", true);
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
				SDM_Debug::log("The .htaccess file was successfully created inside '".self::get_protected_dir_name() . "'.", true);
				file_put_contents($htaccess_file, $htaccess_content);
			} catch (\Exception $e) {
				wp_die(esc_html($e->getMessage()));
			}
		}
	}


	/**
	 * Check and create thumbnail attachment and related data if not created already.
	 * 
	 * @return void
	 */
	public function check_and_create_protected_file_thumbnail(){

		$settings = get_option( 'sdm_global_options' );

		$thumbnail_created = isset( $settings['protected_file_thumbnail_created'] ) && !empty($settings['protected_file_thumbnail_created']) ? sanitize_text_field($settings['protected_file_thumbnail_created']) : 'no';
		$thumbnail_id = isset( $settings['protected_file_thumbnail_id'] ) && !empty($settings['protected_file_thumbnail_id']) ? sanitize_text_field($settings['protected_file_thumbnail_id']) : '';

		if( $thumbnail_created === 'yes' && !empty($thumbnail_id) && self::does_attachment_exist($thumbnail_id) ){
			return;
		}

		$thumbnail_id = $this->create_protected_file_thumbnail_attachment();

		// Update Settings Options.
		$settings['protected_file_thumbnail_created'] = 'yes';
		$settings['protected_file_thumbnail_id'] = $thumbnail_id;
		update_option('sdm_global_options', $settings );

		SDM_Debug::log("The protected file thumbnail attachment post was successfully created. Attachment ID: ". $thumbnail_id, true);
	}

	/**
	 * Create an attachment post type for protected file's custom thumbnail.
	 *
	 * @return int|WP_Error The ID of created attachment post.
	 */
	private function create_protected_file_thumbnail_attachment() {
		$thumbnail_url = self::get_protected_file_thumb_url();

		// Get the custom thumbnail data and create attachment post.
		$upload_dir = wp_upload_dir();
		$thumbnail_image_data = file_get_contents( $thumbnail_url );
		$thumbnail_filename   = basename( $thumbnail_url );

		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			$file = $upload_dir['path'] . DIRECTORY_SEPARATOR . $thumbnail_filename;
		} else {
			$file = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $thumbnail_filename;
		}

		file_put_contents( $file, $thumbnail_image_data );

		// Check the file type
		$filetype = wp_check_filetype( $thumbnail_filename, null );

		// Create the attachment post data array
		$attachment = array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name( $thumbnail_filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert the attachment into the media library.
		$attachment_id = wp_insert_attachment( $attachment, $file, 0, true );

		add_post_meta( $attachment_id, '_exclude_from_media_library', true );

		if (is_wp_error($attachment_id)){
			SDM_Debug::log('Protected file thumbnail attachment could not be created!', false);
			wp_die($attachment_id->get_error_message());
		}

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $file );
		wp_update_attachment_metadata( $attachment_id, $attachment_metadata );

		return $attachment_id;
	}

	public function override_wp_media_upload_directory_path($upload) {
		// Check if the custom sdm directory is set in the request
		if ( isset($_POST['sdm_upload_to_protected_dir']) && !empty($_POST['sdm_upload_to_protected_dir']) ) {
			// Use the custom sdm directory provided in the POST request
			$dir = self::get_protected_dir_name();

			// Set the custom sdm upload directory path
			$upload['path'] = $upload['basedir'] . '/' . $dir;
			$upload['url'] = $upload['baseurl'] . '/' . $dir;
			$upload['subdir'] = '/' . $dir;
		}

		return $upload;
	}

	public function override_media_library_protected_file_thumbnail( $response, $attachment, $meta ) {
		$dl_link = $attachment->guid;

		if(!SDM_File_Protection_Handler::contains_protected_dirname($dl_link)){
			return $response;
		}

		$attachment_post_thumbnail_id = get_post_thumbnail_id($attachment->ID);

		// Check if it has a customized thumbnail attachment.
		if ( $attachment_post_thumbnail_id && isset($response['sizes']) && !empty($response['sizes'])) {
			if(is_array($response['sizes'])){
				foreach ($response['sizes'] as $size_name => $size_info){
					$thumbnail = wp_get_attachment_image_src($attachment_post_thumbnail_id, $size_name, true);
					if (!empty($thumbnail)){
						$thumbnail_url = isset($thumbnail[0]) ? $thumbnail[0] : '';
						$thumbnail_height = isset($thumbnail[2]) ? $thumbnail[2] : '';
						$thumbnail_width = isset($thumbnail[1]) ? $thumbnail[1] : '';
	
						$response['sizes'][$size_name]['url'] = $thumbnail_url;
						$response['sizes'][$size_name]['height'] = $thumbnail_height;
						$response['sizes'][$size_name]['width'] = $thumbnail_width;
						$response['sizes'][$size_name]['orientation'] = 'portrait';
					}
				}
			} else {
				// If the sizes is not an array, then it's a string. We can handle this case as well.
				SDM_Debug::log("Could not override protected image file's thubmnail because the response['sizes'] is not an array.", false);

			}

		}

		return $response;
	}

	public function exclude_our_hidden_attachments_in_media_library( $query ) {
		//Note: We only run this hook fom admin dashboard side. When is_admin() is true.

		if( !self::is_file_protection_enabled() ){
			// File protection is not enabled. Nothing to do here.
			// We don't update/modify the query if the file protection is not enabled. Updating the query can cause conflict with plugins such as WP Fastest Cache.
			return;
		}

		if ( $query->get('post_type') !== 'attachment' ) {
			return;
		}

		$old_meta_queries = $query->get('meta_query');

		// SDM_Debug::log_array_data($old_meta_queries); // Debug Purpose.

		$meta_query = array();

		// Check for existing meta queries.
		if (is_array($old_meta_queries) && !empty($old_meta_queries)){
			$meta_query = array_merge($meta_query, $old_meta_queries);
		}

		// Only include attachment posts that does not have '_exclude_from_media_library' meta.
		// That meta is only set to our protected file thumbnail attachment post, which we don't want to show it to users.
		$meta_query[] = array(
			'key'     => '_exclude_from_media_library',
			'compare' => 'NOT EXISTS',
		);

		$query->set( 'meta_query', $meta_query );
	}

}

new SDM_File_Protection_Handler();