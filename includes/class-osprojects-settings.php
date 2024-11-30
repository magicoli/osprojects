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
     * Default URL prefix
     */
    const DEFAULT_PROJECT_URL_PREFIX = 'projects';

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
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Set default project URL prefix if not set
        $options = get_option( 'osprojects-settings' );
        if ( ! isset( $options['project_url_prefix'] ) ) {
            $options['project_url_prefix'] = self::DEFAULT_PROJECT_URL_PREFIX;
            update_option( 'osprojects-settings', $options );
        }
    }

    public function register_settings() {
        register_setting( 'osprojects-settings-group', 'osprojects-settings' );

        add_settings_section(
            'osprojects-settings-section',
            __( 'OSProjects Settings', 'osprojects' ),
            null,
            'osprojects-settings'
        );

        add_settings_field(
            'project_url_prefix',
            __( 'Project URL Prefix', 'osprojects' ),
            array( $this, 'project_url_prefix_callback' ),
            'osprojects-settings',
            'osprojects-settings-section'
        );
    }

    public function project_url_prefix_callback() {
        $options = get_option( 'osprojects-settings' );
        $project_url_prefix = isset( $options['project_url_prefix'] ) ? $options['project_url_prefix'] : self::DEFAULT_PROJECT_URL_PREFIX;
        echo '<input type="text" name="osprojects-settings[project_url_prefix]" value="' . esc_attr( $project_url_prefix ) . '" />';
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

