<?php

function sdm_generate_fancy0_popular_downloads_display_output( $get_posts, $args ) {

    $output		 = "";
    isset( $args[ 'button_text' ] ) ? $button_text	 = $args[ 'button_text' ] : $button_text	 = '';
    isset( $args[ 'new_window' ] ) ? $new_window	 = $args[ 'new_window' ] : $new_window	 = '';
    foreach ( $get_posts as $item ) {
	$id	 = $item->ID;  //Get the post ID
	//Create a args array
	$args	 = array(
	    'id'		 => $id,
	    'fancy'		 => '0',
	    'button_text'	 => $button_text,
	    'new_window'	 => $new_window,
	);
	$output	 .= sdm_generate_fancy0_display_output( $args );
    }
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

function sdm_generate_fancy0_latest_downloads_display_output( $get_posts, $args ) {

    $output		 = "";
    isset( $args[ 'button_text' ] ) ? $button_text	 = $args[ 'button_text' ] : $button_text	 = '';
    isset( $args[ 'new_window' ] ) ? $new_window	 = $args[ 'new_window' ] : $new_window	 = '';
    foreach ( $get_posts as $item ) {
	$id	 = $item->ID;  //Get the post ID
	//Create a args array
	$args	 = array(
	    'id'		 => $id,
	    'fancy'		 => '0',
	    'button_text'	 => $button_text,
	    'new_window'	 => $new_window,
	);
	$output	 .= sdm_generate_fancy0_display_output( $args );
    }
    $output .= '<div class="sdm_clear_float"></div>';
    return $output;
}

function sdm_generate_fancy0_category_display_output($get_posts, $args) {

   $output = "";

   //TODO - when the CSS file is moved to the fancy1 folder, change it here

   foreach ($get_posts as $item) {
        //Create a args array
        $args = array(
            'id' =>  $item->ID,
            'fancy' => '1',
            'button_text' => isset($args['button_text']) ? $args['button_text'] : '',
            'new_window' => isset($args['new_window']) ? $args['new_window'] : '',
        );

        $output .= sdm_generate_fancy0_display_output($args);
   }
   $output .= '<div class="sdm_clear_float"></div>';
   return $output;
}

/*
 * Generates the output of a single item using fancy2 sytle
 * $args array can have the following parameters
 * id, fancy, button_text, new_window
 */

function sdm_generate_fancy0_display_output( $args ) {
    //Check the download ID
    if ( empty( $args[ 'id' ] ) || ! is_numeric( $args[ 'id' ] ) ) {
		return '<div class="sdm_error_msg">Error! The shortcode is missing the ID parameter. Please refer to the documentation to learn the shortcode usage.</div>';
    }

	return sdm_load_template(0, $args);
}
