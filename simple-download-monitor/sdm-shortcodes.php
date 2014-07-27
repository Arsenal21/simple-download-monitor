<?php

/*
** Register and handle Shortcode
*/

function sdm_register_shortcodes() {	
    add_shortcode('sdm-download', 'sdm_create_download_shortcode' );  // For download shortcode
    add_shortcode('sdm_download', 'sdm_create_download_shortcode' );  // For download shortcode (underscores)
    add_shortcode('sdm-download-counter', 'sdm_create_counter_shortcode' );  // For counter shortcode
    add_shortcode('sdm_download_counter', 'sdm_create_counter_shortcode' );  // For counter shortcode (underscores)
    add_shortcode('sdm_show_dl_from_category', 'sdm_handle_category_shortcode' ); //For category shortcode
    add_shortcode('sdm_download_categories', 'sdm_download_categories_shortcode' );// Ajax file tree browser
}

// Create Download Shortcode
function sdm_create_download_shortcode( $atts ) {
	
	extract( shortcode_atts( array(
		'id' => 'id',
		'fancy' => '0',
                'button_text' => '',
                'new_window' => '',
	), $atts ) );
	
	// Check to see if the download link cpt is password protected
	$get_cpt_object = get_post($id);
	$cpt_is_password = !empty($get_cpt_object->post_password) ? 'yes' : 'no';  // yes = download is password protected;
	
	// Get CPT thumbnail
	$item_download_thumbnail = get_post_meta( $id, 'sdm_upload_thumbnail', true );
	$isset_download_thumbnail = isset($item_download_thumbnail) && !empty($item_download_thumbnail) ? '<img class="sdm_download_thumbnail_image" src="'.$item_download_thumbnail.'" />' : '';
	
	// Get CPT title
	$item_title = get_the_title( $id );
	$isset_item_title = isset($item_title) && !empty($item_title) ? $item_title : '';
	
	// Get CPT description
	$item_description = get_post_meta( $id, 'sdm_description', true );
	$isset_item_description = isset($item_description) && !empty($item_description) ? $item_description : '';
	$isset_item_description = do_shortcode($isset_item_description);
        
	// Get CPT download link
	$item_link = get_post_meta( $id, 'sdm_upload', true );
	$isset_item_link = isset($item_link) && !empty($item_link) ? $item_link : '';
	
	// See if user color option is selected
	$main_opts = get_option('sdm_downloads_options');
	$color_opt = $main_opts['download_button_color'];
	$def_color = isset($color_opt) ? str_replace(' ', '', strtolower($color_opt)) : __('green', 'sdm_lang');
	
        //Download counter
        //$dl_counter = sdm_create_counter_shortcode(array('id'=>$id));
        
	//*** Generate the download now button code ***
        $window_target = '';
        if(!empty($new_window)){
            $window_target = 'target="_blank"';
        }   
        if(empty($button_text)){//Use the default text for the button
            $button_text_string = __('Download Now!', 'sdm_lang');
        }else{//Use the custom text
            $button_text_string = $button_text;
        }
	$homepage = get_bloginfo('url');
	$download_url = $homepage. '/?smd_process_download=1&download_id='.$id;
	$download_button_code = '<a href="'.$download_url.'" class="sdm_download '.$def_color.'" title="'.$isset_item_title.'" '.$window_target.'>'.$button_text_string.'</a>';
	
	if($cpt_is_password !== 'no'){//This is a password protected download so replace the download now button with password requirement
		$download_button_code = sdm_get_password_entry_form($id);
	}
	//End of download now button code generation
	
	if ($fancy == '0') {
		$data = '<div class="sdm_download_link">'.$download_button_code.'</div>';	
		return $data;
	}

	if ($fancy == '1') {
		// Prepare shortcode
		$data = '<div class="sdm_download_item">';
		$data .= '<div class="sdm_download_item_top">';
		$data .= '<div class="sdm_download_thumbnail">'.$isset_download_thumbnail.'</div>';
		$data .= '<div class="sdm_download_title">'.$isset_item_title.'</div>';
		$data .= '</div>';//End of .sdm_download_item_top
		$data .= '<div style="clear:both;"></div>';
		$data .= '<div class="sdm_download_description">'.$isset_item_description.'</div>';                
		$data .= '<div class="sdm_download_link">'.$download_button_code.'</div>';
		$data .= '</div>';
		// Render shortcode
		return $data;
	}
}

// Create Counter Shortcode
function sdm_create_counter_shortcode( $atts ) {
	
	extract( shortcode_atts( array(
		'id' => 'id'
	), $atts ) );
	
	// Get number of downloads by counting db columns matching postID
	global $wpdb;
	$table = $wpdb->prefix.'sdm_downloads';
	$wpdb->get_results($wpdb->prepare('SELECT * FROM '.$table.' WHERE post_id=%s', $id));
	// Count database rows
	$db_count = $wpdb->num_rows;
	
	// Check post meta to see if we need to offset the count before displaying to viewers
	$get_offset = get_post_meta( $id, 'sdm_count_offset', true );
	
	if($get_offset && $get_offset != '') {
		
		$db_count = $db_count + $get_offset;
	}
	
	// Set string for singular/plural results
	$string = ($db_count == '1') ? __('Download','sdm_lang') : __('Downloads','sdm_lang');
	
	// Return result
	return $db_count.' '.$string;
}

