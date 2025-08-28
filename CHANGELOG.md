# Changelog

-## [1.0.0] - 2025-08-28

### Initial release
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

### Technical Features
- WordPress coding standards compliance
- Composer dependency management
- Grunt-based build system for development
- Translation workflow with gettext tools
- Unit test framework setup
- GitHub Actions workflow for releases
- Comprehensive documentation (README, INSTALLATION, DEVELOPERS)
