<?php

function sdm_register_post_type() {

    //*****  Create 'sdm_downloads' Custom Post Type
    $labels = array(
        'name' => __('Downloads', 'simple-download-monitor'),
        'singular_name' => __('Downloads', 'simple-download-monitor'),
        'add_new' => __('Add New', 'simple-download-monitor'),
        'add_new_item' => __('Add New', 'simple-download-monitor'),
        'edit_item' => __('Edit Download', 'simple-download-monitor'),
        'new_item' => __('New Download', 'simple-download-monitor'),
        'all_items' => __('Downloads', 'simple-download-monitor'),
        'view_item' => __('View Download', 'simple-download-monitor'),
        'search_items' => __('Search Downloads', 'simple-download-monitor'),
        'not_found' => __('No Downloads found', 'simple-download-monitor'),
        'not_found_in_trash' => __('No Downloads found in Trash', 'simple-download-monitor'),
        'parent_item_colon' => __('Parent Download', 'simple-download-monitor'),
        'menu_name' => __('Downloads', 'simple-download-monitor')
    );

    $sdm_permalink_base = 'sdm_downloads'; //TODO - add an option to configure in the settings maybe?
    $sdm_slug = untrailingslashit($sdm_permalink_base);
    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => $sdm_slug),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'menu_icon' => 'dashicons-download',
        'supports' => array('title')
    );
    register_post_type('sdm_downloads', $args);
}

function sdm_create_taxonomies() {

    //*****  Create CATEGORIES Taxonomy
    $labels_tags = array(
        'name' => _x('Download Categories', 'simple-download-monitor'),
        'singular_name' => _x('Download Category', 'simple-download-monitor'),
        'search_items' => __('Search Categories', 'simple-download-monitor'),
        'all_items' => __('All Categories', 'simple-download-monitor'),
        'parent_item' => __('Categories Genre', 'simple-download-monitor'),
        'parent_item_colon' => __('Categories Genre:', 'simple-download-monitor'),
        'edit_item' => __('Edit Category', 'simple-download-monitor'),
        'update_item' => __('Update Category', 'simple-download-monitor'),
        'add_new_item' => __('Add New Category', 'simple-download-monitor'),
        'new_item_name' => __('New Category', 'simple-download-monitor'),
        'menu_name' => __('Categories', 'simple-download-monitor')
    );
    $args_tags = array(
        'hierarchical' => true,
        'labels' => $labels_tags,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'sdm_categories'),
        'show_admin_column' => true
    );
    register_taxonomy('sdm_categories', array('sdm_downloads'), $args_tags);

    //*****  Create TAGS Taxonomy
    $labels_tags = array(
        'name' => _x('Download Tags', 'simple-download-monitor'),
        'singular_name' => _x('Download Tag', 'simple-download-monitor'),
        'search_items' => __('Search Tags', 'simple-download-monitor'),
        'all_items' => __('All Tags', 'simple-download-monitor'),
        'parent_item' => __('Tags Genre', 'simple-download-monitor'),
        'parent_item_colon' => __('Tags Genre:', 'simple-download-monitor'),
        'edit_item' => __('Edit Tag', 'simple-download-monitor'),
        'update_item' => __('Update Tag', 'simple-download-monitor'),
        'add_new_item' => __('Add New Tag', 'simple-download-monitor'),
        'new_item_name' => __('New Tag', 'simple-download-monitor'),
        'menu_name' => __('Tags', 'simple-download-monitor')
    );
    $args_tags = array(
        'hierarchical' => false,
        'labels' => $labels_tags,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'sdm_tags'),
        'show_admin_column' => true
    );
    register_taxonomy('sdm_tags', array('sdm_downloads'), $args_tags);
}
