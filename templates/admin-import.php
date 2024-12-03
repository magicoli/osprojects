<?php
/**
 * Template for the OSProjects Import Admin Page
 * 
 * Contains the form to enter the GitHub user URL and displays the list of repositories.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Do not get repos from json, the URL is too long
// $repos_json = isset( $_GET['repos'] ) ? wp_unslash( $_GET['repos'] ) : '';
$error = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';
// $repos = $repos_json ? json_decode( urldecode( $repos_json ), true ) : array();

$github_user_url = isset( $_POST['github_user_url'] ) ? esc_url( $_POST['github_user_url'] ) : '';

?>
<div class="wrap">
    <h1><?php esc_html_e( 'Import GitHub Repositories', 'osprojects' ); ?></h1>

    <?php if ( $error ) : ?>
        <div class="notice notice-error">
            <p>
                <?php
                switch ( $error ) {
                    case 'invalid_url':
                        esc_html_e( 'The provided GitHub URL is invalid. Please try again.', 'osprojects' );
                        break;
                    case 'no_url':
                        esc_html_e( 'Please enter a GitHub user URL.', 'osprojects' );
                        break;
                    default:
                        esc_html_e( 'An unknown error occurred.', 'osprojects' );
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php
            // Security field
            wp_nonce_field( 'osprojects_import_action', 'osprojects_import_nonce' );
        ?>
        <input type="hidden" name="action" value="osprojects_fetch_repos" />
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="github_user_url"><?php esc_html_e( 'GitHub User URL', 'osprojects' ); ?></label></th>
                <td>
                    <input type="url" id="github_user_url" name="github_user_url" class="regular-text" required placeholder="https://github.com/username" value="<?php echo esc_url( $github_user_url ); ?>" /> 
                </td>
            </tr>
        </table>
        <?php submit_button( __( 'Fetch Repositories', 'osprojects' ) ); ?>
    </form>

    <?php if ( ! empty( $repos ) && is_array( $repos ) ) : ?>
        <h2><?php esc_html_e( 'Repositories', 'osprojects' ); ?></h2>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Repository Name', 'osprojects' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'osprojects' ); ?></th>
                    <th><?php esc_html_e( 'URL', 'osprojects' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $repos as $repo ) : ?>
                    <tr>
                        <td><?php echo esc_html( $repo['name'] ); ?></td>
                        <td><?php echo esc_html( $repo['description'] ); ?></td>
                        <td><a href="<?php echo esc_url( $repo['html_url'] ); ?>" target="_blank"><?php esc_html_e( 'View Repository', 'osprojects' ); ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ( ! empty( $repos['error'] ) ) : ?>
        <div class="notice notice-error">
            <p><?php echo esc_html( $repos['error'] ); ?></p>
        </div>
    <?php endif; ?>
</div>
