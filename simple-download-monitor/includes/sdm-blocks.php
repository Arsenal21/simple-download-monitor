<?php

class SDMBlocks {

    function __construct() {
	add_action( 'init', array( $this, 'register_block' ) );
    }

    function register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
	    // Gutenberg is not active.
	    return;
	}

	wp_enqueue_style( 'sdm-styles', WP_SIMPLE_DL_MONITOR_URL . '/css/sdm_wp_styles.css' );

	wp_register_script(
	'sdm-blocks-script', WP_SIMPLE_DL_MONITOR_URL . '/js/sdm_blocks.js', array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ), WP_SIMPLE_DL_MONITOR_VERSION );

	$fancyArr	 = array();
	$fancyArr[]	 = array( 'label' => "Fancy 0", 'value' => 0 );
	$fancyArr[]	 = array( 'label' => "Fancy 1", 'value' => 1 );
	$fancyArr[]	 = array( 'label' => "Fancy 2", 'value' => 2 );

	$defColorArr = sdm_get_download_button_colors();

	$colorArr	 = array();
	$colorArr[]	 = array( 'label' => __( 'Default', 'simple-download-monitor' ), 'value' => '' );
	foreach ( $defColorArr as $value => $label ) {
	    $colorArr[] = array( 'label' => $label, 'value' => $value );
	}

	wp_localize_script( 'sdm-blocks-script', 'sdmDownloadBlockItems', $this->get_items_array() );
	wp_localize_script( 'sdm-blocks-script', 'sdmDownloadBlockFancy', $fancyArr );
	wp_localize_script( 'sdm-blocks-script', 'sdmDownloadBlockColor', $colorArr );
	wp_localize_script( 'sdm-blocks-script', 'sdmBlockDownloadItemStr', array(
	    'title'		 => __( 'SDM Download', 'simple-download-monitor' ),
	    'download'	 => __( 'Download Item', 'simple-download-monitor' ),
	    'downloadHelp'	 => __( 'Select download item.', 'simple-download-monitor' ),
	    'buttonText'	 => __( 'Button Text', 'simple-download-monitor' ),
	    'buttonTextHelp' => __( 'Customized text for the download button. Leave it blank to use default text.', 'simple-download-monitor' ),
	    'fancy'		 => __( 'Template', 'simple-download-monitor' ),
	    'fancyHelp'	 => __( 'Select download item template.', 'simple-download-monitor' ),
	    'newWindow'	 => __( 'Open Download in a New Window', 'simple-download-monitor' ),
	    'color'		 => __( 'Button Color', 'simple-download-monitor' ),
	    'colorHelp'	 => __( 'Select button color. Note that this option may not work for some templates.', 'simple-download-monitor' ),
	) );

	register_block_type( 'simple-download-monitor/download-item', array(
	    'attributes'		 => array(
		'itemId'	 => array(
		    'type'		 => 'string',
		    'default'	 => 0,
		),
		'fancyId'	 => array(
		    'type'		 => 'string',
		    'default'	 => 0,
		),
		'color'		 => array(
		    'type'		 => 'string',
		    'default'	 => '',
		),
		'buttonText'	 => array(
		    'type'		 => 'string',
		    'default'	 => '',
		),
		'newWindow'	 => array(
		    'type'		 => 'boolean',
		    'default'	 => false,
		),
	    ),
	    'editor_script'		 => 'sdm-blocks-script',
	    'render_callback'	 => array( $this, 'render_item_block' ),
	) );
    }

    function render_item_block( $atts ) {

	$itemId		 = ! empty( $atts[ 'itemId' ] ) ? intval( $atts[ 'itemId' ] ) : 0;
	$fancyId	 = ! empty( $atts[ 'fancyId' ] ) ? intval( $atts[ 'fancyId' ] ) : 0;
	$color		 = ! empty( $atts[ 'color' ] ) ? $atts[ 'color' ] : '';
	$buttonText	 = ! empty( $atts[ 'buttonText' ] ) ? esc_attr( $atts[ 'buttonText' ] ) : sdm_get_download_form_with_termsncond( $itemId );
	$newWindow	 = ! empty( $atts[ 'newWindow' ] ) ? 1 : 0;

	if ( empty( $itemId ) ) {
	    return '<p>' . __( 'Select item to view', 'simple-download-monitor' ) . '</p>';
	}

	$sc_str	 = 'sdm_download id="%d" fancy="%d" button_text="%s" new_window="%d" color="%s"';
	$sc_str	 = sprintf( $sc_str, $itemId, $fancyId, $buttonText, $newWindow, $color );

	if ( ! empty( $atts[ 'btnOnly' ] ) ) {
	    $sc_str .= ' button_only="1"';
	}

	return do_shortcode( '[' . $sc_str . ']' );
    }

    private function get_items_array() {
	$q		 = get_posts( array(
	    'post_type'	 => 'sdm_downloads',
	    'post_status'	 => 'publish',
	    'posts_per_page' => -1,
	    'orderby'	 => 'title',
	    'order'		 => 'ASC',
	) );
	$itemsArr	 = array( array( 'label' => __( '(Select item)', 'simple-download-monitor' ), 'value' => 0 ) );
	foreach ( $q as $post ) {
	    $title		 = html_entity_decode( $post->post_title );
	    $itemsArr[]	 = array( 'label' => esc_attr( $title ), 'value' => $post->ID );
	}
	wp_reset_postdata();
	return $itemsArr;
    }

}

new SDMBlocks();
