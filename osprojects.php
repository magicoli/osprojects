<?php
/**
 * Plugin Name:     Open Source Projects for WordPress
 * Plugin URI:      https://magiiic.org/projects/osprojects/
 * Update URI:      https://github.com/magicoli/osprojects
 * Description:     A comprehensive WordPress plugin for showcasing and managing open source projects with automated GitHub integration, project metadata management, and multilingual support.
 * Author:          Magiiic
 * Author URI:      https://magiiic.com/
 * Text Domain:     osprojects
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         osprojects
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Constants
define( 'OSPROJECTS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'OSPROJECTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OSPROJECTS_PLUGIN_VERSION', '1.0.0' );

// Include the main plugin class
require_once OSPROJECTS_PLUGIN_PATH . 'includes/init-class.php';

// Include classes
require_once OSPROJECTS_PLUGIN_PATH . 'includes/class-settings.php';
require_once OSPROJECTS_PLUGIN_PATH . 'includes/class-project.php';

// Include helpers
require_once OSPROJECTS_PLUGIN_PATH . 'includes/helpers/class-git.php';
require_once OSPROJECTS_PLUGIN_PATH . 'includes/helpers/class-admin-import.php'; // Added line

// Initialize the main plugin class
$OSprojects = new OSProjects();

// No need to initialize OSProjectsGit here, it is initialized only on demand

// Initialize classes
$OSProjectsSettings    = new OSProjectsSettings();
$OSProjectsProject     = new OSProjectsProject();
$OSProjectsAdminImport = new OSProjectsAdminImport();

// Add WP-CLI command for debugging ReclaimDetails
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once OSPROJECTS_PLUGIN_PATH . 'includes/class-cli-debug.php';
}
