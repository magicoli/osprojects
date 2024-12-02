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

// Constants
define( 'OSPROJECTS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'OSPROJECTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OSPROJECTS_PLUGIN_VERSION', '0.1.0' );

// Include the main plugin class
require_once OSPROJECTS_PLUGIN_PATH . 'includes/init-class.php';

// Include classes
require_once OSPROJECTS_PLUGIN_PATH . 'includes/class-settings.php';
require_once OSPROJECTS_PLUGIN_PATH . 'includes/class-project.php';

// Include helpers
require_once OSPROJECTS_PLUGIN_PATH . 'includes/helpers/class-git.php';

// Initialize the main plugin class
$OSprojects = new OSProjects();

// No need to initialize OSProjectsGit here, it is initialized only on demand

// Initialize classes
$OSProjectsSettings = new OSProjectsSettings();
$OSProjectsProject = new OSProjectsProject();
