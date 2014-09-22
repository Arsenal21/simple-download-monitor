<?php
/**
 * Plugin Name: Simple Download Monitor
 * Plugin URI: https://www.tipsandtricks-hq.com/simple-wordpress-download-monitor-plugin
 * Description: Easily manage downloadable files and monitor downloads of your digital files from your WordPress site.
 * Version: 3.1.4
 * Author: Tips and Tricks HQ, Ruhul Amin, Josh Lobe
 * Author URI: https://www.tipsandtricks-hq.com/development-center
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_SIMPLE_DL_MONITOR_VERSION', '3.1.4');
define('WP_SIMPLE_DL_MONITOR_DIR_NAME', dirname(plugin_basename(__FILE__)));
define('WP_SIMPLE_DL_MONITOR_URL', plugins_url('', __FILE__));
define('WP_SIMPLE_DL_MONITOR_PATH', plugin_dir_path(__FILE__));

global $sdm_db_version;
$sdm_db_version = '1.2';

//File includes
include_once('includes/sdm-utility-functions.php');
include_once('includes/sdm-logs-list-table.php');
include_once('sdm-shortcodes.php');
include_once('sdm-post-type-content-handler.php');

//Activation hook handler
register_activation_hook(__FILE__, 'sdm_install_db_table');

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
			  UNIQUE KEY id (id)
		);';

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);

    update_option('sdm_db_version', $sdm_db_version);
}

function sdm_db_update_check() {
    if (is_admin()) {//Check if DB needs to be upgraded
        global $sdm_db_version;
        $inst_db_version = get_option('sdm_db_version');
        if ($inst_db_version != $sdm_db_version) {
            sdm_install_db_table();
        }
    }
}

/*
 * * Handle Plugins loaded tasks
 */
add_action('plugins_loaded', 'sdm_plugins_loaded_tasks');

function sdm_plugins_loaded_tasks() {
    //Load language
    load_plugin_textdomain('sdm_lang', false, dirname(plugin_basename(__FILE__)) . '/langs/');

    //Handle db upgrade stuff
    sdm_db_update_check();

    //Handle download request if any
    handle_sdm_download_via_direct_post();
}

/*
 * * Add a 'Settings' link to plugins list page
 */
add_filter('plugin_action_links', 'sdm_settings_link', 10, 2);

