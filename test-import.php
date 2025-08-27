<?php
/**
 * Test script to simulate repository import behavior
 */

// Mock WordPress functions for testing
if (!function_exists('wp_remote_head')) {
    function wp_remote_head($url, $args = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, isset($args['redirection']) ? $args['redirection'] : 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, isset($args['timeout']) ? $args['timeout'] : 10);
        curl_setopt($ch, CURLOPT_USERAGENT, isset($args['user-agent']) ? $args['user-agent'] : 'OSProjectsPlugin');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return new WP_Error('http_request_failed', $error);
        }
        
        return array(
            'response' => array('code' => $http_code),
            'headers' => array(),
            'body' => '',
            'cookies' => array(),
            'filename' => null,
            'http_response' => null,
            'effective_url' => $effective_url
        );
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return $response['response']['code'];
    }
}

if (!function_exists('wp_remote_retrieve_header')) {
    function wp_remote_retrieve_header($response, $header) {
        // For redirects, we need to check if URL changed
        if ($header === 'location' && isset($response['effective_url'])) {
            return $response['effective_url'];
        }
        return '';
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return ($thing instanceof WP_Error);
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, isset($args['timeout']) ? $args['timeout'] : 30);
        curl_setopt($ch, CURLOPT_USERAGENT, isset($args['user-agent']) ? $args['user-agent'] : 'OSProjectsPlugin');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return new WP_Error('http_request_failed', $error);
        }
        
        return array(
            'response' => array('code' => $http_code),
            'body' => $response
        );
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'];
    }
}

class WP_Error {
    public $errors;
    public $error_data;
    
    public function __construct($code = '', $message = '', $data = '') {
        $this->errors = array($code => array($message));
        $this->error_data = array($code => $data);
    }
    
    public function get_error_message() {
        $code = $this->get_error_code();
        return isset($this->errors[$code][0]) ? $this->errors[$code][0] : '';
    }
    
    public function get_error_code() {
        $codes = array_keys($this->errors);
        return !empty($codes) ? $codes[0] : '';
    }
}

// Simulated project checking method
function get_repo_project_id($repo_url) {
    // This would normally query the database
    $existing_projects = array(
        'https://github.com/GuduleLapointe/opensim-helpers' => 123,
        // Add other known projects here
    );
    
    return isset($existing_projects[$repo_url]) ? $existing_projects[$repo_url] : false;
}

// Simulate the resolve_repository_redirects method
function resolve_repository_redirects($repo_url) {
    echo "Testing URL: $repo_url\n";
    
    // Only handle HTTP(S) URLs
    if (!filter_var($repo_url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//', $repo_url)) {
        return array('url' => $repo_url, 'error' => null, 'redirected' => false);
    }

    // Use WordPress HTTP API to follow redirects
    $response = wp_remote_head($repo_url, array(
        'timeout'     => 10,
        'redirection' => 5, // Follow up to 5 redirects
        'user-agent'  => 'OSProjectsPlugin',
    ));

    // If request failed, return error
    if (is_wp_error($response)) {
        return array( 
            'url' => $repo_url, 
            'error' => 'Network error: ' . $response->get_error_message(), 
            'redirected' => false 
        );
    }

    // Check HTTP status code
    $status_code = wp_remote_retrieve_response_code($response);
    echo "Status code: $status_code\n";
    
    // Handle 4xx errors (client errors like 404, 403, etc.)
    if ($status_code >= 400 && $status_code < 500) {
        $error_messages = array(
            404 => 'Repository not found (404)',
            403 => 'Access forbidden (403)',
            401 => 'Unauthorized access (401)',
        );
        $error_message = isset($error_messages[$status_code]) 
            ? $error_messages[$status_code] 
            : "Client error ($status_code)";
        
        return array( 
            'url' => $repo_url, 
            'error' => $error_message, 
            'redirected' => false 
        );
    }

    // Handle 5xx errors (server errors)
    if ($status_code >= 500) {
        return array( 
            'url' => $repo_url, 
            'error' => "Server error ($status_code)", 
            'redirected' => false 
        );
    }

    // For redirects, check if URL actually changed
    $final_url = wp_remote_retrieve_header($response, 'location');
    echo "Effective URL: $final_url\n";
    
    // If the effective URL is different, it's a redirect
    if (!empty($final_url) && $final_url !== $repo_url) {
        // Remove trailing .git if present for consistency
        $final_url_clean = preg_replace('/\.git$/', '', $final_url);
        $repo_url_clean = preg_replace('/\.git$/', '', $repo_url);
        
        if ($final_url_clean !== $repo_url_clean) {
            echo "Redirect detected: $repo_url -> $final_url_clean\n";
            return array('url' => $final_url_clean, 'error' => null, 'redirected' => true);
        }
    }

    return array('url' => $repo_url, 'error' => null, 'redirected' => false);
}

// Simulate fetching GitHub repositories
function fetch_github_repositories($github_user_url) {
    echo "Fetching repositories from: $github_user_url\n";
    
    // Extract username from URL
    if (preg_match('/github\.com\/([^\/]+)/', $github_user_url, $matches)) {
        $username = $matches[1];
        $api_url = "https://api.github.com/users/$username/repos?per_page=100";
        
        echo "API URL: $api_url\n";
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 30,
            'user-agent' => 'OSProjectsPlugin',
        ));
        
        if (is_wp_error($response)) {
            return array('error' => 'Failed to fetch repositories: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return array('error' => "GitHub API returned status $status_code");
        }
        
        $body = wp_remote_retrieve_body($response);
        $repos = json_decode($body, true);
        
        if (!is_array($repos)) {
            return array('error' => 'Invalid response from GitHub API');
        }
        
        echo "Found " . count($repos) . " repositories\n";
        return $repos;
    }
    
    return array('error' => 'Invalid GitHub user URL');
}

