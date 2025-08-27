/**
 * Backward compatibility alias for grunt-wp-plugin-tasks.js
 * 
 * This file is maintained for backward compatibility with existing WordPress plugins
 * that are already using grunt-translation-tasks.js.
 * 
 * For new projects:
 * - Use grunt-wp-plugin-tasks.js for WordPress plugins  
 * - Use grunt-translation-tasks.js for other PHP projects
 */

module.exports = function(grunt, pluginName) {
    // Load the WordPress plugin tasks (which includes translation tasks)
    require('./grunt-wp-plugin-tasks.js')(grunt, pluginName);
};
