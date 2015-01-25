<?php

function sdm_register_post_type() {

    //*****  Create 'sdm_downloads' Custom Post Type
    $labels = array(
        'name' => __('Downloads', 'sdm_lang'),
        'singular_name' => __('Downloads', 'sdm_lang'),
        'add_new' => __('Add New', 'sdm_lang'),
        'add_new_item' => __('Add New', 'sdm_lang'),
        'edit_item' => __('Edit Download', 'sdm_lang'),
        'new_item' => __('New Download', 'sdm_lang'),
        'all_items' => __('Downloads', 'sdm_lang'),
        'view_item' => __('View Download', 'sdm_lang'),
        'search_items' => __('Search Downloads', 'sdm_lang'),
        'not_found' => __('No Downloads found', 'sdm_lang'),
        'not_found_in_trash' => __('No Downloads found in Trash', 'sdm_lang'),
        'parent_item_colon' => __('Parent Download', 'sdm_lang'),
        'menu_name' => __('Downloads', 'sdm_lang')
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
        'name' => _x('Categories', 'sdm_lang'),
        'singular_name' => _x('Category', 'sdm_lang'),
        'search_items' => __('Search Categories', 'sdm_lang'),
        'all_items' => __('All Categories', 'sdm_lang'),
        'parent_item' => __('Categories Genre', 'sdm_lang'),
        'parent_item_colon' => __('Categories Genre:', 'sdm_lang'),
        'edit_item' => __('Edit Category', 'sdm_lang'),
        'update_item' => __('Update Category', 'sdm_lang'),
        'add_new_item' => __('Add New Category', 'sdm_lang'),
        'new_item_name' => __('New Category', 'sdm_lang'),
        'menu_name' => __('Categories', 'sdm_lang')
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
        'name' => _x('Tags', 'sdm_lang'),
        'singular_name' => _x('Tag', 'sdm_lang'),
        'search_items' => __('Search Tags', 'sdm_lang'),
        'all_items' => __('All Tags', 'sdm_lang'),
        'parent_item' => __('Tags Genre', 'sdm_lang'),
        'parent_item_colon' => __('Tags Genre:', 'sdm_lang'),
        'edit_item' => __('Edit Tag', 'sdm_lang'),
        'update_item' => __('Update Tag', 'sdm_lang'),
        'add_new_item' => __('Add New Tag', 'sdm_lang'),
        'new_item_name' => __('New Tag', 'sdm_lang'),
        'menu_name' => __('Tags', 'sdm_lang')
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
