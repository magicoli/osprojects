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

        // Register custom post status
        add_action( 'init', array( $this, 'register_custom_post_status' ) );

        // Add set_active_submenu action
        add_action( 'admin_head', array( $this, 'set_active_submenu' ) );

        // Add filter dropdown for project category
        add_action( 'restrict_manage_posts', array( $this, 'add_project_category_filter' ) );
        add_filter( 'request', array( $this, 'filter_projects_by_category_request' ) );
        add_action( 'pre_get_posts', array( $this, 'filter_projects_by_category' ) );

        // Add custom columns for project list
        add_filter( 'manage_project_posts_columns', array( $this, 'add_project_columns' ) );
        add_action( 'manage_project_posts_custom_column', array( $this, 'render_project_columns' ), 10, 2 );
        add_filter( 'manage_edit-project_sortable_columns', array( $this, 'make_project_columns_sortable' ) );
        add_action( 'pre_get_posts', array( $this, 'handle_project_column_sorting' ) );

        // Change columns orderr
        add_filter( 'manage_edit-project_columns', array( $this, 'reorder_project_columns' ) );

        // Custom post row actions for non-hierarchical post types (treated like posts by WordPress)
        // add_filter( 'post_row_actions', array( $this, 'add_ignore_row_action' ), 10, 2 );

        // Custom post row actions for hierarchical post types (treaded like pages by WordPress)
        add_filter( 'page_row_actions', array( $this, 'add_ignore_row_action' ), 10, 2 );

        add_filter( 'bulk_actions-edit-project', array( $this, 'add_ignore_bulk_action' ) );
        add_filter( 'handle_bulk_actions-edit-project', array( $this, 'handle_ignore_bulk_action' ), 10, 3 );
        add_action( 'admin_notices', array( $this, 'ignore_bulk_action_admin_notice' ) );

        // Handle individual ignore/unignore actions
        add_action( 'admin_init', array( $this, 'handle_ignore_actions' ) );
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
            'hierarchical'       => true,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments', 'page-attributes' ),
            'show_in_rest'       => OSProjects::get_option('enable_gutenberg'), // Use the OSProjectsSettings method for show_in_rest
            'show_in_admin_bar'  => true, // Ensure it shows in the admin bar
            'taxonomies'         => array( 'project_category', 'post_tag' )
        );

        register_post_type( 'project', $args );
    }

    /**
     * Register the "project_category" taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => __( 'Project Categories', 'osprojects' ),
            'singular_name'     => __( 'Project Category', 'osprojects' ),
            'search_items'      => __( 'Search Project Categories', 'osprojects' ),
            'all_items'         => __( 'All Project Categories', 'osprojects' ),
            'parent_item'       => __( 'Parent Project Category', 'osprojects' ),
            'parent_item_colon' => __( 'Parent Project Category:', 'osprojects' ),
            'edit_item'         => __( 'Edit Project Category', 'osprojects' ),
            'update_item'       => __( 'Update Project Category', 'osprojects' ),
            'add_new_item'      => __( 'Add New Project Category', 'osprojects' ),
            'new_item_name'     => __( 'New Project Category Name', 'osprojects' ),
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
        
        // Ensure the built-in post_tag taxonomy is available for projects
        register_taxonomy_for_object_type( 'post_tag', 'project' );
    }

    /**
     * Register custom post status for ignored projects
     */
    public function register_custom_post_status() {
        register_post_status( 'ignored', array(
            'label'                     => _x( 'Ignored', 'post status', 'osprojects' ),
            'public'                    => false,
            'internal'                  => true,
            'publicly_queryable'        => false,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Ignored <span class="count">(%s)</span>', 'Ignored <span class="count">(%s)</span>', 'osprojects' ),
        ) );
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
                    
                    // Check for repository redirects and accessibility
                    $redirect_result = $this->resolve_repository_redirects( $repository_url );
                    
                    // Handle errors (404, network issues, etc.)
                    if ( ! empty( $redirect_result['error'] ) ) {
                        error_log(__METHOD__ . " DEBUG $repository_url redirects " . print_r( $redirect_result, true ) );
                        // Set project to ignored status
                        wp_update_post( array(
                            'ID'          => $post_id,
                            'post_status' => 'ignored',
                        ) );
                        update_post_meta( $post_id, 'osp_project_git_error', $redirect_result['error'] );
                        update_post_meta( $post_id, $key, $repository_url ); // Keep original URL for reference
                        break; // Don't attempt git operations
                    }
                    
                    $final_url = $redirect_result['url'];
                    
                    // Handle redirects
                    if ( $redirect_result['redirected'] && $final_url !== $repository_url ) {
                        // Check if the redirect target is already used by another project
                        $existing_project_id = self::get_repo_project_id( $final_url );
                        if ( $existing_project_id !== false && $existing_project_id != $post_id ) {
                            // Another project already uses this URL, set current project to ignored
                            wp_update_post( array(
                                'ID'          => $post_id,
                                'post_status' => 'ignored',
                            ) );
                            update_post_meta( $post_id, 'osp_project_git_error', 'Repository redirects to URL already used by another project.' );
                            update_post_meta( $post_id, $key, $repository_url ); // Keep original URL
                            break; // Don't attempt git operations
                        }
                        
                        // Update to the redirect target URL
                        $repository_url = $final_url;
                    }
                    
                    update_post_meta( $post_id, $key, $repository_url );

                    // Instantiate OSProjectsGit with the repository URL
                    $git = new OSProjectsGit( $repository_url );

                    // Check if the repository was cloned successfully
                    if ( $git->is_repository_cloned() ) {
                        try {
                            // Clear any previous git errors
                            delete_post_meta( $post_id, 'osp_project_git_error' );
                            
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

                            // Collect tags from package metadata and assign as post tags.
                            $project_tags = array();
                            if ( method_exists( $git, 'get_project_tags' ) ) {
                                $project_tags = $git->get_project_tags();
                            } else {
                                $project_type = $git->get_project_type();
                                if ( $project_type ) {
                                    $project_tags[] = $project_type;
                                }
                            }

                            if ( ! empty( $project_tags ) ) {
                                // Assign as post tags (post_tag taxonomy)
                                wp_set_post_terms( $post_id, $project_tags, 'post_tag', false );
                            }
                        } catch ( Exception $e ) {
                            // Git operations failed (empty repository, no commits, etc.) - set project to ignored status
                            wp_update_post( array(
                                'ID'          => $post_id,
                                'post_status' => 'ignored',
                            ) );
                            update_post_meta( $post_id, 'osp_project_git_error', 'Git operations failed: ' . $e->getMessage() );
                        }
                    } else {
                        // Repository is unreachable or empty - set project to ignored status
                        wp_update_post( array(
                            'ID'          => $post_id,
                            'post_status' => 'ignored',
                        ) );
                        update_post_meta( $post_id, 'osp_project_git_error', 'Repository is empty or unreachable.' );
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

        // Add "Project Categories" submenu
        add_submenu_page(
            'osprojects',
            __( 'Project Categories', 'osprojects' ),
            __( 'Categories', 'osprojects' ),
            'manage_options',
            'edit-tags.php?taxonomy=project_category&post_type=project',
            null
        );

        // Add "Tags" submenu for post_tag
        add_submenu_page(
            'osprojects',
            __( 'Tags', 'osprojects' ),
            __( 'Tags', 'osprojects' ),
            'manage_options',
            'edit-tags.php?taxonomy=post_tag&post_type=project',
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
        // Verify nonce with updated action
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

        // Check for repository redirects and accessibility
        $redirect_result = $this->resolve_repository_redirects( $repository_url );
        
        // Handle errors (404, network issues, etc.)
        if ( ! empty( $redirect_result['error'] ) ) {
            error_log(__METHOD__ . " DEBUG $repository_url redirects " . print_r( $redirect_result, true ) );
            wp_send_json_error( $redirect_result['error'] );
        }
        
        $final_url = $redirect_result['url'];
        $redirect_info = '';
        
        // Handle redirects
        if ( $redirect_result['redirected'] && $final_url !== $repository_url ) {
            $redirect_info = ' (redirected from ' . $repository_url . ')';
            $repository_url = $final_url;
        }

        // Instantiate OSProjectsGit with the final repository URL
        $git = new OSProjectsGit( $repository_url );

        // Check if the repository was cloned successfully
        if ( !$git->is_repository_cloned() ) {
            wp_send_json_error( 'Repository is empty or unreachable.' . $redirect_info );
        }

        try {
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
            $project_tags = array();
            if ( method_exists( $git, 'get_project_tags' ) ) {
                $project_tags = $git->get_project_tags();
            } elseif ( $project_type ) {
                $project_tags[] = $project_type;
            }
            
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
                'project_tags'             => $project_tags,
                'final_repository_url'     => $repository_url, // Include final URL in case of redirects
                'redirect_info'            => $redirect_info,
            );

            wp_send_json_success( $data );
        } catch ( Exception $e ) {
            wp_send_json_error( 'Git operations failed: ' . $e->getMessage() . $redirect_info );
        }
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

    /**
     * Add a filter dropdown for project category in the admin list
     */
    public function add_project_category_filter() {
        global $typenow;

        if ( $typenow !== 'project' ) {
            return;
        }

        $taxonomy = 'project_category';
        $selected = isset( $_GET[$taxonomy] ) ? $_GET[$taxonomy] : '';

        // Add "Uncategorized" option
        echo '<select name="' . esc_attr( $taxonomy ) . '" id="' . esc_attr( $taxonomy ) . '">';
        echo '<option value="">' . __( 'All Categories', 'osprojects' ) . '</option>';
        echo '<option value="__uncategorized__" ' . selected( $selected, '__uncategorized__', false ) . '>' . __( 'Uncategorized', 'osprojects' ) . '</option>';

        $categories = get_terms( array(
            'taxonomy'   => $taxonomy,
            'orderby'    => 'name',
            'hide_empty' => false,
        ) );

        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            // Sort categories hierarchically
            $hierarchical_categories = $this->sort_categories_hierarchically( $categories );
            
            foreach ( $hierarchical_categories as $category ) {
                $indent = str_repeat( '&nbsp;&nbsp;', $category->level );
                echo '<option value="' . esc_attr( $category->slug ) . '" ' . selected( $selected, $category->slug, false ) . '>' . $indent . esc_html( $category->name ) . ' (' . $category->count . ')</option>';
            }
        }

        echo '</select>';
    }

    /**
     * Sort categories hierarchically for display
     */
    private function sort_categories_hierarchically( $categories ) {
        $sorted = array();
        $children = array();
        
        // Group children by parent
        foreach ( $categories as $category ) {
            if ( $category->parent == 0 ) {
                $category->level = 0;
                $sorted[] = $category;
            } else {
                $children[$category->parent][] = $category;
            }
        }
        
        // Sort top-level categories by name
        usort( $sorted, function( $a, $b ) {
            return strcmp( $a->name, $b->name );
        } );
        
        // Add children recursively
        $result = array();
        foreach ( $sorted as $parent ) {
            $result[] = $parent;
            $this->add_category_children( $result, $children, $parent->term_id, 1 );
        }
        
        return $result;
    }
    
    /**
     * Recursively add category children
     */
    private function add_category_children( &$result, $children, $parent_id, $level ) {
        if ( ! isset( $children[$parent_id] ) ) {
            return;
        }
        
        // Sort children by name
        usort( $children[$parent_id], function( $a, $b ) {
            return strcmp( $a->name, $b->name );
        } );
        
        foreach ( $children[$parent_id] as $child ) {
            $child->level = $level;
            $result[] = $child;
            $this->add_category_children( $result, $children, $child->term_id, $level + 1 );
        }
    }

    /**
     * Reorder project columns to place 'Tags' after 'Project Category'
     */
    public function reorder_project_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $title ) {
            $new_columns[$key] = $title;
            if ( $key === 'taxonomy-project_category' ) {
                unset( $new_columns['tags'] ); // Temporarily remove to reinsert later
                // Insert 'Tags' column right after 'Project Category'
                $new_columns['tags'] = $columns['tags'];
            }
        }

        return $new_columns;
    }

    /**
     * Filter projects by category in the admin list
     */
    public function filter_projects_by_category( $query ) {
        // Only modify the main query for the project post type in admin
        if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== 'project' ) {
            return;
        }

        // The request filter should have already handled the __uncategorized__ case
        // This is just a fallback for regular category filtering
        $taxonomy = 'project_category';
        if ( isset( $_GET[$taxonomy] ) && $_GET[$taxonomy] !== '' && $_GET[$taxonomy] !== '__uncategorized__' ) {
            $selected = $_GET[$taxonomy];
            
            // Show posts in selected category (regular case)
            $tax_query = array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $selected,
                ),
            );

            $query->set( 'tax_query', $tax_query );
        }
    }

    /**
     * Filter projects by category using request filter - intercept early
     */
    public function filter_projects_by_category_request( $query_vars ) {
        global $typenow;
        
        // Only modify for project post type in admin
        if ( ! is_admin() || $typenow !== 'project' ) {
            return $query_vars;
        }

        $taxonomy = 'project_category';
        
        // Check if we have our special uncategorized value
        if ( isset( $_GET[$taxonomy] ) && $_GET[$taxonomy] === '__uncategorized__' ) {
            // Remove the taxonomy parameter to prevent WordPress from processing it
            unset( $query_vars[$taxonomy] );
            
            // Get all term IDs from the taxonomy
            $terms = get_terms( array(
                'taxonomy' => $taxonomy,
                'fields'   => 'ids',
                'hide_empty' => false,
            ) );
            
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                // Set up tax_query with NOT IN
                $query_vars['tax_query'] = array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'term_id',
                        'terms'    => $terms,
                        'operator' => 'NOT IN',
                    ),
                );
            }
        }

        return $query_vars;
    }

    /**
     * Add custom columns to the project admin list
     */
    public function add_project_columns( $columns ) {
        // Insert repository column after title
        $new_columns = array();
        foreach ( $columns as $key => $title ) {
            $new_columns[$key] = $title;
            if ( $key === 'title' ) {
                $new_columns['repository'] = __( 'Repository', 'osprojects' );
            }
        }
        return $new_columns;
    }

    /**
     * Render custom column content
     */
    public function render_project_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'repository':
                $repo_url = get_post_meta( $post_id, 'osp_project_repository', true );
                if ( $repo_url ) {
                    $short_repo = $this->get_short_repo_name( $repo_url );
                    if ( $short_repo ) {
                        echo '<a href="' . esc_url( $repo_url ) . '" target="_blank" title="' . esc_attr( $repo_url ) . '">' . esc_html( $short_repo ) . '</a>';
                    } else {
                        echo '<a href="' . esc_url( $repo_url ) . '" target="_blank">' . esc_html( $repo_url ) . '</a>';
                    }
                } else {
                    echo '<span class="na">—</span>';
                }
                break;
        }
    }

    /**
     * Make custom columns sortable
     */
    public function make_project_columns_sortable( $columns ) {
        $columns['repository'] = 'repository';
        return $columns;
    }

    /**
     * Handle sorting for custom columns
     */
    public function handle_project_column_sorting( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== 'project' ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        if ( $orderby === 'repository' ) {
            $query->set( 'meta_key', 'osp_project_repository' );
            $query->set( 'orderby', 'meta_value' );
        }
    }

    /**
     * Extract short repo name (user/repo) from repository URL
     */
    private function get_short_repo_name( $repo_url ) {
        $parsed = wp_parse_url( $repo_url );
        if ( ! isset( $parsed['path'] ) ) {
            return false;
        }

        $path = trim( $parsed['path'], '/' );
        
        // Handle common Git hosting patterns
        // Remove .git suffix if present
        $path = preg_replace( '/\.git$/', '', $path );
        
        // Split by / and take last two parts for user/repo format
        $parts = explode( '/', $path );
        if ( count( $parts ) >= 2 ) {
            return end( $parts ) !== '' ? $parts[ count( $parts ) - 2 ] . '/' . end( $parts ) : false;
        }

        return false;
    }

    /**
     * Add "Ignore" action to post row actions
     */
    public function add_ignore_row_action( $actions, $post ) {
        if ( $post->post_type === 'project' && current_user_can( 'edit_post', $post->ID ) ) {
            if ( $post->post_status === 'ignored' ) {
                // Add "Unignore" action for ignored posts - preserve current query args
                $unignore_url = add_query_arg( array(
                    'action' => 'unignore',
                    'post'   => $post->ID,
                ), $_SERVER['REQUEST_URI'] );
                
                $actions['unignore'] = sprintf(
                    '<a href="%s" aria-label="%s">%s</a>',
                    wp_nonce_url( $unignore_url, 'unignore_post_' . $post->ID ),
                    esc_attr( sprintf( __( 'Unignore "%s"', 'osprojects' ), $post->post_title ) ),
                    __( 'Unignore', 'osprojects' )
                );
            } elseif ( in_array( $post->post_status, array( 'publish', 'draft', 'pending', 'private' ) ) ) {
                // Add "Ignore" action for non-ignored, non-trashed posts - preserve current query args
                $ignore_url = add_query_arg( array(
                    'action' => 'ignore',
                    'post'   => $post->ID,
                ), $_SERVER['REQUEST_URI'] );
                
                $actions['ignore'] = sprintf(
                    '<a href="%s" aria-label="%s">%s</a>',
                    wp_nonce_url( $ignore_url, 'ignore_post_' . $post->ID ),
                    esc_attr( sprintf( __( 'Ignore "%s"', 'osprojects' ), $post->post_title ) ),
                    __( 'Ignore', 'osprojects' )
                );
            }
        }
        return $actions;
    }

    /**
     * Add "Ignore" to bulk actions
     */
    public function add_ignore_bulk_action( $bulk_actions ) {
        $bulk_actions['ignore'] = __( 'Ignore', 'osprojects' );
        return $bulk_actions;
    }

    /**
     * Handle bulk ignore action
     */
    public function handle_ignore_bulk_action( $redirect_to, $action, $post_ids ) {
        if ( $action !== 'ignore' ) {
            return $redirect_to;
        }

        $ignored_count = 0;
        foreach ( $post_ids as $post_id ) {
            if ( get_post_type( $post_id ) === 'project' ) {
                wp_update_post( array(
                    'ID'          => $post_id,
                    'post_status' => 'ignored',
                ) );
                $ignored_count++;
            }
        }

        $redirect_to = add_query_arg( 'ignored', $ignored_count, $redirect_to );
        return $redirect_to;
    }

    /**
     * Display admin notice for bulk ignore action
     */
    public function ignore_bulk_action_admin_notice() {
        if ( ! empty( $_REQUEST['ignored'] ) ) {
            $ignored_count = intval( $_REQUEST['ignored'] );
            printf(
                '<div id="message" class="updated notice is-dismissible"><p>' .
                _n( '%d project ignored.', '%d projects ignored.', $ignored_count, 'osprojects' ) .
                '</p></div>',
                $ignored_count
            );
        }

        if ( ! empty( $_REQUEST['unignored'] ) ) {
            printf(
                '<div id="message" class="updated notice is-dismissible"><p>%s</p></div>',
                __( 'Project unignored.', 'osprojects' )
            );
        }
    }

    /**
     * Handle individual ignore/unignore actions
     */
    public function handle_ignore_actions() {
        if ( ! isset( $_GET['action'] ) || ! isset( $_GET['post'] ) ) {
            return;
        }

        $action = $_GET['action'];
        $post_id = intval( $_GET['post'] );

        if ( ! in_array( $action, array( 'ignore', 'unignore' ) ) || get_post_type( $post_id ) !== 'project' ) {
            return;
        }

        // Verify nonce
        $nonce_action = $action . '_post_' . $post_id;
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], $nonce_action ) ) {
            wp_die( __( 'Security check failed.', 'osprojects' ) );
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'osprojects' ) );
        }

        // Preserve current query parameters using WordPress functions
        // Remove only our specific action parameters, keep everything else
        $redirect_url = remove_query_arg( array( 'action', 'post', '_wpnonce', 'ignored', 'unignored' ) );

        // Perform the action
        if ( $action === 'ignore' ) {
            wp_update_post( array(
                'ID'          => $post_id,
                'post_status' => 'ignored',
            ) );
            $redirect_url = add_query_arg( 'ignored', '1', $redirect_url );
        } else { // unignore
            wp_update_post( array(
                'ID'          => $post_id,
                'post_status' => 'publish',
            ) );
            $redirect_url = add_query_arg( 'unignored', '1', $redirect_url );
        }

        wp_redirect( $redirect_url );
        exit;
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
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'ignored', 'trash' ),
        );
        $project_ids = get_posts( $args );
        if (empty($project_ids)) {
            return false;
        } else {
            return $project_ids[0];
        }
    }

    /**
     * Resolve repository URL redirects and check accessibility
     * 
     * @param string $repo_url Original repository URL
     * @return array Array with 'url' (final URL), 'error' (error message if any), 'redirected' (boolean)
     */
    public function resolve_repository_redirects( $repo_url ) {
        // Only handle HTTP(S) URLs
        if ( ! filter_var( $repo_url, FILTER_VALIDATE_URL ) || ! preg_match( '/^https?:\/\//', $repo_url ) ) {
            return array( 'url' => $repo_url, 'error' => null, 'redirected' => false );
        }

        // Use WordPress HTTP API to follow redirects
        $response = wp_remote_head( $repo_url, array(
            'timeout'     => 10,
            'redirection' => 5, // Follow up to 5 redirects
            'user-agent'  => 'OSProjectsPlugin',
        ) );

        // If request failed, return error
        if ( is_wp_error( $response ) ) {
            return array( 
                'url' => $repo_url, 
                'error' => 'Network error: ' . $response->get_error_message(), 
                'redirected' => false 
            );
        }

        // Check HTTP status code
        $status_code = wp_remote_retrieve_response_code( $response );
        
        // Handle 4xx errors (client errors like 404, 403, etc.)
        if ( $status_code >= 400 && $status_code < 500 ) {
            $error_messages = array(
                404 => 'Repository not found (404)',
                403 => 'Access forbidden (403)',
                401 => 'Unauthorized access (401)',
            );
            $error_message = isset( $error_messages[ $status_code ] ) 
                ? $error_messages[ $status_code ] 
                : "Client error ($status_code)";
            
            return array( 
                'url' => $repo_url, 
                'error' => $error_message, 
                'redirected' => false 
            );
        }

        // Handle 5xx errors (server errors)
        if ( $status_code >= 500 ) {
            return array( 
                'url' => $repo_url, 
                'error' => "Server error ($status_code)", 
                'redirected' => false 
            );
        }

        // Get the final URL after redirects
        $final_url = wp_remote_retrieve_header( $response, 'location' );
        
        // If no location header and status is 2xx, no redirect occurred
        if ( empty( $final_url ) && $status_code >= 200 && $status_code < 300 ) {
            return array( 'url' => $repo_url, 'error' => null, 'redirected' => false );
        }

        // Validate the final URL and ensure it's different from the original
        if ( ! empty( $final_url ) && filter_var( $final_url, FILTER_VALIDATE_URL ) && $final_url !== $repo_url ) {
            // Remove trailing .git if present for consistency
            $final_url = preg_replace( '/\.git$/', '', $final_url );
            $repo_url_clean = preg_replace( '/\.git$/', '', $repo_url );
            
            if ( $final_url !== $repo_url_clean ) {
                return array( 'url' => $final_url, 'error' => null, 'redirected' => true );
            }
        }

        return array( 'url' => $repo_url, 'error' => null, 'redirected' => false );
    }

    /**
     * Refresh metadata for all projects by re-calling update_project_meta_fields for each post
     * This will update tags, license, releases and other fetched data from the repository.
     */
    public static function refresh_all_projects() {
        // Deprecated: prefer queued batched processing. Keep for backwards compat but run safely.
        $args = array(
            'post_type'      => 'project',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        );
        $project_ids = get_posts( $args );
        if ( empty( $project_ids ) ) return;

        foreach ( $project_ids as $post_id ) {
            try {
                $repo_url = get_post_meta( $post_id, 'osp_project_repository', true );
                if ( empty( $repo_url ) ) continue;

                $git = new OSProjectsGit( $repo_url );
                if ( ! $git->is_repository_cloned() ) {
                    continue;
                }

                $meta_data = array( 'osp_project_repository' => $repo_url );
                $instance = new self();
                if ( method_exists( $instance, 'update_project_meta_fields' ) ) {
                    $instance->update_project_meta_fields( $post_id, $meta_data );
                }
            } catch ( Exception $e ) {
                error_log( 'OSProjects refresh error for post ' . $post_id . ': ' . $e->getMessage() );
                continue;
            } catch ( Error $e ) {
                error_log( 'OSProjects refresh fatal error for post ' . $post_id . ': ' . $e->getMessage() );
                continue;
            }
        }
    }

    /**
     * Enqueue a full refresh queue. If $post_ids is null, all projects are queued.
     */
    public static function enqueue_refresh_queue( $post_ids = null ) {
        if ( $post_ids === null ) {
            $args = array(
                'post_type'      => 'project',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            );
            $post_ids = get_posts( $args );
        }
        $post_ids = array_values( (array) $post_ids );
        update_option( 'osprojects_refresh_queue', $post_ids );
        $total = count( $post_ids );
        // Compute dynamic batch size: for <100 projects use batch_size=1 (more frequent updates)
        if ( $total < 100 ) {
            $batch_size = 1;
        } else {
            $batch_size = (int) ceil( $total / 100 );
            if ( $batch_size > 10 ) $batch_size = 10; // maximum 10 per batch
            if ( $batch_size < 1 ) $batch_size = 1;
        }

        update_option( 'osprojects_refresh_progress', array(
            'total'       => $total,
            'processed'   => 0,
            'failed'      => 0,
            'failed_items'=> array(),
            'processed_items' => array(),
            'started'     => time(),
            'finished'    => 0,
            'batch_size'  => $batch_size,
            // Status lifecycle: preparing -> processing -> finished
            'status'      => 'preparing',
            'current_project' => null,
            'status_text' => 'Loading projects',
        ) );

        // Mark as running
        update_option( 'osprojects_refresh_running', true );
    }

    /**
     * Process a batch from the refresh queue. Safe to be run repeatedly via single-schedule events.
     */
    public static function process_refresh_queue_batch( $batch_size = 10 ) {
        $queue = get_option( 'osprojects_refresh_queue', array() );
        if ( empty( $queue ) ) {
            $progress = get_option( 'osprojects_refresh_progress', array() );
            $progress['finished'] = time();
            // Ensure current project is cleared when nothing is queued
            $progress['current_project'] = null;
            $progress['status'] = 'finished';
            $processed = isset( $progress['processed'] ) ? (int) $progress['processed'] : 0;
            $total = isset( $progress['total'] ) ? (int) $progress['total'] : 0;
            $failed = isset( $progress['failed'] ) ? (int) $progress['failed'] : 0;
            $progress['status_text'] = sprintf( 'Processing completed - %d / %d processed, %d failed', $processed, $total, $failed );
            update_option( 'osprojects_refresh_progress', $progress );
            // Clear running flag
            update_option( 'osprojects_refresh_running', false );
            return;
        }

        // Allow dynamic batch size from progress
        $progress = get_option( 'osprojects_refresh_progress', array() );
        if ( isset( $progress['batch_size'] ) && $progress['batch_size'] > 0 ) {
            $batch_size = (int) $progress['batch_size'];
        }

        $batch = array_splice( $queue, 0, $batch_size );
        $processed = 0;
        $failed = 0;
        $failed_items = array();

    // (Removed duplicate batch-level pre-announcement; per-item pre announcement handles it.)

    $batch_count = count( $batch );
    foreach ( $batch as $i => $post_id ) {
            // Set current project and status for the UI before processing this item
            $progress_now = get_option( 'osprojects_refresh_progress', array() );
            $processed_count = isset( $progress_now['processed'] ) ? (int) $progress_now['processed'] : 0;
            $failed_count = isset( $progress_now['failed'] ) ? (int) $progress_now['failed'] : 0;
            $index = $processed_count + $failed_count + 1; // 1-based index of attempt
            // Compute a display title with fallbacks
            $title = get_the_title( $post_id );
            if ( ! is_string( $title ) ) { $title = ''; }
            if ( trim( $title ) === '' ) {
                $repo_url_meta = get_post_meta( $post_id, 'osp_project_repository', true );
                if ( is_string( $repo_url_meta ) && trim( $repo_url_meta ) !== '' ) {
                    // Use repo name from URL as a friendly fallback
                    $parsed = wp_parse_url( $repo_url_meta );
                    if ( isset( $parsed['path'] ) ) {
                        $path = trim( $parsed['path'], '/' );
                        // repo path often like owner/repo
                        $parts = explode( '/', $path );
                        $title = end( $parts );
                    } else {
                        $title = $repo_url_meta;
                    }
                } else {
                    $post_name = get_post_field( 'post_name', $post_id );
                    $title = $post_name ? $post_name : ( 'Project #' . $post_id );
                }
            }
            $progress_now['status'] = 'processing';
            $progress_now['current_project'] = array( 'post_id' => $post_id, 'title' => $title, 'index' => $index );
            $total = isset( $progress_now['total'] ) ? (int) $progress_now['total'] : 0;
            $progress_now['status_text'] = sprintf( 'Processing %s - %d / %d processed, %d failed', $title, $processed_count, $total, $failed_count );
            update_option( 'osprojects_refresh_progress', $progress_now );

            try {
                $repo_url = get_post_meta( $post_id, 'osp_project_repository', true );
                if ( empty( $repo_url ) ) {
                    $failed++;
                    // Update failed count immediately
                    $p = get_option( 'osprojects_refresh_progress', array() );
                    $p['failed'] = isset( $p['failed'] ) ? $p['failed'] + 1 : 1;
                    if ( ! isset( $p['failed_items'] ) ) $p['failed_items'] = array();
                    $p['failed_items'][] = array( 'post_id' => $post_id, 'title' => get_the_title( $post_id ), 'error' => 'Missing repository URL' );
                    // Keep last status_text; next loop will set new one
                    update_option( 'osprojects_refresh_progress', $p );
                    continue;
                }

                // Attempt to refresh metadata for this project
                $instance = new self();
                $meta_data = array( 'osp_project_repository' => $repo_url );
                if ( method_exists( $instance, 'update_project_meta_fields' ) ) {
                    $instance->update_project_meta_fields( $post_id, $meta_data );
                }

                // Increment processed immediately so UI updates per-item
                $processed++;
                // Mark this item complete and clear current_project (next item will set it at loop start)
                $p = get_option( 'osprojects_refresh_progress', array() );
                $p['processed'] = isset( $p['processed'] ) ? $p['processed'] + 1 : 1;
                if ( ! isset( $p['processed_items'] ) ) $p['processed_items'] = array();
                $p['processed_items'][] = array( 'post_id' => $post_id, 'title' => $title, 'status' => 'ok' );
                // Announce next item (if any) so UI reflects the actual currently processing project
                if ( $i + 1 < $batch_count ) {
                    $next_id = $batch[ $i + 1 ];
                    $next_title = get_the_title( $next_id );
                    if ( ! is_string( $next_title ) ) { $next_title = ''; }
                    if ( trim( $next_title ) === '' ) {
                        $repo_url_meta = get_post_meta( $next_id, 'osp_project_repository', true );
                        if ( is_string( $repo_url_meta ) && trim( $repo_url_meta ) !== '' ) {
                            $parsed = wp_parse_url( $repo_url_meta );
                            if ( isset( $parsed['path'] ) ) {
                                $path = trim( $parsed['path'], '/' );
                                $parts = explode( '/', $path );
                                $next_title = end( $parts );
                            } else {
                                $next_title = $repo_url_meta;
                            }
                        } else {
                            $post_name = get_post_field( 'post_name', $next_id );
                            $next_title = $post_name ? $post_name : ( 'Project #' . $next_id );
                        }
                    }
                    $p['status'] = 'processing';
                    $p['current_project'] = array( 'post_id' => $next_id, 'title' => $next_title, 'index' => $p['processed'] + $p['failed'] + 1 );
                    $total_now = isset( $p['total'] ) ? (int) $p['total'] : 0;
                    $p['status_text'] = sprintf( 'Processing %s - %d / %d processed, %d failed', $next_title, (int)$p['processed'], $total_now, (int)$p['failed'] );
                } else {
                    // No next item in this batch; clear current until batch/queue handler updates to finished or next batch
                    $p['current_project'] = null;
                }
                update_option( 'osprojects_refresh_progress', $p );
            } catch ( Exception $e ) {
                error_log( 'OSProjects refresh error for post ' . $post_id . ': ' . $e->getMessage() );
                $failed++;
                $failed_items[] = array( 'post_id' => $post_id, 'title' => get_the_title( $post_id ), 'error' => $e->getMessage() );
                // Persist failed item immediately
                $p = get_option( 'osprojects_refresh_progress', array() );
                $p['failed'] = isset( $p['failed'] ) ? $p['failed'] + 1 : 1;
                if ( ! isset( $p['failed_items'] ) ) $p['failed_items'] = array();
                $p['failed_items'][] = array( 'post_id' => $post_id, 'title' => get_the_title( $post_id ), 'error' => $e->getMessage() );
                if ( ! isset( $p['processed_items'] ) ) $p['processed_items'] = array();
                // Record failed in processed_items for debug list
                $fail_title = get_the_title( $post_id );
                if ( ! is_string( $fail_title ) || trim( $fail_title ) === '' ) {
                    $repo_url_meta = get_post_meta( $post_id, 'osp_project_repository', true );
                    if ( is_string( $repo_url_meta ) && trim( $repo_url_meta ) !== '' ) {
                        $parsed = wp_parse_url( $repo_url_meta );
                        if ( isset( $parsed['path'] ) ) {
                            $path = trim( $parsed['path'], '/' );
                            $parts = explode( '/', $path );
                            $fail_title = end( $parts );
                        } else {
                            $fail_title = $repo_url_meta;
                        }
                    } else {
                        $post_name = get_post_field( 'post_name', $post_id );
                        $fail_title = $post_name ? $post_name : ( 'Project #' . $post_id );
                    }
                }
                $p['processed_items'][] = array( 'post_id' => $post_id, 'title' => $fail_title, 'status' => 'failed', 'error' => $e->getMessage() );
                // Announce next item (if any)
                if ( $i + 1 < $batch_count ) {
                    $next_id = $batch[ $i + 1 ];
                    $next_title = get_the_title( $next_id );
                    if ( ! is_string( $next_title ) ) { $next_title = ''; }
                    if ( trim( $next_title ) === '' ) {
                        $repo_url_meta = get_post_meta( $next_id, 'osp_project_repository', true );
                        if ( is_string( $repo_url_meta ) && trim( $repo_url_meta ) !== '' ) {
                            $parsed = wp_parse_url( $repo_url_meta );
                            if ( isset( $parsed['path'] ) ) {
                                $path = trim( $parsed['path'], '/' );
                                $parts = explode( '/', $path );
                                $next_title = end( $parts );
                            } else {
                                $next_title = $repo_url_meta;
                            }
                        } else {
                            $post_name = get_post_field( 'post_name', $next_id );
                            $next_title = $post_name ? $post_name : ( 'Project #' . $next_id );
                        }
                    }
                    $p['status'] = 'processing';
                    $p['current_project'] = array( 'post_id' => $next_id, 'title' => $next_title, 'index' => $p['processed'] + $p['failed'] + 1 );
                    $total_now = isset( $p['total'] ) ? (int) $p['total'] : 0;
                    $p['status_text'] = sprintf( 'Processing %s - %d / %d processed, %d failed', $next_title, (int)$p['processed'], $total_now, (int)$p['failed'] );
                } else {
                    $p['current_project'] = null;
                }
                update_option( 'osprojects_refresh_progress', $p );
                continue;
            } catch ( Error $e ) {
                error_log( 'OSProjects refresh fatal error for post ' . $post_id . ': ' . $e->getMessage() );
                $failed++;
                $failed_items[] = array( 'post_id' => $post_id, 'title' => get_the_title( $post_id ), 'error' => $e->getMessage() );
                $p = get_option( 'osprojects_refresh_progress', array() );
                $p['failed'] = isset( $p['failed'] ) ? $p['failed'] + 1 : 1;
                if ( ! isset( $p['failed_items'] ) ) $p['failed_items'] = array();
                $p['failed_items'][] = array( 'post_id' => $post_id, 'title' => get_the_title( $post_id ), 'error' => $e->getMessage() );
                if ( ! isset( $p['processed_items'] ) ) $p['processed_items'] = array();
                $fail_title = get_the_title( $post_id );
                if ( ! is_string( $fail_title ) || trim( $fail_title ) === '' ) {
                    $repo_url_meta = get_post_meta( $post_id, 'osp_project_repository', true );
                    if ( is_string( $repo_url_meta ) && trim( $repo_url_meta ) !== '' ) {
                        $parsed = wp_parse_url( $repo_url_meta );
                        if ( isset( $parsed['path'] ) ) {
                            $path = trim( $parsed['path'], '/' );
                            $parts = explode( '/', $path );
                            $fail_title = end( $parts );
                        } else {
                            $fail_title = $repo_url_meta;
                        }
                    } else {
                        $post_name = get_post_field( 'post_name', $post_id );
                        $fail_title = $post_name ? $post_name : ( 'Project #' . $post_id );
                    }
                }
                $p['processed_items'][] = array( 'post_id' => $post_id, 'title' => $fail_title, 'status' => 'failed', 'error' => $e->getMessage() );
                if ( $i + 1 < $batch_count ) {
                    $next_id = $batch[ $i + 1 ];
                    $next_title = get_the_title( $next_id );
                    if ( ! is_string( $next_title ) ) { $next_title = ''; }
                    if ( trim( $next_title ) === '' ) {
                        $repo_url_meta = get_post_meta( $next_id, 'osp_project_repository', true );
                        if ( is_string( $repo_url_meta ) && trim( $repo_url_meta ) !== '' ) {
                            $parsed = wp_parse_url( $repo_url_meta );
                            if ( isset( $parsed['path'] ) ) {
                                $path = trim( $parsed['path'], '/' );
                                $parts = explode( '/', $path );
                                $next_title = end( $parts );
                            } else {
                                $next_title = $repo_url_meta;
                            }
                        } else {
                            $post_name = get_post_field( 'post_name', $next_id );
                            $next_title = $post_name ? $post_name : ( 'Project #' . $next_id );
                        }
                    }
                    $p['status'] = 'processing';
                    $p['current_project'] = array( 'post_id' => $next_id, 'title' => $next_title, 'index' => $p['processed'] + $p['failed'] + 1 );
                    $total_now = isset( $p['total'] ) ? (int) $p['total'] : 0;
                    $p['status_text'] = sprintf( 'Processing %s - %d / %d processed, %d failed', $next_title, (int)$p['processed'], $total_now, (int)$p['failed'] );
                } else {
                    $p['current_project'] = null;
                }
                update_option( 'osprojects_refresh_progress', $p );
                continue;
            }
        }

        // Update queue
        update_option( 'osprojects_refresh_queue', $queue );

        // Update finished/current state if queue drained
        $progress = get_option( 'osprojects_refresh_progress', array( 'total' => 0, 'processed' => 0, 'failed' => 0, 'failed_items' => array() ) );
        if ( empty( $queue ) ) {
            $progress['finished'] = time();
            // Clear current project when finished
            $progress['current_project'] = null;
            $progress['status'] = 'finished';
            $processed = isset( $progress['processed'] ) ? (int) $progress['processed'] : 0;
            $total = isset( $progress['total'] ) ? (int) $progress['total'] : 0;
            $failed = isset( $progress['failed'] ) ? (int) $progress['failed'] : 0;
            $progress['status_text'] = sprintf( 'Processing completed - %d / %d processed, %d failed', $processed, $total, $failed );
        } else {
            // Keep processing state between batches
            $progress['status'] = 'processing';
            // Pre-announce the first item of the next batch to avoid stale title during the inter-batch gap
            $next_id = $queue[0];
            $next_title = get_the_title( $next_id );
            if ( ! is_string( $next_title ) ) { $next_title = ''; }
            if ( trim( $next_title ) === '' ) {
                $repo_url_meta = get_post_meta( $next_id, 'osp_project_repository', true );
                if ( is_string( $repo_url_meta ) && trim( $repo_url_meta ) !== '' ) {
                    $parsed = wp_parse_url( $repo_url_meta );
                    if ( isset( $parsed['path'] ) ) {
                        $path = trim( $parsed['path'], '/' );
                        $parts = explode( '/', $path );
                        $next_title = end( $parts );
                    } else {
                        $next_title = $repo_url_meta;
                    }
                } else {
                    $post_name = get_post_field( 'post_name', $next_id );
                    $next_title = $post_name ? $post_name : ( 'Project #' . $next_id );
                }
            }
            $progress['current_project'] = array( 'post_id' => $next_id, 'title' => $next_title, 'index' => (int)$progress['processed'] + (int)$progress['failed'] + 1 );
            $total_now = isset( $progress['total'] ) ? (int) $progress['total'] : 0;
            $progress['status_text'] = sprintf( 'Processing %s - %d / %d processed, %d failed', $next_title, (int)$progress['processed'], $total_now, (int)$progress['failed'] );
        }
        if ( ! isset( $progress['failed_items'] ) ) $progress['failed_items'] = array();
        update_option( 'osprojects_refresh_progress', $progress );

        // Schedule next batch if queue remains
        if ( ! empty( $queue ) ) {
            if ( ! wp_next_scheduled( 'osprojects_process_refresh_batch' ) ) {
                // Adjust delay: make it short for small batches
                wp_schedule_single_event( time() + 2, 'osprojects_process_refresh_batch' );
            }
        } else {
            // Clear running flag when queue drained
            update_option( 'osprojects_refresh_running', false );
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
                $link_text
            );
        }
        return '';
    }

}

