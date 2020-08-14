<?php
/**
 * Plugin Name: Simple Download Monitor
 * Plugin URI: https://simple-download-monitor.com/
 * Description: Easily manage downloadable files and monitor downloads of your digital files from your WordPress site.
 * Version: 3.8.9
 * Author: Tips and Tricks HQ, Ruhul Amin, Josh Lobe
 * Author URI: https://www.tipsandtricks-hq.com/development-center
 * License: GPL2
 * Text Domain: simple-download-monitor
 * Domain Path: /languages/
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_SIMPLE_DL_MONITOR_VERSION', '3.8.9' );
define( 'WP_SIMPLE_DL_MONITOR_DIR_NAME', dirname( plugin_basename( __FILE__ ) ) );
define( 'WP_SIMPLE_DL_MONITOR_URL', plugins_url( '', __FILE__ ) );
define( 'WP_SIMPLE_DL_MONITOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_SIMPLE_DL_MONITOR_SITE_HOME_URL', home_url() );
define( 'WP_SDM_LOG_FILE', WP_SIMPLE_DL_MONITOR_PATH . 'sdm-debug-log.txt' );

global $sdm_db_version;
$sdm_db_version = '1.4';

//File includes
require_once 'includes/sdm-debug.php';
require_once 'includes/sdm-utility-functions.php';
require_once 'includes/sdm-utility-functions-admin-side.php';
require_once 'includes/sdm-download-request-handler.php';
require_once 'includes/sdm-user-login-related.php';
require_once 'includes/sdm-logs-list-table.php';
require_once 'includes/sdm-latest-downloads.php';
require_once 'includes/sdm-popular-downloads.php';
require_once 'includes/sdm-search-shortcode-handler.php';
require_once 'sdm-post-type-and-taxonomy.php';
require_once 'sdm-shortcodes.php';
require_once 'sdm-post-type-content-handler.php';

if ( is_admin() ) {
	//load addons update checker class
	include_once WP_SIMPLE_DL_MONITOR_PATH . 'includes/class-sdm-addons-updater.php';
}

//Activation hook handler
register_activation_hook( __FILE__, 'sdm_install_db_table' );

function sdm_install_db_table() {

	global $wpdb;
	global $sdm_db_version;
	$table_name = $wpdb->prefix . 'sdm_downloads';

	$sql = 'CREATE TABLE ' . $table_name . ' (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  post_id mediumint(9) NOT NULL,
			  post_title mediumtext NOT NULL,
			  file_url mediumtext NOT NULL,
			  visitor_ip mediumtext NOT NULL,
			  date_time datetime DEFAULT "0000-00-00 00:00:00" NOT NULL,
			  visitor_country mediumtext NOT NULL,
			  visitor_name mediumtext NOT NULL,
                          user_agent mediumtext NOT NULL,
                          referrer_url mediumtext NOT NULL,
			  UNIQUE KEY id (id)
		);';

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'sdm_db_version', $sdm_db_version );

	//Register the post type so you can flush the rewrite rules
	sdm_register_post_type();

	// Flush rules after install/activation
	flush_rewrite_rules();
}

/*
 * * Handle Plugins loaded tasks
 */
add_action( 'plugins_loaded', 'sdm_plugins_loaded_tasks' );

