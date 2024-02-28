<?php

function sdm_register_post_type() {

	//*****  Create 'sdm_downloads' Custom Post Type
	$labels = array(
		'name'               => __( 'Downloads', 'simple-download-monitor' ),
		'singular_name'      => __( 'Downloads', 'simple-download-monitor' ),
		'add_new'            => __( 'Add New', 'simple-download-monitor' ),
		'add_new_item'       => __( 'Add New', 'simple-download-monitor' ),
		'edit_item'          => __( 'Edit Download', 'simple-download-monitor' ),
		'new_item'           => __( 'New Download', 'simple-download-monitor' ),
		'all_items'          => __( 'Downloads', 'simple-download-monitor' ),
		'view_item'          => __( 'View Download', 'simple-download-monitor' ),
		'search_items'       => __( 'Search Downloads', 'simple-download-monitor' ),
		'not_found'          => __( 'No Downloads found', 'simple-download-monitor' ),
		'not_found_in_trash' => __( 'No Downloads found in Trash', 'simple-download-monitor' ),
		'parent_item_colon'  => __( 'Parent Download', 'simple-download-monitor' ),
		'menu_name'          => __( 'Downloads', 'simple-download-monitor' ),
	);

	$sdm_admin_access_permission = get_sdm_admin_access_permission();
    //Trigger filter hook to allow overriding of the default SDM Post capability.
	$sdm_post_capability = apply_filters( 'sdm_post_type_capability', $sdm_admin_access_permission );
	
	$capabilities = array(
		'edit_post'          => $sdm_post_capability,
		'delete_post'        => $sdm_post_capability,
		'read_post'          => $sdm_post_capability,
		'edit_posts'         => $sdm_post_capability,
		'edit_others_posts'  => $sdm_post_capability,
		'delete_posts'       => $sdm_post_capability,
		'publish_posts'      => $sdm_post_capability,
		'read_private_posts' => $sdm_post_capability,
	);

	$sdm_permalink_base = 'sdm_downloads'; //TODO - add an option to configure in the settings maybe?
	$sdm_slug           = untrailingslashit( $sdm_permalink_base );
	$args               = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => $sdm_slug ),
		'capability_type'    => 'post',
		'capabilities'       => $capabilities,
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'menu_icon'          => 'dashicons-download',
		'supports'           => array( 'title' ),
	);

	//Trigger filter before registering the post type. Can be used to override the slug of the downloads
	$args = apply_filters( 'sdm_downloads_post_type_before_register', $args );

	register_post_type( 'sdm_downloads', $args );
}

function sdm_create_taxonomies() {

    $sdm_admin_access_permission = get_sdm_admin_access_permission();
	//Trigger filter hook to allow overriding of the default SDM taxonomies capability.
	$sdm_taxonomies_capability = apply_filters( 'sdm_taxonomies_capability', $sdm_admin_access_permission );

	$capabilities = array(
		'manage_terms' 		 => $sdm_taxonomies_capability,
		'edit_terms'   		 => $sdm_taxonomies_capability,
		'delete_terms'  	 => $sdm_taxonomies_capability,
		'assign_terms' 		 => $sdm_taxonomies_capability,
	);

	//*****  Create CATEGORIES Taxonomy
	$labels_tags = array(
		'name'              => __( 'Download Categories', 'simple-download-monitor' ),
		'singular_name'     => __( 'Download Category', 'simple-download-monitor' ),
		'search_items'      => __( 'Search Categories', 'simple-download-monitor' ),
		'all_items'         => __( 'All Categories', 'simple-download-monitor' ),
		'parent_item'       => __( 'Categories Genre', 'simple-download-monitor' ),
		'parent_item_colon' => __( 'Categories Genre:', 'simple-download-monitor' ),
		'edit_item'         => __( 'Edit Category', 'simple-download-monitor' ),
		'update_item'       => __( 'Update Category', 'simple-download-monitor' ),
		'add_new_item'      => __( 'Add New Category', 'simple-download-monitor' ),
		'new_item_name'     => __( 'New Category', 'simple-download-monitor' ),
		'menu_name'         => __( 'Categories', 'simple-download-monitor' ),
	);
	$args_tags   = array(
		'hierarchical'      => true,
		'labels'            => $labels_tags,
		'show_ui'           => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'sdm_categories' ),
		'show_admin_column' => true,
		'capabilities'      => $capabilities,
	);

	$args_tags = apply_filters( 'sdm_downloads_categories_before_register', $args_tags );

	register_taxonomy( 'sdm_categories', array( 'sdm_downloads' ), $args_tags );

	//*****  Create TAGS Taxonomy
	$labels_tags = array(
		'name'              => __( 'Download Tags', 'simple-download-monitor' ),
		'singular_name'     => __( 'Download Tag', 'simple-download-monitor' ),
		'search_items'      => __( 'Search Tags', 'simple-download-monitor' ),
		'all_items'         => __( 'All Tags', 'simple-download-monitor' ),
		'parent_item'       => __( 'Tags Genre', 'simple-download-monitor' ),
		'parent_item_colon' => __( 'Tags Genre:', 'simple-download-monitor' ),
		'edit_item'         => __( 'Edit Tag', 'simple-download-monitor' ),
		'update_item'       => __( 'Update Tag', 'simple-download-monitor' ),
		'add_new_item'      => __( 'Add New Tag', 'simple-download-monitor' ),
		'new_item_name'     => __( 'New Tag', 'simple-download-monitor' ),
		'menu_name'         => __( 'Tags', 'simple-download-monitor' ),
	);

	$args_tags   = array(
		'hierarchical'      => false,
		'labels'            => $labels_tags,
		'show_ui'           => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'sdm_tags' ),
		'show_admin_column' => true,
		'capabilities'      => $capabilities,
	);

	$args_tags = apply_filters( 'sdm_downloads_tags_before_register', $args_tags );

	register_taxonomy( 'sdm_tags', array( 'sdm_downloads' ), $args_tags );
}
