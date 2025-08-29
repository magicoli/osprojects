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
		
		// Load ReclaimDetails
		require_once OSPROJECTS_PLUGIN_PATH . 'lib/reclaim-details/vendor/autoload.php';
		
		try {
			// Use the correct constructor - it expects readme.txt path and plugin file path
			$readme_path = OSPROJECTS_PLUGIN_PATH . 'readme.txt';
			$plugin_file = OSPROJECTS_PLUGIN_PATH . 'osprojects.php';
			
			WP_CLI::line( "README path: $readme_path" );
			WP_CLI::line( "Plugin file: $plugin_file" );
			
			if ( ! file_exists( $readme_path ) ) {
				WP_CLI::error( "README file not found: $readme_path" );
				return;
			}
			
			$details = new \Reclaim\Details\ReclaimDetails( $readme_path, $plugin_file );
			
			// Test screenshots
			WP_CLI::line( "\n1. Testing Screenshots:" );
			WP_CLI::line( str_repeat( '-', 30 ) );
			
			$reflection = new ReflectionClass( $details );
			$method = $reflection->getMethod( 'get_screenshots' );
			$method->setAccessible( true );
			$screenshots = $method->invoke( $details );
			
			if ( empty( $screenshots ) ) {
				WP_CLI::warning( "No screenshots found!" );
			} else {
				WP_CLI::success( "Found " . count( $screenshots ) . " screenshots:" );
				foreach ( $screenshots as $num => $screenshot ) {
					WP_CLI::line( "  Screenshot $num:" );
					WP_CLI::line( "    src: " . $screenshot['src'] );
					WP_CLI::line( "    caption: " . $screenshot['caption'] );
				}
				
				// Debug screenshot captions
				WP_CLI::line( "\nDebugging screenshot captions:" );
				
				// Get readme data from the details object
				$readme_property = $reflection->getProperty( 'readme_data' );
				$readme_property->setAccessible( true );
				$readme_data = $readme_property->getValue( $details );
				
				if ( isset( $readme_data['sections'] ) ) {
					WP_CLI::line( "Available sections: " . implode( ', ', array_keys( $readme_data['sections'] ) ) );
				} else {
					WP_CLI::warning( "No sections found in readme_data" );
				}
				
				// Try different case variations
				$screenshots_content = null;
				foreach ( ['Screenshots', 'screenshots', 'SCREENSHOTS'] as $key ) {
					if ( isset( $readme_data['sections'][$key] ) ) {
						$screenshots_content = $readme_data['sections'][$key];
						WP_CLI::line( "Found screenshots section with key: '$key'" );
						break;
					}
				}
				
				if ( $screenshots_content ) {
					WP_CLI::line( "Screenshots section content:" );
					WP_CLI::line( "  " . str_replace( "\n", "\n  ", $screenshots_content ) );
					
					$lines = explode( "\n", $screenshots_content );
					foreach ( $lines as $i => $line ) {
						$trimmed = trim( $line );
						if ( !empty( $trimmed ) ) {
							WP_CLI::line( "  Line $i: '" . $trimmed . "'" );
							if ( preg_match( '/^(\d+)\.\s*(.+)$/', $trimmed, $matches ) ) {
								WP_CLI::line( "    Matches: num=" . $matches[1] . ", caption=" . $matches[2] );
							}
						}
					}
				} else {
					WP_CLI::warning( "No Screenshots section found in any case variation" );
				}
			}
			
			// Test readme parsing
			WP_CLI::line( "\n2. Testing README parsing:" );
			WP_CLI::line( str_repeat( '-', 30 ) );
			
			$readme_property = $reflection->getProperty( 'readme_data' );
			$readme_property->setAccessible( true );
			$readme_data = $readme_property->getValue( $details );
			
			if ( empty( $readme_data ) ) {
				WP_CLI::warning( "No readme data found!" );
			} else {
				WP_CLI::success( "README data loaded successfully" );
				WP_CLI::line( "  Name: " . ( $readme_data['name'] ?? 'N/A' ) );
				WP_CLI::line( "  Short description: " . ( $readme_data['short_description'] ?? 'N/A' ) );
				
				if ( isset( $readme_data['sections'] ) ) {
					WP_CLI::line( "  Sections found: " . implode( ', ', array_keys( $readme_data['sections'] ) ) );
					
					// Test for Unicode issues in sections
					foreach ( $readme_data['sections'] as $section_name => $content ) {
						if ( preg_match( '/[^\x00-\x7F]/', $content ) ) {
							WP_CLI::line( "  Unicode characters found in section '$section_name'" );
							// Show first 100 chars to check encoding
							$preview = substr( $content, 0, 100 );
							WP_CLI::line( "    Preview: " . $preview );
						}
					}
				}
			}
			
			// Test plugin info generation
			WP_CLI::line( "\n3. Testing Plugin Info Generation:" );
			WP_CLI::line( str_repeat( '-', 30 ) );
			
			// Simulate the plugin_information filter
			$plugin_info = $details->handle_plugin_info( 
				false, 
				'plugin_information', 
				(object) array( 'slug' => $plugin_slug )
			);
			
			if ( $plugin_info ) {
				WP_CLI::success( "Plugin info generated successfully" );
				WP_CLI::line( "  Name: " . ( $plugin_info->name ?? 'N/A' ) );
				WP_CLI::line( "  Version: " . ( $plugin_info->version ?? 'N/A' ) );
				WP_CLI::line( "  Screenshots count: " . ( is_array( $plugin_info->screenshots ) ? count( $plugin_info->screenshots ) : '0' ) );
				
				// Check sections for Unicode
				if ( isset( $plugin_info->sections ) ) {
					foreach ( $plugin_info->sections as $section_name => $content ) {
						if ( preg_match( '/[^\x00-\x7F]/', $content ) ) {
							WP_CLI::warning( "Unicode characters detected in final output section '$section_name'" );
						}
					}
				}
			} else {
				WP_CLI::error( "Failed to generate plugin info" );
			}
			
		} catch ( Exception $e ) {
			WP_CLI::error( "Error: " . $e->getMessage() );
		}
	}

	/**
	 * Test screenshot detection specifically
	 *
	 * ## EXAMPLES
	 *
	 *     wp osprojects test-screenshots
	 */
	public function test_screenshots( $args, $assoc_args ) {
		WP_CLI::line( "Testing screenshot detection..." );
		
		// Check assets directory
		$assets_dir = OSPROJECTS_PLUGIN_PATH . 'assets';
		WP_CLI::line( "Assets directory: $assets_dir" );
		
		if ( ! is_dir( $assets_dir ) ) {
			WP_CLI::error( "Assets directory does not exist!" );
			return;
		}
		
		// List all files in assets
		$files = glob( $assets_dir . '/*' );
		WP_CLI::line( "Files in assets directory:" );
		foreach ( $files as $file ) {
			$basename = basename( $file );
			$size = filesize( $file );
			WP_CLI::line( "  $basename (" . number_format( $size ) . " bytes)" );
		}
		
		// Test glob pattern specifically
		$screenshot_pattern = $assets_dir . '/screenshot-*.{png,jpg,jpeg,gif}';
		$screenshots = glob( $screenshot_pattern, GLOB_BRACE );
		
		WP_CLI::line( "\nScreenshot pattern: $screenshot_pattern" );
		WP_CLI::line( "Screenshots found by glob:" );
		foreach ( $screenshots as $screenshot ) {
			WP_CLI::line( "  " . basename( $screenshot ) );
		}
	}
}

// Register WP-CLI commands
WP_CLI::add_command( 'osprojects', 'OSProjects_CLI_Debug' );
