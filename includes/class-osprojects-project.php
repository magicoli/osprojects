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

        // Add meta boxes for project post type
        add_action( 'add_meta_boxes', array( $this, 'add_project_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_project_meta_boxes' ) );

        // Use a content template for project post type
        add_filter( 'the_content', array( $this, 'load_project_content_template' ), 20 );
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
            'show_in_menu'       => false, // Ensure it does not create a separate main menu item
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'project' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions' ),
            // 'show_in_rest'       => true // Temporary disabled due to a bug in Gutenberg
        );

        register_post_type( 'project', $args );
    }

    /**
     * Add meta boxes for project post type
     */
    public function add_project_meta_boxes( $post_type ) {
        if ( 'project' != $post_type ) {
            return;
        }
        add_meta_box(
            'short_description_meta_box',
            __( 'Short Description', 'osprojects' ),
            array( $this, 'render_short_description_meta_box' ),
            'project',
            'normal',
            'high'
        );
    }

    /**
     * Render the short description meta box
     */
    public function render_short_description_meta_box( $post ) {
        wp_nonce_field( 'save_short_description', 'short_description_nonce' );
        $short_description = get_post_meta( $post->ID, '_short_description', true );
        echo '<input type="text" style="width:100%;" name="short_description" value="' . esc_attr( $short_description ) . '" />';
    }

    /**
     * Save the meta boxes
     */
    public function save_project_meta_boxes( $post_id ) {
        // Save short description
        if ( ! isset( $_POST['short_description_nonce'] ) || ! wp_verify_nonce( $_POST['short_description_nonce'], 'save_short_description' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( isset( $_POST['post_type'] ) && 'project' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }

        if ( isset( $_POST['short_description'] ) ) {
            update_post_meta( $post_id, '_short_description', sanitize_text_field( $_POST['short_description'] ) );
        }
    }

    /**
     * Load the project content template
     */
    public function load_project_content_template( $content ) {
        if ( is_singular( 'project' ) && in_the_loop() && is_main_query() ) {
            ob_start();
            require OSPROJECTS_PATH . 'templates/content-project.php';
            $template_content = ob_get_clean();
            return $template_content;
        }
        return $content;
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
            'edit.php?post_type=project',
            null,
            1
        );
    }
}
