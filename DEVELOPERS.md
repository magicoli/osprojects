# Developer Documentation

This document contains information for developers who want to contribute to or customize the Open Source Projects plugin.

## Development Setup

### Prerequisites

- **Node.js**: 16.x or higher
- **Composer**: 2.x or higher
- **WordPress Development Environment**
- **Git**

### Getting Started

1. Fork and clone the repository:
   ```bash
   git clone https://github.com/your-username/osprojects.git
   cd osprojects
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install Node.js dependencies:
   ```bash
   npm install
   ```

4. Link the plugin to your WordPress development site

## Build Tools

The plugin uses Grunt for build tasks:

### Available Grunt Tasks

```bash
# Generate translation template (.pot file)
grunt makepot

# Build readme.txt from README.md and other files
grunt makereadmetxt

# Development watcher (default task)
grunt

# All tasks
grunt all
```

### Available npm Scripts

```bash
# Run default grunt task
npm start
```

For translation tasks, see the Translation section below.

## File Structure

```
osprojects/
├── osprojects.php              # Main plugin file
├── includes/                   # Core plugin classes
│   ├── init-class.php         # Main initialization
│   ├── class-project.php      # Project post type & taxonomy
│   ├── class-settings.php     # Settings management
│   └── helpers/               # Helper classes
│       ├── class-git.php      # Git operations
│       └── class-admin-import.php # Import functionality
├── templates/                  # Admin templates
│   ├── admin-import.php       # Import interface
│   ├── dashboard.php          # Dashboard template
│   ├── project-content.php    # Project display template
│   ├── project-edit-metabox-details.php # Edit metabox
│   ├── refresh-streaming.php  # Refresh progress
│   └── settings.php           # Settings page
├── css/                       # Stylesheets
│   ├── admin.css             # Admin interface styles
│   └── project.css           # Project display styles
├── js/                        # JavaScript files
│   └── admin-project-ajax.js  # AJAX functionality
├── lib/                       # Composer dependencies
├── languages/                 # Translation files
├── tests/                     # Unit tests
├── dev/                       # Development tools
└── workflows/                 # GitHub Actions
```

## Key Classes

### OSProjectsProject (`includes/class-project.php`)

Main class handling project post type and related functionality:

- **Post Type Registration**: Registers the `project` custom post type
- **Taxonomy Management**: Handles project categories and tags
- **AJAX Operations**: Processes metadata updates
- **Admin Interface**: Manages project list columns and filters
- **Bulk Operations**: Handles ignore/unignore actions

Key methods:
- `register_post_type()` - Registers project post type
- `register_taxonomy()` - Registers project_category taxonomy
- `ajax_fetch_git_data()` - AJAX handler for Git metadata updates
- `update_project_meta_fields()` - Updates project metadata

### OSProjectsAdminImport (`includes/helpers/class-admin-import.php`)

Handles GitHub repository import functionality:

- **GitHub API Integration**: Fetches repositories with pagination
- **WordPress Importer**: Integrates with WordPress import system
- **Duplicate Detection**: Prevents importing existing projects
- **Batch Processing**: Handles multiple repository imports

Key methods:
- `fetch_github_repositories()` - Fetches repos from GitHub API
- `import_repositories()` - Processes selected repositories
- `get_existing_project_for_repo()` - Checks for existing projects

### OSProjectsGit (`includes/helpers/class-git.php`)

Git repository operations and metadata extraction:

- **Repository Cloning**: Temporary git repository access
- **Metadata Extraction**: Commits, tags, releases, license detection
- **Error Handling**: Manages repository access issues
- **Cleanup**: Automatic temporary directory cleanup

Key methods:
- `last_commit()` - Gets latest commit information
- `last_tag()` - Gets latest tag/release
- `license()` - Detects repository license
- `cleanup()` - Removes temporary files

### OSProjectsSettings (`includes/class-settings.php`)

Plugin settings and configuration management:

- **Options Management**: Handles plugin settings
- **Default Values**: Sets sensible defaults
- **Scheduled Tasks**: Manages daily refresh cron
- **Helper Methods**: Static access to settings

Key methods:
- `get_option()` - Retrieves plugin options
- `register_settings()` - Registers WordPress settings

## Hooks and Filters

### Actions

```php
// Triggered daily for automated project refresh
do_action('osprojects_daily_project_refresh');

