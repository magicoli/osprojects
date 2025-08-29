=== Open Source Projects for WordPress ===
Contributors: (this should be a list of wordpress.org userid's)
Donate link: https://example.com/
Tags: comments, spam
Requires at least: 5.0
Tested up to: 6.8.2
Requires PHP: 7.4
Stable tag: 1.0.0
License: AGPLv3 or later
License URI: https://www.gnu.org/licenses/agpl-3.0.html

A comprehensive WordPress plugin for showcasing and managing open source projects with automated GitHub integration, project metadata management, and multilingual support.

== Description ==

A comprehensive WordPress plugin for showcasing and managing open source projects with automated GitHub integration, project metadata management, and multilingual support.

== Features ==

= Core Functionality =
- **Custom Post Type**: Dedicated `project` post type for organizing open source projects
- **GitHub Integration**: Automated import and synchronization of GitHub repositories
- **Project Metadata**: Automatic extraction of project details (license, latest release, last commit, etc.)
- **Taxonomies**: Project categories and tags for better organization
- **Multi-language Support**: Built-in internationalization with French translations included

= GitHub Import System =
- **Bulk Repository Import**: Import multiple repositories from any GitHub user/organization
- **Smart Duplicate Detection**: Prevents importing existing projects and handles redirects
- **Pagination Support**: Handles users with large numbers of repositories (up to 1000)
- **Selective Import**: Choose which repositories to import with visual interface
- **Status Management**: Automatic handling of ignored/problematic repositories

= Project Management =
- **Automated Metadata Updates**: Regular synchronization of project data from Git repositories
- **Custom Project Status**: Support for ignored projects with dedicated status
- **Rich Project Display**: Automatic project information display with customizable templates
- **AJAX-powered Updates**: Real-time project metadata fetching in admin interface
- **Gutenberg Compatible**: Full support for block editor with classic editor fallback

= Admin Interface =
- **Dedicated Admin Menu**: Complete admin interface under "Open Source Projects"
- **Repository Importer**: WordPress-integrated importer for GitHub repositories
- **Batch Operations**: Bulk actions for managing multiple projects
- **Filter & Search**: Advanced filtering by categories, status, and project attributes
- **Manual Refresh**: On-demand project metadata refresh with progress tracking

== Screenshots ==

1. Settings admin page
2. Projects list admin page

== Requirements ==

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Dependencies**: 
  - `czproject/git-php` (included) - Git repository interaction
  - `erusev/parsedown` (included) - Markdown parsing

== Usage ==

= Importing GitHub Repositories =

#### Method 1: WordPress Importer (Recommended)
1. Go to **Tools > Import** in WordPress admin
2. Select **OSProjects Importer**
3. Enter a GitHub user/organization URL (e.g., `https://github.com/username`)
4. Click **Check** to fetch available repositories
5. Select repositories to import and click **Import**

#### Method 2: Direct Import Page
1. Navigate to **Open Source Projects > Import**
2. Follow the same process as above

= Managing Projects =

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

= Displaying Projects =

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

== Troubleshooting ==

= GitHub API Rate Limiting =
- **Problem**: Import fails with rate limit errors
- **Solution**: Add GitHub API token as described above

For users with many repositories, you may encounter GitHub API rate limits. To increase limits:

1. Create a personal access token at https://github.com/settings/tokens
2. No special permissions needed for public repositories
3. Set the token using WP-CLI:
   ```bash
   wp option update osprojects-settings --format=json '{"github_api_token":"your_token_here"}'
   ```

= Repository Access Errors =
- **Problem**: Projects marked as ignored
- **Solution**: Check repository URLs and access permissions

= Memory Issues with Large Imports =
- **Problem**: PHP memory limit exceeded
- **Solution**: Increase PHP memory limit or import in smaller batches

= Gutenberg Save Issues =
- **Problem**: Project saves hang in Gutenberg editor
- **Solution**: Try clearing browser cookies or disable Gutenberg in plugin settings

= Debug Mode =
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

== Development ==

For developers interested in contributing or customizing the plugin, see [DEVELOPERS.md](DEVELOPERS.md) for detailed development information.

== License ==

This plugin is licensed under the AGPL-3.0-or-later license.

== Support ==

- **Issues**: [GitHub Issues](https://github.com/magicoli/osprojects/issues)
- **Author**: [Magiiic](https://magiiic.com/)

== Roadmap ==

= Planned Features =
- **GitLab Integration**: Support for GitLab repositories
- **Bitbucket Support**: Bitbucket repository import
- **Project Analytics**: Download and activity tracking
- **Advanced Filters**: More sophisticated project filtering options
- **REST API**: Full REST API for external integrations
- **Shortcode Support**: Display projects via shortcodes
- **Widget Support**: Project widgets for sidebars

---

*Made with care by [Magìiíc](https://magiiic.com/)*


== Installation ==

= Requirements =

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Dependencies**: Automatically included with the plugin
  - `czproject/git-php` - Git repository interaction
  - `erusev/parsedown` - Markdown parsing


**Recommended: Download Release**

1. Download the latest release from [GitHub Releases](https://github.com/magicoli/osprojects/releases)
2. Upload the plugin ZIP file through WordPress admin:
   - Go to **Plugins > Add New**
   - Click **Upload Plugin**
   - Choose the downloaded ZIP file
   - Click **Install Now**
3. Activate the plugin through the **Plugins** screen in WordPress

**Alternative: Manual Upload**

1. Download and extract the latest release
2. Upload the `osprojects` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** screen in WordPress

= Configuration =

#### Basic Setup

1. Navigate to **Open Source Projects > Settings** in your WordPress admin
2. Configure the project URL prefix (default: `projects`)
3. Choose between Gutenberg or Classic editor mode

#### GitHub API Token (Optional but Recommended)

For users importing many repositories, you may encounter GitHub API rate limits. To increase limits:

1. Create a personal access token at https://github.com/settings/tokens
   - Click **Generate new token (classic)**
   - Give it a descriptive name like "OSProjects Plugin"
   - No special scopes/permissions needed for public repositories
   - Copy the generated token

2. Set the token using WP-CLI:
   ```bash
   wp option update osprojects-settings --format=json '{"github_api_token":"your_token_here"}'
   ```

   Or using WordPress database:
   ```sql
   UPDATE wp_options 
   SET option_value = '{"github_api_token":"your_token_here"}' 
   WHERE option_name = 'osprojects-settings';
   ```

#### URL Structure Configuration

By default, projects will be available at `/projects/`. To customize this:

1. Go to **Open Source Projects > Settings**
2. Change the **Project URL Prefix** field
3. Update your WordPress permalinks:
   - Go to **Settings > Permalinks**
   - Click **Save Changes** (even without making changes)

#### Editor Mode

**Gutenberg Mode** is enabled by default if your theme allows it. You can disable it to force **Classic Mode**.

Note: If you experience saving issues in Gutenberg mode, try clearing browser cookies or switch to Classic mode.

= First Steps After Installation =

1. **Import Your First Repository**:
   - Go to **Tools > Import**
   - Select **OSProjects Importer**
   - Enter a GitHub user URL (e.g., `https://github.com/your-username`)
   - Select repositories to import

2. **Create Project Categories**:
   - Go to **Open Source Projects > Project Categories**
   - Create categories to organize your projects

3. **Check Your Frontend**:
   - Visit `/projects/` on your site to see the project archive
   - Visit individual project pages to see project details

= Security Considerations =

- Only download the plugin from official GitHub releases
- Keep the plugin updated to the latest version
- Use GitHub personal access tokens instead of passwords
- Regularly review imported projects for any issues


== Changelog ==

= 1.0.0 Initial release =
- Custom `project` post type for managing open source projects
- Project categories taxonomy with hierarchical structure
- GitHub repository import system with WordPress importer integration
- GitHub API pagination support (handles up to 1000 repositories)
- Automated project metadata extraction (commits, releases, license, etc.)
- Smart duplicate detection and redirect handling during import
- Project status management with custom "ignored" status
- AJAX-powered project metadata updates in admin interface
- Bulk operations for project management (ignore/unignore)
- Manual project refresh functionality with progress tracking
- Custom project display templates with metadata showcase
- Admin interface with dedicated menu and filtering options
- French translation support (fr_FR)
- Internationalization framework with .pot template
- Gutenberg block editor support with classic editor fallback
- Project URL prefix configuration
- Daily automated project refresh via WP-Cron
- Comprehensive error handling and logging
- Git repository analysis with czproject/git-php integration
- Markdown content parsing with erusev/parsedown
- Security features with nonce protection and input sanitization

**Technical Features**
- WordPress coding standards compliance
- Composer dependency management
- Grunt-based build system for development
- Translation workflow with gettext tools
- Unit test framework setup
- GitHub Actions workflow for releases
- Comprehensive documentation (README, INSTALLATION, DEVELOPERS)