function sdm_settings_link($links, $file) {
    static $this_plugin;
    if (!$this_plugin)
        $this_plugin = plugin_basename(__FILE__);
    if ($file == $this_plugin) {
        $settings_link = '<a href="edit.php?post_type=sdm_downloads&page=settings" title="SDM Settings Page">' . __("Settings", 'sdm_lang') . '</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}

// Houston... we have lift-off!!
class simpleDownloadManager {

    public function __construct() {

        add_action('init', array(&$this, 'sdm_register_post_type'));  // Create 'sdm_downloads' custom post type
        add_action('init', array(&$this, 'sdm_create_taxonomies'));  // Register 'tags' and 'categories' taxonomies
        add_action('init', 'sdm_register_shortcodes'); //Register the shortcodes
        add_action('wp_enqueue_scripts', array(&$this, 'sdm_frontend_scripts'));  // Register frontend scripts

        if (is_admin()) {
            add_action('admin_menu', array(&$this, 'sdm_create_menu_pages'));  // Create admin pages
            add_action('add_meta_boxes', array(&$this, 'sdm_create_upload_metabox'));  // Create metaboxes

            add_action('save_post', array(&$this, 'sdm_save_description_meta_data'));  // Save 'description' metabox
            add_action('save_post', array(&$this, 'sdm_save_upload_meta_data'));  // Save 'upload file' metabox
            add_action('save_post', array(&$this, 'sdm_save_thumbnail_meta_data'));  // Save 'thumbnail' metabox
            add_action('save_post', array(&$this, 'sdm_save_statistics_meta_data'));  // Save 'thumbnail' metabox

            add_action('admin_enqueue_scripts', array(&$this, 'sdm_admin_scripts'));  // Register admin scripts
            add_action('admin_print_styles', array(&$this, 'sdm_admin_styles'));  // Register admin styles

            add_action('admin_init', array(&$this, 'sdm_register_options'));  // Register admin options

            //add_filter('post_row_actions', array(&$this, 'sdm_remove_view_link_cpt'), 10, 2);  // Remove 'View' link in all downloads list view
        }
    }

    public function sdm_admin_scripts() {

        global $current_screen, $post;

        if (is_admin() && $current_screen->post_type == 'sdm_downloads' && $current_screen->base == 'post') {

            // These scripts are needed for the media upload thickbox
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
            wp_register_script('sdm-upload', WP_SIMPLE_DL_MONITOR_URL . '/js/sdm_admin_scripts.js', array('jquery', 'media-upload', 'thickbox'));
            wp_enqueue_script('sdm-upload');

            // Pass postID for thumbnail deletion
            ?>
            <script type="text/javascript">
                var sdm_del_thumb_postid = '<?php echo $post->ID; ?>';
            </script>
            <?php
            // Localize langauge strings used in js file
            $sdmTranslations = array(
                'image_removed' => __('Image Successfully Removed', 'sdm_lang'),
                'ajax_error' => __('Error with AJAX', 'sdm_lang')
            );
            wp_localize_script('sdm-upload', 'sdm_translations', $sdmTranslations);
        }

        // Pass admin ajax url
        ?>
        <script type="text/javascript">
            var sdm_admin_ajax_url = {sdm_admin_ajax_url: '<?php echo admin_url('admin-ajax.php?action=ajax'); ?>'};
            var sdm_plugin_url = '<?php echo plugins_url(); ?>';
            var tinymce_langs = {select_download_item: '<?php _e('Please select a Download Item:', 'sdm_lang') ?>', download_title: '<?php _e('Download Title', 'sdm_lang') ?>', include_fancy: '<?php _e('Include Fancy Box', 'sdm_lang') ?>', insert_shortcode: '<?php _e('Insert SDM Shortcode', 'sdm_lang') ?>'};
        </script>
        <?php
    }

    public function sdm_frontend_scripts() {

        // Pass language strings to frontend of WP for js usage
        ?>
        <script type="text/javascript">
            var sdm_frontend_translations = {incorrect_password: '<?php _e('Incorrect Password', 'sdm_lang') ?>'};
        </script>
        <?php
    }

    public function sdm_admin_styles() {

        wp_enqueue_style('thickbox');  // Needed for media upload thickbox
        wp_enqueue_style('sdm_admin_styles', WP_SIMPLE_DL_MONITOR_URL . '/css/sdm_admin_styles.css');  // Needed for media upload thickbox
    }

    public function sdm_register_post_type() {

        //*****
        //*****  Create 'sdm_downloads' Custom Post Type
        $labels = array(
            'name' => __('Downloads', 'sdm_lang'),
            'singular_name' => __('Downloads', 'sdm_lang'),
            'add_new' => __('Add New', 'sdm_lang'),
            'add_new_item' => __('Add New', 'sdm_lang'),
            'edit_item' => __('Edit Download', 'sdm_lang'),
            'new_item' => __('New Download', 'sdm_lang'),
            'all_items' => __('Downloads', 'sdm_lang'),
            'view_item' => __('View Download', 'sdm_lang'),
            'search_items' => __('Search Downloads', 'sdm_lang'),
            'not_found' => __('No Downloads found', 'sdm_lang'),
            'not_found_in_trash' => __('No Downloads found in Trash', 'sdm_lang'),
            'parent_item_colon' => __('Parent Download', 'sdm_lang'),
            'menu_name' => __('Downloads', 'sdm_lang')
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'sdm_downloads'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-download',
            'supports' => array('title')
        );
        register_post_type('sdm_downloads', $args);
    }

    public function sdm_create_taxonomies() {

        //*****
        //*****  Create CATEGORIES Taxonomy
        $labels_tags = array(
            'name' => _x('Categories', 'sdm_lang'),
            'singular_name' => _x('Category', 'sdm_lang'),
            'search_items' => __('Search Categories', 'sdm_lang'),
            'all_items' => __('All Categories', 'sdm_lang'),
            'parent_item' => __('Categories Genre', 'sdm_lang'),
            'parent_item_colon' => __('Categories Genre:', 'sdm_lang'),
            'edit_item' => __('Edit Category', 'sdm_lang'),
            'update_item' => __('Update Category', 'sdm_lang'),
            'add_new_item' => __('Add New Category', 'sdm_lang'),
            'new_item_name' => __('New Category', 'sdm_lang'),
            'menu_name' => __('Categories', 'sdm_lang')
        );
        $args_tags = array(
            'hierarchical' => true,
            'labels' => $labels_tags,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'sdm_categories'),
            'show_admin_column' => true
        );
        register_taxonomy('sdm_categories', array('sdm_downloads'), $args_tags);

        //*****
        //*****  Create TAGS Taxonomy
        $labels_tags = array(
            'name' => _x('Tags', 'sdm_lang'),
            'singular_name' => _x('Tag', 'sdm_lang'),
            'search_items' => __('Search Tags', 'sdm_lang'),
            'all_items' => __('All Tags', 'sdm_lang'),
            'parent_item' => __('Tags Genre', 'sdm_lang'),
            'parent_item_colon' => __('Tags Genre:', 'sdm_lang'),
            'edit_item' => __('Edit Tag', 'sdm_lang'),
            'update_item' => __('Update Tag', 'sdm_lang'),
            'add_new_item' => __('Add New Tag', 'sdm_lang'),
            'new_item_name' => __('New Tag', 'sdm_lang'),
            'menu_name' => __('Tags', 'sdm_lang')
        );
        $args_tags = array(
            'hierarchical' => false,
            'labels' => $labels_tags,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'sdm_tags'),
            'show_admin_column' => true
        );
        register_taxonomy('sdm_tags', array('sdm_downloads'), $args_tags);
    }

    public function sdm_create_menu_pages() {
        include_once('includes/sdm-admin-menu-handler.php');
        sdm_handle_admin_menu();

    }

    public function sdm_create_upload_metabox() {

        //*****
        //*****  Create metaboxes for the custom post type
        add_meta_box('sdm_description_meta_box', __('Description', 'sdm_lang'), array(&$this, 'display_sdm_description_meta_box'), 'sdm_downloads', 'normal', 'default'
        );
        add_meta_box('sdm_upload_meta_box', __('Upload File', 'sdm_lang'), array(&$this, 'display_sdm_upload_meta_box'), 'sdm_downloads', 'normal', 'default'
        );
        add_meta_box('sdm_thumbnail_meta_box', __('File Thumbnail (Optional)', 'sdm_lang'), array(&$this, 'display_sdm_thumbnail_meta_box'), 'sdm_downloads', 'normal', 'default'
        );
        add_meta_box('sdm_shortcode_meta_box', __('Shortcodes', 'sdm_lang'), array(&$this, 'display_sdm_shortcode_meta_box'), 'sdm_downloads', 'normal', 'default'
        );
        add_meta_box('sdm_stats_meta_box', __('Statistics', 'sdm_lang'), array(&$this, 'display_sdm_stats_meta_box'), 'sdm_downloads', 'normal', 'default'
        );
    }

    public function display_sdm_description_meta_box($post) {  // Description metabox
        _e('Add a description for this download item.', 'sdm_lang');
        echo '<br /><br />';

        $old_description = get_post_meta($post->ID, 'sdm_description', true);
        $sdm_description_field = array('textarea_name' => 'sdm_description');
        wp_editor($old_description, "sdm_description_editor_content", $sdm_description_field);
        
        wp_nonce_field('sdm_description_box_nonce', 'sdm_description_box_nonce_check');
    }

    public function display_sdm_upload_meta_box($post) {  // File Upload metabox
        $old_upload = get_post_meta($post->ID, 'sdm_upload', true);
        $old_value = isset($old_upload) ? $old_upload : '';
        _e('Click "Select File" to upload (or choose) the file.', 'sdm_lang');
        ?>
        <br /><br />
        <input id="upload_image_button" type="button" class="button-primary" value="<?php _e('Select File', 'sdm_lang'); ?>" />
        <span style="margin-left:40px;"></span>
        <?php _e('File URL:', 'sdm_lang') ?> <input id="sdm_upload" type="text" size="70" name="sdm_upload" value="<?php echo $old_value; ?>" />
        <?php
        wp_nonce_field('sdm_upload_box_nonce', 'sdm_upload_box_nonce_check');
    }

    public function display_sdm_thumbnail_meta_box($post) {  // Thumbnail upload metabox
        $old_thumbnail = get_post_meta($post->ID, 'sdm_upload_thumbnail', true);
        $old_value = isset($old_thumbnail) ? $old_thumbnail : '';
        _e('Click "Select Image" to upload (or choose) the file thumbnail image. This thumbnail image will be used to create a fancy file download box if you want to use it.', 'sdm_lang');
        echo '<br />';
        //_e('Recommended image size is 75px by 75px.', 'sdm_lang');
        ?>
        <br /><br />
        <input id="upload_thumbnail_button" type="button" class="button-primary" value="<?php _e('Select Image', 'sdm_lang'); ?>" />
        <input id="remove_thumbnail_button" type="button" class="button" value="<?php _e('Remove Image', 'sdm_lang'); ?>" />
        <span style="margin-left:40px;"></span>
        <input id="sdm_upload_thumbnail" type="hidden" size="70" name="sdm_upload_thumbnail" value="<?php echo $old_value; ?>" />
        <span id="sdm_get_thumb">
            <?php
            if ($old_value != '') {
                ?><img id="sdm_thumbnail_image" src="<?php echo $old_value; ?>" style="width:75px;height:75px;" />
                <?php
            }
            ?></span><?php
        wp_nonce_field('sdm_thumbnail_box_nonce', 'sdm_thumbnail_box_nonce_check');
    }

    public function display_sdm_shortcode_meta_box($post) {  // Shortcode metabox
        _e('This is the shortcode which can used on posts or pages to embed a download now button for this file. You can also use the shortcode inserter to add this shortcode to a post or page.', 'sdm_lang');
        echo '<br />';
        echo '[sdm_download id="' . $post->ID . '" fancy="0"]';
        echo '<br /><br />';

        _e('This shortcode may be used as a download counter.', 'sdm_lang');
        echo '<br />';
        echo '[sdm_download_counter id="' . $post->ID . '"]';
    }

    public function display_sdm_stats_meta_box($post) {  // Stats metabox
        $old_count = get_post_meta($post->ID, 'sdm_count_offset', true);
        $value = isset($old_count) && $old_count != '' ? $old_count : '0';

        _e('These are the statistics for this download item.', 'sdm_lang');
        echo '<br /><br />';

        global $wpdb;
        $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'sdm_downloads WHERE post_id=%s', $post->ID));
        _e('Number of Downloads:', 'sdm_lang');
        echo ' <strong>' . $wpdb->num_rows . '</strong>';
        echo '<span style="margin-left: 20px;"></span>';
        _e('Offset Count', 'sdm_lang');
        echo ' <input type="text" style="width:50px;" name="sdm_count_offset" value="' . $value . '" />';
        echo ' <img src="' . WP_SIMPLE_DL_MONITOR_URL . '/css/images/info.png" style="margin-left:10px;" title="Enter any positive or negative numerical value; to offset the download count shown, when using the download counter shortcode." />';
        wp_nonce_field('sdm_count_offset_nonce', 'sdm_count_offset_nonce_check');
    }

    public function sdm_save_description_meta_data($post_id) {  // Save Description metabox
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!isset($_POST['sdm_description_box_nonce_check']) || !wp_verify_nonce($_POST['sdm_description_box_nonce_check'], 'sdm_description_box_nonce'))
            return;

        if (isset($_POST['sdm_description'])) {
            update_post_meta($post_id, 'sdm_description', $_POST['sdm_description']);
        }
    }

    public function sdm_save_upload_meta_data($post_id) {  // Save File Upload metabox
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!isset($_POST['sdm_upload_box_nonce_check']) || !wp_verify_nonce($_POST['sdm_upload_box_nonce_check'], 'sdm_upload_box_nonce'))
            return;

        if (isset($_POST['sdm_upload'])) {
            update_post_meta($post_id, 'sdm_upload', $_POST['sdm_upload']);
        }
    }

    public function sdm_save_thumbnail_meta_data($post_id) {  // Save Thumbnail Upload metabox
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!isset($_POST['sdm_thumbnail_box_nonce_check']) || !wp_verify_nonce($_POST['sdm_thumbnail_box_nonce_check'], 'sdm_thumbnail_box_nonce'))
            return;

        if (isset($_POST['sdm_upload_thumbnail'])) {
            update_post_meta($post_id, 'sdm_upload_thumbnail', $_POST['sdm_upload_thumbnail']);
        }
    }

    public function sdm_save_statistics_meta_data($post_id) {  // Save Thumbnail Upload metabox
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!isset($_POST['sdm_count_offset_nonce_check']) || !wp_verify_nonce($_POST['sdm_count_offset_nonce_check'], 'sdm_count_offset_nonce'))
            return;

        if (isset($_POST['sdm_count_offset']) && is_numeric($_POST['sdm_count_offset'])) {

            update_post_meta($post_id, 'sdm_count_offset', $_POST['sdm_count_offset']);
        }
    }

    public function sdm_remove_view_link_cpt($action, $post) {

        // Only execute on SDM CPT posts page
        if ($post->post_type == 'sdm_downloads') {
            unset($action['view']);
        }

        return $action;
    }

    public function sdm_register_options() {

        register_setting('sdm_downloads_options', 'sdm_downloads_options');
        add_settings_section('admin_options', __('Admin Options', 'sdm_lang'), array($this, 'admin_options_cb'), 'admin_options_section');
        add_settings_section('sdm_colors', __('Colors', 'sdm_lang'), array($this, 'sdm_colors_cb'), 'sdm_colors_section');

        add_settings_field('admin_tinymce_button', __('Remove Tinymce Button', 'sdm_lang'), array($this, 'admin_tinymce_button_cb'), 'admin_options_section', 'admin_options');
        add_settings_field('download_button_color', __('Download Button Color', 'sdm_lang'), array($this, 'download_button_color_cb'), 'sdm_colors_section', 'sdm_colors');
    }

    public function admin_options_cb() {
        _e('Admin options settings', 'sdm_lang');
    }

    public function sdm_colors_cb() {
        _e('Front End colors settings', 'sdm_lang');
    }

    public function admin_tinymce_button_cb() {
        $main_opts = get_option('sdm_downloads_options');
        echo '<input name="sdm_downloads_options[admin_tinymce_button]" id="admin_tinymce_button" type="checkbox" class="sdm_opts_ajax_checkboxes" ' . checked(1, isset($main_opts['admin_tinymce_button']), false) . ' /> ';
        _e('Removes the SDM Downloads button from the WP content editor.', 'sdm_lang');
    }

    public function download_button_color_cb() {
        $main_opts = get_option('sdm_downloads_options');
        $color_opt = $main_opts['download_button_color'];
        $color_opts = array(__('Green', 'sdm_lang'), __('Blue', 'sdm_lang'), __('Purple', 'sdm_lang'), __('Teal', 'sdm_lang'), __('Dark Blue', 'sdm_lang'), __('Black', 'sdm_lang'), __('Grey', 'sdm_lang'), __('Pink', 'sdm_lang'), __('Orange', 'sdm_lang'), __('White', 'sdm_lang'));
        echo '<select name="sdm_downloads_options[download_button_color]" id="download_button_color" class="sdm_opts_ajax_dropdowns">';
        if (isset($color_opt)) {
            echo '<option value="' . $color_opt . '" selected="selected">' . $color_opt . ' (' . __('current', 'sdm_lang') . ')</option>';
        }
        foreach ($color_opts as $color) {
            echo '<option value="' . $color . '" ' . $sel_color . '>' . $color . '</option>';
        }
        echo '</select> ';
        _e('Adjusts the color of the "Download Now" button.', 'sdm_lang');
    }

}

