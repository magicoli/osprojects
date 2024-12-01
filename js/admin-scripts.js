// Perform AJAX test with repository URL
jQuery(document).ready(function($) {
    // Use the repository URL passed from PHP
    var repositoryUrl = OSProjectsAjax.repository_url;

    if (repositoryUrl) {
        $.ajax({
            url: OSProjectsAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'osprojects_fetch_git_data',
                repository_url: repositoryUrl,
                nonce: OSProjectsAjax.nonce // Include nonce in initial AJAX call
            },
            success: function(response) {
                if (response.success) {
                    // console.log('AJAX test success:', response.data);
                } else {
                    console.error('AJAX test failed:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX test error:', error);
            }
        });
    // } else {
    //     console.warn('No repository URL provided.');
    }

    // Function to debounce AJAX calls
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Function to fetch and update Git data
    function fetchGitData() {
        var repositoryUrl = $('#osp_project_repository').val(); // Get current value from input field
        var nonce = OSProjectsAjax.nonce; // Retrieve nonce

        if (repositoryUrl && nonce) { // Ensure both repositoryUrl and nonce are present
            // Show loading message
            $('#osp_project_repository_notification').text('Loading...');
            $('#osp_project_repository_notification').removeClass().addClass('notification loading').text('Loading...');
            $('#osp_project_repository_notification').html('<span class="spinner"></span> Loading...');

            $.ajax({
                url: OSProjectsAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'osprojects_fetch_git_data',
                    repository_url: repositoryUrl, // Use updated repositoryUrl
                    nonce: nonce // Include nonce for security
                },
                success: function(response) {
                    if (response.success) {
                        $('#osp_project_repository_notification').html('<span class="spinner"></span> Loading...');
                        // Check if last_commit_html exists to validate the repository
                        if (response.data.last_commit_html) {
                            // Update the UI with the new data
                            $('#osp_project_license').text(response.data.license || ''); // Allow empty license
                            $('#osp_project_release').html(response.data.last_release_html || ''); // Allow empty release
                            $('#osp_project_last_commit').html(response.data.last_commit_html);
                            
                            // Clear the notification
                            $('#osp_project_repository_notification').text('');
                        } else {
                            // Display error message in notification
                            $('#osp_project_repository_notification').text('Invalid Git repository: Last commit not found.');
                            
                            // Clear the fields since the repository is invalid
                            $('#osp_project_license').text('');
                            $('#osp_project_release').html('');
                            $('#osp_project_last_commit').html('');
                        }
                    } else {
                        // Display error message in notification
                        $('#osp_project_repository_notification').text('Error fetching Git data: ' + response.data);
                        $('#osp_project_repository_notification').removeClass().addClass('notification error').text('Error message');

                        // Clear the fields since the repository is invalid
                        $('#osp_project_license').text('');
                        $('#osp_project_release').html('');
                        $('#osp_project_last_commit').html('');
                    }
                },
                error: function(xhr, status, error) {
                    // Display error message in notification
                    $('#osp_project_repository_notification').text('An error occurred while fetching Git data.');
                    
                    // Clear the fields since the repository is invalid
                    $('#osp_project_license').text('');
                    $('#osp_project_release').html('');
                    $('#osp_project_last_commit').html('');
                }
            });
        // } else {
        //     console.warn('No repository URL or nonce provided.');
        }
    }

    // Debounced version of fetchGitData
    var debouncedFetchGitData = debounce(fetchGitData, 500); // 500ms delay

    // Bind event handlers to the repository URL field
    $('#osp_project_repository').on('blur', function() {
        // Clear existing data and show notification
        $('#osp_project_license').text('');
        $('#osp_project_release').html('');
        $('#osp_project_last_commit').html('');
        $('#osp_project_repository_notification').text('Enter a valid repository URL.');
        fetchGitData();
    });

    $('#osp_project_repository').on('input', function() {
        // Clear existing data and show notification
        $('#osp_project_license').text('');
        $('#osp_project_release').html('');
        $('#osp_project_last_commit').html('');
        $('#osp_project_repository_notification').text('Enter a valid repository URL.');
        debouncedFetchGitData();
    });

    // Listen for the save button clicks to fetch updated Git data using delegated event binding
    $(document).on('click', '.editor-post-publish-button, .editor-post-publish-panel__toggle', function(e) {
        var repositoryUrl = $('#osp_project_repository').val(); // Use current field value
        var nonce = OSProjectsAjax.nonce; // Retrieve nonce

        if (repositoryUrl && nonce) { // Ensure both repositoryUrl and nonce are present
            // Show loading message
            $('#osp_project_repository_notification').text('Loading...');

            $.ajax({
                url: OSProjectsAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'osprojects_fetch_git_data',
                    repository_url: repositoryUrl, // Use updated repositoryUrl
                    nonce: nonce // Include nonce for security
                },
                success: function(response) {
                    if (response.success) {
                        // Check if last_commit_html exists to validate the repository
                        if (response.data.last_commit_html) {
                            // Update the UI with the new data
                            $('#osp_project_license').text(response.data.license || ''); // Allow empty license
                            $('#osp_project_release').html(response.data.last_release_html || ''); // Allow empty release
                            $('#osp_project_last_commit').html(response.data.last_commit_html);
                            
                            // Clear the notification
                            $('#osp_project_repository_notification').text('');
                        } else {
                            // Display error message in notification
                            $('#osp_project_repository_notification').text('Enter a valid repository URL.G');
                            
                            // Clear the fields since the repository is invalid
                            $('#osp_project_license').text('');
                            $('#osp_project_release').html('');
                            $('#osp_project_last_commit').html('');
                        }
                    } else {
                        // Display error message in notification
                        $('#osp_project_repository_notification').text('Error fetching Git data: ' + response.data);
                        
                        // Clear the fields since the repository is invalid
                        $('#osp_project_license').text('');
                        $('#osp_project_release').html('');
                        $('#osp_project_last_commit').html('');
                    }
                },
                error: function(xhr, status, error) {
                    // Display error message in notification
                    $('#osp_project_repository_notification').text('An error occurred while fetching Git data.');
                    
                    // Clear the fields since the repository is invalid
                    $('#osp_project_license').text('');
                    $('#osp_project_release').html('');
                    $('#osp_project_last_commit').html('');
                }
            });
        // } else {
        //     console.warn('No repository URL or nonce provided.');
        }
    });
});
