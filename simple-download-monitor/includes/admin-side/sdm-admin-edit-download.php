<?php

class SDM_Admin_Edit_Download {

	public function __construct() {
		add_action( 'add_meta_boxes_sdm_downloads', array( $this, 'add_meta_boxes_handler' ) );  // Create metaboxes
		add_action( 'save_post_sdm_downloads', array( $this, 'save_post_handler' ), 10, 3 );
		// Grabs the inserted post data so we can sanitize/modify it.
		add_filter( 'wp_insert_post_data' , array( $this, 'insert_post_sdm_post_title' ), '99', 1 );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_sdm_remove_thumbnail_image', array( $this, 'remove_thumbnail_image_ajax_handler' ) );
		}
	}

	public function add_meta_boxes_handler( $post ) {
		add_meta_box( 'sdm_description_meta_box', __( 'Description', 'simple-download-monitor' ), array( $this, 'display_sdm_description_meta_box' ), 'sdm_downloads', 'normal', 'default' );
		add_meta_box( 'sdm_upload_meta_box', __( 'Downloadable File (Visitors will download this item)', 'simple-download-monitor' ), array( $this, 'display_sdm_upload_meta_box' ), 'sdm_downloads', 'normal', 'default' );
		add_meta_box( 'sdm_dispatch_meta_box', __( 'PHP Dispatch or Redirect', 'simple-download-monitor' ), array( $this, 'display_sdm_dispatch_meta_box' ), 'sdm_downloads', 'normal', 'default' );
		add_meta_box( 'sdm_misc_properties_meta_box', __( 'Miscellaneous Download Item Properties', 'simple-download-monitor' ), array( $this, 'display_sdm_misc_properties_meta_box' ), 'sdm_downloads', 'normal', 'default' ); // Meta box for misc properies/settings
		add_meta_box( 'sdm_thumbnail_meta_box', __( 'File Thumbnail (Optional)', 'simple-download-monitor' ), array( $this, 'display_sdm_thumbnail_meta_box' ), 'sdm_downloads', 'normal', 'default' );
		add_meta_box( 'sdm_stats_meta_box', __( 'Statistics', 'simple-download-monitor' ), array( $this, 'display_sdm_stats_meta_box' ), 'sdm_downloads', 'normal', 'default' );
		do_action( 'sdm_admin_add_edit_download_before_other_details_meta_box_action' );
		add_meta_box( 'sdm_other_details_meta_box', __( 'Other Details (Optional)', 'simple-download-monitor' ), array( $this, 'display_sdm_other_details_meta_box' ), 'sdm_downloads', 'normal', 'default' );
		add_meta_box( 'sdm_shortcode_meta_box', __( 'Shortcodes', 'simple-download-monitor' ), array( $this, 'display_sdm_shortcode_meta_box' ), 'sdm_downloads', 'normal', 'default' );
	}

	public function remove_thumbnail_image_ajax_handler() {

		// terminates the script if the nonce verification fails.
		check_ajax_referer( 'sdm_remove_thumbnail_nonce_action', 'sdm_remove_thumbnail_nonce' );

		$dashboard_access_role = get_sdm_admin_access_permission();
		if ( ! current_user_can( $dashboard_access_role ) ) {
			//not permissions for current user
			wp_die( 'You do not have permission to access this settings page.' );
		}

		// Go ahead with the thumbnail removal
		$post_id    = filter_input( INPUT_POST, 'post_id_del', FILTER_SANITIZE_NUMBER_INT );
		$post_id    = empty( $post_id ) ? 0 : intval( $post_id );
		$key_exists = metadata_exists( 'post', $post_id, 'sdm_upload_thumbnail' );
		if ( $key_exists ) {
			$success = delete_post_meta( $post_id, 'sdm_upload_thumbnail' );
			if ( $success ) {
				$response = array( 'success' => true );
			}
		} else {
			// in order for frontend script to not display "Ajax error", let's return some data
			$response = array( 'not_exists' => true );
		}

		wp_send_json( $response );
	}

	public function display_sdm_description_meta_box( $post ) {
		wp_nonce_field( 'sdm_admin_edit_download_' . $post->ID, 'sdm_admin_edit_download' );

		// Description metabox
		esc_html_e( 'Add a description for this download item.', 'simple-download-monitor' );
		echo '<br /><br />';

		$old_description       = get_post_meta( $post->ID, 'sdm_description', true );
		$sdm_description_field = array( 'textarea_name' => 'sdm_description' );
		wp_editor( $old_description, 'sdm_description_editor_content', $sdm_description_field );
	}

	public function display_sdm_upload_meta_box( $post ) {
		// File Upload metabox
		$old_upload = get_post_meta( $post->ID, 'sdm_upload', true );
		$old_value  = isset( $old_upload ) ? $old_upload : '';

		// Trigger filter to allow "sdm_upload" field validation override.
		$url_validation_override = apply_filters( 'sdm_file_download_url_validation_override', '' );
		if ( ! empty( $url_validation_override ) ) {
			// This site has customized the behavior and overriden the "sdm_upload" field validation. It can be useful if you are offering app download URLs (that has unconventional URL patterns).
		} else {
			// Do the normal URL validation.
			$old_value = esc_url( $old_value );
		}

		esc_html_e( 'Manually enter a valid URL of the file in the text box below, or click "Select File" button to upload (or choose) the downloadable file.', 'simple-download-monitor' );
		echo '<br /><br />';

		echo '<div class="sdm-download-edit-file-url-section">';
		echo '<input id="sdm_upload" type="text" style="width: 95%" name="sdm_upload" value="' . esc_attr( $old_value ) . '" placeholder="http://..." />';
		echo '</div>';

		echo '<br />';
		echo '<input id="upload_image_button" type="button" class="button-primary" value="' . esc_attr__( 'Select File', 'simple-download-monitor' ) . '" />';

		echo '<br /><br />';
		esc_html_e( 'Steps to upload a file or choose one from your media library:', 'simple-download-monitor' );
		echo '<ol>';
		echo '<li>' . esc_html__( 'Hit the "Select File" button.', 'simple-download-monitor' ) . '</li>';
		echo '<li>' . esc_html__( 'Upload a new file or choose an existing one from your media library.', 'simple-download-monitor' ) . '</li>';
		echo '<li>' . esc_html__( 'Click the "Insert" button, this will populate the uploaded file\'s URL in the above text field.', 'simple-download-monitor' ) . '</li>';
		echo '</ol>';
	}

	public function display_sdm_dispatch_meta_box( $post ) {
		$dispatch = get_post_meta( $post->ID, 'sdm_item_dispatch', true );

		if ( $dispatch === '' ) {
			// No value yet (either new item or saved with older version of plugin)
			$screen = get_current_screen();

			if ( $screen->action === 'add' ) {
				// New item: set default value as per plugin settings.
				$main_opts = get_option( 'sdm_downloads_options' );
				$dispatch  = isset( $main_opts['general_default_dispatch_value'] ) && $main_opts['general_default_dispatch_value'];
			}
		}

		echo '<input id="sdm_item_dispatch" type="checkbox" name="sdm_item_dispatch" value="yes"' . checked( true, $dispatch, false ) . ' />';
		echo '<label for="sdm_item_dispatch">' . esc_html__( 'Dispatch the file via PHP directly instead of redirecting to it. PHP Dispatching keeps the download URL hidden. Dispatching works only for local files (files that you uploaded to this site via this plugin or media library).', 'simple-download-monitor' ) . '</label>';
	}

	// Open Download in new window
	public function display_sdm_misc_properties_meta_box( $post ) {

		// Check the open in new window value
		$new_window = get_post_meta( $post->ID, 'sdm_item_new_window', true );
		if ( $new_window === '' ) {
			// No value yet (either new item or saved with older version of plugin)
			$screen = get_current_screen();
			if ( $screen->action === 'add' ) {
				// New item: we can set a default value as per plugin settings. If a general settings is introduced at a later stage.
				// Does nothing at the moment.
			}
		}

		// Check the sdm_item_disable_single_download_page value
		$sdm_item_disable_single_download_page        = get_post_meta( $post->ID, 'sdm_item_disable_single_download_page', true );
		$sdm_item_hide_dl_button_single_download_page = get_post_meta( $post->ID, 'sdm_item_hide_dl_button_single_download_page', true );

		echo '<p> <input id="sdm_item_new_window" type="checkbox" name="sdm_item_new_window" value="yes"' . checked( true, $new_window, false ) . ' />';
		echo '<label for="sdm_item_new_window">' . esc_html__( 'Open download in a new window.', 'simple-download-monitor' ) . '</label> </p>';

		// the new window will have no download button
		echo '<p> <input id="sdm_item_hide_dl_button_single_download_page" type="checkbox" name="sdm_item_hide_dl_button_single_download_page" value="yes"' . checked( true, $sdm_item_hide_dl_button_single_download_page, false ) . ' />';
		echo '<label for="sdm_item_hide_dl_button_single_download_page">';

		$disable_dl_button_label = __( 'Hide the download button on the single download page of this item.', 'simple-download-monitor' );
		echo esc_html( $disable_dl_button_label ) . '</label>';
		echo '</p>';

		echo '<p> <input id="sdm_item_disable_single_download_page" type="checkbox" name="sdm_item_disable_single_download_page" value="yes"' . checked( true, $sdm_item_disable_single_download_page, false ) . ' />';
		echo '<label for="sdm_item_disable_single_download_page">';
		$disable_single_dl_label  = __( 'Disable the single download page for this download item. ', 'simple-download-monitor' );
		$disable_single_dl_label .= __( 'This can be useful if you are using an addon like the ', 'simple-download-monitor' );
		$disable_single_dl_label .= '<a href="https://simple-download-monitor.com/squeeze-form-addon-for-simple-download-monitor/" target="_blank">Squeeze Form</a>.';
		echo wp_kses_post( $disable_single_dl_label ) . '</label>';
		echo '</p>';

		$sdm_item_anonymous_can_download = get_post_meta( $post->ID, 'sdm_item_anonymous_can_download', true );

		echo '<p> <input id="sdm_item_anonymous_can_download" type="checkbox" name="sdm_item_anonymous_can_download" value="yes"' . checked( true, $sdm_item_anonymous_can_download, false ) . ' />';
		echo '<label for="sdm_item_anonymous_can_download">' . esc_html__( 'Ignore "Only Allow Logged-in Users to Download" global setting for this item.', 'simple-download-monitor' ) . '</label> </p>';
	}

	public function display_sdm_thumbnail_meta_box( $post ) {
		// Thumbnail upload metabox
		$old_thumbnail = get_post_meta( $post->ID, 'sdm_upload_thumbnail', true );
		$old_value     = isset( $old_thumbnail ) ? $old_thumbnail : '';
		esc_html_e( 'Manually enter a valid URL, or click "Select Image" to upload (or choose) the file thumbnail image.', 'simple-download-monitor' );
		?>
	<br /><br />
	<input id="sdm_upload_thumbnail" type="text" style="width: 95%" name="sdm_upload_thumbnail" value="<?php echo esc_attr( $old_value ); ?>" placeholder="http://..." />
	<br /><br />
	<input id="upload_thumbnail_button" type="button" class="button-primary" value="<?php esc_attr_e( 'Select Image', 'simple-download-monitor' ); ?>" />
	<!--	Creating the nonce field for csrf protection-->
	<input id="sdm_remove_thumbnail_nonce" type="hidden" value="<?php echo wp_create_nonce( 'sdm_remove_thumbnail_nonce_action' ); ?>"/>
	<input id="remove_thumbnail_button" type="button" class="button" value="<?php esc_attr_e( 'Remove Image', 'simple-download-monitor' ); ?>"/>
	<br /><br />

	<span id="sdm_admin_thumb_preview">
		<?php
		if ( ! empty( $old_value ) ) {
			?>
		<img id="sdm_thumbnail_image" src="<?php echo esc_url( $old_value ); ?>" style="max-width:200px;" />
			<?php
		}
		?>
	</span>

		<?php
		echo '<p class="description">';
		esc_html_e( 'This thumbnail image will be used to create a fancy file download box if you want to use it.', 'simple-download-monitor' );
		echo '</p>';
	}

	public function display_sdm_stats_meta_box( $post ) {
		// Stats metabox
		$old_count = get_post_meta( $post->ID, 'sdm_count_offset', true );
		$value     = isset( $old_count ) && ! empty( $old_count ) ? $old_count : '0';

		// Get checkbox for "disable download logging"
		$no_logs = get_post_meta( $post->ID, 'sdm_item_no_log', true );
		$checked = isset( $no_logs ) && $no_logs === 'on' ? ' checked' : '';

		esc_html_e( 'These are the statistics for this download item.', 'simple-download-monitor' );
		echo '<br /><br />';

		global $wpdb;
		$wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'sdm_downloads WHERE post_id=%s', $post->ID ) );

		echo '<div class="sdm-download-edit-dl-count">';
		esc_html_e( 'Number of Downloads:', 'simple-download-monitor' );
		echo ' <strong>' . esc_html( $wpdb->num_rows ) . '</strong>';
		echo '</div>';

		echo '<div class="sdm-download-edit-offset-count">';
		esc_html_e( 'Offset Count: ', 'simple-download-monitor' );
		echo '<br />';
		echo ' <input type="text" size="10" name="sdm_count_offset" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter any positive or negative numerical value; to offset the download count shown to the visitors (when using the download counter shortcode).', 'simple-download-monitor' ) . '</p>';
		echo '</div>';

		echo '<br />';
		echo '<div class="sdm-download-edit-disable-logging">';
		echo '<input type="checkbox" name="sdm_item_no_log" ' . esc_attr( $checked ) . ' />';
		echo '<span style="margin-left: 5px;"></span>';
		esc_html_e( 'Disable download logging for this item.', 'simple-download-monitor' );
		echo '</div>';
	}

	public function display_sdm_other_details_meta_box( $post ) {
		// Other details metabox
		$show_date_fd                  = get_post_meta( $post->ID, 'sdm_item_show_date_fd', true );
		$sdm_item_show_file_size_fd    = get_post_meta( $post->ID, 'sdm_item_show_file_size_fd', true );
		$sdm_item_show_item_version_fd = get_post_meta( $post->ID, 'sdm_item_show_item_version_fd', true );

		$file_size = get_post_meta( $post->ID, 'sdm_item_file_size', true );
		$file_size = isset( $file_size ) ? $file_size : '';

		$version = get_post_meta( $post->ID, 'sdm_item_version', true );
		$version = isset( $version ) ? $version : '';

		$download_button_text = get_post_meta( $post->ID, 'sdm_download_button_text', true );
		$download_button_text = isset( $download_button_text ) ? $download_button_text : '';

		echo '<div class="sdm-download-edit-filesize">';
		echo '<strong>' . esc_html__( 'File Size: ', 'simple-download-monitor' ) . '</strong>';
		echo '<br />';
		echo ' <input type="text" name="sdm_item_file_size" value="' . esc_attr( $file_size ) . '" size="20" />';
		echo '<p class="description">' . esc_html__( 'Enter the size of this file (example value: 2.15 MB).', 'simple-download-monitor' ) . '</p>';
		echo '<div class="sdm-download-edit-show-file-size"> <input id="sdm_item_show_file_size_fd" type="checkbox" name="sdm_item_show_file_size_fd" value="yes"' . checked( true, $sdm_item_show_file_size_fd, false ) . ' />';
		echo '<label for="sdm_item_show_file_size_fd">' . esc_html__( 'Show file size in fancy display.', 'simple-download-monitor' ) . '</label> </div>';
		echo '</div>';
		echo '<hr />';

		echo '<div class="sdm-download-edit-version">';
		echo '<strong>' . esc_html__( 'Version: ', 'simple-download-monitor' ) . '</strong>';
		echo '<br />';
		echo ' <input type="text" name="sdm_item_version" value="' . esc_attr( $version ) . '" size="20" />';
		echo '<p class="description">' . esc_html__( 'Enter the version number for this item if any (example value: v2.5.10).', 'simple-download-monitor' ) . '</p>';
		echo '<div class="sdm-download-edit-show-item-version"> <input id="sdm_item_show_item_version_fd" type="checkbox" name="sdm_item_show_item_version_fd" value="yes"' . checked( true, $sdm_item_show_item_version_fd, false ) . ' />';
		echo '<label for="sdm_item_show_item_version_fd">' . esc_html__( 'Show version number in fancy display.', 'simple-download-monitor' ) . '</label> </div>';
		echo '</div>';
		echo '<hr />';

		echo '<div class="sdm-download-edit-show-publish-date">';
		echo '<strong>' . esc_html__( 'Publish Date: ', 'simple-download-monitor' ) . '</strong>';
		echo '<br /> <input id="sdm_item_show_date_fd" type="checkbox" name="sdm_item_show_date_fd" value="yes"' . checked( true, $show_date_fd, false ) . ' />';
		echo '<label for="sdm_item_show_date_fd">' . esc_html__( 'Show download published date in fancy display.', 'simple-download-monitor' ) . '</label>';
		echo '</div>';
		echo '<hr />';

		echo '<div class="sdm-download-edit-button-text">';
		echo '<strong>' . esc_html__( 'Download Button Text: ', 'simple-download-monitor' ) . '</strong>';
		echo '<br />';
		echo '<input id="sdm-download-button-text" type="text" name="sdm_download_button_text" value="' . esc_attr( $download_button_text ) . '" />';
		echo '<p class="description">' . esc_html__( 'You can use this field to customize the download now button text of this item.', 'simple-download-monitor' ) . '</p>';
		echo '</div>';
	}

	public function display_sdm_shortcode_meta_box( $post ) {
		// Shortcode metabox
		esc_html_e( 'The following shortcode can be used on posts or pages to embed a download now button for this file. You can also use the shortcode inserter (in the post editor) to add this shortcode to a post or page.', 'simple-download-monitor' );
		echo '<br />';
		$shortcode_text = '[sdm_download id="' . $post->ID . '" fancy="0"]';
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . esc_attr( $shortcode_text ) . "' size='40'>";
		echo '<br /><br />';

		esc_html_e( 'The following shortcode can be used on posts or pages to embed a download now button that includes the title, description, thumbnail image and download counter.', 'simple-download-monitor' );
		echo wp_kses(
			__( ' <a href="https://simple-download-monitor.com/basic-usage-creating-a-simple-downloadable-item/" target="_blank">Click here for more documentation</a>.', 'simple-download-monitor' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
			)
		);                
		echo '<br />';
		$shortcode_text = '[sdm_download id="' . $post->ID . '" fancy="1"]';
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . esc_attr( $shortcode_text ) . "' size='40'>";
		echo '<br /><br />';
                
		esc_html_e( 'The following shortcode can be used to show a download counter for this item.', 'simple-download-monitor' );
		echo '<br />';
		$shortcode_text = '[sdm_download_counter id="' . $post->ID . '"]';
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . esc_attr( $shortcode_text ) . "' size='40'>";

		echo '<br /><br />';
		esc_html_e( 'Direct Download URL.', 'simple-download-monitor' );
		echo '<br />';
		$direct_download_url = WP_SIMPLE_DL_MONITOR_SITE_HOME_URL . '/?sdm_process_download=1&download_id=' . $post->ID;
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . esc_attr( $direct_download_url ) . "' size='40'>";

		echo '<br /><br />';
		esc_html_e( 'Direct Download URL without Tracking Count (Ignore Logging).', 'simple-download-monitor' );
		echo '<br />';
		$direct_download_url_ignore_logging = add_query_arg( array( 'sdm_ignore_logging' => '1' ), $direct_download_url );
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . esc_attr( $direct_download_url_ignore_logging ) . "' size='40'>";

		echo '<br /><br />';
		echo wp_kses(
			__( 'Read the full shortcode <a href="https://simple-download-monitor.com/miscellaneous-shortcodes-and-shortcode-parameters/" target="_blank">usage documentation here</a>.', 'simple-download-monitor' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
			)
		);
	}

	public function insert_post_sdm_post_title( $data ) {
		//Edit the core post data (example: title) at the point it's inserted, rather than updating it afterwards. 
		//It also avoids the danger of creating an infinite loop by triggering update_post within save_post.
		if( isset($data['post_type']) && $data['post_type'] == 'sdm_downloads') { 
			//This is a download item post. Let's modify the title.
			if( isset($data['post_title'])){
				//SDM_Debug::log( 'Post title before: ' . $data['post_title'] );
				$data['post_title'] = sanitize_text_field(stripslashes($data['post_title']));
			}
		}
		return $data; // Returns the modified data.
	}

	public function save_post_handler( $post_id, $post, $update ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! $update || empty( $post_id ) ) {
			return;
		}

        $action = isset( $_POST['action'] ) ? sanitize_text_field( stripslashes ( $_POST['action'] ) ) : '';

		if ( empty( $action ) ) {
			return;
		}
                
		if ( $action == 'inline-save' ){
			//This is a quick edit action. The nonce comes from WordPress's ajax-actions.php.
			//The default wordpress post_save action will handle the standard post data update (for example: the title, slug, date etc).
			check_ajax_referer( 'inlineeditnonce', '_inline_edit' );
		} else {
			//Full post edit. Do the normal nonce check
			check_admin_referer( 'sdm_admin_edit_download_' . $post_id, 'sdm_admin_edit_download' );
		}

		// *** Description  ***
		if ( isset( $_POST['sdm_description'] ) ) {
			update_post_meta( $post_id, 'sdm_description', wp_kses_post( wp_unslash( $_POST['sdm_description'] ) ) );
		}

		// *** File Upload ***
		if ( isset( $_POST['sdm_upload'] ) ) {
			update_post_meta( $post_id, 'sdm_upload', esc_url_raw( $_POST['sdm_upload'], array( 'http', 'https', 'dropbox' ) ) );
		}

		// *** PHP Dispatch or Redirect ***
		$value = filter_input( INPUT_POST, 'sdm_item_dispatch', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'sdm_item_dispatch', $value );

		// *** Miscellaneous Download Item Properties ***
		// Get POST-ed data as boolean value
		$new_window_open                              = filter_input( INPUT_POST, 'sdm_item_new_window', FILTER_VALIDATE_BOOLEAN );
		$sdm_item_hide_dl_button_single_download_page = filter_input( INPUT_POST, 'sdm_item_hide_dl_button_single_download_page', FILTER_VALIDATE_BOOLEAN );
		$sdm_item_disable_single_download_page        = filter_input( INPUT_POST, 'sdm_item_disable_single_download_page', FILTER_VALIDATE_BOOLEAN );
		$sdm_item_anonymous_can_download              = filter_input( INPUT_POST, 'sdm_item_anonymous_can_download', FILTER_VALIDATE_BOOLEAN );

		// Save the data
		update_post_meta( $post_id, 'sdm_item_new_window', $new_window_open );
		update_post_meta( $post_id, 'sdm_item_hide_dl_button_single_download_page', $sdm_item_hide_dl_button_single_download_page );
		update_post_meta( $post_id, 'sdm_item_disable_single_download_page', $sdm_item_disable_single_download_page );
		update_post_meta( $post_id, 'sdm_item_anonymous_can_download', $sdm_item_anonymous_can_download );

		// *** File Thumbnail ***
		if ( isset( $_POST['sdm_upload_thumbnail'] ) ) {
			update_post_meta( $post_id, 'sdm_upload_thumbnail', sanitize_text_field( wp_unslash( $_POST['sdm_upload_thumbnail'] ) ) );
		}

		// *** Statistics ***
		if ( isset( $_POST['sdm_count_offset'] ) && is_numeric( $_POST['sdm_count_offset'] ) ) {
			update_post_meta( $post_id, 'sdm_count_offset', intval( $_POST['sdm_count_offset'] ) );
		}

		// Checkbox for disabling download logging for this item
		if ( isset( $_POST['sdm_item_no_log'] ) ) {
			update_post_meta( $post_id, 'sdm_item_no_log', sanitize_text_field( wp_unslash( $_POST['sdm_item_no_log'] ) ) );
		} else {
			delete_post_meta( $post_id, 'sdm_item_no_log' );
		}

		// *** Other Details ***
		$show_date_fd = filter_input( INPUT_POST, 'sdm_item_show_date_fd', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'sdm_item_show_date_fd', $show_date_fd );

		$sdm_item_show_file_size_fd = filter_input( INPUT_POST, 'sdm_item_show_file_size_fd', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'sdm_item_show_file_size_fd', $sdm_item_show_file_size_fd );

		$sdm_item_show_item_version_fd = filter_input( INPUT_POST, 'sdm_item_show_item_version_fd', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'sdm_item_show_item_version_fd', $sdm_item_show_item_version_fd );

		if ( isset( $_POST['sdm_item_file_size'] ) ) {
			update_post_meta( $post_id, 'sdm_item_file_size', sanitize_text_field( wp_unslash( $_POST['sdm_item_file_size'] ) ) );
		}

		if ( isset( $_POST['sdm_item_version'] ) ) {
			update_post_meta( $post_id, 'sdm_item_version', sanitize_text_field( wp_unslash( $_POST['sdm_item_version'] ) ) );
		}

		if ( isset( $_POST['sdm_download_button_text'] ) ) {
			update_post_meta( $post_id, 'sdm_download_button_text', sanitize_text_field( wp_unslash( $_POST['sdm_download_button_text'] ) ) );
		}
	}
}

new SDM_Admin_Edit_Download();
