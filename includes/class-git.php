<?php
/**
 * Test file to check git-php implementation
**/

if(!defined('OSPROJECTS_PLUGIN_PATH')){
    define('OSPROJECTS_PLUGIN_PATH', __DIR__ . '/');
}
require_once OSPROJECTS_PLUGIN_PATH . 'lib/autoload.php';

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

    public function __construct($repo_url)
    {
        // Set environment variable to ensure Git outputs messages in English
        putenv('LANG=C');

        $this->git = new CzProject\GitPhp\Git();

        // Use a subfolder of the current path instead of sys_get_temp_dir()
        $tmp_dir = __DIR__ . '/tmp/osprojects_git_' . uniqid();
        $this->repoPath = $tmp_dir;

        // Create the directory if it doesn't exist
        if (!file_exists(dirname($tmp_dir))) {
            mkdir(dirname($tmp_dir), 0755, true);
        }

        try {
            // Clone the repository without depth and single-branch options to include all tags
            $this->repository = $this->git->cloneRepository($repo_url, $tmp_dir, [
                // '--depth' => '1', // Removed to allow full clone with complete history
                // '--single-branch' => null, // Removed to fetch all branches and tags
            ]);
        } catch (CzProject\GitPhp\GitException $e) {
            error_log ( 'Git clone failed: ' . $e->getMessage() );
            $this->repository = null;
            return;
        }

        $this->repo_url = $repo_url;
    }

    public function last_commit()
    {
        if ($this->repository === null) {
            return false;
        }
        if (!empty($this->last_commit)) return $this->last_commit;

        // Use execute() and handle the array output
        $logArray = $this->repository->execute('log', '-1', '--format=%cd');
        $logString = isset($logArray[0]) ? $logArray[0] : '';
        $this->last_commit = trim($logString);
        return $this->last_commit;
    }

    /**
     * Get the last commit hash.
     *
     * @return string|false The last commit hash or false if unavailable.
     */
    public function last_commit_hash()
    {
        if ($this->repository === null) {
            return false;
        }
        // Use execute() and handle the array output
        $hashArray = $this->repository->execute('log', '-1', '--format=%H');
        $hash = isset($hashArray[0]) ? $hashArray[0] : '';
        return trim($hash);
    }

    public function get_last_tag()
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
        if( empty( $this->get_last_tag() ) ) return false;

        $this->version = $this->last_tag;
        return $this->version;
    }

    public function release_date() {
        if( ! empty( $this->release_date ) ) return $this->release_date;
        if( empty( $this->get_last_tag() ) ) return false;

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
        if( empty( $this->get_last_tag() ) ) return false;

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

    public function license()
    {
        $license_files = ['LICENSE', 'LICENSE.md', 'LICENSE.txt'];
        foreach ($license_files as $file) {
            $license_path = $this->repoPath . '/' . $file;
            if (file_exists($license_path)) {
                $content = file_get_contents($license_path);
                // Return only the first line of the license
                $first_line = strtok($content, "\n");
                return trim($first_line);
            }
        }
        return 'License file not found.';
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

$repo_url = 'https://github.com/GuduleLapointe/w4os';

$git = new OSProjectsGit($repo_url);

error_log( "\n" .
    "Last commit: " . $git->last_commit_hash() . " on " . $git->last_commit() . "\n" .
    "Last release: " . $git->version() . " on " . $git->release_date() . "\n" .
    "Last release download link: " . $git->download_url() . "\n" .
    "License: " . $git->license() . "\n"
);
// echo "Last commit: " . $git->last_commit_hash() . " on " . $git->last_commit() . "\n";
// echo "Last release: " . $git->version() . " on " . $git->release_date() . "\n";
// echo "Last release download link: " . $git->download_url() . "\n";
// echo "License: " . $git->license() . "\n";

$git->cleanup();
