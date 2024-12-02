<?php
/**
 * Test file to check git-php implementation
**/

if(!defined('OSPROJECTS_PLUGIN_PATH')){
    define('OSPROJECTS_PLUGIN_PATH', dirname(__DIR__) . '/');
}

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
 * @since 0.1.0
 */
class OSProjectsGit
{
    private $git;
    private $repository;
    private $repo_url;
    private $repoPath;
    private $last_commit = null;
    private $last_tag = null;
    private $version = null;
    private $release_date = null;
    private $download_url = null;
    private $license = null;
    private $fileContents = [];
    
    public function __construct($repo_url)
    {
        if( empty( $repo_url ) ) return;
        
        // Make sure $repo_url is a valid URL
        if (!filter_var($repo_url, FILTER_VALIDATE_URL)) {
            return;
        }
        
        require_once OSPROJECTS_PLUGIN_PATH . 'lib/autoload.php';

        // Set environment variable to ensure Git outputs messages in English
        putenv('LANG=C');

        $this->git = new CzProject\GitPhp\Git();

        $this->repoPath = get_temp_dir() . 'osprojects_git_' . uniqid();

        // Create the directory if it doesn't exist
        if (!file_exists(dirname($this->repoPath))) {
            mkdir(dirname($this->repoPath), 0755, true);
        }

        try {
            // Clone the repository without depth and single-branch options to include all tags
            $this->repository = $this->git->cloneRepository($repo_url, $this->repoPath, [
                // '--depth' => '1', // Removed to allow full clone with complete history
                // '--single-branch' => null, // Removed to fetch all branches and tags
            ]);
        } catch (CzProject\GitPhp\GitException $e) {
            // error_log ( 'Git clone failed: ' . $e->getMessage() );
            $this->repository = null;
            return;
        }

        // Hook the cleanup method to WordPress shutdown within the class
        add_action('shutdown', array($this, 'cleanup'));

        $this->repo_url = $repo_url;
        $this->loadRepositoryFiles();
    }

    private function loadRepositoryFiles()
    {
        if( empty( $this->repository ) ) return;

        $files = ['composer.json', 'package.json', 'readme.txt', 'README.md'];
        foreach ($files as $file) {
            $filePath = $this->repoPath . '/' . $file;
            if (file_exists($filePath)) {
                $this->fileContents[$file] = file_get_contents($filePath);
            } else {
                $this->fileContents[$file] = null;
            }
        }
    }

    public function last_commit()
    {
        if ($this->repository === null) {
            return false;
        }
        if (!empty($this->last_commit)) return $this->last_commit;
        
        // Use execute() and handle the array output
        $logArray = $this->repository->execute('log', '-1', '--format=%H %cd');
        if ( ! empty( $logArray ) ) {
            $commit = [];
            $logArray = explode(' ', $logArray[0]);
            
            // Format the date
            $date = new DateTime($logArray[1]);
            $commit['date'] = $date->format('Y-m-d H:i:s');
            
            // Format the commit hash
            $commit['hash_long'] = $logArray[0];
            $commit['hash'] = substr($logArray[0], 0, 7);
            $this->last_commit = $commit;
        } else {
            $this->last_commit = false;
        }
        return $this->last_commit;
    }

    public function last_commit_date() {
        if( empty( $this->last_commit() ) ) return false;
        return $this->last_commit['date'];
    }

    public function last_commit_hash() {
        if( empty( $this->last_commit() ) ) return false;
        return $this->last_commit['hash'];
    }

    public function last_commit_hash_long() {
        if( empty( $this->last_commit() ) ) return false;
        return $this->last_commit['hash_long'];
    }

    public function last_commit_url() {
        if( empty( $this->last_commit() ) ) return false;
        return $this->repo_url . '/commit/' . $this->last_commit['hash_long'];
    }

