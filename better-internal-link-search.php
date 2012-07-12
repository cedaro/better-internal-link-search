<?php
/*
Plugin Name: Better Internal Link Search
Plugin URI: http://wordpress.org/extend/plugins/better-internal-link-search/
Version: 1.1
Description: Search by post or page title when adding links into the editor or adding pages to a nav menu.
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


Blazer_Six_Better_Internal_Link_Search::start();


class Blazer_Six_Better_Internal_Link_Search {
	static $s;
	
	/**
	 * Start when plugins are loaded
	 * 
	 * @since 1.0
	 */
	function start() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_plugin' ) );
	}

	/**
	 * Hook into actions to execute when needed
	 * 
	 * @since 1.0
	 */
	function load_plugin() {
		add_action( 'wp_ajax_bils-get-link-search-results', array( __CLASS__, 'ajax_get_link_search_results' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_footer-post.php', array( __CLASS__, 'admin_footer' ) );
		add_action( 'admin_footer-post-new.php', array( __CLASS__, 'admin_footer' ) );
	}
	
	/**
	 * Add a filter to limit search results
	 * 
	 * The filter is only attached when a request comes from the Pages meta
	 * box on the Menus screen or from the "Insert/edit link" editor popup.
	 * 
	 * @since 1.0
	 */
	function admin_init() {
		if ( defined('DOING_AJAX') && DOING_AJAX && isset( $_POST['action'] ) ) {
			if ( 'bils-get-link-search-results' == $_POST['action'] || 'menu-quick-search' == $_POST['action'] || 'wp-link-ajax' == $_POST['action'] ) {
				add_filter( 'posts_search', array( __CLASS__, 'limit_search_to_title' ), 10, 2 );
			}
		}
	}
	
	/**
	 * Limits search queries to the post title field
	 * 
	 * @see wp-includes/query.php
	 * 
	 * @since 1.0
	 */
	function limit_search_to_title( $search, &$wp_query ) {
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
	 * Returns search results
	 *
	 * Results return in a format expected by the internal link manager.
	 * Doesn't have support for paging.
	 * 
	 * @since 1.1
	 */
	function ajax_get_link_search_results() {
		global $wpdb;
		
		check_ajax_referer( 'internal-linking', '_ajax_linking_nonce' );
		
		if ( isset( $_POST['search'] ) ) {
			$s = stripslashes( $_POST['search'] );
			
			$args = array(
				's' => $s,
				'pagenum' => 1
			);
			
			require_once(ABSPATH . WPINC . '/class-wp-editor.php');
			$results = _WP_Editors::wp_link_query( $args );
			
			
			$search = '%' . like_escape( $s ) . '%';
			$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.term_id, t.name, tt.taxonomy
				FROM $wpdb->terms t
				INNER JOIN $wpdb->term_taxonomy tt ON t.term_id=tt.term_id
				WHERE t.name LIKE %s
				ORDER BY name ASC", $search ) );
			
			if ( $terms ) {
				foreach ( $terms as $term ) {
					$taxonomy = get_taxonomy( $term->taxonomy );
					
					$results[] = array(
						'title' => trim( esc_html( strip_tags( $term->name ) ) ),
						'permalink' => get_term_link( (int) $term->term_id, $term->taxonomy ),
						'info' => $taxonomy->labels->singular_name
					);
				}
			}
			
			if ( ! empty( $results ) ) {
				self::$s = $s;
				$results = apply_filters( 'better_internal_link_search_results', $results, $args );
				usort( $results, array( __CLASS__, 'sort_results' ) );	
			} else {
				$results = array();	
			}
			
			
			$shortcuts = apply_filters( 'better_internal_link_search_shortcuts', array(
				'home' => array(
					'title' => 'Home',
					'permalink' => home_url( '/' )
				),
				'siteurl' => array(
					'title' => 'Site URL',
					'permalink' => site_url( '/' )
				),
				'theme' => array(
					'title' => 'Theme URL',
					'permalink' => get_stylesheet_directory_uri() . '/'
				)
			) );
			
			// sanitize the shortcuts a bit
			foreach( $shortcuts as $key => $shortcut ) {
				if ( empty( $shortcut['title'] ) || empty( $shortcut['permalink'] ) ) {
					unset( $shortcuts[ $key ] );
					break;
				}
				
				if ( empty( $shortcut['info'] ) )
					$shortcuts[ $key ]['info'] = 'Shortcut';
				
				$shortcuts[ $key ]['title'] = trim( esc_html( strip_tags( $shortcut['title'] ) ) );
				$shortcuts[ $key ]['info'] = trim( esc_html( strip_tags( $shortcuts[ $key ]['info'] ) ) );
			}
			
			if ( array_key_exists( $s, $shortcuts ) ) {
				array_unshift( $results, $shortcuts[ $s ] );
			} elseif ( 'shortcuts' == $s ) {
				$results = array_merge( $shortcuts, $results );	
			}
		}
		
	
		if ( ! isset( $results ) )
			wp_die( 0 );
	
		echo json_encode( $results );
		echo "\n";
	
		wp_die();
	}
	
	/**
	 * Custom results sorter
	 * 
	 * Attempts to return results in a more natural order. Titles that exactly match
	 * a search query are returned first, followed by titles that begin with the query.
	 * Remaining results are sorted alphabetically.
	 *
	 * TODO: Potentially remove articles (a, an, the) when doing matches.
	 *
	 * @since 1.1
	 */
	function sort_results( $a, $b ) {
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
			// return the shorter title first
			return ( mb_strlen( $a_title ) < mb_strlen( $b_title ) ) ? -1 : 1;
		} elseif ( 0 === $a_strpos ) {
			return -1;
		} elseif ( 0 === $b_strpos ) {
			return 1;	
		}
		
		return strcmp( $a_title, $b_title );
	}
	
	/**
	 * Javascript to automatically search for selected text
	 * 
	 * Inserts any text selected in the editor into the search field in the
	 * "Insert/edit link" popup when the link button in the toolbar is
	 * clicked. Automatically executes a search request and returns the
	 * results.
	 * 
	 * @since 1.0
	 */
	function admin_footer() {
		?>
		<script type="text/javascript">
		jQuery(function($) {
			// hijack ajax requests and replace with a custom action
			$.ajaxPrefilter(function(options, originalOptions, jqXHR) {
				if ( -1 != options.data.indexOf('search=') ) {
					options.data = options.data.replace('action=wp-link-ajax', 'action=bils-get-link-search-results');
				}
			});
				
			$('#wp-link').bind('wpdialogbeforeopen', function() {
				var searchTerm = '';
				
				// determine what text is selected in the editor
				if ( 'undefined' != typeof tinyMCE && ( editor = tinyMCE.activeEditor ) && ! editor.isHidden() ) {
					var a = editor.dom.getParent(editor.selection.getNode(), 'A');
					if ( null == a ) {
						searchTerm = editor.selection.getContent();
					} else {
						searchTerm = $(a).text();
					}
				} else {
					var start = wpLink.textarea().selectionStart,
						end = wpLink.textarea().selectionEnd;
					
					if ( 0 < end-start ) {
						searchTerm = wpLink.textarea().value.substring( start, end );
					}
				}
				
				// strip any html to get a clean search term
				if ( -1 !== searchTerm.indexOf('<') ) {
					searchTerm = searchTerm.replace(/(<[^>]+>)/ig,'');
				}
				
				if ( searchTerm.length ) {
					$('#search-field').val( $.trim(searchTerm) ).keyup();
				}
				
				/*
				Prototype for selecting entities to search
				if ( !$('#bils-search-type-content').is('input') ) {
					$('div.link-search-wrapper', '#search-panel').append('<label style="margin: 0 10px"><input type="radio" name="bils_search_type" id="bils-search-type-content" class="bils-search-type" checked="checked" /> Posts</label> <label><input type="radio" name="bils_search_type" id="bils-search-type-terms" class="bils-search-type" /> Terms</label>');
					$('input.bils-search-type', '#search-panel').on('click', function() {
						var searchField = $('#search-field'),
							s = searchField.val();
						
						// a wee bit hackish
						searchField.val('better-internal-link-search-reset-river-flag').keyup().val(s).keyup().focus()[0].select();
					});
				}
				
				// filter ajax requests in order to send the right action
				$.ajaxPrefilter(function(options, originalOptions, jqXHR) {
					if ( $('#bils-search-type-terms').prop('checked') && -1 != options.data.indexOf('wp-link-ajax') ) {
						// abort the request if it's just for resetting the river
						if ( -1 != options.data.indexOf('better-internal-link-search-reset-river-flag') )
							jqXHR.abort();
						
						options.data = options.data.replace('action=wp-link-ajax', 'action=bils-get-link-search-results');
					}
				});
				*/
			});
		});
		</script>
		<?php
	}
}
?>