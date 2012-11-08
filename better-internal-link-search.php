<?php
/*
Plugin Name: Better Internal Link Search
Plugin URI: http://wordpress.org/extend/plugins/better-internal-link-search/
Version: 1.1.2
Description: Improve the internal link popup functionality with time saving enhancements and features.
Author: Blazer Six, Inc.
Author URI: http://www.blazersix.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

------------------------------------------------------------------------
Copyright 2012  Blazer Six, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Load the plugin.
 */
if ( is_admin() ) {
	add_action( 'plugins_loaded', array( 'Blazer_Six_Better_Internal_Link_Search', 'load' ) );
}

/**
 * Main plugin class.
 *
 * @since 1.0
 */
class Blazer_Six_Better_Internal_Link_Search {
	private static $s;
	
	/**
	 * Hook into actions to execute when needed.
	 * 
	 * @since 1.0
	 */
	public static function load() {
		add_action( 'admin_init', array( __CLASS__, 'upgrade' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		
		if ( isset( $_POST['search'] ) ) {
			remove_action( 'wp_ajax_wp-link-ajax', 'wp_link_ajax', 1 );
			add_action( 'wp_ajax_wp-link-ajax', array( __CLASS__, 'ajax_get_link_search_results' ), 1 );
			#add_action( 'wp_ajax_bils-get-link-search-results', array( __CLASS__, 'ajax_get_link_search_results' ) );
		}
		
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_footer-post.php', array( __CLASS__, 'admin_footer' ) );
		add_action( 'admin_footer-post-new.php', array( __CLASS__, 'admin_footer' ) );
	}
	
	/**
	 * Add a filter to limit search results.
	 * 
	 * The filter is only attached when a request comes from the Pages meta
	 * box on the Menus screen or from the "Insert/edit link" editor popup.
	 * 
	 * @since 1.0
	 */
	public static function admin_init() {
		add_filter( 'better_internal_link_search_modifier-help', array( __CLASS__, 'search_modifier_help' ), 10, 2 );
		
		// Disable default search modifiers by returning false for this filter.
		if ( apply_filters( 'better_internal_link_search_load_default_modifiers', true ) ) {
			include ( plugin_dir_path(__FILE__) . 'search-modifiers.php' );
		}
		
		if ( defined('DOING_AJAX') && DOING_AJAX && isset( $_POST['action'] ) ) {
			$actions = array(
				'bils-get-link-search-results',
				'menu-quick-search',
				'wp-link-ajax'
			);
			
			if ( in_array( $_POST['action'], $actions ) ) {
				add_filter( 'posts_search', array( __CLASS__, 'limit_search_to_title' ), 10, 2 );
				add_action( 'pre_get_posts', array( __CLASS__, 'set_query_vars' ) );
			}
		}
	}
	
	/**
	 * Register plugin settings.
	 * 
	 * Adds a setting to enable the text selected in the editor to be searched
	 * automatically. This was the default behavior in versions prior to
	 * version 1.1.2, but it can cause a delay on large sites.
	 * 
	 * @since 1.1.2
	 */
	public static function register_settings() {
		register_setting( 'writing', 'better_internal_link_search' );
		
		add_settings_section( 'better-internal-link-search', __( 'Internal Linking', 'better-internal-link-search' ), '__return_null', 'writing' );

		add_settings_field(
			'extensions',
			__( 'Automatic Search', 'better-internal-link-search' ),
			array( __CLASS__, 'automatic_internal_link_search_field' ),
			'writing',
			'better-internal-link-search'
		);
	}
	
	/**
	 * Automatic search setting field.
	 * 
	 * @since 1.1.2
	 */
	public static function automatic_internal_link_search_field() {
		$settings = self::get_settings();
		?>
		<input type="checkbox" name="better_internal_link_search[automatically_search_selection]" id="better-internal-link-search-automatically-search-selection" value="yes"<?php checked( $settings['automatically_search_selection'], 'yes' ); ?>>
		<label for="better-internal-link-search-automatically-search-selection"><?php _e( 'Automatically search for text selected in the editor?', 'better-internal-link-search' ); ?></label>
		<br><span class="description"><?php _e( 'May cause a slight delay on sites with a lot of content.', 'better-internal-link-search' ); ?></span>
		<?php
	}
	
	/**
	 * Set query vars in pre_get_posts.
	 *
	 * Includes scheduled posts in search results and disables paging.
	 *
	 * @since 1.1
	 */
	public static function set_query_vars( $query ) {
		if ( 'bils-get-link-search-results' == $_POST['action'] || 'wp-link-ajax' == $_POST['action'] ) {
			// Scheduled post concept from Evan Solomon's plugin.
			// http://wordpress.org/extend/plugins/internal-linking-for-scheduled-posts/
			$post_status = (array) $query->get( 'post_status' );
			if ( ! in_array( 'future', $post_status ) ) {
				$post_status[] = 'future';
				$query->set( 'post_status', $post_status );
			}
			
			// Make sure 'posts_per_page' hasn't been explicitly set by a modifier to allow for paging of local results before overriding it.
			if ( ! $query->get( 'posts_per_page' ) ) {
				// Paging won't work with multiple data sources and ideally the search term
				// should be unique enough that there aren't a ton of matches.
				$query->set( 'posts_per_page', -1 );
			}
		}
	}
	
	/**
	 * Limits search queries to the post title field.
	 * 
	 * @see wp-includes/query.php
	 * 
	 * @since 1.0
	 */
	public static function limit_search_to_title( $search, &$wp_query ) {
		global $wpdb;
		
		if ( empty( $search ) ) {
			return $search;
		}
		
		$q = $wp_query->query_vars;
		$n = ! empty( $q['exact'] ) ? '' : '%';
		$search = '';
		$searchand = '';
		
		foreach( (array) $q['search_terms'] as $term ) {
			$term = esc_sql( like_escape( $term ) );
			$search.= "{$searchand}(($wpdb->posts.post_title LIKE '{$n}{$term}{$n}'))";
			$searchand = ' AND ';
		}
	
		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
		}
		
		return $search;
	}
	
	/**
	 * Returns search results.
	 *
	 * Results returned in a format expected by the internal link manager.
	 * Doesn't have support for paging.
	 *
	 * Multiple filters provided for either adding results or short-circuiting
	 * the flow at various points.
	 *
	 * @since 1.1
	 */
	public static function ajax_get_link_search_results() {
		global $wpdb;
		
		check_ajax_referer( 'internal-linking', '_ajax_linking_nonce' );
		
		if ( isset( $_POST['search'] ) ) {
			$results = array();
			$s = stripslashes( $_POST['search'] );
			
			$args['s'] = $s;
			$args['page'] = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
			$args['per_page'] = 20; // Default for usage in filters, otherwise, it shouldn't do anything.
			
			// Check to see if the request is prepended with a modifier (ex: -wikipedia interrobang, -spotify:artist willie nelson).
			if ( 0 === mb_strpos( $s, '-' ) ) {
				preg_match( '/-([^\s]+)\s?(.*)?/', $s, $matches );
				
				$s = trim( $matches[2] );
				$args['s'] = $s;
				$args['modifier'] = explode( ':', trim( $matches[1] ) );
				
				$results = (array) apply_filters( 'better_internal_link_search_modifier-' . $args['modifier'][0], array(), $args );
				if ( ! empty( $results ) ) {
					echo json_encode( $results );
					wp_die();
				}
			}
			
			// Allow plugins to intercept the request and add their own results or short-circuit execution.
			$pre_results = (array) apply_filters( 'pre_better_internal_link_search_results', array(), $args );
			if ( ! empty( $pre_results ) ) {
				$array_merge( $results, $pre_results );
			}
			
			// Short-circuit if this is a paged request. The first request should have returned all results.
			if ( isset( $_POST['page'] ) && $_POST['page'] > 1 ) {
				wp_die( 0 );
			}
			
			// Don't continue if the query length is less than three.
			if ( strlen( $args['s'] ) < 3 ) {
				wp_die( 0 );
			}
			
			// @see wp_link_ajax();
			require_once(ABSPATH . WPINC . '/class-wp-editor.php');
			$posts = _WP_Editors::wp_link_query( $args );
			if ( $posts ) {
				$post_status_object = get_post_status_object( 'future' );
				
				foreach( $posts as $key => $post ) {
					if ( 'future' == get_post_status( $post['ID'] ) ) {
						$posts[ $key ]['info'] = $post_status_object->label;
					}
				}
				
				$results = array_merge( $results, $posts );
			}
			
			// Search for matching term archives.
			$search = '%' . like_escape( $s ) . '%';
			$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.term_id, t.name, tt.taxonomy
				FROM $wpdb->terms t
				INNER JOIN $wpdb->term_taxonomy tt ON t.term_id=tt.term_id
				WHERE t.name LIKE %s
				ORDER BY name ASC", $search ) );
			
			if ( $terms ) {
				foreach ( $terms as $term ) {
					$taxonomy = get_taxonomy( $term->taxonomy );
					
					if ( $taxonomy->query_var ) {
						$results[] = array(
							'title' => trim( esc_html( strip_tags( $term->name ) ) ),
							'permalink' => get_term_link( (int) $term->term_id, $term->taxonomy ),
							'info' => $taxonomy->labels->singular_name
						);
					}
				}
			}
			
			// Allow results to be filtered one last time and attempt to sort them.
			if ( ! empty( $results ) ) {
				self::$s = $s;
				$results = apply_filters( 'better_internal_link_search_results', $results, $args );
				
				if ( apply_filters( 'better_internal_link_search_sort_results', true, $results, $args ) ) {
					usort( $results, array( __CLASS__, 'sort_results' ) );	
				}
			}
			
			// Add shortcut results.
			$shortcuts = (array) self::get_shortcuts();
			if ( ! empty( $shortcuts ) ) {
				if ( array_key_exists( $s, $shortcuts ) ) {
					array_unshift( $results, $shortcuts[ $s ] );
				} elseif ( 'shortcuts' == $s ) {
					$results = array_merge( $shortcuts, $results );	
				}
			}
		}
		
		if ( ! isset( $results ) || empty( $results ) ) {
			wp_die( 0 );
		}
		
		echo json_encode( $results );
		echo "\n";
		wp_die();
	}
	
	/**
	 * Internal link shortcuts.
	 *
	 * A couple of basic shortcuts for easily linking to the home url and site
	 * url. Also gives plugins the ability to add more shortcuts.
	 *
	 * @since 1.1
	 */
	public static function get_shortcuts() {
		$shortcuts = apply_filters( 'better_internal_link_search_shortcuts', array(
			'home' => array(
				'title' => 'Home',
				'permalink' => home_url( '/' )
			),
			'siteurl' => array(
				'title' => 'Site URL',
				'permalink' => site_url( '/' )
			)
		) );
		
		if ( ! empty( $shortcuts ) ) {
			// Sanitize the shortcuts a bit.
			foreach( $shortcuts as $key => $shortcut ) {
				if ( empty( $shortcut['title'] ) || empty( $shortcut['permalink'] ) ) {
					unset( $shortcuts[ $key ] );
					break;
				}
				
				if ( empty( $shortcut['info'] ) ) {
					$shortcuts[ $key ]['info'] = 'Shortcut';
				}
				
				$shortcuts[ $key ]['title'] = trim( esc_html( strip_tags( $shortcut['title'] ) ) );
				$shortcuts[ $key ]['info'] = trim( esc_html( strip_tags( $shortcuts[ $key ]['info'] ) ) );
			}
		}
		
		return $shortcuts;
	}
	
	/**
	 * Custom results sorter.
	 * 
	 * Attempts to return results in a more natural order. Titles that exactly
	 * match a search query are returned first, followed by titles that begin
	 * with the query. Remaining results are sorted alphabetically.
	 *
	 * @todo Potentially remove articles (a, an, the) when doing matches.
	 *
	 * @since 1.1
	 */
	public static function sort_results( $a, $b ) {
		$a_title = mb_strtolower( $a['title'] );
		$b_title = mb_strtolower( $b['title'] );
		$s = mb_strtolower( self::$s );
		
		if ( $a_title == $b_title ) {
			return 0;
		}
		
		if ( $s == $a_title ) {
			return -1;
		} elseif ( $s == $b_title ) {
			return 1;
		}
		
		$a_strpos = mb_strpos( $a_title, $s );
		$b_strpos = mb_strpos( $b_title, $s );
		if ( 0 === $a_strpos && 0 === $b_strpos ) {
			// Return the shorter title first.
			return ( mb_strlen( $a_title ) < mb_strlen( $b_title ) ) ? -1 : 1;
		} elseif ( 0 === $a_strpos ) {
			return -1;
		} elseif ( 0 === $b_strpos ) {
			return 1;	
		}
		
		return strcmp( $a_title, $b_title );
	}
	
	/**
	 * Javascript to automatically search for text selected in the editor.
	 *
	 * Inserts any text selected in the editor into the search field in the
	 * "Insert/edit link" popup when the link button in the toolbar is
	 * clicked. Automatically executes a search request and returns the
	 * results.
	 *
	 * @since 1.0
	 */
	public static function admin_footer() {
		?>
		<script type="text/javascript">
		// Output a settings object.
		var BILSSettings = <?php echo json_encode( self::get_settings() ); ?>;
		
		jQuery(function($) {
			$.ajaxPrefilter(function(options, originalOptions, jqXHR) {
				if ( -1 != options.data.indexOf('action=wp-link-ajax') && -1 != options.data.indexOf('search=') ) {
					// Abort the request if it's just for resetting the river.
					if ( -1 != options.data.indexOf('better-internal-link-search-reset-river-flag') ) {
						jqXHR.abort();
					}
					
					// Reset the search field to a single dash.
					if ( -1 != options.data.indexOf('search=-help') ) {
						$('#search-field').val('-');
					}
				}
			});
				
			$('#wp-link').bind('wpdialogbeforeopen', function() {
				var searchField = $('#search-field').width(200),
					searchTerm = '-',
					timeout;
				
				// Don't mind me, just debouncing, yo.
				searchField.off('keyup').on('keyup.bils', function() {
					var self = this
						$self = $(this);
					
					clearTimeout(timeout);
					timeout = setTimeout( function() {
						if ( '-' == $self.val() || 0 === $self.val().indexOf('-help') ) {
							// Ugly hack to reset the river...
							$self.val('better-internal-link-search-reset-river-flag');
							wpLink.searchInternalLinks.apply( self );
							// And then bypass the three character minimum requirement.
							$self.val('-help');
						}
						
						wpLink.searchInternalLinks.apply( self );
					}, 500 );
				});
				
				// Determine what text is selected in the editor.
				if ( 'undefined' != typeof tinyMCE && ( editor = tinyMCE.activeEditor ) && ! editor.isHidden() ) {
					var a = editor.dom.getParent(editor.selection.getNode(), 'A');
					if ( null == a ) {
						searchTerm = editor.selection.getContent();
					} else {
						searchTerm = $(a).text();
					}
				} else {
					var start = wpLink.textarea.selectionStart,
						end = wpLink.textarea.selectionEnd;
					
					if ( 0 < end-start ) {
						searchTerm = wpLink.textarea.value.substring(start, end);
					}
				}
				
				// Strip any html to get a clean search term.
				if ( -1 !== searchTerm.indexOf('<') ) {
					searchTerm = searchTerm.replace(/(<[^>]+>)/ig,'');
				}
				
				if ( 'yes' == BILSSettings.automatically_search_selection && searchTerm.length ) {
					searchField.val( $.trim(searchTerm) ).keyup();
				}
			});
		});
		</script>
		<style type="text/css">
		#wp-link .item-description { display: block; padding: 3px 0 0 10px;}
		#wp-link .link-search-wrapper img.waiting { margin-top: 5px;}
		#wp-link .link-search-wrapper input { float: left;}
		#wp-link .link-search-wrapper span { margin-top: 0; padding-top: 5px; line-height: 15px;}
		</style>
		<?php
	}
	
	/**
	 * Search modifier help.
	 *
	 * Intercepts a request for '-help' and displays any modifiers that have
	 * been added via the filter.
	 *
	 * @since 1.1
	 */
	public static function search_modifier_help( $results, $args ) {
		if ( intval( $args['page'] ) > 1 ) {
			return array();
		}
		
		$results = apply_filters( 'better_internal_link_search_modifier_help', array() );
		if ( ! empty( $results ) && ! empty( $args['s'] ) && array_key_exists( $args['s'], $results ) ) {
			// If the -help request has a search query, limit the returned results to that modifier.
			$results = array( $results[ $args['s'] ] );
		}
		
		return $results;
	}
	
	/**
	 * Retrieve the plugin settings.
	 * 
	 * @since 1.1.2
	 */
	public static function get_settings() {
		$settings = wp_parse_args( (array) get_option( 'better_internal_link_search' ), array(
			'automatically_search_selection' => 'no'
		) );
		
		return $settings;
	}
	
	/**
	 * Upgrade plugin settings.
	 * 
	 * The plugin version is saved as a different option so that updates to
	 * the settings option don't stomp on it.
	 * 
	 * @since 1.1.2
	 */
	public static function upgrade() {
		$saved_version = get_option( 'better_internal_link_search_version' );
		
		// If the plugin version setting isn't set or if it's below 1.1.2, add default settings and update the saved version.
		if ( ! $saved_version || version_compare( $saved_version, '1.1.2', '<' ) ) {
			$plugin_data = get_plugin_data( __FILE__ );
			
			// Add default settings.
			update_option( 'better_internal_link_search', array( 'automatically_search_selection' => 'yes' ) );
			
			// Update saved version number.
			update_option( 'better_internal_link_search_version', $plugin_data['Version'] );
		}
	}
}
?>