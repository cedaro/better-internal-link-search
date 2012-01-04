<?php
/*
Plugin Name: Better Internal Link Search
Version: 0.1
Description: Limit search to the post title when adding links to content or adding pages to a menu.
Author: Blazer Six, Inc.
Author URI: http://www.blazersix.com/
*/

/**
On sites with a large number of posts/pages, the search feature quickly becomes useless when trying to
link content because WordPress defaults to searching the title and content fields. This plugin helps remedy
that by limiting searches to the title field only. It works when creating internal links with the
"Insert/edit link" popup in the editor and when searching for pages to add to a menu.

For a slight producivity boost, it also automatically searches for the selected text when the link button
is clicked on the editor toolbar.
*/


class Blazer_Six_Better_Internal_Link_Search {
	function __construct() {
		add_action( 'plugins_loaded', array( &$this, 'load_plugin' ) );
	}
	
	
	function load_plugin() {
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_footer-post.php', array( &$this, 'admin_footer' ) );
	}
	
	
	function admin_init() {
		if ( defined('DOING_AJAX') && DOING_AJAX && isset( $_POST['action'] ) ) {
			if ( 'menu-quick-search' == $_POST['action'] || 'wp-link-ajax' == $_POST['action'] ) {
				add_filter( 'posts_search', array( &$this, 'limit_search_to_title' ), 10, 2 );
			}
		}
	}
	
	
	/**
	 * Mostly comes from wp-includes/query.php
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
	 * Automatically searches for the selected text when using internal linking
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
$blazersix_better_internal_link_search = new Blazer_Six_Better_Internal_Link_Search();
?>