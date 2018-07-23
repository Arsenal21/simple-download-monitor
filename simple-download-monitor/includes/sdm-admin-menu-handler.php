<?php
/*
 * Creates/adds the other admin menu page links to the main SDM custom post type menu
 */

function sdm_handle_admin_menu() {

//*****  Create the 'logs' and 'settings' submenu pages
    $sdm_logs_page		 = add_submenu_page( 'edit.php?post_type=sdm_downloads', __( 'Logs', 'simple-download-monitor' ), __( 'Logs', 'simple-download-monitor' ), 'manage_options', 'logs', 'sdm_create_logs_page' );
    $sdm_logs_page		 = add_submenu_page( 'edit.php?post_type=sdm_downloads', __( 'Stats', 'simple-download-monitor' ), __( 'Stats', 'simple-download-monitor' ), 'manage_options', 'sdm-stats', 'sdm_create_stats_page' );
    $sdm_settings_page	 = add_submenu_page( 'edit.php?post_type=sdm_downloads', __( 'Settings', 'simple-download-monitor' ), __( 'Settings', 'simple-download-monitor' ), 'manage_options', 'sdm-settings', 'sdm_create_settings_page' );
    $sdm_addons_page	 = add_submenu_page( 'edit.php?post_type=sdm_downloads', __( 'Add-ons', 'simple-download-monitor' ), __( 'Add-ons', 'simple-download-monitor' ), 'manage_options', 'sdm-addons', 'sdm_create_addons_page' );
}

add_filter( 'whitelist_options', 'sdm_admin_menu_function_hook' );

/**
 * sdm_admin_menu_function_hook
 * Its hook for add advanced testings tab, and working on saving options to db, if not used, you receive error "options page not found"
 * @param array $whitelist_options
 * @return string
 */
function sdm_admin_menu_function_hook( $whitelist_options = array() ) {
    $whitelist_options[ 'recaptcha_options_section' ] = array( 'sdm_advanced_options' );
    $whitelist_options[ 'termscond_options_section' ] = array( 'sdm_advanced_options' );
    $whitelist_options[ 'adsense_options_section' ] = array( 'sdm_advanced_options' );
    
    return $whitelist_options;
}

/*
 * Settings menu page
 */