function sdm_plugins_loaded_tasks() {
	//Load language
	load_plugin_textdomain( 'simple-download-monitor', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	//Handle db upgrade stuff
	sdm_db_update_check();
}

/*
 * * Handle Generic Init tasks
 */
add_action( 'init', 'sdm_init_time_tasks' );
add_action( 'admin_init', 'sdm_admin_init_time_tasks' );

function sdm_init_time_tasks() {
	//Handle download request if any
	handle_sdm_download_via_direct_post();

	//Check if the redirect option is being used
	sdm_check_redirect_query_and_settings();

	if ( is_admin() ) {
		//Register Google Charts library
		wp_register_script( 'sdm_google_charts', 'https://www.gstatic.com/charts/loader.js', array(), null, true );
		wp_register_style( 'sdm_jquery_ui_style', WP_SIMPLE_DL_MONITOR_URL . '/css/jquery.ui.min.css', array(), null, 'all' );
	}
}

function sdm_admin_init_time_tasks() {
	//Register ajax handlers
	add_action( 'wp_ajax_sdm_reset_log', 'sdm_reset_log_handler' );
	add_action( 'wp_ajax_sdm_delete_data', 'sdm_delete_data_handler' );

	if ( is_admin() ) {
		if ( user_can( wp_get_current_user(), 'administrator' ) ) {
			// user is an admin
			if ( isset( $_GET['sdm-action'] ) ) {
				if ( $_GET['sdm-action'] === 'view_log' ) {
					$logfile = fopen( WP_SDM_LOG_FILE, 'rb' );
					header( 'Content-Type: text/plain' );
					fpassthru( $logfile );
					die;
				}
			}
		}
	}
}

function sdm_reset_log_handler() {
	SDM_Debug::reset_log();
	echo '1';
	wp_die();
}

function sdm_delete_data_handler() {
	if ( ! check_ajax_referer( 'sdm_delete_data', 'nonce', false ) ) {
		//nonce check failed
		wp_die( 0 );
	}
	global $wpdb;
	//let's find and delete smd_download posts and meta
	$posts = $wpdb->get_results( 'SELECT id FROM ' . $wpdb->prefix . 'posts WHERE post_type="sdm_downloads"', ARRAY_A );
	if ( ! is_null( $posts ) ) {
		foreach ( $posts as $post ) {
			wp_delete_post( $post['id'], true );
		}
	}
	//let's delete options
	delete_option( 'sdm_downloads_options' );
	delete_option( 'sdm_db_version' );
	//remove post type and taxonomies
	unregister_post_type( 'sdm_downloads' );
	unregister_taxonomy( 'sdm_categories' );
	unregister_taxonomy( 'sdm_tags' );
	//let's delete sdm_downloads table
	$wpdb->query( 'DROP TABLE ' . $wpdb->prefix . 'sdm_downloads' );
	//deactivate plugin
	deactivate_plugins( plugin_basename( __FILE__ ) );
	//flush rewrite rules
	flush_rewrite_rules( false );
	echo '1';
	wp_die();
}

/*
 * DB upgrade check
 */

function sdm_db_update_check() {
	if ( is_admin() ) {//Check if DB needs to be upgraded
		global $sdm_db_version;
		$inst_db_version = get_option( 'sdm_db_version' );
		if ( $inst_db_version != $sdm_db_version ) {
			sdm_install_db_table();
		}
	}
}

/*
 * * Add a 'Settings' link to plugins list page
 */
add_filter( 'plugin_action_links', 'sdm_settings_link', 10, 2 );

function sdm_settings_link( $links, $file ) {
	static $this_plugin;
	if ( ! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}
	if ( $file == $this_plugin ) {
		$settings_link = '<a href="edit.php?post_type=sdm_downloads&page=sdm-settings" title="SDM Settings Page">' . __( 'Settings', 'simple-download-monitor' ) . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}

// Houston... we have lift-off!!
class simpleDownloadManager {

	public function __construct() {

		add_action( 'init', 'sdm_register_post_type' );  // Create 'sdm_downloads' custom post type
		add_action( 'init', 'sdm_create_taxonomies' );  // Register 'tags' and 'categories' taxonomies
		add_action( 'init', 'sdm_register_shortcodes' ); //Register the shortcodes
		add_action( 'wp_enqueue_scripts', array( $this, 'sdm_frontend_scripts' ) );  // Register frontend scripts
		include_once 'includes/sdm-blocks.php';

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'sdm_create_menu_pages' ) );  // Create admin pages
			add_action( 'add_meta_boxes', array( $this, 'sdm_create_upload_metabox' ) );  // Create metaboxes

			add_action( 'save_post', array( $this, 'sdm_save_description_meta_data' ) );  // Save 'description' metabox
			add_action( 'save_post', array( $this, 'sdm_save_upload_meta_data' ) );  // Save 'upload file' metabox
			add_action( 'save_post', array( $this, 'sdm_save_dispatch_meta_data' ) );  // Save 'dispatch' metabox
			add_action( 'save_post', array( $this, 'sdm_save_misc_properties_meta_data' ) );  // Save 'misc properties/settings' metabox
			add_action( 'save_post', array( $this, 'sdm_save_thumbnail_meta_data' ) );  // Save 'thumbnail' metabox
			add_action( 'save_post', array( $this, 'sdm_save_statistics_meta_data' ) );  // Save 'statistics' metabox
			add_action( 'save_post', array( $this, 'sdm_save_other_details_meta_data' ) );  // Save 'other details' metabox

			add_action( 'admin_enqueue_scripts', array( $this, 'sdm_admin_scripts' ) );  // Register admin scripts
			add_action( 'admin_print_styles', array( $this, 'sdm_admin_styles' ) );  // Register admin styles

			add_action( 'admin_init', array( $this, 'sdm_register_options' ) );  // Register admin options
			//add_filter('post_row_actions', array($this, 'sdm_remove_view_link_cpt'), 10, 2);  // Remove 'View' link in all downloads list view

			add_filter( 'page_row_actions', array( $this, 'sdm_add_clone_record_btn' ), 10, 2 );  // Add 'Clone' link in all downloads list view
			add_filter( 'post_row_actions', array( $this, 'sdm_add_clone_record_btn' ), 10, 2 );  // Add 'Clone' link in all downloads list view

			add_action( 'admin_action_sdm_clone_post', array( $this, 'sdm_action_clone_post' ) );
		}
	}

	public function sdm_admin_scripts() {

		global $current_screen, $post;

		if ( is_admin() && $current_screen->post_type == 'sdm_downloads' && $current_screen->base == 'post' ) {

			// These scripts are needed for the media upload thickbox
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
			wp_register_script( 'sdm-upload', WP_SIMPLE_DL_MONITOR_URL . '/js/sdm_admin_scripts.js', array( 'jquery', 'media-upload', 'thickbox' ), WP_SIMPLE_DL_MONITOR_VERSION );
			wp_enqueue_script( 'sdm-upload' );

			// Localize langauge strings used in js file
			$sdmTranslations = array(
				'select_file'      => __( 'Select File', 'simple-download-monitor' ),
				'select_thumbnail' => __( 'Select Thumbnail', 'simple-download-monitor' ),
				'insert'           => __( 'Insert', 'simple-download-monitor' ),
				'image_removed'    => __( 'Image Successfully Removed', 'simple-download-monitor' ),
				'ajax_error'       => __( 'Error with AJAX', 'simple-download-monitor' ),
			);
			wp_localize_script( 'sdm-upload', 'sdm_translations', $sdmTranslations );
		}
	}

	public function sdm_frontend_scripts() {
		//Use this function to enqueue fron-end js scripts.
		wp_enqueue_style( 'sdm-styles', WP_SIMPLE_DL_MONITOR_URL . '/css/sdm_wp_styles.css' );
		wp_register_script( 'sdm-scripts', WP_SIMPLE_DL_MONITOR_URL . '/js/sdm_wp_scripts.js', array( 'jquery' ) );
		wp_enqueue_script( 'sdm-scripts' );

		//Check if reCAPTCHA is enabled.
		$main_advanced_opts = get_option( 'sdm_advanced_options' );
		$recaptcha_enable   = isset( $main_advanced_opts['recaptcha_enable'] ) ? true : false;
		if ( $recaptcha_enable ) {
			wp_register_script( 'sdm-recaptcha-scripts-js', WP_SIMPLE_DL_MONITOR_URL . '/js/sdm_g_recaptcha.js', array(), true );
			wp_localize_script( 'sdm-recaptcha-scripts-js', 'sdm_recaptcha_opt', array( 'site_key' => $main_advanced_opts['recaptcha_site_key'] ) );
			wp_register_script( 'sdm-recaptcha-scripts-lib', '//www.google.com/recaptcha/api.js?hl=' . get_locale() . '&onload=sdm_reCaptcha&render=explicit', array(), false );
			wp_enqueue_script( 'sdm-recaptcha-scripts-js' );
			wp_enqueue_script( 'sdm-recaptcha-scripts-lib' );
		}

		// Localize ajax script for frontend
		wp_localize_script( 'sdm-scripts', 'sdm_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	public function sdm_admin_styles() {

		wp_enqueue_style( 'thickbox' );  // Needed for media upload thickbox
		wp_enqueue_style( 'sdm_admin_styles', WP_SIMPLE_DL_MONITOR_URL . '/css/sdm_admin_styles.css', array(), WP_SIMPLE_DL_MONITOR_VERSION );  // Needed for media upload thickbox
	}

	public function sdm_create_menu_pages() {
		include_once 'includes/sdm-admin-menu-handler.php';
		sdm_handle_admin_menu();
	}

	public function sdm_create_upload_metabox() {

		//*****  Create metaboxes for the custom post type
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

	public function display_sdm_description_meta_box( $post ) {
		// Description metabox
		_e( 'Add a description for this download item.', 'simple-download-monitor' );
		echo '<br /><br />';

		$old_description       = get_post_meta( $post->ID, 'sdm_description', true );
		$sdm_description_field = array( 'textarea_name' => 'sdm_description' );
		wp_editor( $old_description, 'sdm_description_editor_content', $sdm_description_field );

		wp_nonce_field( 'sdm_description_box_nonce', 'sdm_description_box_nonce_check' );
	}

	public function display_sdm_upload_meta_box( $post ) {
		// File Upload metabox
		$old_upload = get_post_meta( $post->ID, 'sdm_upload', true );
		$old_value  = isset( $old_upload ) ? $old_upload : '';

		//Trigger filter to allow "sdm_upload" field validation override.
		$url_validation_override = apply_filters( 'sdm_file_download_url_validation_override', '' );
		if ( ! empty( $url_validation_override ) ) {
			//This site has customized the behavior and overriden the "sdm_upload" field validation. It can be useful if you are offering app download URLs (that has unconventional URL patterns).
		} else {
			//Do the normal URL validation.
			$old_value = esc_url( $old_value );
		}

		_e( 'Manually enter a valid URL of the file in the text box below, or click "Select File" button to upload (or choose) the downloadable file.', 'simple-download-monitor' );
		echo '<br /><br />';

		echo '<div class="sdm-download-edit-file-url-section">';
		echo '<input id="sdm_upload" type="text" size="100" name="sdm_upload" value="' . $old_value . '" placeholder="http://..." />';
		echo '</div>';

		echo '<br />';
		echo '<input id="upload_image_button" type="button" class="button-primary" value="' . __( 'Select File', 'simple-download-monitor' ) . '" />';

		echo '<br /><br />';
		_e( 'Steps to upload a file or choose one from your media library:', 'simple-download-monitor' );
		echo '<ol>';
		echo '<li>' . __( 'Hit the "Select File" button.', 'simple-download-monitor' ) . '</li>';
		echo '<li>' . __( 'Upload a new file or choose an existing one from your media library.', 'simple-download-monitor' ) . '</li>';
		echo '<li>' . __( 'Click the "Insert" button, this will populate the uploaded file\'s URL in the above text field.', 'simple-download-monitor' ) . '</li>';
		echo '</ol>';

		wp_nonce_field( 'sdm_upload_box_nonce', 'sdm_upload_box_nonce_check' );
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
		echo '<label for="sdm_item_dispatch">' . __( 'Dispatch the file via PHP directly instead of redirecting to it. PHP Dispatching keeps the download URL hidden. Dispatching works only for local files (files that you uploaded to this site via this plugin or media library).', 'simple-download-monitor' ) . '</label>';

		wp_nonce_field( 'sdm_dispatch_box_nonce', 'sdm_dispatch_box_nonce_check' );
	}

	// Open Download in new window
	public function display_sdm_misc_properties_meta_box( $post ) {

		//Check the open in new window value
		$new_window = get_post_meta( $post->ID, 'sdm_item_new_window', true );
		if ( $new_window === '' ) {
			// No value yet (either new item or saved with older version of plugin)
			$screen = get_current_screen();
			if ( $screen->action === 'add' ) {
				//New item: we can set a default value as per plugin settings. If a general settings is introduced at a later stage.
				//Does nothing at the moment.
			}
		}

		//Check the sdm_item_disable_single_download_page value
		$sdm_item_disable_single_download_page        = get_post_meta( $post->ID, 'sdm_item_disable_single_download_page', true );
		$sdm_item_hide_dl_button_single_download_page = get_post_meta( $post->ID, 'sdm_item_hide_dl_button_single_download_page', true );

		echo '<p> <input id="sdm_item_new_window" type="checkbox" name="sdm_item_new_window" value="yes"' . checked( true, $new_window, false ) . ' />';
		echo '<label for="sdm_item_new_window">' . __( 'Open download in a new window.', 'simple-download-monitor' ) . '</label> </p>';

		//the new window will have no download button
		echo '<p> <input id="sdm_item_hide_dl_button_single_download_page" type="checkbox" name="sdm_item_hide_dl_button_single_download_page" value="yes"' . checked( true, $sdm_item_hide_dl_button_single_download_page, false ) . ' />';
		echo '<label for="sdm_item_hide_dl_button_single_download_page">';

		$disable_dl_button_label = __( 'Hide the download button on the single download page of this item.', 'simple-download-monitor' );
		echo $disable_dl_button_label . '</label>';
		echo '</p>';

		echo '<p> <input id="sdm_item_disable_single_download_page" type="checkbox" name="sdm_item_disable_single_download_page" value="yes"' . checked( true, $sdm_item_disable_single_download_page, false ) . ' />';
		echo '<label for="sdm_item_disable_single_download_page">';
		$disable_single_dl_label  = __( 'Disable the single download page for this download item. ', 'simple-download-monitor' );
		$disable_single_dl_label .= __( 'This can be useful if you are using an addon like the ', 'simple-download-monitor' );
		$disable_single_dl_label .= '<a href="https://simple-download-monitor.com/squeeze-form-addon-for-simple-download-monitor/" target="_blank">Squeeze Form</a>' . '.';
		echo $disable_single_dl_label . '</label>';
		echo '</p>';

		$sdm_item_anonymous_can_download = get_post_meta( $post->ID, 'sdm_item_anonymous_can_download', true );

		echo '<p> <input id="sdm_item_anonymous_can_download" type="checkbox" name="sdm_item_anonymous_can_download" value="yes"' . checked( true, $sdm_item_anonymous_can_download, false ) . ' />';
		echo '<label for="sdm_item_anonymous_can_download">' . __( 'Ignore "Only Allow Logged-in Users to Download" global setting for this item.', 'simple-download-monitor' ) . '</label> </p>';

		wp_nonce_field( 'sdm_misc_properties_box_nonce', 'sdm_misc_properties_box_nonce_check' );
	}

	public function display_sdm_thumbnail_meta_box( $post ) {
		// Thumbnail upload metabox
		$old_thumbnail = get_post_meta( $post->ID, 'sdm_upload_thumbnail', true );
		$old_value     = isset( $old_thumbnail ) ? $old_thumbnail : '';
		_e( 'Manually enter a valid URL, or click "Select Image" to upload (or choose) the file thumbnail image.', 'simple-download-monitor' );
		?>
	<br /><br />
	<input id="sdm_upload_thumbnail" type="text" size="100" name="sdm_upload_thumbnail" value="<?php echo esc_url( $old_value ); ?>" placeholder="http://..." />
	<br /><br />
	<input id="upload_thumbnail_button" type="button" class="button-primary" value="<?php _e( 'Select Image', 'simple-download-monitor' ); ?>" />
	<input id="remove_thumbnail_button" type="button" class="button" value="<?php _e( 'Remove Image', 'simple-download-monitor' ); ?>" />
	<br /><br />

	<span id="sdm_admin_thumb_preview">
		<?php
		if ( ! empty( $old_value ) ) {
			?>
		<img id="sdm_thumbnail_image" src="<?php echo $old_value; ?>" style="max-width:200px;" />
			<?php
		}
		?>
	</span>

		<?php
		echo '<p class="description">';
		_e( 'This thumbnail image will be used to create a fancy file download box if you want to use it.', 'simple-download-monitor' );
		echo '</p>';

		wp_nonce_field( 'sdm_thumbnail_box_nonce', 'sdm_thumbnail_box_nonce_check' );
	}

	public function display_sdm_stats_meta_box( $post ) {
		//Stats metabox
		$old_count = get_post_meta( $post->ID, 'sdm_count_offset', true );
		$value     = isset( $old_count ) && $old_count != '' ? $old_count : '0';

		// Get checkbox for "disable download logging"
		$no_logs = get_post_meta( $post->ID, 'sdm_item_no_log', true );
		$checked = isset( $no_logs ) && $no_logs === 'on' ? 'checked="checked"' : '';

		_e( 'These are the statistics for this download item.', 'simple-download-monitor' );
		echo '<br /><br />';

		global $wpdb;
		$wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'sdm_downloads WHERE post_id=%s', $post->ID ) );

		echo '<div class="sdm-download-edit-dl-count">';
		_e( 'Number of Downloads:', 'simple-download-monitor' );
		echo ' <strong>' . $wpdb->num_rows . '</strong>';
		echo '</div>';

		echo '<div class="sdm-download-edit-offset-count">';
		_e( 'Offset Count: ', 'simple-download-monitor' );
		echo '<br />';
		echo ' <input type="text" size="10" name="sdm_count_offset" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . __( 'Enter any positive or negative numerical value; to offset the download count shown to the visitors (when using the download counter shortcode).', 'simple-download-monitor' ) . '</p>';
		echo '</div>';

		echo '<br />';
		echo '<div class="sdm-download-edit-disable-logging">';
		echo '<input type="checkbox" name="sdm_item_no_log" ' . $checked . ' />';
		echo '<span style="margin-left: 5px;"></span>';
		_e( 'Disable download logging for this item.', 'simple-download-monitor' );
		echo '</div>';

		wp_nonce_field( 'sdm_count_offset_nonce', 'sdm_count_offset_nonce_check' );
	}

	public function display_sdm_other_details_meta_box( $post ) {
		//Other details metabox
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
		echo '<strong>' . __( 'File Size: ', 'simple-download-monitor' ) . '</strong>';
		echo '<br />';
		echo ' <input type="text" name="sdm_item_file_size" value="' . esc_attr( $file_size ) . '" size="20" />';
		echo '<p class="description">' . __( 'Enter the size of this file (example value: 2.15 MB).', 'simple-download-monitor' ) . '</p>';
		echo '<div class="sdm-download-edit-show-file-size"> <input id="sdm_item_show_file_size_fd" type="checkbox" name="sdm_item_show_file_size_fd" value="yes"' . checked( true, $sdm_item_show_file_size_fd, false ) . ' />';
		echo '<label for="sdm_item_show_file_size_fd">' . __( 'Show file size in fancy display.', 'simple-download-monitor' ) . '</label> </div>';
		echo '</div>';
		echo '<hr />';

		echo '<div class="sdm-download-edit-version">';
		echo '<strong>' . __( 'Version: ', 'simple-download-monitor' ) . '</strong>';
		echo '<br />';
		echo ' <input type="text" name="sdm_item_version" value="' . esc_attr( $version ) . '" size="20" />';
		echo '<p class="description">' . __( 'Enter the version number for this item if any (example value: v2.5.10).', 'simple-download-monitor' ) . '</p>';
		echo '<div class="sdm-download-edit-show-item-version"> <input id="sdm_item_show_item_version_fd" type="checkbox" name="sdm_item_show_item_version_fd" value="yes"' . checked( true, $sdm_item_show_item_version_fd, false ) . ' />';
		echo '<label for="sdm_item_show_item_version_fd">' . __( 'Show version number in fancy display.', 'simple-download-monitor' ) . '</label> </div>';
		echo '</div>';
		echo '<hr />';

		echo '<div class="sdm-download-edit-show-publish-date">';
		echo '<strong>' . __( 'Publish Date: ', 'simple-download-monitor' ) . '</strong>';
		echo '<br /> <input id="sdm_item_show_date_fd" type="checkbox" name="sdm_item_show_date_fd" value="yes"' . checked( true, $show_date_fd, false ) . ' />';
		echo '<label for="sdm_item_show_date_fd">' . __( 'Show download published date in fancy display.', 'simple-download-monitor' ) . '</label>';
		echo '</div>';
		echo '<hr />';

		echo '<div class="sdm-download-edit-button-text">';
		echo '<strong>' . __( 'Download Button Text: ', 'simple-download-monitor' ) . '</strong>';
		echo '<br />';
		echo "<input id='sdm-download-button-text' type='text' name='sdm_download_button_text' value='{$download_button_text}' />";
		echo '<p class="description">' . __( 'You can use this field to customize the download now button text of this item.', 'simple-download-monitor' ) . '</p>';
		echo '</div>';

		wp_nonce_field( 'sdm_other_details_nonce', 'sdm_other_details_nonce_check' );
	}

	public function display_sdm_shortcode_meta_box( $post ) {
		//Shortcode metabox
		_e( 'The following shortcode can be used on posts or pages to embed a download now button for this file. You can also use the shortcode inserter (in the post editor) to add this shortcode to a post or page.', 'simple-download-monitor' );
		echo '<br />';
		$shortcode_text = '[sdm_download id="' . $post->ID . '" fancy="0"]';
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . $shortcode_text . "' size='40'>";
		echo '<br /><br />';

		_e( 'The following shortcode can be used to show a download counter for this item.', 'simple-download-monitor' );
		echo '<br />';
		$shortcode_text = '[sdm_download_counter id="' . $post->ID . '"]';
		echo "<input type='text' class='code' onfocus='this.select();' readonly='readonly' value='" . $shortcode_text . "' size='40'>";

		echo '<br /><br />';
		_e( 'Read the full shortcode usage documentation <a href="https://simple-download-monitor.com/miscellaneous-shortcodes-and-shortcode-parameters/" target="_blank">here</a>.', 'simple-download-monitor' );
	}

	public function sdm_save_description_meta_data( $post_id ) {
		// Save Description metabox
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['sdm_description_box_nonce_check'] ) || ! wp_verify_nonce( $_POST['sdm_description_box_nonce_check'], 'sdm_description_box_nonce' ) ) {
			return;
		}
		if ( isset( $_POST['sdm_description'] ) ) {
			update_post_meta( $post_id, 'sdm_description', wp_kses_post( $_POST['sdm_description'] ) );
		}
	}

	public function sdm_save_upload_meta_data( $post_id ) {
		// Save File Upload metabox
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['sdm_upload_box_nonce_check'] ) || ! wp_verify_nonce( $_POST['sdm_upload_box_nonce_check'], 'sdm_upload_box_nonce' ) ) {
			return;
		}

		if ( isset( $_POST['sdm_upload'] ) ) {
			update_post_meta( $post_id, 'sdm_upload', sanitize_text_field( $_POST['sdm_upload'] ) );
		}
	}

	public function sdm_save_dispatch_meta_data( $post_id ) {
		// Save "Dispatch or Redirect" metabox
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['sdm_dispatch_box_nonce_check'] ) || ! wp_verify_nonce( $_POST['sdm_dispatch_box_nonce_check'], 'sdm_dispatch_box_nonce' ) ) {
			return;
		}
		// Get POST-ed data as boolean value
		$value = filter_input( INPUT_POST, 'sdm_item_dispatch', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'sdm_item_dispatch', $value );
	}

	public function sdm_save_misc_properties_meta_data( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['sdm_misc_properties_box_nonce_check'] ) || ! wp_verify_nonce( $_POST['sdm_misc_properties_box_nonce_check'], 'sdm_misc_properties_box_nonce' ) ) {
			return;
		}
		// Get POST-ed data as boolean value
		$new_window_open                              = filter_input( INPUT_POST, 'sdm_item_new_window', FILTER_VALIDATE_BOOLEAN );
		$sdm_item_hide_dl_button_single_download_page = filter_input( INPUT_POST, 'sdm_item_hide_dl_button_single_download_page', FILTER_VALIDATE_BOOLEAN );
		$sdm_item_disable_single_download_page        = filter_input( INPUT_POST, 'sdm_item_disable_single_download_page', FILTER_VALIDATE_BOOLEAN );
		$sdm_item_anonymous_can_download              = filter_input( INPUT_POST, 'sdm_item_anonymous_can_download', FILTER_VALIDATE_BOOLEAN );

		//Save the data
		update_post_meta( $post_id, 'sdm_item_new_window', $new_window_open );
		update_post_meta( $post_id, 'sdm_item_hide_dl_button_single_download_page', $sdm_item_hide_dl_button_single_download_page );
		update_post_meta( $post_id, 'sdm_item_disable_single_download_page', $sdm_item_disable_single_download_page );
		update_post_meta( $post_id, 'sdm_item_anonymous_can_download', $sdm_item_anonymous_can_download );
	}

	public function sdm_save_thumbnail_meta_data( $post_id ) {
		// Save Thumbnail Upload metabox
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['sdm_thumbnail_box_nonce_check'] ) || ! wp_verify_nonce( $_POST['sdm_thumbnail_box_nonce_check'], 'sdm_thumbnail_box_nonce' ) ) {
			return;
		}
		if ( isset( $_POST['sdm_upload_thumbnail'] ) ) {
			update_post_meta( $post_id, 'sdm_upload_thumbnail', sanitize_text_field( $_POST['sdm_upload_thumbnail'] ) );
		}
	}

	public function sdm_save_statistics_meta_data( $post_id ) {
		// Save Statistics Upload metabox
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['sdm_count_offset_nonce_check'] ) || ! wp_verify_nonce( $_POST['sdm_count_offset_nonce_check'], 'sdm_count_offset_nonce' ) ) {
			return;
		}
		if ( isset( $_POST['sdm_count_offset'] ) && is_numeric( $_POST['sdm_count_offset'] ) ) {
			update_post_meta( $post_id, 'sdm_count_offset', intval( $_POST['sdm_count_offset'] ) );
		}

		// Checkbox for disabling download logging for this item
		if ( isset( $_POST['sdm_item_no_log'] ) ) {
			update_post_meta( $post_id, 'sdm_item_no_log', sanitize_text_field( $_POST['sdm_item_no_log'] ) );
		} else {
			delete_post_meta( $post_id, 'sdm_item_no_log' );
		}
	}

	public function sdm_save_other_details_meta_data( $post_id ) {
		// Save Statistics Upload metabox
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['sdm_other_details_nonce_check'] ) || ! wp_verify_nonce( $_POST['sdm_other_details_nonce_check'], 'sdm_other_details_nonce' ) ) {
			return;
		}

		$show_date_fd = filter_input( INPUT_POST, 'sdm_item_show_date_fd', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'sdm_item_show_date_fd', $show_date_fd );

		$sdm_item_show_file_size_fd = filter_input( INPUT_POST, 'sdm_item_show_file_size_fd', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'sdm_item_show_file_size_fd', $sdm_item_show_file_size_fd );

		$sdm_item_show_item_version_fd = filter_input( INPUT_POST, 'sdm_item_show_item_version_fd', FILTER_VALIDATE_BOOLEAN );
		update_post_meta( $post_id, 'sdm_item_show_item_version_fd', $sdm_item_show_item_version_fd );

		if ( isset( $_POST['sdm_item_file_size'] ) ) {
			update_post_meta( $post_id, 'sdm_item_file_size', sanitize_text_field( $_POST['sdm_item_file_size'] ) );
		}

		if ( isset( $_POST['sdm_item_version'] ) ) {
			update_post_meta( $post_id, 'sdm_item_version', sanitize_text_field( $_POST['sdm_item_version'] ) );
		}

		if ( isset( $_POST['sdm_download_button_text'] ) ) {
			update_post_meta( $post_id, 'sdm_download_button_text', sanitize_text_field( $_POST['sdm_download_button_text'] ) );
		}
	}

	public function sdm_remove_view_link_cpt( $action, $post ) {

		// Only execute on SDM CPT posts page
		if ( $post->post_type == 'sdm_downloads' ) {
			unset( $action['view'] );
		}

		return $action;
	}

	public function sdm_register_options() {

		//Register the main setting
		register_setting( 'sdm_downloads_options', 'sdm_downloads_options' );

		/*	 * ************************** */
		/* General Settings Section */
		/*	 * ************************** */

		//Add all the settings section that will go under the main settings
		add_settings_section( 'general_options', __( 'General Options', 'simple-download-monitor' ), array( $this, 'general_options_cb' ), 'general_options_section' );
		add_settings_section( 'user_login_options', __( 'User Login Related', 'simple-download-monitor' ), array( $this, 'user_login_options_cb' ), 'user_login_options_section' );
		add_settings_section( 'admin_options', __( 'Admin Options', 'simple-download-monitor' ), array( $this, 'admin_options_cb' ), 'admin_options_section' );

		add_settings_section( 'sdm_colors', __( 'Colors', 'simple-download-monitor' ), array( $this, 'sdm_colors_cb' ), 'sdm_colors_section' );
		add_settings_section( 'sdm_debug', __( 'Debug', 'simple-download-monitor' ), array( $this, 'sdm_debug_cb' ), 'sdm_debug_section' );
		add_settings_section( 'sdm_deldata', __( 'Delete Plugin Data', 'simple-download-monitor' ), array( $this, 'sdm_deldata_cb' ), 'sdm_deldata_section' );

		//Add all the individual settings fields that goes under the sections
		add_settings_field( 'general_hide_donwload_count', __( 'Hide Download Count', 'simple-download-monitor' ), array( $this, 'hide_download_count_cb' ), 'general_options_section', 'general_options' );
		add_settings_field( 'general_default_dispatch_value', __( 'PHP Dispatching', 'simple-download-monitor' ), array( $this, 'general_default_dispatch_value_cb' ), 'general_options_section', 'general_options' );

		add_settings_field( 'only_logged_in_can_download', __( 'Only Allow Logged-in Users to Download', 'simple-download-monitor' ), array( $this, 'general_only_logged_in_can_download_cb' ), 'user_login_options_section', 'user_login_options' );
		add_settings_field( 'general_login_page_url', __( 'Login Page URL', 'simple-download-monitor' ), array( $this, 'general_login_page_url_cb' ), 'user_login_options_section', 'user_login_options' );
		add_settings_field( 'redirect_user_back_to_download_page', __( 'Redirect Users to Download Page', 'simple-download-monitor' ), array( $this, 'redirect_user_back_to_download_page_cb' ), 'user_login_options_section', 'user_login_options' );

		add_settings_field( 'admin_log_unique', __( 'Log Unique IP', 'simple-download-monitor' ), array( $this, 'admin_log_unique' ), 'admin_options_section', 'admin_options' );
		add_settings_field( 'admin_do_not_capture_ip', __( 'Do Not Capture IP Address', 'simple-download-monitor' ), array( $this, 'admin_do_not_capture_ip' ), 'admin_options_section', 'admin_options' );
                add_settings_field( 'admin_do_not_capture_user_agent', __( 'Do Not Capture User Agent', 'simple-download-monitor' ), array( $this, 'admin_do_not_capture_user_agent' ), 'admin_options_section', 'admin_options' );
                add_settings_field( 'admin_do_not_capture_referrer_url', __( 'Do Not Capture Referrer URL', 'simple-download-monitor' ), array( $this, 'admin_do_not_capture_referrer_url' ), 'admin_options_section', 'admin_options' );
		add_settings_field( 'admin_dont_log_bots', __( 'Do Not Count Downloads from Bots', 'simple-download-monitor' ), array( $this, 'admin_dont_log_bots' ), 'admin_options_section', 'admin_options' );
		add_settings_field( 'admin_no_logs', __( 'Disable Download Logs', 'simple-download-monitor' ), array( $this, 'admin_no_logs_cb' ), 'admin_options_section', 'admin_options' );

		add_settings_field( 'download_button_color', __( 'Download Button Color', 'simple-download-monitor' ), array( $this, 'download_button_color_cb' ), 'sdm_colors_section', 'sdm_colors' );

		add_settings_field( 'enable_debug', __( 'Enable Debug', 'simple-download-monitor' ), array( $this, 'enable_debug_cb' ), 'sdm_debug_section', 'sdm_debug' );

		/*	 * ************************** */
		/* Advanced Settings Section */
		/*	 * ************************** */
		//Add the advanced settings section
		add_settings_section( 'recaptcha_options', __( 'Google Captcha (reCAPTCHA)', 'simple-download-monitor' ), array( $this, 'recaptcha_options_cb' ), 'recaptcha_options_section' );
		add_settings_section( 'termscond_options', __( 'Terms and Conditions', 'simple-download-monitor' ), array( $this, 'termscond_options_cb' ), 'termscond_options_section' );
		add_settings_section( 'adsense_options', __( 'Adsense/Ad Insertion', 'simple-download-monitor' ), array( $this, 'adsense_options_cb' ), 'adsense_options_section' );
		add_settings_section( 'maps_api_options', __( 'Google Maps API Key', 'simple-download-monitor' ), array( $this, 'maps_api_options_cb' ), 'maps_api_options_section' );

		//Add reCAPTCHA section fields
		add_settings_field( 'recaptcha_enable', __( 'Enable reCAPTCHA', 'simple-download-monitor' ), array( $this, 'recaptcha_enable_cb' ), 'recaptcha_options_section', 'recaptcha_options' );
		add_settings_field( 'recaptcha_site_key', __( 'Site Key', 'simple-download-monitor' ), array( $this, 'recaptcha_site_key_cb' ), 'recaptcha_options_section', 'recaptcha_options' );
		add_settings_field( 'recaptcha_secret_key', __( 'Secret Key', 'simple-download-monitor' ), array( $this, 'recaptcha_secret_key_cb' ), 'recaptcha_options_section', 'recaptcha_options' );

		//Add Terms & Condition section fields
		add_settings_field( 'termscond_enable', __( 'Enable Terms and Conditions', 'simple-download-monitor' ), array( $this, 'termscond_enable_cb' ), 'termscond_options_section', 'termscond_options' );
		add_settings_field( 'termscond_url', __( 'Terms Page URL', 'simple-download-monitor' ), array( $this, 'termscond_url_cb' ), 'termscond_options_section', 'termscond_options' );

		//Add Adsense section fields
		add_settings_field( 'adsense_below_description', __( 'Below Download Description', 'simple-download-monitor' ), array( $this, 'adsense_below_description_cb' ), 'adsense_options_section', 'adsense_options' );

		//Maps API section fields
		add_settings_field( 'maps_api_key', __( 'API Key', 'simple-download-monitor' ), array( $this, 'maps_api_key_cb' ), 'maps_api_options_section', 'maps_api_options' );
	}

	public function general_options_cb() {
		//Set the message that will be shown below the general options settings heading
		_e( 'General options settings', 'simple-download-monitor' );
	}

	public function user_login_options_cb() {
		//Set the message that will be shown below the user login related settings heading
		_e( 'Visitor login related settings (useful if you only want to allow logged-in users to be able to download files.', 'simple-download-monitor' );
	}

	public function admin_options_cb() {
		//Set the message that will be shown below the admin options settings heading
		_e( 'Admin options settings', 'simple-download-monitor' );
	}

	public function sdm_colors_cb() {
		//Set the message that will be shown below the color options settings heading
		_e( 'Front End colors settings', 'simple-download-monitor' );
	}

	public function sdm_debug_cb() {
		//Set the message that will be shown below the debug options settings heading
		_e( 'Debug settings', 'simple-download-monitor' );
	}

	public function sdm_deldata_cb() {
		//Set the message that will be shown below the debug options settings heading
		_e( 'You can delete all the data related to this plugin from database using the button below. Useful when you\'re uninstalling the plugin and don\'t want any leftovers remaining.', 'simple-download-monitor' );
		echo '<p><b>' . __( 'Warning', 'simple-download-monitor' ) . ': </b> ' . __( 'this can\'t be undone. All settings, download items, download logs will be deleted.', 'simple-download-monitor' ) . '</p>';
		echo '<p><button id="sdmDeleteData" class="button" style="color:red;">' . __( 'Delete all data and deactivate plugin', 'simple-download-monitor' ) . '</button></p>';
		echo '<br />';
	}

	public function recaptcha_options_cb() {
		//Set the message that will be shown below the recaptcha options settings heading
		_e( 'Google Captcha (reCAPTCHA) options', 'simple-download-monitor' );
	}

	public function termscond_options_cb() {

	}

	public function adsense_options_cb() {
		//Set the message that will be shown below the adsense/ad code settings heading
		_e( 'You can use this section to insert adsense or other ad code inside the download item output', 'simple-download-monitor' );
	}

	public function maps_api_options_cb() {
		_e( 'Google Maps API key is required to display the "Downloads by Country" chart.', 'simple-download-monitor' );
	}

	public function recaptcha_enable_cb() {
		$main_opts = get_option( 'sdm_advanced_options' );
		echo '<input name="sdm_advanced_options[recaptcha_enable]" id="recaptcha_enable" type="checkbox" ' . checked( 1, isset( $main_opts['recaptcha_enable'] ), false ) . ' /> ';
		echo '<p class="description">' . __( 'Check this box if you want to use <a href="https://www.google.com/recaptcha/admin" target="_blank">reCAPTCHA</a>. ', 'simple-download-monitor' ) . '</p>';
		echo '<p class="description">' . __( 'The captcha option adds a captcha to the download now buttons.', 'simple-download-monitor' ) . '</p>';
	}

	public function recaptcha_site_key_cb() {
		$main_opts = get_option( 'sdm_advanced_options' );
		$value     = isset( $main_opts['recaptcha_site_key'] ) ? $main_opts['recaptcha_site_key'] : '';
		echo '<input size="100" name="sdm_advanced_options[recaptcha_site_key]" id="recaptcha_site_key" type="text" value="' . $value . '" /> ';
		echo '<p class="description">' . __( 'The site key for the reCAPTCHA API', 'simple-download-monitor' ) . '</p>';
	}

	public function recaptcha_secret_key_cb() {
		$main_opts = get_option( 'sdm_advanced_options' );
		$value     = isset( $main_opts['recaptcha_secret_key'] ) ? $main_opts['recaptcha_secret_key'] : '';
		echo '<input size="100" name="sdm_advanced_options[recaptcha_secret_key]" id="recaptcha_secret_key" type="text" value="' . $value . '" /> ';
		echo '<p class="description">' . __( 'The secret key for the reCAPTCHA API', 'simple-download-monitor' ) . '</p>';
	}

	public function hide_download_count_cb() {
		$main_opts = get_option( 'sdm_downloads_options' );
		echo '<input name="sdm_downloads_options[general_hide_donwload_count]" id="general_hide_download_count" type="checkbox" ' . checked( 1, isset( $main_opts['general_hide_donwload_count'] ), false ) . ' /> ';
		echo '<label for="general_hide_download_count">' . __( 'Hide the download count that is shown in some of the fancy templates.', 'simple-download-monitor' ) . '</label>';
	}

	public function general_default_dispatch_value_cb() {
		$main_opts = get_option( 'sdm_downloads_options' );
		$value     = isset( $main_opts['general_default_dispatch_value'] ) && $main_opts['general_default_dispatch_value'];
		echo '<input name="sdm_downloads_options[general_default_dispatch_value]" id="general_default_dispatch_value" type="checkbox" value="1"' . checked( true, $value, false ) . ' />';
		echo '<label for="general_default_dispatch_value">' . __( 'When you create a new download item, The PHP Dispatching option should be enabled by default. PHP Dispatching keeps the URL of the downloadable files hidden.', 'simple-download-monitor' ) . '</label>';
	}

	public function general_only_logged_in_can_download_cb() {
		$main_opts = get_option( 'sdm_downloads_options' );
		$value     = isset( $main_opts['only_logged_in_can_download'] ) && $main_opts['only_logged_in_can_download'];
		echo '<input name="sdm_downloads_options[only_logged_in_can_download]" id="only_logged_in_can_download" type="checkbox" value="1"' . checked( true, $value, false ) . ' />';
		echo '<label for="only_logged_in_can_download">' . __( 'Enable this option if you want to allow downloads only for logged-in users. When enabled, anonymous users clicking on the download button will receive an error message.', 'simple-download-monitor' ) . '</label>';
	}

	public function redirect_user_back_to_download_page_cb() {
		$main_opts = get_option( 'sdm_downloads_options' );
		$value     = isset( $main_opts['redirect_user_back_to_download_page'] ) && $main_opts['redirect_user_back_to_download_page'];
		echo '<input name="sdm_downloads_options[redirect_user_back_to_download_page]" id="redirect_user_back_to_download_page" type="checkbox" value="1"' . checked( true, $value, false ) . ' />';
		echo '<label for="redirect_user_back_to_download_page">' . __( 'Only works if you have set a Login Page URL value above. Enable this option if you want to redirect the users to the download page after they log into the site.', 'simple-download-monitor' ) . '</label>';
	}

	public function general_login_page_url_cb() {
		$main_opts = get_option( 'sdm_downloads_options' );
		$value     = isset( $main_opts['general_login_page_url'] ) ? $main_opts['general_login_page_url'] : '';
		echo '<input size="100" name="sdm_downloads_options[general_login_page_url]" id="general_login_page_url" type="text" value="' . $value . '" />';
		echo '<p class="description">' . __( '(Optional) Specify a login page URL where users can login. This is useful if you only allow logged in users to be able to download. This link will be added to the message that is shown to anonymous users.', 'simple-download-monitor' ) . '</p>';
	}

	public function admin_log_unique() {
		$main_opts = get_option( 'sdm_downloads_options' );
		echo '<input name="sdm_downloads_options[admin_log_unique]" id="admin_log_unique" type="checkbox" class="sdm_opts_ajax_checkboxes" ' . checked( 1, isset( $main_opts['admin_log_unique'] ), false ) . ' /> ';
		echo '<label for="admin_log_unique">' . __( 'Only logs downloads from unique IP addresses.', 'simple-download-monitor' ) . '</label>';
	}

	public function admin_do_not_capture_ip() {
		$main_opts = get_option( 'sdm_downloads_options' );
		echo '<input name="sdm_downloads_options[admin_do_not_capture_ip]" id="admin_do_not_capture_ip" type="checkbox" class="sdm_opts_ajax_checkboxes" ' . checked( 1, isset( $main_opts['admin_do_not_capture_ip'] ), false ) . ' /> ';
		echo '<label for="admin_do_not_capture_ip">' . __( 'Use this if you do not want to capture the IP address and Country of the visitors when they download an item.', 'simple-download-monitor' ) . '</label>';
	}

	public function admin_do_not_capture_user_agent() {
		$main_opts = get_option( 'sdm_downloads_options' );
		echo '<input name="sdm_downloads_options[admin_do_not_capture_user_agent]" id="admin_do_not_capture_user_agent" type="checkbox" class="sdm_opts_ajax_checkboxes" ' . checked( 1, isset( $main_opts['admin_do_not_capture_user_agent'] ), false ) . ' /> ';
		echo '<label for="admin_do_not_capture_user_agent">' . __( 'Use this if you do not want to capture the User Agent value of the browser when they download an item.', 'simple-download-monitor' ) . '</label>';
	}

	public function admin_do_not_capture_referrer_url() {
		$main_opts = get_option( 'sdm_downloads_options' );
		echo '<input name="sdm_downloads_options[admin_do_not_capture_referrer_url]" id="admin_do_not_capture_referrer_url" type="checkbox" class="sdm_opts_ajax_checkboxes" ' . checked( 1, isset( $main_opts['admin_do_not_capture_referrer_url'] ), false ) . ' /> ';
		echo '<label for="admin_do_not_capture_referrer_url">' . __( 'Use this if you do not want to capture the Referrer URL value when they download an item. The plugin only tries to capture this value if it is available.', 'simple-download-monitor' ) . '</label>';
	}

	public function admin_dont_log_bots() {
		$main_opts = get_option( 'sdm_downloads_options' );
		echo '<input name="sdm_downloads_options[admin_dont_log_bots]" id="admin_dont_log_bots" type="checkbox" class="sdm_opts_ajax_checkboxes" ' . checked( 1, isset( $main_opts['admin_dont_log_bots'] ), false ) . ' /> ';
		echo '<label for="admin_dont_log_bots">' . __( 'When enabled, the plugin won\'t count and log downloads from bots.', 'simple-download-monitor' ) . '</label>';
	}

	public function admin_no_logs_cb() {
		$main_opts = get_option( 'sdm_downloads_options' );
		echo '<input name="sdm_downloads_options[admin_no_logs]" id="admin_no_logs" type="checkbox" class="sdm_opts_ajax_checkboxes" ' . checked( 1, isset( $main_opts['admin_no_logs'] ), false ) . ' /> ';
		echo '<label for="admin_no_logs">' . __( 'Disables all download logs. (This global option overrides the individual download item option.)', 'simple-download-monitor' ) . '</label>';
	}

	public function download_button_color_cb() {
		$main_opts = get_option( 'sdm_downloads_options' );
		// Read current color class.
		$color_opt  = isset( $main_opts['download_button_color'] ) ? $main_opts['download_button_color'] : null;
		$color_opts = sdm_get_download_button_colors();

		echo '<select name="sdm_downloads_options[download_button_color]" id="download_button_color" class="sdm_opts_ajax_dropdowns">';
		foreach ( $color_opts as $color_class => $color_name ) {
			echo '<option value="' . $color_class . '"' . selected( $color_class, $color_opt, false ) . '>' . $color_name . '</option>';
		}
		echo '</select> ';
		esc_html_e( 'Adjusts the color of the "Download Now" button.', 'simple-download-monitor' );
	}

	public function enable_debug_cb() {
		$main_opts = get_option( 'sdm_downloads_options' );
		echo '<input name="sdm_downloads_options[enable_debug]" id="enable_debug" type="checkbox" class="sdm_opts_ajax_checkboxes" ' . checked( 1, isset( $main_opts['enable_debug'] ), false ) . ' /> ';
		echo '<label for="enable_debug">' . __( 'Check this option to enable debug logging.', 'simple-download-monitor' ) .
		'<p class="description"><a href="' . get_admin_url() . '?sdm-action=view_log" target="_blank">' .
		__( 'Click here', 'simple-download-monitor' ) . '</a>' .
		__( ' to view log file.', 'simple-download-monitor' ) . '<br>' .
		'<a id="sdm-reset-log" href="#0" style="color: red">' . __( 'Click here', 'simple-download-monitor' ) . '</a>' .
		__( ' to reset log file.', 'simple-download-monitor' ) . '</p></label>';
	}

	public function termscond_enable_cb() {
		$main_opts = get_option( 'sdm_advanced_options' );
		echo '<input name="sdm_advanced_options[termscond_enable]" id="termscond_enable" type="checkbox" ' . checked( 1, isset( $main_opts['termscond_enable'] ), false ) . ' /> ';
		echo '<p class="description">' . __( 'You can use this option to make the visitors agree to your terms before they can download the item.', 'simple-download-monitor' ) . '</p>';
	}

	public function termscond_url_cb() {
		$main_opts = get_option( 'sdm_advanced_options' );
		$value     = isset( $main_opts['termscond_url'] ) ? $main_opts['termscond_url'] : '';
		echo '<input size="100" name="sdm_advanced_options[termscond_url]" id="termscond_url" type="text" value="' . $value . '" /> ';
		echo '<p class="description">' . __( 'Enter the URL of your terms and conditions page.', 'simple-download-monitor' ) . '</p>';
	}

	public function adsense_below_description_cb() {
		$main_opts = get_option( 'sdm_advanced_options' );
		$value     = isset( $main_opts['adsense_below_description'] ) ? $main_opts['adsense_below_description'] : '';
		//echo '<input size="100" name="sdm_advanced_options[adsense_below_description]" id="adsense_below_description" type="text" value="'.$value.'" /> ';
		echo '<textarea name="sdm_advanced_options[adsense_below_description]" id="adsense_below_description" rows="6" cols="60">' . $value . '</textarea>';
		echo '<p class="description">' . __( 'Enter the Adsense or Ad code that you want to show below the download item description.', 'simple-download-monitor' ) . '</p>';
	}

	public function maps_api_key_cb() {
		$main_opts = get_option( 'sdm_advanced_options' );
		$value     = isset( $main_opts['maps_api_key'] ) ? $main_opts['maps_api_key'] : '';
		echo '<input size="100" name="sdm_advanced_options[maps_api_key]" id="maps_api_key" type="text" value="' . $value . '" />';
		echo '<p class="description">' . __( 'Enter your Google Maps API key. You can create new API key using <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">this instruction</a>.', 'simple-download-monitor' ) . '</p>';
	}

	public function sdm_add_clone_record_btn( $action, $post ) {
		// Only execute on SDM CPT posts page
		if ( $post->post_type == 'sdm_downloads' ) {
			$action['clone'] = sprintf(
				'<a href="%2$s" aria-label="%1$s">%1$s</a>',
				esc_html__( 'Clone', 'simple-download-monitor' ),
				$this->get_duplicate_url( $post->ID )
			);
		}
		return $action;
	}

	/**
	 * Returns duplicate post URL
	 *
	 * @return string
	 */
	public function get_duplicate_url( $post_id ) {
		global $wp;
		return add_query_arg(
			array(
				'action' => 'sdm_clone_post',
				'post'   => $post_id,
				'ref'    => urlencode( add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) ),
				'_nonce' => wp_create_nonce( 'sdm_downloads' ),
			),
			esc_url( admin_url( 'admin.php' ) )
		);
	}

	public function sdm_action_clone_post() {

		global $wpdb;
		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) || ( isset( $_REQUEST['action'] ) && 'sdm_clone_post' == $_REQUEST['action'] ) ) ) {
			wp_die( __( 'No post to duplicate has been supplied!', 'simple-download-monitor' ) );
		}

		/*
		* Nonce verification
		*/
		if ( ! isset( $_GET['_nonce'] ) || ! wp_verify_nonce( $_GET['_nonce'], 'sdm_downloads' ) ) {
			return;
		}

		/*
		* get the original post id
		*/
		$post_id = ( isset( $_GET['post'] ) ? absint( $_GET['post'] ) : absint( $_POST['post'] ) );
		/*
		* and all the original post data then
		*/
		$post = get_post( $post_id );

		/*
		* if you don't want current user to be the new post author,
		* then change next couple of lines to this: $new_post_author = $post->post_author;
		*/
		$current_user    = wp_get_current_user();
		$new_post_author = $current_user->ID;

		/*
		* if post data exists, create the post duplicate
		*/
		if ( isset( $post ) && $post != null ) {

			/*
			 * new post data array
			 */
			$args = array(
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $new_post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				'post_status'    => 'draft',
				'post_title'     => $post->post_title,
				'post_type'      => $post->post_type,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order,
			);

			/*
			 * insert the post by wp_insert_post() function
			 */
			$new_post_id = wp_insert_post( $args );

			/*
			 * get all current post terms ad set them to the new post draft
			 */
			$taxonomies = get_object_taxonomies( $post->post_type ); // returns array of taxonomy names for post type, ex array("category", "post_tag");
			foreach ( $taxonomies as $taxonomy ) {
				$post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
				wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
			}

			/*
			 * duplicate all post meta just in two SQL queries
			 */
			$post_meta_infos = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id" );
			if ( count( $post_meta_infos ) != 0 ) {
				$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
				foreach ( $post_meta_infos as $meta_info ) {
					$meta_key = $meta_info->meta_key;
					if ( $meta_key == '_wp_old_slug' ) {
						continue;
					}
					$meta_value      = addslashes( $meta_info->meta_value );
					$sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
				}
				$sql_query .= implode( ' UNION ALL ', $sql_query_sel );
				$wpdb->query( $sql_query );
			}

			/*
			 * finally, redirect to the edit post screen for the new draft
			 */
			wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
			exit;
		} else {
			wp_die( __( 'Post creation failed, could not find original post: ', 'simple-download-monitor' ) . $post_id );
		}
	}

}

