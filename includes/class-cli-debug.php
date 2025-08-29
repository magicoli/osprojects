<?php
/**
 * WP-CLI Commands for debugging OSProjects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OSProjects_CLI_Debug {

	/**
	 * Test ReclaimDetails functionality
	 *
	 * ## OPTIONS
	 *
	 * [--plugin=<plugin>]
	 * : Plugin slug to test (default: osprojects)
	 *
	 * ## EXAMPLES
	 *
	 *     wp osprojects test-details
	 *     wp osprojects test-details --plugin=osprojects
	 */
	public function test_details( $args, $assoc_args ) {
		$plugin_slug = isset( $assoc_args['plugin'] ) ? $assoc_args['plugin'] : 'osprojects';
		
		WP_CLI::line( "Testing ReclaimDetails for plugin: $plugin_slug" );
		WP_CLI::line( str_repeat( '=', 50 ) );
		
		// Test the actual WordPress plugins_api processing
		$args_obj = (object) array('slug' => $plugin_slug);
		$result = apply_filters('plugins_api', false, 'plugin_information', $args_obj);
		
		if ( $result && !is_wp_error( $result ) ) {
			WP_CLI::success( "WordPress plugins_api returned data" );
			WP_CLI::line( "Name: " . ( $result->name ?? 'N/A' ) );
			WP_CLI::line( "Version: " . ( $result->version ?? 'N/A' ) );
			WP_CLI::line( "Author: " . ( strip_tags( $result->author ?? 'N/A' ) ) );
			WP_CLI::line( "Requires: " . ( $result->requires ?? 'N/A' ) );
			WP_CLI::line( "Tested: " . ( $result->tested ?? 'N/A' ) );
			
			// Check sections
			if ( isset( $result->sections ) ) {
				WP_CLI::line( "\nSections: " . implode( ', ', array_keys( $result->sections ) ) );
			} else {
				WP_CLI::warning( "No sections found" );
			}
			
			// Check screenshots
			if ( isset( $result->screenshots ) && is_array( $result->screenshots ) ) {
				WP_CLI::line( "Screenshots: " . count( $result->screenshots ) . " items" );
			} else {
				WP_CLI::line( "Screenshots: None" );
			}
			
		} else {
			WP_CLI::error( "WordPress plugins_api returned no data or error" );
		}
	}



	/**
	 * Test the actual WordPress plugins_api processing
	 *
	 * ## EXAMPLES
	 *
	 *     wp osprojects test_wordpress_api
	 */
	public function test_wordpress_api( $args, $assoc_args ) {
		WP_CLI::line( "Testing actual WordPress plugins_api processing..." );
		
		// Simulate exactly what WordPress does for plugin information
		$args_obj = (object) array('slug' => 'osprojects');
		$result = apply_filters('plugins_api', false, 'plugin_information', $args_obj);
		
		if ( $result && !is_wp_error( $result ) ) {
			WP_CLI::success( "WordPress plugins_api returned data" );
			WP_CLI::line( "Name: " . ( $result->name ?? 'N/A' ) );
			WP_CLI::line( "Version: " . ( $result->version ?? 'N/A' ) );
			
			// Check screenshots specifically
			WP_CLI::line( "\nScreenshots analysis:" );
			if ( isset( $result->screenshots ) ) {
				if ( is_array( $result->screenshots ) ) {
					WP_CLI::line( "Screenshots: Array with " . count( $result->screenshots ) . " items" );
					foreach ( $result->screenshots as $num => $screenshot ) {
						WP_CLI::line( "  $num: " . $screenshot['src'] . " - " . $screenshot['caption'] );
					}
				} else {
					WP_CLI::line( "Screenshots: Not an array - " . gettype( $result->screenshots ) );
				}
			} else {
				WP_CLI::warning( "Screenshots: Property not set" );
			}
			
			// Check sections
			WP_CLI::line( "\nSections analysis:" );
			if ( isset( $result->sections ) ) {
				foreach ( $result->sections as $name => $content ) {
					$preview = substr( strip_tags( $content ), 0, 100 );
					WP_CLI::line( "  $name: $preview..." );
				}
			} else {
				WP_CLI::warning( "No sections found" );
			}
			
		} else {
			WP_CLI::error( "WordPress plugins_api returned no data or error" );
		}
	}
}

// Register WP-CLI commands
WP_CLI::add_command( 'osprojects', 'OSProjects_CLI_Debug' );
