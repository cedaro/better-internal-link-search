module.exports = function( grunt ) {

	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');

	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		/**
		 * Check JavaScript for errors and warnings.
		 */
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

		/**
		 * Minify JavaScript source files.
		 */
		/*
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
		*/

		/**
		 * Watch sources files and compile when they're changed.
		 */
		watch: {
			js: {
				files: [ '<%= jshint.all %>' ],
				tasks: [ 'jshint', 'uglify' ]
			}
		}

	} );

	/**
	 * Register the default task.
	 */
	grunt.registerTask( 'default', [ 'jshint', 'watch' ] );

};