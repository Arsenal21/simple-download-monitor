<?php

function sdm_logs_export_tab_page()
{
    //    jQuery functions
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('sdm_jquery_ui_style');

    //  tab heading
    echo '<h2>';
    _e('Export Download Log Entries', 'simple-download-monitor');
    echo '</h2>';

    // datetime fileds
    if (isset($_POST['sdm_stats_start_date'])) {
        $start_date = sanitize_text_field($_POST['sdm_stats_start_date']);
    } else {
        // default start date is 30 days back
        $start_date = date('Y-m-d', time() - 60 * 60 * 24 * 30);
    }

    if (isset($_POST['sdm_stats_end_date'])) {
        $end_date = sanitize_text_field($_POST['sdm_stats_end_date']);
    } else {
        $end_date = date('Y-m-d', time());
    }

    // csv export message box
    if (isset($_POST['sdm_export_log_entries'])) {
        //Export log entries
        $log_file_url = sdm_export_download_logs_to_csv($start_date, $end_date);
        echo '<div id="message" class="updated"><p>';
        _e('Log entries exported! Click on the following link to download the file.', 'simple-download-monitor');
        echo '<br /><br /><a href="' . $log_file_url . '">' . __('Download Logs CSV File', 'simple-download-monitor') . '</a>';
        echo '</p></div>';
    }

    ?>

    <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
        <p><?php _e('This menu allows you to export all the log entries to a CSV file that you can download. The download link will be shown at the top of this page.', 'simple-download-monitor'); ?></p>
    </div>

    <div id="poststuff">
        <div id="post-body">
            <div class="postbox">
                <h3 class="hndle"><label
                            for="title"><?php _e('Choose Date Range (yyyy-mm-dd)', 'simple-download-monitor'); ?></label>
                </h3>
                <div class="inside">
                    <form id="sdm_choose_logs_date" method="post"
                          onSubmit="return confirm('Are you sure you want to export all the log entries?');">

                        <div class="sdm-row">
                            <div class="sdm-col s12 l6 sdm-row" style="margin-bottom: 10px">
                                <div class="sdm-col s12 m6 sdm-row" style="margin-bottom: 5px">
                                    <div class="sdm-col s4 m4 d-flex align-center" style="padding-top: .4rem">
                                        <label for="sdm_stats_start_date_input"><?php _e('Start Date: ', 'simple-download-monitor'); ?></label>
                                    </div>
                                    <div class="sdm-col s8 m7">
                                        <input type="text"
                                               id="sdm_stats_start_date_input"
                                               class="datepicker d-block w-100"
                                               name="sdm_stats_start_date"
                                               value="<?php echo $start_date; ?>">
                                    </div>
                                </div>
                                <div class="sdm-col s12 m6 sdm-row" style="margin-bottom: 5px">
                                    <div class="sdm-col s4 m5 l4 d-flex align-center" style="padding-top: .4rem">
                                        <label for="sdm_stats_end_date_input"><?php _e('End Date: ', 'simple-download-monitor'); ?></label>
                                    </div>
                                    <div class="sdm-col s8 m7">
                                        <input type="text"
                                               id="sdm_stats_end_date_input"
                                               class="datepicker d-block w-100"
                                               name="sdm_stats_end_date"
                                               value="<?php echo $end_date; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="sdm-col s12 l6 sdm-row" style="margin-bottom: 10px" id="sdm_logs_date_buttons">
                                <div class="sdm-col s4 m2 l2">
                                    <button id="aUnoqueId" class="button d-block w-100" type="button"
                                            data-start-date="<?php echo date('Y-m-d'); ?>"
                                            data-end-date="<?php echo date('Y-m-d'); ?>"><?php _e('Today', 'simple-download-monitor'); ?></button>
                                </div>
                                <div class="sdm-col s4 m2 l2">
                                    <button id="aUnoqueId" class="button d-block w-100" type="button"
                                            data-start-date="<?php echo date('Y-m-01'); ?>"
                                            data-end-date="<?php echo date('Y-m-d'); ?>"><?php _e('This Month', 'simple-download-monitor'); ?></button>
                                </div>
                                <div class="sdm-col s4 m2 l2">
                                    <button class="button d-block w-100" type="button"
                                            data-start-date="<?php echo date('Y-m-d', strtotime('first day of last month')); ?>"
                                            data-end-date="<?php echo date('Y-m-d', strtotime('last day of last month')); ?>"><?php _e('Last Month', 'simple-download-monitor'); ?></button>
                                </div>
                                <div class="sdm-col s4 m2 l2">
                                    <button class="button d-block w-100" type="button"
                                            data-start-date="<?php echo date('Y-01-01'); ?>"
                                            data-end-date="<?php echo date('Y-m-d'); ?>"><?php _e('This Year', 'simple-download-monitor'); ?></button>
                                </div>
                                <div class="sdm-col s4 m2 l2">
                                    <button class="button d-block w-100" type="button"
                                            data-start-date="<?php echo date("Y-01-01", strtotime("-1 year")); ?>"
                                            data-end-date="<?php echo date("Y-12-31", strtotime('last year')); ?>"><?php _e('Last Year', 'simple-download-monitor'); ?></button>
                                </div>
                                <div class="sdm-col s4 m2 l2">
                                    <button class="button d-block w-100" type="button"
                                            data-start-date="<?php echo "1970-01-01"; ?>"
                                            data-end-date="<?php echo date('Y-m-d'); ?>"><?php _e('All Time', 'simple-download-monitor'); ?></button>
                                </div>
                            </div>
                        </div>
                        <div class="submit">
                            <input type="submit" class="button-primary" name="sdm_export_log_entries"
                                   value="<?php _e('Export Log Entries to CSV File', 'simple-download-monitor'); ?>"/>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php } ?>

<!-- scripts -->
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