// Triggered after successful project import
do_action('osprojects_project_imported', $project_id, $repo_data);

// Triggered after project metadata update
do_action('osprojects_project_updated', $project_id, $meta_data);
```

### Filters

```php
// Customize displayed project fields
$fields = apply_filters('osprojects_project_fields', $default_fields);

// Modify Git operation timeout (default: 30 seconds)
$timeout = apply_filters('osprojects_git_timeout', 30);

// Adjust import batch size (default: 10)
$batch_size = apply_filters('osprojects_import_batch_size', 10);

// Customize GitHub API request arguments
$args = apply_filters('osprojects_github_api_args', $default_args);
```

## Translation

### Workflow

1. Extract translatable strings:
   ```bash
   grunt makepot
   ```

2. Update translatable strings in existing .po translation files:
   ```bash
   msgmerge --update languages/osprojects-fr_FR.po languages/osprojects.pot
   ```

3. Update the .po translation files with your favorite tool.

4. Compile translations:
   ```bash
   msgfmt languages/osprojects-fr_FR.po -o languages/osprojects-fr_FR.mo
   ```

### Adding New Language

1. Create new .po file:
   ```bash
   msginit -l es_ES -o languages/osprojects-es_ES.po -i languages/osprojects.pot
   ```

2. Translate strings in the .po file

3. Compile to .mo file:
   ```bash
   msgfmt languages/osprojects-es_ES.po -o languages/osprojects-es_ES.mo
   ```

## Testing

### Manual Testing

1. **Import Testing**: Test with various GitHub users/organizations
2. **Edge Cases**: Test with redirected repositories, private repos, non-existent users
3. **Performance**: Test with users having many repositories
4. **Browser Testing**: Test in different browsers and WordPress versions

### Unit Tests

```bash
# Run PHPUnit tests (when available)
composer test

# Run WordPress coding standards check
composer phpcs
```

## Database Schema

### Post Meta Fields

Projects store metadata in WordPress post meta:

- `osp_project_repository` - Repository URL
- `osp_project_last_commit_hash` - Latest commit hash
- `osp_project_last_commit_date` - Latest commit date
- `osp_project_last_commit_html` - Formatted commit link
- `osp_project_last_release_html` - Formatted release link
- `osp_project_license` - Repository license
- `osp_project_website` - Official website URL
- `osp_project_git_error` - Git operation error messages

### Custom Post Status

- `ignored` - Projects excluded from public display

### Taxonomies

- `project_category` - Hierarchical project categories
- `post_tag` - Non-hierarchical project tags

## API Integration

### GitHub API

The plugin interacts with GitHub's REST API v3:

- **Endpoints Used**:
  - `/users/{username}/repos` - List user repositories
  - Repository metadata via git operations

- **Rate Limiting**: 
  - 60 requests/hour without token
  - 5000 requests/hour with personal access token

- **Pagination**: Supports up to 1000 repositories (10 pages × 100 per page)

## Contributing

### Code Standards

- Follow WordPress Coding Standards
- Use meaningful variable and function names
- Add inline documentation for complex logic
- Include error handling for external API calls

### Git Workflow

1. Create feature branch from `master`
2. Make changes with clear commit messages
3. Test thoroughly
4. Submit pull request with description

### Pull Request Requirements

- [ ] Code follows WordPress standards
- [ ] New features include documentation
- [ ] Translations updated if new strings added
- [ ] Manual testing completed
- [ ] No breaking changes (or clearly documented)

## Security Considerations

- Sanitize all user inputs
- Validate URLs and file paths
- Use WordPress nonces for AJAX requests
- Escape output appropriately
- Handle file operations securely
- Limit Git operations to prevent abuse

## Performance Optimization

- Use transients for expensive operations
- Implement proper caching strategies
- Limit concurrent Git operations
- Optimize database queries
- Consider background processing for large imports

## Debugging

### Enable Debug Mode

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Common Debug Scenarios

- **Import Failures**: Check GitHub API responses and rate limits
- **Git Errors**: Verify repository accessibility and git installation
- **Memory Issues**: Monitor memory usage during large imports
- **AJAX Problems**: Check browser console and WordPress debug log

## Release Process

(for maintainer only)

1. Update version numbers in relevant files
2. Update CHANGELOG.md
3. Run all build tasks: `grunt all`
4. Test thoroughly
5. Create release on GitHub with built files
6. Update any documentation as needed
