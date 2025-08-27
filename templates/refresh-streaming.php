<?php
// Streaming refresh page for OSProjects within the WP Admin UI
// Included from admin_post_osprojects_manual_refresh handler.

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Insufficient permissions', 'osprojects' ) ); }

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

			// Reuse the existing update logic from the CPT class
			$meta_data = array( 'osp_project_repository' => $repo_url );
			if ( method_exists( $project_obj, 'update_project_meta_fields' ) ) {
				$project_obj->update_project_meta_fields( $post_id, $meta_data );
			}

			$ok++;
			echo '<span class="updated">' . esc_html__( 'OK', 'osprojects' ) . '</span><br />' . "\n";
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

