<?php

function sdm_handle_individual_logs_tab_page() {

	$sdm_logs_dl_id = isset( $_REQUEST['sdm_logs_dl_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sdm_logs_dl_id'] ) ) : '';
	$sdm_logs_dl_id = intval( $sdm_logs_dl_id );

	if ( isset( $_REQUEST['sdm_show_specific_item_logs'] ) ) {
		$sdm_specific_download_id = isset( $_REQUEST['sdm_specific_download_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sdm_specific_download_id'] ) ) : '';
		$sdm_specific_download_id = intval( $sdm_specific_download_id );

		if ( ! empty( $sdm_specific_download_id ) ) {
			check_admin_referer( 'sdm_view_specific_download_id_log' );
			$target_url = 'edit.php?post_type=sdm_downloads&page=sdm-logs&tab=sdm-logs-by-download&sdm_logs_dl_id=' . $sdm_specific_download_id;
			
			$view_log_sdm_nonce = wp_create_nonce('sdm_view_log_nonce');
			$nonced_new_url = add_query_arg ( '_wpnonce', $view_log_sdm_nonce, $target_url);
			sdm_redirect_to_url($nonced_new_url);
			exit;
		}
	}

	?>

	<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
	<p><?php esc_html_e( 'This menu allows you to view the download logs of individual items.', 'simple-download-monitor' ); ?></p>
	</div>

	<div id="poststuff"><div id="post-body">

		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php esc_html_e( 'View Specific Item Logs', 'simple-download-monitor' ); ?></label></h3>
			<div class="inside">
				<form method="post" action="" >
					<?php wp_nonce_field( 'sdm_view_specific_download_id_log' ); ?>
					<?php esc_html_e( 'Enter the Download ID of the item: ', 'simple-download-monitor' ); ?>
					<input type="text" name="sdm_specific_download_id" value="<?php echo esc_attr( $sdm_logs_dl_id ); ?>" size="10" />
					<p class='description'>
						<?php esc_html_e( 'You can find the Download ID of an item from the Downloads menu of the plugin.', 'simple-download-monitor' ); ?>
					</p>
					<div class="submit">
						<input type="submit" class="button" name="sdm_show_specific_item_logs" value="<?php esc_html_e( 'View Logs', 'simple-download-monitor' ); ?>" />
					</div>
				</form>
			</div>
		</div>

	</div></div><!-- end of .poststuff and .post-body -->

	<?php
	if ( isset( $sdm_logs_dl_id ) && ! empty( $sdm_logs_dl_id ) ) {
		//Show the specific item logs

		/**
		 * Check if the current request is a post request by WP_List_Table, if so, skip the nonce check. Otherwise the code will not proceed due a nonce collision. 
		 * Otherwise do the nonce check for showing specific items log.
		 * 
		 * The nonce check for WP_List_Table related operations will take place in the sdm_List_Table class.
		 */
		if (!isset($_POST['action'])) {
			check_admin_referer( 'sdm_view_log_nonce' );
		}

		$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';

		/* Prepare everything for the specific logs table */
		//Create an instance of our package class...
		$sdm_list_table = new sdm_List_Table();
		//Fetch, prepare, sort, and filter our data...
		$sdm_list_table->prepare_items();
		echo '<strong>' . esc_html__( 'The following table shows the download logs of the item with Download ID: ', 'simple-download-monitor' ) . esc_html( $sdm_logs_dl_id ) . '</strong>'
		?>
			<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
			<form id="sdm_downloads-filter" method="post">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>" />
			<!-- Now we can render the completed list table -->
				<?php $sdm_list_table->display(); ?>
			</form>
		<?php
	}

}