// Create Category Shortcode
function sdm_handle_category_shortcode($args){
	
    extract( shortcode_atts( array(
            'category_slug' => '',
            'category_id' => '',
            'fancy' => '0',
            'button_text' => '',
            'new_window' => '',
    ), $args ) );
	
	// Define vars
	$field = '';
	$terms = '';

	// If category slug and category id are empty.. return error
    if(empty($category_slug) && empty($category_id)){
        return '<p style="color: red;">'.__('Error! You must enter a category slug OR a category id with this shortcode. Refer to the documentation for usage instructions.', 'sdm_lang').'</p>';
    }
	// Else if both category slug AND category id are defined... return error
	else if(!empty($category_slug) && !empty($category_id)) {
		return '<p style="color: red;">'.__('Error! Please enter a category slug OR id; not both.', 'sdm_lang').'</p>';
	} 
	// Else setup query arguments for category_slug
	else if(!empty($category_slug) && empty($category_id)) { 
	
		$field = 'slug';
		$terms = $category_slug;
	}
	// Else setup query arguments for category_id
	else if(!empty($category_id) && empty($category_slug)) {
		
		$field = 'term_id';
		$terms = $category_id;
	}
	
	// Query cpt's based on arguments above
	$get_posts = get_posts(array(
		'post_type' => 'sdm_downloads',
		'show_posts' => -1,
                'posts_per_page' => 9999,
		'tax_query' => array(
			array(
				'taxonomy' => 'sdm_categories',
				'field' => $field,
				'terms' => $terms
			)
		)
	));
		
	// If no cpt's are found
	if(!$get_posts) {
		return '<p style="color: red;">'.__('There are no download items matching this category criteria.', 'sdm_lang').'</p>';
	}
	// Else iterate cpt's
	else {
		
		// Setup download location
		$data = '';
		$homepage = get_bloginfo('url');
		
		// See if user color option is selected
		$main_opts = get_option('sdm_downloads_options');
		$color_opt = $main_opts['download_button_color'];
		$def_color = isset($color_opt) ? str_replace(' ', '', strtolower($color_opt)) : __('green', 'sdm_lang');
		
                $window_target = '';
                if(!empty($new_window)){
                    $window_target = 'target="_blank"';
                }
                
                if(empty($button_text)){//Use the default text for the button
                    $button_text_string = __('Download Now!', 'sdm_lang');
                }else{//Use the custom text
                    $button_text_string = $button_text;
                }
                
		// Iterate cpt's
		foreach ($get_posts as $item) {
			
			// Set download location
			$id = $item->ID;  // get each cpt ID
			$download_url = $homepage. '/?smd_process_download=1&download_id='.$id;
			
			// Get each cpt title
			$item_title = get_the_title( $id );
			$isset_item_title = isset($item_title) && !empty($item_title) ? $item_title : '';
			
			// Get CPT thumbnail (for fancy option)
			$item_download_thumbnail = get_post_meta( $id, 'sdm_upload_thumbnail', true );
			$isset_download_thumbnail = isset($item_download_thumbnail) && !empty($item_download_thumbnail) ? '<img class="sdm_download_thumbnail_image" src="'.$item_download_thumbnail.'" />' : '';
			
			// Get CPT description (for fancy option)
			$item_description = get_post_meta( $id, 'sdm_description', true );
			$isset_item_description = isset($item_description) && !empty($item_description) ? $item_description : '';
			
			// Setup download button code
			$download_button_code = '<a href="'.$download_url.'" class="sdm_download '.$def_color.'" title="'.$isset_item_title.'" '.$window_target.'>'.$button_text_string.'</a>';
		
			// Generate download buttons
			if ($fancy == '0') {
				
				$data .= '<div class="sdm_download_link">'.$download_button_code.'</div><br />';
			}
		
			if ($fancy == '1') {
				
				$data .= '<div class="sdm_download_item">';
					$data .= '<div class="sdm_download_item_top">';
						$data .= '<div class="sdm_download_thumbnail">'.$isset_download_thumbnail.'</div>';
						$data .= '<div class="sdm_download_title">'.$isset_item_title.'</div>';
					$data .= '</div>';  // End of .sdm_download_item_top
					$data .= '<div style="clear:both;"></div>';
					$data .= '<div class="sdm_download_description">'.$isset_item_description.'</div>';
					$data .= '<div class="sdm_download_link">'.$download_button_code.'</div>';
				$data .= '</div>';
				$data .= '<br />';
			}
		}  // End foreach
		
		// Return results
		return $data;
		
	}  // End else iterate cpt's
		
    
    exit;
}

// Create category tree shortcode
function sdm_download_categories_shortcode() {

	function custom_taxonomy_walker($taxonomy, $parent = 0) {
            
		// Get terms (check if has parent)
		$terms = get_terms($taxonomy, array('parent' => $parent, 'hide_empty' => false));
		
		// If there are terms, start displaying
		if(count($terms) > 0) {
			// Displaying as a list
			$out = '<ul>';
			// Cycle though the terms
			foreach ($terms as $term) {
				// Secret sauce.  Function calls itself to display child elements, if any
				$out .= '<li class="sdm_cat" id="'.$term->slug.'"><span id="'.$term->term_id.'" class="sdm_cat_title" style="cursor:pointer;">' . $term->name .'</span>';
                                $out .= '<p class="sdm_placeholder" style="margin-bottom:0;"></p>' . custom_taxonomy_walker($taxonomy, $term->term_id);
                                $out .= '</li>'; 
			}
			$out .= '</ul>';    
			return $out;
		}
		return;
	}
	return '<div class="sdm_object_tree">'.custom_taxonomy_walker('sdm_categories').'</div>';
}