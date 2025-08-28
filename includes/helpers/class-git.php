<?php
/**
 * Test file to check git-php implementation
 **/

/**
 * Class OSProjectsGit
 *
 * This class is used to interact with a Git repository using the git-php library.
 *
 * Public methods:
 * - last_commit(): Get the last commit hash and date.
 * - last_commit_date(): Get the last commit date.
 * - last_commit_hash(): Get the last commit hash.
 * - last_commit_hash_long(): Get the last commit long hash.
 * - last_commit_url(): Get the last commit URL.
 * - last_commit_html(): Get the last commit HTML link.
 * - last_tag(): Get the last tag.
 * - version(): Get the version.
 * - release_date(): Get the release date.
 * - download_url(): Get the download URL.
 * - last_release_html(): Get the last release HTML link.
 * - license(): Get the license.
 * - cleanup(): Remove the temporary directory.
 *
 * @package osprojects
 * @since 1.0.0
 */
class OSProjectsGit {

	private $git;
	private $repository;
	private $repo_url;
	private $repoPath;
	private $last_commit  = null;
	private $last_tag     = null;
	private $version      = null;
	private $release_date = null;
	private $download_url = null;
	private $license      = null;
	private $fileContents = array();

	public function __construct( $repo_url ) {
		if ( empty( $repo_url ) ) {
			return;
		}

		// Make sure $repo_url is a valid URL
		if ( ! filter_var( $repo_url, FILTER_VALIDATE_URL ) ) {
			return;
		}

		require_once OSPROJECTS_PLUGIN_PATH . 'vendor/autoload.php';

		// Set environment variable to ensure Git outputs messages in English
		putenv( 'LANG=C' );

		$this->git = new CzProject\GitPhp\Git();

		$this->repoPath = get_temp_dir() . 'osprojects_git_' . uniqid();

		// Create the directory if it doesn't exist
		if ( ! file_exists( dirname( $this->repoPath ) ) ) {
			mkdir( dirname( $this->repoPath ), 0755, true );
		}

		try {
			// Limit clone depth to speed up cloning and prevent timeouts
			$this->repository = $this->git->cloneRepository(
				$repo_url,
				$this->repoPath,
				array(
					'--depth'         => '1',
					'--single-branch' => null,
				)
			);
		} catch ( CzProject\GitPhp\GitException $e ) {
			// error_log ( 'Git clone failed: ' . $e->getMessage() );
			$this->repository = null;
			return;
		}

		// Hook the cleanup method to WordPress shutdown within the class
		add_action( 'shutdown', array( $this, 'cleanup' ) );

		$this->repo_url = $repo_url;
		$this->loadRepositoryFiles();
	}

	private function loadRepositoryFiles() {
		if ( empty( $this->repository ) ) {
			return;
		}

		$files = array( 'composer.json', 'package.json', 'readme.txt', 'README.md' );
		foreach ( $files as $file ) {
			$filePath = $this->repoPath . '/' . $file;
			if ( file_exists( $filePath ) ) {
				$this->fileContents[ $file ] = file_get_contents( $filePath );
			} else {
				$this->fileContents[ $file ] = null;
			}
		}
	}

	public function last_commit() {
		if ( $this->repository === null ) {
			return false;
		}
		if ( ! empty( $this->last_commit ) ) {
			return $this->last_commit;
		}

		// Use execute() and handle the array output
		$logArray = $this->repository->execute( 'log', '-1', '--format=%H %cd' );
		if ( ! empty( $logArray ) ) {
			$commit   = array();
			$logArray = explode( ' ', $logArray[0] );

			// Format the date
			$date           = new DateTime( $logArray[1] );
			$commit['date'] = OSProjects::date_time( $date );

			// Format the commit hash
			$commit['hash_long'] = $logArray[0];
			$commit['hash']      = substr( $logArray[0], 0, 7 );
			$this->last_commit   = $commit;
		} else {
			$this->last_commit = false;
		}
		return $this->last_commit;
	}

	public function last_commit_date() {
		if ( empty( $this->last_commit() ) ) {
			return false;
		}
		return $this->last_commit['date'];
	}

	public function last_commit_hash() {
		if ( empty( $this->last_commit() ) ) {
			return false;
		}
		return $this->last_commit['hash'];
	}

	public function last_commit_hash_long() {
		if ( empty( $this->last_commit() ) ) {
			return false;
		}
		return $this->last_commit['hash_long'];
	}

	public function last_commit_url() {
		if ( empty( $this->last_commit() ) ) {
			return false;
		}
		return $this->repo_url . '/commit/' . $this->last_commit['hash_long'];
	}

