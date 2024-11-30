<?php
/**
 * Class OSProjectsProject
 * 
 * Registers the "project" custom post type.
 * 
 * @package         osprojects
**/

class OSProjectsProject {
    /**
     * Constructor
     */
    public function __construct() {
        // Register custom post type
        add_action( 'init', array( $this, 'register_post_type' ) );

        // Add projects submenu
        add_action( 'admin_menu', array( $this, 'add_projects_submenu' ) );
    }

    /**
     * Register the "project" custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Projects', 'osprojects' ),
            'singular_name'      => __( 'Project', 'osprojects' ),
            'menu_name'          => __( 'Projects', 'osprojects' ),
            'name_admin_bar'     => __( 'Project', 'osprojects' ),
            'add_new'            => __( 'Add New', 'osprojects' ),
            'add_new_item'       => __( 'Add New Project', 'osprojects' ),
            'new_item'           => __( 'New Project', 'osprojects' ),
            'edit_item'          => __( 'Edit Project', 'osprojects' ),
            'view_item'          => __( 'View Project', 'osprojects' ),
            'all_items'          => __( 'All Projects', 'osprojects' ),
            'search_items'       => __( 'Search Projects', 'osprojects' ),
            'parent_item_colon'  => __( 'Parent Projects:', 'osprojects' ),
            'not_found'          => __( 'No projects found.', 'osprojects' ),
            'not_found_in_trash' => __( 'No projects found in Trash.', 'osprojects' )
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'project' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
        );

        register_post_type( 'project', $args );
    }

    /**
     * Add projects submenu
     */
    public function add_projects_submenu() {
        add_submenu_page(
            'osprojects',
            __( 'Projects', 'osprojects' ),
            __( 'Projects', 'osprojects' ),
            'manage_options',
            'edit.php?post_type=project'
        );
    }
}
