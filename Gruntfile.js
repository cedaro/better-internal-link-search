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
						poedit: true
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