	public function last_commit_html() {
		if ( empty( $this->last_commit() ) ) {
			return false;
		}
		$html = sprintf(
			'<a href="%s/commit/%s">%s</a> (%s)',
			$this->repo_url,
			$this->last_commit_hash_long(),
			$this->last_commit_hash(),
			$this->last_commit_date(),
		);
		return $html;
	}

	public function last_tag() {
		if ( ! empty( $this->last_tag ) ) {
			return $this->last_tag;
		}
		if ( $this->last_tag === false ) {
			return false;
		}

		if ( $this->repository === null ) {
			return false;
		}

		// Adjusted to handle shallow clones
		$tagsArray = $this->repository->execute( 'tag', '-l', '--sort=-creatordate' );
		$tags      = array_filter( $tagsArray );

		if ( ! empty( $tags ) ) {
			$this->last_tag = trim( $tags[0] );
		} else {
			$this->last_tag = false;
		}
		return $this->last_tag;
	}

	public function version() {
		if ( ! empty( $this->version ) ) {
			return $this->version;
		}
		if ( empty( $this->last_tag() ) ) {
			return false;
		}

		$this->version = $this->last_tag;
		return $this->version;
	}

	public function release_date() {
		if ( ! empty( $this->release_date ) ) {
			return $this->release_date;
		}
		if ( empty( $this->last_tag() ) ) {
			return false;
		}

		$dateArray  = $this->repository->execute( 'log', '-1', '--format=%cd', $this->last_tag );
		$dateString = isset( $dateArray[0] ) ? $dateArray[0] : '';
		$date       = new DateTime( $dateString );

		return OSProjects::date( $date );
	}

	/**
	 * Construct the download link based on the last tag and the repository URL.
	 *
	 * @return string|false Download URL or false if unable to construct.
	 */
	public function download_url() {
		if ( ! empty( $this->download_url ) ) {
			return $this->download_url;
		}
		if ( empty( $this->last_tag() ) ) {
			return false;
		}

		// Use $this->repo_url to construct the download link
		$parsed     = parse_url( $this->repo_url );
		$path       = trim( $parsed['path'], '/' );
		$path_parts = explode( '/', $path );
		$platform   = $parsed['host'];
		$owner      = $path_parts[0];
		$repo       = $path_parts[1];

		switch ( $platform ) {
			case 'github.com':
				$this->download_url = "https://github.com/$owner/$repo/archive/refs/tags/{$this->last_tag}.zip";
				break;
			case 'gitlab.com':
				$this->download_url = "https://gitlab.com/$owner/$repo/-/archive/{$this->last_tag}/$repo-{$this->last_tag}.zip";
				break;
			case 'bitbucket.com':
				$this->download_url = "https://bitbucket.org/$owner/$repo/get/{$this->last_tag}.zip";
				break;
			default:
				$this->download_url = false;
				break;
		}

		return $this->download_url;
	}

	function last_release_html() {
		if ( empty( $this->last_tag() ) ) {
			return false;
		}
		return sprintf(
			'<a href="%s">%s</a> (%s)',
			$this->download_url(),
			$this->version(),
			$this->release_date(),
		);
	}

	public function get_license_info() {
		$standard_files = array( 'composer.json', 'package.json', 'readme.txt' );
		foreach ( $standard_files as $file ) {
			if ( ! isset( $this->fileContents[ $file ] ) ) {
				continue;
			}
			$content = $this->fileContents[ $file ];
			if ( $content ) {
				if ( $file === 'composer.json' || $file === 'package.json' ) {
					$json = json_decode( $content, true );
					if ( isset( $json['license'] ) ) {
						return array(
							'name' => is_array( $json['license'] ) ? implode( ', ', $json['license'] ) : $json['license'],
							'url'  => isset( $json['license_url'] ) ? $json['license_url'] : '',
						);
					}
				} elseif ( $file === 'readme.txt' ) {
					if ( preg_match( '/^License:\s*(.+)$/mi', $content, $matches ) ) {
						return array(
							'name' => trim( $matches[1] ),
							'url'  => '',
						);
					}
				}
			}
		}

		// Fallback to LICENSE file
		$license_files = array( 'LICENSE', 'LICENSE.md', 'LICENSE.txt' );
		foreach ( $license_files as $file ) {
			$license_path = $this->repoPath . '/' . $file;
			if ( file_exists( $license_path ) ) {
				$content    = file_get_contents( $license_path );
				$first_line = strtok( $content, "\n" );
				return array(
					'name' => trim( $first_line ),
					'url'  => '',
				);
			}
		}

		return null;
	}

