<?php
/**
 * Settings template for settings page.
**/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

echo "<h1>" . __( 'OSProjects Settings', 'osprojects' ) . "</h1>";

echo '<form method="post" action="options.php">';
settings_fields( 'osprojects-settings-group' );
do_settings_sections( 'osprojects-settings' );
submit_button();
echo '</form>';

// Manual refresh button (streams output on a dedicated page)
echo '<h2>' . esc_html__( 'Manual Refresh', 'osprojects' ) . '</h2>';
echo '<p>' . esc_html__( 'Trigger an immediate metadata refresh for all projects. The process runs on a dedicated progress page with live output.', 'osprojects' ) . '</p>';
echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
wp_nonce_field( 'osprojects_manual_refresh' );
echo '<input type="hidden" name="action" value="osprojects_manual_refresh" />';
submit_button( __( 'Refresh all projects now', 'osprojects' ), 'primary', 'submit', false );
echo '</form>';
