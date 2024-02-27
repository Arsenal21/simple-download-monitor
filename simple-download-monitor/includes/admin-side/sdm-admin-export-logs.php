<?php

function sdm_logs_export_tab_page() {
	//    jQuery functions
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_style( 'sdm_jquery_ui_style' );

	// datetime fileds
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

	?>

	<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
		<p><?php esc_html_e( 'This menu allows you to export all the log entries to a CSV file that you can download. The download link will be shown at the top of this page.', 'simple-download-monitor' ); ?></p>
	</div>

	<div id="poststuff">
		<div id="post-body">
			<div class="postbox">
				<h3 class="hndle"><label
							for="title"><?php esc_html_e( 'Choose Date Range (yyyy-mm-dd)', 'simple-download-monitor' ); ?></label>
				</h3>
				<div class="inside">
					<form id="sdm_choose_logs_date" method="post"
						onSubmit="return confirm('Are you sure you want to export all the log entries?');">
						<div>
							<label for="sdm_stats_start_date_input"><?php esc_html_e( 'Start Date: ', 'simple-download-monitor' ); ?></label>
							<input type="text"
								   id="sdm_stats_start_date_input"
								   class="datepicker d-block w-100"
								   name="sdm_stats_start_date"
								   value="<?php echo esc_attr( $start_date ); ?>">
							<label for="sdm_stats_end_date_input"><?php esc_html_e( 'End Date: ', 'simple-download-monitor' ); ?></label>
							<input type="text"
								   id="sdm_stats_end_date_input"
								   class="datepicker d-block w-100"
								   name="sdm_stats_end_date"
								   value="<?php echo esc_attr( $end_date ); ?>">
						</div>
						<br>
						<div id="sdm_logs_date_buttons">
							<button class="button" type="button"
									data-start-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'Today', 'simple-download-monitor' ); ?></button>
							<button class="button" type="button"
									data-start-date="<?php echo esc_attr( date( 'Y-m-01' ) ); ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'This Month', 'simple-download-monitor' ); ?></button>
							<button class="button" type="button"
									data-start-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( 'first day of last month' ) ) ); ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( 'last day of last month' ) ) ); ?>"><?php esc_html_e( 'Last Month', 'simple-download-monitor' ); ?></button>
							<button class="button" type="button"
									data-start-date="<?php echo esc_attr( date( 'Y-01-01' ) ); ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'This Year', 'simple-download-monitor' ); ?></button>
							<button class="button" type="button"
									data-start-date="<?php echo esc_attr( date( 'Y-01-01', strtotime( '-1 year' ) ) ); ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-12-31', strtotime( 'last year' ) ) ); ?>"><?php esc_html_e( 'Last Year', 'simple-download-monitor' ); ?></button>
							<button class="button" type="button"
									data-start-date="<?php echo '1970-01-01'; ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'All Time', 'simple-download-monitor' ); ?></button>
						</div>

						<div class="submit">
							<input type="submit" class="button-primary" name="sdm_export_log_entries"
								   value="<?php esc_html_e( 'Export Log Entries to CSV File', 'simple-download-monitor' ); ?>"/>
						</div>
						<?php wp_nonce_field( 'sdm_export_logs', 'sdm_export_logs_nonce' ); ?>
					</form>
				</div>
			</div>

		</div>
	</div>

	<?php
}

?>

<script>
	jQuery(document).ready(function () {
		jQuery('#sdm_logs_date_buttons button').click(function (e) {
			jQuery('#sdm_choose_logs_date').find('input[name="sdm_stats_start_date"]').val(jQuery(this).attr('data-start-date'));
			jQuery('#sdm_choose_logs_date').find('input[name="sdm_stats_end_date"]').val(jQuery(this).attr('data-end-date'));
		});

		jQuery('.datepicker').datepicker({
			dateFormat: 'yy-mm-dd'
		});
	});
</script>