	public function get_project_type() {
		if ( ! empty( $this->fileContents['composer.json'] ) ) {
			$json = json_decode( $this->fileContents['composer.json'], true );
			if ( isset( $json['type'] ) ) {
				return $json['type'];
			}
		}
		if ( ! empty( $this->fileContents['package.json'] ) ) {
			$json = json_decode( $this->fileContents['package.json'], true );
			if ( isset( $json['type'] ) ) {
				return $json['type'];
			}
		}
		// ...additional logic based on readme files...
		// Return null if type is not found
		return null;
	}

	/**
	 * Collect useful metadata from composer.json / package.json to use as tags.
	 * Returns an array of tag strings (may be empty).
	 */
	public function get_project_tags() {
		$tags = array();

		// Helper to merge and normalise tag values
		$add_tag = function ( $value ) use ( &$tags ) {
			if ( empty( $value ) ) {
				return;
			}
			if ( is_array( $value ) ) {
				foreach ( $value as $v ) {
					$v = trim( (string) $v );
					if ( $v !== '' ) {
						$tags[] = $v;
					}
				}
			} else {
				$v = trim( (string) $value );
				if ( $v !== '' ) {
					$tags[] = $v;
				}
			}
		};

		// composer.json
		if ( ! empty( $this->fileContents['composer.json'] ) ) {
			$json = json_decode( $this->fileContents['composer.json'], true );
			if ( is_array( $json ) ) {
				if ( isset( $json['keywords'] ) && is_array( $json['keywords'] ) ) {
					$add_tag( $json['keywords'] );
				}
				if ( isset( $json['type'] ) ) {
					$add_tag( $json['type'] );
				}
				if ( isset( $json['name'] ) ) {
					$name = (string) $json['name'];
					$add_tag( $name );
					// split vendor/package
					if ( strpos( $name, '/' ) !== false ) {
						list($vendor, $pkg) = explode( '/', $name, 2 );
						$add_tag( $vendor );
						$add_tag( $pkg );
					}
				}
				// include top-level require package names (vendor parts) as tags
				if ( isset( $json['require'] ) && is_array( $json['require'] ) ) {
					foreach ( $json['require'] as $req_name => $_ ) {
						if ( strpos( $req_name, '/' ) !== false ) {
							list($vendor, $pkg) = explode( '/', $req_name, 2 );
							$add_tag( $vendor );
							$add_tag( $pkg );
						} else {
							$add_tag( $req_name );
						}
					}
				}
			}
		}

		// package.json
		if ( ! empty( $this->fileContents['package.json'] ) ) {
			$json = json_decode( $this->fileContents['package.json'], true );
			if ( is_array( $json ) ) {
				if ( isset( $json['keywords'] ) && is_array( $json['keywords'] ) ) {
					$add_tag( $json['keywords'] );
				}
				if ( isset( $json['name'] ) ) {
					$name = (string) $json['name'];
					$add_tag( $name );
					if ( strpos( $name, '/' ) !== false ) {
						list($vendor, $pkg) = explode( '/', $name, 2 );
						$add_tag( $vendor );
						$add_tag( $pkg );
					}
				}
			}
		}

		// Normalise: unique, trim, lower-case-ish but preserve original for readability
		$normalized = array();
		foreach ( $tags as $t ) {
			$t = trim( $t );
			if ( $t === '' ) {
				continue;
			}
			// Lowercase for uniqueness but keep original-case value
			$key = mb_strtolower( $t );
			if ( ! isset( $normalized[ $key ] ) ) {
				$normalized[ $key ] = $t;
			}
		}

		return array_values( $normalized );
	}

