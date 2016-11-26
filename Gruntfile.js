/*jshint node:true */

module.exports = function( grunt ) {
	'use strict';

	require( 'matchdep' ).filterDev( 'grunt-*' ).forEach( grunt.loadNpmTasks );

	grunt.initConfig({

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
				files: [ '<%= jshint.all %>' ],
				tasks: [ 'jshint' ]
			}
		}

	});

	grunt.registerTask( 'default', [ 'jshint', 'watch' ]);

};
