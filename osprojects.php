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
define( 'OSPROJECTS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Define the plugin URL
define( 'OSPROJECTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include the main plugin class
require_once OSPROJECTS_PLUGIN_PATH . 'includes/init-class.php';

// Include classes
require_once OSPROJECTS_PLUGIN_PATH . 'includes/class-settings.php';
require_once OSPROJECTS_PLUGIN_PATH . 'includes/class-project.php';
require_once OSPROJECTS_PLUGIN_PATH . 'includes/class-git.php';
// Initialize the main plugin class
$OSprojects = new OSProjects();

// Initialize classes
$OSProjectsSettings = new OSProjectsSettings();
$OSProjectsProject = new OSProjectsProject();
// No need to initialize OSProjectsGit here, it is initialized only on demand
