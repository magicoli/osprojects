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
                            
                            // Update the title field if project_title is available
                            if (response.data.project_title) {
                                // For Classic Editor
                                $('#title').val(response.data.project_title); // Set the post title
                                
                                // For Gutenberg Editor
                                if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                                    wp.data.dispatch('core/editor').editPost({ title: response.data.project_title });
                                }
                            }

                            // Update the content editor if project_description is available
                            if (response.data.project_description) {
                                // For Classic Editor
                                if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                                    tinyMCE.activeEditor.setContent(response.data.project_description);
                                }

                                // For Gutenberg Editor
                                if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
                                    wp.data.dispatch('core/editor').resetEditorBlocks(); // Reset existing blocks
                                    wp.data.dispatch('core/editor').editPost({ content: response.data.project_description });
                                }
                            }

                            // Note: project categories are intentionally left as a manual choice.
                            // Update tags if project_tags are available
                            if (response.data.project_tags && response.data.project_tags.length) {
                                // For Gutenberg Editor: need term IDs
                                if (typeof wp !== 'undefined' && wp.data) {
                                    const tagNames = response.data.project_tags;
                                    const taxonomy = 'post_tag';
                                    const idPromises = tagNames.map(name => {
                                        try {
                                            const records = wp.data.select('core').getEntityRecords('taxonomy', taxonomy, { search: name });
                                            if (records && records.length) {
                                                return Promise.resolve(records[0].id);
                                            }
                                        } catch (e) {
                                            // fallthrough to creation
                                        }
                                        // Create the tag if it doesn't exist
                                        return wp.data.dispatch('core').createEntityRecord('taxonomy', taxonomy, { name: name })
                                            .then(created => created && created.id ? created.id : null)
                                            .catch(() => null);
                                    });

                                    Promise.all(idPromises).then(ids => {
                                        const validIds = ids.filter(Boolean);
                                        if (validIds.length) {
                                            wp.data.dispatch('core/editor').editPost({ terms: { 'post_tag': validIds } });
                                        }
                                    }).catch(err => {
                                        console.error('Error resolving tags:', err);
                                    });
                                }

                                // For Classic Editor: populate the tags input field
                                if ($('#new-tag-post_tag').length) {
                                    $('#new-tag-post_tag').val(response.data.project_tags.join(', '));
                                }
                            }
                            
                            // Clear the notification
                            $('#osp_project_repository_notification').text('');
                        } else {
                            // Display error message in notification
                            $('#osp_project_repository_notification').text('Invalid Git repository.');
                            
                            // Clear the fields since the repository is invalid
                            $('#osp_project_license').text('');
                            $('#osp_project_release').html('');
                            $('#osp_project_last_commit').html('');
                            
                            // Clear title and content for Gutenberg
                            if (typeof wp !== 'undefined' && wp.data) {
                                wp.data.dispatch('core/editor').editPost({ title: '', content: '' });
                            }
                            
                            // For Classic Editor
                            $('#title').val('');
                            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                                tinyMCE.activeEditor.setContent('');
                            }
                        }
                    } else {
                        // Display error message in notification
                        $('#osp_project_repository_notification').text('Error fetching Git data: ' + response.data);
                        $('#osp_project_repository_notification').removeClass().addClass('notification error').text('Error message');

                        // Clear the fields since the repository is invalid
                        $('#osp_project_license').text('');
                        $('#osp_project_release').html('');
                        $('#osp_project_last_commit').html('');
                        
                        // Clear title and content for Gutenberg
                        if (typeof wp !== 'undefined' && wp.data) {
                            wp.data.dispatch('core/editor').editPost({ title: '', content: '' });
                        }
                        
                        // For Classic Editor
                        $('#title').val('');
                        if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                            tinyMCE.activeEditor.setContent('');
                        }
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

    // Remove the event listener for the save button
    // $(document).on('click', '.editor-post-publish-button, .editor-post-publish-panel__toggle', function(e) {
    //     // ...code that triggers AJAX on save...
    // });
});
