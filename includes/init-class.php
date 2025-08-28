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
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 5 );
	}

	/**
	 * Add main menu and dashboard
	 */
	public function register_admin_menu() {
		// Add main menu
		add_menu_page(
			__( 'Open Source Projects', 'osprojects' ),
			__( 'Open Source Projects', 'osprojects' ),
			'manage_options',
			'osprojects',
			array( $this, 'dashboard' ),
			$this->get_menu_icon(),
			6
		);

		// Temporary disabled until fully implemented
		// // Add dashboard
		// add_submenu_page(
		// 'osprojects',
		// __( 'Dashboard', 'osprojects' ),
		// __( 'Dashboard', 'osprojects' ),
		// 'manage_options',
		// 'osprojects',
		// array( $this, 'dashboard' ),
		// 0
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
	 * Get SVG menu icon as data URI
	 */
	private function get_menu_icon() {
		$svg_file = OSPROJECTS_PLUGIN_PATH . 'assets/menu-icon.svg';
		
		// Check if the SVG file exists
		if ( ! file_exists( $svg_file ) ) {
			// Fallback to dashicon if SVG file is missing
			return 'dashicons-editor-code';
		}
		
		// Read the SVG file content
		$svg_content = file_get_contents( $svg_file );
		
		// Return as data URI
		return 'data:image/svg+xml;base64,' . base64_encode( $svg_content );
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
	 * @param mixed  $default
	 * @return mixed
	 */
	public static function get_option( $option_name, $default = false ) {
		$options = get_option( 'osprojects-settings' );
		return isset( $options[ $option_name ] ) ? $options[ $option_name ] : $default;
	}

	/**
	 * Update option in osprojects-settings
	 *
	 * @param string $option_name
	 * @param mixed  $value
	 * @return bool
	 */
	public static function update_option( $option_name, $value ) {
		$options                 = get_option( 'osprojects-settings' );
		$options[ $option_name ] = $value;
		return update_option( 'osprojects-settings', $options );
	}

	/**
	 * Replicate enqueue_styles to share the same default args
	 */
	public static function enqueue_styles( $handle, $src = '', $deps = array(), $ver = OSPROJECTS_PLUGIN_VERSION, $media = 'all' ) {
		if ( defined( 'OSP_DEBUG' ) && OSP_DEBUG ) {
			$ver = filemtime( OSPROJECTS_PLUGIN_PATH . $src );
		}
		$src = OSPROJECTS_PLUGIN_URL . $src;
		if ( is_admin() ) {
			add_action(
				'admin_enqueue_scripts',
				function () use ( $handle, $src, $deps, $ver, $media ) {
					wp_enqueue_style( $handle, $src, $deps, $ver, $media );
				}
			);
		} else {
			add_action(
				'wp_enqueue_scripts',
				function () use ( $handle, $src, $deps, $ver, $media ) {
					wp_enqueue_style( $handle, $src, $deps, $ver, $media );
				}
			);
		}
	}

	/**
	 * Replicate enqueue_scripts to share the same default args
	 */
	public static function enqueue_scripts( $handle, $src = '', $deps = array(), $ver = OSPROJECTS_PLUGIN_VERSION, $in_footer = false ) {
		if ( defined( 'OSP_DEBUG' ) && OSP_DEBUG ) {
			$ver = filemtime( OSPROJECTS_PLUGIN_PATH . $src );
		}
		$src = OSPROJECTS_PLUGIN_URL . $src;
		if ( is_admin() ) {
			add_action(
				'admin_enqueue_scripts',
				function () use ( $handle, $src, $deps, $ver, $in_footer ) {
					wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
				}
			);
		} else {
			add_action(
				'wp_enqueue_scripts',
				function () use ( $handle, $src, $deps, $ver, $in_footer ) {
					wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
				}
			);
		}
	}

	/**
	 * Format date based on site preferences.
	 *
	 * @param DateTime|string $date Date object or date string.
	 * @return string Formatted date.
	 */
	public static function date( $date ) {
		if ( ! $date instanceof DateTime ) {
			$date = new DateTime( $date );
		}
		return wp_date( get_option( 'date_format' ), $date->getTimestamp() );
	}

	/**
	 * Format date and time based on site preferences.
	 *
	 * @param DateTime|string $date Date object or date string.
	 * @return string Formatted date and time.
	 */
	public static function date_time( $date ) {
		if ( ! $date instanceof DateTime ) {
			$date = new DateTime( $date );
		}
		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date->getTimestamp() );
	}

	/**
	 * Format time based on site preferences.
	 *
	 * @param DateTime|string $time Date object or date string.
	 * @return string Formatted time.
	 */
	public static function time( $time ) {
		if ( ! $time instanceof DateTime ) {
			$time = new DateTime( $time );
		}
		return wp_date( get_option( 'time_format' ), $time->getTimestamp() );
	}
}
