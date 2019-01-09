<?php

function sdm_handle_individual_logs_tab_page(){
    echo '<h2>';
    _e( 'Specific Download Item Logs', 'simple-download-monitor' );
    echo '</h2>';
    
    $sdm_specific_download_id = isset($_REQUEST['sdm_specific_download_id'])? sanitize_text_field($_REQUEST['sdm_specific_download_id']): '';
    
    if(isset($_REQUEST['sdm_show_specific_item_logs'])){
        echo 'Show logs of item: ' . $sdm_specific_download_id;
    }
    ?>

    <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
    <p><?php _e( 'This menu allows you to view the download logs of individual items.', 'simple-download-monitor' ); ?></p>
    </div>

    <div id="poststuff"><div id="post-body">
        
    	    <div class="postbox">
    		<h3 class="hndle"><label for="title"><?php _e( 'Specific Item Log', 'simple-download-monitor' ); ?></label></h3>
    		<div class="inside">
    		    <form method="post" action="" >
                        <?php _e('Enter the Download ID of the item: ', 'simple-download-monitor' ); ?>
                        <input type="text" name="sdm_specific_download_id" value="<?php echo esc_attr($sdm_specific_download_id); ?>" size="10" />
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
}