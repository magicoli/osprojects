<?php
// Streaming refresh page for OSProjects within the WP Admin UI
// Included from admin_post_osprojects_manual_refresh handler.

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Insufficient permissions', 'osprojects' ) ); }

// Repository checking functions
function check_repository_status( $repo_url, $post_id = null ) {
	$result = array(
		'status' => 'unknown',
		'url' => $repo_url,
		'redirected' => false,
		'final_url' => $repo_url,
		'project_id' => null,
		'error' => null
	);

	// Check HTTP status of repository URL
	$response = wp_remote_head( $repo_url, array(
		'timeout' => 10,
		'redirection' => 5,
		'user-agent' => 'OSProjects/1.0'
	) );

	if ( is_wp_error( $response ) ) {
		$result['status'] = 'network_error';
		$result['error'] = $response->get_error_message();
		return $result;
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	$final_url = wp_remote_retrieve_header( $response, 'location' );
	
	// If no Location header, check if URL was redirected by comparing with original
	if ( empty( $final_url ) ) {
		$final_url = $repo_url;
	} else {
		$result['redirected'] = true;
		$result['final_url'] = $final_url;
	}

	// Handle different status codes
	if ( $status_code >= 200 && $status_code < 300 ) {
		// Success - check for redirects and duplicates
		if ( $result['redirected'] ) {
			// Check if redirect target is used by another project
			$existing_project_id = get_repo_project_id( $final_url );
			if ( $existing_project_id !== false && $existing_project_id != $post_id ) {
				$result['status'] = 'redirect_exists';
				$result['project_id'] = $existing_project_id;
			} else {
				$result['status'] = 'redirect_new';
			}
		} else {
			$result['status'] = 'accessible';
		}
	} elseif ( $status_code >= 300 && $status_code < 400 ) {
		// Redirects - follow them
		$redirect_response = wp_remote_get( $repo_url, array(
			'timeout' => 10,
			'redirection' => 5,
			'user-agent' => 'OSProjects/1.0'
		) );
		
		if ( ! is_wp_error( $redirect_response ) ) {
			$redirect_code = wp_remote_retrieve_response_code( $redirect_response );
			$redirect_url = wp_remote_retrieve_header( $redirect_response, 'location' );
			
			if ( $redirect_code >= 200 && $redirect_code < 300 ) {
				$result['redirected'] = true;
				$result['final_url'] = $redirect_url ?: $repo_url;
				
				// Check if redirect target is used by another project
				$existing_project_id = get_repo_project_id( $result['final_url'] );
				if ( $existing_project_id !== false && $existing_project_id != $post_id ) {
					$result['status'] = 'redirect_exists';
					$result['project_id'] = $existing_project_id;
				} else {
					$result['status'] = 'redirect_new';
				}
			}
		}
	} elseif ( $status_code == 404 ) {
		$result['status'] = 'not_found';
		$result['error'] = 'Repository not found (404)';
	} elseif ( $status_code == 403 ) {
		$result['status'] = 'forbidden';
		$result['error'] = 'Repository access forbidden (403)';
	} elseif ( $status_code >= 500 ) {
		$result['status'] = 'server_error';
		$result['error'] = 'Server error (' . $status_code . ')';
	} else {
		$result['status'] = 'unknown';
		$result['error'] = 'Unknown HTTP status: ' . $status_code;
	}

	return $result;
}

function get_repo_project_id( $repo_url ) {
	$args = array(
		'post_type'      => 'project',
		'meta_query'     => array(
			array(
				'key'   => 'osp_project_repository',
				'value' => $repo_url,
			),
		),
		'fields'         => 'ids',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'ignored', 'trash' ),
	);
	$project_ids = get_posts( $args );
	if ( empty( $project_ids ) ) {
		return false;
	} else {
		return $project_ids[0];
	}
}

// Try to disable buffering for streaming output
@ini_set( 'output_buffering', 'off' );
@ini_set( 'zlib.output_compression', '0' );
if ( ! headers_sent() ) {
	header( 'X-Accel-Buffering: no' );
	header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
}
while ( ob_get_level() > 0 ) { @ob_end_flush(); }
@ob_implicit_flush( true );

ignore_user_abort( true );
@set_time_limit( 0 );

$back_url = admin_url( 'admin.php?page=osprojects-settings' );

// Helper to flush output cross-environment
$flush = function() {
	echo str_repeat( ' ', 1024 ); // push buffers
	@flush();
};

// Fetch all project IDs
$args = array(
	'post_type'      => 'project',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'orderby'        => 'ID',
	'order'          => 'ASC',
);
$project_ids = get_posts( $args );
$total = is_array( $project_ids ) ? count( $project_ids ) : 0;

require_once ABSPATH . 'wp-admin/admin-header.php';
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Refreshing Projects', 'osprojects' ); ?></h1>
	<p class="description">
		<?php
		if ( $total === 0 ) {
			esc_html_e( 'No projects found to refresh.', 'osprojects' );
		} else {
			echo esc_html( sprintf( __( 'Found %d projects. Starting refresh…', 'osprojects' ), $total ) );
		}
		?>
	</p>
	<div id="log" style="margin-top:12px;">
