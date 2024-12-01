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

        // Enqueue admin styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

        // Move the short description meta box above the editor
        add_action( 'edit_form_after_title', array( $this, 'render_short_description_after_title' ) );

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
            'show_in_rest'       => OSProjects::get_option('enable_gutenberg') // Use the OSProjectsSettings method for show_in_rest
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
            'general_meta_box',
            __( 'General', 'osprojects' ),
            array( $this, 'render_general_meta_box' ),
            'project',
            'normal',
            'high'
        );

        add_meta_box(
            'repository_meta_box',
            __( 'Repository', 'osprojects' ),
            array( $this, 'render_repository_meta_box' ),
            'project',
            'normal',
            'high'
        );
    }

    /**
     * Render the general meta box
     */
    public function render_general_meta_box( $post ) {
        wp_nonce_field( 'save_general', 'general_nonce' );
        require OSPROJECTS_PLUGIN_PATH . 'templates/project-fields-general.php';
    }

    /**
     * Render the repository meta box
     */
    public function render_repository_meta_box( $post ) {
        wp_nonce_field( 'save_repository', 'repository_nonce' );
        require OSPROJECTS_PLUGIN_PATH . 'templates/project-fields-repository.php';
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles( $hook_suffix ) {
        global $post_type;
        if ( 'project' === $post_type && ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) ) {
            wp_enqueue_style( 'osprojects-admin-styles', OSPROJECTS_PLUGIN_URL . 'css/admin-styles.css' );
        }
    }

    /**
     * Render the short description field after the title
     */
    public function render_short_description_after_title( $post ) {
        if ( $post->post_type == 'project' ) {
            // No need to render the short description meta box separately
        }
    }

    /**
     * Save the meta boxes
     */
    public function save_project_meta_boxes( $post_id ) {
        // Check if our nonce is set.
        if ( ! isset( $_POST['general_nonce'] ) || ! wp_verify_nonce( $_POST['general_nonce'], 'save_general' ) ) {
            return;
        }

        if ( ! isset( $_POST['repository_nonce'] ) || ! wp_verify_nonce( $_POST['repository_nonce'], 'save_repository' ) ) {
            return;
        }

        // Check if this is an autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions.
        if ( isset( $_POST['post_type'] ) && 'project' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }

        // Save the fields.
        if ( isset( $_POST['osprojects_project_website'] ) ) {
            update_post_meta( $post_id, '_osprojects_project_website', sanitize_text_field( $_POST['osprojects_project_website'] ) );
        }

        if ( isset( $_POST['osprojects_project_repository'] ) ) {
            update_post_meta( $post_id, '_osprojects_project_repository', sanitize_text_field( $_POST['osprojects_project_repository'] ) );
        }

        if ( isset( $_POST['osprojects_project_license'] ) ) {
            update_post_meta( $post_id, '_osprojects_project_license', sanitize_text_field( $_POST['osprojects_project_license'] ) );
        }

        if ( isset( $_POST['osprojects_stable_release_version'] ) ) {
            update_post_meta( $post_id, '_osprojects_stable_release_version', sanitize_text_field( $_POST['osprojects_stable_release_version'] ) );
        }

        if ( isset( $_POST['osprojects_stable_release_link'] ) ) {
            update_post_meta( $post_id, '_osprojects_stable_release_link', esc_url_raw( $_POST['osprojects_stable_release_link'] ) );
        }

        if ( isset( $_POST['osprojects_development_release_version'] ) ) {
            update_post_meta( $post_id, '_osprojects_development_release_version', sanitize_text_field( $_POST['osprojects_development_release_version'] ) );
        }

        if ( isset( $_POST['osprojects_development_release_link'] ) ) {
            update_post_meta( $post_id, '_osprojects_development_release_link', esc_url_raw( $_POST['osprojects_development_release_link'] ) );
        }
    }

    /**
     * Load the project content template
     */
    public function load_project_content_template( $content ) {
        if ( is_singular( 'project' ) && in_the_loop() && is_main_query() ) {
            ob_start();
            require OSPROJECTS_PLUGIN_PATH . 'templates/project-content.php';
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
