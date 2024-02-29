<?php
/*
 * Creates/adds the other admin menu page links to the main SDM custom post type menu
 */

 function sdm_handle_admin_menu() {
	$sdm_admin_access_permission =  get_sdm_admin_access_permission();
	$sdm_pages_capability = apply_filters("sdm_pages_capability", $sdm_admin_access_permission);

	// Create the 'logs' and 'settings' submenu pages
	add_submenu_page( 'edit.php?post_type=sdm_downloads', __( 'Logs', 'simple-download-monitor' ), __( 'Logs', 'simple-download-monitor' ), $sdm_pages_capability, 'sdm-logs', 'sdm_create_logs_page' );
	add_submenu_page( 'edit.php?post_type=sdm_downloads', __( 'Stats', 'simple-download-monitor' ), __( 'Stats', 'simple-download-monitor' ), $sdm_pages_capability, 'sdm-stats', 'sdm_create_stats_page' );
	add_submenu_page( 'edit.php?post_type=sdm_downloads', __( 'Settings', 'simple-download-monitor' ), __( 'Settings', 'simple-download-monitor' ), $sdm_pages_capability, 'sdm-settings', 'sdm_create_settings_page' );
	add_submenu_page( 'edit.php?post_type=sdm_downloads', __( 'Add-ons', 'simple-download-monitor' ), __( 'Add-ons', 'simple-download-monitor' ), $sdm_pages_capability, 'sdm-addons', 'sdm_create_addons_page' );
}

add_filter( 'allowed_options', 'sdm_admin_menu_function_hook' );

add_action( 'admin_enqueue_scripts', 'sdm_admin_menu_enqueue_scripts' );

function sdm_admin_menu_enqueue_scripts( $hook_suffix ) {
	switch ( $hook_suffix ) {
		case 'sdm_downloads_page_sdm-stats':
			wp_register_script( 'sdm-admin-stats', WP_SIMPLE_DL_MONITOR_URL . '/js/sdm_admin_stats.js', array( 'jquery' ), WP_SIMPLE_DL_MONITOR_VERSION, true );
			wp_enqueue_script( 'sdm-admin-stats' );
			break;
		default:
			break;
	}
}

/**
 * Its hook for add advanced tab, and working on saving options to db, if not used, you receive error "options page not found"
 *
 * @param array $allowed_options
 * @return string
 */
