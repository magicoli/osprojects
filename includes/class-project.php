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
        add_action( 'admin_menu', array( $this, 'register_admin_submenus' ), 5 ); // Priority 5 to match the main menu

        // Add meta boxes for project post type
        add_action( 'add_meta_boxes', array( $this, 'add_project_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_project_meta_boxes' ) );

        // Enqueue admin styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

        // Move the short description meta box above the editor
        add_action( 'edit_form_after_title', array( $this, 'renderosp_project_shortdesc_after_title' ) );

        // Use a content template for project post type
        add_filter( 'the_content', array( $this, 'load_project_content_template' ), 20 );

        // Disable "Archives: " prefix on projects archive page
        add_filter( 'get_the_archive_title', array( $this, 'get_archive_title' ) );

        // Hook AJAX handler
        add_action( 'wp_ajax_osprojects_fetch_git_data', array( $this, 'ajax_fetch_git_data' ) );

        // Enqueue admin scripts with AJAX localization
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Register taxonomy
        add_action( 'init', array( $this, 'register_taxonomy' ) );

        // Add set_active_submenu action
        add_action( 'admin_head', array( $this, 'set_active_submenu' ) );
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

        $options = get_option( 'osprojects-settings' );
        $project_url_prefix = isset( $options['project_url_prefix'] ) ? $options['project_url_prefix'] : OSProjectsSettings::DEFAULT_PROJECT_URL_PREFIX;

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, // Ensure it does not create a separate main menu item
            'query_var'          => true,
            'rewrite'            => array( 'slug' => $project_url_prefix ),
            'capability_type'    => 'post',
            'has_archive'        => $project_url_prefix,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions' ),
            'show_in_rest'       => OSProjects::get_option('enable_gutenberg'), // Use the OSProjectsSettings method for show_in_rest
            'show_in_admin_bar'  => true, // Ensure it shows in the admin bar
            'taxonomies'         => array( 'project_category' )
        );

        register_post_type( 'project', $args );
    }

    /**
     * Register the "project_category" taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => __( 'Categories', 'osprojects' ),
            'singular_name'     => __( 'Category', 'osprojects' ),
            'search_items'      => __( 'Search Categories', 'osprojects' ),
            'all_items'         => __( 'All Categories', 'osprojects' ),
            'parent_item'       => __( 'Parent Category', 'osprojects' ),
            'parent_item_colon' => __( 'Parent Category:', 'osprojects' ),
            'edit_item'         => __( 'Edit Category', 'osprojects' ),
            'update_item'       => __( 'Update Category', 'osprojects' ),
            'add_new_item'      => __( 'Add New Category', 'osprojects' ),
            'new_item_name'     => __( 'New Category Name', 'osprojects' ),
            'menu_name'         => __( 'Categories', 'osprojects' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'project-category' ),
            'show_in_rest'      => OSProjects::get_option('enable_gutenberg'), // Use the OSProjectsSettings method for show_in_rest
        );

        register_taxonomy( 'project_category', 'project', $args );
    }

    /**
     * Add meta boxes for project post type
     */
    public function add_project_meta_boxes( $post_type ) {
        if ( 'project' != $post_type ) {
            return;
        }

        // add_meta_box(
        //     'general_meta_box',
        //     __( 'General', 'osprojects' ),
        //     array( $this, 'render_general_meta_box' ),
        //     'project',
        //     'normal',
        //     'high'
        // );

        add_meta_box(
            'project_details_metabox',
            __( 'Project Details', 'osprojects' ),
            array( $this, 'render_project_details_metabox' ),
            'project',
            'normal',
            'high'
        );
    }

    /**
     * Render the repository meta box
     */
    public function render_project_details_metabox( $post ) {
        wp_nonce_field( 'save_repository', 'repository_nonce' );
        require OSPROJECTS_PLUGIN_PATH . 'templates/project-edit-metabox-details.php';
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles( $hook_suffix ) {
        global $post_type;
        if ( 'project' === $post_type && ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) ) {
            OSProjects::enqueue_styles( 'osprojects-admin', 'css/admin.css' );
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook_suffix ) {
        global $post_type;
        if ( 'project' === $post_type && in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
            wp_enqueue_script(
                'osprojects-admin-project-ajax',
                OSPROJECTS_PLUGIN_URL . 'js/admin-project-ajax.js',
                array(), // No dependencies
                '1.0' . time(), // Version
                true
            );

            $repository_url = '';
            if ( 'post.php' === $hook_suffix && isset( $_GET['post'] ) ) {
                $post_id = intval( $_GET['post'] );
                $repository_url = get_post_meta( $post_id, 'osp_project_repository', true );
            }

            // Localize script with AJAX URL, repository URL, and updated nonce
            wp_localize_script( 'osprojects-admin-project-ajax', 'OSProjectsAjax', array(
                'ajax_url'       => admin_url( 'admin-ajax.php' ),
                'repository_url' => $repository_url,
                'nonce'          => wp_create_nonce( 'osprojects_fetch_git_data' ), // Updated nonce action
            ) );

        }
    }

    /**
     * Render the short description field after the title
     */
    public function renderosp_project_shortdesc_after_title( $post ) {
        if ( $post->post_type == 'project' ) {
            // No need to render the short description meta box separately
        }
    }

    /**
     * Update project meta fields.
     *
     * @param int   $post_id  The ID of the post.
     * @param array $meta_data Associative array of meta keys and values.
     */
    public function update_project_meta_fields( $post_id, $meta_data ) {
        remove_action( 'save_post', array( $this, 'save_project_meta_boxes' ) );
        foreach ( $meta_data as $key => $value ) {
            switch ( $key ) {
                case 'osp_project_website':
                    update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
                    break;
                case 'osp_project_repository':
                    $repository_url = sanitize_text_field( $value );
                    update_post_meta( $post_id, $key, $repository_url );

                    // Instantiate OSProjectsGit with the repository URL
                    $git = new OSProjectsGit( $repository_url );

                    // Check if the repository was cloned successfully
                    if ( $git->is_repository_cloned() ) {
                        // Fetch data using OSProjectsGit methods
                        $license = $git->license();
                        $version = $git->version();
                        $release_date = $git->release_date();
                        $last_release_html = $git->last_release_html();
                        $last_commit_html = $git->last_commit_html();

                        // Save fetched data as post meta
                        update_post_meta( $post_id, 'osp_project_license', sanitize_text_field( $license ) );
                        update_post_meta( $post_id, 'osp_project_stable_release_version', sanitize_text_field( $version ) );
                        update_post_meta( $post_id, 'osp_project_last_release_html', wp_kses_post( $last_release_html ) );
                        update_post_meta( $post_id, 'osp_project_last_commit_html', wp_kses_post( $last_commit_html ) );

                        // Set post title if available
                        $project_title = $git->get_project_title();
                        if ( $project_title ) {
                            wp_update_post( array(
                                'ID'         => $post_id,
                                'post_title' => sanitize_text_field( $project_title ),
                            ) );
                        }

                        // Set post content if available
                        $project_description = $git->get_project_description();
                        if ( $project_description ) {
                            $post_content = self::text_to_blocks( $project_description );
                            wp_update_post( array(
                                'ID'           => $post_id,
                                'post_content' => $post_content,
                            ) );
                        }

                        // Assign project category based on 'type'
                        $project_type = $git->get_project_type();
                        if ( $project_type ) {
                            // Ensure term exists
                            $term = term_exists( $project_type, 'project_category' );
                            if ( ! $term ) {
                                $term = wp_insert_term( $project_type, 'project_category' );
                            }
                            if ( ! is_wp_error( $term ) ) {
                                // Set the term for the post using term IDs
                                wp_set_post_terms( $post_id, array( (int) $term['term_id'] ), 'project_category', false );
                            }
                        }
                    } else {
                        // Handle cloning failure
                        update_post_meta( $post_id, 'osp_project_git_error', 'Failed to clone repository.' );
                    }
                    break;
                case 'osp_project_license':
                case 'osp_project_stable_release_version':
                case 'osp_project_stable_release_link':
                case 'osp_project_development_release_version':
                case 'osp_project_development_release_link':
                    if ( strpos( $key, 'link' ) !== false ) {
                        update_post_meta( $post_id, $key, esc_url_raw( $value ) );
                    } elseif ( strpos( $key, 'version' ) !== false ) {
                        update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
                    } else {
                        update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
                    }
                    break;
                default:
                    // Handle other meta fields if necessary
                    break;
            }
        }
        add_action( 'save_post', array( $this, 'save_project_meta_boxes' ) );
    }

    /**
     * Save the meta boxes
     */
    public function save_project_meta_boxes( $post_id ) {
        // Check if our nonce is set.
        // if ( ! isset( $_POST['general_nonce'] ) || ! wp_verify_nonce( $_POST['general_nonce'], 'save_general' ) ) {
        //     return;
        // }

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

        // Prepare meta data array
        $meta_data = array();

        if ( isset( $_POST['osp_project_website'] ) ) {
            $meta_data['osp_project_website'] = $_POST['osp_project_website'];
        }

        if ( isset( $_POST['osp_project_repository'] ) ) {
            $meta_data['osp_project_repository'] = $_POST['osp_project_repository'];
        }

        if ( isset( $_POST['osp_project_license'] ) ) {
            $meta_data['osp_project_license'] = $_POST['osp_project_license'];
        }

        if ( isset( $_POST['osp_project_stable_release_version'] ) ) {
            $meta_data['osp_project_stable_release_version'] = $_POST['osp_project_stable_release_version'];
        }

        if ( isset( $_POST['osp_project_stable_release_link'] ) ) {
            $meta_data['osp_project_stable_release_link'] = $_POST['osp_project_stable_release_link'];
        }

        if ( isset( $_POST['osp_project_development_release_version'] ) ) {
            $meta_data['osp_project_development_release_version'] = $_POST['osp_project_development_release_version'];
        }

        if ( isset( $_POST['osp_project_development_release_link'] ) ) {
            $meta_data['osp_project_development_release_link'] = $_POST['osp_project_development_release_link'];
        }

        // Update meta fields using the new method
        $this->update_project_meta_fields( $post_id, $meta_data );
    }

    /**
     * Convert text to blocks for Gutenberg
     */
    public function text_to_blocks( $text ) {
        // Check if $text already contains Gutenberg blocks
        if ( preg_match( '/<!--\s*wp:/', $text ) || empty( $text ) ) {
            return $text; // Already contains Gutenberg blocks, return unchanged
        }

        $enable_gutenberg = OSProjects::get_option( 'enable_gutenberg' );
 
        $parsedown = new Parsedown();

        if ( ! $enable_gutenberg ) {
            // Use classic editor content
            return wp_kses_post( $parsedown->text( $text ) );
        }

        // Split the description by double returns to create separate blocks
        $segments = preg_split('/\n\s*\n/', $text);
        $blocks = array_map(function($segment) use ($parsedown) {
            $segment = trim($segment);
            // Convert Markdown to HTML
            $html = $parsedown->text($segment);
            // Wrap the HTML in a Gutenberg paragraph block
            return '<!-- wp:paragraph -->' . "\n" . $html . "\n<!-- /wp:paragraph -->";
        }, $segments);
        return implode("\n\n", $blocks);
    }

    
    /**
     * Load the project content template
     */
    public function load_project_content_template( $content ) {
        if ( is_singular( 'project' ) && in_the_loop() && is_main_query() ) {
            ob_start();
            require OSPROJECTS_PLUGIN_PATH . 'templates/project-content.php';
            // Modify the project content template to use OSProjects::date_time()
            $release_date = get_post_meta( get_the_ID(), 'osp_project_release_date', true );
            if ( $release_date ) {
                $formatted_date = OSProjects::date_time( $release_date );
                // Use $formatted_date in the template
            }
            $template_content = ob_get_clean();
            return $template_content;
        }
        return $content;
    }

    /**
     * Add submenu pages for the osprojects admin menu
     */
    public function register_admin_submenus() {
        add_submenu_page(
            'osprojects',
            __( 'Projects', 'osprojects' ),
            __( 'Projects', 'osprojects' ),
            'manage_options',
            'edit.php?post_type=project',
            null,
            1
        );

        // Add "Categories" submenu
        add_submenu_page(
            'osprojects',
            __( 'Categories', 'osprojects' ),
            __( 'Categories', 'osprojects' ),
            'manage_options',
            'edit-tags.php?taxonomy=project_category&post_type=project',
            null
        );
    }

    /**
     * Remove "Archives: " prefix from the archive title
     */
    public function get_archive_title( $title ) {
        if ( is_post_type_archive( 'project' ) ) {
            $title = post_type_archive_title( '', false );
        }
        return $title;
    }

    /**
     * AJAX handler to fetch Git data
     */
    public function ajax_fetch_git_data() {
        // Verify nonce with updated action name
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'osprojects_fetch_git_data' ) ) { // Updated nonce action
            wp_send_json_error( 'Invalid nonce.' );
        }

        // Check if repository_url is set
        if ( ! isset( $_POST['repository_url'] ) ) {
            wp_send_json_error( 'Repository URL is missing.' );
        }

        $repository_url = sanitize_text_field( $_POST['repository_url'] );

        // Validate the repository URL
        if ( ! filter_var( $repository_url, FILTER_VALIDATE_URL ) ) {
            wp_send_json_error( 'Invalid Repository URL.' );
        }

        // Instantiate OSProjectsGit with the repository URL
        $git = new OSProjectsGit( $repository_url );

        // Check if the repository was cloned successfully
        if ( !$git->is_repository_cloned() ) {
            wp_send_json_error( 'Failed to clone repository.' );
        }

        // Fetch data using OSProjectsGit methods
        $license = $git->license();
        $version = $git->version();
        $release_date = $git->release_date();
        $last_release_html = $git->last_release_html();
        $last_commit_html = $git->last_commit_html();

        // Add project title, description, and type
        $project_title = $git->get_project_title();
        $project_description = self::text_to_blocks($git->get_project_description());
        $project_type = $git->get_project_type();
        // Prepare the response data
        $data = array(
            'license'                  => $license,
            'version'                  => $version,
            'release_date'             => $release_date,
            'last_release_html'        => $last_release_html,
            'last_commit_html'         => $last_commit_html,
            'project_title'            => $project_title,
            'project_description'      => $project_description,
            'project_type'             => $project_type,
        );

        wp_send_json_success( $data );
    }

    /**
     * Set active submenu for project categories
     */
    public function set_active_submenu() {
        global $parent_file, $submenu_file, $current_screen;

        if ( isset( $current_screen->taxonomy ) && $current_screen->taxonomy === 'project_category' ) {
            $parent_file = 'osprojects';
            $submenu_file = 'edit-tags.php?taxonomy=project_category&post_type=project';
        }
    }

    public static function get_repo_project_id( $repo_url ) {
        $args = array(
            'post_type'      => 'project',
            'meta_query'     => array(
                array(
                    'key'   => 'osp_project_repository',
                    'value' => $repo_url,
                ),
            ),
            'fields'         => 'ids',
        );
        $project_ids = get_posts( $args );
        if (empty($project_ids)) {
            return false;
        } else {
            return $project_ids[0];
        }
    }

    public static function project_action_link( $post_id, $action = 'view', $target = '_blank' ) {
        $view_link = get_permalink( $post_id );
        switch ($action) {
            case 'edit':
                $link_text = esc_html__( 'Edit Project', 'osprojects' );
                $view_link = get_edit_post_link( $post_id );
                break;
            case 'view':
            default:
                $link_text = esc_html__( 'View Project', 'osprojects' );
                break;
        }
        if ( ! empty( $view_link ) ) {
            return sprintf(
                '<a href="%s" target="%s">%s</a>',
                esc_url( $view_link ),
                esc_attr( $target ),
                esc_html__( 'View Project', 'osprojects' )
            );
        }
        return '';
    }

}