// Test the repository checking logic
function test_repository_checking($repo_url) {
    echo "\n=== Testing Repository: $repo_url ===\n";
    
    // First check if it already exists
    $existing_id = get_repo_project_id($repo_url);
    if ($existing_id) {
        echo "Repository already exists as project ID: $existing_id\n";
        return array('status' => 'exists', 'project_id' => $existing_id);
    }
    
    // Check redirects and accessibility
    $redirect_result = resolve_repository_redirects($repo_url);
    
    if (!empty($redirect_result['error'])) {
        echo "Repository has errors: " . $redirect_result['error'] . "\n";
        return array('status' => 'error', 'error' => $redirect_result['error']);
    }
    
    if ($redirect_result['redirected']) {
        $final_url = $redirect_result['url'];
        echo "Repository redirects to: $final_url\n";
        
        // Check if the redirect target already exists
        $existing_id = get_repo_project_id($final_url);
        if ($existing_id) {
            echo "Redirect target already exists as project ID: $existing_id\n";
            return array('status' => 'redirect_exists', 'project_id' => $existing_id, 'final_url' => $final_url);
        }
        
        return array('status' => 'redirect_available', 'final_url' => $final_url);
    }
    
    return array('status' => 'available');
}

// Test the specific opensim-helpers redirect case
function test_specific_cases() {
    echo "\n=== Testing Specific Cases ===\n";
    
    // Test the opensim-helpers redirect
    echo "\n--- Testing opensim-helpers redirect ---\n";
    $original_url = 'https://github.com/magicoli/opensim-helpers';
    $result = test_repository_checking($original_url);
    
    echo "Original URL: $original_url\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    // Test a non-existent repository
    echo "\n--- Testing non-existent repository ---\n";
    $bad_url = 'https://github.com/GuduleLapointe/arcadia-mgk-plus';
    $result = test_repository_checking($bad_url);
    
    echo "Bad URL: $bad_url\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
}

// Main test
function main() {
    $github_user_url = 'https://github.com/magicoli';
    
    echo "=== Simulating Import Process ===\n";
    echo "GitHub User: $github_user_url\n\n";
    
    // Test specific redirect cases first
    test_specific_cases();
    
    // Fetch repositories
    $repos = fetch_github_repositories($github_user_url);
    
    if (isset($repos['error'])) {
        echo "Error: " . $repos['error'] . "\n";
        return;
    }
    
    echo "\n=== Testing Repository List (first 5) ===\n";
    
    $count = 0;
    foreach ($repos as $repo) {
        if ($count >= 5) break; // Only test first 5 for brevity
        
        $repo_url = $repo['html_url'];
        $result = test_repository_checking($repo_url);
        
        echo "Repository: " . $repo['name'] . "\n";
        echo "URL: $repo_url\n";
        echo "Result: " . $result['status'] . "\n";
        
        // Check if it would appear in import list
        switch ($result['status']) {
            case 'exists':
                echo "Import status: DISABLED (already imported)\n";
                break;
            case 'redirect_exists':
                echo "Import status: DISABLED (redirects to existing project)\n";
                break;
            case 'error':
                echo "Import status: AVAILABLE (will be set to ignored on import)\n";
                break;
            case 'redirect_available':
                echo "Import status: AVAILABLE (will import with new URL: " . $result['final_url'] . ")\n";
                break;
            case 'available':
                echo "Import status: AVAILABLE\n";
                break;
        }
        
        echo "\n";
        $count++;
    }
}

// Run the test
main();
