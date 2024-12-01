<?php
/**
 * Class OSProjects
 * 
 * Main OSProjects class, includes global initialization, actions, filters and scripts.
**/

class OSProjects {
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
        // Load plugin text domain
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

        // Add main menu and dashboard
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
    }

    /**
     * Add main menu and dashboard
     */
    public function add_menu() {
        // Add main menu
        add_menu_page(
            __( 'Open Source Projects', 'osprojects' ),
            __( 'Open Source Projects', 'osprojects' ),
            'manage_options',
            'osprojects',
            array( $this, 'dashboard' ),
            'dashicons-admin-generic',
            6
        );

        # Temporary disabled until fully implemented
        // // Add dashboard
        // add_submenu_page(
        //     'osprojects',
        //     __( 'Dashboard', 'osprojects' ),
        //     __( 'Dashboard', 'osprojects' ),
        //     'manage_options',
        //     'osprojects',
        //     array( $this, 'dashboard' ),
        //     0
        // );
    }

    /**
     * Dashboard
     */
    public function dashboard() {
        // Load the dashboard template
        require_once OSPROJECTS_PLUGIN_PATH . 'templates/dashboard.php';
    }

    /**
     * Load the plugin text domain for translation
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'osprojects', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Get option from osprojects-settings
     *
     * @param string $option_name
     * @param mixed $default
     * @return mixed
     */
    public static function get_option($option_name, $default = false) {
        $options = get_option('osprojects-settings');
        return isset($options[$option_name]) ? $options[$option_name] : $default;
    }

    /**
     * Update option in osprojects-settings
     *
     * @param string $option_name
     * @param mixed $value
     * @return bool
     */
    public static function update_option($option_name, $value) {
        $options = get_option('osprojects-settings');
        $options[$option_name] = $value;
        return update_option('osprojects-settings', $options);
    }
}
