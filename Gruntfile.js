/*jshint node:true */

module.exports = function( grunt ) {
	'use strict';

	require( 'matchdep' ).filterDev( 'grunt-*' ).forEach( grunt.loadNpmTasks );

	grunt.initConfig( {

		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: [
				'Gruntfile.js',
				'js/*.js',
				'!js/*.min.js'
			]
		},

		makepot: {
			plugin: {
				options: {
					mainFile: 'better-internal-link-search.php',
					potHeaders: {
						'report-msgid-bugs-to': 'http://wordpress.org/support/plugin/better-internal-link-search',
						'language': 'en',
						'plural-forms': 'nplurals=2; plural=(n != 1);',
						'x-poedit-basepath': '../',
						'x-poedit-bookmarks': '',
						'x-poedit-country': 'United States',
						'x-poedit-keywordslist': '__;_e;__ngettext:1,2;_n:1,2;__ngettext_noop:1,2;_n_noop:1,2;_c;_nc:1,2;_x:1,2c;_ex:1,2c;_nx:4c,1,2;_nx_noop:4c,1,2;',
						'x-poedit-searchpath-0': '.',
						'x-poedit-sourcecharset': 'utf-8',
						'x-textdomain-support': 'yes'
					},
					type: 'wp-plugin',
					updateTimestamp: false
				}
			}
		},

		uglify: {
			dist: {
				files: [
					{
						src: [
							'js/internal-link-manager.js',
							'js/posts-list-table.js'
						],
						dest: 'js/better-internal-link-search.min.js'
					}
				]
			}
		},

		watch: {
			js: {
				files: ['<%= jshint.all %>'],
				tasks: ['jshint']
			}
		}

	} );

	grunt.registerTask( 'default', ['jshint', 'watch']);

};