//End of simpleDownloadManager class
//Initialize the simpleDownloadManager class
$simpleDownloadManager = new simpleDownloadManager();

// Tinymce Button Populate Post ID's
add_action( 'wp_ajax_nopriv_sdm_tiny_get_post_ids', 'sdm_tiny_get_post_ids_ajax_call' );
add_action( 'wp_ajax_sdm_tiny_get_post_ids', 'sdm_tiny_get_post_ids_ajax_call' );

function sdm_tiny_get_post_ids_ajax_call() {

	$posts = get_posts(
		array(
			'post_type'   => 'sdm_downloads',
			'numberposts' => -1,
		)
	);
	foreach ( $posts as $item ) {
		$test[] = array(
			'post_id'    => $item->ID,
			'post_title' => $item->post_title,
		);
	}

	$response = json_encode(
		array(
			'success' => true,
			'test'    => $test,
		)
	);

	header( 'Content-Type: application/json' );
	echo $response;
	exit;
}

//Remove Thumbnail Image
//add_action('wp_ajax_nopriv_sdm_remove_thumbnail_image', '');//This is only available to logged-in users
add_action( 'wp_ajax_sdm_remove_thumbnail_image', 'sdm_remove_thumbnail_image_ajax_call' ); //Execute this for authenticated users only