function sdm_create_settings_page() {

    echo '<div class="wrap">';
    //echo '<div id="poststuff"><div id="post-body">';
    ?>
    <style>
        div.sdm-settings-grid {
    	display: inline-block;
        }
        div.sdm-main-cont {
    	width: 80%;
        }
        div.sdm-sidebar-cont {
    	width: 19%;
    	float: right;
        }
        div#poststuff {
    	min-width: 19%;
        }
        .sdm-stars-container {
    	text-align: center;
    	margin-top: 10px;
        }
        .sdm-stars-container span {
    	vertical-align: text-top;
    	color: #ffb900;
        }
        .sdm-stars-container a {
    	text-decoration: none;
        }
        @media (max-width: 782px) {
    	div.sdm-settings-grid {
    	    display: block;
    	    float: none;
    	    width: 100%;
    	}
        }
    </style>
    <h1><?php _e( 'Simple Download Monitor Settings Page', 'simple-download-monitor' ) ?></h1>

    <?php
    ob_start();
    $wpsdm_plugin_tabs	 = array(
	'sdm-settings'				 => __( 'General Settings', 'simple-download-monitor' ),
	'sdm-settings&action=advanced-settings'	 => __( 'Advanced Settings', 'simple-download-monitor' ),
    );
    $current		 = "";
    if ( isset( $_GET[ 'page' ] ) ) {
	$current = sanitize_text_field( $_GET[ 'page' ] );
	if ( isset( $_GET[ 'action' ] ) ) {
	    $current .= "&action=" . sanitize_text_field( $_GET[ 'action' ] );
	}
    }
    $nav_tabs	 = '';
    $nav_tabs	 .= '<h2 class="nav-tab-wrapper">';
    foreach ( $wpsdm_plugin_tabs as $location => $tabname ) {
	if ( $current == $location ) {
	    $class = ' nav-tab-active';
	} else {
	    $class = '';
	}
	$nav_tabs .= '<a class="nav-tab' . $class . '" href="?post_type=sdm_downloads&page=' . $location . '">' . $tabname . '</a>';
    }
    $nav_tabs .= '</h2>';

    if ( isset( $_GET[ 'action' ] ) ) {
	switch ( $_GET[ 'action' ] ) {
	    case 'advanced-settings':
		sdm_admin_menu_advanced_settings();
		break;
	}
    } else {
	sdm_admin_menu_general_settings();
    }
    $settings_cont = ob_get_clean();
    echo $nav_tabs;
    ?>
    <div class="sdm-settings-cont">
        <div class="sdm-settings-grid sdm-main-cont">
    	<!-- settings page form -->
    	<form method="post" action="options.php">
		<?php echo $settings_cont; ?>
    	    <!-- End of settings page form -->
    	</form>
        </div>
        <div id="poststuff" class="sdm-settings-grid sdm-sidebar-cont">
    	<div class="postbox" style="min-width: inherit;">
    	    <h3 class="hndle"><label for="title"><?php _e( 'Plugin Documentation', 'simple-download-monitor' ); ?></label></h3>
    	    <div class="inside">
		    <?php echo sprintf( __( 'Please read the <a target="_blank" href="%s">Simple Download Monitor</a> plugin setup instructions and tutorials to learn how to configure and use it.', 'simple-download-monitor' ), 'https://simple-download-monitor.com/download-monitor-tutorials/' ); ?>
    	    </div>
    	</div>
    	<div class="postbox" style="min-width: inherit;">
    	    <h3 class="hndle"><label for="title"><?php _e( 'Add-ons', 'simple-download-monitor' ); ?></label></h3>
    	    <div class="inside">
		    <?php echo sprintf( __( 'Want additional functionality? Check out our <a target="_blank" href="%s">Add-Ons!</a>', 'simple-download-monitor' ), 'edit.php?post_type=sdm_downloads&page=sdm-addons' ); ?>
    	    </div>
    	</div>
    	<div class="postbox" style="min-width: inherit;">
    	    <h3 class="hndle"><label for="title"><?php _e( 'Rate Us', 'simple-download-monitor' ); ?></label></h3>
    	    <div class="inside">
		    <?php echo sprintf( __( 'Like the plugin? Please give us a <a href="%s" target="_blank">rating!</a>', 'simple-download-monitor' ), 'https://wordpress.org/support/plugin/simple-download-monitor/reviews/?filter=5' ); ?>
    		<div class="sdm-stars-container">
    		    <a href="https://wordpress.org/support/plugin/simple-download-monitor/reviews/?filter=5" target="_blank">
    			<span class="dashicons dashicons-star-filled"></span>
    			<span class="dashicons dashicons-star-filled"></span>
    			<span class="dashicons dashicons-star-filled"></span>
    			<span class="dashicons dashicons-star-filled"></span>
    			<span class="dashicons dashicons-star-filled"></span>
    		    </a>
    		</div>
    	    </div>
    	</div>
    	<div class="postbox" style="min-width: inherit;">
    	    <h3 class="hndle"><label for="title"><?php _e( 'Our Other Plugins', 'simple-download-monitor' ); ?></label></h3>
    	    <div class="inside">
		    <?php echo sprintf( __( 'Check out <a target="_blank" href="%s">our other plugins</a>', 'simple-download-monitor' ), 'https://www.tipsandtricks-hq.com/development-center' ); ?>
    	    </div>
    	</div>
    	<div class="postbox" style="min-width: inherit;">
    	    <h3 class="hndle"><label for="title"><?php _e( 'Social', 'simple-download-monitor' ); ?></label></h3>
    	    <div class="inside">
		    <?php echo sprintf( __( '<a target="_blank" href="%s">Facebook</a>', 'simple-download-monitor' ), 'https://www.facebook.com/Tips-and-Tricks-HQ-681802408532789/' ); ?> | 
		    <?php echo sprintf( __( '<a target="_blank" href="%s">Google Plus</a>', 'simple-download-monitor' ), 'https://plus.google.com/+Tipsandtricks-hq/' ); ?> | 
		    <?php echo sprintf( __( '<a target="_blank" href="%s">Twitter</a>', 'simple-download-monitor' ), 'https://twitter.com/TipsAndTricksHQ' ); ?>
    	    </div>
    	</div>
        </div>
    </div>

    <div style="background: none repeat scroll 0 0 #FFF6D5;border: 1px solid #D1B655;color: #3F2502;margin: 10px 0;padding: 5px 5px 5px 10px;text-shadow: 1px 1px #FFFFFF;">
        <p><?php _e( 'If you need a feature rich and supported plugin for selling your digital items then checkout our', 'simple-download-monitor' ); ?> <a href="https://www.tipsandtricks-hq.com/wordpress-estore-plugin-complete-solution-to-sell-digital-products-from-your-wordpress-blog-securely-1059" target="_blank"><?php _e( 'WP eStore Plugin', 'simple-download-monitor' ); ?></a>
        </p>
    </div>

    <?php
    echo '</div>'; //end of wrap
}

