<?php

function sdm_handle_individual_logs_tab_page(){
    echo '<h2>';
    _e( 'Specific Download Item Logs', 'simple-download-monitor' );
    echo '</h2>';

    $sdm_logs_dl_id = isset($_REQUEST['sdm_logs_dl_id'])? sanitize_text_field($_REQUEST['sdm_logs_dl_id']): '';
    $sdm_logs_dl_id = intval($sdm_logs_dl_id);

    if(isset($_REQUEST['sdm_show_specific_item_logs'])){
        $sdm_specific_download_id = isset($_REQUEST['sdm_specific_download_id'])? sanitize_text_field($_REQUEST['sdm_specific_download_id']): '';
        $sdm_specific_download_id = intval($sdm_specific_download_id);
        
        if(!empty($sdm_specific_download_id)){
            $target_url = 'edit.php?post_type=sdm_downloads&page=sdm-logs&action=sdm-logs-by-download&sdm_logs_dl_id='.$sdm_specific_download_id;
            sdm_redirect_to_url( $target_url );
            exit;
        }
    }

    ?>

    <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
    <p><?php _e( 'This menu allows you to view the download logs of individual items.', 'simple-download-monitor' ); ?></p>
    </div>

    <div id="poststuff"><div id="post-body">

        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php _e( 'View Specific Item Logs', 'simple-download-monitor' ); ?></label></h3>
            <div class="inside">
                <form method="post" action="" >
                    <?php _e('Enter the Download ID of the item: ', 'simple-download-monitor' ); ?>
                    <input type="text" name="sdm_specific_download_id" value="<?php echo esc_attr($sdm_logs_dl_id); ?>" size="10" />
                    <p class='description'>
                        <?php _e('You can find the Download ID of an item from the Downloads menu of the plugin.', 'simple-download-monitor' ); ?>
                    </p>
                    <div class="submit">
                        <input type="submit" class="button" name="sdm_show_specific_item_logs" value="<?php _e( 'View Logs', 'simple-download-monitor' ); ?>" />
                    </div>
                </form>
            </div>
        </div>

    </div></div><!-- end of .poststuff and .post-body -->

    <?php
    if(isset($sdm_logs_dl_id) && !empty($sdm_logs_dl_id)){
        //Show the specific item logs

        /* Prepare everything for the specific logs table */
        //Create an instance of our package class...
        $sdmListTable = new sdm_List_Table();
        //Fetch, prepare, sort, and filter our data...
        $sdmListTable->prepare_items();
        echo '<strong>' . __('The following table shows the download logs of the item with Download ID: ', 'simple-download-monitor') . $sdm_logs_dl_id . '</strong>'
        ?>
            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="sdm_downloads-filter" method="post">
            <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST[ 'page' ] ) ?>" />
            <!-- Now we can render the completed list table -->
                <?php $sdmListTable->display() ?>
            </form>
        <?php
    }

}