$simpleDownloadManager = new simpleDownloadManager();

function sdm_get_password_entry_form($id) {
    $data = __('Enter Password to Download:', 'sdm_lang');
    $data .= '<form method="post">';
    $data .= '<input type="password" class="pass_text" value="" /> ';
    $data .= '<input type="button" class="pass_sumbit" value="' . __('Submit', 'sdm_lang') . '" />';
    $data .= '<input type="hidden" value="' . $id . '" />';
    $data .= '</form>';
    return $data;
}

/*
 * * Register scripts for front-end posts/pages
 */
add_action('wp_enqueue_scripts', 'sdm_wp_scripts');

function sdm_wp_scripts() {

    wp_enqueue_style('sdm-styles', WP_SIMPLE_DL_MONITOR_URL . '/css/sdm_wp_styles.css');
    wp_register_script('sdm-scripts', WP_SIMPLE_DL_MONITOR_URL . '/js/sdm_wp_scripts.js', array('jquery'));
    wp_enqueue_script('sdm-scripts');

    // Localize ajax script for frontend
    wp_localize_script('sdm-scripts', 'sdm_ajax_script', array('ajaxurl' => admin_url('admin-ajax.php')));
}

function handle_sdm_download_via_direct_post() {
    if (isset($_REQUEST['smd_process_download']) && $_REQUEST['smd_process_download'] == '1') {
        $download_id = strip_tags($_REQUEST['download_id']);
        $download_title = get_the_title($download_id);
        $download_link = get_post_meta($download_id, 'sdm_upload', true);
        $ipaddress = $_SERVER["REMOTE_ADDR"];
        $date_time = current_time('mysql');
        $visitor_country = sdm_ip_info('Visitor', 'Country');
		
        if(is_user_logged_in()) {  // Get user name (if logged in)
            global $current_user;
            get_currentuserinfo();
            $visitor_name = $current_user->user_login;
        }
        else {
            $visitor_name = __('Not Logged In','sdm_lang');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sdm_downloads';
        $data = array(
            'post_id' => $download_id,
            'post_title' => $download_title,
            'file_url' => $download_link,
            'visitor_ip' => $ipaddress,
            'date_time' => $date_time,
            'visitor_country' => $visitor_country,
            'visitor_name' => $visitor_name
        );

        $insert_table = $wpdb->insert($table, $data);

        if ($insert_table) {//Download request was logged successfully
            sdm_redirect_to_url($download_link);
        } else {//Failed to log the download request
            wp_die(__('Error! Failed to log the download request in the database table', 'sdm_lang'));
        }
        exit;
    }
}

// Tinymce Button Populate Post ID's
add_action('wp_ajax_nopriv_sdm_tiny_get_post_ids', 'sdm_tiny_get_post_ids_ajax_call');
add_action('wp_ajax_sdm_tiny_get_post_ids', 'sdm_tiny_get_post_ids_ajax_call');

function sdm_tiny_get_post_ids_ajax_call() {

    $args = array(
        'post_type' => 'sdm_downloads',
    );
    $loop = new WP_Query($args);
    $test = '';
    foreach ($loop->posts as $loop_post) {
        //$test .= $loop_post->ID.'|'.$loop_post->post_title.'_';
        $test[] = array('post_id' => $loop_post->ID, 'post_title' => $loop_post->post_title);
    }

    $response = json_encode(array('success' => true, 'test' => $test));

    header('Content-Type: application/json');
    echo $response;
    exit;
}

// Remove Thumbnail Image
add_action('wp_ajax_nopriv_sdm_remove_thumbnail_image', 'sdm_remove_thumbnail_image_ajax_call');
add_action('wp_ajax_sdm_remove_thumbnail_image', 'sdm_remove_thumbnail_image_ajax_call');

function sdm_remove_thumbnail_image_ajax_call() {

    $post_id = $_POST['post_id_del'];
    $success = delete_post_meta($post_id, 'sdm_upload_thumbnail');
    if ($success) {
        $response = json_encode(array('success' => true));
    }

    header('Content-Type: application/json');
    echo $response;
    exit;
}

// Check download password
add_action('wp_ajax_nopriv_sdm_check_pass', 'sdm_check_pass_ajax_call');
add_action('wp_ajax_sdm_check_pass', 'sdm_check_pass_ajax_call');

function sdm_check_pass_ajax_call() {

    $button_id = $_POST['button_id'];  // Get button cpt id
    $pass_val = $_POST['pass_val'];  // Get password attempt
    $success = '';
    $download_link = '';

    // Get post object
    $post_object = get_post($button_id);
    // Get post password
    $post_pass = $post_object->post_password;

    // Check if password is a match
    if ($post_pass != $pass_val) { // Match is a failure
        $success = 'no';  // Pass back to ajax
    } else {  // Match is a success
        $success = 'yes';  // Pass back to ajax

        $download_id = $button_id;
        $download_title = get_the_title($download_id);
        $download_link = get_post_meta($download_id, 'sdm_upload', true);
        $ipaddress = $_SERVER["REMOTE_ADDR"];
        $date_time = current_time('mysql');
        $visitor_country = sdm_ip_info('Visitor', 'Country');
		
        if(is_user_logged_in()) {  // Get user name (if logged in)
            global $current_user;
            get_currentuserinfo();
            $visitor_name = $current_user->user_login;
        }
        else {
            $visitor_name = __('Not Logged In','sdm_lang');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sdm_downloads';
        $data = array(
            'post_id' => $download_id,
            'post_title' => $download_title,
            'file_url' => $download_link,
            'visitor_ip' => $ipaddress,
            'date_time' => $date_time,
            'visitor_country' => $visitor_country,
            'visitor_name' => $visitor_name
        );

        $insert_table = $wpdb->insert($table, $data);
    }

    // Generate ajax response
    $response = json_encode(array('success' => $success, 'url' => $download_link));
    header('Content-Type: application/json');
    echo $response;
    exit;
}

// Populate category tree
add_action('wp_ajax_nopriv_sdm_pop_cats', 'sdm_pop_cats_ajax_call');
add_action('wp_ajax_sdm_pop_cats', 'sdm_pop_cats_ajax_call');

function sdm_pop_cats_ajax_call() {

    $cat_slug = $_POST['cat_slug'];  // Get button cpt slug
    $parent_id = $_POST['parent_id'];  // Get button cpt id
    // Query custom posts based on taxonomy slug
    $posts = query_posts(array(
        'post_type' => 'sdm_downloads',
        //'showposts' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'sdm_categories',
                'terms' => $cat_slug,
                'field' => 'slug',
                'include_children' => 0
            )
        ),
        'orderby' => 'title',
        'order' => 'ASC')
    );

    // Loop results
    foreach ($posts as $post) {

        // Create array of variables to pass to js
        $final_array[] = array('id' => $post->ID, 'permalink' => get_permalink($post->ID), 'title' => $post->post_title);
    }

    // Generate ajax response
    $response = json_encode(array('final_array' => $final_array));
    header('Content-Type: application/json');
    echo $response;
    exit;
}