function sdm_admin_menu_general_settings() {
    ?>
    <!-- BEGIN GENERAL OPTIONS DIV -->
    <?php
    // This prints out all hidden setting fields
    do_settings_sections( 'general_options_section' );
    settings_fields( 'sdm_downloads_options' );

    submit_button();
    ?>
    <!-- END GENERAL OPTIONS DIV -->


    <!-- BEGIN ADMIN OPTIONS DIV -->
    <?php
    // This prints out all hidden setting fields
    do_settings_sections( 'admin_options_section' );
    settings_fields( 'sdm_downloads_options' );

    submit_button();
    ?>
    <!-- END ADMIN OPTIONS DIV -->

    <!-- BEGIN COLORS DIV -->
    <?php
    // This prints out all hidden setting fields
    do_settings_sections( 'sdm_colors_section' );
    settings_fields( 'sdm_downloads_options' );

    submit_button();
    ?>
    <!-- END COLORS OPTIONS DIV -->

    <!-- BEGIN DEBUG OPTIONS DIV -->
    <?php
    // This prints out all hidden setting fields
    do_settings_sections( 'sdm_debug_section' );
    settings_fields( 'sdm_downloads_options' );

    submit_button();
    ?>
    <!-- END DEBUG OPTIONS DIV -->
    <!-- BEGIN DELDATA OPTIONS DIV -->
    <?php
    // This prints out all hidden setting fields
    do_settings_sections( 'sdm_deldata_section' );
    settings_fields( 'sdm_downloads_options' );

    $deldataNonce = wp_create_nonce( 'sdm_delete_data' );
    ?>
    <!-- END DELDATA OPTIONS DIV -->

    <script>
        jQuery('button#sdmDeleteData').click(function (e) {
    	e.preventDefault();
    	jQuery(this).attr('disabled', 'disabled');
    	if (confirm("<?php echo __( "Are you sure want to delete all plugin's data and deactivate plugin?", 'simple-download-monitor' ); ?>")) {
    	    jQuery.post(ajaxurl,
    		    {'action': 'sdm_delete_data', 'nonce': '<?php echo $deldataNonce; ?>'},
    		    function (result) {
    			if (result === '1') {
    			    alert('<?php echo __( 'Data has been deleted and plugin deactivated. Click OK to go to Plugins page.', 'simple-download-monitor' ); ?>');
    			    jQuery(location).attr('href', '<?php echo get_admin_url() . 'plugins.php'; ?>');
    			    return true;
    			} else {
    			    alert('<?php echo __( 'Error occured.', 'simple-download-monitor' ); ?>');
    			}
    		    });
    	} else {
    	    jQuery(this).removeAttr('disabled');
    	}
        });
        jQuery('a#sdm-reset-log').click(function (e) {
    	e.preventDefault();
    	jQuery.post(ajaxurl,
    		{'action': 'sdm_reset_log'},
    		function (result) {
    		    if (result === '1') {
    			alert('Log has been reset.');
    		    }
    		});
        });
    </script>
    <?php
}

function sdm_admin_menu_advanced_settings() {
    //More advanced options will be added here in the future.

    // This prints out all hidden setting fields
    do_settings_sections( 'recaptcha_options_section' );
    settings_fields( 'recaptcha_options_section' );
    submit_button();

    do_settings_sections( 'termscond_options_section' );
    settings_fields( 'termscond_options_section' );
    submit_button();

    do_settings_sections( 'adsense_options_section' );
    settings_fields( 'adsense_options_section' );
    submit_button();
    
}

/*
 * * Logs menu page
 */

function sdm_create_logs_page() {
    global $wpdb;

    if ( isset( $_POST[ 'sdm_export_log_entries' ] ) ) {
	//Export log entries
	$log_file_url = sdm_export_download_logs_to_csv();
	echo '<div id="message" class="updated"><p>';
	_e( 'Log entries exported! Click on the following link to download the file.', 'simple-download-monitor' );
	echo '<br /><br /><a href="' . $log_file_url . '">' . __( 'Download Logs CSV File', 'simple-download-monitor' ) . '</a>';
	echo '</p></div>';
    }

    if ( isset( $_POST[ 'sdm_reset_log_entries' ] ) ) {
	//Reset log entries
	$table_name	 = $wpdb->prefix . 'sdm_downloads';
	$query		 = "TRUNCATE $table_name";
	$result		 = $wpdb->query( $query );
	echo '<div id="message" class="updated fade"><p>';
	_e( 'Download log entries deleted!', 'simple-download-monitor' );
	echo '</p></div>';
    }

    /* Display the logs table */
    //Create an instance of our package class...
    $sdmListTable = new sdm_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $sdmListTable->prepare_items();
    ?>
    <div class="wrap">

        <h2><?php _e( 'Download Logs', 'simple-download-monitor' ); ?></h2>

        <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
    	<p><?php _e( 'This page lists all tracked downloads.', 'simple-download-monitor' ); ?></p>
        </div>

        <div id="poststuff"><div id="post-body">

    	    <!-- Log export button -->
    	    <div class="postbox">
    		<h3 class="hndle"><label for="title"><?php _e( 'Export Download Log Entries', 'simple-download-monitor' ); ?></label></h3>
    		<div class="inside">
    		    <form method="post" action="" onSubmit="return confirm('Are you sure you want to export all the log entries?');" >
    			<div class="submit">
    			    <input type="submit" class="button" name="sdm_export_log_entries" value="<?php _e( 'Export Log Entries to CSV File', 'simple-download-monitor' ); ?>" />
    			</div>
    		    </form>
    		</div></div>

    	    <!-- Log reset button -->
    	    <div class="postbox">
    		<h3 class="hndle"><label for="title"><?php _e( 'Reset Download Log Entries', 'simple-download-monitor' ); ?></label></h3>
    		<div class="inside">
    		    <form method="post" action="" onSubmit="return confirm('Are you sure you want to reset all the log entries to a CSV file?');" >
    			<div class="submit">
    			    <input type="submit" class="button" name="sdm_reset_log_entries" value="<?php _e( 'Reset Log Entries', 'simple-download-monitor' ); ?>" />
    			</div>
    		    </form>
    		</div></div>

    	</div></div><!-- end of .poststuff and .post-body -->

        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="sdm_downloads-filter" method="post">
    	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST[ 'page' ] ) ?>" />
    	<!-- Now we can render the completed list table -->
	    <?php $sdmListTable->display() ?>
        </form>

    </div><!-- end of wrap -->
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
    	$('.fade').click(function () {
    	    $(this).fadeOut('slow');
    	});
        });
    </script>
    <?php
}