	public function get_project_title() {
		// Prefer a level-1 Markdown heading (# Title) only if it is the first non-empty line
		if ( ! empty( $this->fileContents['README.md'] ) ) {
			$content = $this->fileContents['README.md'];
			$lines   = preg_split( '/\r\n|\r|\n/', $content );
			// find first non-empty line
			foreach ( $lines as $line ) {
				if ( trim( $line ) === '' ) {
					continue;
				}
				// Match only a single leading # followed by a space (level-1 heading)
				if ( preg_match( '/^#\s+(.+)$/', $line, $matches ) ) {
					$title = trim( $matches[1] );
					// Strip trailing dots
					$title = rtrim( $title, '. ' );
					return $title;
				}
				// If first non-empty line is not a level-1 heading, do not use any subsequent headings
				break;
			}
		}

		// Fallbacks: plugin readme, composer/package name, then repo basename
		if ( ! empty( $this->fileContents['readme.txt'] ) ) {
			$content = $this->fileContents['readme.txt'];
			if ( preg_match( '/^Plugin Name:\s*(.+)$/mi', $content, $matches ) ) {
				$title = trim( $matches[1] );
				$title = rtrim( $title, '. ' );
				return $title;
			}
		}

		// Try composer.json/package.json name
		if ( ! empty( $this->fileContents['composer.json'] ) ) {
			$json = json_decode( $this->fileContents['composer.json'], true );
			if ( is_array( $json ) && ! empty( $json['name'] ) ) {
				// Use package name (vendor/package) as readable fallback
				$title = trim( $json['name'] );
				$title = rtrim( $title, '. ' );
				return $title;
			}
		}

		if ( ! empty( $this->fileContents['package.json'] ) ) {
			$json = json_decode( $this->fileContents['package.json'], true );
			if ( is_array( $json ) && ! empty( $json['name'] ) ) {
				$title = trim( $json['name'] );
				$title = rtrim( $title, '. ' );
				return $title;
			}
		}

		// Finally fall back to repository basename from URL
		if ( ! empty( $this->repo_url ) ) {
			$parsed = parse_url( $this->repo_url );
			if ( ! empty( $parsed['path'] ) ) {
				$path  = trim( $parsed['path'], '/' );
				$parts = explode( '/', $path );
				$repo  = end( $parts );
				// strip .git suffix
				$repo = preg_replace( '/\.git$/', '', $repo );
				if ( $repo ) {
					$repo = rtrim( $repo, '. ' );
					return $repo;
				}
			}
		}

		return null;
	}

	public function get_project_description() {
		$description = null;

		if ( ! empty( $this->fileContents['README.md'] ) ) {
			$content = $this->fileContents['README.md'];
			// Extract content under ## Description until the next heading
			if ( preg_match( '/##\s*Description\s*(.*?)\n##/s', $content, $matches ) ) {
				$description = $matches[1];
			} elseif ( preg_match( '/##\s*Description\s*(.*)/s', $content, $matches ) ) {
				// Fallback if there's no subsequent heading
				$description = $matches[1];
			} else {
				// If description is still empty, use the whole readme file except the first line if it's a first-level title
				$lines = preg_split( '/\r\n|\r|\n/', $content );
				if ( ! empty( $lines ) ) {
					$first_line = $lines[0];
					if ( preg_match( '/^#\s+/', $first_line ) ) {
						array_shift( $lines ); // Remove the first line (title)
					}
					$description = implode( "\n", $lines );
				}
			}
		} elseif ( ! empty( $this->fileContents['readme.txt'] ) ) {
			$content = $this->fileContents['readme.txt'];
			// Extract content under == Description == until the next section
			if ( preg_match( '/==\s*Description\s*==\s*(.*?)\s*==/s', $content, $matches ) ) {
				$description = trim( $matches[1] );
			} elseif ( preg_match( '/==\s*Description\s*==\s*(.*)/s', $content, $matches ) ) {
				// Fallback if there's no subsequent section
				$description = trim( $matches[1] );
			} else {
				// If description is still empty, use the whole readme file except the first line if it's a first-level title
				$lines = preg_split( '/\r\n|\r|\n/', $content );
				if ( ! empty( $lines ) ) {
					$first_line = $lines[0];
					if ( preg_match( '/^==\s*.+\s*==$/', $first_line ) ) {
						array_shift( $lines ); // Remove the first line (title)
					}
					$description = implode( "\n", $lines );
				}
			}
		}

		return trim( $description );
	}

	public function license() {
		$license_info = $this->get_license_info();
		if ( $license_info && ! empty( $license_info['name'] ) ) {
			return $license_info['name'];
		}
		return;
	}

	public function cleanup() {
		$this->removeDirectory( $this->repoPath );
	}

	private function removeDirectory( $dir ) {
		if ( is_dir( $dir ) ) {
			$objects = scandir( $dir );
			foreach ( $objects as $object ) {
				if ( $object !== '.' && $object !== '..' ) {
					$path = $dir . DIRECTORY_SEPARATOR . $object;
					if ( is_dir( $path ) ) {
						$this->removeDirectory( $path );
					} else {
						unlink( $path );
					}
				}
			}
			rmdir( $dir );
		}
	}

	public function is_repository_cloned() {
		return $this->repository !== null;
	}
}

// // Test code, don't enable unless needed for debug
// $repo_url = 'https://github.com/GuduleLapointe/w4os';
// $git = new OSProjectsGit($repo_url);

// error_log( "\n" .
// "Last commit html: " . $git->last_commit_html() . "\n" .
// " Last commit date: " . $git->last_commit_date() . "\n" .
// " Last commit hash: " . $git->last_commit_hash() . "\n" .
// " Last commit hash long: " . $git->last_commit_hash_long() . "\n" .
// " Last release: " . $git->last_release_html() . "\n" .
// " License: " . $git->license() . "\n"
// );
