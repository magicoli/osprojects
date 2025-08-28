<?php
/**
 * Unified repository checking logic
 * This method should be used by all import, save, and update operations
 */

class OSProjectsRepositoryChecker {

	/**
	 * Check repository status with unified logic
	 *
	 * @param string $repo_url Repository URL to check
	 * @return array Status information with keys: status, error, final_url, action
	 */
	public static function check_repository_status( $repo_url ) {
		// Only handle HTTP(S) URLs
		if ( ! filter_var( $repo_url, FILTER_VALIDATE_URL ) || ! preg_match( '/^https?:\/\//', $repo_url ) ) {
			return array(
				'status'    => 'invalid_url',
				'error'     => 'Invalid URL format',
				'final_url' => $repo_url,
				'action'    => 'reject',
			);
		}

		// Use WordPress HTTP API to check the repository
		$response = wp_remote_head(
			$repo_url,
			array(
				'timeout'     => 10,
				'redirection' => 5, // Follow up to 5 redirects
				'user-agent'  => 'OSProjectsPlugin',
			)
		);

		// If request failed, return error
		if ( is_wp_error( $response ) ) {
			return array(
				'status'    => 'network_error',
				'error'     => 'Network error: ' . $response->get_error_message(),
				'final_url' => $repo_url,
				'action'    => 'skip', // Don't ignore, might be temporary
			);
		}

		// Check HTTP status code
		$status_code = wp_remote_retrieve_response_code( $response );

		// Handle 4xx errors (client errors like 404, 403, etc.)
		if ( $status_code >= 400 && $status_code < 500 ) {
			$error_messages = array(
				404 => 'Repository not found (404)',
				403 => 'Access forbidden (403)',
				401 => 'Unauthorized access (401)',
			);
			$error_message  = isset( $error_messages[ $status_code ] )
				? $error_messages[ $status_code ]
				: "Client error ($status_code)";

			return array(
				'status'    => 'client_error',
				'error'     => $error_message,
				'final_url' => $repo_url,
				'action'    => 'ignore', // Set to ignored status
			);
		}

		// Handle 5xx errors (server errors)
		if ( $status_code >= 500 ) {
			return array(
				'status'    => 'server_error',
				'error'     => "Server error ($status_code)",
				'final_url' => $repo_url,
				'action'    => 'skip', // Don't ignore, might be temporary
			);
		}

		// Check for redirects
		$final_url  = self::get_final_url_after_redirects( $repo_url, $response );
		$redirected = ( $final_url !== $repo_url );

		if ( $redirected ) {
			// Check if the redirect target already exists
			$existing_project_id = OSProjectsProject::get_repo_project_id( $final_url );
			if ( $existing_project_id !== false ) {
				return array(
					'status'              => 'redirect_exists',
					'error'               => null,
					'final_url'           => $final_url,
					'action'              => 'ignore',
					'existing_project_id' => $existing_project_id,
					'redirected'          => true,
				);
			}

			return array(
				'status'     => 'redirect_available',
				'error'      => null,
				'final_url'  => $final_url,
				'action'     => 'import_with_new_url',
				'redirected' => true,
			);
		}

		// Check if the original URL already exists
		$existing_project_id = OSProjectsProject::get_repo_project_id( $repo_url );
		if ( $existing_project_id !== false ) {
			return array(
				'status'              => 'exists',
				'error'               => null,
				'final_url'           => $repo_url,
				'action'              => 'skip',
				'existing_project_id' => $existing_project_id,
				'redirected'          => false,
			);
		}

		return array(
			'status'     => 'available',
			'error'      => null,
			'final_url'  => $repo_url,
			'action'     => 'import',
			'redirected' => false,
		);
	}

	/**
	 * Get the final URL after following redirects
	 * WordPress wp_remote_head follows redirects automatically, but we need to detect the final URL
	 */
	private static function get_final_url_after_redirects( $original_url, $response ) {
		// WordPress HTTP API follows redirects automatically
		// We need to make a second request to get the effective URL
		$response_with_info = wp_remote_get(
			$original_url,
			array(
				'timeout'     => 10,
				'redirection' => 5,
				'user-agent'  => 'OSProjectsPlugin',
			)
		);

		if ( is_wp_error( $response_with_info ) ) {
			return $original_url;
		}

		// WordPress doesn't expose the effective URL directly
		// We'll use a different approach: check for redirect headers
		$headers = wp_remote_retrieve_headers( $response );
		if ( isset( $headers['location'] ) ) {
			$location = $headers['location'];
			if ( filter_var( $location, FILTER_VALIDATE_URL ) ) {
				// Remove trailing .git if present for consistency
				$location       = preg_replace( '/\.git$/', '', $location );
				$original_clean = preg_replace( '/\.git$/', '', $original_url );

				if ( $location !== $original_clean ) {
					return $location;
				}
			}
		}

		return $original_url;
	}

	/**
	 * Apply the repository check result to a project
	 *
	 * @param int    $post_id Project post ID
	 * @param array  $check_result Result from check_repository_status()
	 * @param string $original_url Original repository URL
	 * @return bool Success
	 */
	public static function apply_repository_check_result( $post_id, $check_result, $original_url ) {
		switch ( $check_result['action'] ) {
			case 'ignore':
				// Set project to ignored status
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_status' => 'ignored',
					)
				);
				update_post_meta( $post_id, 'osp_project_git_error', $check_result['error'] );
				update_post_meta( $post_id, 'osp_project_repository', $original_url );
				return false; // Don't continue with git operations

			case 'import_with_new_url':
				// Update to the redirect target URL
				update_post_meta( $post_id, 'osp_project_repository', $check_result['final_url'] );
				return true; // Continue with git operations using new URL

			case 'import':
				// Proceed normally
				update_post_meta( $post_id, 'osp_project_repository', $check_result['final_url'] );
				return true;

			case 'skip':
				// Don't proceed, but don't mark as ignored either
				return false;

			case 'reject':
			default:
				// Invalid URL, set error but don't mark as ignored
				update_post_meta( $post_id, 'osp_project_git_error', $check_result['error'] );
				return false;
		}
	}
}