function sdm_create_stats_page() {

    $main_opts = get_option( 'sdm_downloads_options' );

    if ( isset( $main_opts[ 'admin_no_logs' ] ) ) {
	?>
	<div class="notice notice-warning"><p><b>Download Logs are disabled in <a href="?post_type=sdm_downloads&page=settings">plugin settings</a>. Please enable Download Logs to see current stats.</b></p></div>
	<?php
    }
    wp_enqueue_script( 'sdm_google_charts' );
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'sdm_jquery_ui_style' );

    if ( isset( $_POST[ 'sdm_stats_start_date' ] ) ) {
	$start_date = sanitize_text_field( $_POST[ 'sdm_stats_start_date' ] );
    } else {
	// default start date is 30 days back
	$start_date = date( 'Y-m-d', time() - 60 * 60 * 24 * 30 );
    }

    if ( isset( $_POST[ 'sdm_stats_end_date' ] ) ) {
	$end_date = sanitize_text_field( $_POST[ 'sdm_stats_end_date' ] );
    } else {
	$end_date = date( 'Y-m-d', time() );
    }
    if ( isset( $_REQUEST[ 'sdm_active_tab' ] ) && ! empty( $_REQUEST[ 'sdm_active_tab' ] ) ) {
	$active_tab = sanitize_text_field( $_REQUEST[ 'sdm_active_tab' ] );
    } else {
	$active_tab = 'datechart';
    }
    $downloads_by_date = sdm_get_downloads_by_date( $start_date, $end_date );

    $downloads_by_country = sdm_get_downloads_by_country( $start_date, $end_date );
    ?>
    <div class="wrap">
        <h2><?php _e( 'Stats', 'simple-download-monitor' ); ?></h2>
        <div id="poststuff"><div id="post-body">

    	    <div class="postbox">
    		<h3 class="hndle"><label for="title"><?php _e( 'Choose Date Range (yyyy-mm-dd)', 'simple-download-monitor' ); ?></label></h3>
    		<div class="inside">
    		    <form id="sdm_choose_date" method="post">
    			<input type="hidden" name="sdm_active_tab" value="<?php echo $active_tab; ?>">
			    <?php _e( 'Start Date: ', 'simple-download-monitor' ); ?><input type="text" class="datepicker" name="sdm_stats_start_date" value="<?php echo $start_date; ?>">
			    <?php _e( 'End Date: ', 'simple-download-monitor' ); ?><input type="text" class="datepicker" name="sdm_stats_end_date" value="<?php echo $end_date; ?>">
    			<p id="sdm_date_buttons">
    			    <button type="button" data-start-date="<?php echo date( 'Y-m-01' ); ?>" data-end-date="<?php echo date( 'Y-m-d' ); ?>"><?php _e( 'This Month', 'simple-download-monitor' ); ?></button>
    			    <button type="button" data-start-date="<?php echo date( 'Y-m-d', strtotime( 'first day of last month' ) ); ?>" data-end-date="<?php echo date( 'Y-m-d', strtotime( 'last day of last month' ) ); ?>"><?php _e( 'Last Month', 'simple-download-monitor' ); ?></button>
    			    <button button type="button" data-start-date="<?php echo date( 'Y-01-01' ); ?>" data-end-date="<?php echo date( 'Y-m-d' ); ?>"><?php _e( 'This Year', 'simple-download-monitor' ); ?></button>
    			    <button button type="button" data-start-date="<?php echo date( "Y-01-01", strtotime( "-1 year" ) ); ?>" data-end-date="<?php echo date( "Y-12-31", strtotime( 'last year' ) ); ?>"><?php _e( 'Last Year', 'simple-download-monitor' ); ?></button>
    			    <button button type="button" data-start-date="<?php echo "1970-01-01"; ?>" data-end-date="<?php echo date( 'Y-m-d' ); ?>"><?php _e( 'All Time', 'simple-download-monitor' ); ?></button>
    			</p>
    			<div class="submit">
    			    <input type="submit" class="button-primary" value="<?php _e( 'View Stats', 'simple-download-monitor' ); ?>">
    			</div>
    		    </form>
    		</div>
    	    </div>
    	    <div class="nav-tab-wrapper sdm-tabs">
    		<a href="edit.php?post_type=sdm_downloads&page=stats&sdm_active_tab=datechart" class="nav-tab<?php echo ($active_tab == 'datechart' ? ' nav-tab-active' : ''); ?>" data-tab-name="datechart"><?php _e( 'Downloads by date', 'simple-download-monitor' ); ?></a>
    		<a href="edit.php?post_type=sdm_downloads&page=stats&sdm_active_tab=geochart" href="" class="nav-tab<?php echo ($active_tab == 'geochart' ? ' nav-tab-active' : ''); ?>" data-tab-name="geochart"><?php _e( 'Downloads by country', 'simple-download-monitor' ); ?></a>
    	    </div>
    	    <div class="sdm-tabs-content-wrapper" style="height: 500px;margin-top: 10px;">
    		<div data-tab-name="datechart" class="sdm-tab"<?php echo ($active_tab == 'datechart' ? '' : ' style="display:none;"'); ?>>
    		    <div id="downloads_chart" style="width: 700px;"></div>
    		</div>
    		<div data-tab-name="geochart" class="sdm-tab"<?php echo ($active_tab == 'geochart' ? '' : ' style="display:none;"'); ?>>
    		    <div id="country_chart" style="width: 700px;height:437px;"></div>
    		</div>
    	    </div>
    	</div></div>
    </div>
    <script>
        var sdm = [];
        sdm.datechart = false;
        sdm.geochart = false;
        sdm.activeTab = '<?php echo $active_tab; ?>';
        jQuery('#sdm_date_buttons button').click(function (e) {
    	jQuery('#sdm_choose_date').find('input[name="sdm_stats_start_date"]').val(jQuery(this).attr('data-start-date'));
    	jQuery('#sdm_choose_date').find('input[name="sdm_stats_end_date"]').val(jQuery(this).attr('data-end-date'));
        });
        function sdm_init_chart(tab) {
    	if (!sdm.datechart && tab == 'datechart') {
    	    sdm.datechart = true;
    	    google.charts.load('current', {'packages': ['corechart']});
    	    google.charts.setOnLoadCallback(sdm_drawDateChart);
    	} else if (!sdm.geochart && tab == 'geochart') {
    	    sdm.geochart = true;
    	    google.charts.load('current', {'packages': ['geochart'], 'mapsApiKey': 'AIzaSyAjtHaEc8TX3JbzxWgjS96MiN7p7ePQilM'});
    	    google.charts.setOnLoadCallback(sdm_drawGeoChart);
    	}
        }
        function sdm_drawDateChart() {
    	var sdm_dateData = new google.visualization.DataTable();
    	sdm_dateData.addColumn('string', '<?php _e( 'Date', 'simple-download-monitor' ); ?>');
    	sdm_dateData.addColumn('number', '<?php _e( 'Number of downloads', 'simple-download-monitor' ); ?>');
    	sdm_dateData.addRows([<?php echo $downloads_by_date; ?>]);

    	var sdm_dateChart = new google.visualization.AreaChart(document.getElementById('downloads_chart'));
    	sdm_dateChart.draw(sdm_dateData, {width: 700, height: 300, title: '<?php _e( 'Downloads by Date', 'simple-download-monitor' ); ?>', colors: ['#3366CC', '#9AA2B4', '#FFE1C9'],
    	    hAxis: {title: 'Date', titleTextStyle: {color: 'black'}},
    	    vAxis: {title: 'Downloads', titleTextStyle: {color: 'black'}},
    	    legend: 'top',
    	});
        }
        function sdm_drawGeoChart() {

    	var sdm_countryData = google.visualization.arrayToDataTable([<?php echo $downloads_by_country; ?>]);

    	var sdm_countryOptions = {colorAxis: {colors: ['#ddf', '#00f']}};

    	var sdm_countryChart = new google.visualization.GeoChart(document.getElementById('country_chart'));

    	sdm_countryChart.draw(sdm_countryData, sdm_countryOptions);

        }
        jQuery(function () {
    	sdm_init_chart(sdm.activeTab);
    	jQuery('div.sdm-tabs a').click(function (e) {
    	    e.preventDefault();
    	    var tab = jQuery(this).attr('data-tab-name');
    	    jQuery('div.sdm-tabs').find('a').removeClass('nav-tab-active');
    	    jQuery(this).addClass('nav-tab-active');
    	    jQuery('div.sdm-tabs-content-wrapper').find('div.sdm-tab').hide();
    	    jQuery('div.sdm-tabs-content-wrapper').find('div[data-tab-name="' + tab + '"]').fadeIn('fast');
    	    sdm_init_chart(tab);
    	    jQuery('#sdm_choose_date').find('input[name="sdm_active_tab"]').val(tab);
    	});
    	jQuery('.datepicker').datepicker({
    	    dateFormat: 'yy-mm-dd'
    	});
        });
    </script>
    <?php
}

function sdm_create_addons_page() {
    include(WP_SIMPLE_DL_MONITOR_PATH . 'includes/admin-side/sdm-admin-add-ons-page.php');
}
