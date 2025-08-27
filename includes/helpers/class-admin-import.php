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
                $repositories = $this->fetch_github_repositories( $github_user_url );

                if ( is_array( $repositories ) && ! isset( $repositories['error'] ) ) {
                    // Display the form with the list of repositories
                    $this->display_repository_selection_form( $repositories );
                    return;
                } else {
                    // Display error message with "Try again" link including the GitHub user URL
                    echo '<div class="notice notice-error"><p>' . esc_html( $repositories['error'] ) . '</p></div>';
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
                            } else {
                                $error_messages[] = $repo_url . ' - Project class instance not available.';
                                $error_count++;
                                continue;
                            }

                            // Generate View and Edit links
                            $success_messages[] = sprintf( __( '%s imported successfully.', 'osprojects' ), esc_html( $repo_url ) )
                                . ' ' . OSProjectsProject::project_action_link( $project_id, 'view', '_blank' )
                                . ' | ' . OSProjectsProject::project_action_link( $project_id, 'edit', '_blank' );
                            $imported_count++;
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

    private function display_repository_selection_form( $repositories ) {
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
                            // Check if the repository is already imported
                            $project_id = OSProjectsProject::get_repo_project_id( $repo_url );
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
        $api_url = "https://api.github.com/users/{$username}/repos";

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
            return array( 'error' => __( 'No repositories found for this user.', 'osprojects' ) );
        }

        return $repos;
    }
}
