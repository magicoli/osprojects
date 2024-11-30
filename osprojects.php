<?php
/**
 * Plugin Name:     Open Source Projects
 * Plugin URI:      https://magiiic.org/projects/osprojects
 * Description:     Showcase open source projects
 * Author:          Magiiic.org
 * Author URI:      https://magiiic.org/
 * Text Domain:     osprojects
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         osprojects
**/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define the plugin path
define( 'OSPROJECTS_PATH', plugin_dir_path( __FILE__ ) );

// Include the main plugin class
require_once OSPROJECTS_PATH . 'includes/class-osprojects.php';

// Include classes
require_once OSPROJECTS_PATH . 'includes/class-osprojects-settings.php';
require_once OSPROJECTS_PATH . 'includes/class-osprojects-project.php';

// Initialize the plugin
$OSprojects = new OSProjects();

// Initialize classes
$OSProjectsSettings = new OSProjectsSettings();
$OSProjectsProject = new OSProjectsProject();