function sdm_admin_menu_function_hook( $allowed_options = array() ) {
	$allowed_options['recaptcha_options_section'] = array( 'sdm_advanced_options' );
	$allowed_options['termscond_options_section'] = array( 'sdm_advanced_options' );
	$allowed_options['adsense_options_section']   = array( 'sdm_advanced_options' );
	$allowed_options['maps_api_options_section']  = array( 'sdm_advanced_options' );

	return $allowed_options;
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
	<h1><?php esc_html_e( 'Simple Download Monitor Settings Page', 'simple-download-monitor' ); ?></h1>

	<?php
	$wpsdm_plugin_tabs = array(
		'sdm-settings'                          => __( 'General Settings', 'simple-download-monitor' ),
		'sdm-settings&action=advanced-settings' => __( 'Advanced Settings', 'simple-download-monitor' ),
	);
	$current           = '';
	if ( isset( $_GET['page'] ) ) {
                $current = isset( $_GET['page'] ) ? sanitize_text_field( stripslashes ( $_GET['page'] ) ) : '';
		if ( isset( $_GET['action'] ) ) {
                        $action = isset( $_GET['action'] ) ? sanitize_text_field( stripslashes ( $_GET['action'] ) ) : '';
			$current .= '&action=' . $action;
		}
	}
	$nav_tabs  = '';
	$nav_tabs .= '<h2 class="nav-tab-wrapper">';
	foreach ( $wpsdm_plugin_tabs as $location => $tabname ) {
		if ( $current === $location ) {
			$class = ' nav-tab-active';
		} else {
			$class = '';
		}
		$nav_tabs .= '<a class="nav-tab' . esc_attr( $class ) . '" href="?post_type=sdm_downloads&page=' . esc_attr( $location ) . '">' . esc_attr( $tabname ) . '</a>';
	}
	$nav_tabs .= '</h2>';

	echo wp_kses_post( $nav_tabs );
	?>
	<div class="sdm-settings-cont">
		<div class="sdm-settings-grid sdm-main-cont">
		<!-- settings page form -->
		<form method="post" action="options.php">
		<?php
		if ( isset( $_GET['action'] ) ) {
                        $action = isset( $_GET['action'] ) ? sanitize_text_field( stripslashes ( $_GET['action'] ) ) : '';
			switch ( $action ) {
				case 'advanced-settings':
					sdm_admin_menu_advanced_settings();
					break;
			}
		} else {
			sdm_admin_menu_general_settings();
		}
		?>
			<!-- End of settings page form -->
		</form>
		</div>
		<div id="poststuff" class="sdm-settings-grid sdm-sidebar-cont">
		<div class="postbox" style="min-width: inherit;">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Plugin Documentation', 'simple-download-monitor' ); ?></label></h3>
			<div class="inside">
			<?php
			echo wp_kses(
				// translators: %s = URL to documentation page
				sprintf( __( 'Please read the <a target="_blank" href="%s">Simple Download Monitor</a> plugin setup instructions and tutorials to learn how to configure and use it.', 'simple-download-monitor' ), 'https://simple-download-monitor.com/download-monitor-tutorials/' ),
				array(
					'a' => array(
						'target' => array(),
						'href'   => array(),
					),
				)
			);
			?>
			</div>
		</div>
		<div class="postbox" style="min-width: inherit;">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Add-ons', 'simple-download-monitor' ); ?></label></h3>
			<div class="inside">
			<?php
			echo wp_kses(
				// translators: %s = URL to add-ons page
				sprintf( __( 'Want additional functionality? Check out our <a target="_blank" href="%s">Add-Ons!</a>', 'simple-download-monitor' ), 'edit.php?post_type=sdm_downloads&page=sdm-addons' ),
				array(
					'a' => array(
						'target' => array(),
						'href'   => array(),
					),
				)
			);
			?>
			</div>
		</div>
		<div class="postbox" style="min-width: inherit;">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Help Us Keep the Plugin Free & Maintained', 'simple-download-monitor' ); ?></label></h3>
			<div class="inside">
			<?php
			echo wp_kses(
				// translators: %s = URL to rating page
				sprintf( __( 'Like the plugin? Please give it a good <a href="%s" target="_blank">rating!</a>', 'simple-download-monitor' ), 'https://wordpress.org/support/plugin/simple-download-monitor/reviews/?filter=5' ),
				array(
					'a' => array(
						'target' => array(),
						'href'   => array(),
					),
				)
			);
			?>
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
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Our Other Plugins', 'simple-download-monitor' ); ?></label></h3>
			<div class="inside">
			<?php
			echo wp_kses(
				// translators: %s = URL to other plugins page
				sprintf( __( 'Check out <a target="_blank" href="%s">our other plugins</a>', 'simple-download-monitor' ), 'https://www.tipsandtricks-hq.com/development-center' ),
				array(
					'a' => array(
						'target' => array(),
						'href'   => array(),
					),
				)
			);
			?>
			</div>
		</div>
		<div class="postbox" style="min-width: inherit;">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Want to Sell Digital Downloads?', 'simple-download-monitor' ); ?></label></h3>
			<div class="inside">
			<?php
                        _e( 'Check out the fast and simple ', 'simple-download-monitor' );
			echo wp_kses(
				// translators: %s = Twitter URL
				sprintf( __( '<a target="_blank" href="%s">WP Express Checkout</a> plugin.', 'simple-download-monitor' ), 'https://wordpress.org/plugins/wp-express-checkout/' ),
				array(
					'a' => array(
						'target' => array(),
						'href'   => array(),
					),
				)
			);
			?>
			</div>
		</div>
		</div>
	</div>

	<div style="background: none repeat scroll 0 0 #FFF6D5;border: 1px solid #D1B655;color: #3F2502;margin: 10px 0;padding: 5px 5px 5px 10px;text-shadow: 1px 1px #FFFFFF;">
		<p>
			<?php esc_html_e( 'If you need an easy to use and supported plugin for selling your digital items then check out our ', 'simple-download-monitor' ); ?>
			<a href="https://wordpress.org/plugins/wp-express-checkout/" target="_blank"><?php esc_html_e( 'WP Express Checkout', 'simple-download-monitor' ); ?></a>
			or <a href="https://wordpress.org/plugins/stripe-payments/" target="_blank"><?php esc_html_e( 'Stripe Payments', 'simple-download-monitor' ); ?></a>
			or <a href="https://www.tipsandtricks-hq.com/wordpress-estore-plugin-complete-solution-to-sell-digital-products-from-your-wordpress-blog-securely-1059" target="_blank"><?php esc_html_e( 'WP eStore', 'simple-download-monitor' ); ?></a> Plugin.
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

	<!-- BEGIN USER LOGIN OPTIONS DIV -->
	<?php
	// This prints out all hidden setting fields
	do_settings_sections( 'user_login_options_section' );
	settings_fields( 'sdm_downloads_options' );

	submit_button();
	?>
	<!-- END USER LOGIN OPTIONS DIV -->

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
		if (confirm("<?php echo esc_js( __( "Are you sure want to delete all plugin's data and deactivate plugin?", 'simple-download-monitor' ) ); ?>")) {
			jQuery.post(ajaxurl,
				{'action': 'sdm_delete_data', 'nonce': '<?php echo esc_js( $deldataNonce ); ?>'},
				function (result) {
				if (result === '1') {
					alert('<?php echo esc_js( __( 'Data has been deleted and plugin deactivated. Click OK to go to Plugins page.', 'simple-download-monitor' ) ); ?>');
					jQuery(location).attr('href', '<?php echo esc_js( get_admin_url() . 'plugins.php' ); ?>');
					return true;
				} else {
					alert('<?php echo esc_js( __( 'Error occurred.', 'simple-download-monitor' ) ); ?>');
				}
				});
		} else {
			jQuery(this).removeAttr('disabled');
		}
		});
		jQuery('a#sdm-reset-log').click(function (e) {
		e.preventDefault();
		jQuery.post(ajaxurl,
			{'action': 'sdm_reset_log', 'nonce': '<?php echo esc_js( $deldataNonce ); ?>'},
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

	do_settings_sections( 'maps_api_options_section' );
	settings_fields( 'maps_api_options_section' );
	submit_button();
}

/**
 * Logs menu page
 */
function sdm_create_logs_page() {
	$dashboard_access_role = get_sdm_admin_access_permission();
	if ( ! current_user_can( $dashboard_access_role ) ) {
		wp_die( 'You do not have permission to access this settings page.' );
	}

	echo '<div class="wrap">';

	$sdm_logs_menu_tabs = array(
		'sdm-logs' => array(
			'name' => __( 'Main Logs', 'simple-download-monitor' ),
			'title' =>__( 'Download Logs', 'simple-download-monitor' ),
		),
		'sdm-logs-by-download' => array(
			'name' => __( 'Specific Item Logs', 'simple-download-monitor' ),
			'title' =>__( 'Specific Download Item Logs', 'simple-download-monitor' ),
		),
		'sdm-logs-export' => array(
			'name' =>  __( 'Export', 'simple-download-monitor' ),
			'title' =>__( 'Export Download Log Entries', 'simple-download-monitor' ),
		),
	);
	
	$current = 'sdm-logs';
	if ( isset( $_GET['page'] ) && isset( $_GET['tab'] ) ) {
		$current = sanitize_text_field( $_GET['tab'] );
	}

	$content = '';
	foreach ( $sdm_logs_menu_tabs as $tab_slug => $tab ) {
		$tab_query = '&tab=' . $tab_slug;
		if ( $current === $tab_slug ) {
			$class = ' nav-tab-active';
		} else {
			$class = '';
		}
		$content .= '<a class="nav-tab' . $class . '" href="?post_type=sdm_downloads&page=sdm-logs' . $tab_query . '">' . $tab['name'] . '</a>';
	}

	echo "<h2>" . esc_html__( $sdm_logs_menu_tabs[$current]['title'], 'simple-download-monitor' )."</h2>";

	echo '<h2 class="nav-tab-wrapper">';
	echo wp_kses(
		$content,
		array(
			'a' => array(
				'href'  => array(),
				'class' => array(),
			),
		)
	);
	echo '</h2>';

	if ( isset( $_GET['tab'] ) ) {
		switch ( $_GET['tab'] ) {
			case 'sdm-logs-by-download':
				include_once WP_SIMPLE_DL_MONITOR_PATH . 'includes/admin-side/sdm-admin-individual-item-logs-page.php';
				sdm_handle_individual_logs_tab_page();
				break;
			case 'sdm-logs-export':
				include_once WP_SIMPLE_DL_MONITOR_PATH . 'includes/admin-side/sdm-admin-export-logs.php';
				sdm_logs_export_tab_page();
				break;
			default:
				sdm_handle_logs_main_tab_page();
				break;
		}
	} else {
		sdm_handle_logs_main_tab_page();
	}

	echo '</div>'; //<!-- end of wrap -->
}

function sdm_handle_logs_main_tab_page() {
	global $wpdb;
	$advanced_options = get_option( 'sdm_advanced_options' );

	if ( isset( $_POST['sdm_reset_log_entries'] ) && check_admin_referer( null, 'sdm_delete_all_logs_nonce' ) ) {
		//Reset log entries
		$table_name = $wpdb->prefix . 'sdm_downloads';
		$query      = "TRUNCATE $table_name";
		$result     = $wpdb->query( $query );
		echo '<div id="message" class="updated fade"><p>';
		esc_html_e( 'Download log entries deleted!', 'simple-download-monitor' );
		echo '</p></div>';
	}

	if ( isset( $_POST['sdm_trim_log_entries'] ) && check_admin_referer( null, 'sdm_delete_logs_nonce' ) ) {
		//Trim log entries
		$interval_val  = intval( $_POST['sdm_trim_log_entries_days'] );
		$interval_unit = 'DAY';
		$cur_time      = current_time( 'mysql' );

		//Save the interval value for future use on this site.
		$advanced_options ['sdm_trim_log_entries_days_saved'] = $interval_val;
		update_option( 'sdm_advanced_options', $advanced_options );

		//Trim entries in the DB table.
		$table_name = $wpdb->prefix . 'sdm_downloads';
		$cond       = " DATE_SUB('$cur_time',INTERVAL '$interval_val' $interval_unit) > date_time";
		$result     = $wpdb->query( "DELETE FROM $table_name WHERE $cond", OBJECT );

		echo '<div id="message" class="updated fade"><p>';
		esc_html_e( 'Download log entries trimmed!', 'simple-download-monitor' );
		echo '</p></div>';
	}

	//Set the default log trim days value
	$trim_log_entries_days_default_val = isset( $advanced_options ['sdm_trim_log_entries_days_saved'] ) ? $advanced_options ['sdm_trim_log_entries_days_saved'] : '30';

	/* Display the logs table */
	//Create an instance of our package class...
	$sdmListTable = new sdm_List_Table();
	//Fetch, prepare, sort, and filter our data...
	$sdmListTable->prepare_items();
	?>

	<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
		<p><?php esc_html_e( 'This page lists all tracked downloads.', 'simple-download-monitor' ); ?></p>
	</div>

	<div id="poststuff"><div id="post-body">

		<!-- Log reset button -->
		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Reset Download Log Entries', 'simple-download-monitor' ); ?></label></h3>
			<div class="inside">
			<form method="post" action="" onSubmit="return confirm('Are you sure you want to reset all the log entries?');" >
				<div class="submit">
				<input type="submit" class="button" name="sdm_reset_log_entries" value="<?php esc_html_e( 'Reset Log Entries', 'simple-download-monitor' ); ?>" />
						<p class="description"><?php esc_html_e( 'This button will reset all log entries. It can useful if you want to export all your log entries then reset them.', 'simple-download-monitor' ); ?></p>
				</div>
				<?php wp_nonce_field( null, 'sdm_delete_all_logs_nonce' ); ?>
			</form>

			<form method="post" action="" onSubmit="return confirm('Are you sure you want to trim log entries?');" >
				<div class="submit">
						<?php esc_html_e( 'Delete Log Entries Older Than ', 'simple-download-monitor' ); ?><input name="sdm_trim_log_entries_days" type="text" size="4" value="<?php echo esc_attr( $trim_log_entries_days_default_val ); ?>"/><?php esc_html_e( ' Days', 'simple-download-monitor' ); ?>
				<input type="submit" class="button" name="sdm_trim_log_entries" value="<?php esc_html_e( 'Trim Log Entries', 'simple-download-monitor' ); ?>" />
						<p class="description"><?php esc_html_e( 'This option can be useful if you want to delete older log entries. Enter a number of days value then click the Trim Log Entries button.', 'simple-download-monitor' ); ?></p>
				</div>
				<?php wp_nonce_field( null, 'sdm_delete_logs_nonce' ); ?>
			</form>
			</div>
		</div>

		</div></div><!-- end of .poststuff and .post-body -->

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="sdm_downloads-filter" method="post">
		<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
		<!-- Now we can render the completed list table -->
	<?php $sdmListTable->display(); ?>
	</form>

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

	if ( isset( $main_opts['admin_no_logs'] ) ) {
		?>
	<div class="notice notice-warning"><p><b>Download Logs are disabled in <a href="?post_type=sdm_downloads&page=settings">plugin settings</a>. Please enable Download Logs to see current stats.</b></p></div>
		<?php
	}
	wp_enqueue_script( 'sdm_google_charts' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_style( 'sdm_jquery_ui_style' );

	if ( isset( $_POST['sdm_stats_start_date'] ) ) {
		$start_date = sanitize_text_field( $_POST['sdm_stats_start_date'] );
	} else {
		// default start date is 30 days back
		$start_date = date( 'Y-m-d', time() - 60 * 60 * 24 * 30 );
	}

	if ( isset( $_POST['sdm_stats_end_date'] ) ) {
		$end_date = sanitize_text_field( $_POST['sdm_stats_end_date'] );
	} else {
		$end_date = date( 'Y-m-d', time() );
	}
	if ( isset( $_REQUEST['sdm_active_tab'] ) && ! empty( $_REQUEST['sdm_active_tab'] ) ) {
		$active_tab = sanitize_text_field( $_REQUEST['sdm_active_tab'] );
	} else {
		$active_tab = 'datechart';
	}
	$downloads_by_date = sdm_get_downloads_by_date( $start_date, $end_date, false );

	$downloads_by_country = sdm_get_downloads_by_country( $start_date, $end_date, false );

	$adv_opts = get_option( 'sdm_advanced_options' );

	$api_key = '';
	if ( isset( $adv_opts['maps_api_key'] ) ) {
		$api_key = $adv_opts['maps_api_key'];
	}
	?>
	<style>
		#sdm-api-key-warning {
		padding: 5px 0;
		width: auto;
		margin: 5px 0;
		display: none;
		}
	</style>
	<div class="wrap">
		<h2><?php esc_html_e( 'Stats', 'simple-download-monitor' ); ?></h2>
		<div id="poststuff"><div id="post-body">

			<div class="postbox">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'Choose Date Range (yyyy-mm-dd)', 'simple-download-monitor' ); ?></label></h3>
			<div class="inside">
				<form id="sdm_choose_date" method="post">
				<input type="hidden" name="sdm_active_tab" value="<?php echo esc_attr( sdm_sanitize_text( $active_tab ) ); ?>">
				<?php esc_html_e( 'Start Date: ', 'simple-download-monitor' ); ?><input type="text" class="datepicker" name="sdm_stats_start_date" value="<?php echo esc_attr( sdm_sanitize_text( $start_date ) ); ?>">
				<?php esc_html_e( 'End Date: ', 'simple-download-monitor' ); ?><input type="text" class="datepicker" name="sdm_stats_end_date" value="<?php echo esc_attr( sdm_sanitize_text( $start_date ) ); ?>">
				<p id="sdm_date_buttons">
					<button type="button" data-start-date="<?php echo esc_attr( date( 'Y-m-01' ) ); ?>" data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'This Month', 'simple-download-monitor' ); ?></button>
					<button type="button" data-start-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( 'first day of last month' ) ) ); ?>" data-end-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( 'last day of last month' ) ) ); ?>"><?php esc_html_e( 'Last Month', 'simple-download-monitor' ); ?></button>
					<button button type="button" data-start-date="<?php echo esc_attr( date( 'Y-01-01' ) ); ?>" data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'This Year', 'simple-download-monitor' ); ?></button>
					<button button type="button" data-start-date="<?php echo esc_attr( date( 'Y-01-01', strtotime( '-1 year' ) ) ); ?>" data-end-date="<?php echo esc_attr( date( 'Y-12-31', strtotime( 'last year' ) ) ); ?>"><?php esc_html_e( 'Last Year', 'simple-download-monitor' ); ?></button>
					<button button type="button" data-start-date="<?php echo '1970-01-01'; ?>" data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'All Time', 'simple-download-monitor' ); ?></button>
				</p>
				<div class="submit">
					<input type="submit" class="button-primary" value="<?php esc_html_e( 'View Stats', 'simple-download-monitor' ); ?>">
				</div>
				</form>
			</div>
			</div>
			<div class="nav-tab-wrapper sdm-tabs">
			<a href="edit.php?post_type=sdm_downloads&page=sdm-stats&sdm_active_tab=datechart" class="nav-tab<?php echo ( $active_tab === 'datechart' ? ' nav-tab-active' : '' ); ?>" data-tab-name="datechart"><?php esc_html_e( 'Downloads by date', 'simple-download-monitor' ); ?></a>
			<a href="edit.php?post_type=sdm_downloads&page=stats&sdm_active_tab=geochart" href="" class="nav-tab<?php echo ( $active_tab === 'geochart' ? ' nav-tab-active' : '' ); ?>" data-tab-name="geochart"><?php esc_html_e( 'Downloads by country', 'simple-download-monitor' ); ?></a>
				<a href="edit.php?post_type=sdm_downloads&page=stats&sdm_active_tab=countrylistchart" href="" class="nav-tab<?php echo ( $active_tab === 'countrylistchart' ? ' nav-tab-active' : '' ); ?>" data-tab-name="countrylistchart"><?php esc_html_e( 'Downloads by country list', 'simple-download-monitor' ); ?></a>
			<a href="edit.php?post_type=sdm_downloads&page=stats&sdm_active_tab=browserList" href="" class="nav-tab<?php echo ( $active_tab === 'browserList' ? ' nav-tab-active' : '' ); ?>" data-tab-name="browserList"><?php esc_html_e( 'Downloads by browser', 'simple-download-monitor' ); ?></a>
			<a href="edit.php?post_type=sdm_downloads&page=stats&sdm_active_tab=osList" href="" class="nav-tab<?php echo ( $active_tab === 'osList' ? ' nav-tab-active' : '' ); ?>" data-tab-name="osList"><?php esc_html_e( 'Downloads by OS', 'simple-download-monitor' ); ?></a>
			<a href="edit.php?post_type=sdm_downloads&page=stats&sdm_active_tab=userList" href="" class="nav-tab<?php echo ( $active_tab === 'userList' ? ' nav-tab-active' : '' ); ?>" data-tab-name="userList"><?php esc_html_e( 'Downloads by User', 'simple-download-monitor' ); ?></a>
			<a href="edit.php?post_type=sdm_downloads&page=stats&sdm_active_tab=topDownloads" href="" class="nav-tab<?php echo ( $active_tab === 'topDownloads' ? ' nav-tab-active' : '' ); ?>" data-tab-name="topDownloads"><?php esc_html_e( 'Top Downloads', 'simple-download-monitor' ); ?></a>
			</div>
			<div class="sdm-tabs-content-wrapper" style="height: 500px;margin-top: 10px;">
			<div data-tab-name="datechart" class="sdm-tab"<?php echo ( $active_tab === 'datechart' ? '' : ' style="display:none;"' ); ?>>
				<div id="downloads_chart" style="width: auto; max-width: 700px"></div>
			</div>
			<div data-tab-name="geochart" class="sdm-tab"<?php echo ( $active_tab === 'geochart' ? '' : ' style="display:none;"' ); ?>>
					<div id="sdm-api-key-warning">
						<div class="sdm_yellow_box">
							<span class="dashicons dashicons-warning" style="color: #ffae42;"></span>
								<?php
								echo wp_kses(
									__( 'Enter your Google Maps API Key <a href="edit.php?post_type=sdm_downloads&page=sdm-settings&action=advanced-settings#maps_api_key" target="_blank">in the settings</a> to properly display the chart.', 'simple-download-monitor' ),
									array(
										'a' => array(
											'target' => array(),
											'href'   => array(),
										),
									)
								);
								?>
						</div>
					</div>

				<div id="country_chart" style="width: auto; max-width: 700px; height:437px;"></div>
			</div>

				<div data-tab-name="countrylistchart" class="sdm-tab"<?php echo ( $active_tab === 'countrylistchart' ? '' : ' style="display:none;"' ); ?>>
					<div class="wrap">
						<table class="widefat">
							<thead>
							<th><strong><?php esc_html_e( 'Country Name', 'simple-download-monitor' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'simple-download-monitor' ); ?></strong></th>
							</thead>
							<tbody>
								<?php
								//An array containing the downloads.
								$downloads_by_country_array = sdm_get_downloads_by_country( $start_date, $end_date, false );
								foreach ( $downloads_by_country_array as $item ) {
									if ( empty( $item['country'] ) ) {
										//Lets skip any unknown country rows
										continue;
									}
									echo '<tr>';
									echo '<td>' . esc_html( $item['country'] ) . '</td>';
									echo '<td>' . esc_html( $item['cnt'] ) . '</td>';
									echo '</tr>';
								}
								?>
							</tbody>
							<tfoot>
							<th><strong><?php esc_html_e( 'Country Name', 'simple-download-monitor' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'simple-download-monitor' ); ?></strong></th>
							</tfoot>
						</table>
					</div>
				</div><!-- end of countrylistchart -->

				<div data-tab-name="browserList"
					 class="sdm-tab"<?php echo( $active_tab === 'browserList' ? '' : ' style="display:none;"' ); ?>>
					<div class="wrap">
						<table class="widefat">
							<thead>
							<th><strong><?php esc_html_e( 'Browser', 'simple-download-monitor' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'simple-download-monitor' ); ?></strong></th>
							</thead>
							<tbody>
							<?php
							$downloads_by_browser_array = sdm_get_all_downloads_by_browser( $start_date, $end_date );
							foreach ( $downloads_by_browser_array as $name => $count ) {
								?>
								<tr>
									<td><?php echo esc_html( $name ); ?></td>
									<td><?php echo esc_html( $count ); ?></td>
								</tr>
							<?php } ?>
							</tbody>
							<tfoot>
							<th><strong><?php esc_html_e( 'Browser', 'simple-download-monitor' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'simple-download-monitor' ); ?></strong></th>
							</tfoot>
						</table>
					</div>
				</div><!-- end of browserList tab-->

				<div data-tab-name="osList"
					 class="sdm-tab"<?php echo( $active_tab === 'osList' ? '' : ' style="display:none;"' ); ?>>
					<div class="wrap">
						<table class="widefat">
							<thead>
							<th><strong><?php esc_html_e( 'Operating System', 'simple-download-monitor' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'simple-download-monitor' ); ?></strong></th>
							</thead>
							<tbody>
							<?php
							$downloads_by_os_array = sdm_get_all_downloads_by_os( $start_date, $end_date );
							foreach ( $downloads_by_os_array as $name => $count ) {
								?>
								<tr>
									<td><?php echo esc_html( $name ); ?></td>
									<td><?php echo esc_html( $count ); ?></td>
								</tr>
							<?php } ?>
							</tbody>
							<tfoot>
							<th><strong><?php esc_html_e( 'Operating System', 'simple-download-monitor' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'simple-download-monitor' ); ?></strong></th>
							</tfoot>
						</table>
					</div>
				</div><!-- end of osList tab-->
 
				<div data-tab-name="userList" class="sdm-tab"<?php echo( $active_tab === 'userList' ? '' : ' style="display:none;"' ); ?>>
					<div class="wrap">
						<table class="widefat">
							<thead>
							<th><strong><?php _e( 'User', 'simple-download-monitor' ); ?></strong></th>
							<th><strong><?php _e( 'Total Downloads', 'simple-download-monitor' ); ?></strong></th>
							</thead>
							<tbody>
							<?php
							$downloads_by_count = sdm_get_top_users_by_download_count( $start_date, $end_date, 25 );
							foreach ( $downloads_by_count as $item ) {
								?>
								<tr>
									<td><?php echo esc_html( $item['visitor_name'] ); ?></td>
									<td><?php echo esc_html( $item['cnt'] ); ?></td>
								</tr>
							<?php } ?>
							</tbody>
							<tfoot>
							<th><strong><?php _e( 'User', 'simple-download-monitor' ); ?></strong></th>
							<th><strong><?php _e( 'Total Downloads', 'simple-download-monitor' ); ?></strong></th>
							</tfoot>
						</table>
					</div>
				</div><!-- end of top userList tab-->

				<div data-tab-name="topDownloads"
					 class="sdm-tab"<?php echo( $active_tab === 'topDownloads' ? '' : ' style="display:none;"' ); ?>>
					<div class="wrap">
						<table class="widefat">
							<thead>
							<th><strong><?php esc_html_e( 'Download Item', 'simple-download-monitor' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'simple-download-monitor' ); ?></strong></th>
							</thead>
							<tbody>
							<?php
							$downloads_by_count = sdm_get_top_downloads_by_count( $start_date, $end_date, 15 );
							foreach ( $downloads_by_count as $item ) {
								?>
								<tr>
									<td><?php echo esc_html( $item['post_title'] ); ?></td>
									<td><?php echo esc_html( $item['cnt'] ); ?></td>
								</tr>
							<?php } ?>
							</tbody>
							<tfoot>
							<th><strong><?php esc_html_e( 'Download Item', 'simple-download-monitor' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Total Downloads', 'simple-download-monitor' ); ?></strong></th>
							</tfoot>
						</table>
					</div>
				</div><!-- end of top downloads tab-->

			</div>
		</div></div>
	</div>

	<?php

	$dbd_prop = array();

	foreach ( $downloads_by_date as $dbd ) {
		$dbd_prop[] = array( $dbd['day'], intval( $dbd['cnt'] ) );
	}

	$dbc_prop = array();

	$dbc_prop[] = array( __( 'Country', 'simple-download-monitor' ), __( 'Downloads', 'simple-download-monitor' ) );

	foreach ( $downloads_by_country as $dbc ) {
		$dbc_prop[] = array( $dbc['country'], intval( $dbc['cnt'] ) );
	}

		wp_localize_script(
			'sdm-admin-stats',
			'sdmAdminStats',
			array(
				'activeTab'  => $active_tab,
				'apiKey'     => $api_key,
				'dByDate'    => $dbd_prop,
				'dByCountry' => $dbc_prop,
				'str'        => array(
					'downloadsByDate'   => __( 'Downloads by Date', 'simple-download-monitor' ),
					'date'              => __( 'Date', 'simple-download-monitor' ),
					'numberOfDownloads' => __( 'Number of downloads', 'simple-download-monitor' ),
					'downloads'         => __( 'Downloads', 'single-download-monitor' ),
				),
			)
		);
}

function sdm_create_addons_page() {
	include WP_SIMPLE_DL_MONITOR_PATH . 'includes/admin-side/sdm-admin-add-ons-page.php';
}
