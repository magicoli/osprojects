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
    const DEFAULT_enable_gutenberg = true;

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
        add_action( 'admin_menu', array( $this, 'register_admin_submenus' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Set default project URL prefix if not set
        $options = get_option( 'osprojects-settings' );
        if ( ! isset( $options['project_url_prefix'] ) ) {
            $options['project_url_prefix'] = self::DEFAULT_PROJECT_URL_PREFIX;
            update_option( 'osprojects-settings', $options );
        }

        // Set default Gutenberg block edit-mode if not set
        if ( ! isset( $options['enable_gutenberg'] ) ) {
            $options['enable_gutenberg'] = self::DEFAULT_enable_gutenberg;
            update_option( 'osprojects-settings', $options );
        }

        // Schedule daily project refresh if not scheduled
        if ( ! wp_next_scheduled( 'osprojects_daily_project_refresh' ) ) {
            wp_schedule_event( time(), 'daily', 'osprojects_daily_project_refresh' );
        }

    // Hook the refresh action
    add_action( 'osprojects_daily_project_refresh', array( $this, 'handle_scheduled_refresh' ) );

    // Handle manual refresh via admin-post (streaming page)
    add_action( 'admin_post_osprojects_manual_refresh', array( $this, 'handle_manual_refresh' ) );
    }

    public function handle_scheduled_refresh() {
        if ( class_exists( 'OSProjectsProject' ) && method_exists( 'OSProjectsProject', 'refresh_all_projects' ) ) {
            OSProjectsProject::refresh_all_projects();
        }
    }

    public function handle_manual_refresh() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient permissions', 'osprojects' ) );
        }

        check_admin_referer( 'osprojects_manual_refresh' );

        // Render streaming progress page directly
        require_once OSPROJECTS_PLUGIN_PATH . 'templates/refresh-streaming.php';
        exit;
    }

    public function register_settings() {
        register_setting( 'osprojects-settings-group', 'osprojects-settings' );

        add_settings_section(
            'osprojects-settings-section',
            '', // __( 'OSProjects Settings', 'osprojects' ),
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

        add_settings_field(
            'enable_gutenberg',
            __( 'Enable Gutenberg', 'osprojects' ),
            array( $this, 'enable_gutenberg_callback' ),
            'osprojects-settings',
            'osprojects-settings-section'
        );
    }

    public function project_url_prefix_callback() {
        $options = get_option( 'osprojects-settings' );
        $project_url_prefix = isset( $options['project_url_prefix'] ) ? $options['project_url_prefix'] : self::DEFAULT_PROJECT_URL_PREFIX;
        echo '<input type="text" name="osprojects-settings[project_url_prefix]" value="' . esc_attr( $project_url_prefix ) . '" />';
    }

    public function enable_gutenberg_callback() {
        $options = get_option( 'osprojects-settings' );
        $enable_gutenberg = isset( $options['enable_gutenberg'] ) ? $options['enable_gutenberg'] : self::DEFAULT_enable_gutenberg;
        echo '<input type="checkbox" name="osprojects-settings[enable_gutenberg]" value="1"' . checked( 1, $enable_gutenberg, false ) . ' /> ' . __( 'Gutenberg blocs mode. Disable to use classic edit page.', 'osprojects' );
        echo '<p class="description">' . __( 'In some cases, Gutenberg edit-mode can cause saving to hang. Try deleting cookies before disabling Gutenberg.', 'osprojects' ) . '</p>';
    }

    /**
     * Add settings page to OSProjects menu
     */
    public function register_admin_submenus() {
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
        require_once OSPROJECTS_PLUGIN_PATH . 'templates/settings.php';
    }
}

