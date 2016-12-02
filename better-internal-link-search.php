<?php
/**
 * Better Internal Link Search
 *
 * @package   BetterInternalLinkSearch
 * @author    Brady Vercher
 * @link      https://www.cedaro.com/
 * @copyright Copyright (c) 2016 Cedaro, Inc.
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Better Internal Link Search
 * Plugin URI:  https://wordpress.org/plugins/better-internal-link-search/?utm_source=wordpress-plugin&utm_medium=link&utm_content=better-internal-link-search-plugin-uri&utm_campaign=plugins
 * Description: Improve the internal link popup functionality with time saving enhancements and features.
 * Version:     1.3.0
 * Author:      Cedaro
 * Author URI:  https://www.cedaro.com/?utm_source=wordpress-plugin&utm_medium=link&utm_content=better-internal-link-search-author-uri&utm_campaign=plugins
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: better-internal-link-search
 */

/**
 * Set a constant path to the plugin's root directory.
 */
if ( ! defined( 'BETTER_INTERNAL_LINK_SEARCH_DIR' ) )
	define( 'BETTER_INTERNAL_LINK_SEARCH_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Set a constant URL to the plugin's root directory.
 */
if ( ! defined( 'BETTER_INTERNAL_LINK_SEARCH_URL' ) )
	define( 'BETTER_INTERNAL_LINK_SEARCH_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load the plugin.
 */
if ( is_admin() ) {
	add_action( 'plugins_loaded', array( 'Better_Internal_Link_Search', 'load' ) );
}

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class Better_Internal_Link_Search {
	/**
	 * Static variable for storing the search term.
	 */
	private static $s;

	/**
	 * Hook into actions to modify behavior when needed.
	 *
	 * @since 1.0.0
	 */
	public static function load() {
		// Load settings.
		include( BETTER_INTERNAL_LINK_SEARCH_DIR . 'includes/settings.php' );
		Better_Internal_Link_Search_Settings::load();

		// Load post list table typeahead search.
		include( BETTER_INTERNAL_LINK_SEARCH_DIR . 'includes/posts-list-table.php' );
		Better_Internal_Link_Search_Posts_List_Table::load();

		// Replace the default wp-link-ajax action.
		if ( isset( $_POST['search'] ) ) {
			remove_action( 'wp_ajax_wp-link-ajax', 'wp_link_ajax', 1 );
			add_action( 'wp_ajax_wp-link-ajax', array( __CLASS__, 'ajax_get_link_search_results' ), 1 );
		}

		// Hook it up.
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );

		// Enqueue Internal Link Manager JavaScript and styles.
		add_action( 'wp_enqueue_editor', array( __CLASS__, 'enqueue_editor_assets' ) );

		// Upgrade routine.
		add_action( 'admin_init', array( __CLASS__, 'upgrade' ) );
	}

	/**
	 * Add a filter to limit search results.
	 *
	 * The filter is only attached when a request comes from the Pages meta
	 * box on the Menus screen or from the "Insert/edit link" editor popup.
	 *
	 * @since 1.0.0
	 */
	public static function admin_init() {
		add_filter( 'better_internal_link_search_modifier-help', array( __CLASS__, 'search_modifier_help' ), 10, 2 );

		// Disable default search modifiers by returning false for this filter.
		if ( apply_filters( 'better_internal_link_search_load_default_modifiers', true ) ) {
			include ( BETTER_INTERNAL_LINK_SEARCH_DIR . 'includes/search-modifiers.php' );
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_POST['action'] ) ) {
			$actions = array(
				'bils-get-link-search-results',
				'menu-quick-search',
				'wp-link-ajax',
			);

			if ( in_array( $_POST['action'], $actions ) ) {
				add_filter( 'wp_link_query_args', array( __CLASS__, 'unsupress_posts_search_filters' ) );
				add_filter( 'posts_search', array( __CLASS__, 'limit_search_to_title' ), 10, 2 );
				add_action( 'pre_get_posts', array( __CLASS__, 'set_query_vars' ) );
			}
		}
	}

	/**
	 * Set query vars in pre_get_posts.
	 *
	 * Includes scheduled posts in search results and disables paging.
	 *
	 * @since 1.1.0
	 */
	public static function set_query_vars( $query ) {
		if ( 'bils-get-link-search-results' == $_POST['action'] || 'wp-link-ajax' == $_POST['action'] ) {
			// Scheduled post concept from Evan Solomon's plugin.
			// https://wordpress.org/plugins/internal-linking-for-scheduled-posts/
			$post_status = (array) $query->get( 'post_status' );
			$post_status[] = 'future';
			if ( current_user_can( 'read_private_posts' ) ) {
				$post_status[] = 'private';
			}
			$query->set( 'post_status', array_unique( $post_status ) );

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
	 * @since 1.0.0
	 */
	public static function limit_search_to_title( $search, $wp_query ) {
		global $wpdb;

		if ( empty( $search ) ) {
			return $search;
		}

		$q = $wp_query->query_vars;
		$n = ! empty( $q['exact'] ) ? '' : '%';
		$search = '';
		$searchand = '';

		foreach( (array) $q['search_terms'] as $term ) {
			$term = esc_sql( self::esc_like( $term ) );
			$search.= "{$searchand}(($wpdb->posts.post_title LIKE '{$n}{$term}{$n}'))";
			$searchand = ' AND ';
		}

		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
		}

		return $search;
	}

	/**
	 * Unsupress posts search filters.
	 *
	 * The 'posts_search' filter was disabled for internal link searches in
	 * https://core.trac.wordpress.org/ticket/35594
	 *
	 * @since 1.3.0
	 */
	public static function unsupress_posts_search_filters( $args ) {
		$args['suppress_filters'] = false;
		return $args;
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
	 * @since 1.1.0
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
				$results = array_merge( $results, $pre_results );
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
				$future_status_object = get_post_status_object( 'future' );
				$private_status_object = get_post_status_object( 'private' );

				foreach( $posts as $key => $post ) {
					if ( 'future' == get_post_status( $post['ID'] ) ) {
						$posts[ $key ]['info'] = $future_status_object->label;
					} elseif ( 'private' == get_post_status( $post['ID'] ) ) {
						$posts[ $key ]['info'] .= ' (' . $private_status_object->label . ')';
					}
				}

				$results = array_merge( $results, $posts );
			}

			if ( 'yes' === Better_Internal_Link_Search_Settings::get_settings( 'include_term_results' ) ) {
				// Search for matching term archives.
				$search = '%' . self::esc_like( $s ) . '%';
				$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.term_id, t.name, tt.taxonomy
					FROM $wpdb->terms t
					INNER JOIN $wpdb->term_taxonomy tt ON t.term_id=tt.term_id
					WHERE t.name LIKE %s
					ORDER BY name ASC", $search ) );

				if ( $terms ) {
					foreach ( $terms as $term ) {
						$taxonomy = get_taxonomy( $term->taxonomy );

						if ( isset( $taxonomy->query_var ) ) {
							$results[] = array(
								'title'     => trim( esc_html( strip_tags( $term->name ) ) ),
								'permalink' => get_term_link( (int) $term->term_id, $term->taxonomy ),
								'info'      => $taxonomy->labels->singular_name,
							);
						}
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
	 * Javascript to automatically search for text selected in the editor.
	 *
	 * Inserts any text selected in the editor into the search field in the
	 * "Insert/edit link" popup when the link button in the toolbar is
	 * clicked. Automatically executes a search request and returns the
	 * results.
	 */
	public static function enqueue_editor_assets() {
		wp_enqueue_script(
			'better-internal-link-search-internal-link-manager',
			BETTER_INTERNAL_LINK_SEARCH_URL . 'js/internal-link-manager.js',
			array( 'jquery', 'underscore', 'wplink' )
		);

		wp_localize_script(
			'better-internal-link-search-internal-link-manager',
			'BilsSettings',
			Better_Internal_Link_Search_Settings::get_settings()
		);

		add_action( 'after_wp_tiny_mce', array( __CLASS__, 'print_editor_styles' ), 1 );
	}

	/**
	 * Prints the CSS needed
	 */
	public static function print_editor_styles() {
		?>
		<style type="text/css">
		#wp-link .item-description{ display: block; clear:both; padding: 3px 0 0 10px;}
		</style>
		<?php
	}

	/**
	 * Internal link shortcuts.
	 *
	 * A couple of basic shortcuts for easily linking to the home url and site
	 * url. Also gives plugins the ability to add more shortcuts.
	 *
	 * @since 1.1.0
	 */
	public static function get_shortcuts() {
		$shortcuts = apply_filters( 'better_internal_link_search_shortcuts', array(
			'home' => array(
				'title'     => __( 'Home', 'better-internal-link-search' ),
				'permalink' => home_url( '/' ),
			),
			'siteurl' => array(
				'title'     => __( 'Site URL', 'better-internal-link-search' ),
				'permalink' => site_url( '/' ),
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
	 * @since 1.1.0
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
	 * Search modifier help.
	 *
	 * Intercepts a request for '-help' and displays any modifiers that have
	 * been added via the filter.
	 *
	 * @since 1.1.0
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
	 * Upgrade plugin settings.
	 *
	 * The plugin version is saved as a different option so that updates to
	 * the settings option don't stomp on it.
	 *
	 * @since 1.1.2
	 */
	public static function upgrade() {
		$saved_version  = get_option( 'better_internal_link_search_version' );
		$update_version = false;

		if ( ! $saved_version || version_compare( $saved_version, '1.1.2', '<' ) ) {
			$update_version = true;
			update_option( 'better_internal_link_search', array( 'automatically_search_selection' => 'yes' ) );
		}

		if ( ! $saved_version || version_compare( $saved_version, '1.2.7', '<' ) ) {
			$update_version = true;
			$settings = Better_Internal_Link_Search_Settings::get_settings();
			$settings['include_term_results'] = 'yes';
			update_option( 'better_internal_link_search', $settings );
		}

		if ( $update_version ) {
			// Update saved version number.
			$plugin_data = get_plugin_data( __FILE__ );
			update_option( 'better_internal_link_search_version', $plugin_data['Version'] );
		}
	}

	/**
	 * Escape LIKE special characters.
	 *
	 * @since 1.2.8
	 *
	 * @see wpdb::esc_like()
	 * @link https://make.wordpress.org/core/2014/06/20/like_escape-is-deprecated-in-wordpress-4-0/
	 */
	public static function esc_like( $text ) {
		global $wpdb;

		if ( method_exists( $wpdb, 'esc_like' ) ) {
			return $wpdb->esc_like( $text );
		}

		return addcslashes( $text, '_%\\' );
	}
}