/*
 * * Setup Sortable Columns
 */
add_filter('manage_edit-sdm_downloads_columns', 'sdm_create_columns'); // Define columns
add_filter('manage_edit-sdm_downloads_sortable_columns', 'sdm_downloads_sortable'); // Make sortable
add_action('manage_sdm_downloads_posts_custom_column', 'sdm_downloads_columns_content', 10, 2); // Populate new columns

function sdm_create_columns($cols) {

    unset($cols['title']);
    unset($cols['taxonomy-sdm_tags']);
    unset($cols['taxonomy-sdm_categories']);
    unset($cols['date']);

    $cols['sdm_downloads_thumbnail'] = __('Image', 'sdm_lang');
    $cols['title'] = __('Title', 'sdm_lang');
    $cols['sdm_downloads_id'] = __('ID', 'sdm_lang');
    $cols['sdm_downloads_file'] = __('File', 'sdm_lang');
    $cols['taxonomy-sdm_categories'] = __('Categories', 'sdm_lang');
    $cols['taxonomy-sdm_tags'] = __('Tags', 'sdm_lang');
    $cols['sdm_downloads_count'] = __('Downloads', 'sdm_lang');
    $cols['date'] = __('Date Posted', 'sdm_lang');
    return $cols;
}

function sdm_downloads_sortable($cols) {

    $cols['sdm_downloads_id'] = 'sdm_downloads_id';
    $cols['sdm_downloads_file'] = 'sdm_downloads_file';
    $cols['sdm_downloads_count'] = 'sdm_downloads_count';
    $cols['taxonomy-sdm_categories'] = 'taxonomy-sdm_categories';
    $cols['taxonomy-sdm_tags'] = 'taxonomy-sdm_tags';
    return $cols;
}

