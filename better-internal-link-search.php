<?php
/*
Plugin Name: Better Internal Link Search
Plugin URI: https://github.com/bradyvercher/wp-better-internal-link-search
Version: 1.0
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
			if ( 'menu-quick-search' == $_POST['action'] || 'wp-link-ajax' == $_POST['action'] ) {
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
			});
		});
		</script>
		<?php
	}
}
?>