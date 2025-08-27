const copy = require( "grunt-contrib-copy" );
const fs   = require( 'fs' );
const path = require( 'path' );

module.exports = function(grunt, pluginName) {

    'use strict';

    function getCurrentWordPressVersion() {
        const wpVersionFile = path.join( __dirname, '../../../wp-includes/version.php' );
        if (fs.existsSync( wpVersionFile )) {
            const content = fs.readFileSync( wpVersionFile, 'utf8' );
            const match   = content.match( /\$wp_version = '([^']+)'/ );
            return match ? match[1] : null;
        }
        return null;
    }

    // Function to get the minimum required PHP version from the installation
    function getMinimumPHPVersion() {
        const wpVersionFile = path.join( __dirname, '../../../wp-includes/version.php' );
        if (fs.existsSync( wpVersionFile )) {
            const content = fs.readFileSync( wpVersionFile, 'utf8' );
            const match   = content.match( /\$required_php_version = '([^']+)'/ );
            return match ? match[1] : null;
        }
        return null;
    }

    // Custom Task: Update Text Domain
    // grunt.config.set('addtextdomain.options.textdomain', pluginName);

    // Custom Task: Make POT File
    // grunt.config.set('makepot.target.options.mainFile', `${pluginName}.php`);
    // grunt.config.set('makepot.target.options.potFilename', `${pluginName}.pot`);

    grunt.config.set('addtextdomain', {
        options: {
            textdomain: pluginName,
        },
        update_all_domains: {
            options: {
                updateDomains: true
            },
            src: [ '*.php', '**/*.php', '!\.git/**/*', '!bin/**/*', '!node_modules/**/*', '!tests/**/*' ]
        }
    });

    // Custom Task: Make MO Files
    grunt.config.set('makemo', {
        target: {
            files: [{
                expand: true,
                cwd: 'languages',
                src: ['*.po'],
            }]
        }
    });

    grunt.config.set('makepot', {
        target: {
            options: {
                domainPath: '/languages',
                exclude: [ '\.git/*', 'bin/*', 'node_modules/*', 'vendor/*', 'tests/*', 'tmp/*', 'src/*' ],
                mainFile: `${pluginName}.php`,
                potFilename: `${pluginName}.pot`,
                potHeaders: {
                    poedit: true,
                    'x-poedit-keywordslist': true
                },
                type: 'wp-plugin',
                updateTimestamp: true
            }
        }
    });


    // Custom Task: Copy Files (from other project)
    grunt.config.set('copy', {
        header: {
            src: 'readme.txt',
            dest: 'readme-temp-header-1-copy.txt',
            options: {
                process: function (content) {
                    const parts = content.split( '== Description ==' );
                    return parts[0] + '== Description ==\n';
                }
            }
        },
        append: {
            src: 'readme-temp-md-2-format.txt',
            dest: 'readme-temp-header-2-update.txt',
            options: {
                process: function (content) {
                    const existingContent = grunt.file.read( 'readme-temp-header-2-update.txt' );
                    return existingContent + '\n\n' + content;
                }
            }
        },
    });

    // Custom Task: Concat Files (from other project)
    grunt.config.set('concat', {
        md: {
            options: {
                separator: '\n\n', // Add a new line between files
            },
            src: ['README.md', 'INSTALLATION.md', 'FAQ.md', 'CHANGELOG.md'],
            dest: 'readme-temp-md-1-concat.md',
        },
        combine: {
            options: {
                separator: '\n',
            },
            src: ['readme-temp-header-2-update.txt', 'readme-temp-md-3-clean.txt'],
            dest: 'readme.txt',
        },
    });

    // Custom Task: Replace Content (from other project)
    grunt.config.set('replace', {
        format: {
            src: ['readme-temp-md-1-concat.md'],
            dest: 'readme-temp-md-2-format.txt',
            replacements: [{
                from: /^#  *(.*) *$/gm,
                to: '=== $1 ==='
            }, {
                from: /^##  *(.*) *$/gm,
                to: '== $1 =='
            }, {
                from: /^###  *(.*) *$/gm,
                to: '= $1 ='
            }, {
                from: /^- /gm,
                to: '* '
            }]
        },
        updateinfo: {
            src: ['readme-temp-header-1-copy.txt'],
            dest: 'readme-temp-header-2-update.txt',
            replacements: [{
                from: /Plugin Name: .*/g,
                to: function (matchedWord) {
                    const content = grunt.file.read( `${pluginName}.php` );
                    const match   = content.match( /Plugin Name: (.*)/ );
                    return match ? `Plugin Name: ${match[1]}` : matchedWord;
                }
            }, {
                from: /Plugin URI: .*/g,
                to: function (matchedWord) {
                    const content = grunt.file.read( `${pluginName}.php` );
                    const match   = content.match( /Plugin URI: (.*)/ );
                    return match ? `Plugin URI: ${match[1]}` : matchedWord;
                }
            }, {
                from: /Description: .*/g,
                to: function (matchedWord) {
                    const content = grunt.file.read( `${pluginName}.php` );
                    const match   = content.match( /Description: (.*)/ );
                    return match ? `Description: ${match[1]}` : matchedWord;
                }
            }, {
                from: /Version: .*/g,
                to: function (matchedWord) {
                    const content = grunt.file.read( `${pluginName}.php` );
                    const match   = content.match( /Version: (.*)/ );
                    return match ? `Version: ${match[1]}` : matchedWord;
                }
            }, {
                from: /Author: .*/g,
                to: function (matchedWord) {
                    const content = grunt.file.read( `${pluginName}.php` );
                    const match   = content.match( /Author: (.*)/ );
                    return match ? `Author: ${match[1]}` : matchedWord;
                }
            }, {
                from: /Author URI: .*/g,
                to: function (matchedWord) {
                    const content = grunt.file.read( `${pluginName}.php` );
                    const match   = content.match( /Author URI: (.*)/ );
                    return match ? `Author URI: ${match[1]}` : matchedWord;
                }
            }, {
                from: /License: .*/g,
                to: function (matchedWord) {
                    const content = grunt.file.read( `${pluginName}.php` );
                    const match   = content.match( /License: (.*)/ );
                    return match ? `License: ${match[1]}` : matchedWord;
                }
            }, {
                from: /License URI: .*/g,
                to: function (matchedWord) {
                    const content = grunt.file.read( `${pluginName}.php` );
                    const match   = content.match( /License URI: (.*)/ );
                    return match ? `License URI: ${match[1]}` : matchedWord;
                }
            }, {
                from: /Requires at least: .*/g,
                to: function (matchedWord) {
                    const content = grunt.file.read( `${pluginName}.php` );
                    const match   = content.match( /Requires at least: (.*)/ );
                    return match ? `Requires at least: ${match[1]}` : matchedWord;
                }
            }, {
                from: /Requires PHP: .*/g,
                to: function (matchedWord) {
                    const content = grunt.file.read( `${pluginName}.php` );
                    const match   = content.match( /Requires PHP: (.*)/ );
                    return match ? `Requires PHP: ${match[1]}` : matchedWord;
                }
            }, {
                from: /Tested up to: .*/g,
                to: function (matchedWord) {
                    const version = getCurrentWordPressVersion();
                    return version ? `Tested up to: ${version}` : matchedWord;
                }
            }, {
                from: /^\n\n*(.*)\n*\n==/gm,
                to: function (match, p1) {
                    const content          = grunt.file.read( `${pluginName}.php` );
                    const descriptionMatch = content.match( /Description: (.*)/ );
                    return descriptionMatch ? `\n${descriptionMatch[1]}\n\n==` : `\n${p1}\n\n==`;
                }
            }]
        },
        removeTitle: {
            src: ['readme-temp-md-2-format.txt'],
            dest: 'readme-temp-md-3-clean.txt',
            replacements: [{
                from: /^=== .* ===\n*/,
                to: ''
            }]
        }
    });

    // Custom Task: Clean Files (from other project)
    grunt.config.set('clean', {
        temp: ['readme-temp-header-1-copy.txt', 'readme-temp-md-1-concat.md', 'readme-temp-md-2-format.txt', 'readme-temp-header-2-update.txt', 'readme-temp-md-3-clean.txt']
    });

    // Load all necessary Grunt plugins
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-text-replace');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-wp-i18n');
    grunt.loadNpmTasks('grunt-wp-readme-to-markdown');

    // Register custom tasks
    // grunt.registerTask('default', [ 'makereadmetxt', 'i18n', 'makepot', 'makemo' ]);
    grunt.registerTask('i18n', [ 'makereadmetxt', 'addtextdomain', 'makepot', 'makemo' ]);
    grunt.registerTask( 'makereadmetxt', [
		'copy:header',
		'replace:updateinfo',
		'concat:md',
		'replace:format',
		'replace:removeTitle',
		'concat:combine',
		'clean:temp'
    ] );
    grunt.registerTask('makemo', 'Convert PO files to MO using WP CLI', function() {
        var done = this.async();
        var poFiles = grunt.file.expand('languages/*.po');

        if (!poFiles.length) {
            grunt.log.error('No PO files found');
            return done(false);
        }

        var completed = 0;
        poFiles.forEach(function(poFile) {
            grunt.util.spawn({
                cmd: 'wp',
                args: ['i18n', 'make-mo', poFile]
            }, function(error, result, code) {
                if (error) {
                    grunt.log.error('Error processing ' + poFile + ': ' + error);
                    return done(false);
                }
                grunt.log.writeln('Converted ' + poFile);
                completed++;
                if (completed === poFiles.length) {
                    done();
                }
            });
        });
    });

    grunt.util.linefeed = '\n';

};