    public function last_commit_html() {
        if( empty( $this->last_commit() ) ) {
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

    public function last_tag()
    {
        if ( ! empty( $this->last_tag ) ) return $this->last_tag;
        if ( $this->last_tag === false ) return false;

        if ($this->repository === null) {
            return false;
        }

        // Use execute() and handle the array output
        $tagsArray = $this->repository->execute('tag', '-l', '--sort=-creatordate');
        $tags = array_filter($tagsArray);

        if (!empty($tags)) {
            $this->last_tag = trim($tags[0]);
        } else {
            $this->last_tag = false;
        }
        return $this->last_tag;
    }

    public function version() {
        if( ! empty( $this->version ) ) return $this->version;
        if( empty( $this->last_tag() ) ) return false;

        $this->version = $this->last_tag;
        return $this->version;
    }

    public function release_date() {
        if( ! empty( $this->release_date ) ) return $this->release_date;
        if( empty( $this->last_tag() ) ) return false;

        $dateArray = $this->repository->execute('log', '-1', '--format=%cd', $this->last_tag);
        $dateString = isset($dateArray[0]) ? $dateArray[0] : '';

        return trim($dateString);
    }

    /**
     * Construct the download link based on the last tag and the repository URL.
     *
     * @return string|false Download URL or false if unable to construct.
     */
    public function download_url() {
        if( ! empty( $this->download_url ) ) return $this->download_url;
        if( empty( $this->last_tag() ) ) return false;

        // Use $this->repo_url to construct the download link
        $parsed = parse_url($this->repo_url);
        $path = trim($parsed['path'], '/');
        $path_parts = explode('/', $path);
        $platform = $parsed['host'];
        $owner = $path_parts[0];
        $repo = $path_parts[1];

        switch ($platform) {
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
        if( empty( $this->last_tag() ) ) return false;
        return sprintf(
            '<a href="%s">%s</a> (%s)',
            $this->download_url(),
            $this->version(),
            $this->release_date(),
        );
    }

    public function get_license_info()
    {
        $standard_files = ['composer.json', 'package.json', 'readme.txt'];
        foreach ($standard_files as $file) {
            if( ! isset( $this->fileContents[$file] ) ) continue;
            $content = $this->fileContents[$file];
            if ($content) {
                if ($file === 'composer.json' || $file === 'package.json') {
                    $json = json_decode($content, true);
                    if (isset($json['license'])) {
                        return [
                            'name' => is_array($json['license']) ? implode(', ', $json['license']) : $json['license'],
                            'url' => isset($json['license_url']) ? $json['license_url'] : '',
                        ];
                    }
                } elseif ($file === 'readme.txt') {
                    if (preg_match('/^License:\s*(.+)$/mi', $content, $matches)) {
                        return [
                            'name' => trim($matches[1]),
                            'url' => '',
                        ];
                    }
                }
            }
        }

        // Fallback to LICENSE file
        $license_files = ['LICENSE', 'LICENSE.md', 'LICENSE.txt'];
        foreach ($license_files as $file) {
            $license_path = $this->repoPath . '/' . $file;
            if (file_exists($license_path)) {
                $content = file_get_contents($license_path);
                $first_line = strtok($content, "\n");
                return [
                    'name' => trim($first_line),
                    'url' => '',
                ];
            }
        }

        return null;
    }

    public function get_project_type()
    {
        if (!empty($this->fileContents['composer.json'])) {
            $json = json_decode($this->fileContents['composer.json'], true);
            if (isset($json['type'])) {
                return $json['type'];
            }
        }
        if (!empty($this->fileContents['package.json'])) {
            $json = json_decode($this->fileContents['package.json'], true);
            if (isset($json['type'])) {
                return $json['type'];
            }
        }
        // ...additional logic based on readme files...
        return 'unknown';
    }

    public function license()
    {
        $license_info = $this->get_license_info();
        if ($license_info && !empty($license_info['name'])) {
            return $license_info['name'];
        }
        return;
    }

    public function cleanup()
    {
        $this->removeDirectory($this->repoPath);
    }

    private function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    $path = $dir . DIRECTORY_SEPARATOR . $object;
                    if (is_dir($path)) {
                        $this->removeDirectory($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }
}

// // Test code, don't enable unless needed for debug
// $repo_url = 'https://github.com/GuduleLapointe/w4os';
// $git = new OSProjectsGit($repo_url);

// error_log( "\n" .
//     "Last commit html: " . $git->last_commit_html() . "\n" .
//     " Last commit date: " . $git->last_commit_date() . "\n" .
//     " Last commit hash: " . $git->last_commit_hash() . "\n" .
//     " Last commit hash long: " . $git->last_commit_hash_long() . "\n" .
//     " Last release: " . $git->last_release_html() . "\n" .
//     " License: " . $git->license() . "\n"
// );