function sdm_remove_thumbnail_image_ajax_call() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		//Permission denied
		wp_die( __( 'Permission denied!', 'simple-download-monitor' ) );
		exit;
	}

	//Go ahead with the thumbnail removal
	$post_id    = intval( $_POST['post_id_del'] );
	$key_exists = metadata_exists( 'post', $post_id, 'sdm_upload_thumbnail' );
	if ( $key_exists ) {
		$success = delete_post_meta( $post_id, 'sdm_upload_thumbnail' );
		if ( $success ) {
			$response = json_encode( array( 'success' => true ) );
		}
	} else {
		// in order for frontend script to not display "Ajax error", let's return some data
		$response = json_encode( array( 'not_exists' => true ) );
	}

	header( 'Content-Type: application/json' );
	echo $response;
	exit;
}

// Populate category tree
add_action( 'wp_ajax_nopriv_sdm_pop_cats', 'sdm_pop_cats_ajax_call' );
add_action( 'wp_ajax_sdm_pop_cats', 'sdm_pop_cats_ajax_call' );

function sdm_pop_cats_ajax_call() {

	$cat_slug  = sanitize_text_field( $_POST['cat_slug'] );  // Get button cpt slug
	$parent_id = intval( $_POST['parent_id'] );  // Get button cpt id
	// Query custom posts based on taxonomy slug
	$posts = get_posts(
		array(
			'post_type'   => 'sdm_downloads',
			'numberposts' => -1,
			'tax_query'   => array(
				array(
					'taxonomy'         => 'sdm_categories',
					'field'            => 'slug',
					'terms'            => $cat_slug,
					'include_children' => 0,
				),
			),
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);

	$final_array = array();

	// Loop results
	foreach ( $posts as $post ) {
		// Create array of variables to pass to js
		$final_array[] = array(
			'id'        => $post->ID,
			'permalink' => get_permalink( $post->ID ),
			'title'     => $post->post_title,
		);
	}

	// Generate ajax response
	$response = json_encode( array( 'final_array' => $final_array ) );
	header( 'Content-Type: application/json' );
	echo $response;
	exit;
}

/*
 * * Setup Sortable Columns
 */
add_filter( 'manage_edit-sdm_downloads_columns', 'sdm_create_columns' ); // Define columns
add_filter( 'manage_edit-sdm_downloads_sortable_columns', 'sdm_downloads_sortable' ); // Make sortable
add_action( 'manage_sdm_downloads_posts_custom_column', 'sdm_downloads_columns_content', 10, 2 ); // Populate new columns

function sdm_create_columns( $cols ) {

	unset( $cols['title'] );
	unset( $cols['taxonomy-sdm_tags'] );
	unset( $cols['taxonomy-sdm_categories'] );
	unset( $cols['date'] );

	$cols['title']                   = __( 'Title', 'simple-download-monitor' );
	$cols['sdm_downloads_thumbnail'] = __( 'Image', 'simple-download-monitor' );
	$cols['sdm_downloads_id']        = __( 'ID', 'simple-download-monitor' );
	$cols['sdm_downloads_file']      = __( 'File', 'simple-download-monitor' );
	$cols['taxonomy-sdm_categories'] = __( 'Categories', 'simple-download-monitor' );
	$cols['taxonomy-sdm_tags']       = __( 'Tags', 'simple-download-monitor' );
	$cols['sdm_downloads_count']     = __( 'Downloads', 'simple-download-monitor' );
	$cols['date']                    = __( 'Date Posted', 'simple-download-monitor' );
	return $cols;
}

function sdm_downloads_sortable( $cols ) {

	$cols['sdm_downloads_id']        = 'sdm_downloads_id';
	$cols['sdm_downloads_file']      = 'sdm_downloads_file';
	$cols['sdm_downloads_count']     = 'sdm_downloads_count';
	$cols['taxonomy-sdm_categories'] = 'taxonomy-sdm_categories';
	$cols['taxonomy-sdm_tags']       = 'taxonomy-sdm_tags';
	return $cols;
}

function sdm_downloads_columns_content( $column_name, $post_ID ) {

	if ( $column_name == 'sdm_downloads_thumbnail' ) {
		$old_thumbnail = get_post_meta( $post_ID, 'sdm_upload_thumbnail', true );
		//$old_value = isset($old_thumbnail) ? $old_thumbnail : '';
		if ( $old_thumbnail ) {
			echo '<p class="sdm_downloads_thumbnail_in_admin_listing"><img src="' . $old_thumbnail . '" style="width:50px;height:50px;" /></p>';
		}
	}
	if ( $column_name == 'sdm_downloads_id' ) {
		echo '<p class="sdm_downloads_postid">' . $post_ID . '</p>';
	}
	if ( $column_name == 'sdm_downloads_file' ) {
		$old_file = get_post_meta( $post_ID, 'sdm_upload', true );
		$file     = isset( $old_file ) ? $old_file : '--';
		echo '<p class="sdm_downloads_file">' . $file . '</p>';
	}
	if ( $column_name == 'sdm_downloads_count' ) {
		global $wpdb;
		$wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'sdm_downloads WHERE post_id=%s', $post_ID ) );
		echo '<p class="sdm_downloads_count">' . $wpdb->num_rows . '</p>';
	}
}
