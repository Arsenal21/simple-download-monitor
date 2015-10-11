<?php

function sdm_handle_admin_menu() {
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
    $sdm_logs_page = add_submenu_page('edit.php?post_type=sdm_downloads', __('Logs', 'simple-download-monitor'), __('Logs', 'simple-download-monitor'), 'manage_options', 'logs', 'sdm_create_logs_page');
    $sdm_settings_page = add_submenu_page('edit.php?post_type=sdm_downloads', __('Settings', 'simple-download-monitor'), __('Settings', 'simple-download-monitor'), 'manage_options', 'settings', 'sdm_create_settings_page');
}

/*
 * Settings page
 */
function sdm_create_settings_page() {
    echo '<div class="wrap">';
    echo '<div id="poststuff"><div id="post-body">';
    ?>
    <h2><?php _e('Simple Download Monitor Settings Page', 'simple-download-monitor') ?></h2>

    <div style="background: #FFF6D5; border: 1px solid #D1B655; color: #3F2502; padding: 15px 10px">
        Read the full plugin usage documentation <a href="https://www.tipsandtricks-hq.com/simple-wordpress-download-monitor-plugin" target="_blank">here</a>.
        You can also <a href="http://www.tipsandtricks-hq.com/development-center" target="_blank"><?php _e('follow us', 'simple-download-monitor'); ?></a> <?php _e('on Twitter, Google+ or via Email to stay upto date about the new features of this plugin.', 'simple-download-monitor'); ?>
    </div>

    <!-- settings page form -->
    <form method="post" action="options.php">

        <!-- BEGIN ADMIN OPTIONS DIV -->	    
        <div id="sdm_admin_opts_div" class="sdm_sliding_div_title">
            <div class="sdm_slider_title">
    <?php _e('Admin Options', 'simple-download-monitor') ?>
            </div>
            <div class="sdm_desc">
    <?php _e("Control various plugin features.", 'simple-download-monitor') ?>
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
    <?php _e('Color Options', 'simple-download-monitor') ?>
            </div>
            <div class="sdm_desc">
    <?php _e("Adjust color options", 'simple-download-monitor') ?>
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
        <p><?php _e('If you need a feature rich and supported plugin for selling your digital items then checkout our', 'simple-download-monitor'); ?> <a href="https://www.tipsandtricks-hq.com/wordpress-estore-plugin-complete-solution-to-sell-digital-products-from-your-wordpress-blog-securely-1059" target="_blank"><?php _e('WP eStore Plugin', 'simple-download-monitor'); ?></a>
        </p>
    </div>

    <?php
    echo '</div></div>'; //end of post-stuff
    echo '</div>'; //end of wrap
}

/*
 * * Logs Page
 */
function sdm_create_logs_page() {
    global $wpdb;
    
    if (isset($_POST['sdm_reset_log_entries'])) {
        //reset log entries
	$table_name = $wpdb->prefix . 'sdm_downloads';	
	$query = "TRUNCATE $table_name";
	$result = $wpdb->query($query);
        echo '<div id="message" class="updated fade"><p>';
        _e('Download log entries deleted!', 'simple-download-monitor');
        echo '</p></div>';        
    }
    
    /*** Display the logs table ***/
    //Create an instance of our package class...
    $sdmListTable = new sdm_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $sdmListTable->prepare_items();
    ?>
    <div class="wrap">
    
        <div id="icon-users" class="icon32"><br/></div>
        <h2><?php _e('Download Logs', 'simple-download-monitor'); ?></h2>

        <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
            <p><?php _e('This page lists all tracked downloads.', 'simple-download-monitor'); ?></p>
        </div>

        <div id="poststuff"><div id="post-body">
            <!-- Log reset button -->
            <div class="postbox">
            <h3><label for="title"><?php _e('Reset Download Log Entries', 'simple-download-monitor'); ?></label></h3>
            <div class="inside">

            <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" onSubmit="return confirm('Are you sure you want to reset all the log entries?');" >    
                <div class="submit">
                    <input type="submit" class="button" name="sdm_reset_log_entries" value="<?php _e('Reset Log Entries', 'simple-download-monitor'); ?>" />
                </div>    
            </form> 

            </div></div>
        </div></div>
        
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="sdm_downloads-filter" method="post">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $sdmListTable->display() ?>
        </form>

    
    </div><!-- end of wrap -->
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.fade').click(function() {
                $(this).fadeOut('slow');
            });
        });
    </script>
    <?php
}