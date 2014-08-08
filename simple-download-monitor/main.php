<?php
/**
 * Plugin Name: Simple Download Monitor
 * Plugin URI: http://www.tipsandtricks-hq.com/simple-wordpress-download-monitor-plugin
 * Description: Easily manage downloadable files and monitor downloads of your digital files from your WordPress site.
 * Version: 3.1.2
 * Author: Tips and Tricks HQ, Ruhul Amin, Josh Lobe
 * Author URI: http://www.tipsandtricks-hq.com/development-center
 * License: GPL2
 */
if (!defined('ABSPATH')) {
    exit;
}

define('WP_SIMPLE_DL_MONITOR_VERSION', '3.1.2');
define('WP_SIMPLE_DL_MONITOR_DIR_NAME', dirname(plugin_basename(__FILE__)));
define('WP_SIMPLE_DL_MONITOR_URL', plugins_url('', __FILE__));
define('WP_SIMPLE_DL_MONITOR_PATH', plugin_dir_path(__FILE__));

global $sdm_db_version;
$sdm_db_version = '1.1';

//File includes
include_once('includes/sdm-utility-functions.php');
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

            add_filter('post_row_actions', array(&$this, 'sdm_remove_view_link_cpt'), 10, 2);  // Remove 'View' link in CPT view
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

        //*****
        //*****  If user clicked to download the bulk export log
        if (isset($_GET['download_log'])) {
            global $wpdb;
            $csv_output = '';
            $table = $wpdb->prefix . 'sdm_downloads';

            $result = mysql_query("SHOW COLUMNS FROM " . $table . "");

            $i = 0;
            if (mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_assoc($result)) {
                    $csv_output = $csv_output . $row['Field'] . ",";
                    $i++;
                }
            }
            $csv_output .= "\n";

            $values = mysql_query("SELECT * FROM " . $table . "");
            while ($rowr = mysql_fetch_row($values)) {
                for ($j = 0; $j < $i; $j++) {
                    $csv_output .= $rowr[$j] . ",";
                }
                $csv_output .= "\n";
            }

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false);
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"report.csv\";");
            header("Content-Transfer-Encoding: binary");

            echo $csv_output;
            exit;
        }

        //*****
        //*****  Create the 'logs' and 'settings' submenu pages
        $sdm_logs_page = add_submenu_page('edit.php?post_type=sdm_downloads', __('Logs', 'sdm_lang'), __('Logs', 'sdm_lang'), 'manage_options', 'logs', 'sdm_create_logs_page');
        $sdm_settings_page = add_submenu_page('edit.php?post_type=sdm_downloads', __('Settings', 'sdm_lang'), __('Settings', 'sdm_lang'), 'manage_options', 'settings', array(&$this, 'sdm_create_settings_page'));
    }

    public function sdm_create_settings_page() {
        echo '<div class="wrap">';
        echo '<div id="poststuff"><div id="post-body">';
        ?>
        <h2><?php _e('Simple Download Monitor Settings Page', 'sdm_lang') ?></h2>

        <div style="background: #FFF6D5; border: 1px solid #D1B655; color: #3F2502; padding: 15px 10px">
            <a href="http://www.tipsandtricks-hq.com/development-center" target="_blank"><?php _e('Follow us', 'sdm_lang'); ?></a> <?php _e('on Twitter, Google+ or via Email to stay upto date about the new features of this plugin.', 'sdm_lang'); ?>
        </div>

        <!-- settings page form -->
        <form method="post" action="options.php">

            <!-- BEGIN ADMIN OPTIONS DIV -->	    
            <div id="sdm_admin_opts_div" class="sdm_sliding_div_title">
                <div class="sdm_slider_title">
                    <?php _e('Admin Options', 'sdm_lang') ?>
                </div>
                <div class="sdm_desc">
                    <?php _e("Control various plugin features.", 'sdm_lang') ?>
                </div>
            </div>
            <div id="sliding_div1" class="slidingDiv">
                <?php
                // This prints out all hidden setting fields
                do_settings_sections('admin_options_section');
                settings_fields('sdm_downloads_options');

                submit_button();
                ?>
            </div>
            <!-- END ADMIN OPTIONS DIV -->

            <!-- BEGIN COLORS DIV -->
            <div id="sdm_color_opts_div" class="sdm_sliding_div_title">
                <div class="sdm_slider_title">
                    <?php _e('Color Options', 'sdm_lang') ?>
                </div>
                <div class="sdm_desc">
                    <?php _e("Adjust color options", 'sdm_lang') ?>
                </div>
            </div>
            <div id="sliding_div2" class="slidingDiv">
                <?php
                // This prints out all hidden setting fields
                do_settings_sections('sdm_colors_section');
                settings_fields('sdm_downloads_options');

                submit_button();
                ?>
            </div>
            <!-- END COLORS OPTIONS DIV -->

            <!-- End of settings page form -->
        </form>

        <div style="background: none repeat scroll 0 0 #FFF6D5;border: 1px solid #D1B655;color: #3F2502;margin: 10px 0;padding: 5px 5px 5px 10px;text-shadow: 1px 1px #FFFFFF;">	
            <p><?php _e('If you need a feature rich and supported plugin for selling your digital items then checkout our', 'sdm_lang'); ?> <a href="http://www.tipsandtricks-hq.com/wordpress-estore-plugin-complete-solution-to-sell-digital-products-from-your-wordpress-blog-securely-1059" target="_blank"><?php _e('WP eStore Plugin', 'sdm_lang'); ?></a>
            </p>
        </div>

        <?php
        echo '</div></div>'; //end of post-stuff
        echo '</div>'; //end of wrap
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
        ?>
        <textarea id="sdm_description" name="sdm_description" style="width:60%;height:40px;"><?php echo $old_description; ?></textarea>
        <?php
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

/*
 * *
 * * Logs Page
 * *
 */
//*****
//*****  Check WP_List_Table exists
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

//*****
//*****  Define our new Table
class sdm_List_Table extends WP_List_Table {

    function __construct() {

        global $status, $page;

        //Set parent defaults
        parent::__construct(array(
            'singular' => __('Download', 'sdm_lang'), //singular name of the listed records
            'plural' => __('Downloads', 'sdm_lang'), //plural name of the listed records
            'ajax' => false        //does this table support ajax?
        ));
    }

    function column_default($item, $column_name) {

        switch ($column_name) {
            case 'URL':
            case 'visitor_ip':
            case 'date':
                return $item[$column_name];
            case 'visitor_country':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_title($item) {

        //Build row actions
        $actions = array(
            'edit' => sprintf('<a href="' . admin_url('post.php?post=' . $item['ID'] . '&action=edit') . '">' . __('Edit', 'sdm_lang') . '</a>'),
            'delete' => sprintf('<a href="?post_type=sdm_downloads&page=%s&action=%s&download=%s&datetime=%s">' . __('Delete', 'sdm_lang') . '</a>', $_REQUEST['page'], 'delete', $item['ID'], $item['date'])
        );

        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
                /* $1%s */ $item['title'],
                /* $2%s */ $item['ID'],
                /* $3%s */ $this->row_actions($actions)
        );
    }

    function column_cb($item) {

        return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                /* $1%s */ $this->_args['singular'], //Let's simply repurpose the table's singular label ("Download")
                /* $2%s */ $item['ID'] . '|' . $item['date']            //The value of the checkbox should be the record's id
        );
    }

    function get_columns() {

        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'title' => __('Title', 'sdm_lang'),
            'URL' => __('File', 'sdm_lang'),
            'visitor_ip' => __('Visitor IP', 'sdm_lang'),
            'date' => __('Date', 'sdm_lang'),
            'visitor_country' => __('Country', 'sdm_lang')
        );
        return $columns;
    }

    function get_sortable_columns() {

        $sortable_columns = array(
            'title' => array('title', false), //true means it's already sorted
            'URL' => array('URL', false),
            'visitor_ip' => array('visitor_ip', false),
            'date' => array('date', false),
            'visitor_country' => array('visitor_country', false)
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {

        $actions = array();
        $actions['delete2'] = __('Delete Permanently', 'sdm_lang');
        $actions['export_all'] = __('Export All as Excel', 'sdm_lang');
        //$actions['export-selected'] = __( 'Export Selected', 'sdm_lang' );

        return $actions;
    }

    function process_bulk_action() {

        // security check!
        if (isset($_POST['_wpnonce']) && !empty($_POST['_wpnonce'])) {

            $nonce = filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING);
            $action = 'bulk-' . $this->_args['plural'];

            if (!wp_verify_nonce($nonce, $action))
                wp_die(__('Nope! Security check failed!', 'sdm_lang'));
        }

        $action = $this->current_action();

        // If bulk 'Export All' was clicked
        if ('export_all' === $this->current_action()) {

            echo '<div id="message" class="updated"><p><strong><a id="sdm_download_export" href="?post_type=sdm_downloads&page=logs&download_log">' . __('Download Export File', 'sdm_lang') . '</a></strong></p></div>';
        }

        // if bulk 'Delete Permanently' was clicked
        if ('delete2' === $this->current_action()) {

            if (!isset($_POST['download']) || $_POST['download'] == null) {
                echo '<div id="message" class="updated fade"><p><strong>' . __('No entries were selected.', 'sdm_lang') . '</strong></p><p><em>' . __('Click to Dismiss', 'sdm_lang') . '</em></p></div>';
                return;
            }

            foreach ($_POST['download'] as $item) {
                $str_tok_id = substr($item, 0, strpos($item, '|'));
                $str_tok_datetime = substr($item, strpos($item, '|') + 1);

                global $wpdb;
                $del_row = $wpdb->query(
                        'DELETE FROM ' . $wpdb->prefix . 'sdm_downloads
									WHERE post_id = "' . $str_tok_id . '"
									AND date_time = "' . $str_tok_datetime . '"'
                );
            }
            if ($del_row) {
                echo '<div id="message" class="updated fade"><p><strong>' . __('Entries Deleted!', 'sdm_lang') . '</strong></p><p><em>' . __('Click to Dismiss', 'sdm_lang') . '</em></p></div>';
            } else {
                echo '<div id="message" class="updated fade"><p><strong>' . __('Error', 'sdm_lang') . '</strong></p><p><em>' . __('Click to Dismiss', 'sdm_lang') . '</em></p></div>';
            }
        }

        // If single entry 'Delete' was clicked
        if ('delete' === $this->current_action()) {

            $item_id = isset($_GET['download']) ? strtok($_GET['download'], '|') : '';
            $item_datetime = isset($_GET['datetime']) ? $_GET['datetime'] : '';

            global $wpdb;
            $del_row = $wpdb->query(
                    'DELETE FROM ' . $wpdb->prefix . 'sdm_downloads
								WHERE post_id = "' . $item_id . '"
								AND date_time = "' . $item_datetime . '"'
            );
            if ($del_row) {
                echo '<div id="message" class="updated fade"><p><strong>' . __('Entry Deleted!', 'sdm_lang') . '</strong></p><p><em>' . __('Click to Dismiss', 'sdm_lang') . '</em></p></div>';
            } else {
                echo '<div id="message" class="updated fade"><p><strong>' . __('Error', 'sdm_lang') . '</strong></p><p><em>' . __('Click to Dismiss', 'sdm_lang') . '</em></p></div>';
            }
        }
    }

    function prepare_items() {

        global $wpdb; //This is used only if making any database queries
        $per_page = 10;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();


        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();

        //$data = $this->example_data;

        function usort_reorder($a, $b) {
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
        }

        $data_results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'sdm_downloads');
        $data = array();
        foreach ($data_results as $data_result) {
            $data[] = array('ID' => $data_result->post_id, 'title' => $data_result->post_title, 'URL' => $data_result->file_url, 'visitor_ip' => $data_result->visitor_ip, 'date' => $data_result->date_time, 'visitor_country' => $data_result->visitor_country);
        }


        usort($data, 'usort_reorder');
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        $this->items = $data;
        $this->set_pagination_args(array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page, //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
        ));
    }

}

