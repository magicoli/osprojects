<?php
/**
 * Class OSProjectsAdminImport
 * 
 * Handles the Import functionality for OSProjects, including adding the Import admin page,
 * rendering the import form, processing user input, fetching GitHub repositories, and displaying them.
 * 
 * @package osprojects
 */

class OSProjectsAdminImport {
    /**
     * Constructor
     */
    public function __construct() {
        // Remove the submenu registration
        add_action( 'admin_menu', array( $this, 'register_admin_submenus' ) );
        // add_action( 'admin_post_osprojects_fetch_repos', array( $this, 'handle_form_submission' ) );

        // Register the importer
        add_action( 'admin_init', array( $this, 'register_importer' ) );
    }

    /**
     * Add Import submenu under the main OSProjects menu
     */
    public function register_admin_submenus() {
        add_submenu_page(
            'osprojects',
            __( 'Import', 'osprojects' ),
            __( 'Import', 'osprojects' ),
            'manage_options',
            'osprojects-import',
            array( $this, 'admin_import_page' )
        );
    }

    /**
     * Render the Import admin page.
     */
    public function admin_import_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Import GitHub Repositories', 'osprojects' ) . '</h1>';
        // Instruct to use the importer, include the link
        echo '<p>' . esc_html__( 'This page is deprecated. Please use the importer instead.', 'osprojects' ) . '</p>';
        echo '<p>' . sprintf(
            /* translators: %s: Importer URL */
            esc_html__( 'Go to the %s to import GitHub repositories.', 'osprojects' ),
            '<a href="' . esc_url( admin_url( 'import.php?import=osprojects-importer' ) ) . '">' . esc_html__( 'Importer', 'osprojects' ) . '</a>'
        ) . '</p>';
        echo '</div>';
    }

    public function register_importer() {
        if ( current_user_can( 'import' ) ) {
            register_importer(
                'osprojects-importer',
                __( 'OSProjects Importer', 'osprojects' ),
                __( 'Import GitHub repositories as projects.', 'osprojects' ),
                array( $this, 'importer_callback' )
            );
        }
    }

    public function importer_callback() {
        // Check if the form has been submitted
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            check_admin_referer( 'osprojects_import_action', 'osprojects_import_nonce' );

            $github_user_url = isset( $_REQUEST['github_user_url'] ) ? esc_url_raw( $_REQUEST['github_user_url'] ) : ''; // Added to capture the user URL

            if ( isset( $_POST['step'] ) && 'select_repos' === $_POST['step'] ) {
                // Step 2: Import selected repositories
                $selected_repos = isset( $_POST['selected_repos'] ) ? array_map( 'sanitize_text_field', $_POST['selected_repos'] ) : array();
                // $github_user_url = isset( $_REQUEST['github_user_url'] ) ? esc_url_raw( $_REQUEST['github_user_url'] ) : ''; // Added to capture the user URL

                if ( ! empty( $selected_repos ) ) {
                    $this->import_repositories( $selected_repos, $github_user_url );
                } else {
                    // No repositories selected
                    echo '<div class="notice notice-warning"><p>' . esc_html__( 'No repositories were selected for import.', 'osprojects' ) . '</p></div>';
                }
            } else {
                // Step 1: Fetch repositories
                // $github_user_url = isset( $_REQUEST['github_user_url'] ) ? esc_url_raw( $_REQUEST['github_user_url'] ) : ''; // Ensure the URL is captured
                $repository_data = $this->fetch_github_repositories( $github_user_url );

                if ( is_array( $repository_data ) && ! isset( $repository_data['error'] ) ) {
                    // Extract repositories from the structured response
                    $repositories = isset( $repository_data['repositories'] ) ? $repository_data['repositories'] : $repository_data;
                    $metadata = isset( $repository_data['repositories'] ) ? $repository_data : null;
                    // Display the form with the list of repositories
                    $this->display_repository_selection_form( $repositories, $metadata );
                    return;
                } else {
                    // Display error message with "Try again" link including the GitHub user URL
                    $error_msg = isset( $repository_data['error'] ) ? $repository_data['error'] : 'Unknown error occurred';
                    echo '<div class="notice notice-error"><p>' . esc_html( $error_msg ) . '</p></div>';
                    echo '<p><a href="' . esc_url( admin_url( 'import.php?import=osprojects-importer&github_user_url=' . urlencode($github_user_url) ) ) . '">' . esc_html__( 'Try again', 'osprojects' ) . '</a></p>'; // Added "Try again" link with URL
                }
            }
        }

        // Display the initial form
        $this->display_initial_form();
    }

    public function import_repositories( $selected_repos, $github_user_url = null ) {
        if(empty($selected_repos)) {
            return;
        }
        if(!is_array($selected_repos)) {
            return;
        }
        // Initialize tracking variables
        $success_messages = array();
        $error_messages   = array();
        $imported_count   = 0;
        $error_count      = 0;

        // Process each selected repository
        foreach ( $selected_repos as $repo_data ) {
            try {
                // Decode repository data
                $repo = json_decode( base64_decode( $repo_data ), true );
                if ( is_array( $repo ) ) {
                    $repo_url = isset( $repo['html_url'] ) ? esc_url_raw( $repo['html_url'] ) : '';
                    $project_id = OSProjectsProject::get_repo_project_id( $repo_url );
                    if($project_id !== false) {
                        $success_messages[] = sprintf( __( '%s had already a project.', 'osprojects' ), esc_html( $repo_url ) )
                        . OSProjectsProject::project_action_link( $project_id, 'view', '_blank' ) . ' | '
                        . OSProjectsProject::project_action_link( $project_id, 'edit', '_blank' );
                        continue;
                    }
                    if ( empty( $repo_url ) ) {
                        $error_messages[] = 'Repository URL missing.';
                        $error_count++;
                        continue;
                    }

                    // Create a new project post
                    $project_id = wp_insert_post( array(
                        'post_title'   => sanitize_text_field( $repo['name'] ),
                        'post_status'  => 'publish',
                        'post_type'    => 'project',
                    ) );

                    if ( is_wp_error( $project_id ) ) {
                        $error_messages[] = $repo_url . ' - ' . $project_id->get_error_message();
                        $error_count++;
                    } else {
                        // Set post meta for repository URL
                        $updated = update_post_meta( $project_id, 'osp_project_repository', $repo_url );
                        
                        if ( $updated ) {
                            // Prepare meta data array
                            $meta_data = array(
                                'osp_project_repository' => $repo_url,
                            );

                            // Update meta fields using the project class instance
                            global $OSProjectsProject;
                            if ( ! isset( $OSProjectsProject ) ) {
                                $OSProjectsProject = new OSProjectsProject();
                            }
                            if ( isset( $OSProjectsProject ) && method_exists( $OSProjectsProject, 'update_project_meta_fields' ) ) {
                                $OSProjectsProject->update_project_meta_fields( $project_id, $meta_data );
                                
                                // Check if the project was set to ignored status during update (due to git errors)
                                $post_status = get_post_status( $project_id );
                                $git_error = get_post_meta( $project_id, 'osp_project_git_error', true );
                                
                                if ( $post_status === 'ignored' && !empty( $git_error ) ) {
                                    // Project was set to ignored due to repository issues
                                    $error_messages[] = sprintf( __( '%s - Repository marked as ignored: %s', 'osprojects' ), esc_html( $repo_url ), esc_html( $git_error ) )
                                        . ' ' . OSProjectsProject::project_action_link( $project_id, 'edit', '_blank' );
                                    $error_count++;
                                } else {
                                    // Generate View and Edit links for successful import
                                    $success_messages[] = sprintf( __( '%s imported successfully.', 'osprojects' ), esc_html( $repo_url ) )
                                        . ' ' . OSProjectsProject::project_action_link( $project_id, 'view', '_blank' )
                                        . ' | ' . OSProjectsProject::project_action_link( $project_id, 'edit', '_blank' );
                                    $imported_count++;
                                }
                            } else {
                                $error_messages[] = $repo_url . ' - Project class instance not available.';
                                $error_count++;
                                continue;
                            }
                        } else {
                            $error_messages[] = $repo_url . ' - Failed to set repository URL.';
                            $error_count++;
                        }
                    }
                } else {
                    $error_messages[] = 'Invalid repository data format.';
                    $error_count++;
                }
            } catch (Exception $e) {
                $error_messages[] = sprintf(
                    '%s - %s. <a href="%s">%s</a>',
                    esc_html( isset( $repo_url ) ? $repo_url : 'Unknown Repository' ),
                    esc_html( $e->getMessage() ),
                    esc_url( admin_url( 'import.php?import=osprojects-importer&github_user_url=' . urlencode($github_user_url) ) ), // Modified to include GitHub user URL
                    esc_html__( 'Try again', 'osprojects' )
                );
                $error_count++;
                continue;
            }
        }

        // Prepare success notification
        if ( ! empty( $success_messages ) ) {
            echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'Import GitHub Repositories:', 'osprojects' ) . '</strong></p><ul>';
            foreach ( $success_messages as $message ) {
                echo '<li>' . wp_kses( $message, array(
                    'a' => array(
                        'href'   => array(),
                        'target' => array(),
                    ),
                    'strong' => array(),
                ) ) . '</li>';
            }
            echo '</ul>';
            printf(
                /* translators: 1: Number of imported repositories */
                esc_html__( 'Total Imported: %1$d', 'osprojects' ),
                $imported_count
            );
            echo '</div>';
        }

        // Prepare error notification
        if ( ! empty( $error_messages ) ) {
            echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Import GitHub Repositories:', 'osprojects' ) . '</strong></p><ul>';
            foreach ( $error_messages as $message ) {
                echo '<li>' . wp_kses( $message, array(
                    'a' => array(
                        'href'   => array(),
                        'target' => array(),
                    ),
                    'strong' => array(),
                ) ) . '</li>';
            }
            echo '</ul>';
            printf(
                /* translators: 1: Number of errors */
                esc_html__( 'Total Errors: %1$d', 'osprojects' ),
                $error_count
            );
            echo '</div>';
        }

        // Link back to the GitHub importer page
        echo '<p><a href="' . esc_url( admin_url( 'import.php?import=osprojects-importer' ) ) . '">' . esc_html__( 'Back to Importer', 'osprojects' ) . '</a></p>';
    }

    private function display_initial_form() {
        $github_user_url = isset($_REQUEST['github_user_url']) ? esc_url_raw($_REQUEST['github_user_url']) : ''; // Retrieve the URL from query parameters
        ?>
        <div class="wrap">
            <h2><?php esc_html_e( 'Import GitHub Repositories', 'osprojects' ); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field( 'osprojects_import_action', 'osprojects_import_nonce' ); ?>
                <input type="hidden" name="step" value="fetch_repos" />
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="github_user_url"><?php esc_html_e( 'GitHub User URL', 'osprojects' ); ?></label>
                        </th>
                        <td>
                            <input type="url" name="github_user_url" id="github_user_url" class="regular-text" required placeholder="https://github.com/username" value="<?php echo esc_attr( $github_user_url ); ?>" /> <!-- Pre-fill the URL if available -->
                        </td>
                    </tr>
                </table>
                <?php
                if(empty($github_user_url)) {
                    $submit_label = __( 'Check', 'osprojects' );
                } else {
                    $submit_label = __( 'Check again', 'osprojects' );
                }
                submit_button( __( $submit_label, 'osprojects' ) );
                ?>
            </form>
        </div>
        <?php
    }

    private function display_repository_selection_form( $repositories, $metadata = null ) {
        // Fetch existing project repository URLs and their corresponding post IDs
        $existing_repos = $this->get_existing_repository_urls();
        $github_user_url = isset($_REQUEST['github_user_url']) ? esc_url_raw($_REQUEST['github_user_url']) : ''; // Retrieve the URL from query parameters
        ?>
        <div class="wrap">
            <h2><?php esc_html_e( 'Select Repositories to Import', 'osprojects' ); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field( 'osprojects_import_action', 'osprojects_import_nonce' ); ?>
                <input type="hidden" name="step" value="select_repos" />
                <input type="hidden" name="github_user_url" value="<?php echo esc_attr( $github_user_url ); ?>" /> <!-- Added to include the GitHub user URL -->
                <p><?php echo esc_html(
                    sprintf(
                        __(
                            'Select the repositories you wish to import from %s.', 'osprojects' 
                        ),
                        $github_user_url,
                    )
                );
                ?></p>
                <?php if ( $metadata && isset( $metadata['total_count'] ) ) : ?>
                <p><strong><?php
                    echo esc_html( sprintf(
                        __( 'Found %d repositories', 'osprojects' ),
                        $metadata['total_count']
                    ) );
                ?></strong></p>
                <?php endif; ?>
                
                <?php if ( $metadata && isset( $metadata['limit_reached'] ) && $metadata['limit_reached'] ) : ?>
                <div class="notice notice-warning">
                    <p><strong><?php esc_html_e( 'Warning:', 'osprojects' ); ?></strong> 
                    <?php echo esc_html( sprintf(
                        __( 'Repository limit reached. Only the first %d repositories are shown. This user may have more repositories that are not displayed.', 'osprojects' ),
                        $metadata['total_count']
                    ) ); ?>
                    </p>
                </div>
                <?php endif; ?>
                <p>
                    <a href="#" id="select-all"><?php esc_html_e( 'Select All', 'osprojects' ); ?></a> |
                    <a href="#" id="select-none"><?php esc_html_e( 'Select None', 'osprojects' ); ?></a> |
                    <a href="<?php echo esc_url( admin_url( 'import.php?import=osprojects-importer' ) ); ?>"><?php esc_html_e( 'Cancel', 'osprojects' ); ?></a>
                </p>
                <table class="widefat fixed striped" style="table-layout: fixed; width: 100%;">
                    <thead>
                        <tr>
                            <th class="check-column" style="width: 5%;"><input type="checkbox" id="cb-select-all" /></th>
                            <th style="width: 15%;"><?php esc_html_e( 'Repository Name', 'osprojects' ); ?></th>
                            <th style="width: 20%;"><?php esc_html_e( 'Actions', 'osprojects' ); ?></th>
                            <th><?php esc_html_e( 'Description', 'osprojects' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $repositories as $repo ) :
                            $repo_url = $repo['html_url'];
                            // Check if the repository is already imported (check both original URL and any redirect target)
                            $project_id = $this->get_existing_project_for_repo( $repo_url );
                            ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="selected_repos[]" value="<?php echo esc_attr( base64_encode( wp_json_encode( $repo ) ) ); ?>" <?php if ( $project_id ) echo 'disabled'; ?> />
                                </th>
                                <td><?php echo esc_html( $repo['name'] ); ?></td>
                                <td>
                                    <?php
                                    $actions = array();
                                    $actions['repo'] = sprintf(
                                        '<a href="%s" target="_blank">%s</a>',
                                        esc_url( $repo_url ),
                                        esc_html__( 'Repository', 'osprojects' )
                                    );
                                    if($project_id) {
                                        $actions['view'] = OSProjectsProject::project_action_link( $project_id, 'view', '_blank' );
                                        $actions['edit'] = OSProjectsProject::project_action_link( $project_id, 'edit', '_blank' );
                                    }
                                    echo implode( ' | ', $actions );
                                    ?>
                                </td>
                                <td><?php echo esc_html( $repo['description'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php submit_button( __( 'Import', 'osprojects' ) ); ?>
            </form>
        </div>
        <style>
            /* Optional: Style disabled checkboxes */
            input[type="checkbox"]:disabled {
                background-color: #f1f1f1;
            }
        </style>
        <script>
            (function($) {
                $('#select-all').click(function(e) {
                    e.preventDefault();
                    $('input[name="selected_repos[]"]:not(:disabled)').prop('checked', true);
                });
                $('#select-none').click(function(e) {
                    e.preventDefault();
                    $('input[name="selected_repos[]"]:not(:disabled)').prop('checked', false);
                });
                $('#cb-select-all').on('click', function() {
                    $('input[name="selected_repos[]"]:not(:disabled)').prop('checked', this.checked);
                });
            })(jQuery);
        </script>
        <?php
    }

    private function get_existing_repository_urls() {
        // Fetch all project posts regardless of status to avoid duplicates
        $args = array(
            'post_type'      => 'project',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'ignored', 'trash' ),
        );
        $project_ids = get_posts( $args );

        $existing_repos = array();

        // Get the repository URLs from post meta, map URLs to post IDs
        foreach ( $project_ids as $post_id ) {
            $repo_url = get_post_meta( $post_id, 'osp_project_repository', true );
            if ( $repo_url ) {
                $existing_repos[ $repo_url ] = $post_id;
            }
        }

        return $existing_repos;
    }

    /**
     * Check if a repository URL (or its redirect target) already exists as a project
     * 
     * @param string $repo_url Repository URL to check
     * @return int|false Project ID if exists, false otherwise
     */
    private function get_existing_project_for_repo( $repo_url ) {
        // Check the original URL directly
        $project_id = OSProjectsProject::get_repo_project_id( $repo_url );
        if ( $project_id ) {
            return $project_id;
        }

        // Note: Redirect checking will be handled during import via update_project_meta_fields
        // This keeps the list generation fast and avoids private method access issues
        return false;
    }

    /**
     * Fetch GitHub repositories for a given user URL.
     *
     * @param string $url GitHub user URL.
     * @return array List of repositories or error information.
     */
    private function fetch_github_repositories( $url ) {
        $parsed_url = parse_url( $url );
        if ( ! isset( $parsed_url['path'] ) ) {
            return array( 'error' => __( 'Invalid GitHub URL.', 'osprojects' ) );
        }

        $path_parts = explode( '/', trim( $parsed_url['path'], '/' ) );
        if ( count( $path_parts ) < 1 ) {
            return array( 'error' => __( 'Invalid GitHub URL.', 'osprojects' ) );
        }

        $username = $path_parts[0];
        $all_repos = array();
        $page = 1;
        $per_page = 100; // GitHub's maximum per_page value
        $total_fetched = 0;
        $max_pages = 10; // Limit to 10 pages to handle up to 1000 repositories safely
        $limit_reached = false;

        $args = array(
            'headers' => array(
                'Accept'        => 'application/vnd.github.v3+json',
                'User-Agent'    => 'OSProjectsPlugin',
            ),
        );

        // Optionally add GitHub token if set
        $github_token = OSProjects::get_option( 'github_api_token', '' );
        if ( ! empty( $github_token ) ) {
            $args['headers']['Authorization'] = 'token ' . $github_token;
        }

        // Fetch all pages of repositories
        do {
            // Build URL with query parameters properly
            $api_url = add_query_arg( array(
                'per_page' => $per_page,
                'page' => $page,
            ), "https://api.github.com/users/{$username}/repos" );
            
            $response = wp_remote_get( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                return array( 'error' => __( 'Unable to fetch repositories.', 'osprojects' ) );
            }

            $status_code = wp_remote_retrieve_response_code( $response );
            if ( 200 !== $status_code ) {
                return array( 'error' => __( 'GitHub API returned an error.', 'osprojects' ) );
            }

            $body = wp_remote_retrieve_body( $response );
            $repos = json_decode( $body, true );

            if ( empty( $repos ) ) {
                break; // No more repositories
            }

            $all_repos = array_merge( $all_repos, $repos );
            $total_fetched += count( $repos );
            
            // Check for next page using GitHub's link header (recommended approach)
            $headers = wp_remote_retrieve_headers( $response );
            $link_header = isset( $headers['link'] ) ? $headers['link'] : '';
            $has_next_page = false;
            
            if ( $link_header ) {
                // Parse link header to check if there's a "next" page
                $has_next_page = strpos( $link_header, 'rel="next"' ) !== false;
            }
            
            $page++;

            // Safety check to prevent infinite loops and handle very large repository lists
            if ( $page > $max_pages ) {
                $limit_reached = true;
                break;
            }

        } while ( $has_next_page );

        if ( empty( $all_repos ) ) {
            return array( 'error' => __( 'No repositories found for this user.', 'osprojects' ) );
        }

        // Add metadata about the fetch
        return array(
            'repositories' => $all_repos,
            'total_count' => $total_fetched,
            'pages_fetched' => $page - 1,
            'limit_reached' => $limit_reached,
            'max_pages' => $max_pages
        );
    }
}
