<?php


// Function to create a custom taxonomy for 'Rug Condition'
function create_rug_condition_taxonomy()
{


    /**
     * Create Design Taxonomy
     */

    $labels = array(
        'name'                       => _x('Designs', 'taxonomy general name', 'rug-simple'),
        'singular_name'              => _x('Design', 'taxonomy singular name', 'rug-simple'),
        'search_items'               => __('Search Designs', 'rug-simple'),
        'all_items'                  => __('All Designs', 'rug-simple'),
        'parent_item'                => __('Parent Design', 'rug-simple'),
        'parent_item_colon'          => __('Parent Design:', 'rug-simple'),
        'edit_item'                  => __('Edit Design', 'rug-simple'),
        'update_item'                => __('Update Design', 'rug-simple'),
        'add_new_item'               => __('Add New Design', 'rug-simple'),
        'new_item_name'              => __('New Design Name', 'rug-simple'),
        'menu_name'                  => __('Design', 'rug-simple'),
    );

    $args = array(
        'hierarchical'               => true,
        'labels'                     => $labels,
        'show_ui'                    => true,
        'query_var'                  => true,
        'rewrite'                    => array('slug' => 'rug-design'),
    );

    register_taxonomy('rug_design', array('product'), $args);

    /**
     * Create Rug Type Taxonomy
     */

    $labels = array(
        'name'                       => _x('Rug Types', 'taxonomy general name', 'rug-simple'),
        'singular_name'              => _x('Rug Type', 'taxonomy singular name', 'rug-simple'),
        'search_items'               => __('Search Rug Types', 'rug-simple'),
        'all_items'                  => __('All Rug Types', 'rug-simple'),
        'parent_item'                => __('Parent Rug Type', 'rug-simple'),
        'parent_item_colon'          => __('Parent Rug Type:', 'rug-simple'),
        'edit_item'                  => __('Edit Rug Type', 'rug-simple'),
        'update_item'                => __('Update Rug Type', 'rug-simple'),
        'add_new_item'               => __('Add New Rug Type', 'rug-simple'),
        'new_item_name'              => __('New Rug Type Name', 'rug-simple'),
        'menu_name'                  => __('Rug Type', 'rug-simple'),
    );

    $args = array(
        'hierarchical'               => true,
        'labels'                     => $labels,
        'show_ui'                    => true,
        'query_var'                  => true,
        'rewrite'                    => array('slug' => 'rug-type'),
    );

    register_taxonomy('rug_type', array('product'), $args);

    /**
     * Create Rug Color Taxonomy
     */

    $labels = array(
        'name'                       => _x('Rug Colors', 'taxonomy general name', 'rug-simple'),
        'singular_name'              => _x('Rug Color', 'taxonomy singular name', 'rug-simple'),
        'search_items'               => __('Search Rug Colors', 'rug-simple'),
        'all_items'                  => __('All Rug Colors', 'rug-simple'),
        'parent_item'                => __('Parent Rug Color', 'rug-simple'),
        'parent_item_colon'          => __('Parent Rug Color:', 'rug-simple'),
        'edit_item'                  => __('Edit Rug Color', 'rug-simple'),
        'update_item'                => __('Update Rug Color', 'rug-simple'),
        'add_new_item'               => __('Add New Rug Color', 'rug-simple'),
        'new_item_name'              => __('New Rug Color Name', 'rug-simple'),
        'menu_name'                  => __('Rug Color', 'rug-simple'),
    );

    $args = array(
        'hierarchical'               => true,
        'labels'                     => $labels,
        'show_ui'                    => true,
        'query_var'                  => true,
        'rewrite'                    => array('slug' => 'rug-color'),
    );

    register_taxonomy('rug_color', array('product'), $args);

    /**
     * Create Rug Size Taxonomy
     */

    $labels = array(
        'name'                       => _x('Rug Sizes', 'taxonomy general name', 'rug-simple'),
        'singular_name'              => _x('Rug Size', 'taxonomy singular name', 'rug-simple'),
        'search_items'               => __('Search Rug Sizes', 'rug-simple'),
        'all_items'                  => __('All Rug Sizes', 'rug-simple'),
        'parent_item'                => __('Parent Rug Size', 'rug-simple'),
        'parent_item_colon'          => __('Parent Rug Size:', 'rug-simple'),
        'edit_item'                  => __('Edit Rug Size', 'rug-simple'),
        'update_item'                => __('Update Rug Size', 'rug-simple'),
        'add_new_item'               => __('Add New Rug Size', 'rug-simple'),
        'new_item_name'              => __('New Rug Size Name', 'rug-simple'),
        'menu_name'                  => __('Rug Size', 'rug-simple'),
    );

    $args = array(
        'hierarchical'               => true,
        'labels'                     => $labels,
        'show_ui'                    => true,
        'query_var'                  => true,
        'rewrite'                    => array('slug' => 'rug-size'),
    );

    register_taxonomy('rug_size', array('product'), $args);

    /**
     * Create Rug Shape Taxonomy
     */

    $labels = array(
        'name'                       => _x('Rug Shapes', 'taxonomy general name', 'rug-simple'),
        'singular_name'              => _x('Rug Shape', 'taxonomy singular name', 'rug-simple'),
        'search_items'               => __('Search Rug Shapes', 'rug-simple'),
        'all_items'                  => __('All Rug Shapes', 'rug-simple'),
        'parent_item'                => __('Parent Rug Shape', 'rug-simple'),
        'parent_item_colon'          => __('Parent Rug Shape:', 'rug-simple'),
        'edit_item'                  => __('Edit Rug Shape', 'rug-simple'),
        'update_item'                => __('Update Rug Shape', 'rug-simple'),
        'add_new_item'               => __('Add New Rug Shape', 'rug-simple'),
        'new_item_name'              => __('New Rug Shape Name', 'rug-simple'),
        'menu_name'                  => __('Rug Shape', 'rug-simple'),
    );

    $args = array(
        'hierarchical'               => true,
        'labels'                     => $labels,
        'show_ui'                    => true,
        'query_var'                  => true,
        'rewrite'                    => array('slug' => 'rug-shape'),
    );

    register_taxonomy('rug_shape', array('product'), $args);

    /**
     * Create Rug Pattern Taxonomy
     */

    $labels = array(
        'name'                       => _x('Rug Patterns', 'taxonomy general name', 'rug-simple'),
        'singular_name'              => _x('Rug Pattern', 'taxonomy singular name', 'rug-simple'),
        'search_items'               => __('Search Rug Patterns', 'rug-simple'),
        'all_items'                  => __('All Rug Patterns', 'rug-simple'),
        'parent_item'                => __('Parent Rug Pattern', 'rug-simple'),
        'parent_item_colon'          => __('Parent Rug Pattern:', 'rug-simple'),
        'edit_item'                  => __('Edit Rug Pattern', 'rug-simple'),
        'update_item'                => __('Update Rug Pattern', 'rug-simple'),
        'add_new_item'               => __('Add New Rug Pattern', 'rug-simple'),
        'new_item_name'              => __('New Rug Pattern Name', 'rug-simple'),
        'menu_name'                  => __('Rug Pattern', 'rug-simple'),
    );

    $args = array(
        'hierarchical'               => true,
        'labels'                     => $labels,
        'show_ui'                    => true,
        'query_var'                  => true,
        'rewrite'                    => array('slug' => 'rug-pattern'),
    );

    register_taxonomy('rug_pattern', array('product'), $args);

    /**
     * Create Product Type Taxonomy
     */

    $labels = array(
        'name'                       => _x('Product Types', 'taxonomy general name', 'rug-simple'),
        'singular_name'              => _x('Product Type', 'taxonomy singular name', 'rug-simple'),
        'search_items'               => __('Search Product Types', 'rug-simple'),
        'all_items'                  => __('All Product Types', 'rug-simple'),
        'parent_item'                => __('Parent Product Type', 'rug-simple'),
        'parent_item_colon'          => __('Parent Product Type:', 'rug-simple'),
        'edit_item'                  => __('Edit Product Type', 'rug-simple'),
        'update_item'                => __('Update Product Type', 'rug-simple'),
        'add_new_item'               => __('Add New Product Type', 'rug-simple'),
        'new_item_name'              => __('New Product Type Name', 'rug-simple'),
        'menu_name'                  => __('Product Type', 'rug-simple'),
    );

    $args = array(
        'hierarchical'               => true,
        'labels'                     => $labels,
        'show_ui'                    => true,
        'query_var'                  => true,
        'rewrite'                    => array('slug' => 'rug-product-type'),
    );

    register_taxonomy('rug_product_type', array('product'), $args);
}

add_action('init', 'create_rug_condition_taxonomy', 0);