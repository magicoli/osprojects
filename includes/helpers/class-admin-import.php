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
        add_action( 'admin_menu', array( $this, 'register_admin_submenus' ) );
        // add_action( 'admin_post_osprojects_fetch_repos', array( $this, 'handle_form_submission' ) );
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

        // Include the import template
        require_once OSPROJECTS_PLUGIN_PATH . 'templates/admin-import.php';
    }

    /**
     * Handle the form submission to fetch GitHub repositories
     */
    public function handle_form_submission() {
        // Disabled for now because it's not the right strategy and it breaks the site
        
        // // Verify nonce for security
        // if ( ! isset( $_POST['osprojects_import_nonce'] ) || 
        //      ! wp_verify_nonce( $_POST['osprojects_import_nonce'], 'osprojects_import_action' ) ) {
        //     wp_die( __( 'Security check failed.', 'osprojects' ) );
        // }

        // // Sanitize and validate the GitHub user URL
        // if ( isset( $_POST['github_user_url'] ) ) {
        //     $github_user_url = sanitize_text_field( $_POST['github_user_url'] );
        //     if ( filter_var( $github_user_url, FILTER_VALIDATE_URL ) ) {
        //         // Fetch repositories
        //         $repositories = $this->fetch_github_repositories( $github_user_url );

        //         // Redirect back to the import page with results
        //         $redirect_url = add_query_arg( array(
        //             'page' => 'osprojects-import',
        //             'repos' => urlencode( json_encode( $repositories ) ),
        //         ), admin_url( 'admin.php' ) );

        //         wp_redirect( $redirect_url );
        //         exit;
        //     } else {
        //         // Invalid URL, redirect with error
        //         $redirect_url = add_query_arg( array(
        //             'page' => 'osprojects-import',
        //             'error' => 'invalid_url',
        //         ), admin_url( 'admin.php' ) );

        //         wp_redirect( $redirect_url );
        //         exit;
        //     }
        // }

        // // If no URL provided, redirect with error
        // wp_redirect( admin_url( 'admin.php?page=osprojects-import&error=no_url' ) );
        // exit;
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
