# Installation

### Requirements

* **WordPress**: 5.0 or higher
* **PHP**: 7.4 or higher
* **Dependencies**: Automatically included with the plugin
  - `czproject/git-php` - Git repository interaction
  - `erusev/parsedown` - Markdown parsing

### Installation

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

### Configuration

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

### First Steps After Installation

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

### Security Considerations

* Only download the plugin from official GitHub releases
* Keep the plugin updated to the latest version
* Use GitHub personal access tokens instead of passwords
* Regularly review imported projects for any issues