<?php
echo esc_html( '[' . date_i18n( 'Y-m-d H:i:s' ) . '] ' . __( 'Starting…', 'osprojects' ) ) . "<br />\n";
$flush();

$ok = 0; $fail = 0; $i = 0;
// Use the already-initialized global instance to avoid duplicate hook registration
global $OSProjectsProject;
if ( ! $OSProjectsProject instanceof OSProjectsProject ) {
	$OSProjectsProject = new OSProjectsProject();
}
$project_obj = $OSProjectsProject;

if ( $total === 0 ) {
	echo esc_html__( 'Nothing to do.', 'osprojects' ) . "<br />\n";
	$flush();
} else {
	foreach ( $project_ids as $post_id ) {
		$i++;
		// Compute a display title with fallbacks similar to queue processor
		$title = get_the_title( $post_id );
		if ( ! is_string( $title ) ) { $title = ''; }
		if ( trim( $title ) === '' ) {
			$repo_url_meta = get_post_meta( $post_id, 'osp_project_repository', true );
			if ( is_string( $repo_url_meta ) && trim( $repo_url_meta ) !== '' ) {
				$parsed = wp_parse_url( $repo_url_meta );
				if ( isset( $parsed['path'] ) ) {
					$path = trim( $parsed['path'], '/' );
					$parts = explode( '/', $path );
					$title = end( $parts );
				} else {
					$title = $repo_url_meta;
				}
			} else {
				$post_name = get_post_field( 'post_name', $post_id );
				$title = $post_name ? $post_name : ( 'Project #' . $post_id );
			}
		}

		echo esc_html( sprintf( '(%d/%d) %s … ', $i, $total, $title ) );
		$flush();

		try {
			$repo_url = get_post_meta( $post_id, 'osp_project_repository', true );
			if ( empty( $repo_url ) ) {
				$fail++;
				echo '<span class="error">' . esc_html__( 'SKIP: Missing repository URL', 'osprojects' ) . '</span><br />' . "\n";
				$flush();
				continue;
			}

			// Check repository status first to provide better feedback
			$status_result = check_repository_status( $repo_url, $post_id );
			
			// Reuse the existing update logic from the CPT class
			$meta_data = array( 'osp_project_repository' => $repo_url );
			if ( method_exists( $project_obj, 'update_project_meta_fields' ) ) {
				$project_obj->update_project_meta_fields( $post_id, $meta_data );
			}

			// Provide feedback based on repository status
			switch ( $status_result['status'] ) {
				case 'accessible':
					$ok++;
					echo '<span class="updated">' . esc_html__( 'OK', 'osprojects' ) . '</span><br />' . "\n";
					break;
				case 'redirect_new':
					$ok++;
					echo '<span class="updated">' . esc_html__( 'OK (redirected)', 'osprojects' ) . '</span><br />' . "\n";
					break;
				case 'redirect_exists':
					$fail++;
					echo '<span class="error">' . esc_html__( 'IGNORED: Redirects to existing project', 'osprojects' ) . '</span><br />' . "\n";
					break;
				case 'not_found':
					$fail++;
					echo '<span class="error">' . esc_html__( 'IGNORED: Repository not found (404)', 'osprojects' ) . '</span><br />' . "\n";
					break;
				case 'forbidden':
					$fail++;
					echo '<span class="error">' . esc_html__( 'IGNORED: Repository access forbidden', 'osprojects' ) . '</span><br />' . "\n";
					break;
				case 'network_error':
					$fail++;
					echo '<span class="error">' . esc_html__( 'IGNORED: Network error', 'osprojects' ) . '</span><br />' . "\n";
					break;
				case 'server_error':
					$fail++;
					echo '<span class="error">' . esc_html__( 'SKIP: Server error (will retry later)', 'osprojects' ) . '</span><br />' . "\n";
					break;
				default:
					$ok++;
					echo '<span class="updated">' . esc_html__( 'OK', 'osprojects' ) . '</span><br />' . "\n";
					break;
			}
			$flush();
		} catch ( Exception $e ) {
			$fail++;
			echo '<span class="error">' . esc_html__( 'FAIL', 'osprojects' ) . ': ' . esc_html( $e->getMessage() ) . '</span><br />' . "\n";
			$flush();
		} catch ( Error $e ) {
			$fail++;
			echo '<span class="error">' . esc_html__( 'FAIL', 'osprojects' ) . ': ' . esc_html( $e->getMessage() ) . '</span><br />' . "\n";
			$flush();
		}
	}
}

echo '<br />' . esc_html( sprintf( __( 'Completed: %d OK, %d failed (Total %d).', 'osprojects' ), $ok, $fail, $total ) ) . "<br />\n";
$flush();
?>
	</div>
	<p style="margin-top:16px;">
		<a class="button button-primary" href="<?php echo esc_url( $back_url ); ?>">&larr; <?php esc_html_e( 'Back to Settings', 'osprojects' ); ?></a>
	</p>
</div>
<?php require_once ABSPATH . 'wp-admin/admin-footer.php';

