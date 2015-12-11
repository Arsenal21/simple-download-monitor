(function() {
    tinymce.create('tinymce.plugins.sdmDownloads', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init : function(ed, url) {
            ed.addButton('sdm_downloads', {
                title : 'SDM Downloads',
                cmd : 'sdm_downloads',
                image : url + '/img/sdm_downloads.png'
            });
 
            ed.addCommand('sdm_downloads', function() {
				/*
				ed.windowManager.open({
					file : url + '/sdm_downloads.php',
					width : 400,
					height : 400,
					inline : 1
				});
				*/
				var width = jQuery(window).width(), 
				    H = jQuery(window).height(), 
					W = ( 720 < width ) ? 720 : width;
                    W = W - 80;
                    H = H - 84;
				tb_show( 'SDM Downloads Insert Shortcode', '#TB_inline?width=' + W + '&height=' + H + '&inlineId=highlight-form' );
            });
        },
 
        /**
         * Creates control instances based in the incomming name. This method is normally not
         * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
         * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
         * method can be used to create those.
         *
         * @param {String} n Name of the control to create.
         * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
         * @return {tinymce.ui.Control} New control instance or null if no control was created.
         */
        createControl : function(n, cm) {
            return null;
        },
 
        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo : function() {
            return {
                longname : 'Simple Download Monitor Button',
                author : 'Tips and Tricks HQ',
                authorurl : 'http://www.tipsandtricks-hq.com/development-center',
                infourl : 'http://www.tipsandtricks-hq.com/development-center',
                version : "1.0"
            };
        }
    });
 
    // Register plugin
    tinymce.PluginManager.add( 'sdm_downloads', tinymce.plugins.sdmDownloads );
})();

jQuery(function(){
	
	
	// Run an ajax call to the main.php to get all current CPT items			
	jQuery.post(
		sdm_admin_ajax_url.sdm_admin_ajax_url,
		{
		 action: 'sdm_tiny_get_post_ids'
		},
		function(response) {
			if(response) {  // ** If response was successful
			
				//console.log(response.test);
				
				jQuery.each(response.test, function (index, value) {
					jQuery('#sdm_select').append('<option value="'+value.post_id+'">'+value.post_title+'</option>');  // Populate dropdown list with CPT item titles
				});
			} 
			else {  // ** Else response was unsuccessful
				alert('SDM Downloads AJAX Error! Please deactivate the plugin to permanently dismiss this alert.');
			}
		}
	);
				
	// Instantiate a form in the wp thickbox window (hidden at start)		
    var form = jQuery('<div id="highlight-form"><div id="sdm_tinymce_postids" style="display:none;"></div>\
						<div id="sdm_select_title" style="margin-top:20px;">'+tinymce_langs.select_download_item+'</div>\
						<table id="highlight-table" class="form-table" style="text-align: left">\
							\
							\
						<tr>\
						<th><label class="title" for="highlight-bg">'+tinymce_langs.download_title+'</label></th>\
							<td><select name="sdm_select" id="sdm_select">\
							</select><br />\
						</tr>\
						<tr>\
						<th><label class="sdm_fancy" for "sdm_fancy_option">'+tinymce_langs.include_fancy+'</th>\
							<td><input type="checkbox" name="sdm_fancy_cb" id="sdm_fancy_cb" />\
						</tr>\
						<tr>\
						<th><label class="sdm_new_window" for "sdm_open_new_window">'+tinymce_langs.open_new_window+'</th>\
							<td><input type="checkbox" name="sdm_open_new_window_cb" id="sdm_open_new_window_cb" />\
						</tr>\
                                                </table>\
						<p class="submit">\
							<input type="button" id="sdm-tinymce-submit" class="button-primary" value="'+tinymce_langs.insert_shortcode+'" name="submit" style=""/>\
						</p>\
						<p></p>\
					   </div>');

    var table = form.find('table');
    form.appendTo('body').hide();  // Hide form

    // handles the click event of the submit button
    form.find('#sdm-tinymce-submit').click(function(){

        fancy_cb = jQuery('#sdm_fancy_cb').is(':checked');
        new_window_cb = jQuery('#sdm_open_new_window_cb').is(':checked');
        post_id = jQuery('#sdm_select').find(":selected").val();  // Get selected CPT item title value (item id)

        //Build the shortcode with parameters according to the options
        shortcode = '[sdm_download id="'+post_id+'"';

        //Add the fancy parameter to the shortcode (if needed
        if (jQuery('#sdm_fancy_cb').is(':checked')) {
            shortcode = shortcode + ' fancy="1"';
        } else {
            shortcode = shortcode + ' fancy="0"';
        }
          
        //Add the new_window parameter to the shortcode (if needed)
        if (jQuery('#sdm_open_new_window_cb').is(':checked')) {
            shortcode = shortcode + ' new_window="1"';
        }
        
        shortcode = shortcode + ']';//End the shortcode

        // inserts the shortcode into the active editor
        tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);  // Send processed shortcode to editor

        // close WP thickbox window
        tb_remove();
    });
});