function sdm_downloads_columns_content($column_name, $post_ID) {

    if ($column_name == 'sdm_downloads_thumbnail') {
        $old_thumbnail = get_post_meta($post_ID, 'sdm_upload_thumbnail', true);
        //$old_value = isset($old_thumbnail) ? $old_thumbnail : '';
        if ($old_thumbnail) {
            echo '<p class="sdm_downloads_count"><img src="' . $old_thumbnail . '" style="width:50px;height:50px;" /></p>';
        }
    }
    if ($column_name == 'sdm_downloads_id') {
        echo '<p class="sdm_downloads_postid">' . $post_ID . '</p>';
    }
    if ($column_name == 'sdm_downloads_file') {
        $old_file = get_post_meta($post_ID, 'sdm_upload', true);
        $file = isset($old_file) ? $old_file : '--';
        echo '<p class="sdm_downloads_file">' . $file . '</p>';
    }
    if ($column_name == 'sdm_downloads_count') {
        global $wpdb;
        $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'sdm_downloads WHERE post_id=%s', $post_ID));
        echo '<p class="sdm_downloads_count">' . $wpdb->num_rows . '</p>';
    }
}

// Adjust admin column widths
add_action('admin_head', 'sdm_admin_column_width'); // Adjust column width in admin panel

function sdm_admin_column_width() {

    echo '<style type="text/css">';
    echo '.column-sdm_downloads_thumbnail { width:75px !important; overflow:hidden }';
    echo '.column-sdm_downloads_id { width:100px !important; overflow:hidden }';
    echo '.column-taxonomy-sdm_categories { width:200px !important; overflow:hidden }';
    echo '.column-taxonomy-sdm_tags { width:200px !important; overflow:hidden }';
    echo '</style>';
}

/*
 * * Register Tinymce Button
 */

// First check if option is checked to disable tinymce button
$main_option = get_option('sdm_downloads_options');
$tiny_button_option = isset($main_option['admin_tinymce_button']);
if ($tiny_button_option != true) {

    // Okay.. we're good.  Add the button.
    add_action('init', 'sdm_downloads_tinymce_button');

    function sdm_downloads_tinymce_button() {

        add_filter('mce_external_plugins', 'sdm_downloads_add_button');
        add_filter('mce_buttons', 'sdm_downloads_register_button');
    }

    function sdm_downloads_add_button($plugin_array) {

        $plugin_array['sdm_downloads'] = WP_SIMPLE_DL_MONITOR_URL . '/tinymce/sdm_editor_plugin.js';
        return $plugin_array;
    }

    function sdm_downloads_register_button($buttons) {

        //array_push( $buttons, 'sdm_downloads' );
        $buttons[] = 'sdm_downloads';
        return $buttons;
    }

}
