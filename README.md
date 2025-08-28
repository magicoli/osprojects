# Open Source Projects for WordPress

![Version 1.0.0](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Stable 1.0.0](https://img.shields.io/badge/stable-1.0.0-green.svg)
![WordPress 5.0+](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License AGPLv3](https://img.shields.io/badge/license-AGPLv3-red.svg)

A comprehensive WordPress plugin for showcasing and managing open source projects with automated GitHub integration, project metadata management, and multilingual support.

## Features

### Core Functionality
- **Custom Post Type**: Dedicated `project` post type for organizing open source projects
- **GitHub Integration**: Automated import and synchronization of GitHub repositories
- **Project Metadata**: Automatic extraction of project details (license, latest release, last commit, etc.)
- **Taxonomies**: Project categories and tags for better organization
- **Multi-language Support**: Built-in internationalization with French translations included

### GitHub Import System
- **Bulk Repository Import**: Import multiple repositories from any GitHub user/organization
- **Smart Duplicate Detection**: Prevents importing existing projects and handles redirects
- **Pagination Support**: Handles users with large numbers of repositories (up to 1000)
- **Selective Import**: Choose which repositories to import with visual interface
- **Status Management**: Automatic handling of ignored/problematic repositories

### Project Management
- **Automated Metadata Updates**: Regular synchronization of project data from Git repositories
- **Custom Project Status**: Support for ignored projects with dedicated status
- **Rich Project Display**: Automatic project information display with customizable templates
- **AJAX-powered Updates**: Real-time project metadata fetching in admin interface
- **Gutenberg Compatible**: Full support for block editor with classic editor fallback

### Admin Interface
- **Dedicated Admin Menu**: Complete admin interface under "Open Source Projects"
- **Repository Importer**: WordPress-integrated importer for GitHub repositories
- **Batch Operations**: Bulk actions for managing multiple projects
- **Filter & Search**: Advanced filtering by categories, status, and project attributes
- **Manual Refresh**: On-demand project metadata refresh with progress tracking

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Dependencies**: 
  - `czproject/git-php` (included) - Git repository interaction
  - `erusev/parsedown` (included) - Markdown parsing

## Installation

See [INSTALLATION.md](INSTALLATION.md) for detailed installation and configuration instructions.

## Usage

### Importing GitHub Repositories

#### Method 1: WordPress Importer (Recommended)
1. Go to **Tools > Import** in WordPress admin
2. Select **OSProjects Importer**
3. Enter a GitHub user/organization URL (e.g., `https://github.com/username`)
4. Click **Check** to fetch available repositories
5. Select repositories to import and click **Import**

#### Method 2: Direct Import Page
1. Navigate to **Open Source Projects > Import**
2. Follow the same process as above

### Managing Projects

#### Project Categories
- Create and manage project categories under **Open Source Projects > Project Categories**
- Hierarchical taxonomy supports nested categories
- Bulk categorization available in project list

#### Project Status
- **Published**: Publicly visible projects
- **Draft**: Projects in development
- **Ignored**: Projects excluded from public display (automatic for problematic repositories)

#### Manual Metadata Refresh
1. Go to **Open Source Projects > Settings**
2. Click **Refresh all projects now**
3. Monitor progress on the dedicated refresh page

### Displaying Projects

#### Archive Page
Projects are automatically available at `/projects/` (or your configured URL prefix)

#### Single Project Pages
Each project displays:
- Latest release information
- Last commit details
- Repository link
- Official website (if available)
- License information
- Project description

#### Custom Templates
Override default templates by creating files in your theme:
- `single-project.php` - Single project template
- `archive-project.php` - Projects archive template

## GitHub API Rate Limiting

For users with many repositories, you may encounter GitHub API rate limits. To increase limits:

1. Create a personal access token at https://github.com/settings/tokens
2. No special permissions needed for public repositories
3. Set the token using WP-CLI:
   ```bash
   wp option update osprojects-settings --format=json '{"github_api_token":"your_token_here"}'
   ```

## Troubleshooting

### Common Issues

#### GitHub Rate Limiting
- **Problem**: Import fails with rate limit errors
- **Solution**: Add GitHub API token as described above

#### Repository Access Errors
- **Problem**: Projects marked as ignored
- **Solution**: Check repository URLs and access permissions

#### Memory Issues with Large Imports
- **Problem**: PHP memory limit exceeded
- **Solution**: Increase PHP memory limit or import in smaller batches

#### Gutenberg Save Issues
- **Problem**: Project saves hang in Gutenberg editor
- **Solution**: Try clearing browser cookies or disable Gutenberg in plugin settings

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Development

For developers interested in contributing or customizing the plugin, see [DEVELOPERS.md](DEVELOPERS.md) for detailed development information.

## License

This plugin is licensed under the AGPL-3.0-or-later license.

## Support

- **Issues**: [GitHub Issues](https://github.com/magicoli/osprojects/issues)
- **Author**: [Magiiic](https://magiiic.com/)

## Roadmap

### Planned Features
- **GitLab Integration**: Support for GitLab repositories
- **Bitbucket Support**: Bitbucket repository import
- **Project Analytics**: Download and activity tracking
- **Advanced Filters**: More sophisticated project filtering options
- **REST API**: Full REST API for external integrations
- **Shortcode Support**: Display projects via shortcodes
- **Widget Support**: Project widgets for sidebars

---

*Made with care by [Magiiic](https://magiiic.com/)*
