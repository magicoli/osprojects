<?php
/**
 * Settings template for settings page.
**/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

echo "<h1>OSProjects Settings</h1>";

echo '<form method="post" action="options.php">';
settings_fields( 'osprojects-settings-group' );
do_settings_sections( 'osprojects-settings' );
submit_button();
echo '</form>';