function sdm_create_logs_page() {

    //Create an instance of our package class...
    $sdmListTable = new sdm_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $sdmListTable->prepare_items();
    ?>
    <div class="wrap">

        <div id="icon-users" class="icon32"><br/></div>
        <h2><?php _e('Download Logs', 'sdm_lang'); ?></h2>

        <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
            <p><?php _e('This page lists all tracked downloads.', 'sdm_lang'); ?></p>
        </div>

        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="sdm_downloads-filter" method="post">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $sdmListTable->display() ?>
        </form>

    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.fade').click(function() {
                $(this).fadeOut('slow');
            });
        });
    </script>
    <?php
}

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

        global $wpdb;
        $table = $wpdb->prefix . 'sdm_downloads';
        $data = array(
            'post_id' => $download_id,
            'post_title' => $download_title,
            'file_url' => $download_link,
            'visitor_ip' => $ipaddress,
            'date_time' => $date_time,
            'visitor_country' => $visitor_country
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

function sdm_redirect_to_url($url, $delay = '0', $exit = '1') {
    if (empty($url)) {
        echo '<strong>';
        _e('Error! The URL value is empty. Please specify a correct URL value to redirect to!', 'sdm_lang');
        echo '</strong>';
        exit;
    }
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . $url . '" />';
    }
    if ($exit == '1') {//exit
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

        global $wpdb;
        $table = $wpdb->prefix . 'sdm_downloads';
        $data = array(
            'post_id' => $download_id,
            'post_title' => $download_title,
            'file_url' => $download_link,
            'visitor_ip' => $ipaddress,
            'date_time' => $date_time,
            'visitor_country' => $visitor_country
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
    unset($cols['visitor_country']);

    $cols['sdm_downloads_thumbnail'] = __('Image', 'sdm_lang');
    $cols['title'] = __('Title', 'sdm_lang');
    $cols['sdm_downloads_id'] = __('ID', 'sdm_lang');
    $cols['sdm_downloads_file'] = __('File', 'sdm_lang');
    $cols['taxonomy-sdm_categories'] = __('Categories', 'sdm_lang');
    $cols['taxonomy-sdm_tags'] = __('Tags', 'sdm_lang');
    $cols['sdm_downloads_count'] = __('Downloads', 'sdm_lang');
    $cols['date'] = __('Date Posted', 'sdm_lang');
    $cols['visitor_country'] = __('Visitor Country', 'sdm_lang');
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

// Helper function to get visitor country (or other info)
function sdm_ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
    $output = NULL;
    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        $ip = $_SERVER["REMOTE_ADDR"];
        if ($deep_detect) {
            if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
    }
    $purpose = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
    $support = array("country", "countrycode", "state", "region", "city", "location", "address");
    $continents = array(
        "AF" => "Africa",
        "AN" => "Antarctica",
        "AS" => "Asia",
        "EU" => "Europe",
        "OC" => "Australia (Oceania)",
        "NA" => "North America",
        "SA" => "South America"
    );
    if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
        $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
        if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
            switch ($purpose) {
                case "location":
                    $output = array(
                        "city" => @$ipdat->geoplugin_city,
                        "state" => @$ipdat->geoplugin_regionName,
                        "country" => @$ipdat->geoplugin_countryName,
                        "country_code" => @$ipdat->geoplugin_countryCode,
                        "continent" => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                        "continent_code" => @$ipdat->geoplugin_continentCode
                    );
                    break;
                case "address":
                    $address = array($ipdat->geoplugin_countryName);
                    if (@strlen($ipdat->geoplugin_regionName) >= 1)
                        $address[] = $ipdat->geoplugin_regionName;
                    if (@strlen($ipdat->geoplugin_city) >= 1)
                        $address[] = $ipdat->geoplugin_city;
                    $output = implode(", ", array_reverse($address));
                    break;
                case "city":
                    $output = @$ipdat->geoplugin_city;
                    break;
                case "state":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "region":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "country":
                    $output = @$ipdat->geoplugin_countryName;
                    break;
                case "countrycode":
                    $output = @$ipdat->geoplugin_countryCode;
                    break;
            }
        }
    }
    return $output;
}