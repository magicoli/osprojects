<?php
/**
 * Class OSProjectsSettings
 * 
 * Main OSProjectsSettings class, includes global initialization, actions, filters and scripts.
 * 
 * @package         osprojects
**/

class OSProjectsSettings {
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize the plugin
        add_action( 'init', array( $this, 'init' ) );
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Add settings page
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

    }

    /**
     * Add settings page to OSProjects menu
     */
    public function add_settings_page() {
        // Add settings page
        add_submenu_page(
            'osprojects',
            __( 'Settings', 'osprojects' ),
            __( 'Settings', 'osprojects' ),
            'manage_options',
            'osprojects-settings',
            array( $this, 'settings_page' )
        );
    }

    /**
     * Settings page
     */
    public function settings_page() {
        // Load the settings page template
        require_once OSPROJECTS_PATH . 'templates/settings.php';
    }
}
