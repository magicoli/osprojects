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

        add_meta_box(
            'project_details_meta_box',
            __( 'Project Details', 'osprojects' ),
            array( $this, 'render_project_details_meta_box' ),
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
     * Render the project details meta box
     */
    public function render_project_details_meta_box( $post ) {
        wp_nonce_field( 'save_project_details', 'project_details_nonce' );
        $project_website = get_post_meta( $post->ID, '_osprojects_project_website', true );
        $project_repository = get_post_meta( $post->ID, '_osprojects_project_repository', true );
        $project_license = get_post_meta( $post->ID, '_osprojects_project_license', true );
        $stable_release_version = get_post_meta( $post->ID, '_osprojects_stable_release_version', true );
        $stable_release_link = get_post_meta( $post->ID, '_osprojects_stable_release_link', true );
        $development_release_version = get_post_meta( $post->ID, '_osprojects_development_release_version', true );
        $development_release_link = get_post_meta( $post->ID, '_osprojects_development_release_link', true );

        echo '<label for="osprojects_project_website">' . __( 'Project Website', 'osprojects' ) . '</label>';
        echo '<input type="text" style="width:100%;" name="osprojects_project_website" value="' . esc_attr( $project_website ) . '" />';

        echo '<label for="osprojects_project_repository">' . __( 'Project Repository', 'osprojects' ) . '</label>';
        echo '<input type="text" style="width:100%;" name="osprojects_project_repository" value="' . esc_attr( $project_repository ) . '" />';

        echo '<label for="osprojects_project_license">' . __( 'License', 'osprojects' ) . '</label>';
        echo '<input type="text" style="width:100%;" name="osprojects_project_license" value="' . esc_attr( $project_license ) . '" />';

        echo '<label for="osprojects_stable_release_version">' . __( 'Stable Release Version', 'osprojects' ) . '</label>';
        echo '<input type="text" style="width:100%;" name="osprojects_stable_release_version" value="' . esc_attr( $stable_release_version ) . '" />';

        echo '<label for="osprojects_stable_release_link">' . __( 'Stable Release Link', 'osprojects' ) . '</label>';
        echo '<input type="text" style="width:100%;" name="osprojects_stable_release_link" value="' . esc_attr( $stable_release_link ) . '" />';

        echo '<label for="osprojects_development_release_version">' . __( 'Development Release Version', 'osprojects' ) . '</label>';
        echo '<input type="text" style="width:100%;" name="osprojects_development_release_version" value="' . esc_attr( $development_release_version ) . '" />';

        echo '<label for="osprojects_development_release_link">' . __( 'Development Release Link', 'osprojects' ) . '</label>';
        echo '<input type="text" style="width:100%;" name="osprojects_development_release_link" value="' . esc_attr( $development_release_link ) . '" />';
    }

    /**
     * Render the short description field after the title
     */
    public function render_short_description_after_title( $post ) {
        if ( $post->post_type == 'project' ) {
            $this->render_short_description_meta_box( $post );
        }
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

        // Save project details
        if ( ! isset( $_POST['project_details_nonce'] ) || ! wp_verify_nonce( $_POST['project_details_nonce'], 'save_project_details' ) ) {
            return;
        }

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
